<?php
/**
 * Developer tool: regenerates install/install.sql from the current database.
 *
 * install.sql is for importing by hand through phpMyAdmin instead of running
 * the web installer. It holds the table structure plus the whole content
 * library, and no user accounts or projects.
 *
 * Run it on your own machine, not on the host:
 *     php install/export-sql.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This can only be run from the command line.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use Install\Schema;

/** Only these tables get their data exported - never user data */
const DATA_TABLES = [
    'library_items',
    'mission_templates',
    'game_templates',
    'community_posts',
    'marketplace_items',
];

$pdo = Database::connect();
$out = [];

$out[] = '-- =====================================================================';
$out[] = '-- GameCraft Studio ' . GC_VERSION . ' - schema and content library';
$out[] = '-- Generated ' . date('j M Y H:i');
$out[] = '--';
$out[] = '-- HOW TO USE THIS ON cPANEL:';
$out[] = '--   1. In cPanel > MySQL Databases, create a database and user, grant ALL PRIVILEGES.';
$out[] = '--   2. Open phpMyAdmin and select that database.';
$out[] = '--   3. Import tab > choose this file > Go.';
$out[] = '--   4. Open the site; the installer will only ask for an admin account.';
$out[] = '--';
$out[] = '-- This file contains NO user accounts and NO projects.';
$out[] = '-- =====================================================================';
$out[] = '';
$out[] = 'SET NAMES utf8mb4;';
$out[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$out[] = '';

// --- Table structure ---
$out[] = '-- ---------------------------------------------------------------------';
$out[] = '-- 1. Table structure';
$out[] = '-- ---------------------------------------------------------------------';
$out[] = '';

foreach (Schema::sql('mysql') as $block) {
    foreach (explode(";\n", $block . "\n") as $statement) {
        $statement = trim(preg_replace('/^--.*$/m', '', $statement) ?? '');
        $statement = trim($statement, " \t\n\r;");
        if ($statement === '') {
            continue;
        }

        $out[] = $statement . ';';
    }
    $out[] = '';
}

// --- Data ---
$out[] = '-- ---------------------------------------------------------------------';
$out[] = '-- 2. Content library data';
$out[] = '-- ---------------------------------------------------------------------';
$out[] = '';

$totalRows = 0;

foreach (DATA_TABLES as $table) {
    $rows = Database::all('SELECT * FROM `' . $table . '` ORDER BY id ASC');
    if (!$rows) {
        continue;
    }

    $out[] = '-- ' . $table . ' (' . count($rows) . ' rows)';
    $out[] = 'DELETE FROM `' . $table . '`;';

    $columns = array_keys($rows[0]);
    $colList = '`' . implode('`, `', $columns) . '`';

    // Batch 50 rows at a time so the statements stay readable
    foreach (array_chunk($rows, 50) as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $cells = [];
            foreach ($columns as $col) {
                $cells[] = quoteValue($pdo, $row[$col]);
            }
            $values[] = '(' . implode(', ', $cells) . ')';
        }
        $out[] = 'INSERT INTO `' . $table . '` (' . $colList . ') VALUES';
        $out[] = implode(",\n", $values) . ';';
    }

    $out[] = '';
    $totalRows += count($rows);
}

$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$out[] = '';
$out[] = '-- Done: ' . $totalRows . ' library rows.';
$out[] = '';

$file = __DIR__ . '/install.sql';
file_put_contents($file, implode("\n", $out));

printf("Wrote %s (%s KB, %d data rows)\n", $file, number_format(filesize($file) / 1024, 1), $totalRows);

/** Turns a PHP value into something safe to embed in a SQL statement */
function quoteValue(PDO $pdo, $value): string
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
    // SQLite returns integers as strings; keep them quoted, it is safe either way
    return $pdo->quote((string) $value);
}
