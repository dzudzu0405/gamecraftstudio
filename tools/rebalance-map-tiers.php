<?php
/**
 * Spreads the map frames evenly across the three plans again.
 *
 * WHY THIS EXISTS
 *
 * Tiers used to be handed out by position inside a group: the first six
 * map-12-* frames were Starter, the next three Pro, the last three Publisher.
 * Correcting the star counts moved frames between groups, so that ordering no
 * longer means anything - Starter was left with three Beginner frames while
 * Publisher had ten.
 *
 * THE SPLIT is the one the plans were designed around: half of each size to
 * Starter, and the rest divided between Pro and Publisher.
 *
 * WHICH frame lands where is chosen for variety, not by number. Starter is
 * filled one theme at a time, so somebody on the free plan sees ten different
 * places rather than five drawings of the same forest. Only once every theme
 * has contributed a frame does a second frame from the same theme get used.
 *
 * USAGE
 *   php tools/rebalance-map-tiers.php            show what would change
 *   php tools/rebalance-map-tiers.php --apply    write the changes
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\Art;
use App\Services\Tiers;

/** Sizes the game understands */
const SIZES = [12, 18, 24];

$apply = in_array('--apply', $argv ?? [], true);

$rows = Database::all(
    "SELECT id, code, name, theme, cells, tier FROM library_items WHERE kind = 'map' ORDER BY code"
);

// ---------------------------------------------------------------
//  Group by size, keeping themes in their usual order
// ---------------------------------------------------------------

$themeOrder = array_keys(Art::THEMES);
$bySize     = [];
$skipped    = [];

foreach ($rows as $r) {
    $cells = (int) $r['cells'];

    if (!in_array($cells, SIZES, true)) {
        $skipped[] = $r;
        continue;
    }

    $bySize[$cells][] = $r;
}

/*
 * Order inside a size: first frame of every theme, then second frames, and so
 * on. Reading the list top to bottom therefore walks through all twelve themes
 * before it repeats one - which is exactly the order to fill Starter from.
 */
foreach ($bySize as $cells => &$group) {
    $seen = [];
    $ranked = [];

    foreach ($group as $item) {
        $theme = (string) $item['theme'];
        $round = $seen[$theme] = ($seen[$theme] ?? 0) + 1;
        $pos   = array_search($theme, $themeOrder, true);

        $ranked[] = [$round, $pos === false ? 99 : $pos, $item];
    }

    usort($ranked, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
    $group = array_column($ranked, 2);
}
unset($group);

ksort($bySize);

// ---------------------------------------------------------------
//  Work out the new tier for each frame
// ---------------------------------------------------------------

$changes = [];
$plan    = [];

foreach ($bySize as $cells => $group) {
    $n = count($group);

    // Half to Starter; Pro takes the odd one when the rest cannot halve evenly
    $starter   = (int) ceil($n / 2);
    $rest      = $n - $starter;
    $pro       = (int) ceil($rest / 2);
    $publisher = $rest - $pro;

    $plan[$cells] = ['starter' => $starter, 'pro' => $pro, 'publisher' => $publisher, 'total' => $n];

    foreach ($group as $i => $item) {
        $tier = $i < $starter
            ? Tiers::STARTER
            : ($i < $starter + $pro ? Tiers::PRO : Tiers::PUBLISHER);

        if ($tier !== $item['tier']) {
            $changes[] = [
                'id'    => (int) $item['id'],
                'code'  => (string) $item['code'],
                'theme' => (string) $item['theme'],
                'cells' => $cells,
                'was'   => (string) $item['tier'],
                'is'    => $tier,
            ];
        }
    }
}

// ---------------------------------------------------------------
//  Report
// ---------------------------------------------------------------

echo "\n=== The split ===\n\n";
printf("  %-8s %-8s %-10s %-12s %s\n", 'SIZE', 'FRAMES', 'STARTER', 'PRO', 'PUBLISHER');
echo '  ' . str_repeat('-', 52) . "\n";
foreach ($plan as $cells => $p) {
    printf("  %-8d %-8d %-10d %-12d %d\n", $cells, $p['total'], $p['starter'], $p['pro'], $p['publisher']);
}

echo "\n=== Frames moving plan ===\n\n";
if (!$changes) {
    echo "  Nothing to change.\n";
} else {
    printf("  %-12s %-9s %-7s %-12s %s\n", 'CODE', 'THEME', 'SIZE', 'WAS', 'NOW');
    echo '  ' . str_repeat('-', 56) . "\n";
    foreach ($changes as $c) {
        printf("  %-12s %-9s %-7d %-12s %s\n", $c['code'], $c['theme'], $c['cells'], $c['was'], $c['is']);
    }
    echo "\n  " . count($changes) . " frames move.\n";
}

if ($skipped) {
    echo "\n=== Left out - not a usable size ===\n\n";
    foreach ($skipped as $s) {
        printf("  %-12s theme %-9s cells=%s  (needs redrawing)\n", $s['code'], $s['theme'], $s['cells']);
    }
}

// ---------------------------------------------------------------
//  What each plan ends up seeing. forPlan is cumulative: Pro sees
//  its own frames plus Starter's, Publisher sees everything.
// ---------------------------------------------------------------

echo "\n=== Frames each plan can pick, per size ===\n\n";
printf("  %-10s %-10s %-10s %s\n", 'SIZE', 'STARTER', 'PRO', 'PUBLISHER');
echo '  ' . str_repeat('-', 44) . "\n";
foreach ($plan as $cells => $p) {
    printf("  %-10d %-10d %-10d %d\n",
        $cells,
        $p['starter'],
        $p['starter'] + $p['pro'],
        $p['total']);
}

// ---------------------------------------------------------------
//  Write
// ---------------------------------------------------------------

if (!$apply) {
    echo "\nNothing written. Re-run with --apply to save these changes.\n";
    exit(0);
}

foreach ($changes as $c) {
    Database::update('library_items', ['tier' => $c['is']], ['id' => $c['id']]);
}

echo "\nWrote " . count($changes) . " tier changes.\n";

/*
 * Moving a frame up a plan can strand a project that already picked it. The
 * choice is left alone - taking somebody's artwork away to tidy the library
 * would be the worse trade - but it is worth naming.
 */
$stranded = Database::all(
    "SELECT p.id, p.title, u.plan, l.code, l.tier
       FROM projects p
       JOIN users u ON u.id = p.user_id
       JOIN library_items l ON l.id = p.map_item_id
      WHERE l.kind = 'map'"
);

$lost = [];
foreach ($stranded as $s) {
    if (!Tiers::atLeast((string) $s['plan'], (string) $s['tier'])) {
        $lost[] = $s;
    }
}

if ($lost) {
    echo "\nProjects holding a frame above their plan (kept, not cleared):\n";
    foreach ($lost as $s) {
        printf("  #%-4s %-32s plan %-10s frame %s is %s\n",
            $s['id'], $s['title'], $s['plan'], $s['code'], $s['tier']);
    }
}
