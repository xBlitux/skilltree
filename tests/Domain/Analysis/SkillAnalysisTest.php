<?php

declare(strict_types=1);

namespace Skilltree\Tests\Domain\Analysis;

use PHPUnit\Framework\TestCase;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\InvalidDataset;
use Skilltree\Domain\Analysis\SkillAnalysis;
use Skilltree\Domain\Analysis\SkillGraph;

final class SkillAnalysisTest extends TestCase
{
    public function testTransitiveTaskAndEmployeeSkillsFollowPrerequisitesOnly(): void
    {
        // UC section 2: task Z(3), Z needs B(2), B needs A(1).
        // Person owns B => also A, but never Z. Skill 4 depends on Z, not vice versa.
        $result = $this->calculate([[3]], [[2]], [3 => [2], 2 => [1], 4 => [3]]);

        self::assertSame([1, 2, 3], $result['required_skill_ids']);
        self::assertSame([1, 2], $result['available_skill_ids']);
        self::assertSame([3], $result['tasks'][0]['direct_skill_ids']);
        self::assertSame([1, 2, 3], $result['tasks'][0]['required_skill_ids']);
        self::assertSame([2], $result['employees'][0]['direct_skill_ids']);
        self::assertSame([1, 2], $result['employees'][0]['available_skill_ids']);
        self::assertSame(['red' => 1, 'yellow' => 2, 'green' => 0], $result['summary']['counts']);
    }

    public function testSharedPathsAndMultipleAssignmentsCountOneSkillAndOnePersonOnly(): void
    {
        // Diamond Z(4) -> A(2)/B(3) -> base(1), plus repeated direct assignments/edges.
        $result = $this->calculate(
            [[4, 2, 4], [4, 1]],
            [[4, 2, 3, 1, 4], [1]],
            [4 => [2, 3, 2], 2 => [1], 3 => [1]],
        );

        self::assertSame([1, 2, 3, 4], $result['required_skill_ids']);
        self::assertSame([2, 1, 1, 1], array_column($result['skills'], 'carrier_count'));
        self::assertSame([1, 2], $result['skills'][0]['employee_ids']);
        self::assertSame(['red' => 0, 'yellow' => 3, 'green' => 1], $result['summary']['counts']);
    }

    public function testTrafficLightThresholdsIncludeExactlyTwoCarriers(): void
    {
        $result = $this->calculate([[1, 2, 3, 4]], [[2, 3, 4], [3, 4], [4]]);

        self::assertSame([0, 1, 2, 3], array_column($result['skills'], 'carrier_count'));
        self::assertSame(['red', 'yellow', 'green', 'green'], array_column($result['skills'], 'status'));
        self::assertSame(['red' => 1, 'yellow' => 1, 'green' => 2], $result['summary']['counts']);
    }

    public function testAvailableSkillsOutsideDemandAreNotTrafficLightSkills(): void
    {
        $result = $this->calculate([[1]], [[1, 5], [5]]);

        self::assertSame([1], $result['required_skill_ids']);
        self::assertSame([1, 5], $result['available_skill_ids']);
        self::assertSame([1], array_column($result['skills'], 'id'));
        self::assertSame(['red' => 0, 'yellow' => 1, 'green' => 0], $result['summary']['counts']);
    }

    public function testNoTasksMeansZeroTrafficLightCountsDespitePossession(): void
    {
        $result = $this->calculate([], [[1], [1]]);
        self::assertSame([], $result['skills']);
        self::assertSame([1], $result['available_skill_ids']);
        self::assertSame(['red' => 0, 'yellow' => 0, 'green' => 0], $result['summary']['counts']);
    }

    public function testNoEmployeesMeansAllRequiredSkillsAreRed(): void
    {
        $result = $this->calculate([[3]], [], [3 => [2], 2 => [1]]);
        self::assertSame([], $result['available_skill_ids']);
        self::assertSame(['red' => 3, 'yellow' => 0, 'green' => 0], $result['summary']['counts']);
        self::assertSame([[], [], []], array_column($result['skills'], 'employee_ids'));
    }

    public function testEmptyAssignmentsKeepTasksAndEmployeesInResult(): void
    {
        $result = $this->calculate([[]], [[]]);
        self::assertSame(1, $result['summary']['task_count']);
        self::assertSame(1, $result['summary']['employee_count']);
        self::assertSame([], $result['tasks'][0]['required_skill_ids']);
        self::assertSame([], $result['employees'][0]['available_skill_ids']);
        self::assertSame(['red' => 0, 'yellow' => 0, 'green' => 0], $result['summary']['counts']);
    }

    public function testEmptyOrganisationAndCatalogProduceEmptyResult(): void
    {
        $result = (new SkillAnalysis())->calculate(new AnalysisDataset(
            ['id' => 1, 'name' => 'Test'], [], [], [], [],
        ));
        self::assertSame([], $result['skills']);
        self::assertSame(0, $result['summary']['required_skill_count']);
        self::assertSame(0, $result['summary']['available_skill_count']);
    }

    public function testAllAndPrerequisitesAreRequiredEvenWhenPersonHasOnlyOneBranch(): void
    {
        $result = $this->calculate([[3]], [[1]], [3 => [1, 2]]);
        self::assertSame([1, 2, 3], $result['required_skill_ids']);
        self::assertSame([1], $result['available_skill_ids']);
        self::assertSame(['yellow', 'red', 'red'], array_column($result['skills'], 'status'));
    }

    public function testSelfReferenceIsRejected(): void
    {
        $this->expectException(InvalidDataset::class);
        $this->calculate([[1]], [], [1 => [1]]);
    }

    public function testIndirectCycleIsRejectedEvenOutsideCurrentDemand(): void
    {
        $this->expectException(InvalidDataset::class);
        $this->calculate([[4]], [], [1 => [2], 2 => [3], 3 => [1]]);
    }

    public function testUnknownDirectSkillIsRejected(): void
    {
        $this->expectException(InvalidDataset::class);
        $this->calculate([[999]], []);
    }

    public function testUnknownPrerequisiteIsRejected(): void
    {
        $this->expectException(InvalidDataset::class);
        $this->calculate([[1]], [], [1 => [999]]);
    }

    public function testLongChainsHaveNoArbitraryDepthCutoff(): void
    {
        $edges = [];
        for ($id = 2; $id <= 12000; $id++) {
            $edges[$id] = [$id - 1];
        }
        $graph = new SkillGraph(range(1, 12000), $edges);
        self::assertSame(range(1, 12000), $graph->expand([12000]));
    }

    public function testNewCalculationDoesNotReusePriorPossessionOrDemand(): void
    {
        $calculator = new SkillAnalysis();
        $first = $this->dataset([[1]], [[1], [1]]);
        $second = $this->dataset([[2]], []);
        self::assertSame(['red' => 0, 'yellow' => 0, 'green' => 1], $calculator->calculate($first)['summary']['counts']);
        self::assertSame(['red' => 1, 'yellow' => 0, 'green' => 0], $calculator->calculate($second)['summary']['counts']);
        self::assertSame([1], $first->tasks[0]['direct_skill_ids']);
    }

    private function calculate(array $tasks, array $employees, array $prerequisites = []): array
    {
        return (new SkillAnalysis())->calculate($this->dataset($tasks, $employees, $prerequisites));
    }

    private function dataset(array $taskSkills, array $employeeSkills, array $prerequisites = []): AnalysisDataset
    {
        $skills = [];
        for ($id = 1; $id <= 5; $id++) {
            // Group ID deliberately overlaps a skill ID; it must never add a skill.
            $skills[$id] = ['id' => $id, 'name' => 'Testskill ' . $id, 'skill_group_id' => 5];
        }
        $tasks = [];
        foreach ($taskSkills as $index => $ids) {
            $tasks[] = ['id' => $index + 1, 'name' => 'Testaufgabe', 'direct_skill_ids' => $ids];
        }
        $employees = [];
        foreach ($employeeSkills as $index => $ids) {
            $employees[] = ['id' => $index + 1, 'first_name' => 'Test', 'last_name' => 'Person', 'direct_skill_ids' => $ids];
        }

        return new AnalysisDataset(['id' => 1, 'name' => 'Testorganisation'], $skills, $tasks, $employees, $prerequisites);
    }
}
