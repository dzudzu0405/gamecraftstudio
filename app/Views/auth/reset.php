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

            <h1 class="auth__title">Choose a new password</h1>
            <p class="auth__sub">Pick something you have not used here before.</p>

            <form method="post" action="<?= Url::to('/reset/' . $token) ?>" novalidate>
                <?= Csrf::field() ?>

                <div class="field">
                    <label class="label" for="password">New password <span class="label__hint">(8+ characters)</span></label>
                    <input class="input <?= isset($errors['password']) ? 'input--error' : '' ?>"
                           type="password" id="password" name="password" minlength="8"
                           required autofocus autocomplete="new-password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="field__error"><?= H::e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="password_confirmation">Confirm new password</label>
                    <input class="input <?= isset($errors['password_confirmation']) ? 'input--error' : '' ?>"
                           type="password" id="password_confirmation" name="password_confirmation"
                           required autocomplete="new-password">
                    <?php if (isset($errors['password_confirmation'])): ?>
                        <div class="field__error"><?= H::e($errors['password_confirmation']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="notice notice--info">
                    <?= Icon::get('info', 17) ?>
                    <span>Once saved you will be signed in straight away, and this link stops working.</span>
                </div>

                <button class="btn btn--primary btn--lg btn--block" type="submit">
                    Save and sign in
                </button>
            </form>

            <p class="auth__foot">
                <a href="<?= Url::to('/login') ?>" class="bold">Back to sign in</a>
            </p>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?= Art::scene('forest', 'reset-art', 440, 300) ?>
            <h2 class="mt-3" style="font-size:22px">Almost done</h2>
            <p class="muted mt-1">
                Your projects and exports are exactly where you left them.
            </p>
        </div>
    </div>

</div>
