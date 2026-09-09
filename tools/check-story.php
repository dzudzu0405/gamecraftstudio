<?php
/**
 * Reads every story the app can tell, and complains about the ones a buyer
 * would complain about.
 *
 *     php tools/check-story.php
 *
 * Twelve themes in four languages is forty-eight stories, and each is
 * assembled from a dozen separate pieces of text. The faults that get through
 * are never spelling - they are the seams: a blank nobody filled, an English
 * sentence still sitting in a French game, a hero name starting a sentence in
 * lower case, a story grown too long for the page it prints on.
 *
 * Exits non-zero if anything is wrong, so it can be run before a release.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\PromptGenerator;

const THEMES = ['forest', 'dino', 'space', 'ocean', 'pirate', 'magic',
                'castle', 'desert', 'arctic', 'candy', 'robot', 'farm'];

/** Beats in an assembled story - see PromptGenerator::storySeed() */
const PARAGRAPHS = 7;

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

$problems = [];

function fault(string $lang, string $theme, string $what): void
{
    global $problems;
    $problems[] = sprintf('%s / %-6s  %s', $lang, $theme, $what);
}

/** A story as the app would print it */
function story(string $theme, string $lang): string
{
    return PromptGenerator::storySeed([
        'theme'     => $theme,
        'language'  => $lang,
        'title'     => 'The Quest',
        'cells'     => 18,
        'hero_name' => 'Maya',
        'setting'   => 'Jungle Adventure',
    ])['story'];
}

// The English versions, to catch one showing through in another language
$english = [];
foreach (THEMES as $theme) {
    $english[$theme] = story($theme, 'en');
}

echo 'Reading ' . (count(THEMES) * 4) . " stories\n\n";

foreach (['en', 'es', 'fr', 'de'] as $lang) {
    $lengths = [];

    foreach (THEMES as $theme) {
        $text  = story($theme, $lang);
        $paras = preg_split('/\n\n/', $text) ?: [];

        if (preg_match('/\{[a-z_]+\}/i', $text, $m)) {
            fault($lang, $theme, 'a blank was left unfilled: ' . $m[0]);
        }

        if (count($paras) !== PARAGRAPHS) {
            fault($lang, $theme, sprintf('%d paragraphs, expected %d', count($paras), PARAGRAPHS));
        }

        foreach ($paras as $i => $para) {
            $n = $i + 1;

            if (trim($para) === '') {
                fault($lang, $theme, "paragraph $n is empty");
                continue;
            }

            if (str_contains($para, '  ')) {
                fault($lang, $theme, "double space in paragraph $n");
            }

            /*
             * A space before punctuation - except in French, where a space
             * before ! ? : and » is correct and printed French expects it.
             */
            $tight = $lang === 'fr' ? '/\s+[.,]/u' : '/\s+[.,!?]/u';
            if (preg_match($tight, $para)) {
                fault($lang, $theme, "a space before punctuation in paragraph $n");
            }

            /*
             * A sentence starting in lower case.
             *
             * This is what a placeholder does when it lands at the start of a
             * sentence: the buyer left the hero name blank, the fallback is
             * "our young hero", and the page reads "our young hero takes
             * bread". French leaves a space before its colon, so a lower-case
             * word after one of those is correct and skipped.
             */
            foreach (preg_split('/(?<=[.!?])\s+/u', trim($para)) ?: [] as $sentence) {
                $first = mb_substr(ltrim($sentence), 0, 1);
                if ($first !== '' && mb_strtolower($first) === $first && preg_match('/\p{L}/u', $first)) {
                    fault($lang, $theme, 'a sentence starts in lower case: "'
                        . mb_substr($sentence, 0, 45) . '"');
                }
            }
        }

        // an English sentence still sitting in a translated story
        if ($lang !== 'en') {
            foreach (preg_split('/(?<=[.!?])\s+/u', $english[$theme]) ?: [] as $sentence) {
                $sentence = trim($sentence);
                if (mb_strlen($sentence) > 40 && str_contains($text, $sentence)) {
                    fault($lang, $theme, 'English shows through: "' . mb_substr($sentence, 0, 50) . '..."');
                    break;
                }
            }
        }

        $words = str_word_count($text, 0, 'áéíóúñüàâçèéêëîïôùûœäöüßÁÉÍÓÚÑÀÂÇÈÉÊËÎÏÔÙÛÄÖÜ');
        $lengths[] = $words;

        if ($words < MIN_WORDS || $words > MAX_WORDS) {
            fault($lang, $theme, sprintf('%d words - outside %d to %d', $words, MIN_WORDS, MAX_WORDS));
        }
    }

    printf("  %s  %d stories, %d to %d words (average %d)\n",
        strtoupper($lang), count(THEMES), min($lengths), max($lengths),
        (int) round(array_sum($lengths) / count($lengths)));
}

if (!$problems) {
    echo "\nNo problems found.\n";
    exit(0);
}

echo "\n" . count($problems) . " problems:\n\n";
foreach ($problems as $line) {
    echo '  ' . $line . "\n";
}
echo "\n";
exit(1);
