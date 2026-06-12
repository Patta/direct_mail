<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Middleware\JumpurlController;

return [
    'frontend' => [
        'direct-mail/jumpurl-controller' => [
            'target' => JumpurlController::class,
            'before' => [
                'friends-of-typo3/jumpurl',
            ],
        ],
    ],
];
