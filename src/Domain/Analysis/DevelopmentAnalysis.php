<?php

declare(strict_types=1);

namespace Skilltree\Domain\Analysis;

/** A-09..A-12, S-01..S-03/S-09. Receives the same snapshot as the dashboard. */
final class DevelopmentAnalysis
{
    public function calculate(AnalysisDataset $data, array $analysis): array
    {
        $graph = new SkillGraph(array_keys($data->skills), $data->prerequisites);
        // Deliberately derive availability from ALL prepared tasks, not current filters.
        $direct = [];
        foreach ($data->tasks as $task) {
            foreach ($task['direct_skill_ids'] as $id) {
                $direct[$id] = true;
            }
        }
        $estimated = array_fill_keys($graph->expand(array_keys($direct)), true);
        $edges = [];
        foreach ($data->prerequisites as $dependent => $prerequisites) {
            foreach (array_unique($prerequisites) as $prerequisite) {
                $estimated[$dependent] = true;
                $edges[] = ['source' => $prerequisite, 'target' => $dependent];
            }
        }
        $candidates = [];
        foreach ($analysis['skills'] as $skill) {
            if ($skill['status'] === 'green') {
                continue;
            }
            $closure = $graph->expand([$skill['id']]);
            $ranked = [];
            foreach ($analysis['employees'] as $employee) {
                if (in_array($employee['id'], $skill['employee_ids'], true)) {
                    continue;
                }
                $ranked[] = [
                    'employee_id' => $employee['id'],
                    'first_name' => $employee['first_name'], 'last_name' => $employee['last_name'],
                    'distance' => count(array_diff($closure, $employee['available_skill_ids'])),
                ];
            }
            usort($ranked, static fn (array $a, array $b): int =>
                ($a['distance'] <=> $b['distance'])
                ?: strcasecmp(self::sortName($a['last_name']), self::sortName($b['last_name']))
                ?: strcasecmp(self::sortName($a['first_name']), self::sortName($b['first_name']))
                ?: ($a['employee_id'] <=> $b['employee_id'])
            );
            $minimum = $ranked[0]['distance'] ?? null;
            $candidates[] = [
                'skill_id' => $skill['id'],
                'slots' => array_pad(array_slice($ranked, 0, 3), 3, null),
                'minimum_distance' => $minimum,
                'recommendations' => [
                    'distance_threshold' => $minimum !== null && $minimum >= 3,
                    'unfilled_slots' => count($ranked) < 3,
                ],
            ];
        }
        $ids = array_keys($estimated);
        sort($ids, SORT_NUMERIC);
        return ['estimated_skill_ids' => $ids, 'edges' => $edges, 'candidates' => $candidates];
    }

    private static function sortName(string $name): string
    {
        // German dictionary order without requiring an additional intl extension.
        return strtr($name, ['Ä' => 'A', 'Ö' => 'O', 'Ü' => 'U', 'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss']);
    }
}
