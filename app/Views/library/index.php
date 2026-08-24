<?php
/** Game library - finished and published games */
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Models\Project;
use App\Services\Difficulty;
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Game Library</h1>
    <span class="section__count"><?= count($projects) ?> <?= count($projects) === 1 ? 'game' : 'games' ?></span>

    <div class="section__tools">
        <form method="get" action="<?= Url::to('/library') ?>">
            <label class="search">
                <span class="sr-only">Search games</span>
                <?= Icon::get('search', 16) ?>
                <input class="input" type="search" name="q" placeholder="Search games..." value="<?= H::e($search) ?>">
            </label>
        </form>
    </div>
</div>

<div class="stat-row mb-3">
    <div class="stat">
        <div class="stat__label">Ready to export</div>
        <div class="stat__value" style="color:var(--green)"><?= (int) $totalReady ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Published</div>
        <div class="stat__value" style="color:var(--primary)"><?= (int) $totalPub ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">All projects</div>
        <div class="stat__value"><?= (int) $totalAll ?></div>
        <div class="stat__sub">drafts included</div>
    </div>
</div>

<?php if (!$projects): ?>
    <div class="empty">
        <div class="empty__icon"><?= Icon::get('book', 40) ?></div>
        <div class="empty__title">Your library is empty</div>
        <div class="empty__desc">
            Games appear here once their status is <b>Ready to export</b> or <b>Published</b>.
        </div>
        <a class="btn btn--primary" href="<?= Url::to('/projects') ?>">View my projects</a>
    </div>
<?php else: ?>
    <div class="grid grid--wide">
        <?php foreach ($projects as $p): ?>
            <?php
            $id   = (int) $p['id'];
            $st   = H::statusBadge((string) $p['status']);
            $diff = Difficulty::get((string) $p['difficulty']);
            $ex   = $lastExport[$id] ?? null;
            ?>
            <div class="project-card">
                <a class="project-card__art" href="<?= Url::to('/preview/' . $id) ?>">
                    <img src="<?= H::e(Project::coverUrl($p)) ?>" alt="" loading="lazy">
                </a>
                <span class="project-card__badge badge <?= H::e($st['class']) ?>"><?= H::e($st['label']) ?></span>

                <div class="project-card__body">
                    <div class="project-card__title"><?= H::e($p['title']) ?></div>
                    <div class="project-card__meta">
                        <span><?= H::e($diff['name']) ?> &middot; <?= (int) $p['cells'] ?> spaces</span>
                        <span><?= H::e(H::ageRange((int) $p['age_min'], (int) $p['age_max'])) ?></span>
                    </div>

                    <div class="small faint mb-1">
                        <?php if ($ex): ?>
                            Exported <?= (int) $ex['times'] ?> <?= (int) $ex['times'] === 1 ? 'time' : 'times' ?>
                            &middot; <?= H::e(H::timeAgo($ex['last_at'])) ?>
                        <?php else: ?>
                            Never exported
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-1 mt-auto">
                        <a class="btn btn--ghost btn--sm flex-1" href="<?= Url::to('/studio/' . $id) ?>">Edit</a>
                        <a class="btn btn--primary btn--sm flex-1" href="<?= Url::to('/print/' . $id) ?>"
                           target="_blank" rel="noopener">Print</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
