<?php
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\AccessLink;
use App\Services\Tiers;
?>

<?= View::partial('partials/admin-nav', ['adminHeading' => 'Access links']) ?>

<div class="card mb-3">
    <div class="card__body">
        <div class="notice notice--info mb-0">
            <?= Icon::get('info', 16) ?>
            <span>
                <b>How this works.</b> Make one link per WarriorPlus product and paste its
                address into that product as the delivery URL. A buyer who opens it is asked
                to sign in, and their account is raised to the plan on the link straight away.
                A link never lowers a plan, so somebody on Publisher can safely re-open an old
                Starter link.
            </span>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card__head"><h3>New link</h3></div>
    <div class="card__body">
        <form method="post" action="<?= Url::to('/admin/links') ?>">
            <?= Csrf::field() ?>

            <div class="form-row">
                <div class="field">
                    <label class="label" for="label">Name <span class="label__hint">(only you see it)</span></label>
                    <input class="input" type="text" id="label" name="label" maxlength="120" required
                           placeholder="WarriorPlus - GameCraft Pro (OTO 1)">
                </div>
                <div class="field">
                    <label class="label" for="new_plan">Gives</label>
                    <select class="input select" id="new_plan" name="plan">
                        <?php foreach ($tiers as $key => $t): ?>
                            <option value="<?= H::e($key) ?>" <?= $key === Tiers::PRO ? 'selected' : '' ?>>
                                <?= H::e($t['name']) ?> - <?= H::e($t['price_label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label class="label" for="max_uses">Limit <span class="label__hint">(0 = no limit)</span></label>
                    <input class="input" type="number" id="max_uses" name="max_uses" min="0" value="0">
                    <div class="field__hint">How many accounts this link may raise before it stops working.</div>
                </div>
                <div class="field">
                    <label class="label" for="expires_at">Stops working on <span class="label__hint">(optional)</span></label>
                    <input class="input" type="date" id="expires_at" name="expires_at">
                    <div class="field__hint">Leave empty for a link that never expires.</div>
                </div>
            </div>

            <div class="field">
                <label class="label" for="note">Note <span class="label__hint">(optional)</span></label>
                <input class="input" type="text" id="note" name="note" maxlength="255"
                       placeholder="WarriorPlus product ID, launch date, anything you want to remember">
            </div>

            <button class="btn btn--primary" type="submit">
                <?= Icon::get('plus', 16) ?> Create link
            </button>
        </form>
    </div>
</div>

<?php if (!$links): ?>

    <div class="card">
        <div class="card__body">
            <div class="empty">
                <div class="empty__icon"><?= Icon::get('card', 22) ?></div>
                <div class="empty__title">No links yet</div>
                <div class="empty__desc">
                    Most sites need three: one for Starter, one for Pro and one for Publisher.
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <?php foreach ($links as $l): ?>
        <?php
        $lid     = (int) $l['id'];
        $url     = AccessLink::url($l);
        $tier    = Tiers::get($l['plan']);
        $off     = (int) $l['is_active'] !== 1;
        $expired = $l['expires_at'] && strtotime((string) $l['expires_at']) < time();
        $full    = (int) $l['max_uses'] > 0 && (int) $l['uses'] >= (int) $l['max_uses'];
        $used    = (int) ($counts[$lid] ?? 0);
        ?>
        <div class="card mb-2">
            <div class="card__head">
                <h3>
                    <?= H::e($l['label']) ?>
                    <span class="badge badge--tier" style="color:<?= H::e($tier['color']) ?>"><?= H::e($tier['name']) ?></span>
                    <?php if ($off): ?><span class="badge badge--locked">Switched off</span><?php endif; ?>
                    <?php if ($expired): ?><span class="badge badge--draft">Expired</span><?php endif; ?>
                    <?php if ($full): ?><span class="badge badge--draft">Limit reached</span><?php endif; ?>
                </h3>
                <a class="section__link" href="<?= Url::to('/admin/links/' . $lid . '/uses') ?>">
                    <?= $used ?> redemption<?= $used === 1 ? '' : 's' ?>
                </a>
            </div>

            <div class="card__body">

                <div class="field">
                    <label class="label" for="url-<?= $lid ?>">Delivery URL for WarriorPlus</label>
                    <div class="flex gap-1">
                        <input class="input" id="url-<?= $lid ?>" type="text" readonly
                               value="<?= H::e($url) ?>" onclick="this.select()" style="font-size:12.5px">
                        <button type="button" class="btn btn--soft nowrap" data-copy="#url-<?= $lid ?>">
                            <?= Icon::get('copy', 15) ?> Copy
                        </button>
                    </div>
                </div>

                <form method="post" action="<?= Url::to('/admin/links/' . $lid) ?>">
                    <?= Csrf::field() ?>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="label-<?= $lid ?>">Name</label>
                            <input class="input" type="text" id="label-<?= $lid ?>" name="label"
                                   maxlength="120" value="<?= H::e($l['label']) ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="plan-<?= $lid ?>">Gives</label>
                            <select class="input select" id="plan-<?= $lid ?>" name="plan">
                                <?php foreach ($tiers as $key => $t): ?>
                                    <option value="<?= H::e($key) ?>" <?= $l['plan'] === $key ? 'selected' : '' ?>>
                                        <?= H::e($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="max-<?= $lid ?>">Limit <span class="label__hint">(0 = none)</span></label>
                            <input class="input" type="number" id="max-<?= $lid ?>" name="max_uses" min="0"
                                   value="<?= (int) $l['max_uses'] ?>">
                            <div class="field__hint">Used <?= (int) $l['uses'] ?> so far.</div>
                        </div>
                        <div class="field">
                            <label class="label" for="exp-<?= $lid ?>">Stops working on</label>
                            <input class="input" type="date" id="exp-<?= $lid ?>" name="expires_at"
                                   value="<?= $l['expires_at'] ? H::e(H::date($l['expires_at'], 'Y-m-d')) : '' ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="note-<?= $lid ?>">Note</label>
                        <input class="input" type="text" id="note-<?= $lid ?>" name="note" maxlength="255"
                               value="<?= H::e($l['note'] ?? '') ?>">
                    </div>

                    <label class="flex items-center gap-1 mb-2" style="cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" <?= $off ? '' : 'checked' ?>>
                        <span class="small">Link is live and handing out plans</span>
                    </label>

                    <button class="btn btn--primary" type="submit">
                        <?= Icon::get('check', 16) ?> Save
                    </button>
                </form>
            </div>

            <div class="card__foot flex items-center gap-1" style="flex-wrap:wrap">
                <form method="post" action="<?= Url::to('/admin/links/' . $lid . '/rotate') ?>"
                      onsubmit="return confirm('Give this link a new address? The current one stops working at once, so you will need to update WarriorPlus.')">
                    <?= Csrf::field() ?>
                    <button class="btn btn--sm btn--ghost" type="submit">
                        <?= Icon::get('refresh', 14) ?> New address
                    </button>
                </form>

                <span class="small faint">
                    Use this if the link has been shared somewhere it should not have been.
                </span>

                <?php if ($used === 0): ?>
                    <form method="post" action="<?= Url::to('/admin/links/' . $lid . '/delete') ?>"
                          style="margin-left:auto"
                          onsubmit="return confirm('Delete this link?')">
                        <?= Csrf::field() ?>
                        <button class="btn btn--sm btn--ghost" type="submit">
                            <?= Icon::get('trash', 14) ?> Delete
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
