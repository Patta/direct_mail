<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Scheduler;

use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Password-masking wrapper for the `directmail:analyzebouncemail` command's task-list information.
 *
 * The TCA override maps this class to the command's scheduler task type; it does not
 * register a separate task type.
 */
class AnalyzeBounceMailTaskListPasswordMaskingWrapper extends ExecuteSchedulableCommandTask
{
    private const string PASSWORD_ARGUMENT = '--password';

    /**
     * Hides the configured password from the Scheduler task list.
     */
    #[\Override]
    public function getAdditionalInformation(): string
    {
        return $this->maskPasswordInTaskList(parent::getAdditionalInformation());
    }

    private function maskPasswordInTaskList(string $information): string
    {
        $pattern = '/(' . preg_quote(self::PASSWORD_ARGUMENT, '/') . '(?:=|\s+))(?:"[^"]*"|\'[^\']*\'|[^\s<]+)/';
        return preg_replace_callback(
            $pattern,
            static fn(array $matches): string => $matches[1] . '********',
            $information
        ) ?? $information;
    }
}
