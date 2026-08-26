<?php
/** Step 5 - review and finish */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Models\Project;
use App\Services\Difficulty;

echo View::partial('partials/stepbar', compact('project', 'step', 'labels'));

$pid  = (int) $project['id'];
$diff = Difficulty::get((string) $project['difficulty']);

$checklist = [
    ['Game title',        trim((string) $project['title']) !== '', (string) $project['title']],
    ['Map frame',         !empty($project['map_item_id']),         $items['map']['name'] ?? 'Not chosen'],
    ['Character set',     !empty($project['character_item_id']),   $items['character']['name'] ?? 'Not chosen'],
    ['Map background',    Project::usesThemeBackground($project) || !empty($project['background_id']),
                          Project::usesThemeBackground($project)
                              ? 'From the chosen theme'
                              : (!empty($project['background_id']) ? 'Uploaded' : 'Using a placeholder')],
    ['Mission cards',     $missionCount >= $expected,              $missionCount . ' of ' . $expected],
    ['Story',             trim((string) $project['story']) !== '', trim((string) $project['story']) !== '' ? 'Written' : 'Not written'],
];
?>

<div class="wizard">
    <div class="wizard__main">

        <div class="card mb-2">
            <div class="card__head">
                <h3>The finished map</h3>
                <a class="section__link" href="<?= Url::to('/preview/' . $pid) ?>">Preview the whole bundle</a>
            </div>
            <div class="card__body">
                <div style="border-radius:12px;overflow:hidden;border:1px solid var(--line)">
                    <img src="<?= Url::to('art/map/' . $pid . '.svg') ?>" alt="Game map" style="width:100%;display:block">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h3>Before you finish</h3></div>
            <div class="card__body" style="padding-top:6px">
                <table class="data" style="margin:-6px 0">
                    <?php foreach ($checklist as [$label, $ok, $value]): ?>
                        <tr>
                            <td style="width:26px;padding-left:0">
                                <?php if ($ok): ?>
                                    <span style="color:var(--green)"><?= Icon::get('check-circle', 17) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--amber)"><?= Icon::get('alert', 17) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="bold" style="font-size:13px"><?= H::e($label) ?></td>
                            <td class="right small muted"><?= H::e(H::truncate($value, 44)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <p class="small muted mt-2 mb-0">
                    Anything still missing will not stop you finishing - you can fill it in later in the Studio.
                </p>
            </div>
        </div>

    </div>

    <aside class="wizard__side">
        <div class="card mb-2">
            <div class="card__body">
                <div class="bold mb-1">Game summary</div>
                <table class="data" style="font-size:12.5px">
                    <tr><td class="muted" style="padding-left:0">Difficulty</td><td class="right"><?= H::e($diff['name']) ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Map spaces</td><td class="right"><?= (int) $project['cells'] ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Mission cards</td><td class="right"><?= (int) $missionCount ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Move cards</td><td class="right"><?= Difficulty::MOVE_CARDS_PER_GAME ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Hero card</td><td class="right"><?= Difficulty::HERO_CARDS_PER_GAME ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Ages</td><td class="right"><?= H::e(H::ageRange((int) $project['age_min'], (int) $project['age_max'])) ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Players</td><td class="right"><?= H::e(H::playerRange((int) $project['players_min'], (int) $project['players_max'])) ?></td></tr>
                    <tr><td class="muted" style="padding-left:0;border-bottom:0">Printed pages</td><td class="right" style="border-bottom:0">~<?= Difficulty::estimatedPages((string) $project['difficulty']) ?></td></tr>
                </table>

                <div class="small muted mt-2"><b><?= (int) $progress ?>%</b> complete</div>
                <div class="bar"><div class="bar__fill" style="width:<?= (int) $progress ?>%"></div></div>
            </div>
        </div>

        <form method="post" action="<?= Url::to('/create/' . $pid . '/step/5') ?>">
            <?= Csrf::field() ?>
            <button class="btn btn--primary btn--lg btn--block" type="submit">
                <?= Icon::get('check', 17) ?> Finish and open the Studio
            </button>
        </form>
        <a class="btn btn--ghost btn--block mt-1" href="<?= Url::to('/create/' . $pid . '/step/4') ?>">
            <?= Icon::get('arrow-left', 15) ?> Back
        </a>
    </aside>
</div>
