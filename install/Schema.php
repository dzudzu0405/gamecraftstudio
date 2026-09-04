<?php
namespace Install;

/**
 * The database schema.
 *
 * Defined once here and rendered as SQL for both MySQL (production on cPanel)
 * and SQLite (local testing), so the two environments can never drift apart.
 */
class Schema
{
    /** Tables in creation order - anything referenced has to come first */
    public static function tables(): array
    {
        return [
            'users' => [
                'id'            => 'pk',
                'name'          => 'varchar:120 notnull',
                'email'         => 'varchar:190 notnull',
                'password_hash' => 'varchar:255 notnull',
                'plan'          => "varchar:20 notnull default:starter",
                'role'          => "varchar:20 notnull default:creator",
                'avatar_seed'   => 'varchar:60 null',
                'locale'        => "varchar:10 notnull default:en",
                'is_active'     => 'tinyint notnull default:1',
                // Google's subject id, set when the account signs in with Google
                'google_id'     => 'varchar:64 null',
                'avatar_url'    => 'varchar:255 null',
                'plan_started_at' => 'datetime null',
                'last_login_at' => 'datetime null',
                'created_at'    => 'datetime notnull',
                'updated_at'    => 'datetime notnull',
                '#unique'       => [['email']],
                '#index'        => [['plan'], ['google_id']],
            ],

            // Password reset links. Only a hash of each token is stored, so a
            // leaked database still cannot be used to take over an account.
            'password_resets' => [
                'id'         => 'pk',
                'user_id'    => 'fk:users',
                'token_hash' => 'varchar:64 notnull',
                'expires_at' => 'datetime notnull',
                'used_at'    => 'datetime null',
                'request_ip' => 'varchar:45 null',
                'created_at' => 'datetime notnull',
                '#index'     => [['token_hash'], ['user_id'], ['expires_at']],
            ],

            // The ready-made content library: maps, characters, move cards, hero cards
            'library_items' => [
                'id'          => 'pk',
                'kind'        => 'varchar:20 notnull',   // map | character | move | reward | sticker
                'tier'        => 'varchar:20 notnull',   // starter | pro | publisher
                'code'        => 'varchar:60 notnull',
                'name'        => 'varchar:160 notnull',
                'theme'       => 'varchar:30 null',
                'cells'       => 'int null',             // kind = map only: 12 | 18 | 24
                'poses'       => 'int null',             // kind = character only
                'card_count'  => 'int null',             // kind = move only
                'art_seed'    => 'varchar:80 notnull',
                'image_path'  => 'varchar:255 null',     // filled in once real artwork exists
                'meta'        => 'text null',            // JSON
                'sort_order'  => 'int notnull default:0',
                'is_active'   => 'tinyint notnull default:1',
                'created_at'  => 'datetime notnull',
                '#unique'     => [['kind', 'code']],
                '#index'      => [['kind', 'tier'], ['kind', 'cells'], ['theme']],
            ],

            // The 15 base mission templates. Variations are generated from these (FR-35)
            'mission_templates' => [
                'id'         => 'pk',
                'code'       => 'varchar:60 notnull',
                'name'       => 'varchar:160 notnull',
                'subject'    => 'varchar:40 notnull',    // math | literacy | english | science | nature | logic | life | geography
                'level'      => 'varchar:20 notnull',    // beginner | standard | advanced
                'tier'       => 'varchar:20 notnull',
                'sticker'    => 'varchar:30 notnull',
                'pattern'    => 'text notnull',          // for example: "{a} + {b} = ?"
                'answer'     => 'text notnull',          // for example: "{a+b}"
                'variables'  => 'text null',             // JSON describing the range of a, b and so on
                'hint'       => 'varchar:255 null',
                'is_active'  => 'tinyint notnull default:1',
                'created_at' => 'datetime notnull',
                '#unique'    => [['code']],
                '#index'     => [['subject', 'level'], ['tier']],
            ],

            'projects' => [
                'id'              => 'pk',
                'user_id'         => 'fk:users',
                'title'           => 'varchar:160 notnull',
                'slug'            => 'varchar:180 notnull',
                'status'          => "varchar:20 notnull default:draft", // draft | in_progress | ready | published
                'difficulty'      => "varchar:20 notnull default:standard",
                'theme'           => "varchar:30 notnull default:forest",
                'subjects'        => 'varchar:255 null',   // question subjects, comma separated (FR-23)
                'setting'         => 'varchar:120 null',   // where the adventure takes place, in the buyer's words
                'rescue_target'   => 'varchar:120 null',   // who the players set out to rescue, in the buyer's words
                'movement'        => "varchar:10 notnull default:dice",  // dice | cards - how a turn moves you
                'background_mode' => "varchar:20 notnull default:theme",  // theme | custom (custom = the buyer makes their own)
                'question_count'  => 'int notnull default:90',
                'cells'           => 'int notnull default:18',
                'players_min'     => 'int notnull default:2',
                'players_max'     => 'int notnull default:4',
                'age_min'         => 'int notnull default:6',
                'age_max'         => 'int notnull default:9',
                'map_item_id'     => 'int null',           // library_items.kind = map
                'character_item_id' => 'int null',
                'move_item_id'    => 'int null',
                'reward_item_id'  => 'int null',
                'background_id'   => 'int null',           // user_assets.id - the user's own artwork (FR-31)
                'cover_seed'      => 'varchar:80 null',
                'story'           => 'text null',          // the story page
                'how_to_play'     => 'text null',          // the rules page
                'hero_name'       => 'varchar:120 null',
                'wizard_step'     => 'int notnull default:1',
                'settings'        => 'text null',           // JSON: map margins, frame style and so on
                'published_at'    => 'datetime null',
                'created_at'      => 'datetime notnull',
                'updated_at'      => 'datetime notnull',
                '#index'          => [['user_id', 'status'], ['user_id', 'updated_at'], ['slug']],
            ],

            // A project's actual mission cards (FR-24 auto-match, FR-25 user-written)
            'project_missions' => [
                'id'          => 'pk',
                'project_id'  => 'fk:projects',
                'cell_no'     => 'int notnull',            // which map space (1..12/18/24)
                'slot_no'     => 'int notnull',            // position in that space's pile (1..5)
                'source'      => "varchar:20 notnull default:library", // library | custom
                'template_id' => 'int null',
                'subject'     => 'varchar:40 null',
                'question'    => 'text notnull',
                'answer'      => 'text null',
                'sticker'     => "varchar:30 notnull default:star",
                'created_at'  => 'datetime notnull',
                '#index'      => [['project_id', 'cell_no', 'slot_no']],
            ],

            // Player tokens (SRS section 9)
            'project_players' => [
                'id'         => 'pk',
                'project_id' => 'fk:projects',
                'name'       => 'varchar:60 notnull',
                'color'      => "varchar:20 notnull default:red",
                'sort_order' => 'int notnull default:0',
                '#index'     => [['project_id']],
            ],

            // User uploads. NOTE: only the path is stored, never the image itself
            'user_assets' => [
                'id'          => 'pk',
                'user_id'     => 'fk:users',
                'project_id'  => 'int null',
                'kind'        => "varchar:20 notnull default:background",
                'original_name' => 'varchar:190 null',
                'path'        => 'varchar:255 notnull',
                'thumb_path'  => 'varchar:255 null',
                'mime'        => 'varchar:60 null',
                'width'       => 'int null',
                'height'      => 'int null',
                'size_bytes'  => 'int null',
                'created_at'  => 'datetime notnull',
                '#index'      => [['user_id', 'kind'], ['project_id']],
            ],

            // Export history (FR-27, the My Exports module)
            'exports' => [
                'id'          => 'pk',
                'user_id'     => 'fk:users',
                'project_id'  => 'int null',
                'format'      => "varchar:20 notnull default:print", // print | png | listing
                'status'      => "varchar:20 notnull default:ready", // pending | ready | failed
                'page_count'  => 'int null',
                'file_path'   => 'varchar:255 null',
                'note'        => 'varchar:255 null',
                'created_at'  => 'datetime notnull',
                '#index'      => [['user_id', 'created_at'], ['project_id']],
            ],

            // 50+ ready-made game templates (FR-17)
            'game_templates' => [
                'id'          => 'pk',
                'code'        => 'varchar:60 notnull',
                'name'        => 'varchar:160 notnull',
                'description' => 'varchar:400 null',
                'theme'       => 'varchar:30 notnull',
                'difficulty'  => 'varchar:20 notnull',
                'subjects'    => 'varchar:255 null',
                'tier'        => 'varchar:20 notnull',
                'age_min'     => 'int notnull default:6',
                'age_max'     => 'int notnull default:9',
                'players_min' => 'int notnull default:2',
                'players_max' => 'int notnull default:4',
                'art_seed'    => 'varchar:80 notnull',
                'image_path'  => 'varchar:255 null',
                'uses_count'  => 'int notnull default:0',
                'is_active'   => 'tinyint notnull default:1',
                'created_at'  => 'datetime notnull',
                '#unique'     => [['code']],
                '#index'      => [['theme'], ['difficulty'], ['tier']],
            ],

            // Community inspiration (FR-19, FR-20)
            'community_posts' => [
                'id'          => 'pk',
                'user_id'     => 'int null',
                'project_id'  => 'int null',
                'author_name' => 'varchar:120 notnull',
                'title'       => 'varchar:180 notnull',
                'caption'     => 'varchar:400 null',
                'theme'       => 'varchar:30 notnull',
                'difficulty'  => 'varchar:20 notnull',
                'art_seed'    => 'varchar:80 notnull',
                'image_path'  => 'varchar:255 null',
                'likes'       => 'int notnull default:0',
                'is_featured' => 'tinyint notnull default:0',
                'created_at'  => 'datetime notnull',
                '#index'      => [['is_featured'], ['created_at']],
            ],

            // Marketplace (buying and selling templates and assets)
            'marketplace_items' => [
                'id'          => 'pk',
                'seller_name' => 'varchar:120 notnull',
                'title'       => 'varchar:180 notnull',
                'description' => 'varchar:400 null',
                'kind'        => "varchar:20 notnull default:template", // template | asset_pack | bundle
                'theme'       => 'varchar:30 notnull',
                'price_cents' => 'int notnull default:0',
                'art_seed'    => 'varchar:80 notnull',
                'image_path'  => 'varchar:255 null',
                'rating'      => 'int notnull default:50',   // 0..50, displayed as rating/10
                'sales'       => 'int notnull default:0',
                'is_active'   => 'tinyint notnull default:1',
                'created_at'  => 'datetime notnull',
                '#index'      => [['kind'], ['theme']],
            ],

            // Product listings for Amazon / Etsy (FR-32, Publisher plan only)
            'listings' => [
                'id'          => 'pk',
                'user_id'     => 'fk:users',
                'project_id'  => 'int null',
                'channel'     => "varchar:20 notnull default:etsy",  // etsy | amazon
                'title'       => 'varchar:200 notnull',
                'bullet_points' => 'text null',
                'description' => 'text null',
                'tags'        => 'varchar:400 null',
                'price_cents' => 'int notnull default:0',
                'created_at'  => 'datetime notnull',
                '#index'      => [['user_id'], ['project_id']],
            ],
        ];
    }

    /** Renders every CREATE TABLE statement for the given driver */
    public static function sql(string $driver): array
    {
        $out = [];
        foreach (self::tables() as $table => $columns) {
            $out[] = self::createTable($table, $columns, $driver);
        }
        return $out;
    }

    private static function createTable(string $table, array $spec, string $driver): string
    {
        $lines   = [];
        $uniques = $spec['#unique'] ?? [];
        $indexes = $spec['#index'] ?? [];
        unset($spec['#unique'], $spec['#index']);

        foreach ($spec as $name => $def) {
            $lines[] = '  ' . self::column($name, $def, $driver);
        }

        foreach ($uniques as $cols) {
            $cols = implode(', ', array_map(fn($c) => self::q($c), $cols));
            $lines[] = '  UNIQUE (' . $cols . ')';
        }

        /*
         * MySQL: declare indexes inside CREATE TABLE. That way importing the
         * .sql file through phpMyAdmin is a single statement, and re-running it
         * never trips over "Duplicate key name".
         *
         * SQLite does not support that, so it needs separate CREATE INDEX calls.
         */
        if ($driver === 'mysql') {
            foreach ($indexes as $cols) {
                $lines[] = '  KEY ' . self::q(self::indexName($table, $cols))
                         . ' (' . implode(', ', array_map(fn($c) => self::q($c), $cols)) . ')';
            }
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . self::q($table) . " (\n" . implode(",\n", $lines) . "\n)";
        if ($driver === 'mysql') {
            $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        }
        $sql .= ';';

        if ($driver !== 'mysql') {
            foreach ($indexes as $cols) {
                $sql .= "\nCREATE INDEX IF NOT EXISTS " . self::q(self::indexName($table, $cols))
                      . ' ON ' . self::q($table)
                      . ' (' . implode(', ', array_map(fn($c) => self::q($c), $cols)) . ');';
            }
        }

        return $sql;
    }

    private static function indexName(string $table, array $cols): string
    {
        return substr('idx_' . $table . '_' . implode('_', $cols), 0, 60);
    }

    private static function column(string $name, string $def, string $driver): string
    {
        $parts = explode(' ', trim($def));
        $type  = array_shift($parts);

        if ($type === 'pk') {
            return $driver === 'mysql'
                ? self::q($name) . ' INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'
                : self::q($name) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
        }

        if (str_starts_with($type, 'fk:')) {
            return $driver === 'mysql'
                ? self::q($name) . ' INT UNSIGNED NOT NULL'
                : self::q($name) . ' INTEGER NOT NULL';
        }

        $sqlType = 'VARCHAR(190)';
        if (str_starts_with($type, 'varchar:')) {
            $len = (int) substr($type, 8);
            $sqlType = $driver === 'mysql' ? 'VARCHAR(' . $len . ')' : 'TEXT';
        } elseif ($type === 'int') {
            $sqlType = $driver === 'mysql' ? 'INT' : 'INTEGER';
        } elseif ($type === 'tinyint') {
            $sqlType = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';
        } elseif ($type === 'text') {
            $sqlType = 'TEXT';
        } elseif ($type === 'datetime') {
            $sqlType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        }

        $sql = self::q($name) . ' ' . $sqlType;

        $notnull = in_array('notnull', $parts, true);
        $sql .= $notnull ? ' NOT NULL' : ' NULL';

        foreach ($parts as $p) {
            if (str_starts_with($p, 'default:')) {
                $val = substr($p, 8);
                $sql .= is_numeric($val) ? ' DEFAULT ' . $val : " DEFAULT '" . $val . "'";
            }
        }

        return $sql;
    }

    private static function q(string $identifier): string
    {
        return '`' . str_replace('`', '', $identifier) . '`';
    }
}
