<?php
/**
 * Checks every question the library can print, in every language.
 *
 *   php tools/check-questions.php
 *
 * Run it after changing anything in install/mission-templates*.php, and after
 * install/upgrade.php on a live site. It prints "No problems found." or a list
 * of what is wrong and where. It only reads - nothing is written.
 *
 *   STRUCTURE - each template on its own: does every blank in every wording
 *   have a value to draw from, does the answer resolve, is anything empty.
 *
 *   OUTPUT - the templates rendered many times over, looking for the faults
 *   that only appear once a word has been dropped in: a leftover {blank},
 *   "1 rabbits", a lower-case sentence start, a double space, no full stop.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\MissionMatcher;

$RENDERS = 40;
$problems = [];

function note(array &$problems, string $locale, string $code, string $kind, string $detail): void
{
    $problems[] = compact('locale', 'code', 'kind', 'detail');
}

/** {Thing} and {thing} are the same variable - one is capitalised on the card */
function auditBaseName(string $name): string
{
    return mb_strtolower(mb_substr($name, 0, 1)) . mb_substr($name, 1);
}

$rows = Database::all('SELECT * FROM mission_templates WHERE is_active = 1 ORDER BY locale, code');
printf("Checking %d template rows\n\n", count($rows));

// ---------------------------------------------------------------------------
//  Structure
// ---------------------------------------------------------------------------

foreach ($rows as $row) {
    $locale = (string) $row['locale'];
    $code   = (string) $row['code'];
    $vars   = json_decode((string) $row['variables'], true) ?: [];
    $known  = array_keys($vars);

    $wordings = MissionMatcher::phrasings($row);

    if (count($wordings) < 2) {
        note($problems, $locale, $code, 'one wording only', 'a deck will repeat its shape');
    }

    foreach (array_merge($wordings, [(string) $row['answer']]) as $text) {
        preg_match_all('/\{([^}]+)\}/u', $text, $m);

        foreach ($m[1] as $name) {
            foreach (preg_split('/[^A-Za-z_]+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                if (!in_array(auditBaseName($part), $known, true)) {
                    note($problems, $locale, $code, 'unknown blank',
                        '{' . $name . '} in "' . mb_substr($text, 0, 60) . '"');
                }
            }
        }
    }

    foreach ($vars as $name => $rule) {
        if (isset($rule['list'])) {
            if (count($rule['list']) !== count(array_unique($rule['list']))) {
                note($problems, $locale, $code, 'duplicate word',
                    'the list for {' . $name . '} repeats itself');
            }
            foreach ($rule['list'] as $word) {
                if (trim((string) $word) === '') {
                    note($problems, $locale, $code, 'empty word', 'in the list for {' . $name . '}');
                }
            }
        }
    }

    if (trim((string) $row['answer']) === '') {
        note($problems, $locale, $code, 'no answer', 'the answer key would be blank');
    }
}

// ---------------------------------------------------------------------------
//  Output
// ---------------------------------------------------------------------------

foreach ($rows as $row) {
    $locale = (string) $row['locale'];
    $code   = (string) $row['code'];
    $count  = count(MissionMatcher::phrasings($row));

    for ($w = 0; $w < $count; $w++) {
        for ($r = 0; $r < $RENDERS; $r++) {
            $card = MissionMatcher::renderTemplate($row, $w);
            $q = $card['question'];
            $a = $card['answer'];

            if (preg_match('/\{[^}]*\}/u', $q . ' ' . $a, $m)) {
                note($problems, $locale, $code, 'blank left in',
                    $m[0] . ' in "' . mb_substr($q, 0, 70) . '"');
                break 2;
            }
            if (trim($q) === '') {
                note($problems, $locale, $code, 'empty question', 'wording ' . ($w + 1));
                break 2;
            }
            if (str_contains($q, '  ')) {
                note($problems, $locale, $code, 'double space', mb_substr($q, 0, 70));
                break 2;
            }
            // " ..." is an ellipsis, not a stray space before a full stop
            if (preg_match('/\s+[.,](?!\.)/u', $q)) {
                note($problems, $locale, $code, 'space before a full stop or comma', mb_substr($q, 0, 70));
                break 2;
            }
            // may end with punctuation, or with a closing quotation mark
            if (!preg_match('/[.?!\x{00bb}\x{201c}\x{201d}"]$/u', trim($q))) {
                note($problems, $locale, $code, 'no end punctuation', mb_substr($q, 0, 70));
                break 2;
            }

            // a sentence may open with a quotation mark; the quoted word keeps its case
            $opensWithQuote = (bool) preg_match('/^\s*[\x{00bf}\x{00a1}]?\s*[\x{201e}\x{201c}\x{00ab}"]/u', $q);
            $first = mb_substr(ltrim($q, "\u{00bf}\u{00a1} "), 0, 1);

            if (!$opensWithQuote && $first !== ''
                && mb_strtolower($first) === $first && !preg_match('/[0-9]/u', $first)) {
                note($problems, $locale, $code, 'starts in lower case', mb_substr($q, 0, 70));
                break 2;
            }

            // a count of one followed by a plural
            if (preg_match('/\b1 (\p{L}+(?:s|en|os|as|es))\b/u', $q, $m)
                && !preg_match('/\b1 (Sekunden|segundos|secondes|seconds)\b/u', $q)) {
                note($problems, $locale, $code, 'one with a plural', mb_substr($q, 0, 70));
                break 2;
            }
        }
    }
}

// ---------------------------------------------------------------------------
//  Report
// ---------------------------------------------------------------------------

if (!$problems) {
    echo "No problems found.\n";
    exit(0);
}

$byKind = [];
foreach ($problems as $p) {
    $byKind[$p['kind']][] = $p;
}

printf("%d problems, in %d kinds:\n\n", count($problems), count($byKind));

foreach ($byKind as $kind => $list) {
    printf("  %-34s %d\n", $kind, count($list));
    foreach ($list as $p) {
        printf("      %-4s %-22s %s\n", $p['locale'], $p['code'], $p['detail']);
    }
    if (count($list) > 8) {
        printf("      ... and %d more\n", count($list) - 8);
    }
    echo "\n";
}

exit(1);
