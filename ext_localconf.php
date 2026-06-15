<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Scheduler\AnalyzeBounceMail;
use DirectMailTeam\DirectMail\Scheduler\DirectMailExecuteSchedulableCommandAdditionalFieldProvider;
use DirectMailTeam\DirectMail\Scheduler\MailFromDraft;
use DirectMailTeam\DirectMail\Scheduler\MailFromDraftAdditionalFields;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

defined('TYPO3') || die();

// https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/BestPractises/ConfigurationFiles.html
(function () {
    // Register hook for simulating a user group
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']['direct_mail'] = 'DirectMailTeam\\DirectMail\\Hooks\\TypoScriptFrontendController->simulateUsergroup';

    // Get extension configuration so we can use it here:
    $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('direct_mail');

    /**
     * Language of the cron task:
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['cronLanguage'] = $extConf['cronLanguage'] ?: 'en';

    /**
     * Number of messages sent per cycle of the cron task:
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['sendPerCycle'] = $extConf['sendPerCycle'] ?: 50;

    /**
     * Default recipient field list:
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['defaultRecipFields'] = 'uid,name,title,email,phone,www,address,company,city,zip,country,fax,firstname,first_name,last_name';

    /**
     * Additional DB fields of the recipient:
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['addRecipFields'] = $extConf['addRecipFields'];

    /**
     * Admin email for sending the cronjob error message
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['adminEmail'] = $extConf['adminEmail'];

    /**
     * Direct Mail send a notification every time a job starts or ends
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['notificationJob'] = $extConf['notificationJob'];

    /**
     * Use HTTP to fetch contents
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['UseHttpToFetch'] = $extConf['UseHttpToFetch'];

    /**
     * Use implicit port in URL for fetching Newsletter-Content: Even if your TYPO3 Backend is on a non-standard-port,
     * the URL for fetching the newsletter contents from one of your Frontend-Domains will not use the PORT you are using to access your TYPO3 Backend,
     * but use implicit port instead (e.g. no explicit port in URL)
     */
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['UseImplicitPortToFetch'] = $extConf['UseImplicitPortToFetch'];

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['SSLVerify'] = $extConf['SSLVerify'] ?? 0;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['SSLVerifyPeer'] = $extConf['SSLVerifyPeer'] ?? 1;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail']['SSLVerifyPeerName'] = $extConf['SSLVerifyPeerName'] ?? 1;

    /**
     * Registering scheduler tasks
     */
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][MailFromDraft::class] = [
        'extension' => 'direct_mail',
        'title' => 'Direct Mail: Create Mail from Draft',
        'description' => 'This task allows you to select a DirectMail draft that gets copied and then sent to the. This allows automatic (periodic) sending of the same TYPO3 page.',
        'additionalFields' => MailFromDraftAdditionalFields::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][AnalyzeBounceMail::class] = [
        'extension' => 'direct_mail',
        'title' => 'Direct Mail: Wrapper for "directmail:analyzebouncemail" Command',
        'description' => 'This is a wrapper task for the `directmail:analyzebouncemail` command, to mask the password argument in the task overview list. When creating this task, ensure that the `directmail:analyzebouncemail` command is selected from the dropdown list.',
        'additionalFields' => DirectMailExecuteSchedulableCommandAdditionalFieldProvider::class,
    ];

    /**
     * Extend scheduler command task's additional field provider
     */
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ExecuteSchedulableCommandTask::class]['additionalFields'] = DirectMailExecuteSchedulableCommandAdditionalFieldProvider::class;

    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.3/Feature-100232-LoadAdditionalStylesheetsInTYPO3Backend.html
    $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['direct_mail'] = 'EXT:direct_mail/Resources/Public/StyleSheets/';
})();
