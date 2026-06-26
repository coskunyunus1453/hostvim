<?php

/**
 * SQLite veritabanını MySQL uyumlu SQL dosyasına aktarır.
 * Kullanım: php deploy/export-sqlite-to-mysql.php > deploy/hostvim_dump.sql
 */

$dbPath = __DIR__ . '/../database/database.sqlite';
if (! file_exists($dbPath)) {
    fwrite(STDERR, "SQLite dosyası bulunamadı: {$dbPath}\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
    ->fetchAll(PDO::FETCH_COLUMN);

echo "-- HostVim MySQL dump (SQLite export)\n";
echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $create = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table))->fetchColumn();
    if (! $create) {
        continue;
    }

    $mysqlCreate = convertCreateTable($create, $table);
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $mysqlCreate . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        continue;
    }

    $columns = array_keys($rows[0]);
    $colList = implode('`, `', $columns);

    foreach (array_chunk($rows, 100) as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $vals = [];
            foreach ($columns as $col) {
                $vals[] = escapeValue($row[$col]);
            }
            $values[] = '(' . implode(', ', $vals) . ')';
        }
        echo "INSERT INTO `{$table}` (`{$colList}`) VALUES\n" . implode(",\n", $values) . ";\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";

function convertCreateTable(string $sql, string $table): string
{
    $sql = preg_replace('/\bINTEGER PRIMARY KEY AUTOINCREMENT\b/i', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', $sql);
    $sql = preg_replace('/\bINTEGER\b/i', 'BIGINT', $sql);
    $sql = preg_replace('/\bREAL\b/i', 'DOUBLE', $sql);
    $sql = preg_replace('/\bBLOB\b/i', 'LONGBLOB', $sql);
    $sql = preg_replace('/\bAUTOINCREMENT\b/i', 'AUTO_INCREMENT', $sql);
    $sql = preg_replace('/\bBOOLEAN\b/i', 'TINYINT(1)', $sql);
    $sql = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP NULL', $sql);
    $sql = preg_replace('/"([^"]+)"/', '`$1`', $sql);
    $sql = preg_replace('/\bCREATE TABLE\s+' . preg_quote($table, '/') . '\b/i', "CREATE TABLE `{$table}`", $sql);

    if (! str_contains(strtoupper($sql), 'ENGINE=')) {
        $sql = rtrim($sql, ';') . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    return $sql;
}

function escapeValue(mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value) . "'";
}
