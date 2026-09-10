<?php
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\Entitlements;
use App\Services\Tiers;

$id      = (int) $row['id'];
$isAdmin = ($row['role'] ?? '') === 'admin';
$active  = (int) $row['is_active'] === 1;
$plan    = Tiers::get($row['plan']);
?>

<?= View::partial('partials/admin-nav', ['adminHeading' => 'Users']) ?>

<p class="mb-2">
    <a class="small" href="<?= Url::to('/admin/users') ?>">
        <?= Icon::get('chevron-left', 13) ?> Back to all users
    </a>
</p>

<div class="wizard">
    <div class="wizard__main">

        <div class="card mb-2">
            <div class="card__body">
                <div class="flex items-center gap-2">
                    <span class="avatar avatar--lg"><?= H::e(H::initials($row['name'])) ?></span>
                    <div class="flex-1" style="min-width:0">
                        <div class="bold" style="font-size:17px"><?= H::e($row['name']) ?></div>
                        <div class="small muted" style="word-break:break-all"><?= H::e($row['email']) ?></div>
                        <div class="small faint mt-1">
                            Joined <?= H::e(H::date($row['created_at'], 'j M Y')) ?>
                            &middot; last seen <?= $row['last_login_at'] ? H::e(H::timeAgo($row['last_login_at'])) : 'never' ?>
                        </div>
                    </div>
                    <div class="right nowrap">
                        <span class="badge badge--tier" style="color:<?= H::e($plan['color']) ?>">
                            <?= H::e($plan['name']) ?>
                        </span>
                        <?php if ($isAdmin): ?>
                            <span class="badge badge--published">Admin</span>
                        <?php endif; ?>
                        <?php if (!$active): ?>
                            <span class="badge badge--locked">Deactivated</span>
                        <?php endif; ?>
                        <?php if (empty($row['email_verified_at'])): ?>
                            <span class="badge badge--progress">Email unconfirmed</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="quota-grid mt-3">
                    <div class="quota">
                        <div class="quota__n"><?= (int) $projectCount ?></div>
                        <div class="quota__l">Games</div>
                    </div>
                    <div class="quota">
                        <div class="quota__n"><?= (int) $exportCount ?></div>
                        <div class="quota__l">Exports</div>
                    </div>
                    <div class="quota">
                        <div class="quota__n"><?= (int) $assetCount ?></div>
                        <div class="quota__l">Uploads</div>
                    </div>
                    <div class="quota">
                        <div class="quota__n" style="font-size:16px;padding-top:4px"><?= H::e($storage) ?></div>
                        <div class="quota__l">Storage used</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__head"><h3>Plan</h3></div>
            <div class="card__body">
                <p class="small muted">
                    Buyers get their plan from the WarriorPlus delivery link. Change it here for
                    a refund, a support case, or a purchase that never came through.
                </p>

                <form method="post" action="<?= Url::to('/admin/users/' . $id . '/plan') ?>">
                    <?= Csrf::field() ?>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="plan">Plan</label>
                            <select class="input select" id="plan" name="plan">
                                <?php foreach (Tiers::ORDER as $tier): ?>
                                    <option value="<?= H::e($tier) ?>" <?= $row['plan'] === $tier ? 'selected' : '' ?>>
                                        <?= H::e(Tiers::name($tier)) ?> - <?= H::e(Tiers::get($tier)['price_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label" for="note">Reason <span class="label__hint">(kept in the log)</span></label>
                            <input class="input" type="text" id="note" name="note" maxlength="255"
                                   placeholder="Refunded on WarriorPlus, 12 Mar">
                        </div>
                    </div>

                    <button class="btn btn--primary" type="submit">
                        <?= Icon::get('check', 16) ?> Save plan
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__head">
                <h3>Plan history</h3>
                <span class="section__count"><?= count($history) ?></span>
            </div>

            <?php if (!$history): ?>
                <div class="card__body"><p class="muted small mb-0">Nothing recorded yet.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Change</th>
                                <th>How</th>
                                <th>Note</th>
                                <th class="nowrap">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $e): ?>
                                <tr>
                                    <td class="nowrap">
                                        <?php if ($e['plan_from']): ?>
                                            <span class="faint"><?= H::e(Tiers::name($e['plan_from'])) ?></span>
                                            <?= Icon::get('arrow-right', 12) ?>
                                        <?php endif; ?>
                                        <b><?= H::e(Tiers::name($e['plan_to'])) ?></b>
                                    </td>
                                    <td>
                                        <span class="badge badge--tier"><?= H::e(Entitlements::sourceLabel((string) $e['source'])) ?></span>
                                        <?php if ($e['link_label']): ?>
                                            <div class="small faint"><?= H::e($e['link_label']) ?></div>
                                        <?php elseif ($e['actor_name']): ?>
                                            <div class="small faint">by <?= H::e($e['actor_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small muted">
                                        <?= H::e($e['note'] ?? '') ?>
                                        <?php if ($e['ip']): ?>
                                            <div class="small faint"><?= H::e($e['ip']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="nowrap small muted" title="<?= H::e(H::date($e['created_at'])) ?>">
                                        <?= H::e(H::timeAgo($e['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($projects): ?>
            <div class="card">
                <div class="card__head">
                    <h3>Their games</h3>
                    <span class="section__count"><?= (int) $projectCount ?></span>
                </div>
                <div class="table-wrap">
                    <table class="data">
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                                <?php $badge = H::statusBadge((string) $p['status']); ?>
                                <tr>
                                    <td><b><?= H::e($p['title']) ?></b></td>
                                    <td class="nowrap"><span class="badge <?= H::e($badge['class']) ?>"><?= H::e($badge['label']) ?></span></td>
                                    <td class="right nowrap small muted"><?= H::e(H::timeAgo($p['updated_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <aside class="wizard__side">

        <div class="card mb-2">
            <div class="card__head"><h3>Access</h3></div>
            <div class="card__body">

                <?php if (empty($row['email_verified_at'])): ?>
                    <p class="small muted">
                        This address has never been confirmed. If the code will not reach them -
                        a bouncing mailbox, a typo they cannot fix - confirm it by hand so they
                        can get to what they paid for.
                    </p>
                    <form method="post" action="<?= Url::to('/admin/users/' . $id . '/verify') ?>" class="mb-2">
                        <?= Csrf::field() ?>
                        <button class="btn btn--soft btn--block" type="submit">
                            <?= Icon::get('check-circle', 16) ?> Confirm this email by hand
                        </button>
                    </form>
                <?php else: ?>
                    <p class="small muted mb-2">
                        <?= Icon::get('check-circle', 14) ?>
                        Email confirmed <?= H::e(H::date($row['email_verified_at'], 'j M Y')) ?>.
                        <?php if (!empty($row['google_id'])): ?>Signs in with Google.<?php endif; ?>
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= Url::to('/admin/users/' . $id . '/status') ?>" class="mb-2">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="active" value="<?= $active ? '0' : '1' ?>">
                    <button class="btn <?= $active ? 'btn--ghost' : 'btn--primary' ?> btn--block" type="submit"
                            <?= $isSelf ? 'disabled title="You cannot deactivate your own account"' : '' ?>>
                        <?= Icon::get($active ? 'lock' : 'check', 16) ?>
                        <?= $active ? 'Deactivate this account' : 'Let them sign in again' ?>
                    </button>
                </form>

                <p class="small faint mb-0">
                    Deactivating blocks sign-in. Nothing is deleted, and turning it back on
                    puts everything back exactly as it was.
                </p>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__head"><h3>Role</h3></div>
            <div class="card__body">
                <p class="small muted">
                    Administrators can see this section, move anybody's plan and manage the
                    delivery links.
                </p>
                <form method="post" action="<?= Url::to('/admin/users/' . $id . '/role') ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="role" value="<?= $isAdmin ? 'creator' : 'admin' ?>">
                    <button class="btn btn--ghost btn--block" type="submit"
                            <?= $isSelf ? 'disabled title="Ask another administrator to change your own rights"' : '' ?>>
                        <?= Icon::get('crown', 16) ?>
                        <?= $isAdmin ? 'Remove administrator rights' : 'Make administrator' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h3>Delete</h3></div>
            <div class="card__body">
                <div class="notice notice--warning">
                    <?= Icon::get('alert', 16) ?>
                    <span>
                        This removes the account, its games, exports and uploaded images.
                        It cannot be undone - deactivating is nearly always the better answer.
                    </span>
                </div>

                <form method="post" action="<?= Url::to('/admin/users/' . $id . '/delete') ?>"
                      onsubmit="return confirm('Delete <?= H::e(addslashes($row['name'])) ?> and everything on the account? This cannot be undone.')">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <label class="label" for="confirm">Type the email address to confirm</label>
                        <input class="input" type="text" id="confirm" name="confirm" autocomplete="off"
                               placeholder="<?= H::e($row['email']) ?>">
                    </div>

                    <button class="btn btn--danger btn--block" type="submit" <?= $isSelf ? 'disabled' : '' ?>>
                        <?= Icon::get('trash', 16) ?> Delete this account
                    </button>
                </form>
            </div>
        </div>

    </aside>
</div>
