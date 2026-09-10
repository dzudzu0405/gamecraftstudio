<?php
/**
 * Left-hand navigation (FR-01, FR-02, FR-03).
 * The open item gets a pale amber background, matching the reference dashboard.
 */
use App\Core\Auth;
use App\Core\Config;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Tiers;

$current = $currentPath ?? '/';
$plan    = Tiers::get(Auth::plan());

$groups = [
    [
        'label' => null,
        'items' => [
            ['/',           'home',     'Dashboard'],
            ['/projects',   'folder',   'My Projects'],
            ['/create',     'wand',     'Create New Game'],
        ],
    ],
    [
        'label' => 'Library',
        'items' => [
            ['/templates',  'template', 'Templates'],
            ['/library',    'book',     'Game Library'],
            ['/assets',     'image',    'Asset Library'],
            ['/exports',    'download', 'My Exports'],
        ],
    ],
    [
        'label' => 'Discover',
        'items' => [
            ['/marketplace', 'cart',    'Marketplace'],
            ['/community',   'users',   'Community'],
        ],
    ],
    [
        'label' => 'Account',
        'items' => [
            ['/billing',     'card',     'Billing'],
            ['/settings',    'settings', 'Settings'],
        ],
    ],
];

// Administration is only in the menu for administrators. Everyone else never
// sees it, and the routes refuse them anyway (Auth::requireAdmin).
if (Auth::isAdmin()) {
    $groups[] = [
        'label' => 'Administration',
        'items' => [
            ['/admin',          'grid',  'Overview', true],
            ['/admin/users',    'users', 'Users'],
            ['/admin/links',    'card',  'Access links'],
            ['/admin/activity', 'clock', 'Plan activity'],
        ],
    ];
}

// Discover holds Marketplace and Community. Both are still sample content with
// no way to buy or post, so the group stays hidden unless config.php asks for it.
if (!Config::get('discover_enabled', false)) {
    $groups = array_values(array_filter(
        $groups,
        static fn (array $group): bool => $group['label'] !== 'Discover'
    ));
}
?>
<aside class="sidebar">

    <a class="sidebar__brand" href="<?= Url::to('/') ?>" style="text-decoration:none;color:inherit">
        <span class="sidebar__logo">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9Z" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="m12 12 8-4.5M12 12v9M12 12 4 7.5" stroke="#fff" stroke-width="1.7" stroke-linejoin="round" opacity=".75"/>
            </svg>
        </span>
        <span>
            <span class="sidebar__name">GameCraft</span><br>
            <span class="sidebar__sub">STUDIO</span>
        </span>
    </a>

    <nav class="sidebar__nav" aria-label="Main navigation">
        <?php foreach ($groups as $group): ?>
            <div class="nav-group">
                <?php if ($group['label']): ?>
                    <div class="nav-group__label"><?= H::e($group['label']) ?></div>
                <?php endif; ?>

                <?php foreach ($group['items'] as $item): ?>
                    <?php
                    [$path, $icon, $label] = $item;

                    /*
                     * A fourth element asks for an exact match. Administration
                     * needs it: every one of its pages sits under /admin, so
                     * without this the Overview item would light up on all of
                     * them alongside the page actually open.
                     */
                    $active = ($item[3] ?? false)
                        ? $current === $path
                        : H::isActive($current, $path);
                    ?>
                    <a class="nav-item<?= $active ? ' nav-item--active' : '' ?>"
                       href="<?= Url::to($path) ?>"
                       <?= $active ? 'aria-current="page"' : '' ?>
                       title="<?= H::e($label) ?>">
                        <?= Icon::get($icon, 18) ?>
                        <span><?= H::e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- FR-03: current plan and a link to manage it -->
    <div class="sidebar__plan">
        <div class="plan-card">
            <div class="plan-card__row">
                <?= Icon::get('crown', 16) ?>
                <span class="plan-card__name"><?= H::e($plan['name']) ?> Plan</span>
            </div>
            <div class="plan-card__desc">
                <?= (int) $plan['maps_total'] ?> maps &middot; <?= (int) $plan['character_sets'] ?> character sets
            </div>
            <a class="btn btn--ghost btn--sm btn--block" href="<?= Url::to('/billing') ?>">Manage Plan</a>
        </div>
    </div>

</aside>
