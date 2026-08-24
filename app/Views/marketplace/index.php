<?php
/** Marketplace */
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$kinds = [
    'template'   => 'Game template',
    'asset_pack' => 'Asset pack',
    'bundle'     => 'Bundle',
];
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Marketplace</h1>
    <span class="section__count"><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></span>

    <div class="section__tools">
        <form method="get" action="<?= Url::to('/marketplace') ?>" class="flex items-center gap-1 flex-wrap">
            <label class="search">
                <span class="sr-only">Search the marketplace</span>
                <?= Icon::get('search', 16) ?>
                <input class="input" type="search" name="q" placeholder="Search..." value="<?= H::e($search) ?>">
            </label>
            <select class="select" name="kind" data-autosubmit-select style="width:auto">
                <option value="">All types</option>
                <?php foreach ($kinds as $k => $label): ?>
                    <option value="<?= H::e($k) ?>" <?= $kind === $k ? 'selected' : '' ?>><?= H::e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="select" name="theme" data-autosubmit-select style="width:auto">
                <option value="">All themes</option>
                <?php foreach ($themes as $k => $label): ?>
                    <option value="<?= H::e($k) ?>" <?= $theme === $k ? 'selected' : '' ?>><?= H::e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="notice notice--info">
    <?= Icon::get('info', 17) ?>
    <span>
        <?php if ($canSell): ?>
            Your Publisher plan lets you sell what you make. Open any project and choose
            <b>Sales listing</b> to draft the copy for Etsy or Amazon.
        <?php else: ?>
            Want to sell your own games? Selling is part of the
            <a href="<?= Url::to('/billing') ?>">Publisher plan</a>.
        <?php endif; ?>
    </span>
</div>

<?php if (!$items): ?>
    <div class="empty">
        <div class="empty__icon"><?= Icon::get('cart', 40) ?></div>
        <div class="empty__title">Nothing matches those filters</div>
        <a class="btn btn--ghost mt-2" href="<?= Url::to('/marketplace') ?>">Clear filters</a>
    </div>
<?php else: ?>
    <div class="grid grid--wide">
        <?php foreach ($items as $m): ?>
            <div class="project-card">
                <div class="project-card__art">
                    <img src="<?= Url::to('art/scene/' . rawurlencode($m['theme']) . '/' . rawurlencode($m['art_seed']) . '.svg?w=480&h=330') ?>"
                         alt="" loading="lazy">
                </div>
                <span class="project-card__badge badge badge--tier"><?= H::e($kinds[$m['kind']] ?? $m['kind']) ?></span>

                <div class="project-card__body">
                    <div class="project-card__title"><?= H::e($m['title']) ?></div>
                    <div class="small muted mb-1" style="line-height:1.45">
                        <?= H::e(H::truncate($m['description'], 84)) ?>
                    </div>
                    <div class="small faint mb-1">
                        <?= H::e($m['seller_name']) ?> &middot;
                        <?= Icon::get('star', 11) ?> <?= number_format($m['rating'] / 10, 1) ?> &middot;
                        <?= (int) $m['sales'] ?> sales
                    </div>

                    <div class="flex items-center gap-1 mt-auto">
                        <div class="bold" style="font-size:17px"><?= H::e(H::money((int) $m['price_cents'])) ?></div>
                        <button class="btn btn--ghost btn--sm" style="margin-left:auto" type="button" disabled
                                title="No payment gateway connected yet">
                            <?= Icon::get('cart', 13) ?> Buy
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
