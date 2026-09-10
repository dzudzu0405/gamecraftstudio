<?php
/**
 * What a WarriorPlus buyer sees when they open their delivery link while
 * signed out. It names the plan first - they have just paid for it and want to
 * see it - and only then asks them to sign in.
 */
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Art;
use App\Services\Tiers;
?>
<div class="auth">

    <div class="auth__panel">
        <div class="auth__box">

            <div class="auth__brand">
                <span class="sidebar__logo">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9Z" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m12 12 8-4.5M12 12v9M12 12 4 7.5" stroke="#fff" stroke-width="1.7" stroke-linejoin="round" opacity=".75"/>
                    </svg>
                </span>
                <div>
                    <div style="font-weight:800;font-size:16px;line-height:1.1">GameCraft</div>
                    <div class="sidebar__sub" style="display:block">STUDIO</div>
                </div>
            </div>

            <?php if ($error): ?>

                <h1 class="auth__title">We could not open that link</h1>
                <div class="notice notice--error mb-2"><?= H::e($error) ?></div>

                <p class="muted small mb-2">
                    If the address is right and this keeps happening, reply to your WarriorPlus
                    receipt with your transaction ID and we will put it right by hand.
                </p>

                <a class="btn btn--ghost btn--lg btn--block" href="<?= Url::to('/login') ?>">Go to sign in</a>

            <?php else: ?>

                <span class="badge badge--published mb-1" style="background:<?= H::e($plan['color']) ?>1A;color:<?= H::e($plan['color']) ?>">
                    <?= Icon::get('crown', 14) ?> <?= H::e($plan['name']) ?> plan
                </span>

                <h1 class="auth__title mt-1">Your <?= H::e($plan['name']) ?> access is ready</h1>
                <p class="auth__sub"><?= H::e($plan['tagline']) ?></p>

                <div class="card mb-2">
                    <div class="card__body">
                        <div class="small bold mb-1">What this unlocks</div>
                        <ul class="perks">
                            <?php foreach (array_slice(Tiers::perks($plan['key']), 0, 6) as $perk): ?>
                                <li><?= Icon::get('check', 14) ?> <?= H::e($perk) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <p class="small muted mb-2">
                    Sign in and it is applied to your account straight away. Nothing to enter,
                    nothing to copy - we have kept this link for you until you are back.
                </p>

                <?php if ($googleOn): ?>
                    <a class="btn btn--primary btn--lg btn--block" href="<?= Url::to('/auth/google') ?>">
                        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                            <path fill="#fff" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.49h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.7-1.57 2.68-3.88 2.68-6.63Z" opacity=".9"/>
                            <path fill="#fff" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.8.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18Z" opacity=".75"/>
                            <path fill="#fff" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.95H.96a9 9 0 0 0 0 8.1l3.01-2.33Z" opacity=".6"/>
                            <path fill="#fff" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58Z"/>
                        </svg>
                        Continue with Google
                    </a>
                    <div class="auth__divider"><span>or</span></div>
                <?php endif; ?>

                <a class="btn <?= $googleOn ? 'btn--ghost' : 'btn--primary' ?> btn--lg btn--block"
                   href="<?= Url::to('/register') ?>">
                    Create an account with email
                </a>

                <p class="auth__foot">
                    Bought before, or already have an account?
                    <a href="<?= Url::to('/login') ?>" class="bold">Sign in</a>
                </p>

            <?php endif; ?>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?= Art::scene('magic', 'access-art', 440, 300) ?>
            <h2 class="mt-3" style="font-size:22px">Thanks for your purchase</h2>
            <p class="muted mt-1">
                Pick a map, add a background you made yourself, and GameCraft composes
                the board, the cards and the rules into one print-ready file.
            </p>
        </div>
    </div>

</div>
