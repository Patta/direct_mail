<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Scheduler;

use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
  * This is a wrapper task for the 'directmail:analyzebouncemail' command, to mask the password argument in the task overview list.
 */
class AnalyzeBounceMail extends ExecuteSchedulableCommandTask
{
    /**
     * @var string
     *
     * @todo Automate command selection in task form.
     */
    protected $commandIdentifier = 'directmail:analyzebouncemail';

    /**
     * @var string
     */
    protected $passwordArgumentName = '--password';

    /**
     * Overrides the parent method to provide additional information for the task list.
     *
     * It retrieves the default command execution details and then specifically
     * masks the value of the configured password argument (`--password=`)
     * with asterisks for display in the Scheduler task list.
     *
     * @return string
     */
    #[\Override]
    public function getAdditionalInformation(): string
    {
        $info = parent::getAdditionalInformation();
        if (str_contains($info, $this->passwordArgumentName)) {
            $pattern = '/(' . preg_quote($this->passwordArgumentName, '/') . '=)([\S]+)/';
            $info = preg_replace_callback($pattern, fn($matches) => $matches[1] . '********', $info);
        }

        return $info;
    }
}
