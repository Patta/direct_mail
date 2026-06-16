<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Middleware\JumpurlController;
use DirectMailTeam\DirectMail\Middleware\SimulateUsergroup;

return [
    'frontend' => [
        'direct-mail/jumpurl-controller' => [
            'target' => JumpurlController::class,
            'before' => [
                'friends-of-typo3/jumpurl',
            ],
        ],
        'direct-mail/simulate-usergroup' => [
            'target' => SimulateUsergroup::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
