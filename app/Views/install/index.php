<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$errors  = Flash::errors();
Flash::clearErrors();
$required = array_filter($checks, fn($c) => empty($c['optional']));
$allOk    = $dbOk && !in_array(false, array_column($required, 'ok'), true);
?>
<div style="max-width:640px;margin:0 auto;padding:40px 20px 70px">

    <div class="flex items-center gap-1 mb-3">
        <span class="sidebar__logo">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9Z" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="m12 12 8-4.5M12 12v9M12 12 4 7.5" stroke="#fff" stroke-width="1.7" stroke-linejoin="round" opacity=".75"/>
            </svg>
        </span>
        <div>
            <div style="font-weight:800;font-size:18px">GameCraft Studio</div>
            <div class="small muted">First-time setup</div>
        </div>
    </div>

    <!-- Step 1: server check -->
    <div class="card mb-2">
        <div class="card__head"><h3>1. Server check</h3></div>
        <div class="card__body" style="padding-top:6px">
            <table class="data" style="margin:-6px 0">
                <?php foreach ($checks as $c): ?>
                    <tr>
                        <td style="width:26px;padding-left:0">
                            <?php if ($c['ok']): ?>
                                <span style="color:var(--green)"><?= Icon::get('check-circle', 17) ?></span>
                            <?php elseif (!empty($c['optional'])): ?>
                                <span style="color:var(--amber)"><?= Icon::get('alert', 17) ?></span>
                            <?php else: ?>
                                <span style="color:var(--red)"><?= Icon::get('x', 17) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="bold" style="font-size:13px"><?= H::e($c['label']) ?></div>
                            <?php if (!$c['ok']): ?>
                                <div class="small muted"><?= H::e($c['fix']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="right small muted nowrap"><?= H::e($c['value']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Step 2: database -->
    <div class="card mb-2">
        <div class="card__head"><h3>2. Database connection</h3></div>
        <div class="card__body">
            <?php if ($dbOk): ?>
                <div class="notice notice--success" style="margin:0">
                    <?= Icon::get('check-circle', 17) ?>
                    <span>Connected to the database.</span>
                </div>
            <?php else: ?>
                <div class="notice notice--error">
                    <?= Icon::get('alert', 17) ?>
                    <div>
                        <div class="bold mb-1">Could not connect</div>
                        <?php if ($dbError): ?>
                            <div class="small" style="word-break:break-word"><?= H::e($dbError) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="small muted">
                    <p class="bold" style="color:var(--text)">How to fix this on cPanel:</p>
                    <ol class="howto-list" style="margin-top:10px">
                        <li>Open <b>cPanel &rsaquo; MySQL&reg; Databases</b> and create a new database.</li>
                        <li>Create a <b>MySQL User</b> and give it a password.</li>
                        <li>Add that user to the database and tick <b>ALL PRIVILEGES</b>.</li>
                        <li>Open <code>config.php</code> in File Manager and fill in <code>name</code>,
                            <code>user</code> and <code>pass</code> exactly as you just created them.</li>
                        <li>Reload this page.</li>
                    </ol>
                    <p class="mt-2">
                        cPanel usually prefixes both the database and user name with your account name,
                        for example <code>abcxyz_gamecraft</code>. Copy them across exactly.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 3: admin account -->
    <div class="card">
        <div class="card__head"><h3>3. Administrator account</h3></div>
        <div class="card__body">

            <?php if (!$allOk): ?>
                <div class="notice notice--warning">
                    <?= Icon::get('alert', 17) ?>
                    <span>Please resolve the items flagged above before installing.</span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= Url::to('/install') ?>">
                <?= Csrf::field() ?>

                <div class="field">
                    <label class="label" for="name">Full name</label>
                    <input class="input <?= isset($errors['name']) ? 'input--error' : '' ?>"
                           type="text" id="name" name="name" maxlength="120" required
                           value="<?= H::e(Flash::old('name')) ?>" placeholder="Alex Morgan">
                    <?php if (isset($errors['name'])): ?><div class="field__error"><?= H::e($errors['name']) ?></div><?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="email">Sign-in email</label>
                    <input class="input <?= isset($errors['email']) ? 'input--error' : '' ?>"
                           type="email" id="email" name="email" maxlength="190" required
                           value="<?= H::e(Flash::old('email')) ?>" placeholder="you@example.com">
                    <?php if (isset($errors['email'])): ?><div class="field__error"><?= H::e($errors['email']) ?></div><?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label class="label" for="password">Password <span class="label__hint">(8+ characters)</span></label>
                        <input class="input <?= isset($errors['password']) ? 'input--error' : '' ?>"
                               type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                        <?php if (isset($errors['password'])): ?><div class="field__error"><?= H::e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="password_confirmation">Confirm password</label>
                        <input class="input <?= isset($errors['password_confirmation']) ? 'input--error' : '' ?>"
                               type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        <?php if (isset($errors['password_confirmation'])): ?><div class="field__error"><?= H::e($errors['password_confirmation']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="notice notice--info">
                    <?= Icon::get('info', 17) ?>
                    <span>The first account gets the <b>Publisher</b> plan and administrator rights, so the whole library is unlocked.</span>
                </div>

                <label class="choice mb-2" style="padding:11px 13px">
                    <input type="checkbox" name="demo" value="1" checked>
                    <div class="choice__inner">
                        <div class="choice__title">Also create 4 sample projects</div>
                        <div class="choice__desc">So the interface has something in it straight away. Delete them any time.</div>
                    </div>
                </label>

                <button class="btn btn--primary btn--lg btn--block <?= $allOk ? '' : 'is-disabled' ?>" type="submit">
                    <?= Icon::get('sparkles', 18) ?> Install GameCraft Studio
                </button>
            </form>

        </div>
    </div>

    <p class="small faint center mt-2">
        This creates the database tables and seeds 36 maps, 30 character sets,
        15 mission templates and over 50 game templates.
    </p>

</div>
