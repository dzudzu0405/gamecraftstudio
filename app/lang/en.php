<?php
/**
 * English - the language every other file is measured against.
 *
 * Only what gets PRINTED lives here: sheet headings, rules, card text, the
 * story. The Studio's own screens stay in English and are not translated.
 */
return [

    // The bar at the top of the print page - on screen, never printed
    'bar' => [
        'pages'   => ['one' => '{n} page', 'other' => '{n} pages'],
        'spaces'  => ['one' => '{n} space', 'other' => '{n} spaces'],
        'back'    => 'Back to Studio',
        'preview' => 'Preview',
        'print'   => 'Print / Save as PDF',
        'hint'    => 'In the print dialog choose <b>Destination: Save as PDF</b>, turn on '
                   . '<b>Background graphics</b> and set <b>Margins: None</b> so the colours '
                   . 'and cut lines come out correctly.',
    ],

    'sheet' => [
        'page' => 'Page {n}',

        'map'       => 'Game map',
        'map_sub'   => ['one' => '{n} mission space', 'other' => '{n} mission spaces'],

        'story'     => 'The story',

        'howto'     => 'How to play',
        'players'   => ['one' => '{n} player', 'other' => '{n} players'],
        'players_range' => '{min}-{max} players',

        'move'      => 'Move cards',
        'move_sub'  => ['one' => 'Cut along the dashed lines - {n} card',
                        'other' => 'Cut along the dashed lines - {n} cards'],

        'dice'      => 'Paper die',
        'dice_sub'  => 'Cut out, fold along the lines and glue the tabs',

        'mission'     => 'Mission cards',
        'mission_none' => 'None generated yet',
        'sheet_of'    => 'Sheet {page} of {total}',
        'cards'       => ['one' => '{n} card', 'other' => '{n} cards'],

        'hero'      => 'Winner hero card',
        'hero_sub'  => 'One per game',

        'tokens'     => 'Player tokens',
        'tokens_sub' => 'Cut out and glue onto card',

        'answers'      => 'Answer key',
        'answers_keep' => 'Keep this sheet',
    ],

    'howto' => [
        'prepare'       => 'What to prepare:',
        'prepare_move'  => ['one' => '{n} move card', 'other' => '{n} move cards'],
        'prepare_dice'  => 'the cut-out die',
        'prepare_cards' => '{total} mission cards shuffled into one pile',
        'prepare_hero'  => ['one' => '{n} hero card', 'other' => '{n} hero cards'],
        'prepare_token' => 'and one token for each player',
    ],

    'dice' => [
        'alt'     => 'Die to cut out and fold',
        'steps'   => [
            'Cut around the outside of the whole shape, tabs included.',
            'Fold along every inside line, so the six faces turn inwards.',
            'Glue the tabs under the neighbouring face and hold until dry.',
            'One die is enough for the whole table - roll and move that many spaces.',
        ],
        'missing' => 'The die artwork is missing. Put a file named <code>dice-net.png</code> into '
                   . '<code>uploads/library/</code> and print again, or play with any ordinary '
                   . 'six-sided die.',
    ],

    'move_card' => [
        'forward' => ['one' => 'Move forward {n} space', 'other' => 'Move forward {n} spaces'],
        'back'    => ['one' => 'Wrong answer: back {n} space', 'other' => 'Wrong answer: back {n} spaces'],
    ],

    'mission' => [
        'empty' => 'This project has no mission cards yet. Go back to the Studio and choose '
                 . '"Match mission cards".',
    ],

    'hero_card' => [
        'champion' => 'Champion',
        'hero_default' => 'the hero',
        'eyebrow'  => 'Hero of',
        'line'     => 'Made it through all {n} challenges and reached the finish first.',
        'congrats' => 'Congratulations, {name}!',
        'winner'   => "Winner's name",
        'date'     => 'Date',
    ],

    'level' => [
        'beginner' => 'Beginner',
        'standard' => 'Standard',
        'advanced' => 'Advanced',
    ],

    'board' => [
        'start'  => 'START',
        'finish' => 'FINISH',
    ],

    'tokens' => [
        'player' => 'Player {n}',
        'note' => 'Each player gets two tokens - one to use and one spare. Glue them onto thick '
                . 'card and cut around the circle so they stand up on the map.',
        'models' => 'Even better: use a small toy figure to mark where you are. Anything that stands on one space will do - a mini figure, a button, a bead or a coin.',
    ],

    'answers' => [
        'warn'  => '<b>For whoever is running the game.</b> Take these last sheets off the back '
                 . 'of the stack and keep them. The mission cards themselves do not show the answers.',
        'in_order' => 'In the order the cards are printed',
    ],
    // The rules page. Numbering is added when they are assembled.
    'rules' => [
        'start'        => 'Each player picks a token and places it on the START space.',
        'move_cards'   => 'On your turn, draw a move card and go forward the number of spaces on it. '
                        . 'Keep the card in front of you.',
        'move_dice'    => 'On your turn, roll the die and go forward that many spaces.',
        'star'         => 'Land on a space with a star and you take the top card from the mission '
                        . 'pile. Land anywhere else and your turn simply ends.',
        'answer'       => 'Answer the question. Get it right and you stay where you are.',
        'wrong_cards'  => 'Get it wrong and you go back by the penalty printed on the move card you drew.',
        'wrong_dice'   => 'Get it wrong and you go back one space.',
        'return_cards' => 'Put the mission card at the bottom of the mission pile, and the move card '
                        . 'at the bottom of its deck.',
        'return_dice'  => 'Put the mission card at the bottom of the mission pile.',
        'win'          => 'The first player to reach the FINISH space wins the hero card.',
    ],
];
