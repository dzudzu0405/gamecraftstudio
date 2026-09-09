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
    ],

    'answers' => [
        'warn'  => '<b>For whoever is running the game.</b> Take these last sheets off the back '
                 . 'of the stack and keep them. The mission cards themselves do not show the answers.',
        'in_order' => 'In the order the cards are printed',
    ],
    // The story page. {placeholders} are filled in from the project; the three
    // phrases that change with the theme stay in PromptGenerator, which is also
    // where the picture prompts read them from.
    // Each beat holds several ways of telling it. One is drawn per game, and
    // a wording the buyer's other games already print is passed over, so a
    // second game does not read like the first one with the names changed.
    'story' => [
        'hero_default' => 'our young hero',

        'place' => [
            'All of it happens in {place}, a place that until this morning had never given '
                . 'anyone a reason to worry.',
            'The whole of it takes place in {place}, which had been getting along perfectly '
                . 'well until today.',
            'This all happens in {place} - somewhere that had never once needed rescuing before.',
        ],

        'p2' => [
            'Word travels quickly, and it reaches {hero} before anyone else. Somewhere out '
                . 'there is {rescue}, waiting, with no idea whether help is coming. Nobody older '
                . 'is willing to go. So {hero} packs a bag, says nothing to anybody, and leaves '
                . 'while the light is still good.',
            'The news gets about, the way news does, and {hero} hears it first. Out there '
                . 'somewhere is {rescue}, waiting, and not at all sure that anyone is coming. The '
                . 'grown-ups talk it over and decide it is much too far. So {hero} fills a bag, '
                . 'tells nobody, and slips away while there is still light to walk by.',
            'By the middle of the morning everyone has heard, and {hero} has heard it twice. '
                . 'Somewhere beyond the last houses is {rescue}, counting on somebody who has not '
                . 'set off yet. No grown-up volunteers, so {hero} packs a bag instead, goes '
                . 'without a word, and takes the road while the day still holds.',
        ],

        'setout' => [
            'Nobody sets out on a journey like this with empty pockets, so {hero} takes bread, '
                . 'a blanket, a length of string that will turn out to be the most useful thing '
                . 'of the lot, and rather less courage than the occasion calls for.',
            'You cannot walk a road like this one with nothing in your hands, so {hero} brings '
                . 'bread, a blanket, a candle and a piece of string whose usefulness nobody could '
                . 'possibly have guessed at the time.',
            'The bag holds whatever {hero} could find in a hurry: bread, a blanket, half a map, '
                . 'and one small stone kept for luck since a summer nobody else remembers.',
        ],

        'p3' => [
            'The road ahead breaks into {cells} stages, and not one of them lets you pass '
                . 'for free. {trouble} At every stage there is a question waiting, and answering it '
                . 'well is the only way through.',
            'There are {cells} stages between here and there, and every one of them asks for '
                . 'something. {trouble} A question waits at each of them, and a good answer is the '
                . 'only fare the road will take.',
            'The way divides into {cells} stages, and none of them is generous. {trouble} Each '
                . 'has a question standing across it, and the only key that fits is a right answer.',
        ],

        'wrong' => [
            'There will be moments when the answer simply will not come. That is allowed. '
                . 'The road takes one step back, waits while you think it through again, and then '
                . 'lets you carry on. Nobody is ever sent home for getting something wrong - the '
                . 'only way to lose a journey like this one is to stop walking it.',
            'Some of the questions will not open on the first try. That is fine, and it is '
                . 'expected. The road gives back a step, lets you turn the thing over once more, '
                . 'and then goes on as before. Nobody has ever been sent home for a wrong answer; '
                . 'the road ends only for whoever stops walking it.',
            'There will be a question that just sits there, and no amount of staring will move '
                . 'it. That happens to everybody. You lose a step, you think again, and on you go. '
                . 'Getting one wrong has never ended a journey - only putting the bag down for good.',
        ],

        'last' => [
            'And then, all at once, there is nothing left between {hero} and {rescue} but '
                . 'one last question.',
            'And then there is only one question left standing between {hero} and {rescue}.',
            'One question more, and nothing else at all, lies between {hero} and {rescue}.',
        ],

        'p4' => [
            'Reach the end and {rescue} comes home, and the story of how it happened belongs '
                . 'to {hero} from then on. That story is called "{title}".',
            'Get to the end and {rescue} comes home again, and from that day the story of how '
                . 'it happened belongs to {hero}. It goes by the name "{title}".',
            'Finish the road and {rescue} sleeps at home tonight. The telling of it belongs to '
                . '{hero} ever after, under the name "{title}".',
        ],
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
