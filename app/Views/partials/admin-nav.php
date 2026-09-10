<?php
/** The row of tabs across the top of every admin screen */
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$current = $currentPath ?? '/admin';

$tabs = [
    ['/admin',          'grid',  'Overview'],
    ['/admin/users',    'users', 'Users'],
    ['/admin/links',    'card',  'Access links'],
    ['/admin/activity', 'clock', 'Plan activity'],
];
?>
<div class="section__head">
    <h1 class="section__title" style="font-size:22px"><?= H::e($adminHeading ?? 'Administration') ?></h1>

    <div class="section__tools">
        <?php foreach ($tabs as [$path, $icon, $label]): ?>
            <?php $active = $path === '/admin' ? $current === '/admin' : H::isActive($current, $path); ?>
            <a class="chip<?= $active ? ' chip--active' : '' ?>" href="<?= Url::to($path) ?>">
                <?= Icon::get($icon, 14) ?> <?= H::e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
