<?php
/**
 * Step 4 - mission cards.
 * FR-24: the system matches suitable cards from the library automatically.
 * FR-35: it expands the 15 base templates to fill the whole game.
 */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\MissionMatcher;

echo View::partial('partials/stepbar', compact('project', 'step', 'labels'));

$pid      = (int) $project['id'];
$complete = $missionCount >= $expected;
?>

<div class="wizard">
    <div class="wizard__main">

        <div class="card mb-2">
            <div class="card__head">
                <h3>Match mission cards</h3>
                <?php if ($complete): ?>
                    <span class="badge badge--ready"><?= Icon::get('check', 11) ?> Complete</span>
                <?php endif; ?>
            </div>
            <div class="card__body">

                <div class="stat-row mb-2">
                    <div class="stat">
                        <div class="stat__label">Needed</div>
                        <div class="stat__value"><?= (int) $expected ?></div>
                        <div class="stat__sub">mission cards</div>
                    </div>
                    <div class="stat">
                        <div class="stat__label">Generated</div>
                        <div class="stat__value" style="color:<?= $complete ? 'var(--green)' : 'var(--amber)' ?>">
                            <?= (int) $missionCount ?>
                        </div>
                        <div class="stat__sub"><?= (int) $project['cells'] ?> spaces &times; <?= (int) $perCell ?> cards</div>
                    </div>
                    <div class="stat">
                        <div class="stat__label">Matching templates</div>
                        <div class="stat__value"><?= count($templates) ?></div>
                        <div class="stat__sub">about <?= number_format($variants) ?> distinct questions</div>
                    </div>
                </div>

                <?php if (!$templates): ?>
                    <div class="notice notice--warning">
                        <?= Icon::get('alert', 17) ?>
                        <span>No templates match the subjects you picked. Try adding another subject below.</span>
                    </div>
                <?php elseif ($variants < $expected): ?>
                    <div class="notice notice--warning">
                        <?= Icon::get('alert', 17) ?>
                        <span>
                            These subjects can only produce about <b><?= number_format($variants) ?></b> distinct
                            questions, but the game needs <b><?= (int) $expected ?></b> cards.
                            Some questions will repeat - add more subjects to avoid that.
                        </span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Url::to('/create/' . $pid . '/generate') ?>">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <span class="label">Question subjects</span>
                        <div class="choice-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
                            <?php foreach ($subjects as $key => $label): ?>
                                <label class="choice" style="padding:9px 12px">
                                    <input type="checkbox" name="subjects[]" value="<?= H::e($key) ?>"
                                           <?= in_array($key, $chosen, true) ? 'checked' : '' ?>>
                                    <div class="choice__inner">
                                        <div class="choice__title" style="font-size:13px;margin:0"><?= H::e($label) ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button class="btn btn--primary" type="submit">
                        <?= Icon::get('shuffle', 16) ?>
                        <?= $missionCount > 0 ? 'Regenerate all cards' : 'Match mission cards' ?>
                    </button>

                    <?php if ($missionCount > 0): ?>
                        <span class="small muted" style="margin-left:10px">
                            Regenerating replaces every card, including any you edited yourself.
                        </span>
                    <?php endif; ?>
                </form>

            </div>
        </div>

        <?php if ($sample): ?>
            <div class="card">
                <div class="card__head">
                    <h3>A few of the cards</h3>
                    <a class="section__link" href="<?= Url::to('/studio/' . $pid) ?>">View and edit all</a>
                </div>
                <div class="card__body" style="padding:0">
                    <?php foreach ($sample as $m): ?>
                        <div class="mission-row">
                            <span class="mission-row__sticker">
                                <img src="<?= Url::to('art/sticker/' . rawurlencode($m['sticker']) . '.svg?size=20') ?>"
                                     alt="" width="20" height="20">
                            </span>
                            <div class="mission-row__body">
                                <div class="mission-row__q"><?= H::e($m['question']) ?></div>
                                <?php if (trim((string) $m['answer']) !== ''): ?>
                                    <div class="mission-row__a">Answer: <?= H::e($m['answer']) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="badge badge--tier nowrap">
                                Space <?= (int) $m['cell_no'] ?>
                                <?php if ($m['subject']): ?>
                                    &middot; <?= H::e(MissionMatcher::subjectLabel((string) $m['subject'])) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <aside class="wizard__side">
        <div class="card mb-2">
            <div class="card__body">
                <div class="bold mb-1" style="font-size:13px">How this works</div>
                <p class="small muted mb-1">
                    The library holds <b>15 base templates</b>. Each one has blanks for numbers and
                    words, such as "There are {a} rabbits and {b} hop away...".
                </p>
                <p class="small muted mb-0">
                    We draw random values for every blank, so 15 templates turn into thousands of
                    different questions and nothing repeats inside one game.
                </p>
            </div>
        </div>

        <form method="post" action="<?= Url::to('/create/' . $pid . '/step/4') ?>">
            <?= Csrf::field() ?>
            <button class="btn btn--primary btn--lg btn--block" type="submit">
                Continue <?= Icon::get('arrow-right', 17) ?>
            </button>
        </form>
        <a class="btn btn--ghost btn--block mt-1" href="<?= Url::to('/create/' . $pid . '/step/3') ?>">
            <?= Icon::get('arrow-left', 15) ?> Back
        </a>
    </aside>
</div>
