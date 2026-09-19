<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Skilltree\Domain\Analysis\SkillAnalysis;
use Skilltree\Infrastructure\Database\AnalysisRepository;
use Skilltree\Infrastructure\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

// Explicit, read-only integration check of the agreed synthetic seed; no import.
try {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    $value = static fn (string $key) => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value('DB_NAME') !== 'testdata_skilltree'
        || !in_array($value('DB_HOST'), ['localhost', '127.0.0.1'], true)
        || $value('DB_USER') !== 'skilltree_test_reader') {
        throw new RuntimeException('Test abgebrochen: lokale Testdatenbank mit Lesebenutzer erforderlich.');
    }

    $connection = ConnectionFactory::fromEnvironment();
    if ($connection->query('SELECT DATABASE()')->fetchColumn() !== 'testdata_skilltree') {
        throw new RuntimeException('Test abgebrochen: verbundenes Datenbankziel stimmt nicht.');
    }
    $data = (new AnalysisRepository($connection))->load();
    $result = (new SkillAnalysis())->calculate($data);
    $check = static function (bool $condition, string $message): void {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    };

    $check(count($data->skills) === 29, 'Erwartet: 29 Skills.');
    $check(count($data->groups) === 10, 'Erwartet: 10 Gruppen.');
    $parents = array_column($data->groups, 'parent_skill_group_id', 'id');
    foreach ($data->skills as $skill) {
        $seen = []; $group = $skill['skill_group_id'];
        while ($group !== null) {
            $check(!isset($seen[$group]) && array_key_exists($group, $parents), 'Ungueltige Hierarchie.');
            $seen[$group] = true; $group = $parents[$group];
        }
        $check(count($seen) === 4, 'Erwartet: vier Gruppenebenen je Skill.');
    }
    $development = (new \Skilltree\Domain\Analysis\DevelopmentAnalysis())->calculate($data, $result);
    $check($development['estimated_skill_ids'] === range(1, 26), 'Die drei freien Skills duerfen nicht geschaetzt sein.');
    $migration = array_column($development['candidates'], null, 'skill_id')[20];
    $check(array_column($migration['slots'], 'employee_id') === [2, 4, 1], 'Migration: Ben, David, Anna erwartet.');
    $check(array_column($migration['slots'], 'distance') === [2, 2, 3], 'Migration: Distanzen 2, 2, 3 erwartet.');
    $check($data->prerequisites === [
        1 => [22, 23], 3 => [22], 14 => [25], 20 => [4, 23, 24],
        22 => [21], 23 => [21], 24 => [21], 25 => [26], 26 => [21],
    ], 'Die 12 DAG-Kanten stimmen nicht mit dem Seed ueberein.');
    $check($result['summary'] === [
        'task_count' => 10,
        'employee_count' => 5,
        'required_skill_count' => 26,
        'available_skill_count' => 23,
        'counts' => ['red' => 3, 'yellow' => 7, 'green' => 16],
    ], 'Die Zusammenfassung weicht vom vereinbarten Seed ab.');
    $check($result['required_skill_ids'] === range(1, 26), 'Unerwartete Soll-Menge.');
    $check($result['available_skill_ids'] === [...range(1, 18), 21, 22, 23, 25, 26], 'Unerwartete Ist-Menge.');
    $expectedCounts = [...array_fill(0, 12, 3), 4, 1, 1, 1, 1, 1, 0, 0, 5, 5, 3, 0, 1, 1];
    $check(array_column($result['skills'], 'carrier_count') === $expectedCounts, 'Unerwartete Wissenstraegerzahlen.');
    $check(array_column($result['skills'], 'status') === [
        ...array_fill(0, 13, 'green'), ...array_fill(0, 5, 'yellow'), 'red', 'red',
        'green', 'green', 'green', 'red', 'yellow', 'yellow',
    ], 'Unerwartete Ampelbewertung.');
    $expectedTaskSkills = [
        1 => [1, 5, 21, 22, 23], 2 => [2, 3, 21, 22], 3 => [4, 20, 21, 23, 24],
        4 => [6, 16], 5 => [7, 8], 6 => [9, 10], 7 => [11, 13], 8 => [12, 18],
        9 => [14, 15, 21, 25, 26], 10 => [17, 19],
    ];
    $directTaskSkills = [];
    foreach ($result['tasks'] as $task) {
        $check(count($task['direct_skill_ids']) === 2, 'Eine Testaufgabe muss genau zwei direkte Skills erfordern.');
        $check($task['required_skill_ids'] === $expectedTaskSkills[$task['id']], 'Unerwartete transitive Aufgaben-Skills.');
        $directTaskSkills = [...$directTaskSkills, ...$task['direct_skill_ids']];
    }
    sort($directTaskSkills, SORT_NUMERIC);
    $check($directTaskSkills === range(1, 20), 'Nur die urspruenglichen 20 Skills duerfen direkt Aufgaben zugeordnet sein.');

    $expectedImplicitPossession = [1 => [21, 22, 23, 25, 26], 2 => [21, 22, 23], 3 => [21, 22], 4 => [21, 22, 23], 5 => [21, 22]];
    $directAssignmentCount = 0;
    foreach ($result['employees'] as $employee) {
        $directAssignmentCount += count($employee['direct_skill_ids']);
        $check(array_intersect($employee['direct_skill_ids'], range(21, 26)) === [], 'Neue Grundlagen duerfen keinen direkten Besitz haben.');
        $implicit = array_values(array_diff($employee['available_skill_ids'], $employee['direct_skill_ids']));
        $check($implicit === $expectedImplicitPossession[$employee['id']], 'Unerwarteter impliziter Besitz.');
    }
    $check($directAssignmentCount === 45, 'Die 45 direkten Mitarbeiterzuordnungen muessen erhalten bleiben.');
    $expectedNewCarriers = [21 => [1, 2, 3, 4, 5], 22 => [1, 2, 3, 4, 5], 23 => [1, 2, 4], 24 => [], 25 => [1], 26 => [1]];
    foreach ($result['skills'] as $skill) {
        if ($skill['id'] >= 21) {
            $check($skill['employee_ids'] === $expectedNewCarriers[$skill['id']], 'Unerwartete implizite Wissenstraeger.');
        }
    }

    echo "OK: testdata_skilltree, 10 Aufgaben, 5 Personen, Soll=26, Ist=23, Rot=3, Gelb=7, Gruen=16.\n";
    echo "29 Katalogskills, 10 Gruppen, vier Ebenen und Kandidaten/Distanzen der Datenmigration stimmen.\n";
    echo "Alle 26 Skillbewertungen, 12 DAG-Kanten, transitiven Aufgabenmengen und impliziten Wissenstraeger stimmen.\n";
    echo "6 Skills sind ausschliesslich implizit im Soll. Direkte Zuordnungen: 20 Aufgaben-Skills / 45 Mitarbeiter-Skills. Nur lesend geprueft.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, "FEHLER: Die Testdatenbank konnte nicht gelesen werden (Zugangsdaten werden nicht ausgegeben).\n");
    exit(1);
} catch (Throwable $exception) {
    // Do not print dotenv/parser errors, which may contain secret configuration values.
    fwrite(STDERR, "FEHLER: Testkonfiguration oder Seed entspricht nicht der erwarteten Testbasis.\n");
    exit(1);
}
