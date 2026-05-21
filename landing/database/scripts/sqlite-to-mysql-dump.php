<?php

/**
 * SQLite (Laravel landing DB) → tek MariaDB/MySQL import dosyası.
 * Kullanım: php database/scripts/sqlite-to-mysql-dump.php [çıktı-dosyası]
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$sqlitePath = $root.'/database/database.sqlite';
$outPath = $argv[1] ?? $root.'/database/hostvim_landing_full.sql';

if (! is_readable($sqlitePath)) {
    fwrite(STDERR, "SQLite bulunamadı: {$sqlitePath}\n");
    exit(1);
}

$pdo = new PDO('sqlite:'.$sqlitePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$tables = $pdo->query(
    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
)->fetchAll(PDO::FETCH_COLUMN);

$fh = fopen($outPath, 'wb');
if ($fh === false) {
    fwrite(STDERR, "Yazılamadı: {$outPath}\n");
    exit(1);
}

$write = static function (string $line) use ($fh): void {
    fwrite($fh, $line."\n");
};

$write('-- Hostvim Landing — tam veritabanı dökümü (şema + veri)');
$write('-- Kaynak: database/database.sqlite');
$write('-- Oluşturulma: '.date('Y-m-d H:i:s'));
$write('-- MariaDB / MySQL 10.4+ utf8mb4_unicode_ci');
$write('');
$write('/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;');
$write('/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;');
$write('/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;');
$write('/*!40101 SET NAMES utf8mb4 */;');
$write('/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;');
$write('/*!40103 SET TIME_ZONE=\'+00:00\' */;');
$write('/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;');
$write('/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;');
$write('/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\' */;');
$write('/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;');
$write('');

$mapColumnType = static function (string $name, string $sqliteType, bool $isPk): string {
    $t = strtolower($sqliteType);
    if ($isPk && str_contains($t, 'int')) {
        return 'bigint(20) unsigned NOT NULL AUTO_INCREMENT';
    }
    if (str_contains($t, 'tinyint')) {
        return 'tinyint(1)';
    }
    if (str_contains($t, 'int') || $t === '') {
        if (str_contains($name, '_id') || $name === 'id' || str_ends_with($name, '_count')) {
            return 'bigint(20) unsigned';
        }

        return 'int(11)';
    }
    if (str_contains($t, 'datetime') || str_contains($t, 'timestamp')) {
        return 'timestamp';
    }
    if (str_contains($t, 'text')) {
        return in_array($name, ['content', 'changelog', 'body', 'community_admin_notes'], true)
            ? 'longtext'
            : 'text';
    }
    if (str_contains($t, 'varchar')) {
        if (preg_match('/varchar\((\d+)\)/', $t, $m)) {
            $len = (int) $m[1];

            return 'varchar('.min(max($len, 1), 2048).')';
        }

        return match ($name) {
            'email' => 'varchar(255)',
            'remember_token' => 'varchar(100)',
            'session_id' => 'varchar(255)',
            'version', 'channel', 'profile', 'git_tag', 'min_panel_version' => 'varchar(64)',
            'artifact_sha256' => 'varchar(64)',
            'robots' => 'varchar(64)',
            default => 'varchar(255)',
        };
    }
    if (str_contains($t, 'numeric') || str_contains($t, 'decimal')) {
        return 'decimal(12,2)';
    }

    return 'varchar(255)';
};

$buildColumnLine = static function (array $c, bool $isPk, PDO $pdo) use ($mapColumnType): string {
    $name = (string) $c['name'];
    $sqliteType = (string) $c['type'];
    $notNull = (int) $c['notnull'] === 1;
    $dfltRaw = $c['dflt_value'];
    $baseType = $mapColumnType($name, $sqliteType, $isPk);

    if ($isPk) {
        return "  `{$name}` {$baseType}";
    }

    $isTimestamp = str_contains(strtolower($sqliteType), 'datetime')
        || str_contains(strtolower($sqliteType), 'timestamp')
        || $baseType === 'timestamp';

    if ($isTimestamp) {
        $dflt = strtoupper(trim((string) ($dfltRaw ?? '')));
        if ($notNull && $dflt === 'CURRENT_TIMESTAMP') {
            return "  `{$name}` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
        }
        if ($notNull) {
            return "  `{$name}` timestamp NOT NULL";
        }

        return "  `{$name}` timestamp NULL DEFAULT NULL";
    }

    $line = "  `{$name}` {$baseType}";
    if ($notNull) {
        $line .= ' NOT NULL';
    }

    if ($dfltRaw !== null) {
        $dv = trim((string) $dfltRaw);
        if (strtoupper($dv) === 'CURRENT_TIMESTAMP') {
            $line .= ' DEFAULT CURRENT_TIMESTAMP';
        } elseif (preg_match("/^'(.*)'$/s", $dv, $m)) {
            $line .= ' DEFAULT '.$pdo->quote(str_replace("''", "'", $m[1]));
        } elseif (preg_match('/^\(\'(.*)\'\)$/', $dv, $m)) {
            $line .= ' DEFAULT '.$pdo->quote($m[1]);
        } elseif (is_numeric($dv)) {
            $line .= ' DEFAULT '.$dv;
        } elseif (strtolower($dv) === 'null') {
            $line .= ' DEFAULT NULL';
        }
    } elseif (! $notNull && str_contains($baseType, 'varchar')) {
        // nullable varchar without explicit default — ok
    }

    return $line;
};

foreach ($tables as $table) {
    $write('--');
    $write("-- Table structure for table `{$table}`");
    $write('--');
    $write("DROP TABLE IF EXISTS `{$table}`;");
    $write('/*!40101 SET @saved_cs_client     = @@character_set_client */;');
    $write('/*!40101 SET character_set_client = utf8 */;');

    $cols = $pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
    $pkCols = array_values(array_filter($cols, static fn (array $c): bool => (int) $c['pk'] > 0));

    $colDefs = [];
    foreach ($cols as $c) {
        $isPk = (int) $c['pk'] > 0 && count($pkCols) === 1 && $pkCols[0]['name'] === $c['name'];
        $colDefs[] = $buildColumnLine($c, $isPk, $pdo);
    }

    $indexes = $pdo->query("PRAGMA index_list({$table})")->fetchAll(PDO::FETCH_ASSOC);
    $constraints = [];
    if (count($pkCols) === 1) {
        $constraints[] = '  PRIMARY KEY (`'.$pkCols[0]['name'].'`)';
    } elseif (count($pkCols) > 1) {
        $pkNames = array_map(static fn (array $c): string => '`'.$c['name'].'`', $pkCols);
        $constraints[] = '  PRIMARY KEY ('.implode(', ', $pkNames).')';
    }

    $createSql = (string) $pdo->query(
        "SELECT sql FROM sqlite_master WHERE type='table' AND name=".$pdo->quote($table)
    )->fetchColumn();
    if (preg_match_all(
        '/foreign\s+key\s*\(\s*[`"]?(\w+)[`"]?\s*\)\s*references\s*[`"]?(\w+)[`"]?\s*\(\s*[`"]?(\w+)[`"]?\s*\)(?:\s+on\s+delete\s+(set\s+null|cascade|restrict|no\s+action))?/i',
        $createSql,
        $fks,
        PREG_SET_ORDER
    )) {
        foreach ($fks as $fk) {
            $col = $fk[1];
            $refTable = $fk[2];
            $refCol = $fk[3];
            $onDelete = '';
            if (preg_match('/on\s+delete\s+(set\s+null|cascade|restrict|no\s+action)/i', $fk[0], $od)) {
                $onDelete = ' ON DELETE '.strtoupper($od[1]);
            }
            $constraints[] = '  CONSTRAINT `'.$table.'_'.$col.'_foreign` FOREIGN KEY (`'.$col.'`) REFERENCES `'.$refTable.'` (`'.$refCol.'`)'.$onDelete;
        }
    }

    foreach ($indexes as $idx) {
        if ((int) ($idx['origin'] ?? 0) === 'pk' || ($idx['name'] ?? '') === 'primary') {
            continue;
        }
        $idxName = (string) $idx['name'];
        if (str_starts_with($idxName, 'sqlite_autoindex_')) {
            continue;
        }
        $idxCols = $pdo->query("PRAGMA index_info({$idxName})")->fetchAll(PDO::FETCH_ASSOC);
        $colList = implode(', ', array_map(static fn (array $r): string => '`'.$r['name'].'`', $idxCols));
        if ($colList === '') {
            continue;
        }
        $unique = (int) ($idx['unique'] ?? 0) === 1;
        $constraints[] = '  '.($unique ? 'UNIQUE ' : '').'KEY `'.$idxName.'` ('.$colList.')';
    }

    $create = "CREATE TABLE `{$table}` (\n".implode(",\n", array_merge($colDefs, $constraints))."\n) ";
    $create .= 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
    $write($create);
    $write('/*!40101 SET character_set_client = @saved_cs_client */;');
    $write('');

    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    $write('--');
    $write("-- Dumping data for table `{$table}`");
    $write('--');
    if ($count === 0) {
        $write('');
        continue;
    }

    $write("LOCK TABLES `{$table}` WRITE;");
    $write("/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;");

    $rows = $pdo->query("SELECT * FROM `{$table}`");
    $colNames = array_column($cols, 'name');
    $colListSql = implode(', ', array_map(static fn (string $n): string => '`'.$n.'`', $colNames));

    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        $vals = [];
        foreach ($colNames as $cn) {
            $v = $row[$cn] ?? null;
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (is_int($v) || is_float($v)) {
                $vals[] = (string) $v;
            } else {
                $vals[] = $pdo->quote((string) $v);
            }
        }
        $write('INSERT INTO `'.$table.'` ('.$colListSql.') VALUES ('.implode(', ', $vals).');');
    }

    $write("/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;");
    $write('UNLOCK TABLES;');
    $write('');
}

$write('/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;');
$write('/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;');
$write('/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;');
$write('/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;');
$write('/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;');
$write('/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;');
$write('/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;');
$write('/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;');
$write('');
$write('-- Dump completed');

fclose($fh);

echo "OK: {$outPath} (".number_format(filesize($outPath)).' bytes, '.count($tables).' tablo)'."\n";
