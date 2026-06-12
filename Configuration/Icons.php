<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/Icon/Index.html
return [
    // icon identifier
    'directmail-attachment' => [
        // icon provider class
        'provider' => BitmapIconProvider::class,
        // the source bitmap file
        'source' => 'EXT:direct_mail/Resources/Public/Icons/attach.png',
    ],
    'directmail-dmail' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/dmail.png',
    ],
    'directmail-dmail-list' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/dmail_list.png',
    ],
    'directmail-folder' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/ext_icon_dmail_folder.png',
    ],
    'directmail-category' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/icon_tx_directmail_category.png',
    ],
    'directmail-mail' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/mail.png',
    ],
    'directmail-mailgroup' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/mailgroup.png',
    ],
    'directmail-page-modules-dmail' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/modules_dmail.png',
    ],
    'directmail-page-modules-dmail-inactive' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/modules_dmail__h.png',
    ],
    'directmail-dmail-new' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/newmail.png',
    ],
    'directmail-dmail-preview-html' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/preview_html.png',
    ],
    'directmail-dmail-preview-text' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Icons/preview_txt.png',
    ],
    'directmail-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail.svg',
    ],
    'directmail-module-configuration' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail-configuration.svg',
    ],
    'directmail-module-directmail' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail-directmail.svg',
    ],
    'directmail-module-mailer-engine' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail-mailer-engine.svg',
    ],
    'directmail-module-recipient-list' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail-recipient-list.svg',
    ],
    'directmail-module-importer' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail-importer.svg',
    ],
    'directmail-module-statistics' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:direct_mail/Resources/Public/Images/module-directmail-statistics.svg',
    ],

    /**
    'mysvgicon' => [
        // icon provider class
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        // the source SVG for the SvgIconProvider
        'source' => 'EXT:my_extension/Resources/Public/Icons/mysvg.svg',
    ],
    'myfontawesomeicon' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
        // the fontawesome icon name
        'name' => 'spinner',
        // all icon providers provide the possibility to register an icon that spins
        'spinning' => true,
    ],
*/
];
