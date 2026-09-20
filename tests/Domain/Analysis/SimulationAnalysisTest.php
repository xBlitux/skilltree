<?php

declare(strict_types=1);

namespace Skilltree\Tests\Domain\Analysis;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\SimulationAnalysis;

final class SimulationAnalysisTest extends TestCase
{
    public function testEmployeeExclusionRecalculatesCarriersCandidatesAndCanBeReversed(): void
    {
        $data = $this->dataset();
        $original = serialize($data);
        $calculator = new SimulationAnalysis();
        $base = $calculator->calculate($data);
        self::assertSame(['red' => 0, 'yellow' => 1, 'green' => 2], $base['summary']['counts']);
        $filtered = $calculator->calculate($data, [], [1]);
        self::assertSame(['red' => 1, 'yellow' => 2, 'green' => 0], $filtered['summary']['counts']);
        self::assertSame([2, 3], array_column($filtered['employees'], 'id'));
        foreach ($filtered['skills'] as $skill) self::assertNotContains(1, $skill['employee_ids']);
        foreach ($filtered['development']['candidates'] as $entry) {
            self::assertNotContains(1, array_column(array_filter($entry['slots']), 'employee_id'));
        }
        self::assertCount(3, $filtered['simulation']['employees']);
        self::assertSame($base, $calculator->calculate($data));
        self::assertSame($original, serialize($data));
    }

    public function testSharedDemandSurvivesOneTaskExclusionAndDisappearsOnlyAfterLastTask(): void
    {
        $data = $this->dataset();
        $calculator = new SimulationAnalysis();
        $one = $calculator->calculate($data, [1]);
        self::assertSame([1, 2, 3], $one['required_skill_ids']);
        $none = $calculator->calculate($data, [2, 1, 2]);
        self::assertSame([], $none['required_skill_ids']);
        self::assertSame(['red' => 0, 'yellow' => 0, 'green' => 0], $none['summary']['counts']);
        self::assertCount(4, $none['taxonomy']['skills']);
        self::assertSame([1, 2, 3], $none['development']['estimated_skill_ids']);
        self::assertSame([1, 2], $none['simulation']['excluded_task_ids']);
        self::assertSame([], $none['development']['candidates']);
    }

    public function testNoEmployeesMeansAllRedAndThreeEmptySlotsWithoutInventedDistance(): void
    {
        $result = (new SimulationAnalysis())->calculate($this->dataset(), [], [1, 2, 3]);
        self::assertSame(['red' => 3, 'yellow' => 0, 'green' => 0], $result['summary']['counts']);
        self::assertSame([], $result['available_skill_ids']);
        foreach ($result['development']['candidates'] as $entry) {
            self::assertSame([null, null, null], $entry['slots']);
            self::assertNull($entry['minimum_distance']);
            self::assertSame(['distance_threshold' => false, 'unfilled_slots' => true], $entry['recommendations']);
        }
        $empty = (new SimulationAnalysis())->calculate($this->dataset(), [1, 2], [1, 2, 3]);
        self::assertSame(['red' => 0, 'yellow' => 0, 'green' => 0], $empty['summary']['counts']);
        self::assertSame([1, 2, 3], $empty['development']['estimated_skill_ids']);
    }

    public function testUnknownTaskCannotSilentlyChangeSimulation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SimulationAnalysis())->calculate($this->dataset(), [999]);
    }

    public function testUnknownEmployeeCannotSilentlyChangeSimulation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SimulationAnalysis())->calculate($this->dataset(), [], [999]);
    }

    private function dataset(): AnalysisDataset
    {
        $skills = [];
        foreach (range(1, 4) as $id) $skills[$id] = ['id' => $id, 'name' => "Skill $id", 'skill_group_id' => 1];
        return new AnalysisDataset(['id' => 1, 'name' => 'Test'], $skills,
            [['id' => 1, 'name' => 'Aufgabe A', 'direct_skill_ids' => [3]], ['id' => 2, 'name' => 'Aufgabe B', 'direct_skill_ids' => [3]]],
            [
                ['id' => 1, 'first_name' => 'Anna', 'last_name' => 'Adler', 'direct_skill_ids' => [3]],
                ['id' => 2, 'first_name' => 'Ben', 'last_name' => 'Berger', 'direct_skill_ids' => [2]],
                ['id' => 3, 'first_name' => 'Carla', 'last_name' => 'Conrad', 'direct_skill_ids' => []],
            ], [3 => [2], 2 => [1]], [['id' => 1, 'name' => 'Gruppe', 'parent_skill_group_id' => null]],
        );
    }
}
