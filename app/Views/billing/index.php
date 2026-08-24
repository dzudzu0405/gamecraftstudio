<?php
/** FR-28, FR-29 - plans and library entitlements */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Difficulty;
use App\Services\Tiers;
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Billing</h1>
    <span class="badge badge--published">Current: <?= H::e(Tiers::name($current)) ?></span>
</div>

<div class="notice notice--info">
    <?= Icon::get('info', 17) ?>
    <span>
        Each plan <b>includes everything</b> from the plans below it, plus its own additions.
        Your plan follows your WarriorPlus purchase, and a change never touches projects
        you have already made.
    </span>
</div>

<div class="pricing mb-3">
    <?php foreach ($tiers as $key => $t): ?>
        <?php
        $isCurrent = $key === $current;
        $u = $unlocked[$key];
        ?>
        <div class="price-card <?= $isCurrent ? 'price-card--current' : ($t['popular'] ? 'price-card--popular' : '') ?>">

            <?php if ($isCurrent): ?>
                <span class="price-card__flag" style="background:var(--primary)">Your plan</span>
            <?php elseif ($t['popular']): ?>
                <span class="price-card__flag"><?= H::e($t['badge']) ?></span>
            <?php endif; ?>

            <div class="price-card__name" style="color:<?= H::e($t['color']) ?>"><?= H::e($t['name']) ?></div>
            <div class="price-card__price"><?= H::e($t['price_label']) ?> <small>one-off</small></div>
            <div class="price-card__tagline"><?= H::e($t['tagline']) ?></div>

            <div class="quota-grid mb-2">
                <div class="quota">
                    <div class="quota__n"><?= (int) $u['maps'] ?></div>
                    <div class="quota__l">Maps</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $u['characters'] ?></div>
                    <div class="quota__l">Character sets</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $t['character_poses'] ?></div>
                    <div class="quota__l">Poses per set</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $u['moves'] ?></div>
                    <div class="quota__l">Move card designs</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $u['missions'] ?></div>
                    <div class="quota__l">Mission templates</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $u['rewards'] ?></div>
                    <div class="quota__l">Hero cards</div>
                </div>
            </div>

            <ul class="perks">
                <?php foreach ($t['perks'] as $perk): ?>
                    <li><?= Icon::get('check', 14) ?> <span><?= H::e($perk) ?></span></li>
                <?php endforeach; ?>
                <?php foreach ($t['locked'] as $locked): ?>
                    <li class="is-locked"><?= Icon::get('lock', 14) ?> <span><?= H::e($locked) ?></span></li>
                <?php endforeach; ?>
            </ul>

            <div class="small muted mb-2">
                <?php if ((int) $t['projects_limit'] === 0): ?>
                    <?= Icon::get('check', 13) ?> Unlimited projects
                <?php else: ?>
                    Up to <?= (int) $t['projects_limit'] ?> projects
                <?php endif; ?>
                <br>
                Difficulty levels:
                <?= H::e(implode(', ', array_map(fn($d) => Difficulty::name($d), $t['difficulties']))) ?>
            </div>

            <?php if ($isCurrent): ?>
                <button class="btn btn--ghost btn--block is-disabled" disabled>Current plan</button>
            <?php elseif (!$canSwitchPlan): ?>
                <?php if ($purchaseUrl !== ''): ?>
                    <a class="btn btn--primary btn--block" href="<?= H::e($purchaseUrl) ?>"
                       target="_blank" rel="noopener noreferrer">
                        Get <?= H::e($t['name']) ?> on WarriorPlus
                    </a>
                <?php else: ?>
                    <button class="btn btn--ghost btn--block is-disabled" disabled
                            title="Purchased on WarriorPlus">Sold on WarriorPlus</button>
                <?php endif; ?>
            <?php else: ?>
                <?php $isUpgrade = Tiers::rank($key) > Tiers::rank($current); ?>
                <form method="post" action="<?= Url::to('/billing/plan') ?>"
                      data-confirm="<?= $isUpgrade
                          ? 'Upgrade to the ' . H::e($t['name']) . ' plan?'
                          : 'Move down to the ' . H::e($t['name']) . ' plan? Some content will be locked again.' ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="plan" value="<?= H::e($key) ?>">
                    <button class="btn <?= $isUpgrade ? 'btn--primary' : 'btn--ghost' ?> btn--block" type="submit">
                        <?= $isUpgrade ? 'Upgrade to ' . H::e($t['name']) : 'Switch to ' . H::e($t['name']) ?>
                    </button>
                </form>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">

    <div class="card">
        <div class="card__head"><h3>Your usage</h3></div>
        <div class="card__body">
            <table class="data" style="font-size:13px">
                <tr>
                    <td class="muted" style="padding-left:0">Current plan</td>
                    <td class="right bold"><?= H::e(Tiers::name($current)) ?></td>
                </tr>
                <tr>
                    <td class="muted" style="padding-left:0">Started</td>
                    <td class="right"><?= H::e(H::date($startedAt, 'j M Y')) ?: '&mdash;' ?></td>
                </tr>
                <tr>
                    <td class="muted" style="padding-left:0;border-bottom:0">Projects</td>
                    <td class="right bold" style="border-bottom:0">
                        <?= (int) $projectCount ?><?= $limit > 0 ? ' / ' . (int) $limit : '' ?>
                    </td>
                </tr>
            </table>

            <?php if ($limit > 0): ?>
                <div class="bar">
                    <div class="bar__fill" style="width:<?= min(100, (int) round($projectCount / max(1, $limit) * 100)) ?>%"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card__head"><h3>About payments</h3></div>
        <div class="card__body">
            <p class="small muted">
                Plans are sold through <b>WarriorPlus</b>, which takes the payment and issues the
                receipt. Nothing on this screen can raise or lower a plan on its own.
            </p>
            <p class="small muted mb-0">
                Once a purchase clears, an administrator sets the plan on the account and the
                whole library unlocks straight away.
            </p>
        </div>
    </div>

</div>
