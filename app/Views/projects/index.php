<?php
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Models\Project;
use App\Services\Difficulty;
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">My Projects</h1>
    <span class="section__count"><?= (int) $total ?> <?= $total === 1 ? 'project' : 'projects' ?></span>

    <div class="section__tools">
        <!-- FR-11: search -->
        <form method="get" action="<?= Url::to('/projects') ?>" class="flex items-center gap-1 flex-wrap">
            <label class="search">
                <span class="sr-only">Search projects</span>
                <?= Icon::get('search', 16) ?>
                <input class="input" type="search" name="q" placeholder="Search projects..."
                       value="<?= H::e($search) ?>" data-live-search=".project-card, .list-row">
            </label>

            <!-- Filter by status -->
            <select class="select" name="status" data-autosubmit-select style="width:auto">
                <option value="">All statuses</option>
                <?php foreach (H::statusOptions() as $key => $label): ?>
                    <option value="<?= H::e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= H::e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- FR-12: sorting -->
            <select class="select" name="sort" data-autosubmit-select style="width:auto">
                <?php foreach (Project::SORTS as $key => $label): ?>
                    <option value="<?= H::e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= H::e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <noscript><button class="btn btn--ghost btn--sm" type="submit">Apply</button></noscript>
        </form>

        <!-- FR-13: switch view mode -->
        <div class="viewtoggle">
            <a href="<?= Url::withQuery(['view' => 'grid'], '/projects') ?>"
               class="<?= $view === 'grid' ? 'is-active' : '' ?>" title="Grid view" aria-label="Grid view">
                <?= Icon::get('grid', 15) ?>
            </a>
            <a href="<?= Url::withQuery(['view' => 'list'], '/projects') ?>"
               class="<?= $view === 'list' ? 'is-active' : '' ?>" title="List view" aria-label="List view">
                <?= Icon::get('list', 15) ?>
            </a>
        </div>

        <a class="btn btn--primary btn--sm" href="<?= Url::to('/create') ?>">
            <?= Icon::get('plus', 15) ?> Create New Game
        </a>
    </div>
</div>

<?php if (!$canCreate): ?>
    <div class="notice notice--warning">
        <?= Icon::get('alert', 17) ?>
        <span>
            Your plan allows <b><?= (int) $limit ?> projects</b> and you have used them all.
            <a href="<?= Url::to('/billing') ?>">Upgrade</a> to create more, or delete an older project.
        </span>
    </div>
<?php endif; ?>

<?php if (!$projects): ?>

    <div class="empty">
        <div class="empty__icon"><?= Icon::get('folder', 40) ?></div>
        <div class="empty__title">
            <?= $search !== '' || $status !== '' ? 'No matching projects' : 'No projects yet' ?>
        </div>
        <div class="empty__desc">
            <?php if ($search !== '' || $status !== ''): ?>
                Try a different search term, or clear the filters.
            <?php else: ?>
                Create your first game to get started.
            <?php endif; ?>
        </div>
        <?php if ($search !== '' || $status !== ''): ?>
            <a class="btn btn--ghost" href="<?= Url::to('/projects') ?>">Clear filters</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= Url::to('/create') ?>"><?= Icon::get('plus', 16) ?> Create New Game</a>
        <?php endif; ?>
    </div>

<?php elseif ($view === 'list'): ?>

    <?php foreach ($projects as $project): ?>
        <?php
        $st   = H::statusBadge((string) $project['status']);
        $diff = Difficulty::get((string) $project['difficulty']);
        $id   = (int) $project['id'];
        ?>
        <div class="list-row" data-search-text="<?= H::e($project['title'] . ' ' . $st['label'] . ' ' . $diff['name']) ?>">
            <div class="list-row__art">
                <img src="<?= H::e(Project::coverUrl($project, 200, 140)) ?>" alt="" loading="lazy">
            </div>

            <div class="list-row__main">
                <a href="<?= Url::to('/studio/' . $id) ?>" style="color:inherit;text-decoration:none">
                    <div class="list-row__title"><?= H::e($project['title']) ?></div>
                </a>
                <div class="list-row__meta">
                    <span><?= H::e(H::ageRange((int) $project['age_min'], (int) $project['age_max'])) ?></span>
                    <span><?= H::e(H::playerRange((int) $project['players_min'], (int) $project['players_max'])) ?></span>
                    <span><?= H::e($diff['name']) ?> &middot; <?= (int) $project['cells'] ?> spaces</span>
                    <span class="faint">updated <?= H::e(H::timeAgo($project['updated_at'])) ?></span>
                </div>
            </div>

            <div class="list-row__side">
                <span class="badge <?= H::e($st['class']) ?>"><?= H::e($st['label']) ?></span>

                <a class="btn btn--ghost btn--sm" href="<?= Url::to('/studio/' . $id) ?>">
                    <?= Icon::get('edit', 14) ?> Edit
                </a>

                <form method="post" action="<?= Url::to('/projects/' . $id . '/delete') ?>"
                      data-confirm="Delete &quot;<?= H::e($project['title']) ?>&quot;? This cannot be undone.">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--danger btn--sm">
                        <?= Icon::get('trash', 14) ?> Delete
                    </button>
                </form>

                <div class="menu" data-menu>
                    <button type="button" class="menu__trigger" data-menu-trigger
                            aria-haspopup="true" aria-expanded="false" aria-label="Actions">
                        <?= Icon::get('more', 16) ?>
                    </button>
                    <div class="menu__list" role="menu">
                        <a class="menu__item" href="<?= Url::to('/preview/' . $id) ?>" role="menuitem">
                            <?= Icon::get('eye', 16) ?> Preview
                        </a>
                        <a class="menu__item" href="<?= Url::to('/print/' . $id) ?>" role="menuitem" target="_blank" rel="noopener">
                            <?= Icon::get('printer', 16) ?> Export for print
                        </a>
                        <div class="menu__sep"></div>
                        <form method="post" action="<?= Url::to('/projects/' . $id . '/duplicate') ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="menu__item" role="menuitem"><?= Icon::get('copy', 16) ?> Duplicate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="empty hidden" data-search-empty>
        <div class="empty__title">Nothing matches that search</div>
    </div>

<?php else: ?>

    <div class="grid grid--projects">
        <?php foreach ($projects as $project): ?>
            <?= View::partial('partials/project-card', ['project' => $project]) ?>
        <?php endforeach; ?>

        <?php if ($canCreate): ?>
            <a class="project-card project-card--new" href="<?= Url::to('/create') ?>" style="text-decoration:none;color:inherit">
                <span class="plus"><?= Icon::get('plus', 20) ?></span>
                <div class="bold" style="color:var(--primary)">Create New Game</div>
                <div class="small muted mt-1">Start a new adventure</div>
            </a>
        <?php endif; ?>
    </div>

    <div class="empty hidden mt-2" data-search-empty>
        <div class="empty__title">Nothing matches that search</div>
    </div>

<?php endif; ?>
