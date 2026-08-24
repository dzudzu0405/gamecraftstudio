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

    /** Sub-folder inside uploads/library/ for each kind */
    private const FOLDERS = [
        self::KIND_MAP       => 'maps',
        self::KIND_CHARACTER => 'characters',
        self::KIND_MOVE      => 'moves',
        self::KIND_REWARD    => 'rewards',
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
     * Library items this plan may use, inheritance included (FR-29).
     *
     * @param array $filters cells, theme, search, limit, offset
     */
    public static function forPlan(string $kind, ?string $plan, array $filters = []): array
    {
        $tiers = Tiers::unlockedTiers($plan);
        $in    = implode(', ', array_fill(0, count($tiers), '?'));

        $sql    = 'SELECT * FROM library_items WHERE kind = ? AND is_active = 1 AND tier IN (' . $in . ')';
        $params = array_merge([$kind], $tiers);

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
            self::KIND_MOVE      => ['label' => 'Move card designs', 'target' => 20],
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
