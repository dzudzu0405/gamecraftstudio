<?php
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\AccessLink;
use App\Services\Tiers;
?>

<?= View::partial('partials/admin-nav', ['adminHeading' => 'Access links']) ?>

<p class="mb-2">
    <a class="small" href="<?= Url::to('/admin/links') ?>">
        <?= Icon::get('chevron-left', 13) ?> Back to all links
    </a>
</p>

<div class="card">
    <div class="card__head">
        <h3>
            <?= H::e($link['label']) ?>
            <span class="badge badge--tier"><?= H::e(Tiers::name($link['plan'])) ?></span>
        </h3>
        <span class="section__count"><?= count($rows) ?> redemption<?= count($rows) === 1 ? '' : 's' ?></span>
    </div>

    <div class="card__body">
        <p class="small faint mb-0" style="word-break:break-all">
            <?= H::e(AccessLink::url($link)) ?>
        </p>
    </div>

    <?php if (!$rows): ?>
        <div class="card__body" style="padding-top:0">
            <div class="empty">
                <div class="empty__icon"><?= Icon::get('users', 22) ?></div>
                <div class="empty__title">Nobody has used this link yet</div>
                <div class="empty__desc">Everyone who redeems it appears here, with the time and address they came from.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Raised to</th>
                        <th>On now</th>
                        <th>From</th>
                        <th class="nowrap">When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <?php if ($r['user_name'] !== null): ?>
                                    <a class="bold" href="<?= Url::to('/admin/users/' . (int) $r['user_id']) ?>">
                                        <?= H::e($r['user_name']) ?>
                                    </a>
                                    <div class="small faint" style="word-break:break-all"><?= H::e($r['user_email']) ?></div>
                                <?php else: ?>
                                    <span class="faint">Deleted account</span>
                                <?php endif; ?>
                            </td>
                            <td class="nowrap"><?= H::e(Tiers::name($r['plan_to'])) ?></td>
                            <td class="nowrap">
                                <?php if ($r['user_plan'] !== null): ?>
                                    <span class="badge badge--tier"><?= H::e(Tiers::name($r['user_plan'])) ?></span>
                                    <?php if ((int) $r['is_active'] !== 1): ?>
                                        <span class="badge badge--locked">Off</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="small muted"><?= H::e($r['ip'] ?? '') ?></td>
                            <td class="nowrap small muted" title="<?= H::e(H::date($r['created_at'])) ?>">
                                <?= H::e(H::timeAgo($r['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
