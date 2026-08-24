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
                'move_sets'       => 5,
                'move_per_set'    => 8,
                'mission_sets'    => 5,
                'reward_cards'    => 10,
                'difficulties'    => ['beginner', 'standard'],
                'projects_limit'  => 5,
                'perks' => [
                    'Map background prompts, written for you',
                    'Upload your background and compose the finished map',
                    'Ready-written story and rules',
                    '8 move cards + 1 hero card per game',
                    'Multi-colour player tokens',
                    'Print-ready export (PDF / PNG)',
                ],
                'locked' => [
                    '24-space maps (Advanced level)',
                    'Selling on Amazon / Etsy',
                ],
            ],

            self::PRO => [
                'key'         => self::PRO,
                'name'        => 'Pro',
                'price'       => 27,
                'price_label' => '$27',
                'tagline'     => 'Everything in Starter, plus the Advanced difficulty level',
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
                    'Everything in the Starter tier',
                    'Unlocks 24-space maps - the Advanced level',
                    'Character sets with 5 poses each',
                    'Unlimited projects',
                    'Priority support',
                ],
                'locked' => [
                    'Selling on Amazon / Etsy',
                ],
            ],

            self::PUBLISHER => [
                'key'         => self::PUBLISHER,
                'name'        => 'Publisher',
                'price'       => 47,
                'price_label' => '$47',
                'tagline'     => 'Everything in Pro, plus the right to sell what you make',
                'color'       => '#E08A2E',
                'badge'       => 'For sellers',
                'popular'     => false,
                'maps'            => ['12' => 12, '18' => 12, '24' => 12],
                'maps_total'      => 36,
                'character_sets'  => 30,
                'character_poses' => 8,
                'move_sets'       => 20,
                'move_per_set'    => 8,
                'mission_sets'    => 15,
                'reward_cards'    => 30,
                'difficulties'    => ['beginner', 'standard', 'advanced'],
                'projects_limit'  => 0,
                'perks' => [
                    'Everything in the Pro tier',
                    'Full library: 36 maps, 30 character sets (8 poses each)',
                    'Export product listings for Amazon and Etsy',
                    'Commercial licence for printed products',
                    'Priority support',
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
