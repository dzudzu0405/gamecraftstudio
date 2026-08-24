<?php
/** FR-19, FR-20 - community inspiration */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Difficulty;
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Community</h1>
    <span class="section__count"><?= count($posts) ?> <?= count($posts) === 1 ? 'game' : 'games' ?></span>

    <div class="section__tools">
        <form method="get" action="<?= Url::to('/community') ?>" class="flex items-center gap-1 flex-wrap">
            <select class="select" name="theme" data-autosubmit-select style="width:auto">
                <option value="">All themes</option>
                <?php foreach ($themes as $k => $label): ?>
                    <option value="<?= H::e($k) ?>" <?= $theme === $k ? 'selected' : '' ?>><?= H::e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="select" name="sort" data-autosubmit-select style="width:auto">
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most liked</option>
                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
            </select>
        </form>
    </div>
</div>

<p class="muted mb-3" style="max-width:70ch">
    Games made by teachers, parents and other creators. Browse them for ideas you can bring
    into your own games.
</p>

<?php if (!$posts): ?>
    <div class="empty">
        <div class="empty__icon"><?= Icon::get('users', 40) ?></div>
        <div class="empty__title">Nothing posted yet</div>
        <a class="btn btn--ghost mt-2" href="<?= Url::to('/community') ?>">Clear filters</a>
    </div>
<?php else: ?>
    <div class="grid grid--wide">
        <?php foreach ($posts as $p): ?>
            <?php
            $id      = (int) $p['id'];
            $isLiked = in_array($id, $liked, true);
            $diff    = Difficulty::get((string) $p['difficulty']);
            ?>
            <div class="project-card">
                <div class="project-card__art">
                    <img src="<?= Url::to('art/scene/' . rawurlencode($p['theme']) . '/' . rawurlencode($p['art_seed']) . '.svg?w=480&h=330') ?>"
                         alt="" loading="lazy">
                </div>

                <?php if ((int) $p['is_featured'] === 1): ?>
                    <span class="project-card__badge badge badge--progress">
                        <?= Icon::get('star', 10) ?> Featured
                    </span>
                <?php endif; ?>

                <div class="project-card__body">
                    <div class="project-card__title"><?= H::e($p['title']) ?></div>
                    <div class="small muted mb-1" style="line-height:1.45">
                        <?= H::e(H::truncate($p['caption'], 96)) ?>
                    </div>
                    <div class="project-card__meta">
                        <span><?= H::e($diff['name']) ?> &middot; <?= (int) $diff['cells'] ?> spaces</span>
                    </div>

                    <div class="flex items-center gap-1 mt-auto">
                        <span class="avatar" style="width:26px;height:26px;font-size:10px">
                            <?= H::e(H::initials($p['author_name'])) ?>
                        </span>
                        <span class="small faint flex-1" style="min-width:0">
                            <?= H::e(H::truncate($p['author_name'], 22)) ?>
                        </span>

                        <form method="post" action="<?= Url::to('/community/' . $id . '/like') ?>">
                            <?= Csrf::field() ?>
                            <button class="btn btn--sm <?= $isLiked ? 'btn--soft' : 'btn--ghost' ?>" type="submit"
                                    aria-label="<?= $isLiked ? 'Remove like' : 'Like' ?>">
                                <?= Icon::get('heart', 13) ?> <?= (int) $p['likes'] ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
