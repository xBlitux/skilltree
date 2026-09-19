<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\SkillAnalysis;
use Skilltree\Infrastructure\Database\AnalysisRepository;
use Skilltree\Infrastructure\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';
$connection = null;
try {
    Dotenv::createImmutable(dirname(__DIR__), '.env.setup')->load();
    $value = static fn (string $key) => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value('DB_NAME') !== 'testdata_skilltree' || $value('DB_USER') !== 'skilltree_test_setup'
        || !in_array($value('DB_HOST'), ['localhost', '127.0.0.1'], true) || $value('DB_PORT') !== '3306') {
        throw new RuntimeException('Unexpected target.');
    }
    $connection = ConnectionFactory::fromEnvironment();
    if ($connection->query('SELECT DATABASE()')->fetchColumn() !== 'testdata_skilltree'
        || $connection->query('SELECT CURRENT_USER()')->fetchColumn() !== 'skilltree_test_setup@localhost') {
        throw new RuntimeException('Unexpected connection.');
    }
    $before = (new AnalysisRepository($connection))->load();
    $analysis = (new SkillAnalysis())->calculate($before);
    if (array_keys($before->skills) !== range(1, 26) || array_column($before->groups, 'id') !== range(1, 4)
        || array_column($before->groups, 'parent_skill_group_id') !== [null, 1, 1, 1]
        || $before->organisation['name'] !== 'Testorganisation Personalentwicklung'
        || (int) $connection->query("SELECT COUNT(*) FROM skill WHERE origin = 'test'")->fetchColumn() !== 26
        || $analysis['summary'] !== ['task_count' => 10, 'employee_count' => 5, 'required_skill_count' => 26,
            'available_skill_count' => 23, 'counts' => ['red' => 3, 'yellow' => 7, 'green' => 16]]) {
        throw new RuntimeException('Unexpected baseline.');
    }
    $seed = file_get_contents(dirname(__DIR__) . '/database/testdata_seed.sql');
    if (!preg_match('/-- BEGIN TAXONOMY EXTENSION\R(.*?)-- END TAXONOMY EXTENSION/s', $seed, $match)) {
        throw new RuntimeException('Missing extension.');
    }
    // Descriptions deliberately contain no semicolons: split only this checked block.
    $sql = preg_replace('/^--.*$/m', '', $match[1]);
    $statements = array_values(array_filter(array_map('trim', explode(';', $sql))));
    if (count($statements) !== 5) throw new RuntimeException('Unexpected SQL block.');
    if ($argc === 1) {
        echo "OK: lokale synthetische Basis bestaetigt. Geplant: 6 Gruppen, 3 Katalogskills, 3 Gruppen umhaengen. --apply zum Schreiben.\n";
        exit(0);
    }
    if ($argc !== 2 || $argv[1] !== '--apply') throw new RuntimeException('Unexpected arguments.');
    $connection->beginTransaction();
    foreach ($statements as $statement) $connection->exec($statement);
    $skills = [];
    foreach ($connection->query('SELECT id, name, skill_group_id FROM skill ORDER BY id') as $row) {
        $id = (int) $row['id'];
        $skills[$id] = ['id' => $id, 'name' => $row['name'], 'skill_group_id' => (int) $row['skill_group_id']];
    }
    $after = (new SkillAnalysis())->calculate(new AnalysisDataset($before->organisation, $skills, $before->tasks, $before->employees, $before->prerequisites));
    if (count($skills) !== 29 || $after['summary'] !== $analysis['summary']
        || $after['required_skill_ids'] !== $analysis['required_skill_ids']
        || $after['available_skill_ids'] !== $analysis['available_skill_ids']) throw new RuntimeException('Changed calculation.');
    $parents = $connection->query('SELECT id, parent_skill_group_id FROM skill_group')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($skills as $skill) {
        $seen = []; $group = $skill['skill_group_id'];
        while ($group !== null) {
            if (isset($seen[$group]) || !array_key_exists($group, $parents)) throw new RuntimeException('Invalid hierarchy.');
            $seen[$group] = true; $group = $parents[$group];
        }
        if (count($seen) !== 4) throw new RuntimeException('Expected four levels.');
    }
    $connection->commit();
    echo "OK: 10 Gruppen, vier Ebenen vor jeder Skillliste, 29 Skills. Soll=26, Ist=23, Ampel=3/7/16 unveraendert.\n";
} catch (Throwable $exception) {
    if ($connection !== null && $connection->inTransaction()) $connection->rollBack();
    fwrite(STDERR, "ABBRUCH: Testziel, Ausgangsdaten oder Erweiterung ungueltig; keine Aenderungen gespeichert.\n");
    exit(1);
}
