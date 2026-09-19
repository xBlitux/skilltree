<?php

declare(strict_types=1);

namespace Skilltree\Tests\Domain\Analysis;

use PHPUnit\Framework\TestCase;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\DevelopmentAnalysis;
use Skilltree\Domain\Analysis\SkillAnalysis;

final class DevelopmentAnalysisTest extends TestCase
{
    public function testDistanceCountsTargetAndSharedPrerequisitesOnceAndExcludesCarrier(): void
    {
        $data = $this->dataset([[], [1], [2, 3], [4]], [4 => [2, 3], 2 => [1], 3 => [1]]);
        $result = $this->calculate($data);
        $target = $this->candidates($result, 4);
        self::assertSame([3, 2, 1], array_column($target['slots'], 'employee_id'));
        self::assertSame([1, 3, 4], array_column($target['slots'], 'distance'));
        self::assertSame(1, $target['minimum_distance']);
        self::assertSame(['distance_threshold' => false, 'unfilled_slots' => false], $target['recommendations']);
    }

    public function testThreePlacesAndIndependentRecommendations(): void
    {
        foreach ([0, 1, 2, 3, 4] as $count) {
            $result = $this->calculate($this->dataset(array_fill(0, $count, []), [4 => [1, 2, 3]]));
            $target = $this->candidates($result, 4);
            self::assertCount(3, $target['slots']);
            self::assertSame($count < 3, $target['recommendations']['unfilled_slots']);
            self::assertSame($count > 0, $target['recommendations']['distance_threshold']);
            self::assertSame($count === 0 ? null : 4, $target['minimum_distance']);
            self::assertCount(max(0, 3 - $count), array_filter($target['slots'], static fn ($slot) => $slot === null));
        }
        $nearby = $this->candidates($this->calculate($this->dataset([[1, 2, 3], [1, 2]], [4 => [1, 2, 3]])), 4);
        self::assertSame(['distance_threshold' => false, 'unfilled_slots' => true], $nearby['recommendations']);
    }

    public function testAlphabeticalTieBreakUsesLastThenFirstName(): void
    {
        $data = $this->dataset([[], [], [], []], []);
        $employees = $data->employees;
        foreach (['Zorn', 'Öster', 'Adler', 'Berger'] as $index => $name) $employees[$index]['last_name'] = $name;
        $data = new AnalysisDataset($data->organisation, $data->skills, $data->tasks, $employees, []);
        self::assertSame([3, 4, 2], array_column($this->candidates($this->calculate($data), 4)['slots'], 'employee_id'));
    }

    public function testEstimatedSetUsesUnfilteredTasksAndOwnPrerequisitesNotOriginOrCurrentDemand(): void
    {
        $data = $this->dataset([[]], [4 => [3], 3 => [2], 5 => [1]]);
        $analysis = (new SkillAnalysis())->calculate($data);
        $analysis['skills'] = []; // Future simulation: no currently required skills.
        $result = (new DevelopmentAnalysis())->calculate($data, $analysis);
        self::assertSame([2, 3, 4, 5], $result['estimated_skill_ids']);
        self::assertSame([], $result['candidates']);
        self::assertContains(['source' => 3, 'target' => 4], $result['edges']);
    }

    public function testCandidatePoolUsesOnlyIncludedEmployees(): void
    {
        $data = $this->dataset([[], [], [], []], []);
        $analysis = (new SkillAnalysis())->calculate($data);
        $analysis['employees'] = [$analysis['employees'][3]];
        $result = (new DevelopmentAnalysis())->calculate($data, $analysis);
        self::assertSame(4, $this->candidates($result, 4)['slots'][0]['employee_id']);
        self::assertNull($this->candidates($result, 4)['slots'][1]);
    }

    private function calculate(AnalysisDataset $data): array
    {
        return (new DevelopmentAnalysis())->calculate($data, (new SkillAnalysis())->calculate($data));
    }

    private function candidates(array $result, int $skill): array
    {
        return array_column($result['candidates'], null, 'skill_id')[$skill];
    }

    private function dataset(array $possession, array $edges): AnalysisDataset
    {
        $skills = [];
        foreach (range(1, 6) as $id) $skills[$id] = ['id' => $id, 'name' => "Testskill $id", 'skill_group_id' => 1];
        $employees = [];
        foreach ($possession as $index => $ids) $employees[] = ['id' => $index + 1, 'first_name' => 'Test', 'last_name' => (string) $index, 'direct_skill_ids' => $ids];
        return new AnalysisDataset(['id' => 1, 'name' => 'Test'], $skills, [['id' => 1, 'name' => 'Aufgabe', 'direct_skill_ids' => [4]]], $employees, $edges);
    }
}
