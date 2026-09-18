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

    public function load(): AnalysisDataset
    {
        // All reads of this request see the same InnoDB snapshot (A-16).
        $this->connection->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->connection->beginTransaction();
        try {
            $organisations = $this->connection->query(
                'SELECT id, name FROM organisation_unit ORDER BY id LIMIT 2',
            )->fetchAll();
            if (count($organisations) !== 1) {
                throw new InvalidDataset('Es muss genau eine vorbereitete Organisationseinheit vorhanden sein.');
            }
            $organisation = $organisations[0];
            $organisation['id'] = (int) $organisation['id'];

            $skills = [];
            foreach ($this->connection->query('SELECT id, name, skill_group_id FROM skill ORDER BY id') as $row) {
                $id = (int) $row['id'];
                $skills[$id] = ['id' => $id, 'name' => $row['name'], 'skill_group_id' => (int) $row['skill_group_id']];
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

            $this->connection->commit();

            return new AnalysisDataset($organisation, $skills, array_values($tasks), array_values($employees), $prerequisites);
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
