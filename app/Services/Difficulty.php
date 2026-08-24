<?php
namespace App\Services;

/**
 * Difficulty presets and map configuration - per section 10 of the SRS (FR-33).
 *
 * Every game uses a FIXED 8 move cards + 1 winner hero card.
 * The number of spaces on the map and the number of mission cards scale
 * with the difficulty level:
 *
 *   BEGINNER   12-space map    60 mission cards
 *   STANDARD   18-space map    90 mission cards
 *   ADVANCED   24-space map   120 mission cards
 *
 * 60 / 12 = 90 / 18 = 120 / 24 = 5  ->  five cards to draw at each space.
 */
class Difficulty
{
    public const BEGINNER = 'beginner';
    public const STANDARD = 'standard';
    public const ADVANCED = 'advanced';

    /** Fixed for every game regardless of difficulty (SRS section 10) */
    public const MOVE_CARDS_PER_GAME = 8;
    public const HERO_CARDS_PER_GAME = 1;

    /** Mission cards stacked at each space */
    public const MISSIONS_PER_CELL = 5;

    public static function all(): array
    {
        return [
            self::BEGINNER => [
                'key'           => self::BEGINNER,
                'name'          => 'Beginner',
                'cells'         => 12,
                'mission_cards' => 60,
                'move_cards'    => self::MOVE_CARDS_PER_GAME,
                'hero_cards'    => self::HERO_CARDS_PER_GAME,
                'ages'          => '4-6',
                'age_min'       => 4,
                'age_max'       => 6,
                'play_minutes'  => '15-20',
                'color'         => '#3FA796',
                'description'   => '12-space map and a short round, good for children just starting out.',
            ],
            self::STANDARD => [
                'key'           => self::STANDARD,
                'name'          => 'Standard',
                'cells'         => 18,
                'mission_cards' => 90,
                'move_cards'    => self::MOVE_CARDS_PER_GAME,
                'hero_cards'    => self::HERO_CARDS_PER_GAME,
                'ages'          => '6-9',
                'age_min'       => 6,
                'age_max'       => 9,
                'play_minutes'  => '25-35',
                'color'         => '#6C4BD6',
                'description'   => '18-space map of moderate length - the most widely used option.',
            ],
            self::ADVANCED => [
                'key'           => self::ADVANCED,
                'name'          => 'Advanced',
                'cells'         => 24,
                'mission_cards' => 120,
                'move_cards'    => self::MOVE_CARDS_PER_GAME,
                'hero_cards'    => self::HERO_CARDS_PER_GAME,
                'ages'          => '8-12',
                'age_min'       => 8,
                'age_max'       => 12,
                'play_minutes'  => '40-60',
                'color'         => '#E08A2E',
                'description'   => '24-space map with plenty of missions, suited to larger groups or older children.',
            ],
        ];
    }

    public static function get(?string $key): array
    {
        $all = self::all();
        return $all[$key ?? ''] ?? $all[self::STANDARD];
    }

    public static function exists(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /** Mission spaces on the map: 12 / 18 / 24 */
    public static function cells(?string $key): int
    {
        return (int) self::get($key)['cells'];
    }

    /** Total mission cards to generate: 60 / 90 / 120 */
    public static function missionCount(?string $key): int
    {
        return (int) self::get($key)['mission_cards'];
    }

    public static function name(?string $key): string
    {
        return self::get($key)['name'];
    }

    public static function color(?string $key): string
    {
        return self::get($key)['color'];
    }

    /** Work the difficulty back out from a space count */
    public static function fromCells(int $cells): string
    {
        foreach (self::all() as $key => $cfg) {
            if ((int) $cfg['cells'] === $cells) {
                return $key;
            }
        }
        return self::STANDARD;
    }

    /**
     * Rough page count for a game, shown before exporting.
     * See PrintBundle for the exact page order (FR-27).
     */
    public static function estimatedPages(?string $key, int $cardsPerSheet = 9): int
    {
        $cfg = self::get($key);
        $map     = 1;
        $story   = 1;
        $howto   = 1;
        $move    = (int) ceil($cfg['move_cards'] / $cardsPerSheet);
        $mission = (int) ceil($cfg['mission_cards'] / $cardsPerSheet);
        $hero    = 1;
        $tokens  = 1;
        return $map + $story + $howto + $move + $mission + $hero + $tokens;
    }
}
