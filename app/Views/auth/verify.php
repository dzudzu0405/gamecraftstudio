<?php
/**
 * The one-time email confirmation, shown on a new account's first password
 * sign-in. Google accounts never see it.
 */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Art;
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

            <h1 class="auth__title">Check your email</h1>
            <p class="auth__sub">
                We sent a six-digit code to <b><?= H::e($email) ?></b>.
                Type it below and you are in - you will not be asked again.
            </p>

            <?php if ($hasPending): ?>
                <div class="notice notice--info">
                    <?= Icon::get('crown', 16) ?>
                    <span>Your purchase is waiting. The plan is applied as soon as this code checks out.</span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= Url::to('/verify') ?>" novalidate>
                <?= Csrf::field() ?>

                <div class="field">
                    <label class="label" for="code">Six-digit code</label>
                    <input class="input" type="text" id="code" name="code"
                           inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                           autocomplete="one-time-code" placeholder="000000"
                           style="font-size:24px;letter-spacing:.4em;text-align:center;font-weight:700">
                    <div class="field__hint">The code stops working after 15 minutes.</div>
                </div>

                <button class="btn btn--primary btn--lg btn--block" type="submit">Confirm and continue</button>
            </form>

            <div class="auth__divider"><span>no email?</span></div>

            <form method="post" action="<?= Url::to('/verify/resend') ?>">
                <?= Csrf::field() ?>
                <button class="btn btn--ghost btn--block" type="submit">
                    <?= Icon::get('refresh', 16) ?> Send a new code
                </button>
            </form>

            <p class="auth__foot">
                Look in your spam folder before asking for another - the first one is
                usually sitting there.
            </p>

            <div class="center mt-2">
                <form method="post" action="<?= Url::to('/logout') ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--sm btn--ghost">Sign in as somebody else</button>
                </form>
            </div>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?= Art::scene('forest', 'verify-art', 440, 300) ?>
            <h2 class="mt-3" style="font-size:22px">One quick check, once</h2>
            <p class="muted mt-1">
                Confirming your address is what keeps your plan tied to you, so a link
                that goes astray can never be used to take it.
            </p>
        </div>
    </div>

</div>
