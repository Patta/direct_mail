<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Utility;

use FoT3\Rdct\Redirects;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RdctUtility
{
    public function installed(): bool
    {
        return class_exists('\FoT3\Rdct\Redirects');
    }

    public function getRedirects(): Redirects
    {
        return GeneralUtility::makeInstance(Redirects::class);
    }
}
