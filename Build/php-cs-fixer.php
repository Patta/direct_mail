<?php

use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$config->getFinder()
    ->in(__DIR__ . '/../Classes')
    ->in(__DIR__ . '/../Configuration')
    ->append([__DIR__ . '/../ext_localconf.php'])
;
return $config;
