<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$user = Auth::user();
?>
<header class="topbar">

    <div class="topbar__tagline">
        <?= Icon::get('sparkles', 16) ?>
        <span>Turn your ideas into unforgettable adventure games</span>
    </div>

    <a class="btn btn--icon btn--ghost" href="<?= Url::to('/exports') ?>" title="My exports" aria-label="My exports">
        <?= Icon::get('bell', 17) ?>
    </a>

    <div class="menu" data-menu>
        <button type="button" class="topbar__user" data-menu-trigger aria-haspopup="true" aria-expanded="false">
            <span class="avatar"><?= H::e(H::initials($user['name'] ?? '')) ?></span>
            <span>Hello, <?= H::e(explode(' ', trim((string) ($user['name'] ?? 'there')))[0]) ?>!</span>
            <?= Icon::get('chevron-down', 14) ?>
        </button>

        <div class="menu__list" role="menu" style="min-width:200px">
            <div style="padding:8px 10px 10px">
                <div class="bold" style="font-size:13px"><?= H::e($user['name'] ?? '') ?></div>
                <div class="small faint" style="word-break:break-all"><?= H::e($user['email'] ?? '') ?></div>
            </div>
            <div class="menu__sep"></div>
            <a class="menu__item" href="<?= Url::to('/settings') ?>" role="menuitem">
                <?= Icon::get('user', 16) ?> My profile
            </a>
            <a class="menu__item" href="<?= Url::to('/billing') ?>" role="menuitem">
                <?= Icon::get('card', 16) ?> Billing
            </a>
            <div class="menu__sep"></div>
            <form method="post" action="<?= Url::to('/logout') ?>">
                <?= Csrf::field() ?>
                <button type="submit" class="menu__item menu__item--danger" role="menuitem">
                    <?= Icon::get('logout', 16) ?> Sign out
                </button>
            </form>
        </div>
    </div>

</header>
