<?php
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\Tiers;

$f = $filters;

/** Keeps the other filters when one of them changes */
$link = static function (array $changes) use ($f): string {
    $q = array_filter(array_merge($f, $changes), static fn ($v): bool => $v !== '' && $v !== null);
    return Url::to('/admin/users') . ($q ? '?' . http_build_query($q) : '');
};
?>

<?= View::partial('partials/admin-nav', ['adminHeading' => 'Users']) ?>

<form method="get" action="<?= Url::to('/admin/users') ?>" class="card mb-2">
    <div class="card__body flex items-center gap-1" style="flex-wrap:wrap">

        <span class="search">
            <?= Icon::get('search', 16) ?>
            <input class="input" type="search" name="q" placeholder="Name or email"
                   value="<?= H::e($f['q']) ?>">
        </span>

        <select class="input select" name="plan" style="max-width:170px">
            <option value="">Any plan</option>
            <?php foreach (Tiers::ORDER as $tier): ?>
                <option value="<?= H::e($tier) ?>" <?= $f['plan'] === $tier ? 'selected' : '' ?>>
                    <?= H::e(Tiers::name($tier)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select class="input select" name="role" style="max-width:170px">
            <option value="">Any role</option>
            <option value="admin"   <?= $f['role'] === 'admin' ? 'selected' : '' ?>>Administrators</option>
            <option value="creator" <?= $f['role'] === 'creator' ? 'selected' : '' ?>>Creators</option>
        </select>

        <select class="input select" name="status" style="max-width:200px">
            <option value="">Any status</option>
            <option value="active"     <?= $f['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="disabled"   <?= $f['status'] === 'disabled' ? 'selected' : '' ?>>Deactivated</option>
            <option value="unverified" <?= $f['status'] === 'unverified' ? 'selected' : '' ?>>Awaiting email code</option>
        </select>

        <button class="btn btn--primary" type="submit">Search</button>

        <?php if (array_filter($f)): ?>
            <a class="btn btn--ghost" href="<?= Url::to('/admin/users') ?>">Clear</a>
        <?php endif; ?>

        <span class="section__count" style="margin-left:auto">
            <?= (int) $total ?> account<?= (int) $total === 1 ? '' : 's' ?>
        </span>
    </div>
</form>

<div class="card">
    <?php if (!$users): ?>
        <div class="card__body">
            <div class="empty">
                <div class="empty__icon"><?= Icon::get('users', 22) ?></div>
                <div class="empty__title">Nobody matches that</div>
                <div class="empty__desc">Try a shorter search, or clear the filters.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th class="num">Games</th>
                        <th class="nowrap">Last seen</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php $id = (int) $u['id']; ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-1">
                                    <span class="avatar"><?= H::e(H::initials($u['name'])) ?></span>
                                    <div style="min-width:0">
                                        <a class="bold" href="<?= Url::to('/admin/users/' . $id) ?>"><?= H::e($u['name']) ?></a>
                                        <?php if (($u['role'] ?? '') === 'admin'): ?>
                                            <span class="badge badge--published">Admin</span>
                                        <?php endif; ?>
                                        <div class="small faint" style="word-break:break-all"><?= H::e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="nowrap">
                                <span class="badge badge--tier" style="color:<?= H::e(Tiers::color($u['plan'])) ?>">
                                    <?= H::e(Tiers::name($u['plan'])) ?>
                                </span>
                            </td>
                            <td class="nowrap">
                                <?php if ((int) $u['is_active'] !== 1): ?>
                                    <span class="badge badge--locked">Deactivated</span>
                                <?php elseif (empty($u['email_verified_at'])): ?>
                                    <span class="badge badge--progress">Unconfirmed</span>
                                <?php else: ?>
                                    <span class="badge badge--ready">Active</span>
                                <?php endif; ?>
                                <?php if (!empty($u['google_id'])): ?>
                                    <div class="small faint">Google</div>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= (int) ($counts[$id] ?? 0) ?></td>
                            <td class="nowrap small muted" title="<?= H::e(H::date($u['last_login_at'])) ?>">
                                <?= $u['last_login_at'] ? H::e(H::timeAgo($u['last_login_at'])) : '<span class="faint">never</span>' ?>
                            </td>
                            <td class="right nowrap">
                                <a class="btn btn--sm btn--ghost" href="<?= Url::to('/admin/users/' . $id) ?>">
                                    Manage <?= Icon::get('chevron-right', 13) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="card__foot flex items-center gap-1">
                <span class="small muted">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
                <span style="margin-left:auto" class="flex gap-1">
                    <?php if ($page > 1): ?>
                        <a class="btn btn--sm btn--ghost" href="<?= H::e($link(['page' => $page - 1])) ?>">
                            <?= Icon::get('chevron-left', 13) ?> Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a class="btn btn--sm btn--ghost" href="<?= H::e($link(['page' => $page + 1])) ?>">
                            Next <?= Icon::get('chevron-right', 13) ?>
                        </a>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
