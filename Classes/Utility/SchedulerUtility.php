<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Utility;

/**
 * Builds the list of direct mail related scheduler tasks shown in the
 * mailer engine module.
 *
 * The task list itself is provided by the public scheduler API
 * (TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository::getGroupedTasks),
 * which already performs the (security hardened) deserialization of the task
 * objects. We only narrow the result down to the direct mail tasks and remap
 * the group keys to the names expected by the Fluid template.
 */

use DirectMailTeam\DirectMail\Repository\TempRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;

class SchedulerUtility
{
    public static function getDMTable(): array
    {
        $emptyTable = ['taskGroupsWithTasks' => [], 'errorClasses' => []];

        // UIDs of the direct mail scheduler tasks (precise filter on the serialized command).
        $dmTaskUids = array_map(
            intval(...),
            array_column(GeneralUtility::makeInstance(TempRepository::class)->getDMTasks(), 'uid')
        );

        if ($dmTaskUids === []) {
            return $emptyTable;
        }

        $groupedTasks = GeneralUtility::makeInstance(SchedulerTaskRepository::class)->getGroupedTasks();

        $taskGroupsWithTasks = [];
        foreach (($groupedTasks['taskGroupsWithTasks'] ?? []) as $groupIndex => $group) {
            $tasks = array_values(array_filter(
                $group['tasks'] ?? [],
                static fn(array $task): bool => in_array((int)$task['uid'], $dmTaskUids, true)
            ));

            if ($tasks === []) {
                continue;
            }

            $taskGroupsWithTasks[$groupIndex] = [
                'tasks' => $tasks,
                'groupName' => $group['groupName'] ?? '',
                'groupUid' => $group['uid'] ?? 0,
                'groupColor' => $group['color'] ?? '',
                'groupDescription' => $group['description'] ?? '',
                'groupHidden' => $group['hidden'] ?? false,
            ];
        }

        $errorClasses = array_values(array_filter(
            $groupedTasks['errorClasses'] ?? [],
            static fn(array $task): bool => in_array((int)$task['uid'], $dmTaskUids, true)
        ));

        return ['taskGroupsWithTasks' => $taskGroupsWithTasks, 'errorClasses' => $errorClasses];
    }
}
