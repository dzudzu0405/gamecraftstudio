<?php
/** Asset library - the system library plus the user's own uploads */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Library;
use App\Services\Tiers;
use App\Services\Uploader;

$folders = [
    Library::KIND_MAP       => 'maps',
    Library::KIND_CHARACTER => 'characters',
    Library::KIND_MOVE      => 'moves',
    Library::KIND_REWARD    => 'rewards',
];
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Asset Library</h1>

    <div class="section__tools">
        <div class="viewtoggle" style="padding:3px">
            <a href="<?= Url::to('/assets') ?>?tab=library"
               class="<?= $tab === 'library' ? 'is-active' : '' ?>" style="width:auto;padding:0 12px;font-size:12.5px;font-weight:700">
                System library
            </a>
            <a href="<?= Url::to('/assets') ?>?tab=uploads"
               class="<?= $tab === 'uploads' ? 'is-active' : '' ?>" style="width:auto;padding:0 12px;font-size:12.5px;font-weight:700">
                My uploads
            </a>
        </div>
    </div>
</div>


<?php if ($tab === 'uploads'): ?>

    <!-- ================= The user's own uploads ================= -->

    <div class="card mb-3">
        <div class="card__body">
            <form method="post" action="<?= Url::to('/assets/upload') ?>" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <label class="dropzone" data-dropzone data-autosubmit>
                    <div class="dropzone__icon"><?= Icon::get('upload', 30) ?></div>
                    <div class="dropzone__title">Upload a background image</div>
                    <div class="dropzone__hint" data-dropzone-label>
                        JPG, PNG, WEBP &middot; up to <?= H::e(Uploader::maxUploadLabel()) ?> &middot; resized to 2400px at most
                    </div>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                </label>
                <noscript><button class="btn btn--primary mt-2" type="submit">Upload</button></noscript>
            </form>

            <div class="small muted mt-2">
                Using <b><?= H::e(Uploader::formatBytes($usage)) ?></b> of image storage.
            </div>
        </div>
    </div>

    <?php if (!$uploads): ?>
        <div class="empty">
            <div class="empty__icon"><?= Icon::get('image', 40) ?></div>
            <div class="empty__title">No uploads yet</div>
            <div class="empty__desc">Backgrounds you upload will appear here.</div>
        </div>
    <?php else: ?>
        <div class="grid grid--tiles">
            <?php foreach ($uploads as $a): ?>
                <div class="project-card">
                    <div class="project-card__art">
                        <img src="<?= Url::upload($a['thumb_path'] ?: $a['path']) ?>" alt="" loading="lazy">
                    </div>
                    <div class="project-card__body">
                        <div class="small bold" style="line-height:1.3">
                            <?= H::e(H::truncate($a['original_name'] ?? 'background', 26)) ?>
                        </div>
                        <div class="small faint mt-1">
                            <?= (int) $a['width'] ?>&times;<?= (int) $a['height'] ?> &middot;
                            <?= H::e(Uploader::formatBytes((int) $a['size_bytes'])) ?>
                        </div>
                        <?php if ($a['project_title']): ?>
                            <div class="small faint">Used in: <?= H::e(H::truncate($a['project_title'], 20)) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= Url::to('/assets/' . (int) $a['id'] . '/delete') ?>"
                              class="mt-1" data-confirm="Delete this image? Any project using it falls back to a placeholder.">
                            <?= Csrf::field() ?>
                            <button class="btn btn--ghost btn--sm btn--block" type="submit">
                                <?= Icon::get('trash', 13) ?> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>

    <!-- ================= The system library ================= -->

    <!-- FR-34: artwork progress against the SRS section 11 targets -->
    <div class="card mb-3">
        <div class="card__head">
            <h3>Artwork progress</h3>
            <span class="small muted">Measured against the content production targets</span>
        </div>
        <div class="card__body">
            <div class="stat-row">
                <?php foreach ($progress as $key => $p): ?>
                    <div class="stat">
                        <div class="stat__label"><?= H::e($p['label']) ?></div>
                        <div class="stat__value">
                            <?= (int) $p['with_art'] ?><span class="faint" style="font-size:14px">/<?= (int) $p['target'] ?></span>
                        </div>
                        <div class="stat__sub"><?= (int) $p['in_db'] ?> items in the library</div>
                        <div class="bar">
                            <div class="bar__fill" style="width:<?= min(100, (int) $p['percent']) ?>%;
                                 background:<?= $p['percent'] >= 100 ? 'var(--green)' : 'var(--amber)' ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <details class="mt-2">
                <summary style="cursor:pointer;font-weight:700;font-size:13px">
                    How to add real artwork
                </summary>
                <div class="small muted mt-2">
                    <p>
                        Artwork is matched by <b>file name</b>. Upload files into the right folder through
                        the cPanel File Manager - no code changes, no clicking upload one item at a time.
                    </p>
                    <table class="data">
                        <thead><tr><th>Kind</th><th>Folder</th><th>Example</th></tr></thead>
                        <tbody>
                            <tr><td>Maps</td><td><code>uploads/library/maps/</code></td><td><code>map-18-01.jpg</code></td></tr>
                            <tr><td>Characters</td><td><code>uploads/library/characters/</code></td><td><code>char-01-1.jpg</code> (pose 1)</td></tr>
                            <tr><td>Move cards</td><td><code>uploads/library/moves/</code></td><td><code>move-01.jpg</code></td></tr>
                            <tr><td>Hero cards</td><td><code>uploads/library/rewards/</code></td><td><code>reward-01.jpg</code></td></tr>
                            <tr><td>Template covers</td><td><code>uploads/library/templates/</code></td><td><code>tpl-forest-standard.jpg</code></td></tr>
                        </tbody>
                    </table>
                    <p class="mt-2">
                        The file name must match the <b>code</b> shown on each card below.
                        Accepted extensions: <code>.jpg .jpeg .png .webp</code>.
                        Anything without real artwork falls back to a drawn placeholder.
                    </p>
                </div>
            </details>
        </div>
    </div>

    <!-- Filters -->
    <div class="section__head">
        <div class="flex flex-wrap gap-1">
            <?php foreach ($kinds as $key => $label): ?>
                <a class="chip <?= $kind === $key ? 'chip--active' : '' ?>"
                   href="<?= Url::to('/assets') ?>?kind=<?= H::e($key) ?>">
                    <?= H::e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="section__tools">
            <form method="get" action="<?= Url::to('/assets') ?>" class="flex items-center gap-1 flex-wrap">
                <input type="hidden" name="kind" value="<?= H::e($kind) ?>">
                <label class="search">
                    <span class="sr-only">Search the library</span>
                    <?= Icon::get('search', 16) ?>
                    <input class="input" type="search" name="q" placeholder="Search..." value="<?= H::e($search) ?>">
                </label>
                <select class="select" name="theme" data-autosubmit-select style="width:auto">
                    <option value="">All themes</option>
                    <?php foreach ($themes as $k => $label): ?>
                        <option value="<?= H::e($k) ?>" <?= $theme === $k ? 'selected' : '' ?>><?= H::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="select" name="tier" data-autosubmit-select style="width:auto">
                    <option value="">All plans</option>
                    <?php foreach (Tiers::ORDER as $t): ?>
                        <option value="<?= H::e($t) ?>" <?= $tier === $t ? 'selected' : '' ?>><?= H::e(Tiers::name($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if (!$items): ?>
        <div class="empty">
            <div class="empty__title">Nothing matches those filters</div>
            <a class="btn btn--ghost mt-2" href="<?= Url::to('/assets') ?>?kind=<?= H::e($kind) ?>">Clear filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid--tiles">
            <?php foreach ($items as $item): ?>
                <?php
                $unlocked = Library::unlocked($item, $planKey);
                $hasArt   = Library::hasRealImage($item);
                ?>
                <div class="project-card">
                    <div class="project-card__art">
                        <img src="<?= H::e(Library::imageFor($item)) ?>" alt="" loading="lazy">
                        <?php if (!$unlocked): ?>
                            <span class="pick__lock">
                                <span><?= Icon::get('lock', 18) ?><br><?= H::e(Tiers::name((string) $item['tier'])) ?> plan</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <span class="project-card__badge badge <?= $hasArt ? 'badge--ready' : 'badge--tier' ?>">
                        <?= $hasArt ? 'Real artwork' : 'Placeholder' ?>
                    </span>

                    <div class="project-card__body">
                        <div class="small bold" style="line-height:1.3">
                            <?= H::e(H::truncate($item['name'], 30)) ?>
                        </div>
                        <div class="small faint mt-1">
                            <code><?= H::e($item['code']) ?></code>
                        </div>
                        <div class="small faint">
                            <?php if ($item['cells']): ?><?= (int) $item['cells'] ?> spaces &middot; <?php endif; ?>
                            <?php if ($item['poses']): ?><?= (int) $item['poses'] ?> poses &middot; <?php endif; ?>
                            <?= H::e(Tiers::name((string) $item['tier'])) ?>
                        </div>

                        <?php if (!$hasArt): ?>
                            <div class="small faint mt-1" style="word-break:break-all">
                                Drop artwork at:<br>
                                <code>uploads/library/<?= H::e($folders[$kind] ?? $kind) ?>/<?= H::e($item['code']) ?>.jpg</code>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
