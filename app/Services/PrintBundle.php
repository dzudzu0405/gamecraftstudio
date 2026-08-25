<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Project;

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

        // --- 4. Move cards ---
        $moveCards = self::moveCards($project);
        $sections[] = [
            'key'         => 'move',
            'order'       => 4,
            'title'       => 'Move cards',
            'orientation' => 'portrait',
            'pages'       => (int) ceil(count($moveCards) / self::CARDS_PER_SHEET),
            'data'        => ['cards' => $moveCards, 'theme' => $theme],
        ];

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
                'hero_name' => trim((string) ($project['hero_name'] ?? '')) ?: 'the hero',
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
            $players = self::defaultPlayers((int) ($project['players_max'] ?? 4));
        }
        $sections[] = [
            'key'         => 'tokens',
            'order'       => 7,
            'title'       => 'Player tokens',
            'orientation' => 'portrait',
            'pages'       => 1,
            'data'        => ['players' => $players],
        ];

        return $sections;
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
    public static function moveCards(array $project): array
    {
        $count = Difficulty::MOVE_CARDS_PER_GAME;

        // Mostly small steps, a couple of big ones, and one step back for fun
        $steps = [1, 1, 2, 2, 3, 3, 4, -1];
        $steps = array_slice($steps, 0, $count);

        $cards = [];
        foreach ($steps as $i => $step) {
            $cards[] = [
                'no'    => $i + 1,
                'steps' => $step,
                'label' => $step < 0
                    ? 'Move back ' . abs($step) . ' ' . (abs($step) === 1 ? 'space' : 'spaces')
                    : 'Move forward ' . $step . ' ' . ($step === 1 ? 'space' : 'spaces'),
                'sticker' => $step < 0 ? 'moon' : 'footprint',
            ];
        }

        return $cards;
    }

    /** Default player list when the project has not named anyone yet */
    public static function defaultPlayers(int $count): array
    {
        $colors = array_keys(Art::TOKEN_COLORS);
        $out = [];
        $count = max(Project::MIN_PLAYERS, min(Project::MAX_PLAYERS, $count));

        for ($i = 0; $i < $count; $i++) {
            $out[] = [
                'name'       => 'Player ' . ($i + 1),
                'color'      => $colors[$i % count($colors)],
                'sort_order' => $i,
            ];
        }
        return $out;
    }

    /** Map background: prefers the image the user uploaded (FR-31) */
    public static function backgroundUrl(array $project): ?string
    {
        $bgId = (int) ($project['background_id'] ?? 0);
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
