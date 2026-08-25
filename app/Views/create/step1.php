<?php
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Models\Project;

echo View::partial('partials/stepbar', compact('project', 'step', 'labels'));
?>

<form method="post" action="<?= Url::to('/create/' . (int) $project['id'] . '/step/1') ?>">
    <?= Csrf::field() ?>

    <div class="wizard">
        <div class="wizard__main">

            <div class="card mb-2">
                <div class="card__head"><h3>Game details</h3></div>
                <div class="card__body">
                    <div class="field">
                        <label class="label" for="title">Who does the game rescue?</label>
                        <input class="input" type="text" id="title" name="title" maxlength="160" required
                               value="<?= H::e($project['title']) ?>"
                               placeholder="Dinosaur, ...">
                    </div>

                    <div class="field">
                        <label class="label" for="setting">Where does the adventure take place?</label>
                        <input class="input" type="text" id="setting" name="setting" maxlength="120"
                               value="<?= H::e($project['setting'] ?? '') ?>"
                               placeholder="outer space, desert, city, ...">
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="players_min">Minimum players</label>
                            <select class="select" id="players_min" name="players_min">
                                <?php for ($i = Project::MIN_PLAYERS; $i <= Project::MAX_PLAYERS; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int) $project['players_min'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label" for="players_max">Maximum players</label>
                            <select class="select" id="players_max" name="players_max">
                                <?php for ($i = Project::MIN_PLAYERS; $i <= Project::MAX_PLAYERS; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int) $project['players_max'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card__head"><h3>Difficulty</h3></div>
                <div class="card__body">

                    <?php if ($missionCount > 0): ?>
                        <div class="notice notice--warning">
                            <?= Icon::get('alert', 17) ?>
                            <span>
                                This project has <b><?= (int) $missionCount ?> mission cards</b>.
                                Changing the difficulty deletes them, and they will need regenerating at step 4.
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="choice-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                        <?php foreach ($difficulties as $key => $d): ?>
                            <?php $locked = !in_array($key, $allowed, true); ?>
                            <label class="choice">
                                <input type="radio" name="difficulty" value="<?= H::e($key) ?>"
                                       <?= $project['difficulty'] === $key ? 'checked' : '' ?>
                                       <?= $locked ? 'disabled' : '' ?>>
                                <div class="choice__inner">
                                    <div class="choice__title">
                                        <?= H::e($d['name']) ?>
                                        <?php if ($locked): ?>
                                            <span class="badge badge--locked"><?= Icon::get('lock', 10) ?> Pro plan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="choice__desc">
                                        <?= (int) $d['cells'] ?>-space map &middot; <?= (int) $d['mission_cards'] ?> mission cards
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card__head"><h3>Question subjects</h3></div>
                <div class="card__body">
                    <div class="choice-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
                        <?php foreach ($subjects as $key => $label): ?>
                            <label class="choice" style="padding:10px 12px">
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

            <div class="card">
                <div class="card__head">
                    <h3>Theme</h3>
                    <span class="small muted">Pick a ready-made theme and you can skip making a background later</span>
                </div>
                <div class="card__body">
                    <div class="pick-grid">
                        <?php foreach ($themes as $key => $label): ?>
                            <label class="pick">
                                <input type="radio" name="theme" value="<?= H::e($key) ?>"
                                       <?= $project['theme'] === $key ? 'checked' : '' ?>>
                                <div class="pick__art">
                                    <img src="<?= Url::to('art/scene/' . rawurlencode($key) . '/theme-' . rawurlencode($key) . '.svg?w=300&h=225') ?>"
                                         alt="" loading="lazy" width="300" height="225">
                                </div>
                                <div class="pick__label"><?= H::e($label) ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <aside class="wizard__side">
            <button class="btn btn--primary btn--lg btn--block" type="submit">
                Save and continue <?= Icon::get('arrow-right', 17) ?>
            </button>
            <a class="btn btn--ghost btn--block mt-1" href="<?= Url::to('/studio/' . (int) $project['id']) ?>">
                Open the Studio
            </a>
        </aside>
    </div>
</form>
