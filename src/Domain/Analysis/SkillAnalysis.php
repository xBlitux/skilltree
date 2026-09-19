<?php

declare(strict_types=1);

namespace Skilltree\Domain\Analysis;

/** D-07/D-08, A-01..A-05: pure calculation, without database or HTTP access. */
final class SkillAnalysis
{
    public function calculate(AnalysisDataset $data): array
    {
        $graph = new SkillGraph(array_keys($data->skills), $data->prerequisites);
        $required = [];
        $carriers = [];
        $tasks = [];
        $employees = [];

        foreach ($data->tasks as $task) {
            $skillIds = $graph->expand($task['direct_skill_ids']);
            foreach ($skillIds as $skillId) {
                $required[$skillId] = true;
            }
            $tasks[] = [...$task, 'required_skill_ids' => $skillIds];
        }

        foreach ($data->employees as $employee) {
            $skillIds = $graph->expand($employee['direct_skill_ids']);
            foreach ($skillIds as $skillId) {
                // One person per skill, even through multiple possession paths.
                $carriers[$skillId][$employee['id']] = true;
            }
            $employees[] = [...$employee, 'available_skill_ids' => $skillIds];
        }

        $requiredIds = array_keys($required);
        $availableIds = array_keys($carriers);
        sort($requiredIds, SORT_NUMERIC);
        sort($availableIds, SORT_NUMERIC);
        $counts = ['red' => 0, 'yellow' => 0, 'green' => 0];
        $skills = [];

        foreach ($requiredIds as $skillId) {
            $employeeIds = array_keys($carriers[$skillId] ?? []);
            sort($employeeIds, SORT_NUMERIC);
            $count = count($employeeIds);
            $status = match (true) {
                $count === 0 => 'red',
                $count === 1 => 'yellow',
                default => 'green',
            };
            $counts[$status]++;
            $skills[] = [
                ...$data->skills[$skillId],
                'carrier_count' => $count,
                'employee_ids' => $employeeIds,
                'status' => $status,
            ];
        }

        return [
            'organisation' => $data->organisation,
            'summary' => [
                'task_count' => count($tasks),
                'employee_count' => count($employees),
                'required_skill_count' => count($requiredIds),
                'available_skill_count' => count($availableIds),
                'counts' => $counts,
            ],
            'required_skill_ids' => $requiredIds,
            'available_skill_ids' => $availableIds,
            'skills' => $skills,
            'tasks' => $tasks,
            'employees' => $employees,
            'taxonomy' => ['groups' => $data->groups, 'skills' => array_values($data->skills)],
        ];
    }
}
