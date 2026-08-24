<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
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

            <h1 class="auth__title">Welcome back</h1>
            <p class="auth__sub">Sign in to carry on building adventure games for kids.</p>

            <form method="post" action="<?= Url::to('/login') ?>" novalidate>
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

                <div class="field">
                    <label class="label" for="password">Password</label>
                    <input class="input <?= isset($errors['password']) ? 'input--error' : '' ?>"
                           type="password" id="password" name="password" required autocomplete="current-password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="field__error"><?= H::e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn--primary btn--lg btn--block" type="submit">Sign in</button>
            </form>

            <p class="auth__foot">
                No account yet?
                <a href="<?= Url::to('/register') ?>" class="bold">Create one</a>
            </p>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?= Art::scene('magic', 'login-art', 440, 300) ?>
            <h2 class="mt-3" style="font-size:22px">Printable games for kids, in minutes</h2>
            <p class="muted mt-1">
                Pick a map, upload a background you made with AI, and we compose it and
                export a print-ready file.
            </p>
        </div>
    </div>

</div>
