<?php
/**
 * Brings an already-installed database up to date.
 *
 * Run this after uploading a new version. It only ever ADDS missing tables and
 * columns - it never drops or rewrites anything, so your projects and accounts
 * are untouched and it is safe to run twice.
 *
 * TWO WAYS TO RUN IT
 *
 *   In a browser:  https://yourdomain.com/install/upgrade.php
 *                  You have to be signed in as an administrator.
 *
 *   On the command line (if your host gives you Terminal):
 *                  php install/upgrade.php
 *
 * Delete the install folder again once you are done.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use Install\Schema;

$isCli = PHP_SAPI === 'cli';

// In a browser this must not be open to the world - it touches the schema
if (!$isCli) {
    if (!Auth::check() || !Auth::isAdmin()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Not allowed</title>'
           . '<div style="font:15px/1.6 system-ui;max-width:520px;margin:60px auto;padding:0 20px">'
           . '<h1 style="font-size:20px">Administrators only</h1>'
           . '<p>Sign in with your administrator account first, then open this page again.</p>'
           . '<p><a href="' . htmlspecialchars(App\Core\Url::to('/login'), ENT_QUOTES) . '">Go to sign in</a></p>'
           . '</div>';
        exit;
    }
}

$log = [];

function step(string $message, bool $ok = true): void
{
    global $log;
    $log[] = ['message' => $message, 'ok' => $ok];
}

try {
    $pdo    = Database::connect();
    $driver = Database::driver();

    // ---------------------------------------------------------------
    //  1. Create any table that does not exist yet
    // ---------------------------------------------------------------
    foreach (Schema::tables() as $table => $columns) {
        if (Database::tableExists($table)) {
            continue;
        }

        foreach (Schema::sql($driver) as $block) {
            if (!str_contains($block, '`' . $table . '`')) {
                continue;
            }
            foreach (explode(";\n", $block . "\n") as $statement) {
                $statement = trim(preg_replace('/^--.*$/m', '', $statement) ?? '');
                $statement = trim($statement, " \t\n\r;");
                if ($statement === '') {
                    continue;
                }
                $pdo->exec($statement);
            }
        }

        step('Created the missing table: ' . $table);
    }

    // ---------------------------------------------------------------
    //  2. Add any missing column to tables that already exist
    // ---------------------------------------------------------------
    foreach (Schema::tables() as $table => $columns) {
        if (!Database::tableExists($table)) {
            continue;
        }

        $existing = existingColumns($pdo, $driver, $table);

        foreach ($columns as $name => $definition) {
            // Skip the '#unique' / '#index' entries
            if (str_starts_with($name, '#')) {
                continue;
            }
            if (in_array(strtolower($name), $existing, true)) {
                continue;
            }

            $sql = 'ALTER TABLE `' . $table . '` ADD COLUMN ' . columnSql($name, $definition, $driver);

            try {
                $pdo->exec($sql);
                step('Added the column ' . $table . '.' . $name);
            } catch (PDOException $e) {
                // Already there under a different case, or added by another run
                if (!str_contains(strtolower($e->getMessage()), 'duplicate')) {
                    throw $e;
                }
            }
        }
    }

    // ---------------------------------------------------------------
    //  3. Data the new columns cannot express on their own
    // ---------------------------------------------------------------

    // background_mode arrived defaulting to 'theme', which tells the app to use
    // the theme's own scene and ignore any upload. Projects that already have an
    // uploaded background were plainly made the custom way, so say so - otherwise
    // the upgrade would quietly hide artwork somebody made.
    if (Database::tableExists('projects')
        && in_array('background_mode', existingColumns($pdo, $driver, 'projects'), true)) {

        $stale = Database::count(
            "SELECT COUNT(*) FROM projects WHERE background_id IS NOT NULL AND background_mode = 'theme'"
        );

        if ($stale > 0) {
            Database::run(
                "UPDATE projects SET background_mode = 'custom' WHERE background_id IS NOT NULL AND background_mode = 'theme'"
            );
            step('Kept the uploaded background on ' . $stale . ' existing project' . ($stale === 1 ? '' : 's'));
        }
    }

    // Library names that described artwork nobody had. The move card designs were
    // named off a wheel of twelve themes, which repeated once it went round twice
    // and matched nothing on the card; the first three character sets were named
    // before their artwork existed. An installed site cannot re-run the seeder, so
    // the names are corrected here.
    $renames = [
        'move' => [
            'move-01' => 'Peach',    'move-02' => 'Cocoa',  'move-03' => 'Lime',
            'move-04' => 'Sky',      'move-05' => 'Vanilla', 'move-06' => 'Butter',
            'move-07' => 'Ice',      'move-08' => 'Lavender', 'move-09' => 'Rose',
            'move-10' => 'Honey',    'move-11' => 'Snow',   'move-12' => 'Sage',
            'move-13' => 'Mist',     'move-14' => 'Lilac',  'move-15' => 'Meadow',
            'move-16' => 'Card style 16', 'move-17' => 'Card style 17',
            'move-18' => 'Card style 18', 'move-19' => 'Card style 19',
            'move-20' => 'Card style 20',
        ],
        'character' => [
            'char-01' => 'Junior Hero - Bunny',
            'char-02' => 'Junior Hero - Kitten',
            'char-03' => 'Junior Hero - Puppy',
        ],
    ];

    if (Database::tableExists('library_items')) {
        $renamed = 0;

        foreach ($renames as $kind => $rows) {
            foreach ($rows as $code => $name) {
                $current = Database::first(
                    'SELECT name FROM library_items WHERE kind = ? AND code = ? LIMIT 1',
                    [$kind, $code]
                );

                if ($current && $current['name'] !== $name) {
                    Database::run(
                        'UPDATE library_items SET name = ? WHERE kind = ? AND code = ?',
                        [$name, $kind, $code]
                    );
                    $renamed++;
                }
            }
        }

        if ($renamed > 0) {
            step('Renamed ' . $renamed . ' library item' . ($renamed === 1 ? '' : 's') . ' to match their artwork');
        }
    }

    /*
     * Move card styles 16 to 20 were seeded as numbered placeholders and no
     * artwork was ever drawn for them, so the picker offered five blank styles
     * called "Card style 16" and up. Fifteen is the real set.
     *
     * A project that picked one keeps it - clearing somebody's choice to tidy
     * the library would be a worse trade than leaving one stale row behind.
     */
    if (Database::tableExists('library_items') && Database::tableExists('projects')) {
        $spare = Database::all(
            "SELECT id, code FROM library_items WHERE kind = 'move' AND code > 'move-15'"
        );

        $removed = 0;
        $kept    = 0;

        foreach ($spare as $row) {
            $inUse = Database::count(
                'SELECT COUNT(*) FROM projects WHERE move_item_id = ?',
                [(int) $row['id']]
            );

            if ($inUse > 0) {
                $kept++;
                continue;
            }

            Database::delete('library_items', ['id' => (int) $row['id']]);
            $removed++;
        }

        if ($removed > 0) {
            step('Removed ' . $removed . ' move card style' . ($removed === 1 ? '' : 's') . ' that had no artwork');
        }
        if ($kept > 0) {
            step('Kept ' . $kept . ' placeholder move style' . ($kept === 1 ? '' : 's') . ' still chosen by a project');
        }
    }

    if (!$log) {
        step('Everything is already up to date. Nothing needed changing.');
    }

    $failed = false;

} catch (Throwable $e) {
    step('Upgrade stopped: ' . $e->getMessage(), false);
    $failed = true;
    error_log('[GameCraft upgrade] ' . $e->getMessage());
}

// ---------------------------------------------------------------
//  Helpers
// ---------------------------------------------------------------

/** Column names already on a table, lower-cased */
function existingColumns(PDO $pdo, string $driver, string $table): array
{
    $names = [];

    if ($driver === 'sqlite') {
        foreach ($pdo->query('PRAGMA table_info(`' . $table . '`)') as $row) {
            $names[] = strtolower((string) $row['name']);
        }
    } else {
        foreach ($pdo->query('SHOW COLUMNS FROM `' . $table . '`') as $row) {
            $names[] = strtolower((string) $row['Field']);
        }
    }

    return $names;
}

/**
 * Renders one column definition, reusing the same grammar Schema.php uses.
 * Schema::column() is private, so this mirrors the handful of cases we need.
 */
function columnSql(string $name, string $definition, string $driver): string
{
    $parts = explode(' ', trim($definition));
    $type  = array_shift($parts);

    $sqlType = 'VARCHAR(190)';

    if (str_starts_with($type, 'varchar:')) {
        $len     = (int) substr($type, 8);
        $sqlType = $driver === 'mysql' ? 'VARCHAR(' . $len . ')' : 'TEXT';
    } elseif ($type === 'int' || str_starts_with($type, 'fk:')) {
        $sqlType = $driver === 'mysql' ? 'INT' : 'INTEGER';
    } elseif ($type === 'tinyint') {
        $sqlType = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';
    } elseif ($type === 'text') {
        $sqlType = 'TEXT';
    } elseif ($type === 'datetime') {
        $sqlType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
    }

    $sql = '`' . $name . '` ' . $sqlType;

    /*
     * Adding a NOT NULL column to a table that already holds rows fails unless
     * there is a default. Anything new therefore goes in as nullable, which is
     * exactly what an optional field like google_id wants anyway.
     */
    $default = null;
    foreach ($parts as $p) {
        if (str_starts_with($p, 'default:')) {
            $default = substr($p, 8);
        }
    }

    if ($default !== null) {
        $sql .= ' NULL DEFAULT ' . (is_numeric($default) ? $default : "'" . $default . "'");
    } else {
        $sql .= ' NULL';
    }

    return $sql;
}

// ---------------------------------------------------------------
//  Output
// ---------------------------------------------------------------

if ($isCli) {
    foreach ($log as $line) {
        echo ($line['ok'] ? '  ok    ' : '  FAIL  ') . $line['message'] . "\n";
    }
    exit($failed ? 1 : 0);
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Database upgrade</title>
<link rel="stylesheet" href="<?= App\Core\Url::asset('css/app.css') ?>">
</head>
<body>
<div style="max-width:620px;margin:0 auto;padding:44px 20px">

    <h1 style="font-size:22px;margin-bottom:6px">Database upgrade</h1>
    <p class="muted" style="margin-bottom:22px">
        Adds anything the newer version needs. Existing data is never touched.
    </p>

    <div class="card">
        <div class="card__body" style="padding-top:8px">
            <table class="data" style="margin:-8px 0">
                <?php foreach ($log as $line): ?>
                    <tr>
                        <td style="width:26px;padding-left:0">
                            <?php if ($line['ok']): ?>
                                <span style="color:var(--green)"><?= App\Core\Icon::get('check-circle', 17) ?></span>
                            <?php else: ?>
                                <span style="color:var(--red)"><?= App\Core\Icon::get('x', 17) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px"><?= htmlspecialchars($line['message'], ENT_QUOTES) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <?php if (!$failed): ?>
        <div class="notice notice--success mt-3">
            <?= App\Core\Icon::get('check-circle', 17) ?>
            <span>Done. You can delete the <code>install</code> folder again now.</span>
        </div>
    <?php else: ?>
        <div class="notice notice--error mt-3">
            <?= App\Core\Icon::get('alert', 17) ?>
            <span>Something went wrong. Check <code>storage/logs/error.log</code> for the details.</span>
        </div>
    <?php endif; ?>

    <a class="btn btn--primary mt-2" href="<?= App\Core\Url::to('/') ?>">Back to the dashboard</a>

</div>
</body>
</html>
