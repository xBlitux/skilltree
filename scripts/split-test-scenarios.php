<?php
declare(strict_types=1);
use Dotenv\Dotenv;
use Skilltree\Infrastructure\Database\ConnectionFactory;
require dirname(__DIR__) . '/vendor/autoload.php';
$db = null;
try {
    Dotenv::createImmutable(dirname(__DIR__), '.env.setup')->load();
    if ($_ENV['DB_NAME'] !== 'testdata_skilltree' || $_ENV['DB_USER'] !== 'skilltree_test_setup'
        || !in_array($_ENV['DB_HOST'], ['localhost', '127.0.0.1'], true)) throw new RuntimeException('Unexpected target');
    $db = ConnectionFactory::fromEnvironment();
    if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'testdata_skilltree') throw new RuntimeException('Unexpected database');
    $scenarios = $db->query('SELECT id,name,organisation_unit_id FROM scenario ORDER BY id')->fetchAll();
    $links = $db->query('SELECT st.scenario_id,st.task_id,t.organisation_unit_id FROM scenario_task st JOIN task t ON t.id=st.task_id ORDER BY st.scenario_id,st.task_id')->fetchAll();
    if (!in_array('--apply', $argv, true)) {
        echo json_encode(['scenarios'=>$scenarios,'task_links'=>$links], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";
        exit;
    }
    // Only migrate the exact previously supplied synthetic examples.
    $expected = [1=>[9,10,11,14],2=>[1,2,3,11,13,14,16],3=>range(1,16)];
    if (array_map('intval', array_column($scenarios,'id')) !== [1,2,3]
        || array_column($scenarios,'name') !== ['Kernaufgaben','Analyse und Planung','Erweiterter Betrieb']) throw new RuntimeException('Unexpected scenarios');
    $actual=[];
    foreach ($links as $link) $actual[(int)$link['scenario_id']][]=(int)$link['task_id'];
    if ($actual !== $expected) throw new RuntimeException('Unexpected task assignments');
    $db->beginTransaction();
    $insert = $db->prepare('INSERT INTO scenario (name,organisation_unit_id) VALUES (?,?)');
    $assign = $db->prepare('INSERT INTO scenario_task (scenario_id,task_id) VALUES (?,?)');
    $remove = $db->prepare('DELETE FROM scenario_task WHERE scenario_id=? AND task_id=?');
    foreach ($scenarios as $scenario) {
        $db->prepare('UPDATE scenario SET organisation_unit_id=1 WHERE id=?')->execute([$scenario['id']]);
        foreach ([2,3] as $unit) {
            $insert->execute([$scenario['name'],$unit]);
            $newId=(int)$db->lastInsertId();
            foreach ($links as $link) {
                if ((int)$link['scenario_id'] !== (int)$scenario['id'] || (int)$link['organisation_unit_id'] !== $unit) continue;
                $assign->execute([$newId,$link['task_id']]);
                $remove->execute([$scenario['id'],$link['task_id']]);
            }
        }
    }
    $invalid = $db->query('SELECT COUNT(*) FROM scenario_task st JOIN scenario s ON s.id=st.scenario_id JOIN task t ON t.id=st.task_id WHERE s.organisation_unit_id<>t.organisation_unit_id')->fetchColumn();
    if ((int)$invalid !== 0) throw new RuntimeException('Cross-organisation task');
    $db->commit();
    echo "OK: neun organisationsgebundene Szenarien; bisherige Aufgabenauswahlen pro Einheit erhalten.\n";
} catch (Throwable $error) {
    if ($db?->inTransaction()) $db->rollBack();
    fwrite(STDERR,"ABBRUCH: Testziel, Schema oder vorbereitete Beispielszenarien unerwartet. Keine Teiltransaktion gespeichert.\n");
    exit(1);
}
