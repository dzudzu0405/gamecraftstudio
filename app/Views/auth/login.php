<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\GoogleAuth;

$errors = Flash::errors();
Flash::clearErrors();

$googleOn = GoogleAuth::isEnabled();
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

            <?php /* Google first: it is one click, and it is the only way in
                     that never asks for an emailed code. */ ?>
            <?php if ($googleOn): ?>
                <a class="btn btn--ghost btn--lg btn--block" href="<?= Url::to('/auth/google') ?>">
                    <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                        <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.49h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.7-1.57 2.68-3.88 2.68-6.63Z"/>
                        <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.8.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18Z"/>
                        <path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.95H.96a9 9 0 0 0 0 8.1l3.01-2.33Z"/>
                        <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58Z"/>
                    </svg>
                    Continue with Google
                </a>

                <div class="auth__divider"><span>or sign in with email</span></div>
            <?php endif; ?>

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
                    <div class="flex items-center gap-1" style="margin-bottom:6px">
                        <label class="label" for="password" style="margin:0">Password</label>
                        <a class="small" href="<?= Url::to('/forgot') ?>" style="margin-left:auto">Forgotten?</a>
                    </div>
                    <input class="input <?= isset($errors['password']) ? 'input--error' : '' ?>"
                           type="password" id="password" name="password" required autocomplete="current-password"
                           placeholder="Your password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="field__error"><?= H::e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn--primary btn--lg btn--block mt-1" type="submit">
                    Sign in <?= Icon::get('arrow-right', 16) ?>
                </button>
            </form>

            <p class="auth__foot">
                No account yet?
                <a href="<?= Url::to('/register') ?>" class="bold">Create one</a>
            </p>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?php /* Three finished boards, fanned. The page is selling what the
                     studio makes, and what it makes is a printed sheet - so they
                     are drawn as sheets, with a white edge and a shadow under
                     them, rather than as pictures in a frame. */ ?>
            <div class="board-fan" aria-hidden="true">
                <img class="board-fan__sheet board-fan__sheet--left"
                     src="<?= Url::asset('img/board-baby-rescue.jpg') ?>" alt="">
                <img class="board-fan__sheet board-fan__sheet--right"
                     src="<?= Url::asset('img/board-candy-land.jpg') ?>" alt="">
                <img class="board-fan__sheet board-fan__sheet--front"
                     src="<?= Url::asset('img/board-magic-academy.jpg') ?>" alt="">
            </div>

            <h2 class="mt-3" style="font-size:23px">Printable games for kids, in minutes</h2>
            <p class="muted mt-1">
                Pick a map, add a background you made yourself, and GameCraft composes the
                board, the cards and the rules into one print-ready file.
            </p>

            <?php /* The size of the library is the thing worth showing, and three
                     numbers say it faster than three sentences would. */ ?>
            <div class="auth__stats">
                <div class="auth__stat"><b>36</b><span>maps</span></div>
                <div class="auth__stat"><b>30</b><span>character sets</span></div>
                <div class="auth__stat"><b>15</b><span>card designs</span></div>
            </div>
        </div>
    </div>

</div>
