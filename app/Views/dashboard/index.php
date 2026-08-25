<?php
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\Art;
use App\Services\Library;
use App\Services\Tiers;

/**
 * The dashboard, laid out to match the reference screenshot.
 *
 * The "How it works" block is REWRITTEN to describe the product's actual
 * assembler flow (SRS section 2.3) rather than the four steps in the reference
 * image.
 */

$steps = [
    ['book',    'Pick from the library',   'Choose a difficulty, a theme and a 12, 18 or 24-space map frame.'],
    ['copy',    'Copy the image prompt',   'We write the prompt; run it in an image generator to make the artwork.'],
    ['layers',  'Upload and compose',      'Upload your image and we compose the map, then match the mission cards.'],
    ['printer', 'Preview and export',      'Swap anything you do not like, then export a print-ready file in the right order.'],
];
?>

<!-- ===== FR-04: welcome banner ===== -->
<section class="hero">
    <div class="hero__text">
        <div class="hero__eyebrow">Welcome to</div>
        <h1 class="hero__title">GameCraft Studio <span class="spark">&#10022;</span></h1>
        <p class="hero__desc">
            The studio that helps you design, customise and export printable adventure
            games for kids - built from a ready-made library, no design skills needed.
        </p>

        <div class="hero__actions">
            <!-- FR-05 -->
            <a class="btn btn--primary btn--lg" href="<?= Url::to('/create') ?>">
                <?= Icon::get('sparkles', 18) ?> Create New Game
            </a>
        </div>

        <!-- FR-07 -->
        <p class="hero__hint">
            Not sure where to start?
            <a href="<?= Url::to('/templates') ?>" class="bold">Explore <?= (int) $templateCount ?> ready-made templates <?= Icon::get('arrow-right', 13) ?></a>
        </p>
    </div>

    <div class="hero__art" aria-hidden="true">
        <?= Art::scene('magic', 'dashboard-hero', 620, 420) ?>
    </div>
</section>

<!-- ===== FR-08: how it works (rewritten per SRS section 2.3) ===== -->
<section class="section">
    <div class="card">
        <div class="card__body">
            <div class="flex items-center gap-1 mb-2">
                <h3>How it works</h3>
                <span class="badge badge--tier">4 steps</span>
            </div>

            <div class="steps">
                <?php foreach ($steps as $i => [$icon, $title, $desc]): ?>
                    <div class="step">
                        <span class="step__icon"><?= Icon::get($icon, 20) ?></span>
                        <div>
                            <div class="step__title">
                                <span class="step__no"><?= $i + 1 ?></span><?= H::e($title) ?>
                            </div>
                            <div class="step__desc"><?= H::e($desc) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== FR-09, FR-16: my projects ===== -->
<section class="section">
    <div class="section__head">
        <h2 class="section__title">My Projects</h2>
        <span class="section__count"><?= (int) $projectCount ?> <?= $projectCount === 1 ? 'project' : 'projects' ?></span>

        <div class="section__tools">
            <?php if ($projects): ?>
                <a class="section__link" href="<?= Url::to('/projects') ?>">
                    View all <?= Icon::get('arrow-right', 13) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$projects): ?>
        <div class="empty">
            <div class="empty__icon"><?= Icon::get('puzzle', 40) ?></div>
            <div class="empty__title">No projects yet</div>
            <div class="empty__desc">Start from a ready-made template, or build your first game from scratch.</div>
            <div class="flex gap-1" style="justify-content:center">
                <a class="btn btn--primary" href="<?= Url::to('/create') ?>">
                    <?= Icon::get('plus', 16) ?> Create New Game
                </a>
                <a class="btn btn--ghost" href="<?= Url::to('/templates') ?>">Browse templates</a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid--projects">
            <?php foreach ($projects as $project): ?>
                <?= View::partial('partials/project-card', ['project' => $project]) ?>
            <?php endforeach; ?>

            <!-- FR-15: quick-create card at the end of the list -->
            <a class="project-card project-card--new" href="<?= Url::to('/create') ?>" style="text-decoration:none;color:inherit">
                <span class="plus"><?= Icon::get('plus', 20) ?></span>
                <div class="bold" style="color:var(--primary)">Create New Game</div>
                <div class="small muted mt-1">Start a new adventure</div>
            </a>
        </div>
    <?php endif; ?>
</section>

<!-- ===== Bottom row: templates / community / plan ===== -->
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(290px,1fr))">

    <!-- FR-17, FR-18 -->
    <div class="card">
        <div class="card__head">
            <h3>Templates</h3>
            <a class="section__link" href="<?= Url::to('/templates') ?>">Browse all</a>
        </div>
        <div class="card__body">
            <p class="small muted mb-2">
                <?= (int) $templateCount ?> professionally designed templates, sorted by age and theme -
                usable as they are.
            </p>
            <div class="grid grid--cards" style="gap:10px">
                <?php foreach ($templates as $t): ?>
                    <a href="<?= Url::to('/templates') ?>" style="text-decoration:none;color:inherit">
                        <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line)">
                            <img src="<?= H::e(Library::templateImage($t)) ?>" alt="" loading="lazy"
                                 style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block">
                        </div>
                        <div class="small bold mt-1" style="line-height:1.3"><?= H::e(H::truncate($t['name'], 30)) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- FR-19, FR-20. No posts means Discover is off, so the card goes too. -->
    <?php if ($community): ?>
    <div class="card">
        <div class="card__head">
            <h3>Community Inspiration</h3>
            <a class="section__link" href="<?= Url::to('/community') ?>">See more</a>
        </div>
        <div class="card__body">
            <p class="small muted mb-2">See what other creators are making.</p>
            <?php foreach ($community as $c): ?>
                <div class="flex items-center gap-1 mb-1">
                    <div style="width:52px;height:38px;border-radius:8px;overflow:hidden;flex:0 0 auto;border:1px solid var(--line)">
                        <img src="<?= Url::to('art/scene/' . rawurlencode($c['theme']) . '/' . rawurlencode($c['art_seed']) . '.svg?w=160&h=120') ?>"
                             alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                    </div>
                    <div style="min-width:0">
                        <div class="small bold" style="line-height:1.3"><?= H::e($c['title']) ?></div>
                        <div class="small faint"><?= H::e(H::truncate($c['author_name'], 28)) ?></div>
                    </div>
                    <div class="small faint nowrap" style="margin-left:auto">
                        <?= Icon::get('heart', 12) ?> <?= (int) $c['likes'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FR-21, FR-22 -->
    <div class="card">
        <div class="card__head">
            <h3>Your Plan</h3>
            <a class="section__link" href="<?= Url::to('/billing') ?>">Manage Plan</a>
        </div>
        <div class="card__body">
            <div class="flex items-center gap-1 mb-2">
                <span style="color:<?= H::e($plan['color']) ?>"><?= Icon::get('crown', 20) ?></span>
                <div>
                    <div class="bold"><?= H::e($plan['name']) ?> Plan</div>
                    <div class="small muted"><?= H::e($plan['tagline']) ?></div>
                </div>
            </div>

            <div class="quota-grid">
                <div class="quota">
                    <div class="quota__n"><?= (int) $libraryStats['maps'] ?></div>
                    <div class="quota__l">Maps</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $libraryStats['characters'] ?></div>
                    <div class="quota__l">Character sets</div>
                </div>
                <div class="quota">
                    <div class="quota__n"><?= (int) $libraryStats['rewards'] ?></div>
                    <div class="quota__l">Hero cards</div>
                </div>
            </div>

            <?php $limit = Tiers::projectLimit($planKey); ?>
            <div class="small muted mt-2">
                <?php if ($limit === 0): ?>
                    <?= Icon::get('check', 13) ?> Unlimited projects
                <?php else: ?>
                    Using <b><?= (int) $projectCount ?> of <?= $limit ?></b> projects
                    <div class="bar"><div class="bar__fill" style="width:<?= min(100, (int) round($projectCount / max(1, $limit) * 100)) ?>%"></div></div>
                <?php endif; ?>
            </div>

            <?php $next = Tiers::nextUp($planKey); ?>
            <?php if ($next): ?>
                <a class="btn btn--soft btn--sm btn--block mt-2" href="<?= Url::to('/billing') ?>">
                    Upgrade to <?= H::e(Tiers::name($next)) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>
