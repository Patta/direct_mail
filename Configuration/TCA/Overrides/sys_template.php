<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

ExtensionManagementUtility::addStaticFile('direct_mail', 'Configuration/TypoScript/boundaries/', 'Direct Mail Content Boundaries');
ExtensionManagementUtility::addStaticFile('direct_mail', 'Configuration/TypoScript/plaintext/', 'Direct Mail Plain text');
