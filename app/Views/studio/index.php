<?php
/** The Studio - fine-tuning game content (FR-25, FR-26) */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Models\Project;
use App\Services\Difficulty;
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\PrintBundle;

$pid    = (int) $project['id'];
$status = H::statusBadge((string) $project['status']);
$diff   = Difficulty::get((string) $project['difficulty']);

// The questions the buyer has added themselves, on top of the dealt game
$extraQ = (string) ($project['extra_questions'] ?? '');
$extraA = (string) ($project['extra_answers'] ?? '');
$extraN = count(MissionMatcher::pairLines($extraQ, $extraA));
?>

<div class="section__head">
    <a class="btn btn--ghost btn--sm" href="<?= Url::to('/projects') ?>">
        <?= Icon::get('arrow-left', 14) ?> Projects
    </a>
    <h1 class="section__title" style="font-size:21px"><?= H::e($project['title']) ?></h1>
    <span class="badge <?= H::e($status['class']) ?>"><?= H::e($status['label']) ?></span>

    <div class="section__tools">
        <a class="btn btn--ghost btn--sm" href="<?= Url::to('/create/' . $pid . '/step/1') ?>">
            <?= Icon::get('settings', 14) ?> Setup
        </a>
        <a class="btn btn--ghost btn--sm" href="<?= Url::to('/preview/' . $pid) ?>">
            <?= Icon::get('eye', 14) ?> Preview
        </a>
        <a class="btn btn--primary btn--sm" href="<?= Url::to('/print/' . $pid) ?>" target="_blank" rel="noopener">
            <?= Icon::get('printer', 14) ?> Export for print
        </a>
    </div>
</div>

<?php if ($readiness): ?>
    <div class="notice notice--warning">
        <?= Icon::get('alert', 17) ?>
        <div>
            <div class="bold mb-1">Still missing before export</div>
            <ul style="margin:0;padding-left:18px">
                <?php foreach ($readiness as $issue): ?>
                    <li class="small"><?= H::e($issue) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="wizard">
    <div class="wizard__main">

        <!-- ===== Mission cards ===== -->
        <div class="card mb-2" id="missions">
            <div class="card__head">
                <h3>Mission cards</h3>
                <?php /* "63 of 60" would look like a fault - the 3 are the buyer's own */ ?>
                <span class="small muted">
                    <?php if ($missionCount < $expected): ?>
                        <?= (int) $missionCount ?> of <?= (int) $expected ?>
                    <?php elseif ($extraN): ?>
                        <?= (int) $expected ?> + <?= (int) $extraN ?> of your own
                    <?php else: ?>
                        <?= (int) $missionCount ?> of <?= (int) $expected ?>
                    <?php endif; ?>
                </span>
                <span class="spacer" style="flex:1"></span>
                <form method="post" action="<?= Url::to('/studio/' . $pid . '/regenerate') ?>"
                      data-confirm="Regenerate every mission card? Anything you edited yourself will be replaced. The questions you added below are kept.">
                    <?= Csrf::field() ?>
                    <button class="btn btn--ghost btn--sm" type="submit">
                        <?= Icon::get('shuffle', 14) ?> Regenerate all
                    </button>
                </form>
            </div>

            <?php if (!$missionCount): ?>
                <div class="card__body">
                    <div class="empty" style="border:0;padding:26px 10px">
                        <div class="empty__icon"><?= Icon::get('flag', 34) ?></div>
                        <div class="empty__title">No mission cards yet</div>
                        <div class="empty__desc">Generate them from the library using the subjects you chose.</div>
                        <form method="post" action="<?= Url::to('/studio/' . $pid . '/regenerate') ?>">
                            <?= Csrf::field() ?>
                            <button class="btn btn--primary" type="submit">
                                <?= Icon::get('sparkles', 16) ?> Match mission cards
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>

                <!-- One shared pile: a star space means "take the top card" -->
                <div class="card__body" style="padding-bottom:10px">
                    <div class="small muted mb-1">
                        All the cards are one pile - landing on a star space means drawing the top card.
                    </div>

                    <?php /* The one thing on this page worth interrupting the buyer for */ ?>
                    <div class="notice notice--warning mb-2">
                        <?= Icon::get('sparkles', 17) ?>
                        <span>
                            <b><i>Add a few questions of your own - it is the surest way to keep this
                            game from resembling anybody else's.</i></b>
                            The library deals different questions to every game, but your own -
                            this week's spelling words, the names in your class, a joke only your
                            family gets - can never turn up in someone else's copy.
                            <a href="#own-questions">Add your questions below</a>.
                        </span>
                    </div>
                    <?php if ($pageCount > 1): ?>
                        <div class="flex flex-wrap gap-1">
                            <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                                <?php
                                    $from = ($p - 1) * $perPage + 1;
                                    $to   = min($p * $perPage, (int) $missionCount);
                                ?>
                                <a class="chip <?= $p === $currentPage ? 'chip--active' : '' ?>"
                                   href="<?= Url::to('/studio/' . $pid) ?>?page=<?= $p ?>#missions">
                                    <?= $from ?>-<?= $to ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="border-top:1px solid var(--line)">
                    <?php foreach ($missions as $idx => $m): ?>
                        <?php $isCustom = ($m['source'] ?? '') === 'custom'; ?>
                        <?php $isOwn    = ($m['source'] ?? '') === 'extra'; ?>

                        <?php if ($isOwn): ?>
                            <?php /*
                                   * A card the buyer typed in the box below. The box is where it
                                   * lives, so it is not editable here - two places to change the
                                   * same words is how one of them ends up wrong.
                                   */ ?>
                            <div class="mission-row mission-row--custom"
                                 style="display:flex;gap:11px;align-items:flex-start">
                                <span class="mission-row__sticker">
                                    <img src="<?= Url::to('art/sticker/' . rawurlencode($m['sticker']) . '.svg?size=20') ?>"
                                         alt="" width="20" height="20">
                                </span>
                                <span class="mission-row__body">
                                    <span class="mission-row__q"><?= H::e($m['question']) ?></span>
                                    <span class="mission-row__a">
                                        <?= trim((string) $m['answer']) !== '' ? 'Answer: ' . H::e($m['answer']) : '' ?>
                                    </span>
                                </span>
                                <span class="mission-row__tools">
                                    <span class="badge badge--new">Your own</span>
                                    <a class="btn btn--ghost btn--sm" href="#own-questions">
                                        <?= Icon::get('edit', 14) ?> Edit
                                    </a>
                                </span>
                            </div>
                            <?php continue; ?>
                        <?php endif; ?>

                        <details class="mission-row <?= $isCustom ? 'mission-row--custom' : '' ?>" style="display:block">
                            <summary style="display:flex;gap:11px;align-items:flex-start;cursor:pointer;list-style:none">
                                <span class="mission-row__sticker">
                                    <img src="<?= Url::to('art/sticker/' . rawurlencode($m['sticker']) . '.svg?size=20') ?>"
                                         alt="" width="20" height="20">
                                </span>
                                <span class="mission-row__body">
                                    <span class="mission-row__q" data-mission-question><?= H::e($m['question']) ?></span>
                                    <span class="mission-row__a" data-mission-answer>
                                        <?= trim((string) $m['answer']) !== '' ? 'Answer: ' . H::e($m['answer']) : '' ?>
                                    </span>
                                </span>
                                <span class="mission-row__tools">
                                    <?php if ($isCustom): ?>
                                        <span class="badge badge--new">Your own</span>
                                    <?php endif; ?>
                                    <?php /* The row itself opens the form - this just says so */ ?>
                                    <span class="btn btn--ghost btn--sm mission-row__edit">
                                        <?= Icon::get('edit', 14) ?>
                                        <span class="mission-row__edit-open">Edit</span>
                                        <span class="mission-row__edit-close">Close</span>
                                    </span>
                                    <button type="button" class="btn btn--ghost btn--sm"
                                            data-reroll="<?= Url::to('/studio/' . $pid . '/mission/' . (int) $m['id'] . '/reroll') ?>"
                                            title="Swap for a different question">
                                        <?= Icon::get('refresh', 14) ?>
                                    </button>
                                </span>
                            </summary>

                            <!-- FR-25: write your own question and pick a sticker -->
                            <form method="post" action="<?= Url::to('/studio/' . $pid . '/mission/' . (int) $m['id']) ?>"
                                  style="padding:12px 0 4px 45px">
                                <?= Csrf::field() ?>

                                <div class="field" style="margin-bottom:10px">
                                    <label class="label" for="q<?= (int) $m['id'] ?>">Question</label>
                                    <textarea class="textarea" id="q<?= (int) $m['id'] ?>" name="question"
                                              maxlength="500" style="min-height:70px" required><?= H::e($m['question']) ?></textarea>
                                </div>

                                <div class="field" style="margin-bottom:10px">
                                    <label class="label" for="a<?= (int) $m['id'] ?>">Answer <span class="label__hint">(optional)</span></label>
                                    <input class="input" type="text" id="a<?= (int) $m['id'] ?>" name="answer"
                                           maxlength="500" value="<?= H::e($m['answer']) ?>">
                                </div>

                                <div class="field" style="margin-bottom:10px">
                                    <span class="label">Sticker</span>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($stickers as $key => $label): ?>
                                            <label class="pick" style="width:44px;border-radius:10px" title="<?= H::e($label) ?>">
                                                <input type="radio" name="sticker" value="<?= H::e($key) ?>"
                                                       <?= $m['sticker'] === $key ? 'checked' : '' ?>>
                                                <span style="display:grid;place-items:center;padding:7px">
                                                    <img src="<?= Url::to('art/sticker/' . rawurlencode($key) . '.svg?size=22') ?>"
                                                         alt="<?= H::e($label) ?>" width="22" height="22">
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button class="btn btn--primary btn--sm" type="submit">
                                    <?= Icon::get('check', 14) ?> Save this card
                                </button>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

        <!-- ===== Questions the buyer adds themselves ===== -->
        <div class="card mb-2" id="own-questions">
            <div class="card__head">
                <h3>Add your own questions</h3>
                <?php if ($extraN): ?>
                    <span class="small muted"><?= $extraN ?> in the pile</span>
                <?php endif; ?>
            </div>
            <div class="card__body">

                <form method="post" action="<?= Url::to('/studio/' . $pid . '/questions') ?>">
                    <?= Csrf::field() ?>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="extra_questions">
                                Questions <span class="label__hint">(one per line)</span>
                            </label>
                            <textarea class="textarea" id="extra_questions" name="extra_questions"
                                      style="min-height:190px;line-height:1.9"
                                      placeholder="How do you spell &quot;necessary&quot;?&#10;What is 7 x 8?&#10;Name three rivers in France"><?= H::e($extraQ) ?></textarea>
                        </div>
                        <div class="field">
                            <label class="label" for="extra_answers">
                                Answers <span class="label__hint">(one per line, same order)</span>
                            </label>
                            <textarea class="textarea" id="extra_answers" name="extra_answers"
                                      style="min-height:190px;line-height:1.9"
                                      placeholder="necessary&#10;56&#10;Any three real rivers"><?= H::e($extraA) ?></textarea>
                        </div>
                    </div>

                    <p class="small muted mb-2">
                        The third line of the answers box answers the third line of the questions
                        box, and so on. Leave an answer line empty for a question that is answered
                        out loud. These cards are <b>added</b> to the mission pile, so nothing you
                        have already edited is replaced, and they print with everything else -
                        answers on the key at the back. Up to <?= MissionMatcher::MAX_EXTRA_QUESTIONS ?> questions.
                    </p>

                    <button class="btn btn--primary" type="submit">
                        <?= Icon::get('check', 16) ?> Save my questions
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== Story and rules ===== -->
        <div class="card mb-2" id="content">
            <div class="card__head"><h3>Story &amp; rules</h3></div>
            <div class="card__body">
                <form method="post" action="<?= Url::to('/studio/' . $pid . '/content') ?>">
                    <?= Csrf::field() ?>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="title">Game title</label>
                            <input class="input" type="text" id="title" name="title" maxlength="160"
                                   value="<?= H::e($project['title']) ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="hero_name">Hero name <span class="label__hint">(shown on the winner card)</span></label>
                            <input class="input" type="text" id="hero_name" name="hero_name" maxlength="120"
                                   value="<?= H::e($project['hero_name']) ?>" placeholder="For example: Maya the Explorer">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="story">
                            Story <span class="label__hint">(yours - written with the prompt at step 3)</span>
                        </label>
                        <textarea class="textarea" id="story" name="story" maxlength="8000"
                                  style="min-height:170px"
                                  data-word-count="#studio-story-words"
                                  data-word-limit="<?= PrintBundle::STORY_WORDS_PER_SHEET ?>"
                                  placeholder="Empty for now. The game prints without a story page until you write one."><?= H::e($project['story']) ?></textarea>
                        <div class="small muted mt-1" id="studio-story-words"></div>
                    </div>

                    <div class="field">
                        <label class="label" for="how_to_play">How to play</label>
                        <textarea class="textarea" id="how_to_play" name="how_to_play" maxlength="4000"
                                  style="min-height:150px"
                                  placeholder="The rules, one step per line..."><?= H::e($project['how_to_play']) ?></textarea>
                    </div>

                    <div class="flex gap-1 flex-wrap">
                        <button class="btn btn--primary" type="submit">
                            <?= Icon::get('check', 16) ?> Save content
                        </button>
                        <?php /* The rules can be rebuilt from the game; the story cannot -
                                 it is the buyer's, written with the prompt at step 3. */ ?>
                        <button class="btn btn--ghost" type="submit" name="use_suggestion" value="1"
                                data-confirm="Replace the rules with the standard ones for this game?">
                            <?= Icon::get('refresh', 16) ?> Reset the rules
                        </button>
                        <a class="btn btn--ghost" href="<?= Url::to('/create/' . $pid . '/step/3') ?>#story">
                            <?= Icon::get('sparkles', 16) ?> Story prompt
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== Players ===== -->
        <div class="card" id="players">
            <div class="card__head">
                <h3>Player tokens</h3>
                <span class="small muted">Up to <?= Project::MAX_PLAYERS ?> players</span>
            </div>
            <div class="card__body">
                <form method="post" action="<?= Url::to('/studio/' . $pid . '/players') ?>">
                    <?= Csrf::field() ?>

                    <?php for ($i = 0; $i < Project::MAX_PLAYERS; $i++): ?>
                        <?php
                        $p = $players[$i] ?? null;
                        $name  = $p['name']  ?? '';
                        $color = $p['color'] ?? array_keys($tokenColors)[$i % count($tokenColors)];
                        ?>
                        <div class="flex items-center gap-1 mb-1">
                            <img src="<?= Url::to('art/token/' . rawurlencode($color) . '.svg?size=32&label=' . rawurlencode(mb_substr($name ?: (string) ($i + 1), 0, 1))) ?>"
                                 alt="" width="32" height="32" style="flex:0 0 auto">
                            <input class="input" type="text" name="player_name[]" maxlength="60"
                                   value="<?= H::e($name) ?>" placeholder="Player <?= $i + 1 ?> (leave blank to skip)">
                            <select class="select" name="player_color[]" style="width:130px;flex:0 0 auto">
                                <?php foreach ($tokenColors as $key => $hex): ?>
                                    <option value="<?= H::e($key) ?>" <?= $color === $key ? 'selected' : '' ?>>
                                        <?= H::e(ucfirst($key)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endfor; ?>

                    <button class="btn btn--primary mt-1" type="submit">
                        <?= Icon::get('check', 16) ?> Save players
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- ===== Right column ===== -->
    <aside class="wizard__side">

        <div class="card mb-2">
            <div class="card__body">
                <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line)">
                    <img src="<?= Url::to('art/map/' . $pid . '.svg?w=800&h=550') ?>" alt="Game map"
                         style="width:100%;display:block">
                </div>
                <div class="small muted mt-2"><b><?= (int) $progress ?>%</b> complete</div>
                <div class="bar"><div class="bar__fill" style="width:<?= (int) $progress ?>%"></div></div>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__head" style="padding:12px 16px"><h4>Components in use</h4></div>
            <div class="card__body" style="padding-top:10px">
                <?php
                $labels = [
                    'map'       => 'Map frame',
                    'character' => 'Character set',
                    'move'      => 'Move cards',
                    'reward'    => 'Hero card',
                ];
                ?>
                <?php foreach ($labels as $key => $label): ?>
                    <?php $item = $items[$key] ?? null; ?>
                    <div class="flex items-center gap-1 mb-1">
                        <div style="width:38px;height:30px;border-radius:6px;overflow:hidden;flex:0 0 auto;background:var(--bg-sunken);border:1px solid var(--line)">
                            <?php if ($item): ?>
                                <img src="<?= H::e(Library::imageFor($item)) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                            <?php endif; ?>
                        </div>
                        <div style="min-width:0;flex:1">
                            <div class="small faint"><?= H::e($label) ?></div>
                            <div class="small bold" style="line-height:1.25">
                                <?= $item ? H::e(H::truncate($item['name'], 24)) : '<span class="faint">Not chosen</span>' ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <a class="btn btn--ghost btn--sm btn--block mt-1" href="<?= Url::to('/create/' . $pid . '/step/2') ?>">
                    <?= Icon::get('palette', 14) ?> Change components
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card__body">
                <table class="data" style="font-size:12.5px">
                    <tr><td class="muted" style="padding-left:0">Difficulty</td><td class="right"><?= H::e($diff['name']) ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Spaces</td><td class="right"><?= (int) $project['cells'] ?></td></tr>
                    <tr><td class="muted" style="padding-left:0">Mission cards</td><td class="right"><?= (int) $missionCount ?></td></tr>
                    <tr>
                        <td class="muted" style="padding-left:0;border-bottom:0">Subjects</td>
                        <td class="right" style="border-bottom:0">
                            <?= H::e(implode(', ', array_map([MissionMatcher::class, 'subjectLabel'], $subjects))) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </aside>
</div>
