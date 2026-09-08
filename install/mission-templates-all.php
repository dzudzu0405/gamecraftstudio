<?php
/**
 * Every question template, in every language the app prints.
 *
 * WHY THE FILES ARE SPLIT THE WAY THEY ARE
 *
 * mission-templates.php holds the English templates AND the shape of each one:
 * which subject and level it belongs to, its sticker, and the ranges its
 * numbers are drawn from. None of that changes with language, so it is written
 * once. A number between 2 and 12 is a number between 2 and 12 in Madrid.
 *
 * mission-templates-<locale>.php holds only what does change: the sentence, the
 * answer, the hint, and the word lists that get dropped into the blanks. Each
 * entry is keyed by the English template's code.
 *
 *     'math-add' => [
 *         'name'    => 'Sumar',
 *         'pattern' => 'Hay {a} {creature} en {place}. Llegan {b} mas...',
 *         'answer'  => '{a+b} {creature}',
 *         'hint'    => 'Cuentalos todos juntos.',
 *         'lists'   => ['creature' => [...], 'place' => [...]],
 *     ]
 *
 * 'lists' is merged into the English variable spec, so the number ranges stay
 * where they were. A template that needs different blanks altogether - the
 * rhyming and spelling ones, which cannot be translated at all and are written
 * fresh for each language - can give a whole 'variables' spec instead, and that
 * replaces the English one.
 *
 * CODES
 *
 * Every row keeps a unique code, so a translated row is the English code with
 * its language on the end: math-add, math-add.es, math-add.fr, math-add.de.
 * The locale column is what the app actually reads; the suffix is what keeps
 * the existing unique index happy on a site that is already installed.
 *
 * A language that is missing a template simply does not get that row, and
 * MissionMatcher falls back to English for that game.
 */

$english = require __DIR__ . '/mission-templates.php';

$rows = [];

// English first, exactly as it was
foreach ($english as $t) {
    $rows[] = $t + ['locale' => 'en'];
}

foreach (['es', 'fr', 'de'] as $locale) {
    $file = __DIR__ . '/mission-templates-' . $locale . '.php';

    if (!is_file($file)) {
        continue;
    }

    $translated = require $file;

    foreach ($english as $t) {
        $code = $t['code'];

        if (!isset($translated[$code])) {
            continue;   // not written yet - that game falls back to English
        }

        $tr = $translated[$code];

        // The blanks: a whole new spec, or the English one with new word lists
        $variables = $tr['variables'] ?? $t['variables'];
        if (!isset($tr['variables']) && isset($tr['lists'])) {
            foreach ($tr['lists'] as $name => $list) {
                $variables[$name] = ['list' => $list];
            }
        }

        $rows[] = [
            'code'      => $code . '.' . $locale,
            'locale'    => $locale,
            'name'      => $tr['name'] ?? $t['name'],
            'subject'   => $t['subject'],
            'level'     => $t['level'],
            'sticker'   => $t['sticker'],
            'pattern'   => $tr['pattern'],
            'answer'    => $tr['answer'],
            'variables' => $variables,
            'hint'      => $tr['hint'] ?? null,
        ];
    }
}

return $rows;
