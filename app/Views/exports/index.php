<?php
/** The My Exports module - export history */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$formatLabels = [
    'print'     => ['Full print bundle', 'badge--ready'],
    'blueprint' => ['JSON blueprint',    'badge--new'],
    'listing'   => ['Sales listing',     'badge--published'],
];
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">My Exports</h1>
    <span class="section__count"><?= (int) $total ?> <?= $total === 1 ? 'export' : 'exports' ?></span>
    <div class="section__tools">
        <a class="btn btn--primary btn--sm" href="<?= Url::to('/projects') ?>">
            <?= Icon::get('folder', 15) ?> Go to projects
        </a>
    </div>
</div>

<?php if (!$exports): ?>
    <div class="empty">
        <div class="empty__icon"><?= Icon::get('download', 40) ?></div>
        <div class="empty__title">Nothing exported yet</div>
        <div class="empty__desc">Every print file and blueprint you export will be listed here.</div>
        <a class="btn btn--primary" href="<?= Url::to('/projects') ?>">Open my projects</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Type</th>
                        <th class="num">Pages</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exports as $e): ?>
                        <?php [$label, $cls] = $formatLabels[$e['format']] ?? ['Other', 'badge--tier']; ?>
                        <tr>
                            <td>
                                <?php if ($e['project_id']): ?>
                                    <a href="<?= Url::to('/studio/' . (int) $e['project_id']) ?>" class="bold">
                                        <?= H::e($e['project_title'] ?? 'Deleted project') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="faint">Deleted project</span>
                                <?php endif; ?>
                                <?php if (!empty($e['note'])): ?>
                                    <div class="small faint"><?= H::e($e['note']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= H::e($cls) ?>"><?= H::e($label) ?></span></td>
                            <td class="num"><?= (int) $e['page_count'] ?: '&mdash;' ?></td>
                            <td class="small muted nowrap">
                                <?= H::e(H::date($e['created_at'])) ?><br>
                                <span class="faint"><?= H::e(H::timeAgo($e['created_at'])) ?></span>
                            </td>
                            <td class="right nowrap">
                                <?php if ($e['project_id']): ?>
                                    <a class="btn btn--ghost btn--sm" href="<?= Url::to('/print/' . (int) $e['project_id']) ?>"
                                       target="_blank" rel="noopener">Open again</a>
                                <?php endif; ?>
                                <form method="post" action="<?= Url::to('/exports/' . (int) $e['id'] . '/delete') ?>"
                                      style="display:inline" data-confirm="Remove this entry from your history?">
                                    <?= Csrf::field() ?>
                                    <button class="btn btn--ghost btn--sm" type="submit" aria-label="Delete">
                                        <?= Icon::get('trash', 14) ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
