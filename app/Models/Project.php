<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use App\Core\Url;
use App\Services\Art;
use App\Services\Difficulty;
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\Tiers;

/** Queries and operations on a user's projects */
class Project
{
    public const STATUSES = ['draft', 'in_progress', 'ready', 'published'];

    /**
     * How many people can play one game. Referenced by the wizard, the studio's
     * token list and the printed token sheet, so the three cannot drift apart.
     */
    public const MIN_PLAYERS = 2;
    public const MAX_PLAYERS = 6;

    /**
     * Where the map background comes from.
     *
     * A ready-made theme brings its own artwork, so the wizard skips the whole
     * background step. THEME_CUSTOM is the extra tile on the theme grid meaning
     * "I will make my own" - it is a background choice, not a real theme, and
     * never reaches the theme column.
     */
    public const BACKGROUND_THEME  = 'theme';
    public const BACKGROUND_CUSTOM = 'custom';
    public const THEME_CUSTOM      = 'custom';

    /**
     * How a turn moves a player. The two are alternatives, never both: either
     * you roll the paper die, or you draw a move card. Beginner is dice only,
     * because a six-year-old counting pips is enough to be going on with.
     */
    public const MOVE_DICE  = 'dice';
    public const MOVE_CARDS = 'cards';

    /** Does this project move by drawing cards? */
    public static function usesMoveCards(array $project): bool
    {
        return ($project['movement'] ?? self::MOVE_DICE) === self::MOVE_CARDS;
    }

    /** Difficulties where the buyer gets to pick; Beginner has no choice */
    public static function canChooseMovement(string $difficulty): bool
    {
        return $difficulty !== Difficulty::BEGINNER;
    }

    /** True when the map background comes from the chosen theme */
    public static function usesThemeBackground(array $project): bool
    {
        return ($project['background_mode'] ?? self::BACKGROUND_THEME) !== self::BACKGROUND_CUSTOM;
    }

    /** Sort options for the dropdown (FR-12) */
    public const SORTS = [
        'recent'  => 'Recently updated',
        'created' => 'Newest first',
        'name'    => 'Title A - Z',
        'age'     => 'Youngest age first',
        'status'  => 'Status',
    ];

    public static function find(int $id, int $userId): ?array
    {
        return Database::first(
            'SELECT * FROM projects WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        );
    }

    /**
     * Project list with search and sorting (FR-11, FR-12).
     * @param array $filters search, status, sort, limit
     */
    public static function forUser(int $userId, array $filters = []): array
    {
        $sql    = 'SELECT * FROM projects WHERE user_id = ?';
        $params = [$userId];

        if (!empty($filters['search'])) {
            $sql .= ' AND title LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        $sql .= ' ORDER BY ' . self::orderBy($filters['sort'] ?? 'recent');

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return Database::all($sql, $params);
    }

    private static function orderBy(string $sort): string
    {
        // A fixed whitelist - never build ORDER BY from user input
        return [
            'recent'  => 'updated_at DESC, id DESC',
            'created' => 'created_at DESC, id DESC',
            'name'    => 'title ASC',
            'age'     => 'age_min ASC, age_max ASC',
            'status'  => 'status ASC, updated_at DESC',
        ][$sort] ?? 'updated_at DESC, id DESC';
    }

    public static function countForUser(int $userId, ?string $status = null): int
    {
        if ($status !== null) {
            return Database::count('SELECT COUNT(*) FROM projects WHERE user_id = ? AND status = ?', [$userId, $status]);
        }
        return Database::count('SELECT COUNT(*) FROM projects WHERE user_id = ?', [$userId]);
    }

    /** Is there room for another project on this plan? */
    public static function canCreate(int $userId, ?string $plan): bool
    {
        $limit = Tiers::projectLimit($plan);
        if ($limit === 0) {
            return true;   // unlimited
        }
        return self::countForUser($userId) < $limit;
    }

    /** Creates a project with defaults taken from the difficulty preset */
    public static function create(int $userId, array $data): int
    {
        $difficulty = $data['difficulty'] ?? Difficulty::STANDARD;
        $cfg        = Difficulty::get($difficulty);
        $title      = trim((string) ($data['title'] ?? '')) ?: 'Untitled game';
        $now        = date('Y-m-d H:i:s');

        return Database::insert('projects', [
            'user_id'           => $userId,
            'title'             => mb_substr($title, 0, 160),
            'slug'              => self::uniqueSlug($title),
            'status'            => 'draft',
            'difficulty'        => $difficulty,
            'theme'             => $data['theme'] ?? 'forest',
            'subjects'          => $data['subjects'] ?? 'math,nature',
            'setting'           => $data['setting'] ?? null,
            'rescue_target'     => $data['rescue_target'] ?? null,
            // Beginner never gets the choice, so it is forced here as well as in the form
            'movement'          => self::canChooseMovement($difficulty)
                                       ? ($data['movement'] ?? self::MOVE_DICE)
                                       : self::MOVE_DICE,
            'background_mode'   => $data['background_mode'] ?? self::BACKGROUND_THEME,
            'question_count'    => (int) ($data['question_count'] ?? $cfg['mission_cards']),
            'cells'             => (int) $cfg['cells'],
            'players_min'       => (int) ($data['players_min'] ?? 2),
            'players_max'       => (int) ($data['players_max'] ?? 4),
            'age_min'           => (int) ($data['age_min'] ?? $cfg['age_min']),
            'age_max'           => (int) ($data['age_max'] ?? $cfg['age_max']),
            'map_item_id'       => $data['map_item_id'] ?? null,
            'character_item_id' => $data['character_item_id'] ?? null,
            'move_item_id'      => $data['move_item_id'] ?? null,
            'reward_item_id'    => $data['reward_item_id'] ?? null,
            'background_id'     => null,
            'cover_seed'        => 'p' . bin2hex(random_bytes(4)),
            'story'             => $data['story'] ?? null,
            'how_to_play'       => $data['how_to_play'] ?? null,
            'hero_name'         => $data['hero_name'] ?? null,
            'wizard_step'       => 1,
            'settings'          => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    public static function touch(int $id, array $data = []): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('projects', $data, ['id' => $id]);
    }

    /** Duplicates a project along with all its mission cards (FR-14) */
    public static function duplicate(int $id, int $userId): ?int
    {
        $src = self::find($id, $userId);
        if (!$src) {
            return null;
        }

        return Database::transaction(function () use ($src, $userId) {
            $now   = date('Y-m-d H:i:s');
            $title = mb_substr($src['title'] . ' (copy)', 0, 160);

            $copy = $src;
            unset($copy['id']);
            $copy['user_id']      = $userId;
            $copy['title']        = $title;
            $copy['slug']         = self::uniqueSlug($title);
            $copy['status']       = 'draft';
            $copy['cover_seed']   = 'p' . bin2hex(random_bytes(4));
            $copy['published_at'] = null;
            $copy['created_at']   = $now;
            $copy['updated_at']   = $now;

            $newId = Database::insert('projects', $copy);

            // Copy the mission cards
            foreach (MissionMatcher::forProject((int) $src['id']) as $m) {
                unset($m['id']);
                $m['project_id'] = $newId;
                $m['created_at'] = $now;
                Database::insert('project_missions', $m);
            }

            // Copy the player list
            $players = Database::all('SELECT * FROM project_players WHERE project_id = ?', [(int) $src['id']]);
            foreach ($players as $p) {
                unset($p['id']);
                $p['project_id'] = $newId;
                Database::insert('project_players', $p);
            }

            return $newId;
        });
    }

    /** Deletes a project and everything attached to it */
    public static function delete(int $id, int $userId): bool
    {
        $project = self::find($id, $userId);
        if (!$project) {
            return false;
        }

        Database::transaction(function () use ($id) {
            Database::delete('project_missions', ['project_id' => $id]);
            Database::delete('project_players', ['project_id' => $id]);
            Database::run('UPDATE exports SET project_id = NULL WHERE project_id = ?', [$id]);
            Database::run('UPDATE user_assets SET project_id = NULL WHERE project_id = ?', [$id]);
            Database::delete('projects', ['id' => $id]);
        });

        return true;
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Helper::slug($title);
        $slug = $base;
        $i    = 2;

        while (Database::count('SELECT COUNT(*) FROM projects WHERE slug = ?', [$slug]) > 0) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 200) {
                $slug = $base . '-' . bin2hex(random_bytes(3));
                break;
            }
        }

        return $slug;
    }

    /** Cover image shown on the project card */
    public static function coverUrl(array $project, int $w = 480, int $h = 330): string
    {
        // Prefer the background the user uploaded
        if (!empty($project['background_id'])) {
            $asset = Database::first('SELECT * FROM user_assets WHERE id = ? LIMIT 1', [(int) $project['background_id']]);
            if ($asset) {
                $rel = $asset['thumb_path'] ?: $asset['path'];
                if ($rel && is_file(dirname(__DIR__, 2) . '/uploads/' . ltrim((string) $rel, '/'))) {
                    return Url::upload(ltrim((string) $rel, '/'));
                }
            }
        }

        $theme = (string) ($project['theme'] ?? 'forest');
        $seed  = (string) ($project['cover_seed'] ?? ($project['slug'] ?? 'cover'));
        return Url::to('art/scene/' . rawurlencode($theme) . '/' . rawurlencode($seed) . '.svg?w=' . $w . '&h=' . $h);
    }

    /** Completion percentage, shown on the Studio progress bar */
    public static function progress(array $project): int
    {
        $done  = 0;
        $total = 5;

        if (trim((string) ($project['title'] ?? '')) !== '')  { $done++; }
        if (!empty($project['map_item_id']))                  { $done++; }
        if (self::usesThemeBackground($project) || !empty($project['background_id'])) { $done++; }

        $expected = Difficulty::missionCount((string) ($project['difficulty'] ?? 'standard'));
        if (MissionMatcher::countForProject((int) $project['id']) >= $expected) { $done++; }

        if (trim((string) ($project['story'] ?? '')) !== '')   { $done++; }

        return (int) round($done / $total * 100);
    }

    /** The question subjects as an array */
    public static function subjects(array $project): array
    {
        $raw = trim((string) ($project['subjects'] ?? ''));
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** The library items this project is using */
    public static function libraryItems(array $project): array
    {
        $out = [];
        foreach ([
            'map'       => 'map_item_id',
            'character' => 'character_item_id',
            'move'      => 'move_item_id',
            'reward'    => 'reward_item_id',
        ] as $key => $col) {
            $out[$key] = !empty($project[$col]) ? Library::find((int) $project[$col]) : null;
        }
        return $out;
    }
}
