<?php
/** FR-17, FR-18 - the ready-made template library */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\Tiers;
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Templates</h1>
    <span class="section__count"><?= (int) $total ?> templates</span>

    <div class="section__tools">
        <form method="get" action="<?= Url::to('/templates') ?>" class="flex items-center gap-1 flex-wrap">
            <label class="search">
                <span class="sr-only">Search templates</span>
                <?= Icon::get('search', 16) ?>
                <input class="input" type="search" name="q" placeholder="Search templates..." value="<?= H::e($search) ?>">
            </label>

            <select class="select" name="theme" data-autosubmit-select style="width:auto">
                <option value="">All themes</option>
                <?php foreach ($themes as $key => $label): ?>
                    <option value="<?= H::e($key) ?>" <?= $theme === $key ? 'selected' : '' ?>><?= H::e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <select class="select" name="difficulty" data-autosubmit-select style="width:auto">
                <option value="">All difficulties</option>
                <?php foreach ($difficulties as $key => $d): ?>
                    <option value="<?= H::e($key) ?>" <?= $difficulty === $key ? 'selected' : '' ?>>
                        <?= H::e($d['name']) ?> (<?= (int) $d['cells'] ?> spaces)
                    </option>
                <?php endforeach; ?>
            </select>

            <noscript><button class="btn btn--ghost btn--sm" type="submit">Apply</button></noscript>
        </form>
    </div>
</div>

<p class="muted mb-3" style="max-width:70ch">
    Every template arrives with a theme, difficulty, question subjects and a story already set.
    Pick one and we create the project and generate the mission cards - all that is left is
    uploading a background.
</p>

<?php if (!$templates): ?>
    <div class="empty">
        <div class="empty__icon"><?= Icon::get('template', 40) ?></div>
        <div class="empty__title">No matching templates</div>
        <div class="empty__desc">Try removing some filters.</div>
        <a class="btn btn--ghost" href="<?= Url::to('/templates') ?>">Browse all templates</a>
    </div>
<?php else: ?>
    <div class="grid grid--wide">
        <?php foreach ($templates as $t): ?>
            <?php
            $locked = !Tiers::atLeast($planKey, (string) $t['tier']);
            $subjects = array_filter(explode(',', (string) $t['subjects']));
            ?>
            <div class="project-card">
                <div class="project-card__art">
                    <img src="<?= H::e(Library::templateImage($t)) ?>" alt="" loading="lazy">
                </div>

                <?php if ($locked): ?>
                    <span class="project-card__badge badge badge--locked">
                        <?= Icon::get('lock', 10) ?> <?= H::e(Tiers::name((string) $t['tier'])) ?> plan
                    </span>
                <?php else: ?>
                    <span class="project-card__badge badge badge--tier">
                        <?= H::e(ucfirst((string) $t['difficulty'])) ?>
                    </span>
                <?php endif; ?>

                <div class="project-card__body">
                    <div class="project-card__title"><?= H::e($t['name']) ?></div>
                    <div class="small muted mb-1" style="line-height:1.45">
                        <?= H::e(H::truncate($t['description'], 90)) ?>
                    </div>

                    <div class="flex flex-wrap gap-1 mb-1">
                        <?php foreach ($subjects as $s): ?>
                            <span class="chip" style="font-size:11px"><?= H::e(MissionMatcher::subjectLabel(trim($s))) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="project-card__meta">
                        <span><?= H::e(H::ageRange((int) $t['age_min'], (int) $t['age_max'])) ?></span>
                        <span><?= H::e(H::playerRange((int) $t['players_min'], (int) $t['players_max'])) ?></span>
                    </div>

                    <div class="mt-auto">
                        <div class="project-card__time mb-1">Used <?= (int) $t['uses_count'] ?> times</div>
                        <?php if ($locked): ?>
                            <a class="btn btn--ghost btn--sm btn--block" href="<?= Url::to('/billing') ?>">
                                <?= Icon::get('lock', 14) ?> Needs the <?= H::e(Tiers::name((string) $t['tier'])) ?> plan
                            </a>
                        <?php else: ?>
                            <form method="post" action="<?= Url::to('/templates/' . (int) $t['id'] . '/use') ?>">
                                <?= Csrf::field() ?>
                                <button class="btn btn--primary btn--sm btn--block" type="submit">
                                    <?= Icon::get('sparkles', 14) ?> Use this template
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
