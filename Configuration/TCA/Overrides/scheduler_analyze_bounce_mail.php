<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Scheduler\AnalyzeBounceMailTaskListPasswordMaskingWrapper;

defined('TYPO3') || die();

$commandIdentifier = 'directmail:analyzebouncemail';

/*
 * This identifier matches the schedulable command in Configuration/Services.yaml.
 * EXT:scheduler adds it to the task-type selector, so customize that type
 * instead of registering a second, separately selectable task.
 *
 * Docs: https://docs.typo3.org/c/typo3/cms-scheduler/14.3/en-us/DevelopersGuide/CreatingTasks/Index.html
 * Core: https://github.com/TYPO3/typo3/blob/14.3/typo3/sysext/scheduler/Classes/EventListener/AddSchedulableCommandsAsNativeTaskTypes.php
 */
$GLOBALS['TCA']['tx_scheduler_task']['types'][$commandIdentifier]['columnsOverrides']['parameters']['label']
    = 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.schedulableCommand.command_configuration';
$GLOBALS['TCA']['tx_scheduler_task']['types'][$commandIdentifier]['columnsOverrides']['parameters']['description']
    = 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.schedulableCommand.command_configuration.description';

// Registered in ext_localconf.php; renders the password option as a password field in the task form.
$GLOBALS['TCA']['tx_scheduler_task']['types'][$commandIdentifier]['columnsOverrides']['parameters']['config']['renderType']
    = 'analyzeBounceMailCommandConfiguration';

/*
 * TaskService uses taskOptions.className to instantiate the command task wrapper.
 * This mapping depends on an internal TYPO3 implementation detail; verify compatibility when upgrading TYPO3.
 * Core: https://github.com/TYPO3/typo3/blob/14.3/typo3/sysext/scheduler/Classes/Service/TaskService.php
 */
$GLOBALS['TCA']['tx_scheduler_task']['types'][$commandIdentifier]['taskOptions']['className']
    = AnalyzeBounceMailTaskListPasswordMaskingWrapper::class;
