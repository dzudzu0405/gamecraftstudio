<?php
/**
 * Loads install/mission-templates-all.php into a database that is already running.
 *
 * WHY THIS EXISTS
 *
 * The seeder only writes templates into an empty table, so an installed site
 * keeps whatever it was first given. This adds the new ones and updates the
 * wording of any that changed, matching on the template's code.
 *
 * Nothing is deleted: a code that is no longer in the file is left alone and
 * reported, in case it was added by hand.
 *
 * USAGE
 *   php tools/refresh-mission-templates.php            show what would change
 *   php tools/refresh-mission-templates.php --apply    write the changes
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\MissionMatcher;
use App\Services\Tiers;

$apply = in_array('--apply', $argv, true);
$file  = dirname(__DIR__) . '/install/mission-templates-all.php';

if (!is_file($file)) {
    echo "Cannot find install/mission-templates-all.php\n";
    exit(1);
}

$templates = require $file;

$existing = [];
foreach (Database::all('SELECT * FROM mission_templates') as $row) {
    $existing[(string) $row['code']] = $row;
}

$added = $updated = $same = [];

foreach ($templates as $t) {
    $vars = json_encode($t['variables'], JSON_UNESCAPED_UNICODE);

    $fields = [
        'name'      => $t['name'],
        'locale'    => $t['locale'] ?? 'en',
        'subject'   => $t['subject'],
        'level'     => $t['level'],
        // Templates are not sold by plan - see the note in the templates file
        'tier'      => Tiers::STARTER,
        'sticker'   => $t['sticker'],
        'pattern'   => $t['pattern'],
        'answer'    => $t['answer'],
        'variables' => $vars,
        'hint'      => $t['hint'] ?? null,
        'is_active' => 1,
    ];

    $row = $existing[$t['code']] ?? null;

    if ($row === null) {
        $added[] = $t['code'];
        if ($apply) {
            Database::insert('mission_templates',
                $fields + ['code' => $t['code'], 'created_at' => date('Y-m-d H:i:s')]);
        }
        continue;
    }

    $differs = false;
    foreach ($fields as $k => $v) {
        if ((string) ($row[$k] ?? '') !== (string) $v) {
            $differs = true;
            break;
        }
    }

    if ($differs) {
        $updated[] = $t['code'];
        if ($apply) {
            Database::update('mission_templates', $fields, ['id' => (int) $row['id']]);
        }
    } else {
        $same[] = $t['code'];
    }
}

$orphans = array_diff(array_keys($existing), array_column($templates, 'code'));

/*
 * Questions are reached by level, not by plan, so no template should carry a
 * tier above starter - including ones added by hand or left from an older
 * install. Four rows kept their pro and publisher tiers through the first
 * refresh, which left Standard and Advanced still differing by plan.
 */
$mistiered = [];
foreach ($existing as $code => $row) {
    if ((string) $row['tier'] !== Tiers::STARTER) {
        $mistiered[] = $code;
        if ($apply) {
            Database::update('mission_templates', ['tier' => Tiers::STARTER], ['id' => (int) $row['id']]);
        }
    }
}

printf("%d templates in the file\n\n", count($templates));
printf("  %-10s %d  %s\n", 'new', count($added), implode(', ', array_slice($added, 0, 6))
    . (count($added) > 6 ? ' ...' : ''));
printf("  %-10s %d  %s\n", 'changed', count($updated), implode(', ', array_slice($updated, 0, 6))
    . (count($updated) > 6 ? ' ...' : ''));
printf("  %-10s %d\n", 'unchanged', count($same));

if ($orphans) {
    printf("  %-10s %d  %s  (kept)\n", 'not in file', count($orphans), implode(', ', $orphans));
}

if ($mistiered) {
    printf("  %-10s %d  %s\n", 'un-tiered', count($mistiered), implode(', ', $mistiered));
}

if (!$apply) {
    echo "\nNothing written. Re-run with --apply to save these changes.\n";
    exit(0);
}

echo "\nWrote " . (count($added) + count($updated)) . " templates.\n";

// -------------------------------------------------------------------------
//  What a game can now draw on
// -------------------------------------------------------------------------
echo "\n=== Questions available, by plan and difficulty ===\n\n";
printf("  %-11s %-11s %-11s %s\n", 'PLAN', 'DIFFICULTY', 'TEMPLATES', 'DISTINCT QUESTIONS');
echo '  ' . str_repeat('-', 56) . "\n";

$subjects = MissionMatcher::subjectKeys();

foreach ([Tiers::STARTER, Tiers::PRO, Tiers::PUBLISHER] as $plan) {
    foreach (Tiers::difficulties($plan) as $level) {
        $t = MissionMatcher::matchTemplates($subjects, $level, $plan);
        printf("  %-11s %-11s %-11d %s\n", $plan, $level, count($t),
            number_format(MissionMatcher::estimateVariants($t)));
    }
}
