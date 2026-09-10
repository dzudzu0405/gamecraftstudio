<?php
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\Entitlements;
use App\Services\Tiers;

$f = $filters;

$link = static function (array $changes) use ($f): string {
    $q = array_filter(array_merge($f, $changes), static fn ($v): bool => $v !== '' && $v !== null);
    return Url::to('/admin/activity') . ($q ? '?' . http_build_query($q) : '');
};

$sources = [
    Entitlements::SOURCE_LINK     => 'Delivery link',
    Entitlements::SOURCE_ADMIN    => 'Administrator',
    Entitlements::SOURCE_REGISTER => 'Signed up',
    Entitlements::SOURCE_GOOGLE   => 'Google sign-in',
];
?>

<?= View::partial('partials/admin-nav', ['adminHeading' => 'Plan activity']) ?>

<form method="get" action="<?= Url::to('/admin/activity') ?>" class="card mb-2">
    <div class="card__body flex items-center gap-1" style="flex-wrap:wrap">

        <select class="input select" name="source" style="max-width:200px">
            <option value="">Any reason</option>
            <?php foreach ($sources as $key => $label): ?>
                <option value="<?= H::e($key) ?>" <?= $f['source'] === $key ? 'selected' : '' ?>>
                    <?= H::e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select class="input select" name="plan" style="max-width:190px">
            <option value="">Any plan</option>
            <?php foreach (Tiers::ORDER as $tier): ?>
                <option value="<?= H::e($tier) ?>" <?= $f['plan'] === $tier ? 'selected' : '' ?>>
                    Moved to <?= H::e(Tiers::name($tier)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn--primary" type="submit">Filter</button>

        <?php if (array_filter($f)): ?>
            <a class="btn btn--ghost" href="<?= Url::to('/admin/activity') ?>">Clear</a>
        <?php endif; ?>

        <span class="section__count" style="margin-left:auto">
            <?= (int) $total ?> event<?= (int) $total === 1 ? '' : 's' ?>
        </span>
    </div>
</form>

<div class="card">
    <?php if (!$rows): ?>
        <div class="card__body">
            <div class="empty">
                <div class="empty__icon"><?= Icon::get('clock', 22) ?></div>
                <div class="empty__title">Nothing to show</div>
                <div class="empty__desc">Every plan change the site makes is recorded here.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Change</th>
                        <th>Reason</th>
                        <th>Note</th>
                        <th class="nowrap">When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $e): ?>
                        <tr>
                            <td>
                                <?php if ($e['user_name'] !== null): ?>
                                    <a class="bold" href="<?= Url::to('/admin/users/' . (int) $e['user_id']) ?>">
                                        <?= H::e($e['user_name']) ?>
                                    </a>
                                    <div class="small faint" style="word-break:break-all"><?= H::e($e['user_email']) ?></div>
                                <?php else: ?>
                                    <span class="faint">Deleted account</span>
                                <?php endif; ?>
                            </td>
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
                                    <div class="small faint">
                                        <a href="<?= Url::to('/admin/links/' . (int) $e['link_id'] . '/uses') ?>">
                                            <?= H::e($e['link_label']) ?>
                                        </a>
                                    </div>
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
