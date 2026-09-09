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
        $theme     = (string) ($project['theme'] ?? 'forest');

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

        // --- 2. Story ---
        $story = trim((string) ($project['story'] ?? ''));
        if ($story === '') {
            $story = PromptGenerator::storySeed($project)['story'];
        }
        $sections[] = [
            'key'         => 'story',
            'order'       => 2,
            'title'       => 'The story',
            'orientation' => 'portrait',
            'pages'       => 1,
            'data'        => ['text' => $story],
        ];

        // --- 3. How to play ---
        $howTo = trim((string) ($project['how_to_play'] ?? ''));
        if ($howTo === '') {
            $howTo = PromptGenerator::storySeed($project)['how_to_play'];
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

        $parts = [
            Project::usesMoveCards($project)
                ? Lang::choose('howto.prepare_move', Difficulty::MOVE_CARDS_PER_GAME, $lang)
                : Lang::get('howto.prepare_dice', $lang),
            Lang::get('howto.prepare_cards', $lang, [
                'total' => $cells * Difficulty::MISSIONS_PER_CELL,
            ]),
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
        $lang   = Lang::of($project);

        $cards = [];
        $no    = 1;

        for ($c = 0; $c < $copies; $c++) {
            foreach (self::MOVE_FACES as $face) {
                $cards[] = [
                    'no'      => $no++,
                    'steps'   => $face['forward'],
                    'back'    => $face['back'],
                    'label'   => Lang::choose('move_card.forward', $face['forward'], $lang),
                    'penalty' => Lang::choose('move_card.back', $face['back'], $lang),
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
     * Adjust a row here if a frame reads badly; nothing else needs touching.
     */
    public const SAFE_ZONES = [
        1  => ['mission' => [17.8, 21.9], 'move' => [22.8, 23.1]],
        2  => ['mission' => [19.1, 11.2], 'move' => [24.4, 24.4]],
        3  => ['mission' => [28.4, 14.7], 'move' => [25.9, 25.6]],
        4  => ['mission' => [20.0, 10.6], 'move' => [25.9, 25.9]],
        5  => ['mission' => [22.0, 20.0], 'move' => [23.4, 23.1]],
        6  => ['mission' => [22.0, 20.0], 'move' => [22.8, 22.8]],
        7  => ['mission' => [64.0, 10.0], 'move' => [24.4, 24.7]],
        8  => ['mission' => [24.7, 10.0], 'move' => [23.4, 23.4]],
        9  => ['mission' => [24.1, 20.0], 'move' => [26.6, 22.0]],
        10 => ['mission' => [20.9, 20.0], 'move' => [26.6, 26.6]],
        11 => ['mission' => [20.9, 20.0], 'move' => [26.6, 26.6]],
        12 => ['mission' => [8.0, 68.0], 'move' => [21.9, 22.2]],
        13 => ['mission' => [17.8, 20.0], 'move' => [21.9, 21.6]],
        14 => ['mission' => [18.1, 20.0], 'move' => [23.8, 23.8]],
        15 => ['mission' => [62.0, 10.0], 'move' => [23.4, 23.8]],
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
        1   => ['mission' => 93.2, 'move' => 87.1],
        2   => ['mission' => 94.0, 'move' => 86.4],
        3   => ['mission' => null, 'move' => 84.1],
        4   => ['mission' => null, 'move' => 81.2],
        5   => ['mission' => null, 'move' => 87.0],
        6   => ['mission' => null, 'move' => 87.3],
        7   => ['mission' => 93.0, 'move' => 86.1],
        8   => ['mission' => 94.0, 'move' => 94.0],
        9   => ['mission' => null, 'move' => 92.1],
        10  => ['mission' => null, 'move' => 78.6],
        11  => ['mission' => 86.1, 'move' => 81.4],
        12  => ['mission' => 93.0, 'move' => 87.7],
        13  => ['mission' => null, 'move' => 87.9],
        14  => ['mission' => 92.5, 'move' => 86.8],
        15  => ['mission' => null, 'move' => 86.8],
    ];

    /**
     * Bands that are measured to the millimetre and must be left alone.
     *
     * safeZone opens a narrow band out so a question is not squeezed, which is
     * right where the frame has spare background around it. On these it has
     * none: the band is exactly the panel the frame drew, and a millimetre
     * either way puts the words on top of the drawing.
     */
    private const EXACT_BANDS = [
        12 => ['mission' => true],
    ];

    /**
     * Frames that drew their picture window somewhere other than above the
     * words, as top / height / left / right in percent of the card.
     *
     * Frame 12 is the one that needs this: it draws a wide panel at the top
     * and a round badge below, so the question goes in the panel and the hero
     * goes in the badge - the other way round from every other frame.
     */
    private const HERO_WINDOWS = [
        12 => ['mission' => ['top' => 50.0, 'height' => 24.0, 'left' => 28.0, 'right' => 28.0]],
    ];

    /** How much of the card height one caption line needs */
    private const CAPTION_ROOM = 6.0;

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
        if ((self::CAPTION_AT[$style][$kind] ?? null) === null) {
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

        return ['top' => 8.0, 'height' => round($top - 12.0, 1), 'left' => 16.0, 'right' => 16.0];
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

    /**
     * Which card style the project uses. The move card chosen at step 2 picks it,
     * because the mission frame is the matching half of the same set.
     */
    public static function cardStyle(array $project): int
    {
        $itemId = (int) ($project['move_item_id'] ?? 0);

        if ($itemId > 0) {
            $item = Library::find($itemId);
            if ($item && preg_match('/(\d+)$/', (string) $item['code'], $m)) {
                return (int) $m[1];
            }
        }

        return 1;   // nothing chosen yet - the first set
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
        $theme = (string) ($project['theme'] ?? 'forest');
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
                $real = Library::realImagePath($item, 1);
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
