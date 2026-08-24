<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Url;
use App\Services\Art;
use App\Services\Tiers;

$errors = Flash::errors();
Flash::clearErrors();
$chosen = Flash::old('plan', Tiers::PRO);
?>
<div class="auth">

    <div class="auth__panel">
        <div class="auth__box" style="max-width:430px">

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

            <h1 class="auth__title">Create your account</h1>
            <p class="auth__sub">Pick the plan that fits. You can change it any time.</p>

            <form method="post" action="<?= Url::to('/register') ?>" novalidate>
                <?= Csrf::field() ?>

                <div class="field">
                    <label class="label" for="name">Full name</label>
                    <input class="input <?= isset($errors['name']) ? 'input--error' : '' ?>"
                           type="text" id="name" name="name" maxlength="120" required autofocus
                           value="<?= H::e(Flash::old('name')) ?>" placeholder="Alex Morgan">
                    <?php if (isset($errors['name'])): ?><div class="field__error"><?= H::e($errors['name']) ?></div><?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="email">Email</label>
                    <input class="input <?= isset($errors['email']) ? 'input--error' : '' ?>"
                           type="email" id="email" name="email" maxlength="190" required autocomplete="email"
                           value="<?= H::e(Flash::old('email')) ?>" placeholder="you@example.com">
                    <?php if (isset($errors['email'])): ?><div class="field__error"><?= H::e($errors['email']) ?></div><?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label class="label" for="password">Password <span class="label__hint">(8+ characters)</span></label>
                        <input class="input <?= isset($errors['password']) ? 'input--error' : '' ?>"
                               type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                        <?php if (isset($errors['password'])): ?><div class="field__error"><?= H::e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="password_confirmation">Confirm password</label>
                        <input class="input <?= isset($errors['password_confirmation']) ? 'input--error' : '' ?>"
                               type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        <?php if (isset($errors['password_confirmation'])): ?><div class="field__error"><?= H::e($errors['password_confirmation']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <span class="label">Choose a plan</span>
                    <div class="choice-grid">
                        <?php foreach ($tiers as $key => $t): ?>
                            <label class="choice" style="padding:11px 13px">
                                <input type="radio" name="plan" value="<?= H::e($key) ?>"
                                       <?= $chosen === $key ? 'checked' : '' ?>>
                                <div class="choice__inner flex items-center gap-1">
                                    <div class="flex-1">
                                        <div class="choice__title">
                                            <?= H::e($t['name']) ?>
                                            <?php if ($t['popular']): ?>
                                                <span class="badge badge--progress" style="margin-left:4px"><?= H::e($t['badge']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="choice__desc"><?= H::e($t['tagline']) ?></div>
                                    </div>
                                    <div class="bold nowrap" style="font-size:16px"><?= H::e($t['price_label']) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="btn btn--primary btn--lg btn--block" type="submit">Create account</button>
            </form>

            <p class="auth__foot">
                Already have an account?
                <a href="<?= Url::to('/login') ?>" class="bold">Sign in</a>
            </p>

        </div>
    </div>

    <div class="auth__art">
        <div class="auth__art-inner">
            <?= Art::scene('forest', 'register-art', 440, 300) ?>
            <h2 class="mt-3" style="font-size:22px">36 maps, 30 character sets, hundreds of cards</h2>
            <p class="muted mt-1">
                The whole library is designed for you. All you do is choose, compose and export.
            </p>
        </div>
    </div>

</div>
