<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Skilltree\Infrastructure\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';
try {
    Dotenv::createImmutable(dirname(__DIR__))->load();
    if ($_ENV['DB_NAME'] !== 'rework_skilltree'
        || !in_array($_ENV['DB_HOST'], ['localhost', '127.0.0.1'], true)) {
        throw new RuntimeException('Erwartet: lokale rework_skilltree.');
    }
    $db = ConnectionFactory::fromEnvironment();
    if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'rework_skilltree') {
        throw new RuntimeException('Falsches Datenbankziel.');
    }
    echo 'Datenbank: rework_skilltree; Version: ', $db->query('SELECT VERSION()')->fetchColumn(), "\n";
    foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        echo $table, ': ', $db->query("SELECT COUNT(*) FROM $quoted")->fetchColumn(), " Zeilen\n";
        echo $db->query("SHOW CREATE TABLE $quoted")->fetch(PDO::FETCH_NUM)[1], ";\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, "ABBRUCH: Konfiguration, Datenbankziel oder Zugriff pruefen.\n");
    exit(1);
}
