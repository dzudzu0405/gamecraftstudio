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
 * THE SPLIT follows what each plan can actually build. A plan buys a
 * difficulty, and a difficulty is a board size: Starter makes Beginner games
 * on 12-space boards, Pro adds Standard and its 18, Publisher adds Advanced
 * and its 24. So an 18-space frame is only ever seen by Pro and Publisher,
 * and a 24 only by Publisher - tagging either of them "starter" would be a
 * label nobody could reach. Each size is therefore divided only among the
 * plans that unlock it, with the cheapest of those taking half.
 *
 * WHICH frame lands where is chosen for variety, not by number. The cheapest
 * plan is filled one theme at a time, so it sees ten different places rather
 * than five drawings of the same forest. Only once every theme has
 * contributed a frame does a second frame from the same theme get used.
 *
 * USAGE
 *   php tools/rebalance-map-tiers.php            show what would change
 *   php tools/rebalance-map-tiers.php --apply    write the changes
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\Art;
use App\Services\Difficulty;
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

    /*
     * A frame is only worth tagging for a plan that can build that size of
     * game at all. Starter makes Beginner games, so it never asks for an
     * 18-space frame; tagging one "starter" would be a label nobody could
     * reach. The tiers a size is split across are therefore the plans that
     * unlock its difficulty - and the smallest of those gets half, as before.
     */
    $tiersHere = [];
    foreach ([Tiers::STARTER, Tiers::PRO, Tiers::PUBLISHER] as $t) {
        if (Tiers::allowsDifficulty($t, Difficulty::fromCells($cells))) {
            $tiersHere[] = $t;
        }
    }
    if (!$tiersHere) {
        $tiersHere = [Tiers::PUBLISHER];
    }

    $quota = [Tiers::STARTER => 0, Tiers::PRO => 0, Tiers::PUBLISHER => 0];

    if (count($tiersHere) === 1) {
        $quota[$tiersHere[0]] = $n;
    } elseif (count($tiersHere) === 2) {
        $quota[$tiersHere[0]] = (int) ceil($n / 2);
        $quota[$tiersHere[1]] = $n - $quota[$tiersHere[0]];
    } else {
        $quota[$tiersHere[0]] = (int) ceil($n / 2);
        $rest = $n - $quota[$tiersHere[0]];
        $quota[$tiersHere[1]] = (int) ceil($rest / 2);
        $quota[$tiersHere[2]] = $rest - $quota[$tiersHere[1]];
    }

    $starter   = $quota[Tiers::STARTER];
    $pro       = $quota[Tiers::PRO];
    $publisher = $quota[Tiers::PUBLISHER];

    $plan[$cells] = [
        'starter' => $starter, 'pro' => $pro, 'publisher' => $publisher,
        'total'   => $n,
        'reach'   => implode('/', $tiersHere),
    ];

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
printf("  %-7s %-8s %-9s %-6s %-11s %s\n", 'SIZE', 'FRAMES', 'STARTER', 'PRO', 'PUBLISHER', 'PLANS THAT REACH IT');
echo '  ' . str_repeat('-', 72) . "\n";
foreach ($plan as $cells => $p) {
    printf("  %-7d %-8d %-9d %-6d %-11d %s\n",
        $cells, $p['total'], $p['starter'], $p['pro'], $p['publisher'], $p['reach']);
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
