<?php
/**
 * Reads every story the app can tell, and complains about the ones a buyer
 * would complain about.
 *
 *     php tools/check-story.php
 *
 * Twelve themes in four languages, and every beat now has several ways of
 * being told - so there are far more stories than anyone can read. The faults
 * that get through are never spelling. They are the seams: a blank nobody
 * filled, an English sentence still sitting in a French game, a placeholder
 * opening a sentence so the page reads "our young hero takes bread", a story
 * grown too long for the page it prints on.
 *
 * Two passes. The first reads every wording on its own, so nothing is checked
 * by luck of the draw. The second assembles whole stories, which is the only
 * way to catch a fault that lives in the joins between them.
 *
 * Exits non-zero if anything is wrong, so it can be run before a release.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Lang;
use App\Services\PromptGenerator;

const THEMES = ['forest', 'dino', 'space', 'ocean', 'pirate', 'magic',
                'castle', 'desert', 'arctic', 'candy', 'robot', 'farm'];

const LANGUAGES = ['en', 'es', 'fr', 'de'];

/** Beats in an assembled story - see PromptGenerator::storySeed() */
const PARAGRAPHS = 7;

/** The beats that are the same whatever the theme, and what may be filled into each */
const BEATS = [
    'place'  => ['place'],
    'p2'     => ['hero', 'rescue'],
    'setout' => ['hero', 'rescue'],
    'p3'     => ['cells', 'trouble'],
    'wrong'  => [],
    'last'   => ['hero', 'rescue'],
    'p4'     => ['hero', 'rescue', 'title'],
];

/** The beats that change with the theme */
const THEME_BEATS = ['opening', 'trouble', 'companion', 'final', 'rescue'];

/*
 * Blanks holding a name or a fragment rather than a whole sentence, so one of
 * these at the head of a sentence prints in lower case. {trouble} is the
 * exception: a complete sentence goes in there, capital and all.
 */
const NAMES = ['hero', 'rescue', 'place', 'title', 'cells'];

/*
 * What fits the printed page.
 *
 * The story sheet is one A4 page with a picture across the top, which leaves
 * room for roughly four hundred words at that type size. The floor is there
 * because a story that comes out short usually means a piece went missing
 * rather than that somebody wrote briefly.
 */
const MIN_WORDS = 250;
const MAX_WORDS = 420;

/** How many draws of each story to assemble - enough to shuffle the joins about */
const DRAWS = 8;

$problems = [];

function fault(string $where, string $what): void
{
    global $problems;
    $problems[] = sprintf('%-22s %s', $where, $what);
}

/** Every way one key can be told, in one language */
function wordings(string $key, string $lang): array
{
    $value = Lang::raw($key, $lang);

    if (is_string($value)) {
        return $value === '' ? [] : [$value];
    }

    return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
}

/** A story as the app would print it */
function story(string $theme, string $lang, ?int $seed = null): string
{
    return PromptGenerator::storySeed([
        'theme'     => $theme,
        'language'  => $lang,
        'title'     => 'The Quest',
        'cells'     => 18,
        'hero_name' => 'Maya',
        'setting'   => 'Jungle Adventure',
    ], [], $seed)['story'];
}

// ---------------------------------------------------------------
//  Pass one: every wording, on its own
// ---------------------------------------------------------------

$wordingCount = 0;

foreach (LANGUAGES as $lang) {
    $keys = array_keys(BEATS);
    foreach (THEME_BEATS as $group) {
        foreach (THEMES as $theme) {
            $keys[] = $group . '.' . $theme;
        }
    }

    foreach ($keys as $key) {
        $allowed = BEATS[$key] ?? [];      // theme beats take no blanks at all

        foreach (wordings('story.' . $key, $lang) as $i => $wording) {
            $wordingCount++;
            $where = sprintf('%s story.%s[%d]', $lang, $key, $i);

            if (str_contains($wording, '  ')) {
                fault($where, 'double space');
            }

            // French puts a space before ! ? : and », and is right to
            $tight = $lang === 'fr' ? '/\s+[.,]/u' : '/\s+[.,!?]/u';
            if (preg_match($tight, $wording)) {
                fault($where, 'a space before punctuation');
            }

            // the rescue is a name for somebody, not a sentence about them
            $isRescue = str_starts_with($key, 'rescue.');

            if (!$isRescue && !preg_match('/[.!?»"\x{201d}]$/u', trim($wording))) {
                fault($where, 'does not end on punctuation');
            }

            /*
             * A blank opening a sentence.
             *
             * The buyer may leave the hero unnamed, and the stand-in reads
             * "our young hero" - lower case, because it is a description
             * rather than a name. At the head of a sentence it prints
             * "our young hero takes bread".
             */
            foreach (preg_split('/(?<=[.!?])\s+/u', trim($wording)) ?: [] as $sentence) {
                if (preg_match('/^\{(' . implode('|', NAMES) . ')\}/', ltrim($sentence))) {
                    fault($where, 'a blank starts a sentence: "' . mb_substr($sentence, 0, 40) . '"');
                }
            }

            // a blank nobody fills, or one filled that was never asked for
            preg_match_all('/\{([a-z_]+)\}/i', $wording, $found);
            foreach (array_unique($found[1]) as $blank) {
                if (!in_array($blank, $allowed, true)) {
                    fault($where, 'nothing fills {' . $blank . '}');
                }
            }
        }
    }
}

printf("Read %d wordings across %d languages\n", $wordingCount, count(LANGUAGES));

// ---------------------------------------------------------------
//  Pass two: whole stories
// ---------------------------------------------------------------

// the English ones, to catch one showing through in another language
$english = [];
foreach (THEMES as $theme) {
    for ($draw = 0; $draw < DRAWS; $draw++) {
        $english[$theme][] = story($theme, 'en', $draw);
    }
}

printf("Assembled %d stories\n\n", count(THEMES) * count(LANGUAGES) * DRAWS);

foreach (LANGUAGES as $lang) {
    $lengths = [];

    foreach (THEMES as $theme) {
        for ($draw = 0; $draw < DRAWS; $draw++) {
            $text  = story($theme, $lang, $draw);
            $paras = preg_split('/\n\n/', $text) ?: [];
            $where = sprintf('%s / %-6s #%d', $lang, $theme, $draw);

            if (preg_match('/\{[a-z_]+\}/i', $text, $m)) {
                fault($where, 'a blank was left unfilled: ' . $m[0]);
            }

            if (count($paras) !== PARAGRAPHS) {
                fault($where, sprintf('%d paragraphs, expected %d', count($paras), PARAGRAPHS));
            }

            foreach ($paras as $i => $para) {
                if (trim($para) === '') {
                    fault($where, 'paragraph ' . ($i + 1) . ' is empty');
                }
                if (str_contains($para, '  ')) {
                    fault($where, 'double space in paragraph ' . ($i + 1));
                }
            }

            if ($lang !== 'en') {
                foreach ($english[$theme] as $englishStory) {
                    foreach (preg_split('/(?<=[.!?])\s+/u', $englishStory) ?: [] as $sentence) {
                        $sentence = trim($sentence);
                        if (mb_strlen($sentence) > 40 && str_contains($text, $sentence)) {
                            fault($where, 'English shows through: "' . mb_substr($sentence, 0, 45) . '..."');
                            break 2;
                        }
                    }
                }
            }

            $lengths[] = str_word_count($text, 0, 'áéíóúñüàâçèéêëîïôùûœäöüßÁÉÍÓÚÑÀÂÇÈÉÊËÎÏÔÙÛÄÖÜ');
        }
    }

    $last = end($lengths);
    if (min($lengths) < MIN_WORDS || max($lengths) > MAX_WORDS) {
        fault(strtoupper($lang), sprintf('stories run %d to %d words - outside %d to %d',
            min($lengths), max($lengths), MIN_WORDS, MAX_WORDS));
    }

    printf("  %s  %d to %d words (average %d)\n", strtoupper($lang),
        min($lengths), max($lengths), (int) round(array_sum($lengths) / count($lengths)));
}

if (!$problems) {
    echo "\nNo problems found.\n";
    exit(0);
}

echo "\n" . count($problems) . " problems:\n\n";
foreach (array_unique($problems) as $line) {
    echo '  ' . $line . "\n";
}
echo "\n";
exit(1);
