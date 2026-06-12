<?php

declare(strict_types=1);

use DirectMailTeam\DirectMail\Module\ConfigurationController;

return [
    'directmail_configuration_update' => [
        'path' => '/directmail/configuration',
        'methods' => ['POST'],
        'target' => ConfigurationController::class . '::updateConfigAction',
    ],
];
