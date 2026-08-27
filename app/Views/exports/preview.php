<?php
/** FR-26 - preview the product before exporting */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Difficulty;
use App\Services\PrintBundle;

$pid = (int) $project['id'];

// The same frames the print file uses, so the preview is not a different product
$frames = PrintBundle::cardFrames($project);
?>

<?php if ($frames['mission'] || $frames['move']): ?>
    <?php /* One copy of each picture for the page, not one per card */ ?>
    <style>
        <?php if ($frames['mission']): ?>
        .pcard--mission { background-image: url('<?= $frames['mission'] ?>'); }
        <?php endif; ?>
        <?php if ($frames['move']): ?>
        .pcard--move { background-image: url('<?= $frames['move'] ?>'); }
        <?php endif; ?>
    </style>
<?php endif; ?>

<div class="section__head">
    <a class="btn btn--ghost btn--sm" href="<?= Url::to('/studio/' . $pid) ?>">
        <?= Icon::get('arrow-left', 14) ?> Studio
    </a>
    <h1 class="section__title" style="font-size:21px">Preview</h1>
    <span class="badge badge--tier"><?= (int) $totalPages ?> printed pages</span>

    <div class="section__tools">
        <form method="post" action="<?= Url::to('/export/' . $pid) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="format" value="blueprint">
            <button class="btn btn--ghost btn--sm" type="submit">
                <?= Icon::get('download', 14) ?> Download blueprint (.json)
            </button>
        </form>

        <?php if ($canListing): ?>
            <a class="btn btn--ghost btn--sm" href="<?= Url::to('/listing/' . $pid) ?>">
                <?= Icon::get('cart', 14) ?> Sales listing
            </a>
        <?php endif; ?>

        <form method="post" action="<?= Url::to('/export/' . $pid) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="format" value="print">
            <button class="btn btn--primary btn--sm" type="submit">
                <?= Icon::get('printer', 14) ?> Export for print
            </button>
        </form>
    </div>
</div>

<?php if ($readiness): ?>
    <div class="notice notice--warning">
        <?= Icon::get('alert', 17) ?>
        <div>
            <div class="bold mb-1">You can still export, but consider adding:</div>
            <ul style="margin:0;padding-left:18px">
                <?php foreach ($readiness as $issue): ?>
                    <li class="small"><?= H::e($issue) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php else: ?>
    <div class="notice notice--success">
        <?= Icon::get('check-circle', 17) ?>
        <span>Everything is in place. This game is ready to export.</span>
    </div>
<?php endif; ?>

<div class="wizard">
    <div class="wizard__main">

        <!-- Section order in the print bundle (FR-27) -->
        <div class="card mb-2">
            <div class="card__head">
                <h3>Print order</h3>
                <span class="small muted">Follows the FR-27 sequence</span>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Section</th>
                            <th>Paper</th>
                            <th class="num">Pages</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $s): ?>
                            <tr>
                                <td class="bold"><?= (int) $s['order'] ?></td>
                                <td class="bold"><?= H::e($s['title']) ?></td>
                                <td class="small muted">
                                    <?= $s['orientation'] === 'landscape' ? 'A4 landscape' : 'A4 portrait' ?>
                                </td>
                                <td class="num"><?= (int) $s['pages'] ?></td>
                                <td class="right">
                                    <a class="btn btn--ghost btn--sm"
                                       href="<?= Url::to('/print/' . $pid) ?>?only=<?= H::e($s['key']) ?>"
                                       target="_blank" rel="noopener">Print this only</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="bold">Total</td>
                            <td class="num bold"><?= (int) $totalPages ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Map -->
        <div class="card mb-2">
            <div class="card__head"><h3>1. Game map</h3></div>
            <div class="card__body">
                <div style="border-radius:12px;overflow:hidden;border:1px solid var(--line)">
                    <img src="<?= Url::to('art/map/' . $pid . '.svg') ?>" alt="Game map" style="width:100%;display:block">
                </div>
            </div>
        </div>

        <!-- Move cards -->
        <div class="card mb-2">
            <div class="card__head">
                <h3>4. Move cards</h3>
                <span class="small muted"><?= count($moveCards) ?> cards, fixed for every game</span>
            </div>
            <div class="card__body">
                <div class="grid grid--cards">
                    <?php foreach ($moveCards as $c): ?>
                        <div class="card pcard<?= $frames['move'] ? ' pcard--framed pcard--move' : '' ?>"
                             style="text-align:center;box-shadow:none">
                            <?php if (!$frames['move']): ?>
                                <img src="<?= Url::to('art/sticker/' . rawurlencode($c['sticker']) . '.svg?size=22') ?>"
                                     alt="" width="22" height="22">
                            <?php endif; ?>
                            <div style="font-size:26px;font-weight:800;color:<?= $c['steps'] < 0 ? 'var(--red)' : 'var(--primary)' ?>">
                                <?= $c['steps'] < 0 ? '&minus;' . abs($c['steps']) : '+' . $c['steps'] ?>
                            </div>
                            <div class="small bold"><?= H::e($c['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Mission cards (first few) -->
        <div class="card">
            <div class="card__head">
                <h3>5. Mission cards</h3>
                <a class="section__link" href="<?= Url::to('/studio/' . $pid) ?>#missions">Edit cards</a>
            </div>
            <div class="card__body">
                <?php if (!$missions): ?>
                    <div class="notice notice--warning" style="margin:0">
                        <?= Icon::get('alert', 17) ?>
                        <span>No mission cards yet. <a href="<?= Url::to('/studio/' . $pid) ?>">Generate them in the Studio</a>.</span>
                    </div>
                <?php else: ?>
                    <p class="small muted mb-2">Showing the first 9. Every card is included in the print file.</p>
                    <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                        <?php foreach ($missions as $m): ?>
                            <div class="card pcard<?= $frames['mission'] ? ' pcard--framed pcard--mission' : '' ?>"
                                 style="box-shadow:none;display:flex;flex-direction:column">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="mission-row__sticker" style="width:26px;height:26px">
                                        <img src="<?= Url::to('art/sticker/' . rawurlencode($m['sticker']) . '.svg?size=15') ?>"
                                             alt="" width="15" height="15">
                                    </span>
                                    <span class="small faint">Space <?= (int) $m['cell_no'] ?></span>
                                </div>
                                <div class="small bold" style="flex:1;line-height:1.45"><?= H::e($m['question']) ?></div>
                                <?php if (trim((string) $m['answer']) !== ''): ?>
                                    <div class="small faint mt-1">Answer: <?= H::e($m['answer']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <aside class="wizard__side">
        <div class="card mb-2">
            <div class="card__body">
                <div class="bold mb-2">Export</div>
                <p class="small muted">
                    The print file opens in a new tab. Use your browser's <b>Print</b> command and
                    choose <b>Save as PDF</b> to get a file you can send to a customer.
                </p>
                <form method="post" action="<?= Url::to('/export/' . $pid) ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="format" value="print">
                    <button class="btn btn--primary btn--block" type="submit">
                        <?= Icon::get('printer', 16) ?> Open print file
                    </button>
                </form>
                <a class="btn btn--ghost btn--block mt-1" href="<?= Url::to('/exports') ?>">
                    Export history
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card__body">
                <table class="data" style="font-size:12.5px">
                    <tr><td class="muted" style="padding-left:0">Difficulty</td>
                        <td class="right"><?= H::e(Difficulty::name((string) $project['difficulty'])) ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Map spaces</td>
                        <td class="right"><?= (int) $project['cells'] ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Move cards</td>
                        <td class="right"><?= Difficulty::MOVE_CARDS_PER_GAME ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Hero card</td>
                        <td class="right"><?= Difficulty::HERO_CARDS_PER_GAME ?></td></tr>
                    <tr><td class="muted" style="padding-left:0;border-bottom:0">Total pages</td>
                        <td class="right bold" style="border-bottom:0"><?= (int) $totalPages ?></td></tr>
                </table>
            </div>
        </div>
    </aside>
</div>
