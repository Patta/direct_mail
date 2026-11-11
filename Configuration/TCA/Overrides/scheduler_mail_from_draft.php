<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Scheduler\MailFromDraft;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

ExtensionManagementUtility::addTCAcolumns(
    'tx_scheduler_task',
    [
        // This TCA field stores the draft UID previously held in MailFromDraft::$draftUid.
        'module_sys_dmail_draft_uid' => [
            'label' => 'LLL:EXT:direct_mail/Resources/Private/Language/locallang_tca.xlf:scheduler.mailFromDraft.draft',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'itemsProcFunc' => MailFromDraft::class . '->getAvailableDrafts',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
    ]
);

ExtensionManagementUtility::addRecordType(
    new SelectItem(
        type: 'select',
        label: 'LLL:EXT:direct_mail/Resources/Private/Language/locallang_tca.xlf:scheduler.mailFromDraft.title',
        value: MailFromDraft::class,
        icon: 'mimetypes-x-tx_scheduler_task_group',
        group: 'direct_mail',
        description: 'LLL:EXT:direct_mail/Resources/Private/Language/locallang_tca.xlf:scheduler.mailFromDraft.description'
    ),
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            module_sys_dmail_draft_uid,
        --div--;core.form.tabs:timing,
            --palette--;;execution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [],
    '',
    'tx_scheduler_task'
);
