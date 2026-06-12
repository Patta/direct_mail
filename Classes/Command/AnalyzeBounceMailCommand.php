<?php

namespace DirectMailTeam\DirectMail\Command;

use DirectMailTeam\DirectMail\Repository\SysDmailMaillogRepository;
use DirectMailTeam\DirectMail\Utility\ReadmailUtility;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Minimal wrapper to replace Fetch\Message so processBounceMail() stays untouched.
 */
class ImapMessage
{
    private bool $deleted = false;

    public function __construct(private $stream, private readonly int $msgNo) {}

    public function getSubject(): string
    {
        $header = imap_headerinfo($this->stream, $this->msgNo);
        return is_object($header) && isset($header->subject) ? imap_utf8($header->subject) : '';
    }

    public function getMessageBody(): string
    {
        return $this->getBodyPart();
    }

    public function getAttachments(): ?array
    {
        $attachments = [];
        $structure   = imap_fetchstructure($this->stream, $this->msgNo);

        if ($structure === false || !isset($structure->parts)) {
            return null;
        }

        foreach ($structure->parts as $partNo => $part) {
            if ($part->ifdisposition && strtolower((string)$part->disposition) === 'attachment') {
                $data = imap_fetchbody($this->stream, $this->msgNo, (string)($partNo + 1));
                if ($data === false) {
                    continue;
                }
                $data = $this->decodeData($data, $part->encoding);
                $attachments[] = new ImapAttachment($data);
            }
        }

        return $attachments === [] ? null : $attachments;
    }

    public function delete(): void
    {
        imap_delete($this->stream, (string)$this->msgNo);
        $this->deleted = true;
    }

    public function setFlag(string $flag): void
    {
        imap_setflag_full($this->stream, (string)$this->msgNo, '\\' . $flag);
    }

    private function getBodyPart(): string
    {
        $structure = imap_fetchstructure($this->stream, $this->msgNo);
        if ($structure === false) {
            return '';
        }

        // simple message without parts
        if (!isset($structure->parts)) {
            $body = imap_fetchbody($this->stream, $this->msgNo, '1');
            if ($body === false) {
                return '';
            }
            return $this->decodeData($body, $structure->encoding);
        }

        // multipart: find first text/plain or html part
        foreach ($structure->parts as $index => $part) {
            $subtype = strtolower($part->subtype ?? '');
            $type    = $part->type ?? 0;
            if ($type === 0 && in_array($subtype, ['plain', 'html'])) {
                $body = imap_fetchbody($this->stream, $this->msgNo, (string)($index + 1));
                if ($body === false) {
                    continue;
                }
                return $this->decodeData($body, $part->encoding);
            }
        }

        $body = imap_body($this->stream, $this->msgNo);
        return is_string($body) ? $body : '';
    }

    private function decodeData($data, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode((string)$data),
            4 => quoted_printable_decode((string)$data),
            default => $data,
        };
    }
}

/**
 * Minimal attachment wrapper to replace Fetch\Attachment.
 */
class ImapAttachment
{
    public function __construct(private readonly string $data) {}

    public function getData(): string
    {
        return $this->data;
    }
}

class AnalyzeBounceMailCommand extends Command
{
    private ?LanguageService $languageService = null;

    public function configure(): void
    {
        $this->setDescription('This command will get bounce mail from the configured mailbox')
            ->addOption('server', 's', InputOption::VALUE_REQUIRED, 'Server URL/IP')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port number')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Username')
            ->addOption('password', 'pw', InputOption::VALUE_REQUIRED, 'Password')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type of mailserver (imap or pop3)')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of bounce mail to be processed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $this->setLanguageService();

        $server   = '';
        $port     = 0;
        $user     = '';
        $password = '';
        $type     = '';
        $count    = 0;

        if (!extension_loaded('imap')) {
            $io->error($this->languageService->sL('LLL:EXT:direct_mail/Resources/Private/Language/locallang_mod2-6.xlf:scheduler.bounceMail.phpImapError'));
            return Command::FAILURE;
        }

        if ($input->getOption('server')) {
            $server   = $input->getOption('server');
        }
        if ($input->getOption('port')) {
            $port     = (int)$input->getOption('port');
        }
        if ($input->getOption('user')) {
            $user     = $input->getOption('user');
        }
        if ($input->getOption('password')) {
            $password = $input->getOption('password');
        }
        if ($input->getOption('type')) {
            $type = $input->getOption('type');
            if (!in_array($type, ['imap', 'pop3'])) {
                $io->warning('Type: only imap or pop3');
                return Command::FAILURE;
            }
        }
        if ($input->getOption('count')) {
            $count = (int)$input->getOption('count');
        }

        $stream = $this->connectMailServer($server, $port, $type, $user, $password, $io);
        if ($stream === false) {
            return Command::FAILURE;
        }

        // search for UNSEEN messages
        $unseenMsgNos = imap_search($stream, 'UNSEEN');
        if ($unseenMsgNos !== false) {
            if ($count > 0) {
                $unseenMsgNos = array_slice($unseenMsgNos, 0, $count);
            }
            foreach ($unseenMsgNos as $msgNo) {
                $message = new ImapMessage($stream, $msgNo);
                if ($this->processBounceMail($message)) {
                    $message->delete();
                } else {
                    $message->setFlag('SEEN');
                }
            }
        }

        imap_expunge($stream);
        imap_close($stream);

        return Command::SUCCESS;
    }

    /**
     * Process the bounce mail
     * @param ImapMessage $message
     * @return bool
     */
    private function processBounceMail($message): bool
    {
        /** @var ReadmailUtility $readMail */
        $readMail = GeneralUtility::makeInstance(ReadmailUtility::class);

        $attachmentArray = $message->getAttachments();
        $midArray        = [];

        if (is_array($attachmentArray)) {
            foreach ($attachmentArray as $attachment) {
                $bouncedMail = $attachment->getData();
                $midArray    = $readMail->find_XTypo3MID($bouncedMail);
                if (empty($midArray) === false) {
                    break;
                }
            }
        } else {
            $midArray = $readMail->find_XTypo3MID($message->getMessageBody());
        }

        if (empty($midArray)) {
            return false;
        }

        $cp = $readMail->analyseReturnError($message->getMessageBody());

        $sysDmailMaillogRepository = GeneralUtility::makeInstance(SysDmailMaillogRepository::class);
        $row = $sysDmailMaillogRepository->selectForAnalyzeBounceMail($midArray['rid'], $midArray['rtbl'], $midArray['mid']);

        if (!empty($row)) {
            $midArray['email'] = $row['email'];
            try {
                return $sysDmailMaillogRepository->analyzeBounceMailAddToMailLog(
                    $this->getTimestampFromAspect(),
                    $midArray,
                    (int)$cp['reason'],
                    serialize($cp)
                );
            } catch (DBALException) {
                return false;
            }
        }

        return false;
    }

    /**
     * Connect to mail server via native PHP imap_open().
     * Returns IMAP stream resource or false on error.
     *
     * @return resource|false
     */
    private function connectMailServer(string $server, int $port, string $type, string $user, string $password, SymfonyStyle $io)
    {
        // Build IMAP mailbox string: {mail.example.com:993/imap/ssl}INBOX
        $mailbox = '{' . $server . ':' . $port . '/' . $type . '/ssl}INBOX';

        // suppress errors so we can handle them ourselves
        $stream = @imap_open($mailbox, $user, $password, 0, 1);

        if ($stream === false) {
            $errors = imap_errors();
            $errorMsg = is_array($errors) ? implode(', ', $errors) : 'Unknown IMAP error';
            $io->error(
                $this->languageService->sL('LLL:EXT:direct_mail/Resources/Private/Language/locallang_mod2-6.xlf:scheduler.bounceMail.dataVerification')
                . $errorMsg
            );
            return false;
        }

        return $stream;
    }

    private function getTimestampFromAspect(): int
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return $context->getPropertyFromAspect('date', 'timestamp');
    }

    private function setLanguageService(): void
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $this->languageService  = $languageServiceFactory->create('en');
    }
}
