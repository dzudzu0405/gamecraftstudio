<?php
/**
 * Keeps the mission-space count in the library in step with the artwork.
 *
 * WHY THIS EXISTS
 *
 * The count a board really draws used to sit in a hand-written table here,
 * because the file names could not be trusted: only 11 of the 36 drawings
 * carried the number their name claimed.
 *
 * The artwork has since been renamed so that each file says what it draws, and
 * every board was checked against that table, drawing by drawing, before the
 * table was retired. The file name is now the record, so the count is read
 * from it rather than from a list that goes stale the next time a board is
 * renamed or replaced.
 *
 * What this still catches is DRIFT: a `cells` column that no longer agrees
 * with the file it belongs to, and any file named for a size the game cannot
 * use. What it cannot catch is a drawing filed under the wrong number -
 * that means looking at the picture, which map-star-audit.html is for.
 *
 * USAGE
 *   php tools/fix-map-cells.php            show what would change
 *   php tools/fix-map-cells.php --apply    write the changes
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

/**
 * The count a board draws, taken from its own file name: map-18-04 draws 18.
 *
 * Null when the name carries no number, which is left alone rather than
 * guessed at.
 */
function drawnCount(string $code): ?int
{
    return preg_match('/^map-(\d+)-/', $code, $m) ? (int) $m[1] : null;
}

/** The only sizes the game understands */
const VALID = [12, 18, 24];

$apply = in_array('--apply', $argv ?? [], true);

$rows = Database::all("SELECT id, code, name, theme, cells, tier FROM library_items WHERE kind = 'map' ORDER BY code");

if (!$rows) {
    echo "No map frames in the library.\n";
    exit(1);
}

$changes = [];
$broken  = [];
$matrix  = [];   // theme => [size => [codes]]

foreach ($rows as $r) {
    $code = (string) $r['code'];
    $was  = (int) $r['cells'];

    $is = drawnCount($code);

    if ($is === null) {
        echo "  ? {$code} carries no size in its name, left alone
";
        continue;
    }

    /*
     * A board drawn with some other number of stars still gets the true count
     * written. That is what takes it out of circulation: every difficulty asks
     * for 12, 18 or 24, so a frame recorded as 13 matches no picker and simply
     * stops being offered - without pretending it is a size it is not.
     */
    if (!in_array($is, VALID, true)) {
        $broken[] = ['code' => $code, 'theme' => $r['theme'], 'drawn' => $is, 'was' => $was];

        if ($is !== $was) {
            $changes[] = [
                'id'   => (int) $r['id'],
                'code' => $code,
                'was'  => $was,
                'is'   => $is,
                'name' => preg_replace('/\b\d+ spaces\b/', $is . ' spaces', (string) $r['name']),
            ];
        }
        continue;
    }

    $matrix[(string) $r['theme']][$is][] = $code;

    if ($is !== $was) {
        $changes[] = [
            'id'   => (int) $r['id'],
            'code' => $code,
            'was'  => $was,
            'is'   => $is,
            // "Woodland Trail - 12 spaces" -> "... - 18 spaces"
            'name' => preg_replace('/\b\d+ spaces\b/', $is . ' spaces', (string) $r['name']),
        ];
    }
}

// ---------------------------------------------------------------
//  Report
// ---------------------------------------------------------------

echo "\n=== Frames whose space count was wrong ===\n\n";
printf("  %-12s %-8s %-8s %s\n", 'CODE', 'WAS', 'IS', 'NEW NAME');
echo '  ' . str_repeat('-', 66) . "\n";

foreach ($changes as $c) {
    printf("  %-12s %-8d %-8d %s\n", $c['code'], $c['was'], $c['is'], $c['name']);
}
echo "\n  " . count($changes) . " of " . count($rows) . " frames need correcting.\n";

if ($broken) {
    echo "\n=== Unusable - the drawing has no valid star count ===\n\n";
    foreach ($broken as $b) {
        printf("  %-12s theme %-8s drawn with %d stars (needs 12, 18 or 24)\n",
            $b['code'], $b['theme'], $b['drawn']);
    }
    echo "\n  Recorded with their real count, which keeps them out of every\n";
    echo "  picker until somebody redraws them.\n";
}

// ---------------------------------------------------------------
//  What the library looks like afterwards
// ---------------------------------------------------------------

echo "\n=== Coverage once corrected: frames per theme and size ===\n\n";
printf("  %-10s %-6s %-6s %-6s  %s\n", 'THEME', '12', '18', '24', 'GAP');
echo '  ' . str_repeat('-', 52) . "\n";

$gaps = 0;
foreach ($matrix as $theme => $sizes) {
    $n = [];
    foreach (VALID as $v) {
        $n[$v] = count($sizes[$v] ?? []);
    }
    $missing = array_keys(array_filter($n, fn($c) => $c === 0));
    if ($missing) {
        $gaps += count($missing);
    }
    printf("  %-10s %-6d %-6d %-6d  %s\n", $theme, $n[12], $n[18], $n[24],
        $missing ? 'missing ' . implode(', ', $missing) : 'ok');
}

echo "\n  {$gaps} theme/size slots have no frame at all.\n";

// ---------------------------------------------------------------
//  Write
// ---------------------------------------------------------------

if (!$apply) {
    echo "\nNothing written. Re-run with --apply to save these changes.\n";
    exit(0);
}

foreach ($changes as $c) {
    Database::update('library_items', [
        'cells' => $c['is'],
        'name'  => $c['name'],
    ], ['id' => $c['id']]);
}

echo "\nWrote " . count($changes) . " corrections.\n";

/*
 * A project already holding a frame that has just changed size now disagrees
 * with its own difficulty. Say so rather than quietly repointing it - which
 * board the buyer wanted is not something this script can know.
 */
$stranded = Database::all(
    "SELECT p.id, p.title, p.cells AS want, l.cells AS got, l.code
       FROM projects p
       JOIN library_items l ON l.id = p.map_item_id
      WHERE l.kind = 'map' AND l.cells <> p.cells"
);

if ($stranded) {
    echo "\nProjects whose chosen frame is now the wrong size:\n";
    foreach ($stranded as $s) {
        printf("  #%-4s %-32s wants %d, frame %s has %d\n",
            $s['id'], $s['title'], $s['want'], $s['code'], $s['got']);
    }
    echo "\nOpen each at step 2 and choose a frame again.\n";
}
