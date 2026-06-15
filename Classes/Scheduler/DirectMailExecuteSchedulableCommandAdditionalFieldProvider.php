<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Scheduler;

use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandAdditionalFieldProvider;

/**
 * Extends the default scheduler field provider to identify and mask password related
 * command options by setting their input type to 'password'.
 */
class DirectMailExecuteSchedulableCommandAdditionalFieldProvider extends ExecuteSchedulableCommandAdditionalFieldProvider
{
    #[\Override]
    protected function renderOptionField(InputOption $option, bool $enabled, string $currentValue): array
    {
        $field = parent::renderOptionField($option, $enabled, $currentValue);

        if ($this->isPasswordOption($option) && $field['optionValueField'] !== '') {
            $field['optionValueField'] = str_replace(
                'type="text"',
                'type="password"',
                $field['optionValueField']
            );
        }

        return $field;
    }

    private function isPasswordOption(InputOption $option): bool
    {
        $name = strtolower($option->getName());
        $description = strtolower((string)$option->getDescription());

        if (str_contains($name, 'password')) {
            return true;
        }

        return str_contains($description, 'password');
    }
}
