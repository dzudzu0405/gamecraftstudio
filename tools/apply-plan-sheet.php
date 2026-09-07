<?php
/**
 * Puts the library in line with the plans as Tiers.php declares them.
 *
 * WHY THIS EXISTS
 *
 * What a plan is sold is written in one place - Tiers.php - and what a plan
 * can actually pick is decided by the `tier` column on every library row. The
 * two drifted apart: Starter was sold six 18-mission maps while owning none,
 * and was sold no move cards while owning five.
 *
 * So the numbers are not repeated here. They are read from Tiers.php, and the
 * rows are handed out to match. Change a plan there and run this.
 *
 * WHICH row lands where follows the code: map-12-01 upwards, char-01 upwards.
 * Rows used to be dealt one theme at a time, for variety - but the theme column
 * is a label left over from seeding, and the boards were renamed and reshuffled
 * long after it was written, so it no longer describes the picture under it.
 * Sorting by it only scattered the plan's own frames through the grid.
 *
 * USAGE
 *   php tools/apply-plan-sheet.php            show what would change
 *   php tools/apply-plan-sheet.php --apply    write the changes
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\Library;
use App\Services\Tiers;

$apply = in_array('--apply', $argv, true);
$plans = [Tiers::STARTER, Tiers::PRO, Tiers::PUBLISHER];

/**
 * Hands rows out to the plans in code order, cheapest plan first.
 *
 * @param array $rows    library rows in code order, each with id, code, tier
 * @param array $wanted  plan => how many rows that plan should be able to pick,
 *                       counted cumulatively as the plans inherit
 * @return array         id => the tier it should carry
 */
function share(array $rows, array $wanted): array
{
    $queue = array_values($rows);

    $out = [];
    $taken = 0;

    foreach ($wanted as $plan => $total) {
        // $total is cumulative: a plan inherits everything below it
        for (; $taken < $total && $taken < count($queue); $taken++) {
            $out[(int) $queue[$taken]['id']] = $plan;
        }
    }

    // Anything past the last plan's quota belongs to the top plan
    for (; $taken < count($queue); $taken++) {
        $out[(int) $queue[$taken]['id']] = end($plans);
    }

    return $out;
}

$changes = [];
$report  = [];

// ------------------------------------------------------------------ maps
foreach ([12, 18, 24] as $cells) {
    $rows = Database::all(
        "SELECT id, code, theme, tier FROM library_items WHERE kind = 'map' AND cells = ? ORDER BY code",
        [$cells]
    );

    $wanted = [];
    foreach ($plans as $p) {
        $wanted[$p] = (int) (Tiers::get($p)['maps'][(string) $cells] ?? 0);
    }

    $report['maps ' . $cells] = $wanted;

    foreach (share($rows, $wanted) as $id => $tier) {
        $changes[$id] = $tier;
    }
}

// ------------------------------------------------------------------ move cards
$rows = Database::all("SELECT id, code, theme, tier FROM library_items WHERE kind = 'move' ORDER BY code");

$wanted = [];
foreach ($plans as $p) {
    $wanted[$p] = (int) Tiers::get($p)['move_sets'];
}
$report['move cards'] = $wanted;

foreach (share($rows, $wanted) as $id => $tier) {
    $changes[$id] = $tier;
}

// ------------------------------------------------------------------ report
echo "\n=== What each plan is sold, from Tiers.php ===\n\n";
printf("  %-14s %-11s %-11s %s\n", '', 'STARTER', 'PRO', 'PUBLISHER');
echo '  ' . str_repeat('-', 50) . "\n";
foreach ($report as $what => $w) {
    printf("  %-14s %-11d %-11d %d\n", $what, $w[Tiers::STARTER], $w[Tiers::PRO], $w[Tiers::PUBLISHER]);
}

$moving = [];
foreach (Database::all("SELECT id, code, kind, tier FROM library_items WHERE kind IN ('map','move') ORDER BY kind, code") as $r) {
    $to = $changes[(int) $r['id']] ?? null;
    if ($to !== null && $to !== $r['tier']) {
        $moving[] = [$r['code'], $r['tier'], $to];
    }
}

echo "\n=== Rows moving plan ===\n\n";
if (!$moving) {
    echo "  none - the library already matches\n";
} else {
    printf("  %-12s %-11s %s\n", 'CODE', 'WAS', 'NOW');
    echo '  ' . str_repeat('-', 40) . "\n";
    foreach ($moving as $m) {
        printf("  %-12s %-11s %s\n", $m[0], $m[1], $m[2]);
    }
    echo "\n  " . count($moving) . " rows move.\n";
}

if (!$apply) {
    echo "\nNothing written. Re-run with --apply to save these changes.\n";
    exit(0);
}

foreach ($changes as $id => $tier) {
    Database::update('library_items', ['tier' => $tier], ['id' => $id]);
}

echo "\nWrote " . count($moving) . " tier changes.\n";

// ------------------------------------------------------------------ check
echo "\n=== What each plan can now pick ===\n\n";
printf("  %-11s %-8s %-8s %-8s %s\n", 'PLAN', '12', '18', '24', 'MOVE');
echo '  ' . str_repeat('-', 46) . "\n";

foreach ($plans as $p) {
    $n = [];
    foreach ([12, 18, 24] as $c) {
        $n[] = count(Library::forPlan(Library::KIND_MAP, $p, ['cells' => $c]));
    }
    printf("  %-11s %-8d %-8d %-8d %d\n", $p, $n[0], $n[1], $n[2],
        count(Library::forPlan(Library::KIND_MOVE, $p)));
}
