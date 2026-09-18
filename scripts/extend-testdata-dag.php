<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\SkillAnalysis;
use Skilltree\Infrastructure\Database\AnalysisRepository;
use Skilltree\Infrastructure\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

// One-time additive upgrade of the synthetic 20-skill seed. Never run the reset section.
$connection = null;
try {
    Dotenv::createImmutable(dirname(__DIR__), '.env.setup')->load();
    $value = static fn (string $key) => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value('DB_NAME') !== 'testdata_skilltree'
        || !in_array($value('DB_HOST'), ['localhost', '127.0.0.1'], true)
        || $value('DB_PORT') !== '3306'
        || $value('DB_USER') !== 'skilltree_test_setup') {
        throw new RuntimeException('Unexpected database target.');
    }
    $connection = ConnectionFactory::fromEnvironment();
    if ($connection->query('SELECT DATABASE()')->fetchColumn() !== 'testdata_skilltree'
        || $connection->query('SELECT CURRENT_USER()')->fetchColumn() !== 'skilltree_test_setup@localhost') {
        throw new RuntimeException('Unexpected connected database or account.');
    }
    $before = (new AnalysisRepository($connection))->load();
    $baseline = (new SkillAnalysis())->calculate($before);
    if (array_keys($before->skills) !== range(1, 20) || $before->prerequisites !== []
        || $before->organisation['name'] !== 'Testorganisation Personalentwicklung'
        || $baseline['summary'] !== [
            'task_count' => 10, 'employee_count' => 5,
            'required_skill_count' => 20, 'available_skill_count' => 18,
            'counts' => ['red' => 2, 'yellow' => 5, 'green' => 13],
        ]
        || (int) $connection->query("SELECT COUNT(*) FROM skill WHERE origin = 'test'")->fetchColumn() !== 20) {
        throw new RuntimeException('Expected unchanged synthetic baseline.');
    }

    $seed = file_get_contents(dirname(__DIR__) . '/database/testdata_seed.sql');
    if ($seed === false || !preg_match('/-- BEGIN DAG EXTENSION\R(.*?)-- END DAG EXTENSION/s', $seed, $match)) {
        throw new RuntimeException('Missing extension in seed.');
    }
    $sql = preg_replace('/^--.*$/m', '', $match[1]);
    $statements = array_values(array_filter(array_map('trim', explode(';', $sql))));
    if (count($statements) !== 2
        || !str_starts_with($statements[0], 'INSERT INTO `skill` ')
        || !str_starts_with($statements[1], 'INSERT INTO `skill_prerequisite` ')) {
        throw new RuntimeException('Unexpected extension statements.');
    }
    if ($argc === 1) {
        echo "OK: testdata_skilltree, synthetische Basis mit 20 Skills bestaetigt.\n";
        echo "Geplant: 6 neue Skills und 12 DAG-Kanten. Zum Schreiben --apply angeben.\n";
        exit(0);
    }
    if ($argc !== 2 || $argv[1] !== '--apply') {
        throw new RuntimeException('Unexpected command arguments.');
    }

    $connection->beginTransaction();
    foreach ($statements as $statement) {
        $connection->exec($statement);
    }
    $skills = [];
    foreach ($connection->query('SELECT id, name, skill_group_id FROM skill ORDER BY id') as $row) {
        $id = (int) $row['id'];
        $skills[$id] = ['id' => $id, 'name' => $row['name'], 'skill_group_id' => (int) $row['skill_group_id']];
    }
    $edges = [];
    foreach ($connection->query('SELECT skill_id, prerequisite_skill_id FROM skill_prerequisite') as $row) {
        $edges[(int) $row['skill_id']][] = (int) $row['prerequisite_skill_id'];
    }
    // Includes a global cycle check before committing any additions.
    $result = (new SkillAnalysis())->calculate(new AnalysisDataset(
        $before->organisation, $skills, $before->tasks, $before->employees, $edges,
    ));
    if ($result['summary'] !== [
        'task_count' => 10, 'employee_count' => 5,
        'required_skill_count' => 26, 'available_skill_count' => 23,
        'counts' => ['red' => 3, 'yellow' => 7, 'green' => 16],
    ] || array_sum(array_map('count', $edges)) !== 12) {
        throw new RuntimeException('Unexpected DAG calculation.');
    }
    $connection->commit();
    echo "OK: 6 Skills und 12 Kanten ergaenzt; DAG zyklusfrei. Soll=26, Ist=23, Rot/Gelb/Gruen=3/7/16.\n";
    echo "Bestehende Skills, Aufgaben, Personen und direkte Zuordnungen bleiben erhalten.\n";
} catch (Throwable $exception) {
    if ($connection !== null && $connection->inTransaction()) {
        $connection->rollBack();
    }
    // Dotenv or PDO errors can contain secrets; never print the exception.
    fwrite(STDERR, "ABBRUCH: Setup-Ziel, urspruenglicher Seed oder DAG-Pruefung ungueltig. Keine Erweiterung gespeichert.\n");
    exit(1);
}
