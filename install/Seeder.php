<?php
namespace Install;

use App\Core\Database;
use App\Services\Art;
use App\Services\Tiers;

/**
 * Fills the database with its starting content.
 *
 * The quantities match the targets in SRS sections 9 and 11 exactly:
 *
 *   36 maps            (12 spaces: 12 designs | 18 spaces: 12 | 24 spaces: 12)
 *   30 character sets  (starter 10, pro +10, publisher +10)
 *   15 move card sets  (starter 5,  pro +5,  publisher +5)
 *   30 hero cards      (starter 10, pro +10, publisher +10)
 *   15 base mission templates (starter 5, pro +5, publisher +5)
 *   50+ ready-made game templates (FR-17)
 *
 * These all ship with generated SVG placeholder artwork. To use real artwork,
 * drop files into uploads/library/... named after each item's code - see the
 * notes at the top of Library.php.
 */
class Seeder
{
    private static string $now = '';

    public static function run(bool $withDemoData = true): array
    {
        self::$now = date('Y-m-d H:i:s');
        $report = [];

        $report['library_items']     = self::seedLibrary();
        $report['mission_templates'] = self::seedMissionTemplates();
        $report['game_templates']    = self::seedGameTemplates();

        if ($withDemoData) {
            $report['community_posts']   = self::seedCommunity();
            $report['marketplace_items'] = self::seedMarketplace();
        }

        return $report;
    }

    // ---------------------------------------------------------------
    //  The content library
    // ---------------------------------------------------------------

    private static function seedLibrary(): int
    {
        if (Database::count('SELECT COUNT(*) FROM library_items') > 0) {
            return 0;
        }

        $themes = array_keys(Art::THEMES);   // 12 themes
        $n = 0;

        // --- 36 maps: 12 designs at each space count, split across the tiers ---
        $mapTierPlan = [
            12 => [Tiers::STARTER => 6, Tiers::PRO => 3, Tiers::PUBLISHER => 3],
            18 => [Tiers::STARTER => 6, Tiers::PRO => 3, Tiers::PUBLISHER => 3],
            24 => [Tiers::STARTER => 0, Tiers::PRO => 6, Tiers::PUBLISHER => 6],
        ];

        $mapNames = [
            'forest' => 'Woodland Trail',      'dino'   => 'Dinosaur Valley',
            'space'  => 'Deep Space Run',      'ocean'  => 'Coral Reef',
            'pirate' => 'Pirate Waters',       'magic'  => 'Wizard Realm',
            'castle' => 'Storybook Kingdom',   'desert' => 'Desert Canyon',
            'arctic' => 'Frozen North',        'candy'  => 'Candy Land',
            'robot'  => 'Robot City',          'farm'   => 'Happy Farm',
        ];

        foreach ($mapTierPlan as $cells => $plan) {
            $index = 0;
            foreach ($plan as $tier => $qty) {
                for ($i = 0; $i < $qty; $i++) {
                    $theme = $themes[$index % count($themes)];
                    $code  = 'map-' . $cells . '-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    Database::insert('library_items', [
                        'kind'       => 'map',
                        'tier'       => $tier,
                        'code'       => $code,
                        'name'       => ($mapNames[$theme] ?? ucfirst($theme)) . ' - ' . $cells . ' spaces',
                        'theme'      => $theme,
                        'cells'      => $cells,
                        'poses'      => null,
                        'card_count' => null,
                        'art_seed'   => $code,
                        'image_path' => null,
                        'meta'       => json_encode(['layout' => 'serpentine'], JSON_UNESCAPED_UNICODE),
                        'sort_order' => $index,
                        'is_active'  => 1,
                        'created_at' => self::$now,
                    ]);
                    $index++;
                    $n++;
                }
            }
        }

        // --- 30 character sets: pose count rises with the tier (3 / 5 / 8) ---
        $charNames = [
            'Junior Hero - Bunny', 'Junior Hero - Kitten', 'Junior Hero - Puppy', 'Quick Kai',
            'Curious Cora',       'Cheerful Theo',    'Steady Sana',     'Mighty Milo',
            'Bright Bella',       'Patient Pedro',    'Gentle Gia',      'Playful Pax',
            'Creative Cleo',      'Fearless Finn',    'Graceful Grace',  'Solid Sam',
            'Diligent Dara',      'Noble Nico',       'Thoughtful Thea', 'Free-spirited Fox',
            'Sharp Sky',          'Sturdy Stone',     'Wise Willow',     'Certain Cedar',
            'Kind Kira',          'Decisive Dex',     'Dreamy Delia',    'Roaming Rex',
            'Precious Pearl',     'Talented Tessa',
        ];

        $charTierPlan = [Tiers::STARTER => 10, Tiers::PRO => 10, Tiers::PUBLISHER => 10];
        $index = 0;
        foreach ($charTierPlan as $tier => $qty) {
            $poses = Tiers::get($tier)['character_poses'];
            for ($i = 0; $i < $qty; $i++) {
                $code = 'char-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                Database::insert('library_items', [
                    'kind'       => 'character',
                    'tier'       => $tier,
                    'code'       => $code,
                    'name'       => $charNames[$index] ?? ('Character ' . ($index + 1)),
                    'theme'      => $themes[$index % count($themes)],
                    'cells'      => null,
                    'poses'      => $poses,
                    'card_count' => null,
                    'art_seed'   => $code,
                    'image_path' => null,
                    'meta'       => json_encode(['poses' => $poses], JSON_UNESCAPED_UNICODE),
                    'sort_order' => $index,
                    'is_active'  => 1,
                    'created_at' => self::$now,
                ]);
                $index++;
                $n++;
            }
        }

        // --- 15 move card designs ---
        //
        // Named after the artwork, not the theme wheel. These are plain card
        // frames in fifteen colours; calling one "Forest move cards" because it
        // happened to land on that step of the loop described nothing, and the
        // names repeated once the loop went round twice.
        //
        // Fifteen is the whole set. There were five more numbered placeholders
        // here, but no artwork was ever drawn for them, so they only ever showed
        // the buyer an empty card style called "Card style 16".
        $moveNames = [
            'Peach', 'Cocoa', 'Lime', 'Sky', 'Vanilla',
            'Butter', 'Ice', 'Lavender', 'Rose', 'Honey',
            'Snow', 'Sage', 'Mist', 'Lilac', 'Meadow',
        ];

        $moveTierPlan = [Tiers::STARTER => 5, Tiers::PRO => 5, Tiers::PUBLISHER => 5];
        $index = 0;
        foreach ($moveTierPlan as $tier => $qty) {
            for ($i = 0; $i < $qty; $i++) {
                $theme = $themes[$index % count($themes)];
                $code  = 'move-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                Database::insert('library_items', [
                    'kind'       => 'move',
                    'tier'       => $tier,
                    'code'       => $code,
                    'name'       => $moveNames[$index] ?? ('Card style ' . ($index + 1)),
                    'theme'      => $theme,
                    'cells'      => null,
                    'poses'      => null,
                    'card_count' => 8,
                    'art_seed'   => $code,
                    'image_path' => null,
                    'meta'       => json_encode(['cards' => 8], JSON_UNESCAPED_UNICODE),
                    'sort_order' => $index,
                    'is_active'  => 1,
                    'created_at' => self::$now,
                ]);
                $index++;
                $n++;
            }
        }

        // --- 30 winner hero cards ---
        $rewardNames = [
            'Explorer Badge',    'Gold Courage Cup',  'Bright Mind Award',
            'Persistence Medal', 'Creativity Star',   'Golden Key',
            'Wisdom Gem',        'Guardian Shield',   'Little Crown',
            'Treasure Map',      'Victory Flag',      'Kindness Heart',
        ];

        $rewardTierPlan = [Tiers::STARTER => 10, Tiers::PRO => 10, Tiers::PUBLISHER => 10];
        $index = 0;
        foreach ($rewardTierPlan as $tier => $qty) {
            for ($i = 0; $i < $qty; $i++) {
                $theme = $themes[$index % count($themes)];
                $code  = 'reward-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                Database::insert('library_items', [
                    'kind'       => 'reward',
                    'tier'       => $tier,
                    'code'       => $code,
                    'name'       => ($rewardNames[$index % count($rewardNames)]) . ' - ' . Art::themeLabel($theme),
                    'theme'      => $theme,
                    'cells'      => null,
                    'poses'      => null,
                    'card_count' => 1,
                    'art_seed'   => $code,
                    'image_path' => null,
                    'meta'       => null,
                    'sort_order' => $index,
                    'is_active'  => 1,
                    'created_at' => self::$now,
                ]);
                $index++;
                $n++;
            }
        }

        return $n;
    }

    // ---------------------------------------------------------------
    //  The 15 base mission templates (FR-35)
    // ---------------------------------------------------------------

    private static function seedMissionTemplates(): int
    {
        if (Database::count('SELECT COUNT(*) FROM mission_templates') > 0) {
            return 0;
        }

        $templates = [
            // --- STARTER: 5 templates ---
            [
                'code' => 'math-add', 'name' => 'Simple addition', 'subject' => 'math',
                'level' => 'beginner', 'tier' => Tiers::STARTER, 'sticker' => 'star',
                'pattern' => 'There are {a} rabbits in the meadow, and {b} more hop over to join them. How many rabbits are there now?',
                'answer'  => '{a+b} rabbits',
                'variables' => ['a' => ['min' => 2, 'max' => 12], 'b' => ['min' => 1, 'max' => 8]],
                'hint' => 'Count them all together.',
            ],
            [
                'code' => 'math-sub', 'name' => 'Simple subtraction', 'subject' => 'math',
                'level' => 'beginner', 'tier' => Tiers::STARTER, 'sticker' => 'leaf',
                'pattern' => 'There are {a} apples on the tree and {b} of them fall to the ground. How many are still on the tree?',
                'answer'  => '{a-b} apples',
                'variables' => ['a' => ['min' => 6, 'max' => 20], 'b' => ['min' => 1, 'max' => 5]],
                'hint' => 'Take the fallen ones away from the total.',
            ],
            [
                'code' => 'nature-animal', 'name' => 'Animal sounds', 'subject' => 'nature',
                'level' => 'beginner', 'tier' => Tiers::STARTER, 'sticker' => 'heart',
                'pattern' => 'What sound does a {animal} make? Do your best impression!',
                'answer'  => 'Any good impression counts',
                'variables' => ['animal' => ['list' => ['cat', 'dog', 'cow', 'rooster', 'duck', 'sheep', 'frog', 'pig', 'horse', 'goat']]],
                'hint' => 'If everyone laughs, you win.',
            ],
            [
                'code' => 'life-action', 'name' => 'Silly challenge', 'subject' => 'life',
                'level' => 'beginner', 'tier' => Tiers::STARTER, 'sticker' => 'sun',
                'pattern' => 'Try to {action} for {n} seconds. Manage it and you carry on!',
                'answer'  => 'Complete the action',
                'variables' => [
                    'action' => ['list' => ['hop on one foot', 'stand on one leg', 'clap as fast as you can', 'spin around', 'laugh out loud', 'pretend to be a cat']],
                    'n' => ['min' => 5, 'max' => 15, 'step' => 5],
                ],
                'hint' => 'Everyone else counts out loud.',
            ],
            [
                'code' => 'lit-letter', 'name' => 'Words that start with', 'subject' => 'literacy',
                'level' => 'beginner', 'tier' => Tiers::STARTER, 'sticker' => 'book',
                'pattern' => 'Name {n} words that begin with the letter "{letter}".',
                'answer'  => 'Any {n} valid words',
                'variables' => [
                    'n' => ['min' => 2, 'max' => 4],
                    'letter' => ['list' => ['B', 'C', 'H', 'M', 'N', 'T', 'L', 'S', 'D', 'G']],
                ],
                'hint' => 'Look around the room for ideas.',
            ],

            // --- PRO: 5 templates ---
            [
                'code' => 'math-mul', 'name' => 'Multiplication', 'subject' => 'math',
                'level' => 'standard', 'tier' => Tiers::PRO, 'sticker' => 'gem',
                'pattern' => 'There are {a} baskets and each one holds {b} eggs. How many eggs are there altogether?',
                'answer'  => '{a*b} eggs',
                'variables' => ['a' => ['min' => 2, 'max' => 9], 'b' => ['min' => 2, 'max' => 9]],
                'hint' => 'Multiply the baskets by the eggs in each.',
            ],
            [
                'code' => 'en-word', 'name' => 'Vocabulary', 'subject' => 'english',
                'level' => 'standard', 'tier' => Tiers::PRO, 'sticker' => 'bulb',
                'pattern' => 'What does the word "{word}" mean? Explain it in your own words.',
                'answer'  => 'A sensible explanation counts',
                'variables' => ['word' => ['list' => ['forest', 'river', 'mountain', 'treasure', 'bridge', 'castle', 'dragon', 'compass', 'island', 'journey', 'courage', 'friendship']]],
                'hint' => 'Think about where you have heard it before.',
            ],
            [
                'code' => 'science-why', 'name' => 'Why does it happen', 'subject' => 'science',
                'level' => 'standard', 'tier' => Tiers::PRO, 'sticker' => 'drop',
                'pattern' => 'Why {phenomenon}?',
                'answer'  => 'Any reasonable explanation counts',
                'variables' => ['phenomenon' => ['list' => [
                    'does it rain', 'do rainbows appear after rain', 'does ice melt when you leave it out',
                    'do leaves change colour in autumn', 'can we see stars at night',
                    'do balloons float when filled with helium', 'are shadows longer in the afternoon',
                ]]],
                'hint' => 'No textbook needed - just say what you think.',
            ],
            [
                'code' => 'logic-seq', 'name' => 'Number sequence', 'subject' => 'logic',
                'level' => 'standard', 'tier' => Tiers::PRO, 'sticker' => 'key',
                'pattern' => 'What number comes next in this sequence: {a}, {a+d}, {a+d+d}, ... ?',
                'answer'  => '{a+d+d+d}',
                'variables' => ['a' => ['min' => 1, 'max' => 10], 'd' => ['min' => 2, 'max' => 6]],
                'hint' => 'Work out how much each number grows by.',
            ],
            [
                'code' => 'lit-opposite', 'name' => 'Opposites', 'subject' => 'literacy',
                'level' => 'standard', 'tier' => Tiers::PRO, 'sticker' => 'music',
                'pattern' => 'What is the opposite of "{word}"?',
                'answer'  => 'The correct opposite',
                'variables' => ['word' => ['list' => ['tall', 'fast', 'hot', 'bright', 'happy', 'big', 'thick', 'new', 'near', 'heavy', 'clean', 'dry']]],
                'hint' => 'Think of the other end of the scale.',
            ],

            // --- PUBLISHER: 5 templates ---
            [
                'code' => 'math-div', 'name' => 'Sharing equally', 'subject' => 'math',
                'level' => 'advanced', 'tier' => Tiers::PUBLISHER, 'sticker' => 'shield',
                'pattern' => 'Share {a} biscuits equally between {b} friends. How many does each friend get?',
                'answer'  => '{a/b} biscuits each',
                'variables' => ['a' => ['min' => 12, 'max' => 48, 'step' => 6], 'b' => ['min' => 2, 'max' => 6]],
                'hint' => 'Everyone gets exactly the same.',
            ],
            [
                'code' => 'math-word2', 'name' => 'Two-step word problem', 'subject' => 'math',
                'level' => 'advanced', 'tier' => Tiers::PUBLISHER, 'sticker' => 'trophy',
                'pattern' => 'You have {a} coins. You buy {b} items that cost {c} coins each. How many coins are left?',
                'answer'  => '{a-b*c} coins',
                'variables' => ['a' => ['min' => 50, 'max' => 100, 'step' => 10], 'b' => ['min' => 2, 'max' => 4], 'c' => ['min' => 5, 'max' => 12]],
                'hint' => 'Work out the cost first, then subtract.',
            ],
            [
                'code' => 'geo-continent', 'name' => 'Places on the map', 'subject' => 'geography',
                'level' => 'advanced', 'tier' => Tiers::PUBLISHER, 'sticker' => 'flag',
                'pattern' => 'Which continent is "{place}" in?',
                'answer'  => 'The correct continent',
                'variables' => ['place' => ['list' => ['Egypt', 'Brazil', 'Japan', 'Italy', 'Kenya', 'Canada', 'India', 'Norway', 'Peru', 'Vietnam']]],
                'hint' => 'Picture a world map in your head.',
            ],
            [
                'code' => 'logic-riddle', 'name' => 'Days puzzle', 'subject' => 'logic',
                'level' => 'advanced', 'tier' => Tiers::PUBLISHER, 'sticker' => 'moon',
                'pattern' => 'If today is day {a} of the week, what day will it be in {b} days?',
                'answer'  => 'Count {b} days on from day {a}',
                'variables' => ['a' => ['min' => 2, 'max' => 7], 'b' => ['min' => 2, 'max' => 9]],
                'hint' => 'Count on your fingers.',
            ],
            [
                'code' => 'life-team', 'name' => 'Working together', 'subject' => 'life',
                'level' => 'advanced', 'tier' => Tiers::PUBLISHER, 'sticker' => 'rocket',
                'pattern' => 'What would you do if {situation}? Tell everyone one way to handle it.',
                'answer'  => 'Any thoughtful answer counts',
                'variables' => ['situation' => ['list' => [
                    'a friend fell over in the playground',
                    'your group could not agree on anything',
                    'you spilled water all over the table',
                    'you saw someone sitting on their own',
                    'you forgot to bring your homework',
                    'you found something that belongs to someone else',
                ]]],
                'hint' => 'There is no single right answer - just be kind.',
            ],
        ];

        foreach ($templates as $t) {
            Database::insert('mission_templates', [
                'code'       => $t['code'],
                'name'       => $t['name'],
                'subject'    => $t['subject'],
                'level'      => $t['level'],
                'tier'       => $t['tier'],
                'sticker'    => $t['sticker'],
                'pattern'    => $t['pattern'],
                'answer'     => $t['answer'],
                'variables'  => json_encode($t['variables'], JSON_UNESCAPED_UNICODE),
                'hint'       => $t['hint'] ?? null,
                'is_active'  => 1,
                'created_at' => self::$now,
            ]);
        }

        return count($templates);
    }

    // ---------------------------------------------------------------
    //  Ready-made game templates (FR-17: "over 50")
    // ---------------------------------------------------------------

    private static function seedGameTemplates(): int
    {
        if (Database::count('SELECT COUNT(*) FROM game_templates') > 0) {
            return 0;
        }

        $themes = array_keys(Art::THEMES);
        $levels = [
            'beginner' => ['cells' => 12, 'age' => [4, 6],  'players' => [2, 4]],
            'standard' => ['cells' => 18, 'age' => [6, 9],  'players' => [2, 4]],
            'advanced' => ['cells' => 24, 'age' => [8, 12], 'players' => [3, 6]],
        ];

        $subjectSets = [
            'math,logic', 'literacy,life', 'english,nature',
            'science,nature', 'math,science', 'logic,life',
            'literacy,english', 'geography,nature',
        ];

        $titles = [
            'forest' => 'Secret of the Woods',    'dino'   => 'Dinosaur Rescue Mission',
            'space'  => 'Space Explorer Quest',   'ocean'  => 'Treasure Beneath the Waves',
            'pirate' => 'Pirate Voyage',          'magic'  => 'Magic Academy Challenge',
            'castle' => 'The Golden Bell',        'desert' => 'The Hidden Oasis',
            'arctic' => 'Arctic Adventure',       'candy'  => 'Candy Land Quest',
            'robot'  => 'Robot Factory Rescue',   'farm'   => 'A Day on the Farm',
        ];

        $n = 0;
        $i = 0;

        // 12 themes x 3 levels = 36, plus 18 more variants = 54 templates (over 50 - FR-17)
        foreach ([1, 2] as $round) {
            foreach ($themes as $theme) {
                foreach ($levels as $level => $cfg) {
                    if ($round === 2 && $i % 2 === 0) {
                        $i++;
                        continue;   // the second pass only takes half, landing on 54
                    }

                    $tier = $level === 'advanced' ? Tiers::PRO : Tiers::STARTER;
                    if ($round === 2) {
                        $tier = Tiers::PUBLISHER;
                    }

                    $code = 'tpl-' . $theme . '-' . $level . ($round === 2 ? '-b' : '');
                    $suffix = $round === 2 ? ' (extended)' : '';

                    Database::insert('game_templates', [
                        'code'        => $code,
                        'name'        => ($titles[$theme] ?? ucfirst($theme)) . $suffix,
                        'description' => 'A ' . $cfg['cells'] . '-space map with ' . ($cfg['cells'] * 5)
                                       . ' mission cards, suited to ages ' . $cfg['age'][0] . '-' . $cfg['age'][1] . '.',
                        'theme'       => $theme,
                        'difficulty'  => $level,
                        'subjects'    => $subjectSets[$i % count($subjectSets)],
                        'tier'        => $tier,
                        'age_min'     => $cfg['age'][0],
                        'age_max'     => $cfg['age'][1],
                        'players_min' => $cfg['players'][0],
                        'players_max' => $cfg['players'][1],
                        'art_seed'    => $code,
                        'image_path'  => null,
                        'uses_count'  => random_int(3, 240),
                        'is_active'   => 1,
                        'created_at'  => self::$now,
                    ]);
                    $i++;
                    $n++;
                }
            }
        }

        return $n;
    }

    // ---------------------------------------------------------------
    //  Sample projects for the first account
    // ---------------------------------------------------------------

    /**
     * Creates a few sample projects so the dashboard is not empty straight after
     * installing. They can be deleted at any time.
     */
    public static function demoProjects(int $userId, string $plan): int
    {
        self::$now = self::$now ?: date('Y-m-d H:i:s');

        $samples = [
            ['Dinosaur Rescue Mission', 'dino',   'standard', 'in_progress', 'math,nature',      2],
            ['Space Explorer Quest',    'space',  'standard', 'ready',       'science,logic',    4],
            ['Treasure Island Voyage',  'pirate', 'advanced', 'draft',       'math,english',     6],
            ['Magic Academy Challenge', 'magic',  'beginner', 'in_progress', 'literacy,life',   10],
        ];

        $n = 0;
        foreach ($samples as $i => [$title, $theme, $level, $status, $subjects, $daysAgo]) {
            $cfg = \App\Services\Difficulty::get($level);

            // Pick a map with the right space count that this plan can use
            $map = Database::first(
                'SELECT * FROM library_items WHERE kind = ? AND cells = ? AND tier IN ('
                . implode(',', array_fill(0, count(Tiers::unlockedTiers($plan)), '?')) . ')'
                . ' AND theme = ? ORDER BY id ASC LIMIT 1',
                array_merge(['map', $cfg['cells']], Tiers::unlockedTiers($plan), [$theme])
            );
            if (!$map) {
                $map = Database::first(
                    'SELECT * FROM library_items WHERE kind = ? AND cells = ? ORDER BY id ASC LIMIT 1',
                    ['map', $cfg['cells']]
                );
            }

            $char = Database::first(
                'SELECT * FROM library_items WHERE kind = ? ORDER BY id ASC LIMIT 1 OFFSET ?',
                ['character', $i]
            );

            $seed = \App\Services\PromptGenerator::storySeed([
                'title' => $title,
                'theme' => $theme,
                'cells' => (int) $cfg['cells'],
            ]);
            $ts = date('Y-m-d H:i:s', time() - $daysAgo * 86400);

            $projectId = Database::insert('projects', [
                'user_id'           => $userId,
                'title'             => $title,
                'slug'              => \App\Core\Helper::slug($title) . '-' . substr(md5($title . $i), 0, 4),
                'status'            => $status,
                'difficulty'        => $level,
                'theme'             => $theme,
                'subjects'          => $subjects,
                'question_count'    => (int) $cfg['mission_cards'],
                'cells'             => (int) $cfg['cells'],
                'players_min'       => 2,
                'players_max'       => $level === 'advanced' ? 6 : 4,
                'age_min'           => (int) $cfg['age_min'],
                'age_max'           => (int) $cfg['age_max'],
                'map_item_id'       => $map['id'] ?? null,
                'character_item_id' => $char['id'] ?? null,
                'move_item_id'      => null,
                'reward_item_id'    => null,
                'background_id'     => null,
                'cover_seed'        => 'demo-' . $i,
                'story'             => $seed['story'],
                'how_to_play'       => $seed['how_to_play'],
                'hero_name'         => null,
                'wizard_step'       => $status === 'draft' ? 2 : 5,
                'settings'          => null,
                'created_at'        => $ts,
                'updated_at'        => $ts,
            ]);

            // Generate mission cards for anything past the draft stage
            if ($status !== 'draft') {
                $cards = \App\Services\MissionMatcher::generate(
                    explode(',', $subjects),
                    $level,
                    $plan,
                    (int) $cfg['cells'],
                    (int) $cfg['mission_cards'],
                    crc32($title)
                );
                \App\Services\MissionMatcher::saveForProject($projectId, $cards);
            }

            $n++;
        }

        return $n;
    }

    // ---------------------------------------------------------------
    //  Community and marketplace content, so those pages are not blank
    // ---------------------------------------------------------------

    private static function seedCommunity(): int
    {
        if (Database::count('SELECT COUNT(*) FROM community_posts') > 0) {
            return 0;
        }

        $posts = [
            ['Ms. Lane - Sunflower Preschool', 'Secret of the Woods',   'forest', 'beginner', 'Made this for my five-year-olds. They loved swapping the mission cards.'],
            ['Michael, parent',                'Space Explorer Quest',  'space',  'standard', 'Printed at A3 and laminated it - a year later it is still going strong.'],
            ['Hannah, teacher',                'Treasure Beneath the Waves', 'ocean', 'standard', 'Added extra vocabulary questions for my Year 3 class.'],
            ['Mr. Dean - Primary school',      'Dinosaur Rescue Mission', 'dino', 'advanced', 'Used it in form time. 24 spaces runs long but they stayed hooked.'],
            ['Ms. May - Learning centre',      'Magic Academy Challenge', 'magic', 'standard', 'Rewrote every mission card in Spanish for my group.'],
            ['Tara, parent',                   'A Day on the Farm',     'farm',   'beginner', 'Even my four-year-old can play if an adult reads the questions.'],
            ['Nathan - print shop',            'Pirate Voyage',         'pirate', 'advanced', 'Selling well on Etsy. Customers love the winner card.'],
            ['Ms. Yen - Preschool',            'Candy Land Quest',      'candy',  'beginner', 'The colours grab them instantly - they all want the hero card.'],
            ['Mr. Long - Primary school',      'Robot Factory Rescue',  'robot',  'advanced', 'Added science questions and my Year 5s were completely absorbed.'],
            ['Nicole, parent',                 'Arctic Adventure',      'arctic', 'standard', 'Our weekend family adventure, sorted.'],
            ['Ms. Vann - Teacher',             'The Golden Bell',       'castle', 'beginner', 'Great for revising reading with Year 1.'],
            ['Ben - Creator',                  'The Hidden Oasis',      'desert', 'advanced', 'The 24-space map is perfect for a group of six.'],
        ];

        foreach ($posts as $i => $p) {
            Database::insert('community_posts', [
                'user_id'     => null,
                'project_id'  => null,
                'author_name' => $p[0],
                'title'       => $p[1],
                'caption'     => $p[4],
                'theme'       => $p[2],
                'difficulty'  => $p[3],
                'art_seed'    => 'community-' . $i,
                'image_path'  => null,
                'likes'       => random_int(8, 320),
                'is_featured' => $i < 6 ? 1 : 0,
                'created_at'  => date('Y-m-d H:i:s', time() - $i * 86400 * 2),
            ]);
        }

        return count($posts);
    }

    private static function seedMarketplace(): int
    {
        if (Database::count('SELECT COUNT(*) FROM marketplace_items') > 0) {
            return 0;
        }

        $items = [
            ['GameCraft Studio',   'Six rainforest map designs',           'asset_pack', 'forest', 900,  48],
            ['GameCraft Studio',   'Space character set - 8 poses',        'asset_pack', 'space', 1200, 47],
            ['Blue Ocean Studio',  'Year 3 vocabulary mission card pack',  'bundle',     'ocean', 1500, 49],
            ['Lane Handmade',      'Complete "Secret of the Woods" game',  'template',   'forest', 700, 46],
            ['GameCraft Studio',   'Gold foil hero card designs',          'asset_pack', 'castle', 800, 50],
            ['Rainbow Studio',     'Three 24-space desert maps',           'asset_pack', 'desert', 1400, 44],
            ['Nathan Print',       'Pirate game - commercial licence',     'template',   'pirate', 1900, 48],
            ['GameCraft Studio',   'Sticker icon set for mission cards',   'asset_pack', 'magic',  500, 47],
            ['Blue Ocean Studio',  'Farmyard character pack',              'asset_pack', 'farm',  1100, 45],
            ['Rainbow Studio',     'Three preschool games in one bundle',  'bundle',     'candy', 2400, 49],
        ];

        foreach ($items as $i => $m) {
            Database::insert('marketplace_items', [
                'seller_name' => $m[0],
                'title'       => $m[1],
                'description' => 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.',
                'kind'        => $m[2],
                'theme'       => $m[3],
                'price_cents' => $m[4],
                'art_seed'    => 'market-' . $i,
                'image_path'  => null,
                'rating'      => $m[5],
                'sales'       => random_int(12, 480),
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s', time() - $i * 86400 * 3),
            ]);
        }

        return count($items);
    }
}
