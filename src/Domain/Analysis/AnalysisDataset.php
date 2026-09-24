<?php

declare(strict_types=1);

namespace Skilltree\Domain\Analysis;

/** Prepared data for one organisation; only direct assignments are stored here. */
final readonly class AnalysisDataset
{
    /**
     * @param array{id: int, name: string} $organisation
     * @param array<int, array{id: int, name: string, description?: string|null, skill_group_id: int}> $skills Indexed by skill ID.
     * @param list<array{id: int, name: string, direct_skill_ids: list<int>}> $tasks
     * @param list<array{id: int, first_name: string, last_name: string, direct_skill_ids: list<int>}> $employees
     * @param array<int, list<int>> $prerequisites Dependent skill ID => prerequisite IDs.
     * @param list<array{id: int, name: string, parent_skill_group_id: int|null}> $groups
     */
    public function __construct(
        public array $organisation,
        public array $skills,
        public array $tasks,
        public array $employees,
        public array $prerequisites,
        public array $groups = [],
        public array $organisations = [],
        public array $scenarios = [],
    ) {
    }
}
