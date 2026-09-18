<?php

declare(strict_types=1);

namespace Skilltree\Domain\Analysis;

/** Resolves prerequisites, never thematic parent groups or dependent skills. */
final class SkillGraph
{
    /** @var array<int, true> */
    private array $knownSkills;

    /**
     * @param list<int> $skillIds
     * @param array<int, list<int>> $prerequisites
     */
    public function __construct(array $skillIds, private readonly array $prerequisites)
    {
        $this->knownSkills = array_fill_keys($skillIds, true);
        $this->validate();
    }

    /** @param list<int> $directSkillIds @return list<int> */
    public function expand(array $directSkillIds): array
    {
        $visited = [];
        $pending = $directSkillIds;

        // Iterative traversal avoids a recursion-depth limit for valid long chains.
        while ($pending !== []) {
            $skillId = array_pop($pending);
            $this->requireSkill($skillId);
            if (isset($visited[$skillId])) {
                continue;
            }
            $visited[$skillId] = true;
            foreach ($this->prerequisites[$skillId] ?? [] as $prerequisiteId) {
                $pending[] = $prerequisiteId;
            }
        }

        $ids = array_keys($visited);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    private function requireSkill(int $skillId): void
    {
        if (!isset($this->knownSkills[$skillId])) {
            throw new InvalidDataset('Eine Zuordnung verweist auf einen unbekannten Skill.');
        }
    }

    private function validate(): void
    {
        $remaining = array_fill_keys(array_keys($this->knownSkills), 0);
        $dependents = [];
        foreach ($this->prerequisites as $skillId => $prerequisiteIds) {
            $this->requireSkill($skillId);
            foreach (array_unique($prerequisiteIds) as $prerequisiteId) {
                $this->requireSkill($prerequisiteId);
                $remaining[$skillId]++;
                $dependents[$prerequisiteId][] = $skillId;
            }
        }

        // Kahn's algorithm: a DAG must permit all nodes to be processed.
        $ready = array_keys(array_filter($remaining, static fn (int $count): bool => $count === 0));
        $processed = 0;
        while ($ready !== []) {
            $skillId = array_pop($ready);
            $processed++;
            foreach ($dependents[$skillId] ?? [] as $dependentId) {
                if (--$remaining[$dependentId] === 0) {
                    $ready[] = $dependentId;
                }
            }
        }
        if ($processed !== count($this->knownSkills)) {
            throw new InvalidDataset('Die Skillvoraussetzungen enthalten einen Zyklus oder Selbstverweis.');
        }
    }
}
