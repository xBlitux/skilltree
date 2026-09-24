<?php
declare(strict_types=1);
use Dotenv\Dotenv;
use Skilltree\Infrastructure\Database\ConnectionFactory;
require dirname(__DIR__) . '/vendor/autoload.php';
$db = null;
try {
    Dotenv::createImmutable(dirname(__DIR__), '.env.setup')->load();
    if ($_ENV['DB_NAME'] !== 'testdata_skilltree' || $_ENV['DB_USER'] !== 'skilltree_test_setup'
        || !in_array($_ENV['DB_HOST'], ['localhost', '127.0.0.1'], true)) throw new RuntimeException('Falsches Testziel.');
    $db = ConnectionFactory::fromEnvironment();
    if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'testdata_skilltree') throw new RuntimeException('Falsche Datenbank.');
    if ((int) $db->query('SELECT COUNT(*) FROM organisation_unit')->fetchColumn() !== 1
        || $db->query('SELECT name FROM organisation_unit WHERE id=1')->fetchColumn() !== 'Testorganisation Personalentwicklung'
        || (int) $db->query('SELECT COUNT(*) FROM skill')->fetchColumn() !== 29
        || (int) $db->query('SELECT COUNT(*) FROM scenario')->fetchColumn() !== 0) throw new RuntimeException('Ausgangsstand nicht erwartet oder Erweiterung bereits vorhanden.');
    $db->query('SELECT scenario_id, task_id FROM scenario_task LIMIT 1');
    $core = $db->query("SELECT t.id FROM task t JOIN organisation_unit_task ot ON ot.task_id=t.id
        WHERE ot.organisation_unit_id=1 AND t.name IN ('Beschaffung vorbereiten','Sicherheitsvorfall bearbeiten') ORDER BY t.id")->fetchAll(PDO::FETCH_COLUMN);
    if (array_map('intval', $core) !== [9,10]) throw new RuntimeException('Szenario-1-Aufgaben stimmen nicht mit dem Seed ueberein.');
    if (!in_array('--apply', $argv, true)) { echo "Geprueft: testdata_skilltree. Geplant: zwei synthetische Einheiten und neun organisationsgebundene Szenarien. --apply zum Schreiben.\n"; exit; }
    $db->beginTransaction();
    $sql = file_get_contents(dirname(__DIR__) . '/database/testdata_organisations.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) $db->exec($statement);
    $db->commit();
    echo "OK: zwei Testeinheiten, sechs Personen, sechs Aufgaben und neun organisationsgebundene Szenarien additiv angelegt. Originaleinheit unveraendert.\n";
} catch (Throwable $error) {
    if ($db?->inTransaction()) $db->rollBack();
    // Do not emit connection strings or credentials.
    fwrite(STDERR, "ABBRUCH: Ziel, Berechtigungen oder Ausgangsdaten pruefen; keine Teiltransaktion gespeichert.\n");
    exit(1);
}
