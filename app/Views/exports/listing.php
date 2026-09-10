<?php
/** FR-32 - product listings for Amazon / Etsy (Publisher plan) */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$pid = (int) $project['id'];
$v   = $listing ?: $draft;
$v['price'] = isset($listing['price_cents'])
    ? number_format($listing['price_cents'] / 100, 2, '.', '')
    : ($draft['price'] ?? '4.99');
?>

<div class="section__head">
    <a class="btn btn--ghost btn--sm" href="<?= Url::to('/preview/' . $pid) ?>">
        <?= Icon::get('arrow-left', 14) ?> Preview
    </a>
    <h1 class="section__title" style="font-size:21px">Sales listing</h1>
    <span class="badge badge--published"><?= Icon::get('crown', 11) ?> Publisher plan</span>
</div>

<div class="notice notice--info">
    <?= Icon::get('info', 17) ?>
    <span>
        The copy below is drafted from your project details, in the format Etsy and Amazon expect.
        Edit anything you like, then copy each field across to your listing page.
    </span>
</div>

<form method="post" action="<?= Url::to('/listing/' . $pid) ?>">
    <?= Csrf::field() ?>

    <div class="wizard">
        <div class="wizard__main">

            <div class="card mb-2">
                <div class="card__head"><h3>Sales channel</h3></div>
                <div class="card__body">
                    <div class="choice-grid" style="grid-template-columns:repeat(2,1fr)">
                        <?php foreach (['etsy' => 'Etsy', 'amazon' => 'Amazon KDP / Merch'] as $key => $label): ?>
                            <label class="choice">
                                <input type="radio" name="channel" value="<?= H::e($key) ?>"
                                       <?= ($listing['channel'] ?? 'etsy') === $key ? 'checked' : '' ?>>
                                <div class="choice__inner">
                                    <div class="choice__title"><?= H::e($label) ?></div>
                                    <div class="choice__desc">
                                        <?= $key === 'etsy' ? '140-character title limit, 13 tags' : '200-character title limit, 5 bullet points' ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card__head">
                    <h3>Product title</h3>
                    <button type="button" class="btn btn--ghost btn--sm" data-copy="#f-title">
                        <?= Icon::get('copy', 14) ?> Copy
                    </button>
                </div>
                <div class="card__body">
                    <textarea class="textarea" id="f-title" name="title" maxlength="200"
                              style="min-height:64px" data-counter="#c-title"><?= H::e($v['title']) ?></textarea>
                    <div class="small faint right" id="c-title"></div>

                    <?php
                    /*
                     * The same product titled for the other marketplace.
                     * Etsy stops at 140 characters and reads the front of the
                     * line hardest; Amazon allows 200 and has room for the
                     * spaces, the level and the player count. Switching the
                     * channel above does not rewrite what you have edited, so
                     * both are here to copy from.
                     */
                    $other = ($listing['channel'] ?? 'etsy') === 'amazon'
                        ? ['label' => 'Etsy title', 'text' => $draft['etsy_title'], 'max' => 140]
                        : ['label' => 'Amazon title', 'text' => $draft['amazon_title'], 'max' => 200];
                    ?>
                    <div class="mt-2" style="border-top:1px solid var(--line);padding-top:10px">
                        <div class="small muted mb-1">
                            <?= H::e($other['label']) ?>
                            <span class="faint">(<?= mb_strlen($other['text']) ?>/<?= (int) $other['max'] ?>)</span>
                        </div>
                        <textarea class="textarea" id="f-title-other" readonly
                                  style="min-height:56px"><?= H::e($other['text']) ?></textarea>
                        <div class="right mt-1">
                            <button type="button" class="btn btn--ghost btn--sm" data-copy="#f-title-other">
                                <?= Icon::get('copy', 14) ?> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card__head">
                    <h3>Bullet points</h3>
                    <button type="button" class="btn btn--ghost btn--sm" data-copy="#f-bullets">
                        <?= Icon::get('copy', 14) ?> Copy
                    </button>
                </div>
                <div class="card__body">
                    <textarea class="textarea" id="f-bullets" name="bullet_points" maxlength="2000"
                              style="min-height:150px"><?= H::e($v['bullet_points']) ?></textarea>
                    <div class="small muted mt-1">One bullet point per line.</div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card__head">
                    <h3>Full description</h3>
                    <button type="button" class="btn btn--ghost btn--sm" data-copy="#f-desc">
                        <?= Icon::get('copy', 14) ?> Copy
                    </button>
                </div>
                <div class="card__body">
                    <textarea class="textarea" id="f-desc" name="description" maxlength="5000"
                              style="min-height:260px"><?= H::e($v['description']) ?></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card__head">
                    <h3>Tags</h3>
                    <button type="button" class="btn btn--ghost btn--sm" data-copy="#f-tags">
                        <?= Icon::get('copy', 14) ?> Copy
                    </button>
                </div>
                <div class="card__body">
                    <textarea class="textarea" id="f-tags" name="tags" maxlength="400"
                              style="min-height:80px"><?= H::e($v['tags']) ?></textarea>
                    <div class="small muted mt-1">
                        Comma separated. Etsy allows 13 tags, up to 20 characters each -
                        these are drafted inside both limits.
                    </div>
                </div>
            </div>

            <?php if (!empty($draft['backend_keywords'])): ?>
                <div class="card mt-2">
                    <div class="card__head">
                        <h3>Amazon backend keywords</h3>
                        <button type="button" class="btn btn--ghost btn--sm" data-copy="#f-keywords">
                            <?= Icon::get('copy', 14) ?> Copy
                        </button>
                    </div>
                    <div class="card__body">
                        <textarea class="textarea" id="f-keywords" readonly
                                  style="min-height:120px"><?= H::e($draft['backend_keywords']) ?></textarea>
                        <div class="small muted mt-1">
                            One phrase per box, seven boxes, 50 characters each. Nothing here
                            repeats a word from the title above - Amazon indexes both together,
                            so a word used twice buys nothing the first one did not.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <aside class="wizard__side">
            <div class="card mb-2">
                <div class="card__body">
                    <div class="small muted mb-1">Suggested cover image</div>
                    <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line)">
                        <img src="<?= Url::to('art/map/' . $pid . '.svg?w=800&h=550') ?>" alt=""
                             style="width:100%;display:block">
                    </div>
                    <div class="small faint mt-1">
                        Screenshot the map for your main listing image, and add a few card shots alongside it.
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card__body">
                    <div class="field" style="margin-bottom:0">
                        <label class="label" for="price">Price (USD)</label>
                        <input class="input" type="number" id="price" name="price"
                               step="0.01" min="0" value="<?= H::e($v['price']) ?>">
                    </div>
                </div>
            </div>

            <button class="btn btn--primary btn--lg btn--block" type="submit">
                <?= Icon::get('check', 17) ?> Save listing
            </button>

            <?php if ($listing): ?>
                <div class="small faint center mt-1">
                    Saved <?= H::e(H::timeAgo($listing['created_at'])) ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</form>
