<?php

declare(strict_types=1);

namespace Skilltree\Infrastructure\Database;

use PDO;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\InvalidDataset;
use Throwable;

final class AnalysisRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function load(?int $organisationId = null): AnalysisDataset
    {
        // All reads of this request see the same InnoDB snapshot (A-16).
        $this->connection->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->connection->beginTransaction();
        try {
            $organisations = $this->connection->query(
                'SELECT id, name FROM organisation_unit ORDER BY id',
            )->fetchAll();
            if (!$organisations) throw new InvalidDataset('Keine Organisationseinheit vorhanden.');
            $organisations = array_map(static fn ($row) => ['id' => (int) $row['id'], 'name' => $row['name']], $organisations);
            $organisationId ??= $organisations[0]['id'];
            $organisation = array_column($organisations, null, 'id')[$organisationId] ?? null;
            if ($organisation === null) throw new \InvalidArgumentException('Unbekannte Organisationseinheit.');

            $skills = [];
            foreach ($this->connection->query('SELECT id, name, description, skill_group_id FROM skill ORDER BY id') as $row) {
                $id = (int) $row['id'];
                $skills[$id] = ['id' => $id, 'name' => $row['name'], 'description' => $row['description'], 'skill_group_id' => (int) $row['skill_group_id']];
            }

            $tasks = [];
            foreach ($this->forOrganisation(
                'SELECT id, name FROM task WHERE organisation_unit_id = ? ORDER BY id', $organisation['id'],
            ) as $row) {
                $id = (int) $row['id'];
                $tasks[$id] = ['id' => $id, 'name' => $row['name'], 'direct_skill_ids' => []];
            }
            foreach ($this->forOrganisation(
                'SELECT ts.task_id, ts.skill_id FROM task_skill ts
                 JOIN task t ON t.id = ts.task_id WHERE t.organisation_unit_id = ? ORDER BY ts.task_id, ts.skill_id',
                $organisation['id'],
            ) as $row) {
                $tasks[(int) $row['task_id']]['direct_skill_ids'][] = (int) $row['skill_id'];
            }

            $employees = [];
            foreach ($this->forOrganisation(
                'SELECT id, first_name, last_name FROM employee WHERE organisation_unit_id = ? ORDER BY id',
                $organisation['id'],
            ) as $row) {
                $id = (int) $row['id'];
                $employees[$id] = [
                    'id' => $id, 'first_name' => $row['first_name'], 'last_name' => $row['last_name'],
                    'direct_skill_ids' => [],
                ];
            }
            foreach ($this->forOrganisation(
                'SELECT es.employee_id, es.skill_id FROM employee_skill es
                 JOIN employee e ON e.id = es.employee_id
                 WHERE e.organisation_unit_id = ? ORDER BY es.employee_id, es.skill_id',
                $organisation['id'],
            ) as $row) {
                $employees[(int) $row['employee_id']]['direct_skill_ids'][] = (int) $row['skill_id'];
            }

            $prerequisites = [];
            foreach ($this->connection->query(
                'SELECT skill_id, prerequisite_skill_id FROM skill_prerequisite ORDER BY skill_id, prerequisite_skill_id',
            ) as $row) {
                $prerequisites[(int) $row['skill_id']][] = (int) $row['prerequisite_skill_id'];
            }

            $groups = [];
            foreach ($this->connection->query('SELECT id, parent_skill_group_id, name FROM skill_group ORDER BY id') as $row) {
                $groups[] = [
                    'id' => (int) $row['id'], 'name' => $row['name'],
                    'parent_skill_group_id' => $row['parent_skill_group_id'] === null ? null : (int) $row['parent_skill_group_id'],
                ];
            }
            $scenarios = [];
            foreach ($this->forOrganisation('SELECT id, name FROM scenario WHERE organisation_unit_id = ? ORDER BY id LIMIT 9', $organisationId) as $row) {
                $statement = $this->connection->prepare('SELECT st.task_id FROM scenario_task st JOIN task t ON t.id = st.task_id WHERE st.scenario_id = ? AND t.organisation_unit_id = ? ORDER BY st.task_id');
                $statement->execute([(int) $row['id'], $organisationId]);
                $scenarios[] = ['id' => (int) $row['id'], 'name' => $row['name'], 'task_ids' => array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN))];
            }
            $this->connection->commit();

            return new AnalysisDataset($organisation, $skills, array_values($tasks), array_values($employees), $prerequisites, $groups, $organisations, $scenarios);
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    private function forOrganisation(string $sql, int $organisationId): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute([$organisationId]);

        return $statement->fetchAll();
    }
}
