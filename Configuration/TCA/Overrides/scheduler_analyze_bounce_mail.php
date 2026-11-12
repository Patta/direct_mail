<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Scheduler\AnalyzeBounceMailTaskListPasswordMaskingWrapper;

defined('TYPO3') || die();

/*
 * TYPO3 exposes console commands as scheduler task types. This override
 * customizes the generated form, it does not define a separate scheduler task.
 *
 * Docs: https://docs.typo3.org/c/typo3/cms-scheduler/14.3/en-us/DevelopersGuide/CreatingTasks/Index.html
 * Core: https://github.com/TYPO3/typo3/blob/14.3/typo3/sysext/scheduler/Classes/EventListener/AddSchedulableCommandsAsNativeTaskTypes.php
 */
$commandIdentifier = 'directmail:analyzebouncemail';

// The FormEngine node registered in ext_localconf.php masks the command's password field.
$GLOBALS['TCA']['tx_scheduler_task']['types'][$commandIdentifier]['columnsOverrides']['parameters']['config']['renderType']
    = 'analyzeBounceMailCommandConfiguration';

/*
 * This wrapper inherits command execution and only changes the task-list display.
 * The taskOptions.className mapping relies on TYPO3 internals; verify compatibility when upgrading TYPO3.
 * Core: https://github.com/TYPO3/typo3/blob/14.3/typo3/sysext/scheduler/Classes/Service/TaskService.php
 */
$GLOBALS['TCA']['tx_scheduler_task']['types'][$commandIdentifier]['taskOptions']['className']
    = AnalyzeBounceMailTaskListPasswordMaskingWrapper::class;
