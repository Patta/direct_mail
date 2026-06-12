<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Utility;

/**
 * Code from:
 *  TYPO3\CMS\Scheduler\Controller\SchedulerModuleController listTasksAction
 *  TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository getGroupedTasks
 */

use DirectMailTeam\DirectMail\Repository\TempRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

class SchedulerUtility
{
    public function __construct(
        protected readonly TaskSerializer $taskSerializer,
        protected readonly TaskService $taskService,
    ) {}

    protected function isValidTaskObject($task): bool
    {
        return (new TaskValidator())->isValid($task);
    }

    public function getDMTable(): array
    {
        $registeredClasses = $this->taskService->getAvailableTaskTypes();

        $tasks = GeneralUtility::makeInstance(TempRepository::class)->getDMTasks();

        $taskGroupsWithTasks = [];
        $errorClasses = [];

        if (is_array($tasks) && count($tasks) > 0) {
            foreach ($tasks as $task) {
                $taskData = [
                    'uid' => (int)$task['uid'],
                    'lastExecutionTime' => (int)$task['lastexecution_time'],
                    'lastExecutionContext' => $task['lastexecution_context'],
                    'errorMessage' => '',
                    'description' => $task['description'],
                ];

                try {
                    $taskObject = $this->taskSerializer->deserialize($task['serialized_task_object']);
                } catch (InvalidTaskException $e) {
                    $taskData['errorMessage'] = $e->getMessage();
                    $taskData['class'] = $this->taskSerializer->extractClassName($task['serialized_task_object']);
                    $errorClasses[] = $taskData;
                    continue;
                }

                $taskClass = $this->taskSerializer->resolveClassName($taskObject);
                $taskData['class'] = $taskClass;

                if (!$this->isValidTaskObject($taskObject)) {
                    $taskData['errorMessage'] = 'The class ' . $taskClass . ' is not a valid task';
                    $errorClasses[] = $taskData;
                    continue;
                }

                if (!isset($registeredClasses[$taskClass])) {
                    $taskData['errorMessage'] = 'The class ' . $taskClass . ' is not a registered task';
                    $errorClasses[] = $taskData;
                    continue;
                }

                if ($taskObject instanceof ProgressProviderInterface) {
                    $taskData['progress'] = round((float)$taskObject->getProgress(), 2);
                }

                $taskData['classTitle'] = $registeredClasses[$taskClass]['title'];
                $taskData['classExtension'] = $registeredClasses[$taskClass]['extension'];
                $taskData['additionalInformation'] = $taskObject->getAdditionalInformation();
                $taskData['disabled'] = (bool)$task['disable'];
                $taskData['isRunning'] = !empty($task['serialized_executions']);
                $taskData['nextExecution'] = (int)$task['nextexecution'];
                $taskData['type'] = 'single';
                $taskData['frequency'] = '';

                if ($taskObject->getType() === AbstractTask::TYPE_RECURRING) {
                    $taskData['type'] = 'recurring';
                    $taskData['frequency'] = $taskObject->getExecution()->getCronCmd() ?: $taskObject->getExecution()->getInterval();
                }

                $taskData['multiple'] = (bool)$taskObject->getExecution()->getMultiple();
                $taskData['lastExecutionFailure'] = false;

                if (!empty($task['lastexecution_failure'])) {
                    $taskData['lastExecutionFailure'] = true;
                    $exceptionArray = @unserialize($task['lastexecution_failure']);
                    $taskData['lastExecutionFailureCode'] = '';
                    $taskData['lastExecutionFailureMessage'] = '';

                    if (is_array($exceptionArray)) {
                        $taskData['lastExecutionFailureCode'] = $exceptionArray['code'];
                        $taskData['lastExecutionFailureMessage'] = $exceptionArray['message'];
                    }
                }

                // If a group is deleted or no group is set it needs to go into "not assigned groups"
                $groupIndex = $task['isTaskGroupDeleted'] === 1 || $task['isTaskGroupDeleted'] === null ? 0 : (int)$task['task_group'];

                if (!isset($taskGroupsWithTasks[$groupIndex])) {
                    $taskGroupsWithTasks[$groupIndex] = [
                        'tasks' => [],
                        'groupName' => $task['taskGroupName'],
                        'groupUid' => $task['taskGroupId'],
                        'groupDescription' => $task['taskGroupDescription'],
                        'groupHidden' => $task['isTaskGroupHidden'],
                    ];
                }

                $taskGroupsWithTasks[$groupIndex]['tasks'][] = $taskData;
            }
        }

        return ['taskGroupsWithTasks' => $taskGroupsWithTasks, 'errorClasses' => $errorClasses];
    }
}
