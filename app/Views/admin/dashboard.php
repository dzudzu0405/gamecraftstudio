<?php
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\AccessLink;
use App\Services\Entitlements;
use App\Services\Tiers;
?>

<?= View::partial('partials/admin-nav', ['adminHeading' => 'Administration']) ?>

<div class="stat-row mb-3">
    <div class="stat">
        <div class="stat__label">Accounts</div>
        <div class="stat__value"><?= (int) $stats['users'] ?></div>
        <div class="stat__sub"><?= (int) $stats['newWeek'] ?> new this week</div>
    </div>
    <div class="stat">
        <div class="stat__label">On a paid plan</div>
        <div class="stat__value"><?= (int) $stats['paid'] ?></div>
        <div class="stat__sub"><?= (int) $stats['redeemed'] ?> link redemption<?= (int) $stats['redeemed'] === 1 ? '' : 's' ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Games made</div>
        <div class="stat__value"><?= (int) $stats['projects'] ?></div>
        <div class="stat__sub"><?= (int) $stats['exports'] ?> export<?= (int) $stats['exports'] === 1 ? '' : 's' ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Awaiting email code</div>
        <div class="stat__value"><?= (int) $stats['unverified'] ?></div>
        <div class="stat__sub">
            <?php if ((int) $stats['unverified'] > 0): ?>
                <a href="<?= Url::to('/admin/users?status=unverified') ?>">See who</a>
            <?php else: ?>
                Everybody is confirmed
            <?php endif; ?>
        </div>
    </div>
    <div class="stat">
        <div class="stat__label">Deactivated</div>
        <div class="stat__value"><?= (int) $stats['disabled'] ?></div>
        <div class="stat__sub"><?= (int) $stats['links'] ?> live access link<?= (int) $stats['links'] === 1 ? '' : 's' ?></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card__head">
        <h3>Who is on what</h3>
        <a class="section__link" href="<?= Url::to('/admin/users') ?>">Manage users</a>
    </div>
    <div class="card__body">
        <div class="quota-grid">
            <?php foreach (Tiers::ORDER as $tier): ?>
                <?php $t = Tiers::get($tier); ?>
                <a class="quota" href="<?= Url::to('/admin/users?plan=' . $tier) ?>" style="text-decoration:none;color:inherit">
                    <div class="quota__n" style="color:<?= H::e($t['color']) ?>"><?= (int) ($planCounts[$tier] ?? 0) ?></div>
                    <div class="quota__l"><?= H::e($t['name']) ?> &middot; <?= H::e($t['price_label']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid grid--wide mb-3" style="grid-template-columns:minmax(0,1.35fr) minmax(0,1fr)">

    <div class="card">
        <div class="card__head">
            <h3>Recent plan changes</h3>
            <a class="section__link" href="<?= Url::to('/admin/activity') ?>">Full log</a>
        </div>

        <?php if (!$events): ?>
            <div class="card__body">
                <div class="empty">
                    <div class="empty__icon"><?= Icon::get('clock', 22) ?></div>
                    <div class="empty__title">Nothing has moved yet</div>
                    <div class="empty__desc">
                        Plan changes appear here the moment a buyer opens a delivery link.
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Change</th>
                            <th>How</th>
                            <th class="nowrap">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                            <tr>
                                <td>
                                    <?php if ($e['user_name'] !== null): ?>
                                        <a class="bold" href="<?= Url::to('/admin/users/' . (int) $e['user_id']) ?>">
                                            <?= H::e($e['user_name']) ?>
                                        </a>
                                        <div class="small faint"><?= H::e($e['user_email']) ?></div>
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
                                        <div class="small faint"><?= H::e($e['link_label']) ?></div>
                                    <?php elseif ($e['actor_name']): ?>
                                        <div class="small faint">by <?= H::e($e['actor_name']) ?></div>
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

    <div class="card">
        <div class="card__head">
            <h3>Newest accounts</h3>
            <a class="section__link" href="<?= Url::to('/admin/users') ?>">All users</a>
        </div>
        <div class="card__body">
            <?php foreach ($newUsers as $u): ?>
                <div class="flex items-center gap-1 mb-2">
                    <span class="avatar"><?= H::e(H::initials($u['name'])) ?></span>
                    <div class="flex-1" style="min-width:0">
                        <a class="bold small" href="<?= Url::to('/admin/users/' . (int) $u['id']) ?>">
                            <?= H::e($u['name']) ?>
                        </a>
                        <div class="small faint" style="word-break:break-all"><?= H::e($u['email']) ?></div>
                    </div>
                    <div class="right nowrap">
                        <span class="badge badge--tier"><?= H::e(Tiers::name($u['plan'])) ?></span>
                        <div class="small faint"><?= H::e(H::timeAgo($u['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$newUsers): ?>
                <p class="muted small mb-0">No accounts yet.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="card">
    <div class="card__head">
        <h3>Delivery links</h3>
        <a class="section__link" href="<?= Url::to('/admin/links') ?>">Manage links</a>
    </div>

    <?php if (!$links): ?>
        <div class="card__body">
            <div class="empty">
                <div class="empty__icon"><?= Icon::get('card', 22) ?></div>
                <div class="empty__title">No access links yet</div>
                <div class="empty__desc">
                    Make one for each WarriorPlus product, then paste its address into that
                    product as the delivery URL. Buyers who open it are raised to the plan
                    the link carries.
                </div>
                <a class="btn btn--primary mt-2" href="<?= Url::to('/admin/links') ?>">
                    <?= Icon::get('plus', 16) ?> Create the first link
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Link</th>
                        <th>Gives</th>
                        <th class="num">Used</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($links, 0, 6) as $l): ?>
                        <tr>
                            <td>
                                <b><?= H::e($l['label']) ?></b>
                                <?php if ((int) $l['is_active'] !== 1): ?>
                                    <span class="badge badge--locked">Off</span>
                                <?php endif; ?>
                            </td>
                            <td class="nowrap"><?= H::e(Tiers::name($l['plan'])) ?></td>
                            <td class="num"><?= (int) $l['uses'] ?><?= (int) $l['max_uses'] > 0 ? ' / ' . (int) $l['max_uses'] : '' ?></td>
                            <td class="small faint" style="word-break:break-all"><?= H::e(AccessLink::url($l)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
