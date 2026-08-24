<?php
/**
 * A project card (FR-09, FR-10, FR-14).
 * Expects: $project, and optionally $showMenu (defaults to true)
 */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Models\Project;
use App\Services\Difficulty;

$showMenu = $showMenu ?? true;
$status   = H::statusBadge((string) $project['status']);
$diff     = Difficulty::get((string) $project['difficulty']);
$id       = (int) $project['id'];
?>
<div class="project-card"
     data-search-text="<?= H::e($project['title'] . ' ' . $status['label'] . ' ' . $diff['name']) ?>">

    <a class="project-card__art" href="<?= Url::to('/studio/' . $id) ?>" aria-label="Open <?= H::e($project['title']) ?>">
        <img src="<?= H::e(Project::coverUrl($project)) ?>" alt="" loading="lazy" width="480" height="330">
    </a>

    <span class="project-card__badge badge <?= H::e($status['class']) ?>"><?= H::e($status['label']) ?></span>

    <?php if ($showMenu): ?>
        <div class="project-card__menu menu" data-menu>
            <button type="button" class="menu__trigger" data-menu-trigger
                    aria-haspopup="true" aria-expanded="false"
                    aria-label="Actions for <?= H::e($project['title']) ?>">
                <?= Icon::get('more', 16) ?>
            </button>

            <div class="menu__list" role="menu">
                <a class="menu__item" href="<?= Url::to('/studio/' . $id) ?>" role="menuitem">
                    <?= Icon::get('edit', 16) ?> Edit
                </a>
                <a class="menu__item" href="<?= Url::to('/preview/' . $id) ?>" role="menuitem">
                    <?= Icon::get('eye', 16) ?> Preview
                </a>
                <a class="menu__item" href="<?= Url::to('/print/' . $id) ?>" role="menuitem" target="_blank" rel="noopener">
                    <?= Icon::get('printer', 16) ?> Export for print
                </a>

                <div class="menu__sep"></div>

                <form method="post" action="<?= Url::to('/projects/' . $id . '/duplicate') ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="menu__item" role="menuitem">
                        <?= Icon::get('copy', 16) ?> Duplicate
                    </button>
                </form>

                <form method="post" action="<?= Url::to('/projects/' . $id . '/delete') ?>"
                      data-confirm="Delete &quot;<?= H::e($project['title']) ?>&quot;? This cannot be undone.">
                    <?= Csrf::field() ?>
                    <button type="submit" class="menu__item menu__item--danger" role="menuitem">
                        <?= Icon::get('trash', 16) ?> Delete project
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="project-card__body">
        <a href="<?= Url::to('/studio/' . $id) ?>" style="color:inherit;text-decoration:none">
            <div class="project-card__title"><?= H::e($project['title']) ?></div>
        </a>

        <div class="project-card__meta">
            <span><?= Icon::get('user', 12) ?> <?= H::e(H::ageRange((int) $project['age_min'], (int) $project['age_max'])) ?></span>
            <span><?= Icon::get('users', 12) ?> <?= H::e(H::playerRange((int) $project['players_min'], (int) $project['players_max'])) ?></span>
        </div>

        <div class="project-card__time">
            <?= H::e($diff['name']) ?> &middot; <?= (int) $project['cells'] ?> spaces &middot; updated <?= H::e(H::timeAgo($project['updated_at'])) ?>
        </div>
    </div>
</div>
