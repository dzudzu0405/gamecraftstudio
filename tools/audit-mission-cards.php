<?php
/**
 * Renders every mission design against questions of every length, so the words
 * can be checked against the panel each frame drew.
 *
 * Usage: php tools/audit-mission-cards.php [one|all]
 *   one  - one card per design, a middling question (the readable view)
 *   all  - every design against four lengths (the clipping check)
 */
require __DIR__ . '/../app/bootstrap.php';

use App\Services\Library;
use App\Services\PrintBundle;

$mode = $argv[1] ?? 'one';

$QUESTIONS = [
    'short'  => 'What is 7 + 5?',
    'medium' => 'Cora has 7 conkers and finds exactly the same number again. How many now?',
    'real'   => 'Ben counted 18 ducks on the pond and 9 more landed while he watched. '
              . 'How many ducks are on the pond now?',
    'long'   => 'Maya collected 15 crayons and gave 7 of them away to her friends at school. '
              . 'Later that afternoon her brother gave her 4 more. How many crayons now?',
];
if ($mode === 'one') {
    $QUESTIONS = ['medium' => $QUESTIONS['medium']];
}

$pose = '/uploads/library/characters/char-01-1.png';
$css = [];
$cards = [];

for ($style = 1; $style <= PrintBundle::CARD_STYLES; $style++) {
    $rel = Library::framePath('missions', $style);
    $css[] = ".s{$style} { background-image: url('/uploads/{$rel}'); }";

    $band    = PrintBundle::textBand($style, 'mission');
    $caption = PrintBundle::captionAt($style, 'mission', PrintBundle::safeZone($style, 'mission')[1]);
    $win     = PrintBundle::heroWindow($style);

    $vars = "--mission-top: {$band[0]}%; --mission-bottom: {$band[1]}%; --mission-caption: {$caption}%;";
    if ($win) {
        $vars .= " --hero-top: {$win['top']}%; --hero-height: {$win['height']}%;"
               . " --hero-left: {$win['left']}%; --hero-right: {$win['right']}%;";
    }

    $mods = $win ? ' card-cut--tight' : '';
    $mods .= PrintBundle::reversed($style) ? ' card-cut--reverse' : '';
    $mods .= PrintBundle::reversedCaption($style) ? ' card-cut--reverse-foot' : '';

    foreach ($QUESTIONS as $label => $q) {
        $inner = $win
            ? '<div class="card-cut__window pose"></div><div class="card-cut__inner">'
            : '<div class="card-cut__inner"><div class="card-cut__hero pose"></div>';

        // The real deck takes one size from its longest question; here each
        // sample question stands in for a deck whose longest is that long
        $cls = PrintBundle::deckQuestionClass($style, [['question' => $q]]);

        $cards[] = '<figure data-style="' . $style . '" data-len="' . mb_strlen($q) . '">'
                 . '<div class="card-cut card-cut--framed card-cut--art s' . $style . $mods . '"'
                 . ' style="' . $vars . '">'
                 . $inner
                 . '<div class="card-cut__q ' . $cls . '">' . htmlspecialchars($q) . '</div>'
                 . '<div class="card-cut__a">Answer: 14</div>'
                 . (PrintBundle::captionFits($style, 'mission')
                        ? '<div class="card-cut__game">STAR RESCUE MISSION</div>' : '')
                 . '</div>'
                 . '</div>'
                 . '<figcaption>' . $style . ' &middot; ' . $label . ' &middot; band '
                 . $band[0] . '-' . $band[1] . ' &middot; ' . ($cls ?: 'base') . '</figcaption>'
                 . '</figure>';
    }
}

$cols = $mode === 'one' ? 4 : count($QUESTIONS);

$html = '<!doctype html><meta charset="utf-8"><title>Mission frames</title>'
      . '<link rel="stylesheet" href="/assets/css/print.css">'
      . '<style>'
      . 'body{background:#fff;padding:10px;font-family:system-ui}'
      . '.grid{display:grid;grid-template-columns:repeat(' . $cols . ',60mm);gap:5mm 6mm}'
      . 'figure{margin:0}'
      . 'figcaption{font-size:9px;color:#666;margin-top:2px;text-align:center}'
      . '.card-cut{width:60mm;height:80mm}'
      . '.pose{background-image:url(\'' . $pose . '\')}'
      . implode('', $css)
      . '</style>'
      . '<div class="grid">' . implode('', $cards) . '</div>';

file_put_contents(__DIR__ . '/../assets/_missions.html', $html);
echo "wrote assets/_missions.html (" . count($cards) . " cards)\n";
