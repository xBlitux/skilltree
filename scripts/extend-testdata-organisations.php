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
    if (!in_array('--apply', $argv, true)) { echo "Geprueft: testdata_skilltree. Geplant: zwei synthetische Einheiten und neun organisationsgebundene Szenarien. --apply zum Schreiben.\n"; exit; }
    $db->beginTransaction();
    $db->exec("INSERT INTO organisation_unit (id,name) VALUES (2,'Testorganisation Datenservice'),(3,'Testorganisation Verwaltung')");
    $db->exec("INSERT INTO employee (id,organisation_unit_id,first_name,last_name) VALUES (6,2,'Tina','Test'),(7,2,'Tom','Test'),(8,2,'Tessa','Test'),(9,3,'Vera','Versuch'),(10,3,'Viktor','Versuch'),(11,3,'Viola','Versuch')");
    $db->exec("INSERT INTO task (id,organisation_unit_id,name,description) VALUES (11,2,'Test: Daten analysieren','Synthetische Beispieldaten'),(12,2,'Test: Migration begleiten','Synthetische Beispieldaten'),(13,2,'Test: Berichte erstellen','Synthetische Beispieldaten'),(14,3,'Test: Budget planen','Synthetische Beispieldaten'),(15,3,'Test: Kommunikation sichern','Synthetische Beispieldaten'),(16,3,'Test: Beschaffung organisieren','Synthetische Beispieldaten')");
    $db->exec('INSERT INTO task_skill (task_id,skill_id) VALUES (11,1),(12,20),(13,3),(14,14),(15,19),(16,15)');
    $db->exec('INSERT INTO employee_skill (employee_id,skill_id) VALUES (6,1),(6,4),(7,3),(7,24),(8,1),(9,14),(9,15),(10,15),(11,19)');
    $db->exec("INSERT INTO scenario (id,name,organisation_unit_id) VALUES (1,'Kernaufgaben',1),(2,'Analyse und Planung',1),(3,'Erweiterter Betrieb',1),(4,'Kernaufgaben',2),(5,'Kernaufgaben',3),(6,'Analyse und Planung',2),(7,'Analyse und Planung',3),(8,'Erweiterter Betrieb',2),(9,'Erweiterter Betrieb',3)");
    $statement = $db->prepare('INSERT INTO scenario_task (scenario_id,task_id) VALUES (?,?)');
    $core = $db->query("SELECT id FROM task WHERE organisation_unit_id=1 AND name IN ('Beschaffung vorbereiten','Sicherheitsvorfall bearbeiten')")->fetchAll(PDO::FETCH_COLUMN);
    if (count($core) !== 2) throw new RuntimeException('Szenario-1-Aufgaben fehlen.');
    foreach ([1 => $core, 2 => [1,2,3], 3 => range(1,10), 4 => [11], 5 => [14], 6 => [11,13], 7 => [14,16], 8 => [11,12,13], 9 => [14,15,16]] as $scenario => $tasks) {
        foreach ($tasks as $task) $statement->execute([$scenario, $task]);
    }
    $db->commit();
    echo "OK: zwei Testeinheiten, sechs Personen, sechs Aufgaben und neun organisationsgebundene Szenarien additiv angelegt. Originaleinheit unveraendert.\n";
} catch (Throwable $error) {
    if ($db?->inTransaction()) $db->rollBack();
    // Do not emit connection strings or credentials.
    fwrite(STDERR, "ABBRUCH: Ziel, Berechtigungen oder Ausgangsdaten pruefen; keine Teiltransaktion gespeichert.\n");
    exit(1);
}
