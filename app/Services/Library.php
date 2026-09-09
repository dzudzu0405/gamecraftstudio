<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Url;

/**
 * Queries the content library (maps, characters, cards) and applies tier permissions.
 *
 * REAL ARTWORK vs GENERATED PLACEHOLDERS
 * ---------------------------------------------------------------------------
 * Every library item can have real artwork. The system looks for it in two
 * places, in this order:
 *
 *   1. The image_path column in the database (if you fill it in by hand or
 *      upload through the Asset Library screen).
 *
 *   2. MATCHED BY FILE NAME - by far the easiest way to upload in bulk through
 *      the cPanel File Manager. Drop the file in the right folder and name it
 *      after the library item's code:
 *
 *        uploads/library/maps/{code}.jpg          <- map frame artwork
 *        uploads/library/characters/{code}-1.jpg  <- pose 1 of a character set
 *        uploads/library/characters/{code}-2.jpg  <- pose 2 ...
 *        uploads/library/moves/{code}.jpg         <- move card back
 *        uploads/library/rewards/{code}.jpg       <- winner hero card
 *        uploads/library/templates/{code}.jpg     <- game template cover
 *
 *      Accepted extensions: .jpg .jpeg .png .webp
 *
 *   3. If neither exists, a placeholder is drawn as SVG (see Art).
 *
 * That means you can add artwork at any time without touching the code.
 */
class Library
{
    public const KIND_MAP       = 'map';
    public const KIND_CHARACTER = 'character';
    public const KIND_MOVE      = 'move';
    public const KIND_REWARD    = 'reward';

    /** Mission card frames: artwork only, with no library row behind them */
    public const KIND_MISSION   = 'mission';

    /**
     * The pose that stands for a character set.
     *
     * Every set is eight drawings, and pose 1 is whatever the artist drew
     * first - a puppy sprawled on its front, a bunny in profile behind a
     * lightning bolt. Read at thumbnail size those say very little about the
     * character. Pose 8 is the celebration in almost every set: front on,
     * whole body, arms up, sparkles. That is the one to choose from, and the
     * one that goes on the winner card.
     *
     * Two sets number theirs differently, so they are named here. Anything
     * missing that pose falls back to the first drawing rather than a gap.
     */
    private const COVER_POSE = 8;

    private const COVER_POSE_BY_CODE = [
        'char-01' => 7,   // pose 8 is the bunny beside a plank
        'char-04' => 7,   // pose 8 is the bear cub behind a rock
    ];

    /** Which pose represents this set, given what artwork actually exists */
    public static function coverPose(array $item): int
    {
        if ((string) ($item['kind'] ?? '') !== self::KIND_CHARACTER) {
            return 1;
        }

        $code = trim((string) ($item['code'] ?? ''));
        $pose = self::COVER_POSE_BY_CODE[$code] ?? self::COVER_POSE;

        // A set drawn only as far as pose 3 still needs a picture
        return self::hasRealImage($item, $pose) ? $pose : 1;
    }

    /** Sub-folder inside uploads/library/ for each kind */
    private const FOLDERS = [
        self::KIND_MAP       => 'maps',
        self::KIND_CHARACTER => 'characters',
        self::KIND_MOVE      => 'moves',
        self::KIND_REWARD    => 'rewards',
        self::KIND_MISSION   => 'missions',
        'template'           => 'templates',
    ];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Caches directory listings so we only hit the disk once per request */
    private static array $scanCache = [];

    public static function kindLabel(string $kind): string
    {
        return [
            self::KIND_MAP       => 'Map frames',
            self::KIND_CHARACTER => 'Character sets',
            self::KIND_MOVE      => 'Move cards',
            self::KIND_REWARD    => 'Hero cards',
        ][$kind] ?? ucfirst($kind);
    }

    // ---------------------------------------------------------------
    //  Queries
    // ---------------------------------------------------------------

    /**
     * Everything of a kind, with each row marked as available to this plan or
     * not, so a picker can show what the buyer has not bought yet.
     *
     * Kept separate from forPlan(): that one answers "what may this plan use",
     * and entitlement checks depend on it returning nothing else.
     *
     * What the buyer owns comes first and the locked rows follow, so the grid
     * reads as "yours, then the rest" instead of scattering the greyed-out
     * tiles through the choices.
     *
     * @return array rows with an added 'locked' flag
     */
    public static function withLocked(string $kind, ?string $plan, array $filters = []): array
    {
        $open = $shut = [];

        foreach (self::forPlan($kind, null, $filters + ['all_tiers' => true]) as $row) {
            $row['locked'] = !self::unlocked($row, $plan);

            if ($row['locked']) {
                $shut[] = $row;
            } else {
                $open[] = $row;
            }
        }

        return array_merge($open, $shut);
    }

    /**
     * Library items this plan may use, inheritance included (FR-29).
     *
     * @param array $filters cells, theme, search, limit, offset, all_tiers
     */
    public static function forPlan(string $kind, ?string $plan, array $filters = []): array
    {
        $sql    = 'SELECT * FROM library_items WHERE kind = ? AND is_active = 1';
        $params = [$kind];

        // all_tiers is for the pickers, which show the locked rows greyed out
        if (empty($filters['all_tiers'])) {
            $tiers = Tiers::unlockedTiers($plan);
            $sql  .= ' AND tier IN (' . implode(', ', array_fill(0, count($tiers), '?')) . ')';
            $params = array_merge($params, $tiers);
        }

        if (!empty($filters['cells'])) {
            $sql .= ' AND cells = ?';
            $params[] = (int) $filters['cells'];
        }
        if (!empty($filters['theme'])) {
            $sql .= ' AND theme = ?';
            $params[] = $filters['theme'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= ' OFFSET ' . (int) $filters['offset'];
            }
        }

        return Database::all($sql, $params);
    }

    /** The whole library including locked items - the Asset Library greys those out */
    public static function allOfKind(string $kind, array $filters = []): array
    {
        $sql    = 'SELECT * FROM library_items WHERE kind = ? AND is_active = 1';
        $params = [$kind];

        if (!empty($filters['cells'])) {
            $sql .= ' AND cells = ?';
            $params[] = (int) $filters['cells'];
        }
        if (!empty($filters['theme'])) {
            $sql .= ' AND theme = ?';
            $params[] = $filters['theme'];
        }
        if (!empty($filters['tier'])) {
            $sql .= ' AND tier = ?';
            $params[] = $filters['tier'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return Database::all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM library_items WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByCode(string $kind, string $code): ?array
    {
        return Database::first('SELECT * FROM library_items WHERE kind = ? AND code = ? LIMIT 1', [$kind, $code]);
    }

    /** May this plan use the given item? */
    public static function unlocked(array $item, ?string $plan): bool
    {
        return Tiers::atLeast($plan, (string) ($item['tier'] ?? Tiers::STARTER));
    }

    /** Count unlocked items of a kind - used by the stat tiles */
    public static function countForPlan(string $kind, ?string $plan): int
    {
        $tiers = Tiers::unlockedTiers($plan);
        $in    = implode(', ', array_fill(0, count($tiers), '?'));
        return Database::count(
            'SELECT COUNT(*) FROM library_items WHERE kind = ? AND is_active = 1 AND tier IN (' . $in . ')',
            array_merge([$kind], $tiers)
        );
    }

    public static function countAll(string $kind): int
    {
        return Database::count('SELECT COUNT(*) FROM library_items WHERE kind = ? AND is_active = 1', [$kind]);
    }

    // ---------------------------------------------------------------
    //  Artwork
    // ---------------------------------------------------------------

    /**
     * Image URL for a library item.
     * Prefers real artwork; falls back to the generated SVG route.
     *
     * @param int $variant Which pose (character sets only), starting at 1
     */
    public static function imageFor(array $item, int $variant = 1): string
    {
        $real = self::realImagePath($item, $variant);
        if ($real !== null) {
            return Url::upload($real);
        }
        return self::generatedImageUrl($item, $variant);
    }

    /** Is there real artwork yet? Drives the "placeholder" badge in the Asset Library */
    public static function hasRealImage(array $item, int $variant = 1): bool
    {
        return self::realImagePath($item, $variant) !== null;
    }

    /**
     * Locate real artwork. Returns a path relative to the uploads/ folder,
     * or null if there is none yet.
     */
    public static function realImagePath(array $item, int $variant = 1): ?string
    {
        // 1. Path already recorded in the database
        $stored = trim((string) ($item['image_path'] ?? ''));
        if ($stored !== '') {
            $candidate = self::uploadsDir() . '/' . ltrim($stored, '/');
            if (is_file($candidate)) {
                return ltrim($stored, '/');
            }
        }

        // 2. Matched by file name
        $kind = (string) ($item['kind'] ?? 'map');
        $code = trim((string) ($item['code'] ?? ''));
        if ($code === '') {
            return null;
        }

        $folder = self::FOLDERS[$kind] ?? $kind . 's';
        $names  = [$code];

        // Character sets have several poses: prefer {code}-{n}, fall back to {code}
        if ($kind === self::KIND_CHARACTER || $kind === self::KIND_MOVE) {
            array_unshift($names, $code . '-' . max(1, $variant));
        }

        foreach ($names as $name) {
            foreach (self::EXTENSIONS as $ext) {
                $rel = 'library/' . $folder . '/' . $name . '.' . $ext;
                if (self::fileExists($rel)) {
                    return $rel;
                }
            }
        }

        return null;
    }

    /**
     * Card frame artwork, found by style number rather than by library item.
     *
     * Mission frames have no library row of their own: they are the other half of
     * a move card set, so move-07 and mission-07 are the same design. Returns a
     * path relative to uploads/, or null when that style has no artwork yet.
     */
    public static function framePath(string $folder, int $style): ?string
    {
        $code = sprintf('%s-%02d', rtrim($folder, 's'), max(1, $style));

        foreach (self::EXTENSIONS as $ext) {
            $rel = 'library/' . $folder . '/' . $code . '.' . $ext;
            if (self::fileExists($rel)) {
                return $rel;
            }
        }

        return null;
    }
    /** URL of the generated SVG placeholder (the /art/... routes) */
    public static function generatedImageUrl(array $item, int $variant = 1): string
    {
        $kind  = (string) ($item['kind'] ?? 'map');
        $seed  = (string) ($item['art_seed'] ?? ($item['code'] ?? 'seed'));
        $theme = (string) ($item['theme'] ?? 'forest');

        switch ($kind) {
            case self::KIND_CHARACTER:
                return Url::to('art/character/' . rawurlencode($seed) . '/' . max(1, $variant) . '.svg');
            case self::KIND_MOVE:
                return Url::to('art/card/move/' . rawurlencode($theme) . '.svg');
            case self::KIND_REWARD:
                return Url::to('art/card/reward/' . rawurlencode($theme) . '.svg');
            case self::KIND_MAP:
            default:
                return Url::to('art/scene/' . rawurlencode($theme) . '/' . rawurlencode($seed) . '.svg');
        }
    }

    /** Cover image for a ready-made game template */
    public static function templateImage(array $tpl): string
    {
        $stored = trim((string) ($tpl['image_path'] ?? ''));
        if ($stored !== '' && is_file(self::uploadsDir() . '/' . ltrim($stored, '/'))) {
            return Url::upload(ltrim($stored, '/'));
        }

        $code = trim((string) ($tpl['code'] ?? ''));
        if ($code !== '') {
            foreach (self::EXTENSIONS as $ext) {
                $rel = 'library/templates/' . $code . '.' . $ext;
                if (self::fileExists($rel)) {
                    return Url::upload($rel);
                }
            }
        }

        return Url::to('art/scene/' . rawurlencode((string) ($tpl['theme'] ?? 'forest')) . '/' . rawurlencode((string) ($tpl['art_seed'] ?? $code)) . '.svg');
    }

    /**
     * Artwork completion stats, measured against the content production
     * targets in SRS section 11 (FR-34).
     */
    public static function artworkProgress(): array
    {
        $rows = [];
        $targets = [
            self::KIND_MAP       => ['label' => 'Maps',              'target' => 36],
            self::KIND_CHARACTER => ['label' => 'Character sets',    'target' => 30],
            self::KIND_MOVE      => ['label' => 'Move card designs', 'target' => 15],
            self::KIND_REWARD    => ['label' => 'Hero card designs', 'target' => 30],
        ];

        foreach ($targets as $kind => $info) {
            $items = Database::all('SELECT * FROM library_items WHERE kind = ? AND is_active = 1', [$kind]);
            $withArt = 0;
            foreach ($items as $item) {
                if (self::realImagePath($item) !== null) {
                    $withArt++;
                }
            }
            $rows[$kind] = [
                'label'    => $info['label'],
                'target'   => $info['target'],
                'in_db'    => count($items),
                'with_art' => $withArt,
                'percent'  => $info['target'] > 0 ? (int) round($withArt / $info['target'] * 100) : 0,
            ];
        }

        // Mission cards are counted by base template instead (section 11: 15 templates)
        $rows['mission'] = [
            'label'    => 'Mission card templates',
            'target'   => 15,
            'in_db'    => Database::count('SELECT COUNT(*) FROM mission_templates WHERE is_active = 1'),
            'with_art' => Database::count('SELECT COUNT(*) FROM mission_templates WHERE is_active = 1'),
            'percent'  => 0,
        ];
        $rows['mission']['percent'] = (int) round(min(100, $rows['mission']['in_db'] / 15 * 100));

        return $rows;
    }

    // ---------------------------------------------------------------
    //  Internals
    // ---------------------------------------------------------------

    private static function uploadsDir(): string
    {
        return dirname(__DIR__, 2) . '/uploads';
    }

    /** Checks for a file under uploads/, caching each directory listing */
    private static function fileExists(string $relative): bool
    {
        $dir  = dirname($relative);
        $file = basename($relative);

        if (!isset(self::$scanCache[$dir])) {
            $full = self::uploadsDir() . '/' . $dir;
            $list = [];
            if (is_dir($full)) {
                foreach (scandir($full) ?: [] as $f) {
                    if ($f !== '.' && $f !== '..') {
                        $list[strtolower($f)] = true;
                    }
                }
            }
            self::$scanCache[$dir] = $list;
        }

        return isset(self::$scanCache[$dir][strtolower($file)]);
    }
}
