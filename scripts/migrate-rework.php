<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Skilltree\Infrastructure\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';
$step = 'Zielpruefung';
try {
    Dotenv::createImmutable(dirname(__DIR__))->load();
    if ($_ENV['DB_NAME'] !== 'rework_skilltree' || $_ENV['DB_USER'] !== 'db_architect'
        || !in_array($_ENV['DB_HOST'], ['localhost', '127.0.0.1'], true)) {
        throw new RuntimeException('Erwartet: lokale rework_skilltree mit db_architect.');
    }
    $db = ConnectionFactory::fromEnvironment();
    if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'rework_skilltree') {
        throw new RuntimeException('Falsches Datenbankziel.');
    }
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $definitions = [];
    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $definitions[$table] = $db->query("SHOW CREATE TABLE $quoted")->fetch(PDO::FETCH_NUM)[1];
    }
    $export = static function (array $definitions): string {
        return "-- Struktur ohne Daten und ohne DROP TABLE. MariaDB 10.4.32.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n"
            . implode(";\n\n", array_map(static fn ($sql) => preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql), $definitions))
            . ";\n\nSET FOREIGN_KEY_CHECKS=1;\n";
    };
    if (in_array('--export', $argv, true)) {
        if (!isset($definitions['organisation_unit_employee'], $definitions['organisation_unit_task'])
            || str_contains($definitions['employee'], '`organisation_unit_id`')) {
            throw new RuntimeException('Export erwartet fertig migriertes Schema.');
        }
        file_put_contents(dirname(__DIR__) . '/database/24092026_Structure_rework_skilltree.sql', $export($definitions));
        echo "OK: aktuelles Schema ohne Daten exportiert.\n";
        exit;
    }
    // This migration targets the user's empty structure copy, never a populated DB.
    if (count($tables) !== 10 || isset($definitions['organisation_unit_employee'])
        || !str_contains($definitions['employee'] ?? '', '`organisation_unit_id`')
        || !str_contains($definitions['employee_skill'] ?? '', '`uq_employee_skill`')) {
        throw new RuntimeException('Ausgangsschema unerwartet oder bereits migriert.');
    }
    foreach ($tables as $table) {
        if ((int) $db->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`')->fetchColumn() !== 0) {
            throw new RuntimeException('Migration erwartet eine vollstaendig leere Strukturkopie.');
        }
    }
    if (!in_array('--apply', $argv, true)) {
        echo "OK: rework_skilltree, zehn leere Tabellen. --apply migriert, --export exportiert danach.\n";
        exit;
    }
    $directory = dirname(__DIR__) . '/.local';
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    $backup = $directory . '/rework-before-' . date('Ymd-His') . '.sql';
    if (file_put_contents($backup, $export($definitions)) === false) throw new RuntimeException('Sicherung fehlgeschlagen.');
    $sql = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_organisation_memberships.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $index => $statement) {
        $step = 'DDL/SQL-Schritt ' . ($index + 1);
        $db->exec($statement);
    }
    echo "OK: rework_skilltree migriert; lokale Struktursicherung unter .local/. Keine Beispieldaten angelegt.\n";
} catch (Throwable $error) {
    // SQL exceptions can contain credentials or row values; never print them.
    $reason = $error instanceof PDOException ? 'Ziel/Schema/Berechtigungen pruefen.' : $error->getMessage();
    fwrite(STDERR, "ABBRUCH bei $step: $reason\n");
    if ($step !== 'Zielpruefung') {
        fwrite(STDERR, "DDL ist nicht transaktional; bei Teilumbau lokale Struktursicherung verwenden.\n");
    }
    exit(1);
}
