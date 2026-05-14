<?php

namespace DirectMailTeam\DirectMail\Scheduler;

use DirectMailTeam\DirectMail\Repository\SysDmailMaillogRepository;
use DirectMailTeam\DirectMail\Utility\ReadmailUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Minimal wrapper to replace Fetch\Message so processBounceMail() stays untouched.
 */
class ImapMessage
{
    private $stream;
    private int $msgNo;

    public function __construct($stream, int $msgNo)
    {
        $this->stream = $stream;
        $this->msgNo  = $msgNo;
    }

    public function getSubject(): string
    {
        $header = imap_headerinfo($this->stream, $this->msgNo);
        return isset($header->subject) ? imap_utf8($header->subject) : '';
    }

    public function getMessageBody(): string
    {
        $structure = imap_fetchstructure($this->stream, $this->msgNo);
        if ($structure === false) {
            return '';
        }

        if (!isset($structure->parts)) {
            $body = imap_fetchbody($this->stream, $this->msgNo, '1');
            return $this->decodeData($body, $structure->encoding);
        }

        foreach ($structure->parts as $index => $part) {
            $subtype = strtolower($part->subtype ?? '');
            if ($part->type === 0 && in_array($subtype, ['plain', 'html'])) {
                $body = imap_fetchbody($this->stream, $this->msgNo, (string)($index + 1));
                return $this->decodeData($body, $part->encoding);
            }
        }

        return imap_body($this->stream, $this->msgNo);
    }

    public function getAttachments(): ?array
    {
        $attachments = [];
        $structure   = imap_fetchstructure($this->stream, $this->msgNo);

        if ($structure === false || !isset($structure->parts)) {
            return null;
        }

        foreach ($structure->parts as $partNo => $part) {
            if ($part->ifdisposition && strtolower($part->disposition) === 'attachment') {
                $data = imap_fetchbody($this->stream, $this->msgNo, (string)($partNo + 1));
                if ($data === false) {
                    continue;
                }
                $attachments[] = new ImapAttachment($this->decodeData($data, $part->encoding));
            }
        }

        return empty($attachments) ? null : $attachments;
    }

    public function delete(): void
    {
        imap_delete($this->stream, (string)$this->msgNo);
    }

    public function setFlag(string $flag): void
    {
        imap_setflag_full($this->stream, (string)$this->msgNo, '\\' . $flag);
    }

    private function decodeData($data, int $encoding): string
    {
        if (!is_string($data)) {
            return '';
        }

        return match ($encoding) {
            3 => base64_decode($data),
            4 => quoted_printable_decode($data),
            default => $data,
        };
    }
}

/**
 * Minimal attachment wrapper to replace Fetch\Attachment.
 */
class ImapAttachment
{
    private string $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}

/**
 * @deprecated will be removed in TYPO3 v12.0. Use AnalyzeBounceMailCommand instead.
 */
class AnalyzeBounceMail extends AbstractTask
{
    /**
     * url of the mail server
     * @var string
     */
    protected $server;

    /**
     * Port number of the mail server
     * @var int
     */
    protected $port;

    /**
     * Username to use to authenticate
     * @var string
     */
    protected $user;

    /**
     * Password of the user
     * @var string
     */
    protected $password;

    /**
     * Mailserver type (imap or pop3)
     * @var string
     */
    protected $service;

    /**
     * Maximum number of bounce mail to be processed
     * @var int
     */
    protected $maxProcessed;

    public function getPort(): int { return $this->port; }
    public function setPort($port): void { $this->port = $port; }
    public function getUser(): string { return $this->user; }
    public function setUser($user): void { $this->user = $user; }
    public function getPassword(): string { return $this->password; }
    public function setPassword($password): void { $this->password = $password; }
    public function getService(): string { return $this->service; }
    public function setService($service): void { $this->service = $service; }
    public function getServer() { return $this->server; }
    public function setServer($server): void { $this->server = $server; }
    public function getMaxProcessed() { return $this->maxProcessed; }
    public function setMaxProcessed($maxProcessed): void { $this->maxProcessed = (int)$maxProcessed; }

    /**
     * Execute the scheduler task.
     * @return bool
     */
    public function execute(): bool
    {
        trigger_error(
            'will be removed in TYPO3 v12.0. Use AnalyzeBounceMailCommand instead.',
            E_USER_DEPRECATED
        );

        $stream = $this->connectMailServer();
        if ($stream === false) {
            return false;
        }

        $unseenMsgNos = imap_search($stream, 'UNSEEN');
        if ($unseenMsgNos !== false) {
            if ($this->maxProcessed > 0) {
                $unseenMsgNos = array_slice($unseenMsgNos, 0, $this->maxProcessed);
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

        return true;
    }

    /**
     * Process the bounce mail
     * @param ImapMessage $message
     * @return bool true if bounce mail can be parsed, else false
     */
    private function processBounceMail($message): bool
    {
        /** @var ReadmailUtility $readMail */
        $readMail = GeneralUtility::makeInstance(ReadmailUtility::class);

        $attachmentArray = $message->getAttachments();
        $midArray        = [];

        if (is_array($attachmentArray)) {
            foreach ($attachmentArray as $v => $attachment) {
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

        $row = GeneralUtility::makeInstance(SysDmailMaillogRepository::class)
            ->selectForAnalyzeBounceMail($midArray['rid'], $midArray['rtbl'], $midArray['mid']);

        if (!empty($row)) {
            /** @var \TYPO3\CMS\Core\Database\Connection $connection */
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_dmail_maillog');
            try {
                $midArray['email'] = $row['email'];
                $insertFields = [
                    'tstamp'          => $this->getEXEC_TIME(),
                    'response_type'   => -127,
                    'mid'             => (int)$midArray['mid'],
                    'rid'             => (int)$midArray['rid'],
                    'email'           => $midArray['email'],
                    'rtbl'            => $midArray['rtbl'],
                    'return_content'  => serialize($cp),
                    'return_code'     => (int)$cp['reason'],
                ];
                $connection->insert('sys_dmail_maillog', $insertFields);
                $sql_insert_id = $connection->lastInsertId();
                return (bool)$sql_insert_id;
            } catch (\Doctrine\DBAL\DBALException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Create connection to mail server via native imap_open().
     * Returns IMAP stream or false on error.
     *
     * @return resource|false
     */
    private function connectMailServer()
    {
        $mailbox = '{' . $this->server . ':' . (int)$this->port . '/' . $this->service . '/ssl}INBOX';
        $stream  = @imap_open($mailbox, $this->user, $this->password, 0, 1);

        if ($stream === false) {
            $errors = imap_errors();
            $errorMsg = is_array($errors) ? implode(', ', $errors) : 'Unknown IMAP error';
            // log silently – original code also returned false without logging here
            error_log('DirectMail AnalyzeBounceMail: IMAP connection failed: ' . $errorMsg);
            return false;
        }

        return $stream;
    }

    /**
     * @return int
     */
    private function getEXEC_TIME(): int
    {
        return GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
    }
}
