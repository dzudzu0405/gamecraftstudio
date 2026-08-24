<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Uploader;

$errors = Flash::errors();
Flash::clearErrors();
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Settings</h1>
</div>

<div class="wizard">
    <div class="wizard__main">

        <div class="card mb-2">
            <div class="card__head"><h3>Account details</h3></div>
            <div class="card__body">
                <form method="post" action="<?= Url::to('/settings/profile') ?>">
                    <?= Csrf::field() ?>

                    <div class="flex items-center gap-2 mb-2">
                        <span class="avatar avatar--lg"><?= H::e(H::initials($user['name'])) ?></span>
                        <div>
                            <div class="bold"><?= H::e($user['name']) ?></div>
                            <div class="small muted"><?= H::e($user['email']) ?></div>
                            <div class="small faint">
                                Joined <?= H::e(H::date($user['created_at'], 'j M Y')) ?>
                                <?php if (($user['role'] ?? '') === 'admin'): ?>
                                    &middot; <span class="badge badge--published">Administrator</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="name">Full name</label>
                            <input class="input <?= isset($errors['name']) ? 'input--error' : '' ?>"
                                   type="text" id="name" name="name" maxlength="120" required
                                   value="<?= H::e($user['name']) ?>">
                            <?php if (isset($errors['name'])): ?><div class="field__error"><?= H::e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="label" for="email">Email</label>
                            <input class="input <?= isset($errors['email']) ? 'input--error' : '' ?>"
                                   type="email" id="email" name="email" maxlength="190" required
                                   value="<?= H::e($user['email']) ?>">
                            <?php if (isset($errors['email'])): ?><div class="field__error"><?= H::e($errors['email']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <button class="btn btn--primary" type="submit">
                        <?= Icon::get('check', 16) ?> Save details
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__head"><h3>Change password</h3></div>
            <div class="card__body">
                <form method="post" action="<?= Url::to('/settings/password') ?>">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <label class="label" for="current_password">Current password</label>
                        <input class="input <?= isset($errors['current_password']) ? 'input--error' : '' ?>"
                               type="password" id="current_password" name="current_password"
                               required autocomplete="current-password">
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="field__error"><?= H::e($errors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="password">New password <span class="label__hint">(8+ characters)</span></label>
                            <input class="input <?= isset($errors['password']) ? 'input--error' : '' ?>"
                                   type="password" id="password" name="password" minlength="8"
                                   required autocomplete="new-password">
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
                    </div>

                    <button class="btn btn--primary" type="submit">
                        <?= Icon::get('lock', 16) ?> Change password
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h3>About your data</h3></div>
            <div class="card__body">
                <p class="small muted">
                    Images you upload are stored on the server's disk; the database only keeps the path.
                    You can delete individual images from the
                    <a href="<?= Url::to('/assets') ?>?tab=uploads">Asset Library</a>.
                </p>
                <p class="small muted mb-0">
                    Every project can be exported as a <b>.json</b> blueprint for backup or for moving to
                    another account - open the project's preview page and choose "Download blueprint".
                </p>
            </div>
        </div>

    </div>

    <aside class="wizard__side">
        <div class="card mb-2">
            <div class="card__body">
                <div class="flex items-center gap-1 mb-2">
                    <span style="color:<?= H::e($plan['color']) ?>"><?= Icon::get('crown', 18) ?></span>
                    <b><?= H::e($plan['name']) ?> Plan</b>
                </div>
                <a class="btn btn--ghost btn--sm btn--block" href="<?= Url::to('/billing') ?>">Manage plan</a>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__body">
                <div class="bold mb-2" style="font-size:13px">Your numbers</div>
                <table class="data" style="font-size:12.5px">
                    <tr><td class="muted" style="padding-left:0">Projects</td><td class="right bold"><?= (int) $projectCount ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Mission cards</td><td class="right bold"><?= (int) $missionCount ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Exports</td><td class="right bold"><?= (int) $exportCount ?></td></tr>
                    <tr><td class="muted" style="padding-left:0;border-bottom:0">Image storage</td>
                        <td class="right bold" style="border-bottom:0"><?= H::e(Uploader::formatBytes($assetUsage)) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card__body">
                <div class="small muted mb-1">Version</div>
                <div class="bold">GameCraft Studio <?= H::e(GC_VERSION) ?></div>
                <form method="post" action="<?= Url::to('/logout') ?>" class="mt-2">
                    <?= Csrf::field() ?>
                    <button class="btn btn--ghost btn--sm btn--block" type="submit">
                        <?= Icon::get('logout', 14) ?> Sign out
                    </button>
                </form>
            </div>
        </div>
    </aside>
</div>
