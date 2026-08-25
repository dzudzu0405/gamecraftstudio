<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Art;

$errors = Flash::errors();
Flash::clearErrors();
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

            <h1 class="auth__title">Forgotten your password?</h1>
            <p class="auth__sub">
                Enter the email address on your account and we will send you a link to choose a new password.
            </p>

            <?php if (!$mailWorking): ?>
                <div class="notice notice--warning">
                    <?= Icon::get('alert', 17) ?>
                    <span>
                        Email has not been set up on this site yet, so reset links cannot be sent.
                        Please contact the site owner.
                    </span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= Url::to('/forgot') ?>" novalidate>
                <?= Csrf::field() ?>

                <div class="field">
                    <label class="label" for="email">Email</label>
                    <input class="input <?= isset($errors['email']) ? 'input--error' : '' ?>"
                           type="email" id="email" name="email" required autofocus autocomplete="email"
                           value="<?= H::e(Flash::old('email')) ?>" placeholder="you@example.com">
                    <?php if (isset($errors['email'])): ?>
                        <div class="field__error"><?= H::e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn--primary btn--lg btn--block <?= $mailWorking ? '' : 'is-disabled' ?>" type="submit">
                    Send the reset link
                </button>
            </form>

            <p class="auth__foot">
                Remembered it after all?
                <a href="<?= Url::to('/login') ?>" class="bold">Back to sign in</a>
            </p>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?= Art::scene('castle', 'forgot-art', 440, 300) ?>
            <h2 class="mt-3" style="font-size:22px">Back in a moment</h2>
            <p class="muted mt-1">
                The link works once and expires after an hour, so nobody else can use it.
            </p>
        </div>
    </div>

</div>
