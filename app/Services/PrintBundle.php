<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Project;
use App\Services\Lang;
use App\Services\Tiers;

/**
 * Assembles the print bundle in the EXACT ORDER required by FR-27
 * (SRS section 8):
 *
 *      1. Game map
 *      2. Story
 *      3. How to play
 *      4. Move cards
 *      5. Mission cards
 *      6. Winner hero card
 *
 * (A seventh section - the player tokens - comes last, since it is a cut-out
 *  accessory rather than part of the required FR-27 sequence.)
 *
 * A NOTE ON THE MOVE CARD COUNT
 * ---------------------------------------------------------------------------
 * SRS section 8 says "10 move cards", while section 10 and FR-33 specify a
 * fixed 8 move cards per game. The two contradict each other. This build
 * follows FR-33 (8 cards) because that is the numbered, binding functional
 * requirement. If 10 is confirmed instead, change the single constant
 * Difficulty::MOVE_CARDS_PER_GAME.
 */
class PrintBundle
{
    /** Cards printed on each A4 sheet (a 3 x 3 grid) */
    public const CARDS_PER_SHEET = 9;

    /**
     * Builds everything needed to print a project.
     * Returns a list of sections; each one is one or more printed pages.
     */
    public static function build(array $project, ?string $plan = null): array
    {
        $projectId = (int) $project['id'];
        $cells     = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $theme     = Project::artTheme($project);

        $sections = [];

        // --- 1. Game map ---
        $sections[] = [
            'key'         => 'map',
            'order'       => 1,
            'title'       => 'Game map',
            'orientation' => 'landscape',
            'pages'       => 1,
            'data'        => [
                'cells'      => $cells,
                'background' => self::backgroundUrl($project),
            ],
        ];

        /*
         * --- 2. Story ---
         *
         * The buyer's own, written with the prompt at step 3. A game without
         * one simply has no story page: the app used to invent one, and an
         * invented story printed in a game somebody is selling is worse than
         * a game that goes straight from the map to the rules.
         *
         * Long stories run to a second sheet. An AI asked for four hundred
         * words sometimes sends nine hundred, and a page that quietly spilled
         * over the edge of the paper would take the ending with it.
         */
        $story      = trim((string) ($project['story'] ?? ''));
        $storyPages = self::storyPages($story);

        if ($storyPages) {
            $sections[] = [
                'key'         => 'story',
                'order'       => 2,
                'title'       => 'The story',
                'orientation' => 'portrait',
                'pages'       => count($storyPages),
                'data'        => ['pages' => $storyPages],
            ];
        }

        // --- 3. How to play ---
        $howTo = trim((string) ($project['how_to_play'] ?? ''));
        if ($howTo === '') {
            $howTo = PromptGenerator::rules($project);
        }
        $sections[] = [
            'key'         => 'howto',
            'order'       => 3,
            'title'       => 'How to play',
            'orientation' => 'portrait',
            'pages'       => 1,
            'data'        => ['text' => $howTo],
        ];

        /*
         * --- 4. Whatever moves you ---
         *
         * The two are alternatives, so only one of them is printed. Printing
         * both would put a die and a deck in the same box and leave the buyer
         * to guess which the rules meant.
         */
        if (Project::usesMoveCards($project)) {
            $moveCards = self::moveCards($project);
            $sections[] = [
                'key'         => 'move',
                'order'       => 4,
                'title'       => 'Move cards',
                'orientation' => 'portrait',
                'pages'       => (int) ceil(count($moveCards) / self::CARDS_PER_SHEET),
                'data'        => ['cards' => $moveCards, 'theme' => $theme],
            ];
        } else {
            $sections[] = [
                'key'         => 'dice',
                'order'       => 4,
                'title'       => 'Paper die',
                'orientation' => 'portrait',
                'pages'       => 1,
                'data'        => ['image' => self::diceNetUrl()],
            ];
        }

        // --- 5. Mission cards ---
        $missions = MissionMatcher::forProject($projectId);
        $sections[] = [
            'key'         => 'mission',
            'order'       => 5,
            'title'       => 'Mission cards',
            'orientation' => 'portrait',
            'pages'       => (int) ceil(max(1, count($missions)) / self::CARDS_PER_SHEET),
            'data'        => ['cards' => $missions, 'theme' => $theme],
        ];

        // --- 6. Winner hero card ---
        $sections[] = [
            'key'         => 'hero',
            'order'       => 6,
            'title'       => 'Winner hero card',
            'orientation' => 'portrait',
            'pages'       => 1,
            'data'        => [
                'hero_name' => trim((string) ($project['hero_name'] ?? ''))
                            ?: Lang::get('hero_card.hero_default', Lang::of($project)),
                'theme'     => $theme,
                'character' => self::characterUrl($project),
            ],
        ];

        // --- 7. Player tokens (accessory) ---
        $players = Database::all(
            'SELECT * FROM project_players WHERE project_id = ? ORDER BY sort_order ASC, id ASC',
            [$projectId]
        );
        if (!$players) {
            $players = self::defaultPlayers((int) ($project['players_max'] ?? 4), Lang::of($project));
        }
        $sections[] = [
            'key'         => 'tokens',
            'order'       => 7,
            'title'       => 'Player tokens',
            'orientation' => 'portrait',
            'pages'       => 1,
            'data'        => ['players' => $players],
        ];

        /*
         * --- 8. Answer key ---
         *
         * The answers used to be printed on the mission cards themselves, where
         * the child holding the card could read them. They live here instead:
         * the last sheets in the file, for whoever is running the game to keep
         * and everyone else to never see. Last on purpose - it is the part you
         * pull off the back of the stack before handing the rest out.
         */
        if ($missions) {
            $answerPages = self::answerKey($missions);
            $sections[] = [
                'key'         => 'answers',
                'order'       => 8,
                'title'       => 'Answer key',
                'orientation' => 'portrait',
                'pages'       => count($answerPages),
                'data'        => ['pages' => $answerPages],
            ];
        }

        return $sections;
    }

    /**
     * Roughly what a story sheet holds.
     *
     * Measured rather than guessed: 948px of prose column, which took about
     * 740 words of real sentences to fill. Every sheet is the same now - the
     * first one used to carry a picture and hold only 490.
     *
     * The figure is lower than what was measured, on purpose. Line counts
     * shift with the language, with how long the names are and with where
     * the paragraphs fall, and a story that runs off the bottom of the paper
     * takes its ending with it.
     */
    public const STORY_WORDS_PER_SHEET = 650;

    /**
     * The story, split into printable sheets.
     *
     * Split on paragraphs, never inside one: a paragraph carried across a page
     * break reads as though a line went missing. A single paragraph longer than
     * a whole sheet gets one of its own and is allowed to overrun - that is one
     * unbroken block of text, and there is no honest place to cut it.
     *
     * @return array[] one list of paragraphs per sheet
     */
    public static function storyPages(string $story): array
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($story)) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), fn($p) => $p !== ''));

        if (!$paragraphs) {
            return [];
        }

        $pages = [];
        $page  = [];
        $words = 0;

        foreach ($paragraphs as $paragraph) {
            $length = str_word_count($paragraph) ?: (int) ceil(mb_strlen($paragraph) / 6);

            if ($page && $words + $length > self::STORY_WORDS_PER_SHEET) {
                $pages[] = $page;
                $page    = [];
                $words   = 0;
            }

            $page[]  = $paragraph;
            $words  += $length;
        }

        if ($page) {
            $pages[] = $page;
        }

        return $pages;
    }

    /** Answer rows that fit on one sheet, across two columns */
    public const ANSWERS_PER_SHEET = 60;

    /**
     * Splits the answers into printable sheets.
     *
     * The cards are one shuffled pile, so there is no place on the board to
     * look a question up by. They are numbered in the order they are printed
     * instead: card 41 on the sheets is line 41 here, which is how the person
     * checking finds it without reading every line.
     */
    public static function answerKey(array $missions): array
    {
        $rows = [];

        foreach ($missions as $m) {
            $answer = trim((string) ($m['answer'] ?? ''));

            $rows[] = [
                // kept only to hold the printed order
                'cell'     => (int) ($m['cell_no'] ?? 0),
                'slot'     => (int) ($m['slot_no'] ?? 0),
                'question' => (string) ($m['question'] ?? ''),
                // A card with no answer recorded still gets a line, so the
                // gap is visible rather than silently skipped
                'answer'   => $answer !== '' ? $answer : '-',
            ];
        }

        usort($rows, fn($a, $b) => [$a['cell'], $a['slot']] <=> [$b['cell'], $b['slot']]);

        foreach ($rows as $i => $row) {
            $rows[$i]['n'] = $i + 1;
        }

        return array_chunk($rows, self::ANSWERS_PER_SHEET);
    }

    /**
     * The numbered rules, ready to print.
     *
     * The stored text is one rule per line, sometimes numbered by whoever
     * edited it - the sheet numbers them itself, so any leading number goes.
     *
     * A line naming what to prepare is dropped: the callout underneath says
     * the same thing, in the game's language and from the real card counts.
     * Older projects saved before this was worked out still carry that line
     * in English, which is what NEEDS_MARK is for.
     */
    public const NEEDS_MARK = 'You will need';

    public static function ruleSteps(string $text): array
    {
        $steps = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim(preg_replace('/^\d+[.)]\s*/', '', trim($line)) ?? '');

            if ($line === '' || str_starts_with($line, self::NEEDS_MARK)) {
                continue;
            }

            $steps[] = $line;
        }

        return $steps;
    }

    /**
     * How many players, in the game's own language.
     *
     * Helper::playerRange says it in English for the Studio; a printed sheet
     * needs the buyer's language, and German says "Spieler" whether there are
     * two or six.
     */
    public static function playerRange(array $project): string
    {
        $lang = Lang::of($project);
        $min  = (int) ($project['players_min'] ?? 2);
        $max  = (int) ($project['players_max'] ?? 4);

        if ($min === $max) {
            return Lang::choose('sheet.players', $min, $lang);
        }

        return Lang::get('sheet.players_range', $lang, ['min' => $min, 'max' => $max]);
    }

    /**
     * The list under "What to prepare", assembled in the right language.
     *
     * Built here rather than in the view because the pieces differ by how the
     * game moves, and word order differs by language.
     */
    public static function prepareLine(array $project): string
    {
        $lang  = Lang::of($project);
        $cells = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));

        /*
         * Counted from the cards themselves, not from the difficulty: a buyer
         * who adds questions of their own has more cards to lay out than the
         * preset says, and this line is what tells them how many.
         */
        $missions = isset($project['id'])
            ? MissionMatcher::countForProject((int) $project['id'])
            : 0;
        $missions = $missions ?: $cells * Difficulty::MISSIONS_PER_CELL;

        $parts = [
            Project::usesMoveCards($project)
                ? Lang::choose('howto.prepare_move', Difficulty::MOVE_CARDS_PER_GAME, $lang)
                : Lang::get('howto.prepare_dice', $lang),
            Lang::get('howto.prepare_cards', $lang, ['total' => $missions]),
            Lang::choose('howto.prepare_hero', Difficulty::HERO_CARDS_PER_GAME, $lang),
            Lang::get('howto.prepare_token', $lang),
        ];

        // The last item carries its own "and" in every language, and only
        // English would put a comma in front of one.
        $last = array_pop($parts);

        return implode(', ', $parts) . ' ' . $last . '.';
    }

    /** Total printed pages across the whole bundle */
    public static function totalPages(array $sections): int
    {
        return array_sum(array_map(fn($s) => (int) $s['pages'], $sections));
    }

    /**
     * Move cards, showing how many spaces to advance.
     * Fixed at 8 cards per game (FR-33), spread so the pace stays even.
     */
    /**
     * The move cards.
     *
     * A card does two jobs at once. The big number is how far you go when you
     * draw it. The small line underneath is what you lose if the question you
     * then land on is answered wrong - so the card you were pleased to draw is
     * also the card that decides the size of your mistake.
     *
     * The far you go, the further you fall: +1 and +2 cost one space, +3 and
     * +4 cost two. Each of the four is dealt three times, making twelve cards.
     */
    private const MOVE_FACES = [
        ['forward' => 1, 'back' => 1],
        ['forward' => 2, 'back' => 1],
        ['forward' => 3, 'back' => 2],
        ['forward' => 4, 'back' => 2],
    ];

    public static function moveCards(array $project): array
    {
        $copies = (int) max(1, round(Difficulty::MOVE_CARDS_PER_GAME / count(self::MOVE_FACES)));

        $cards = [];
        $no    = 1;

        for ($c = 0; $c < $copies; $c++) {
            foreach (self::MOVE_FACES as $face) {
                $cards[] = [
                    'no'      => $no++,
                    'steps'   => $face['forward'],
                    'back'    => $face['back'],
                    'sticker' => 'footprint',
                ];
            }
        }

        return array_slice($cards, 0, Difficulty::MOVE_CARDS_PER_GAME);
    }

    /** Default player list when the project has not named anyone yet */
    public static function defaultPlayers(int $count, ?string $locale = null): array
    {
        $colors = array_keys(Art::TOKEN_COLORS);
        $out = [];
        $count = max(Project::MIN_PLAYERS, min(Project::MAX_PLAYERS, $count));

        for ($i = 0; $i < $count; $i++) {
            $out[] = [
                'name'       => Lang::get('tokens.player', $locale, ['n' => $i + 1]),
                'color'      => $colors[$i % count($colors)],
                'sort_order' => $i,
            ];
        }
        return $out;
    }

    /**
     * Where text may sit on each card style, as [top inset, bottom inset] in
     * percent of the card height.
     *
     * Measured off the artwork itself: each frame keeps its decoration near the
     * edges - a title, a ring of stars, a picture window - and leaves a clear
     * band for the words. One guessed figure for the whole set put the answer
     * line on top of the stars, which is what these replace.
     *
     * Nine of them were re-measured against the panel the frame actually DRAWS
     * to be written in, rather than against where its clear background happens
     * to start. The difference matters on frames like 13 and 14, which paint a
     * coloured box low on the card: the first reading put the question a tenth
     * of the card ABOVE the box it belongs in, floating on the picture area.
     * Those rows are listed in EXACT_BANDS so nothing widens them again.
     *
     * Adjust a row here if a frame reads badly; nothing else needs touching.
     */
    public const SAFE_ZONES = [
        1 => ['mission' => [54.0, 24.0], 'move' => [22.8, 23.1]],
        2 => ['mission' => [62.0, 13.0], 'move' => [24.4, 24.4]],
        3 => ['mission' => [62.0, 15.0], 'move' => [25.9, 25.6]],
        4 => ['mission' => [66.0, 10.0], 'move' => [25.9, 25.9]],
        5 => ['mission' => [52.0, 18.0], 'move' => [23.4, 23.1]],
        6 => ['mission' => [62.5, 17.0], 'move' => [22.8, 22.8]],
        7 => ['mission' => [60.0, 16.5], 'move' => [24.4, 24.7]],
        8 => ['mission' => [67.0, 9.0], 'move' => [23.4, 23.4]],
        9 => ['mission' => [58.3, 19.2], 'move' => [26.6, 22.0]],
        10 => ['mission' => [51.9, 26.3], 'move' => [26.6, 26.6]],
        11 => ['mission' => [57.9, 19.9], 'move' => [26.6, 26.6]],
        12 => ['mission' => [55.9, 16.7], 'move' => [21.9, 22.2]],
        13 => ['mission' => [56.4, 18.0], 'move' => [21.9, 21.6]],
        14 => ['mission' => [65.5, 11.5], 'move' => [23.8, 23.8]],
        15 => ['mission' => [61.3, 12.8], 'move' => [23.4, 23.8]],
    ];

    /**
     * Where the game's name sits on a card, as a percent down its height.
     *
     * Measured off each frame the same way the text bands were: the lowest run
     * of plain background BELOW the band, which on most frames is the margin
     * between the decoration and the border. null means the frame is drawn all
     * the way down, and the caption stays at the foot of the text band.
     *
     * Capped at 94: a line centred lower than that lands on the cut edge.
     */
    public const CAPTION_AT = [
        1 => ['mission' => 93.0, 'move' => 87.1],
        2 => ['mission' => 94.0, 'move' => 86.4],
        3 => ['mission' => null, 'move' => 84.1],
        4 => ['mission' => 93.0, 'move' => 81.2],
        5 => ['mission' => 88.0, 'move' => 87.0],
        6 => ['mission' => 87.0, 'move' => 87.3],
        7 => ['mission' => 89.0, 'move' => 86.1],
        8 => ['mission' => 95.0, 'move' => 94.0],
        9 => ['mission' => 88.0, 'move' => 92.1],
        10 => ['mission' => null, 'move' => 78.6],
        11 => ['mission' => 85.0, 'move' => 81.4],
        12 => ['mission' => 90.0, 'move' => 87.7],
        13 => ['mission' => 87.0, 'move' => 87.9],
        14 => ['mission' => 92.0, 'move' => 86.8],
        15 => ['mission' => 92.0, 'move' => 86.8],
    ];

    /**
     * Bands that are measured to the millimetre and must be left alone.
     *
     * safeZone opens a narrow band out so a question is not squeezed, which is
     * right where the frame has spare background around it. On these it has
     * none: the band is exactly the panel the frame drew, and a millimetre
     * either way puts the words on top of the drawing.
     *
     * The cost is real - some of these panels are only a sixth of the card, so
     * a long question steps down to the smallest size to fit. A question inside
     * its box small beats a question outside it large.
     */
    private const EXACT_BANDS = [
        1  => ['mission' => true],  2  => ['mission' => true],
        3  => ['mission' => true],  4  => ['mission' => true],
        5  => ['mission' => true],  6  => ['mission' => true],
        7  => ['mission' => true],  8  => ['mission' => true],
        9  => ['mission' => true],  10 => ['mission' => true],
        11 => ['mission' => true],  12 => ['mission' => true],
        13 => ['mission' => true],  14 => ['mission' => true],
        15 => ['mission' => true],
    ];

    /**
     * Frames whose text panel is dark, so the words are set in white on it.
     *
     * Measured the same way as the bands: the panel's own colour, sampled a few
     * percent inside its top edge. 2, 13 and 14 paint a brown, a deep teal and
     * a violet panel, and the ink these cards use is nearly black. 14's caption
     * strip is light again below the panel, which is why the caption is listed
     * separately from the words.
     */
    public const REVERSE_BAND    = [2 => true, 13 => true, 14 => true];
    public const REVERSE_CAPTION = [2 => true];

    /**
     * Move frames dark enough that their number is set in white.
     *
     * Keyed to the MOVE card's own panel, not to the mission card it is paired
     * with. Those two do not always agree: 13 and 14 paint a dark box on the
     * mission card and leave the move card pale, so a number that copied the
     * mission's white ink would have been white on pale blue.
     *
     * Measured the same way: only design 2 is brown on both.
     */
    public const REVERSE_MOVE = [2 => true];

    /** Does this style print its question in white? */
    public static function reversed(int $style): bool
    {
        return self::REVERSE_BAND[$style] ?? false;
    }

    /** Does this style print the game's name in white? */
    public static function reversedCaption(int $style): bool
    {
        return self::REVERSE_CAPTION[$style] ?? false;
    }

    /** Does this style print its move card number in white? */
    public static function reversedMove(int $style): bool
    {
        return self::REVERSE_MOVE[$style] ?? false;
    }

    /**
     * Frames that drew their picture window somewhere other than above the
     * words, as top / height / left / right in percent of the card.
     *
     * Frame 12 is the odd one: it draws a wide panel at the top and a round
     * badge below, so the question goes in the panel and the hero goes in the
     * badge - the other way round from every other frame.
     *
     * 11 and 13 are here for a plainer reason. The window is otherwise taken
     * from the top of the card down to the text band, and both of them write
     * MISSION across the top; without a row here the character stands on the
     * word. The frames that decorate their top with a ring or a scatter of
     * stars are left to the derived window, because a character in front of
     * those reads as a character in front of a picture.
     */
    private const HERO_WINDOWS = [
        1  => ['mission' => ['top' => 17.8, 'height' => 34.2, 'left' =>  9.0, 'right' =>  9.0]],
        2  => ['mission' => ['top' => 19.3, 'height' => 40.7, 'left' =>  9.0, 'right' =>  9.0]],
        3  => ['mission' => ['top' => 28.5, 'height' => 31.5, 'left' =>  9.0, 'right' =>  9.0]],
        4  => ['mission' => ['top' => 20.0, 'height' => 44.0, 'left' =>  9.0, 'right' =>  9.0]],
        5  => ['mission' => ['top' =>  4.0, 'height' => 44.0, 'left' =>  9.0, 'right' =>  9.0]],
        6  => ['mission' => ['top' =>  6.0, 'height' => 53.0, 'left' => 11.0, 'right' => 11.0]],
        7  => ['mission' => ['top' => 11.0, 'height' => 38.0, 'left' => 16.0, 'right' => 16.0]],
        8  => ['mission' => ['top' => 24.8, 'height' => 40.2, 'left' =>  9.0, 'right' =>  9.0]],
        9  => ['mission' => ['top' => 21.5, 'height' => 31.2, 'left' => 12.0, 'right' => 12.0]],
        10 => ['mission' => ['top' => 17.0, 'height' => 34.4, 'left' => 12.0, 'right' => 12.0]],
        11 => ['mission' => ['top' => 21.0, 'height' => 36.4, 'left' => 12.0, 'right' => 12.0]],
        12 => ['mission' => ['top' =>  7.3, 'height' => 46.5, 'left' => 12.0, 'right' => 12.0]],
        13 => ['mission' => ['top' => 18.4, 'height' => 37.8, 'left' =>  9.0, 'right' =>  9.0]],
        14 => ['mission' => ['top' => 17.7, 'height' => 47.7, 'left' =>  9.0, 'right' =>  9.0]],
        15 => ['mission' => ['top' => 19.5, 'height' => 41.3, 'left' => 12.0, 'right' => 12.0]],
    ];

    /**
     * How much of the card height one caption line needs.
     *
     * 5% of 80mm is 4mm for a line set at 6.5px, which is a little over 1.7mm
     * tall. It was 6, and on the frames that draw a small panel that extra
     * percent was coming straight out of the question.
     */
    private const CAPTION_ROOM = 5.0;

    /** A band left shorter than this by the caption goes without one instead */
    private const MIN_CAPTION_BAND = 15.0;

    /**
     * Whether this frame prints the game's name at all.
     *
     * A frame with somewhere of its own to put it always does. A frame that
     * has to take the room out of its text band only does so while the band
     * can spare it.
     */
    public static function captionFits(int $style, string $kind): bool
    {
        if ((self::CAPTION_AT[$style][$kind] ?? null) !== null) {
            return true;
        }

        [$top, $bottom] = self::safeZone($style, $kind);

        return (100.0 - $top - $bottom - self::CAPTION_ROOM) >= self::MIN_CAPTION_BAND;
    }

    /**
     * The band the words get, once the caption has been given its room.
     *
     * A frame with a clear strip below its decoration puts the caption there,
     * clear of everything, and the words keep the whole band. A frame drawn all
     * the way down has nowhere else to put it, so it sits at the foot of the
     * band - and the band has to give up that much, or the question runs
     * underneath it.
     *
     * @return array{0: float, 1: float}
     */
    public static function textBand(int $style, string $kind): array
    {
        [$top, $bottom] = self::safeZone($style, $kind);

        // A frame drawn to its border has nowhere below the band for the name,
        // so the name sits at the band's foot and the question gives up the
        // room. The band cannot grow upwards to make that back: above it is
        // the frame's own decoration, which is why the band stops there.
        //
        // Unless the band cannot spare it. Frame 10 draws a panel a seventh of
        // the card tall, and handing a sixth of that to a signature line left
        // nothing for a long question. The question is the card; the name is a
        // convenience for sorting a spilled box. Below MIN_CAPTION_BAND the
        // name goes and captionFits() stops it being printed.
        if ((self::CAPTION_AT[$style][$kind] ?? null) === null
            && self::captionFits($style, $kind)) {
            $bottom += self::CAPTION_ROOM;
        }

        return [round($top, 1), round($bottom, 1)];
    }

    /** Where the caption goes on one card, in percent down the card */
    public static function captionAt(int $style, string $kind, float $bandBottom): float
    {
        $at = self::CAPTION_AT[$style][$kind] ?? null;

        // No room below the decoration - just inside the foot of the text band
        return $at ?? max(0.0, 100.0 - $bandBottom - 3.0);
    }

    /**
     * Question type sizes, largest first, as [class, normal px, tight px].
     *
     * The tight column is what card-cut--tight sets in print.css; both are kept
     * here because the fitting below has to know the size the class will
     * actually render at.
     */
    private const Q_SIZES = [
        ['card-cut__q--lg',  14.0, 11.5],
        ['',                 11.5,  9.5],
        ['card-cut__q--sm',  10.0,  8.6],
        ['card-cut__q--xs',   8.6,  7.6],
        ['card-cut__q--xxs',  7.4,  6.6],
    ];

    /** A printed card, in CSS pixels at 96dpi: 60 x 80mm */
    private const CARD_PX_H = 302.0;

    /** The column the words are set in - the card less its 14% side insets */
    private const CARD_PX_COL = 163.0;

    /**
     * Average character width as a fraction of the type size.
     *
     * Measured against the rendered cards rather than assumed: 0.5 read a line
     * as fuller than it was and stepped the type down a size it did not need
     * to lose.
     */
    private const CHAR_WIDTH = 0.5;

    /**
     * One size for the whole deck, taken from its longest question.
     *
     * Sizing each card on its own length is what a browser would do and it
     * printed badly: nine cards of one design on one sheet, "Add ten to 25" set
     * half as large again as the word problem beside it. They are shuffled into
     * a single pile and drawn one after another, so they have to look like one
     * deck. The longest question decides, and every card follows it.
     *
     * @param array $cards Mission rows, each with a 'question'
     */
    public static function deckQuestionClass(int $style, array $cards): string
    {
        $longest = '';

        foreach ($cards as $card) {
            $q = trim((string) ($card['question'] ?? ''));
            if (mb_strlen($q) > mb_strlen($longest)) {
                $longest = $q;
            }
        }

        return self::questionClass($style, $longest);
    }

    /**
     * The size class a question is set at on a given frame.
     *
     * This used to be a character count and nothing else, which was fine while
     * every frame gave the words about the same room. It does not survive the
     * measured bands: frame 10 draws a panel a seventh of the card tall and
     * frame 7 draws one a quarter of it, and the same question cannot be set
     * the same size in both. A count alone put a long question two lines past
     * the bottom of the smaller panels, where overflow:hidden ate the answer.
     *
     * So the room is worked out instead - band height, less what the picture
     * and the answer line take - and the largest size that fits in it wins.
     * The estimate is deliberately plain (half the type size per character);
     * it only has to choose between five steps, not typeset the line.
     */
    public static function questionClass(int $style, string $question): string
    {
        $len = mb_strlen(trim($question));
        if ($len === 0) {
            return '';
        }

        // A frame with a window keeps its picture outside the words' box, so
        // the words get the whole band; the rest hold the hero inside it and
        // are left with about half
        $tight = self::heroWindow($style) !== null;

        [$top, $bottom] = self::textBand($style, 'mission');
        $room = (100.0 - $top - $bottom) / 100.0 * self::CARD_PX_H;

        if ($tight) {
            $room -= 18.0;              // the answer line and its rule
        } else {
            $room = $room * 0.5 - 32.0; // the hero, then the answer line
        }

        foreach (self::Q_SIZES as [$class, $normal, $small]) {
            $size    = $tight ? $small : $normal;
            $perLine = max(8.0, self::CARD_PX_COL / ($size * self::CHAR_WIDTH));
            $lines   = max(1, (int) ceil($len / $perLine));

            if ($lines * $size * 1.3 <= $room) {
                return $class;
            }
        }

        return 'card-cut__q--xxs';
    }

    /** A band narrower than this cannot hold a question, so it is opened out */
    private const MIN_BAND = 30.0;

    /** Poses to look for, and how wide a hero is worth printing */
    private const MAX_POSES = 8;
    private const HERO_PRINT_WIDTH = 420;

    /**
     * The clear band for one card, as [top, bottom] insets in percent.
     *
     * @return array{0: float, 1: float}
     */
    public static function safeZone(int $style, string $kind): array
    {
        [$top, $bottom] = self::SAFE_ZONES[$style][$kind] ?? [22.0, 20.0];

        $band = 100 - $top - $bottom;
        if ($band < self::MIN_BAND && !(self::EXACT_BANDS[$style][$kind] ?? false)) {
            // Open it out around its own middle rather than from one side
            $grow   = (self::MIN_BAND - $band) / 2;
            $top    = max(4.0, $top - $grow);
            $bottom = max(4.0, $bottom - $grow);
        }

        return [round($top, 1), round($bottom, 1)];
    }

    /**
     * Frames with a picture window put it above the words, so the hero belongs
     * in the space over the clear band rather than inside it.
     */
    public static function heroWindow(int $style, string $kind = 'mission'): ?array
    {
        $set = self::HERO_WINDOWS[$style][$kind] ?? null;
        if ($set !== null) {
            return $set;
        }

        [$top] = self::safeZone($style, $kind);

        // Not enough room over the band for a picture worth printing
        if ($top < 30.0) {
            return null;
        }

        // The window is opened as wide as the frame's decoration allows: the
        // character is the thing a child looks at, and it was being fitted into
        // a box with clear paper on all four sides of it. It stops 3% short of
        // the text band so the drawing never sits on the words.
        return ['top' => 3.0, 'height' => round($top - 6.0, 1), 'left' => 9.0, 'right' => 9.0];
    }

    /**
     * A character set is drawn in several poses, and a deck of sixty mission
     * cards showing the same picture sixty times wastes them. This returns the
     * poses the project's owner is entitled to, ready for the cards to cycle
     * through.
     *
     * How many that is belongs to the PLAN, not to the set: every set is drawn
     * in eight poses, and Starter is sold three of them, Pro five, Publisher
     * all eight. Reading them all off disk regardless, which is what this did,
     * handed the free plan the whole set.
     *
     * Each one is shrunk on the way out. The window it prints into is around
     * 40mm wide, so 420px is past what the paper can show, while the originals
     * are 591px of PNG that base64 turns into half a megabyte apiece.
     *
     * @return string[] data URIs, in pose order
     */
    public static function heroPoses(array $project): array
    {
        $itemId = (int) ($project['character_item_id'] ?? 0);
        if ($itemId <= 0) {
            return [];
        }

        $item = Library::find($itemId);
        if (!$item) {
            return [];
        }

        $owner = Database::first('SELECT plan FROM users WHERE id = ? LIMIT 1',
            [(int) ($project['user_id'] ?? 0)]);
        $allowed = min(self::MAX_POSES, Tiers::characterPoses($owner['plan'] ?? null));

        $seen  = [];
        $poses = [];

        for ($variant = 1; $variant <= $allowed; $variant++) {
            $rel = Library::realImagePath($item, $variant);

            // A set with three poses answers every variant with the same file
            if ($rel === null || isset($seen[$rel])) {
                continue;
            }
            $seen[$rel] = true;

            $full = dirname(__DIR__, 2) . '/uploads/' . $rel;
            if (is_file($full)) {
                $poses[] = self::scaledDataUri($full, self::HERO_PRINT_WIDTH);
            }
        }

        // No artwork at all - fall back to the drawn character
        if (!$poses) {
            $poses[] = Art::dataUri(Art::character((string) $item['art_seed'], 1, 400));
        }

        return $poses;
    }

    /** The same picture, no wider than $width, as a data URI */
    private static function scaledDataUri(string $path, int $width): string
    {
        $info = @getimagesize($path);

        if ($info === false || (int) $info[0] <= $width || !function_exists('imagecreatetruecolor')) {
            return self::fileToDataUri($path);
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default        => false,
        };

        if (!$src) {
            return self::fileToDataUri($path);
        }

        $h   = max(1, (int) round($info[1] * $width / $info[0]));
        $dst = imagecreatetruecolor($width, $h);

        // Characters are cut out, so the transparency has to survive the resize
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefilledrectangle($dst, 0, 0, $width, $h, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $h, (int) $info[0], (int) $info[1]);

        ob_start();
        imagepng($dst, null, 6);
        $bytes = (string) ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return 'data:image/png;base64,' . base64_encode($bytes);
    }
    /**
     * The chosen map frame's own artwork, embedded so a print file stands alone.
     *
     * Null when the frame is still a drawn placeholder, which is the signal to
     * MapComposer to draw its own trail instead.
     */
    public static function mapFrameUrl(array $project): ?string
    {
        $itemId = (int) ($project['map_item_id'] ?? 0);
        if ($itemId <= 0) {
            return null;
        }

        $item = Library::find($itemId);
        if (!$item) {
            return null;
        }

        $rel = Library::realImagePath($item, 1);
        if ($rel === null) {
            return null;
        }

        $full = dirname(__DIR__, 2) . '/uploads/' . $rel;

        return is_file($full) ? self::fileToDataUri($full) : null;
    }
    /**
     * The card frames this project prints on, as data URIs ready for CSS.
     *
     * Embedded once in a stylesheet rather than per card - ninety mission cards
     * each carrying their own copy of the same 50 KB picture would make the
     * print file unopenable.
     *
     * @return array{mission: ?string, move: ?string, style: int}
     */
    public static function cardFrames(array $project): array
    {
        $style = self::cardStyle($project);
        $out   = [
            'mission' => null,
            'move'    => null,
            'style'   => $style,
            'hero'    => null,
            'window'  => self::heroWindow($style),
            'zone'    => [
                'mission' => self::textBand($style, 'mission'),
                'move'    => self::textBand($style, 'move'),
            ],
            'caption' => [
                'mission' => self::captionAt($style, 'mission', self::safeZone($style, 'mission')[1]),
                'move'    => self::captionAt($style, 'move', self::safeZone($style, 'move')[1]),
            ],
        ];

        // Every mission card shows the hero. Frames that drew a window get a big
        // one inside it; the rest get a small one at the top of their text band.
        // The cards cycle through the poses rather than repeating one picture.
        $out['heroes'] = self::heroPoses($project);
        $out['hero']   = $out['heroes'][0] ?? null;

        foreach (['mission' => 'missions', 'move' => 'moves'] as $key => $folder) {
            $rel = Library::framePath($folder, $style);
            if ($rel === null) {
                continue;
            }
            $full = dirname(__DIR__, 2) . '/uploads/' . $rel;
            if (is_file($full)) {
                $out[$key] = self::fileToDataUri($full);
            }
        }

        return $out;
    }

    /** How many designs the artwork folder holds */
    public const CARD_STYLES = 15;

    /**
     * Which card design the project prints.
     *
     * A game with move cards takes it from the move card chosen at step 2,
     * because the mission frame is the matching half of the same set - one
     * choice, two cards that belong together.
     *
     * A game that plays with the die has no move card to pair with, and used
     * to get design 1 whatever it was about. There is nothing to pair with,
     * so it chooses the mission design on its own.
     */
    public static function cardStyle(array $project): int
    {
        $style = (int) ($project['mission_style'] ?? 0);

        if ($style >= 1 && $style <= self::CARD_STYLES) {
            return $style;
        }

        /*
         * A game made before the two pickers became one stored its design as
         * the move card it chose, so the number is read back off that row.
         */
        $itemId = (int) ($project['move_item_id'] ?? 0);

        if ($itemId > 0) {
            $item = Library::find($itemId);
            if ($item && preg_match('/(\d+)$/', (string) $item['code'], $m)) {
                return (int) $m[1];
            }
        }

        return 1;   // nothing chosen yet - the first design
    }

    /**
     * Map background: the uploaded image when the buyer made their own,
     * otherwise the scene that belongs to the chosen theme (FR-31).
     */
    /**
     * The cut-out die.
     *
     * One drawing serves every game, so it is not a library item and has no
     * per-project variant - it just sits in uploads/library/. Embedded like
     * the other artwork so the print file stands alone.
     */
    public static function diceNetUrl(): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = dirname(__DIR__, 2) . '/uploads/library/dice-net.' . $ext;
            if (is_file($path)) {
                return self::fileToDataUri($path);
            }
        }

        return null;
    }

    public static function backgroundUrl(array $project): ?string
    {
        $bgId = Project::usesThemeBackground($project)
            ? 0                                       // a themed game ignores any leftover upload
            : (int) ($project['background_id'] ?? 0);

        if ($bgId > 0) {
            $asset = Database::first('SELECT * FROM user_assets WHERE id = ? LIMIT 1', [$bgId]);
            if ($asset && !empty($asset['path'])) {
                $full = dirname(__DIR__, 2) . '/uploads/' . ltrim((string) $asset['path'], '/');
                if (is_file($full)) {
                    // Embed it so the print file does not depend on any path
                    return self::fileToDataUri($full);
                }
            }
        }

        // Nothing uploaded yet - use the generated scene
        $theme = Project::artTheme($project);
        $seed  = (string) ($project['cover_seed'] ?? ($project['slug'] ?? 'map'));
        return Art::dataUri(Art::scene($theme, $seed, MapComposer::WIDTH, MapComposer::HEIGHT));
    }

    /** Character artwork for the hero card */
    public static function characterUrl(array $project): string
    {
        $itemId = (int) ($project['character_item_id'] ?? 0);
        if ($itemId > 0) {
            $item = Library::find($itemId);
            if ($item) {
                // the same pose the picker showed, so the card is no surprise
                $real = Library::realImagePath($item, Library::coverPose($item));
                if ($real !== null) {
                    $full = dirname(__DIR__, 2) . '/uploads/' . $real;
                    if (is_file($full)) {
                        return self::fileToDataUri($full);
                    }
                }
                return Art::dataUri(Art::character((string) $item['art_seed'], 1, 400));
            }
        }

        $seed = (string) ($project['cover_seed'] ?? ($project['slug'] ?? 'hero'));
        return Art::dataUri(Art::character($seed, 1, 400));
    }

    /** Reads an image file into a data URI for embedding in the print page */
    private static function fileToDataUri(string $path): string
    {
        $mime = 'image/jpeg';
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png')  { $mime = 'image/png'; }
        if ($ext === 'webp') { $mime = 'image/webp'; }
        if ($ext === 'svg')  { $mime = 'image/svg+xml'; }

        // Very large files stay as ordinary URLs so the page does not bloat
        if (filesize($path) > 4 * 1024 * 1024) {
            $rel = str_replace(dirname(__DIR__, 2) . '/uploads/', '', str_replace(DIRECTORY_SEPARATOR, '/', $path));
            return \App\Core\Url::upload($rel);
        }

        $data = @file_get_contents($path);
        if ($data === false) {
            return '';
        }
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Pre-flight check before exporting.
     * Returns a list of things still missing; an empty array means ready to go.
     */
    public static function readiness(array $project): array
    {
        $issues = [];
        $projectId = (int) $project['id'];

        if (trim((string) ($project['title'] ?? '')) === '') {
            $issues[] = 'The game has no title yet.';
        }

        if (empty($project['map_item_id'])) {
            $issues[] = 'No map frame has been chosen.';
        }

        $expected = Difficulty::missionCount((string) ($project['difficulty'] ?? 'standard'));
        $have     = MissionMatcher::countForProject($projectId);
        if ($have < $expected) {
            $issues[] = 'Only ' . $have . ' of ' . $expected . ' mission cards have been generated.';
        }

        if (empty($project['background_id'])) {
            $issues[] = 'No background image uploaded yet (using a placeholder scene).';
        }

        // Not a fault - a game can be sold without one - but worth saying once,
        // because nothing else on the page shows that the sheet is missing
        if (trim((string) ($project['story'] ?? '')) === '') {
            $issues[] = 'No story written yet, so the game will print without a story page.';
        }

        return $issues;
    }

    /** Records an export in the history (the My Exports module) */
    public static function recordExport(int $userId, int $projectId, string $format, int $pageCount, ?string $note = null): int
    {
        return Database::insert('exports', [
            'user_id'    => $userId,
            'project_id' => $projectId,
            'format'     => $format,
            'status'     => 'ready',
            'page_count' => $pageCount,
            'file_path'  => null,
            'note'       => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
