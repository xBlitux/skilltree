<?php

declare(strict_types=1);

namespace Skilltree\Domain\Analysis;

use InvalidArgumentException;

/** Temporary filters only: prepared data, taxonomy and path availability stay intact. */
final class SimulationAnalysis
{
    public function calculate(AnalysisDataset $data, array $excludedTasks = [], array $excludedEmployees = [], int $maximumDistance = 3): array
    {
        $tasks = $this->validateIds($excludedTasks, array_column($data->tasks, 'id'));
        $employees = $this->validateIds($excludedEmployees, array_column($data->employees, 'id'));
        $filtered = new AnalysisDataset(
            $data->organisation, $data->skills,
            array_values(array_filter($data->tasks, static fn (array $task): bool => !in_array($task['id'], $tasks, true))),
            array_values(array_filter($data->employees, static fn (array $employee): bool => !in_array($employee['id'], $employees, true))),
            $data->prerequisites, $data->groups,
        );
        $result = (new SkillAnalysis())->calculate($filtered);
        $result['development'] = (new DevelopmentAnalysis())->calculate($data, $result, $maximumDistance);
        $result['organisations'] = $data->organisations ?: [$data->organisation];
        $result['simulation'] = [
            'scenarios' => $data->scenarios,
            'excluded_task_ids' => $tasks, 'excluded_employee_ids' => $employees,
            // Include excluded choices so users can re-include them without another endpoint.
            'tasks' => array_map(static fn (array $task): array => ['id' => $task['id'], 'name' => $task['name']], $data->tasks),
            'employees' => array_map(static fn (array $employee): array => [
                'id' => $employee['id'], 'first_name' => $employee['first_name'], 'last_name' => $employee['last_name'],
            ], $data->employees),
        ];
        return $result;
    }

    private function validateIds(array $excluded, array $known): array
    {
        foreach ($excluded as $id) {
            if (!is_int($id) || !in_array($id, $known, true)) {
                throw new InvalidArgumentException('Simulationsfilter enthalten unbekannte oder ungültige IDs.');
            }
        }
        $ids = array_values(array_unique($excluded));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }
}
