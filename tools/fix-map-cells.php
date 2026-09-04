<?php
/**
 * Corrects the mission-space count on the supplied map frames.
 *
 * WHY THIS EXISTS
 *
 * The 36 board illustrations were filed by their file name - map-12-* was
 * assumed to carry 12 mission stars, map-18-* eighteen, and so on. Counting
 * the stars on each one showed that only 11 of the 36 actually matched. A
 * buyer choosing Beginner could therefore pick a frame and be handed a board
 * with 24 stars on it.
 *
 * The drawings themselves are fine, so nothing is renamed and no artwork is
 * touched: the file name stays as the item's id, and only the `cells` column
 * and the display name are corrected to say what the picture really shows.
 *
 * COUNTS were made by eye, one file at a time. map-24-09 is the buyer's own
 * count; the rest are mine.
 *
 * USAGE
 *   php tools/fix-map-cells.php            show what would change
 *   php tools/fix-map-cells.php --apply    write the changes
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

/** code => mission stars actually drawn */
const REAL_COUNTS = [
    // --- filed as 12 ---
    'map-12-01' => 12,  'map-12-02' => 18,  'map-12-03' => 24,
    'map-12-04' => 18,  'map-12-05' => 18,  'map-12-06' => 24,
    'map-12-07' => 18,  'map-12-08' => 24,  'map-12-09' => 12,
    'map-12-10' => 18,  'map-12-11' => 12,  'map-12-12' => 24,

    // --- filed as 18 ---
    'map-18-01' => 18,  'map-18-02' => 12,  'map-18-03' => 24,
    'map-18-04' => 24,  'map-18-05' => 12,  'map-18-06' => 18,
    'map-18-07' => 24,  'map-18-08' => 18,  'map-18-09' => 13,
    'map-18-10' => 24,  'map-18-11' => 18,  'map-18-12' => 12,

    // --- filed as 24 ---
    'map-24-01' => 24,  'map-24-02' => 18,  'map-24-03' => 12,
    'map-24-04' => 12,  'map-24-05' => 18,  'map-24-06' => 24,
    'map-24-07' => 12,  'map-24-08' => 24,  'map-24-09' => 18,
    'map-24-10' => 12,  'map-24-11' => 18,  'map-24-12' => 24,
];

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

    if (!isset(REAL_COUNTS[$code])) {
        echo "  ? no count recorded for {$code}, left alone\n";
        continue;
    }

    $is = REAL_COUNTS[$code];

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
