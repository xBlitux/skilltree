<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Skilltree\Infrastructure\Database\AnalysisRepository;
use Skilltree\Infrastructure\Database\ConnectionFactory;
use Skilltree\Domain\Analysis\SimulationAnalysis;

require dirname(__DIR__) . '/vendor/autoload.php';
$db = null;
$ownedRows = [];
$checks = 0;
$stage = 'Zielpruefung';
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$snapshot = static function (PDO $db): array {
    $rows = [];
    foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $keys = array_column($db->query("SHOW KEYS FROM `$table` WHERE Key_name='PRIMARY'")->fetchAll(), 'Column_name');
        if (!$keys) throw new RuntimeException('Tabelle ohne Primaerschluessel.');
        $rows[$table] = $db->query('SELECT `' . implode('`,`', $keys) . "` FROM `$table`")->fetchAll();
    }
    return $rows;
};
$cleanup = static function () use (&$db, &$ownedRows): void {
    if (!$db) return;
    if ($db->inTransaction()) $db->rollBack();
    if (!$ownedRows) return;
    $db->beginTransaction();
    // Exact fixture primary keys only; never delete other rows added meanwhile.
    foreach (['scenario_task', 'scenario', 'employee_skill', 'task_skill', 'skill_prerequisite',
        'organisation_unit_employee', 'organisation_unit_task', 'employee', 'task', 'skill', 'skill_group', 'organisation_unit'] as $table) {
        $rows = $ownedRows[$table] ?? [];
        if ($table === 'skill_group') {
            foreach ($rows as $row) $db->prepare('UPDATE skill_group SET parent_skill_group_id=NULL WHERE id=?')->execute([$row['id']]);
        }
        foreach ($rows as $row) {
            $where = implode(' AND ', array_map(static fn ($key) => "`$key`=?", array_keys($row)));
            $db->prepare("DELETE FROM `$table` WHERE $where")->execute(array_values($row));
        }
    }
    $db->commit();
    $ownedRows = [];
    echo "OK: saemtliche temporaeren Testzeilen entfernt.\n";
};
register_shutdown_function(static function () use ($cleanup): void {
    try { $cleanup(); } catch (Throwable) { fwrite(STDERR, "FEHLER: Testdatenbereinigung fehlgeschlagen; rework_skilltree pruefen.\n"); }
});
$run = static function (array $command, string $directory): void {
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $directory);
    if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('Unterprozess fehlgeschlagen.');
};
try {
    Dotenv::createImmutable(dirname(__DIR__))->load();
    $check($_ENV['DB_NAME'] === 'rework_skilltree' && $_ENV['DB_USER'] === 'db_architect'
        && in_array($_ENV['DB_HOST'], ['localhost', '127.0.0.1'], true), 'Falsches Testziel.');
    $db = ConnectionFactory::fromEnvironment();
    $check($db->query('SELECT DATABASE()')->fetchColumn() === 'rework_skilltree', 'Falsche Datenbank.');
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $check(count($tables) === 12 && in_array('organisation_unit_employee', $tables, true), 'Aktuelles Schema erforderlich.');
    foreach ($tables as $table) $check((int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() === 0, 'Test verlangt vollstaendig leere Strukturkopie.');
    $stage = 'Synthetischer Regressionsbestand';
    $db->beginTransaction();
    foreach (['testdata_seed.sql', 'testdata_organisations.sql'] as $file) {
        $sql = preg_replace('/^--.*$/m', '', file_get_contents(dirname(__DIR__) . '/database/' . $file));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            // Ignore reset and transaction statements; the checked empty target is populated additively.
            if (preg_match('/^(INSERT|UPDATE)\b/i', $statement)) $db->exec($statement);
        }
    }
    $ownedRows = $snapshot($db);
    $db->commit();
    $run([PHP_BINARY, 'scripts/check-analysis.php', '--rework-fixture'], dirname(__DIR__));
    if (in_array('--browser', $argv, true)) {
        $stage = 'Bestehende Edge-Browsertests';
        $run(['node', 'node_modules/@playwright/test/cli.js', 'test', '--grep-invert', 'm:n'], dirname(__DIR__) . '/frontend');
    }

    $stage = 'Geteilte Personen/Aufgaben';
    $db->beginTransaction();
    $db->exec("INSERT INTO organisation_unit (id,name) VALUES (101,'Test Vorher'),(102,'Test Nachher'),(103,'Test Leer')");
    $db->exec('INSERT INTO organisation_unit_employee (organisation_unit_id,employee_id) VALUES (101,1),(101,2),(102,1),(102,2)');
    $db->exec('INSERT INTO organisation_unit_task (organisation_unit_id,task_id) VALUES (101,1),(101,2),(102,1),(102,2)');
    // Skill 1 requires 22 and 23, both require 21: shared prerequisite counted once.
    $db->exec('INSERT INTO employee_skill (organisation_unit_id,employee_id,skill_id) VALUES (101,1,22),(101,2,22),(102,1,1),(102,1,22),(102,2,1)');
    $db->exec("INSERT INTO scenario (id,name,organisation_unit_id) VALUES (101,'Test gemeinsam',101),(102,'Test gemeinsam',102)");
    $db->exec('INSERT INTO scenario_task (organisation_unit_id,scenario_id,task_id) VALUES (101,101,1),(102,102,1)');
    $ownedRows = $snapshot($db);
    $db->commit();

    $reject = static function (string $sql, int $errorCode) use ($db, $check): void {
        $db->beginTransaction();
        try {
            $db->exec($sql);
            $check(false, 'Constraint hat ungueltigen Schreibzugriff zugelassen.');
        } catch (PDOException $error) {
            $check((int) ($error->errorInfo[1] ?? 0) === $errorCode, 'Unerwarteter Datenbankfehler statt Constraint-Verletzung.');
        } finally { $db->rollBack(); }
    };
    foreach (['INSERT INTO organisation_unit_employee VALUES (101,1)', 'INSERT INTO organisation_unit_task VALUES (101,1)',
        'INSERT INTO employee_skill (organisation_unit_id,employee_id,skill_id) VALUES (101,1,22)',
        'INSERT INTO scenario_task (organisation_unit_id,scenario_id,task_id) VALUES (101,101,1)'] as $sql) $reject($sql, 1062);
    foreach (['INSERT INTO organisation_unit_employee VALUES (999,1)', 'INSERT INTO organisation_unit_employee VALUES (101,999)',
        'INSERT INTO organisation_unit_task VALUES (999,1)', 'INSERT INTO organisation_unit_task VALUES (101,999)',
        'INSERT INTO employee_skill (organisation_unit_id,employee_id,skill_id) VALUES (101,3,1)',
        'INSERT INTO employee_skill (organisation_unit_id,employee_id,skill_id) VALUES (101,1,999)',
        'INSERT INTO scenario_task (organisation_unit_id,scenario_id,task_id) VALUES (102,101,2)',
        'INSERT INTO scenario_task (organisation_unit_id,scenario_id,task_id) VALUES (101,101,11)',
        'UPDATE employee_skill SET organisation_unit_id=103 WHERE organisation_unit_id=101 AND employee_id=1',
        'UPDATE scenario_task SET organisation_unit_id=102 WHERE scenario_id=101'] as $sql) $reject($sql, 1452);
    foreach (['DELETE FROM organisation_unit_employee WHERE organisation_unit_id=101 AND employee_id=1',
        'DELETE FROM organisation_unit_task WHERE organisation_unit_id=101 AND task_id=1',
        'DELETE FROM employee WHERE id=1', 'DELETE FROM task WHERE id=1', 'DELETE FROM organisation_unit WHERE id=101',
        'UPDATE scenario SET organisation_unit_id=102 WHERE id=101'] as $sql) $reject($sql, 1451);

    $repository = new AnalysisRepository($db);
    $analysis = new SimulationAnalysis();
    $before = $repository->load(101);
    $after = $repository->load(102);
    $check(array_column($before->employees, 'id') === [1,2] && array_column($after->employees, 'id') === [1,2], 'Geteilte Personen fehlen/dupliziert.');
    $check($before->tasks === $after->tasks && count($before->tasks) === 2, 'Globale Aufgabenanforderungen inkonsistent.');
    $check($before->employees[0]['direct_skill_ids'] === [22] && $after->employees[0]['direct_skill_ids'] === [1,22], 'Direkter Besitz vermischt.');
    $check($before->scenarios[0]['task_ids'] === [1] && $after->scenarios[0]['task_ids'] === [1], 'Gemeinsame Szenarioaufgabe fehlt.');
    $b = $analysis->calculate($before);
    $a = $analysis->calculate($after);
    $check($b['summary']['counts'] === ['red'=>5,'yellow'=>0,'green'=>2], 'Vorher-Ampel falsch.');
    $check($a['summary']['counts'] === ['red'=>3,'yellow'=>0,'green'=>4], 'Nachher-Ampel falsch.');
    $check($b['employees'][0]['available_skill_ids'] === [21,22] && $a['employees'][0]['available_skill_ids'] === [1,21,22,23], 'Vererbung vermischt/dupliziert.');
    $check(array_column($a['skills'], 'carrier_count', 'id')[21] === 2, 'Mehrere Besitzwege zaehlen Person mehrfach.');
    $candidate = array_column($b['development']['candidates'], null, 'skill_id')[1];
    $check(array_column(array_filter($candidate['slots']), 'employee_id') === [1,2]
        && array_column(array_filter($candidate['slots']), 'distance') === [2,2], 'Kandidaten/Distanz verwenden fremden Besitz.');
    $check(!isset(array_column($a['development']['candidates'], null, 'skill_id')[1]), 'Gruener Skill liefert Kandidaten.');
    $excluded = $analysis->calculate($after, [], [1]);
    $check($excluded['summary']['counts'] === ['red'=>3,'yellow'=>4,'green'=>0] && array_column($excluded['employees'], 'id') === [2], 'Personensimulation falsch.');
    $check($analysis->calculate($before) === $b, 'Simulation hat Vorher veraendert.');
    $check($analysis->calculate($after, [1,2])['summary']['required_skill_count'] === 0, 'Aufgabenausschluss falsch.');
    $check($analysis->calculate($repository->load(103))['summary']['counts'] === ['red'=>0,'yellow'=>0,'green'=>0], 'Leere Einheit falsch.');
    $db->beginTransaction();
    $db->exec('DELETE FROM employee_skill WHERE organisation_unit_id=101 AND employee_id=1');
    $db->commit();
    $check($repository->load(101)->employees[0]['direct_skill_ids'] === [], 'Skilllose Mitgliedschaft fehlt.');
    $check($repository->load(102)->employees[0]['direct_skill_ids'] === [1,22], 'Loeschen wirkt in anderer Einheit.');
    $db->exec('INSERT INTO employee_skill (organisation_unit_id,employee_id,skill_id) VALUES (101,1,22)');

    if (in_array('--browser', $argv, true) || in_array('--browser-rework', $argv, true)) {
        $stage = 'Vorher/Nachher im Edge-Browser';
        putenv('SKILLTREE_REWORK_TEST=1');
        $run(['node', 'node_modules/@playwright/test/cli.js', 'test', 'rework-organisations.spec.ts'], dirname(__DIR__) . '/frontend');
    }
    echo "OK: $checks Ziel-, Constraint- und m:n-Analysepruefungen erfolgreich.\n";
    $cleanup();
    foreach ($tables as $table) $check((int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() === 0, 'Datenbank nach Test nicht leer.');
    echo "OK: alle zwoelf Tabellen wieder leer.\n";
} catch (Throwable $error) {
    // Avoid PDO details; assertions and subprocess logs identify the failing stage.
    fwrite(STDERR, 'FEHLER bei ' . $stage . ($error instanceof PDOException ? ' (SQL/Constraint).' : ': ' . $error->getMessage()) . "\n");
    try { $cleanup(); } catch (Throwable) { fwrite(STDERR, "FEHLER: Bereinigung fehlgeschlagen.\n"); }
    exit(1);
}
