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
use App\Models\Project;
use App\Services\MissionMatcher;

echo View::partial('partials/stepbar', compact('project', 'step', 'labels'));

$pid          = (int) $project['id'];
$complete     = $missionCount >= $expected;
$ownQuestions = Project::usesOwnQuestions($project);
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
                        <?php $ownAdded = count(MissionMatcher::pairLines(
                            (string) ($project['extra_questions'] ?? ''),
                            (string) ($project['extra_answers'] ?? '')
                        )); ?>
                        <div class="stat__sub">
                            <?= $ownAdded ? 'one pile, ' . $ownAdded . ' of them yours' : 'one shared pile' ?>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat__label">Matching templates</div>
                        <div class="stat__value"><?= count($templates) ?></div>
                        <div class="stat__sub">
                            <?= number_format($shapes) ?> question shapes &middot;
                            <?= number_format($variants) ?> distinct questions
                        </div>
                    </div>
                </div>

                <?php if ($ownQuestions): ?>
                    <?php /* Nothing here is about the library when the buyer brought their own */ ?>
                <?php elseif (!$templates): ?>
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
                            Some questions will repeat word for word - add another subject to avoid that.
                        </span>
                    </div>
                <?php elseif ($shapes < $expected): ?>
                    <?php /*
                     * Every card can be a different question and the game can
                     * still feel repetitive, because a child recognises the
                     * SHAPE of a sentence long before the numbers in it. This
                     * says how often a shape will come round, and what to do.
                     */ ?>
                    <div class="notice notice--info">
                        <?= Icon::get('alert', 17) ?>
                        <span>
                            Every card will be a different question, but these subjects have
                            <b><?= number_format($shapes) ?></b> ways of asking one, so the same kind of
                            question comes round about <b><?= (int) ceil($expected / max(1, $shapes)) ?> times</b>
                            across <?= (int) $expected ?> cards. Adding a subject brings that down.
                        </span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Url::to('/create/' . $pid . '/generate') ?>"
                      data-question-source data-cards-needed="<?= (int) $expected ?>">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <span class="label">Where do the questions come from?</span>
                        <div class="choice-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
                            <label class="choice">
                                <input type="radio" name="question_source" value="<?= Project::QUESTIONS_LIBRARY ?>"
                                       <?= $ownQuestions ? '' : 'checked' ?> data-source-radio>
                                <div class="choice__inner">
                                    <div class="choice__title">The question library</div>
                                    <div class="choice__desc">
                                        Written for you from the subjects you pick, and matched to the level.
                                    </div>
                                </div>
                            </label>
                            <label class="choice">
                                <input type="radio" name="question_source" value="<?= Project::QUESTIONS_OWN ?>"
                                       <?= $ownQuestions ? 'checked' : '' ?> data-source-radio>
                                <div class="choice__inner">
                                    <div class="choice__title">My own questions</div>
                                    <div class="choice__desc">
                                        Type or paste your own - your spelling list, this term's topic, anything.
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <?php /* --- the library's own settings --- */ ?>
                    <div data-source-panel="<?= Project::QUESTIONS_LIBRARY ?>" <?= $ownQuestions ? 'hidden' : '' ?>>
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
                    </div>

                    <?php /* --- or the buyer's own list --- */ ?>
                    <div data-source-panel="<?= Project::QUESTIONS_OWN ?>" <?= $ownQuestions ? '' : 'hidden' ?>>
                        <div class="field">
                            <label class="label" for="own_questions">Your questions, one per line</label>
                            <textarea class="input" id="own_questions" name="own_questions" rows="10"
                                      style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;line-height:1.7"
                                      data-own-questions
                                      placeholder="What is 7 x 8?&#9;56&#10;Name three rivers in France | Any three real rivers&#10;How do you spell &quot;necessary&quot;?"><?= H::e((string) ($project['own_questions'] ?? '')) ?></textarea>
                            <div class="field__hint">
                                Put the answer after a tab or a <code>|</code> if you want one on the answer key -
                                so two columns pasted from a spreadsheet come out right. A question with no answer
                                is fine; whoever is running the game decides.
                            </div>
                            <div class="small muted mt-1" data-own-count>&nbsp;</div>
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

                    <?php /* The questions are put together by machine, so somebody has to read them */ ?>
                    <div class="notice notice--info" style="margin:14px 16px 4px">
                        <?= Icon::get('alert', 17) ?>
                        <span><b><i>Please read through the mission questions before you print.
                        They are generated automatically, so a card can come out odd or wrong -
                        open the Studio to edit or swap any you are not happy with.</i></b></span>
                    </div>

                    <?php foreach ($sample as $idx => $m): ?>
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
                                Card <?= (int) $idx + 1 ?>
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
