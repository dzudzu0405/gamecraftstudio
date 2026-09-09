<?php
namespace App\Services;

/**
 * Product tiers and library entitlements - per section 9 of the SRS (FR-28, FR-29, FR-32).
 *
 * Each tier inherits everything from the tier below it:
 *   starter  ->  pro  ->  publisher
 */
class Tiers
{
    public const STARTER   = 'starter';
    public const PRO       = 'pro';
    public const PUBLISHER = 'publisher';

    /** Lowest to highest, used for permission comparisons */
    public const ORDER = [self::STARTER, self::PRO, self::PUBLISHER];

    public static function all(): array
    {
        return [
            self::STARTER => [
                'key'         => self::STARTER,
                'name'        => 'Starter',
                'price'       => 17,
                'price_label' => '$17',
                'tagline'     => 'Your first toolkit for making printable games for kids',
                'color'       => '#3FA796',
                'badge'       => 'Getting started',
                'popular'     => false,
                // Content unlocked at this tier (SRS section 9)
                'maps'            => ['12' => 6, '18' => 6, '24' => 0],
                'maps_total'      => 12,
                'character_sets'  => 10,
                'character_poses' => 3,
                'move_sets'       => 0,
                'move_per_set'    => 8,
                'mission_sets'    => 5,
                'reward_cards'    => 10,
                'difficulties'    => ['beginner', 'standard'],
                'projects_limit'  => 0, // 0 = unlimited
                /*
                  * Keyed, so a higher plan can override the line it supersedes
                  * and inherit every line it does not mention. See perks().
                  */
                'perks' => [
                    'maps'       => '12 maps: 6 with 12 missions, 6 with 18',
                    'characters' => '10 character sets, 3 poses each',
                    'moves'      => 'The paper die to cut out and fold',
                    'missions'   => '5 mission card designs',
                    'yourart'    => 'Your character on the mission and hero cards',
                    'winner'     => '5 winner card designs',
                    'rules'      => 'Rules written for you, in your game\'s language',
                    'story'      => 'A prompt that writes the story for you',
                    'export'     => 'Print-ready export (PDF / PNG)',
                    'projects'   => 'As many games as you like',
                ],
                'locked' => [
                    '24-space maps (Advanced level)',
                    'Move cards - Starter plays with the dice',
                    'Selling on Amazon / Etsy',
                ],
            ],

            self::PRO => [
                'key'         => self::PRO,
                'name'        => 'Pro',
                'price'       => 27,
                'price_label' => '$27',
                // Starter already has Standard; Advanced is what Pro unlocks
                'tagline'     => 'Everything in Starter, plus move cards and the Advanced level',
                'color'       => '#6C4BD6',
                'badge'       => 'Most popular',
                'popular'     => true,
                'maps'            => ['12' => 9, '18' => 9, '24' => 6],
                'maps_total'      => 24,
                'character_sets'  => 20,
                'character_poses' => 5,
                'move_sets'       => 10,
                'move_per_set'    => 8,
                'mission_sets'    => 10,
                'reward_cards'    => 20,
                'difficulties'    => ['beginner', 'standard', 'advanced'],
                'projects_limit'  => 0, // 0 = unlimited
                'perks' => [
                    'levels'     => 'Unlocks the Advanced level and 24-space maps',
                    'maps'       => '24 maps: 9 with 12 missions, 9 with 18, 6 with 24',
                    'characters' => '20 character sets, 5 poses each',
                    'moves'      => 'Move cards instead of the die, in 10 designs',
                    'missions'   => '10 mission card designs',
                    'yourart'    => 'Your character on the mission, move and hero cards',
                ],
                'locked' => [
                    'Selling on Amazon / Etsy',
                ],
            ],

            self::PUBLISHER => [
                'key'         => self::PUBLISHER,
                'name'        => 'Publisher',
                'price'       => 37,
                'price_label' => '$37',
                'tagline'     => 'The whole library, and the right to sell what you make',
                'color'       => '#E08A2E',
                'badge'       => 'For sellers',
                'popular'     => false,
                'maps'            => ['12' => 12, '18' => 12, '24' => 12],
                'maps_total'      => 36,
                'character_sets'  => 30,
                'character_poses' => 8,
                'move_sets'       => 15,
                'move_per_set'    => 8,
                'mission_sets'    => 15,
                'reward_cards'    => 30,
                'difficulties'    => ['beginner', 'standard', 'advanced'],
                'projects_limit'  => 0,
                'perks' => [
                    'maps'       => 'The whole library: 36 maps, 12 of each size',
                    'characters' => '30 character sets, all 8 poses',
                    'moves'      => '15 move card designs',
                    'missions'   => '15 mission card designs',
                    'listings'   => 'Export product listings for Amazon and Etsy',
                    'licence'    => 'Commercial licence for printed products',
                    'support'    => 'Priority support',
                ],
                'locked' => [],
            ],
        ];
    }

    public static function get(?string $key): array
    {
        $all = self::all();
        return $all[$key ?? ''] ?? $all[self::STARTER];
    }

    public static function exists(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /** Position in the permission order (0 = lowest) */
    public static function rank(?string $key): int
    {
        $i = array_search($key, self::ORDER, true);
        return $i === false ? 0 : (int) $i;
    }

    /** Does $plan include at least the rights of $required? (inheritance - FR-29) */
    public static function atLeast(?string $plan, string $required): bool
    {
        return self::rank($plan) >= self::rank($required);
    }

    /**
     * Content tiers this plan may use.
     * For example the pro plan => ['starter', 'pro'] (FR-29 inheritance)
     */
    public static function unlockedTiers(?string $plan): array
    {
        $rank = self::rank($plan);
        return array_slice(self::ORDER, 0, $rank + 1);
    }

    /**
     * The order the perks read in, whichever tier they came from.
     *
     * What the plan unlocks first, then how much of the library it holds,
     * then the things every plan has, then what only the top one adds. A
     * plain merge would have put "Unlocks the Advanced level" last, under
     * lines it is more important than.
     */
    private const PERK_ORDER = [
        'levels', 'maps', 'characters', 'moves', 'missions', 'winner',
        'yourart', 'rules', 'story', 'export', 'projects',
        'listings', 'licence', 'support',
    ];

    /**
     * Everything a plan includes, its own and everything below it.
     *
     * Each plan is the one under it plus what it adds, and the card has to
     * say so - a buyer comparing them should not find Pro listing fewer
     * things than Starter. A line is keyed by what it describes, so a higher
     * tier's "24 maps" replaces "12 maps" rather than sitting beside it, and
     * anything it says nothing about is inherited untouched.
     *
     * @return string[]
     */
    public static function perks(?string $plan): array
    {
        $merged = [];

        foreach (self::unlockedTiers($plan) as $tier) {
            $merged = array_merge($merged, self::get($tier)['perks']);
        }

        $out = [];

        foreach (self::PERK_ORDER as $key) {
            if (isset($merged[$key])) {
                $out[] = $merged[$key];
                unset($merged[$key]);
            }
        }

        // anything added later without a place in the order still gets shown
        return array_merge($out, array_values($merged));
    }

    /** Difficulty levels this plan unlocks (SRS section 10) */
    public static function difficulties(?string $plan): array
    {
        return self::get($plan)['difficulties'];
    }

    public static function allowsDifficulty(?string $plan, string $difficulty): bool
    {
        return in_array($difficulty, self::difficulties($plan), true);
    }

    /** Only the Publisher tier can export sales listings (FR-32) */
    public static function canPublishToMarketplace(?string $plan): bool
    {
        return self::atLeast($plan, self::PUBLISHER);
    }

    /** Maximum number of projects. 0 = unlimited */
    public static function projectLimit(?string $plan): int
    {
        return (int) self::get($plan)['projects_limit'];
    }

    /** Poses available per character set on this plan */
    public static function characterPoses(?string $plan): int
    {
        return (int) self::get($plan)['character_poses'];
    }

    /**
     * Mission card designs this plan may print.
     *
     * Separate from the move card sets on purpose: the Starter plan has no
     * move cards at all - it plays with the die - but every game has mission
     * cards, so every plan gets designs for them.
     */
    public static function missionSets(?string $plan): int
    {
        return (int) self::get($plan)['mission_sets'];
    }

    /** Map allowance broken down by space count */
    public static function mapQuota(?string $plan): array
    {
        return self::get($plan)['maps'];
    }

    public static function name(?string $key): string
    {
        return self::get($key)['name'];
    }

    public static function color(?string $key): string
    {
        return self::get($key)['color'];
    }

    /** The next tier up, or null if already at the top */
    public static function nextUp(?string $plan): ?string
    {
        $rank = self::rank($plan);
        return self::ORDER[$rank + 1] ?? null;
    }
}
