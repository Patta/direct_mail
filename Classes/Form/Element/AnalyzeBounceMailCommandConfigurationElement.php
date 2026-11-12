<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Form\Element;

use Symfony\Component\Console\Input\InputDefinition;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Scheduler\Form\Element\SchedulableCommandConfigurationElement;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Renders the bounce-mail command's password option as a password field in the scheduler form.
 *
 * This extends an internal TYPO3 FormEngine element and overrides its methods; verify compatibility when upgrading TYPO3.
 */
class AnalyzeBounceMailCommandConfigurationElement extends SchedulableCommandConfigurationElement
{
    #[\Override]
    protected function getCommandOptionFields(InputDefinition $inputDefinition, ExecuteSchedulableCommandTask $task): array
    {
        $fields = parent::getCommandOptionFields($inputDefinition, $task);
        if (isset($fields['options']['password'])) {
            $fields['options']['password']['isPassword'] = true;
        }
        return $fields;
    }

    #[\Override]
    protected function renderCommandConfiguration(array $fields, string $taskType, string $itemName): string
    {
        return $this->viewFactory->create(
            new ViewFactoryData(
                templateRootPaths: ['EXT:direct_mail/Resources/Private/Templates/Scheduler'],
                partialRootPaths: ['EXT:scheduler/Resources/Private/Partials'],
                layoutRootPaths: ['EXT:scheduler/Resources/Private/Layouts'],
                request: $this->data['request'],
                format: 'html',
            )
        )->assignMultiple([
            'taskType' => $taskType,
            'fields' => $fields,
            'itemName' => $itemName,
            'renderDebug' => $this->getBackendUser()->shallDisplayDebugInformation(),
        ])->render('AnalyzeBounceMailCommandConfiguration');
    }
}
