<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$errors = Flash::errors();
Flash::clearErrors();
$oldSubjects = Flash::old('subjects', ['math', 'nature']);
if (!is_array($oldSubjects)) {
    $oldSubjects = ['math', 'nature'];
}
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Create New Game</h1>
    <span class="badge badge--tier">Step 1 of 5</span>
</div>

<form method="post" action="<?= Url::to('/create') ?>">
    <?= Csrf::field() ?>

    <div class="wizard">
        <div class="wizard__main">

            <div class="card mb-2">
                <div class="card__head"><h3>Game details</h3></div>
                <div class="card__body">

                    <div class="field">
                        <label class="label" for="title">Game title</label>
                        <input class="input <?= isset($errors['title']) ? 'input--error' : '' ?>"
                               type="text" id="title" name="title" maxlength="160" required autofocus
                               value="<?= H::e(Flash::old('title')) ?>"
                               placeholder="For example: Dinosaur Rescue Mission">
                        <?php if (isset($errors['title'])): ?>
                            <div class="field__error"><?= H::e($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label class="label" for="players_min">Minimum players</label>
                            <select class="select" id="players_min" name="players_min">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int) Flash::old('players_min', 2) === $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label" for="players_max">Maximum players</label>
                            <select class="select" id="players_max" name="players_max">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int) Flash::old('players_max', 4) === $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Difficulty sets the map size and mission card count (SRS section 10) -->
            <div class="card mb-2">
                <div class="card__head">
                    <h3>Difficulty</h3>
                    <span class="small muted">Sets the map size and how many mission cards you get</span>
                </div>
                <div class="card__body">
                    <div class="choice-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                        <?php foreach ($difficulties as $key => $d): ?>
                            <?php $locked = !in_array($key, $allowed, true); ?>
                            <label class="choice">
                                <input type="radio" name="difficulty" value="<?= H::e($key) ?>"
                                       <?= Flash::old('difficulty', 'standard') === $key ? 'checked' : '' ?>
                                       <?= $locked ? 'disabled' : '' ?>>
                                <div class="choice__inner">
                                    <div class="choice__title">
                                        <?= H::e($d['name']) ?>
                                        <?php if ($locked): ?>
                                            <span class="badge badge--locked"><?= Icon::get('lock', 10) ?> Pro plan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="choice__desc">
                                        <?= (int) $d['cells'] ?>-space map &middot; <?= (int) $d['mission_cards'] ?> mission cards<br>
                                        Ages <?= H::e($d['ages']) ?> &middot; <?= H::e($d['play_minutes']) ?> minutes
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['difficulty'])): ?>
                        <div class="field__error"><?= H::e($errors['difficulty']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FR-23: question subjects -->
            <div class="card mb-2">
                <div class="card__head">
                    <h3>Question subjects</h3>
                    <span class="small muted">Pick at least one</span>
                </div>
                <div class="card__body">
                    <p class="small muted mb-2">
                        We match mission cards from the library against the subjects you choose here.
                    </p>
                    <div class="choice-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
                        <?php foreach ($subjects as $key => $label): ?>
                            <label class="choice" style="padding:10px 12px">
                                <input type="checkbox" name="subjects[]" value="<?= H::e($key) ?>"
                                       <?= in_array($key, $oldSubjects, true) ? 'checked' : '' ?>>
                                <div class="choice__inner">
                                    <div class="choice__title" style="font-size:13px;margin:0"><?= H::e($label) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['subjects'])): ?>
                        <div class="field__error"><?= H::e($errors['subjects']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Scene theme -->
            <div class="card">
                <div class="card__head"><h3>Theme</h3></div>
                <div class="card__body">
                    <div class="pick-grid">
                        <?php foreach ($themes as $key => $label): ?>
                            <label class="pick">
                                <input type="radio" name="theme" value="<?= H::e($key) ?>"
                                       <?= Flash::old('theme', 'forest') === $key ? 'checked' : '' ?>>
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
            <div class="card">
                <div class="card__body">
                    <div class="flex items-center gap-1 mb-2">
                        <span style="color:<?= H::e($plan['color']) ?>"><?= Icon::get('crown', 18) ?></span>
                        <b><?= H::e($plan['name']) ?> Plan</b>
                    </div>
                    <ul class="perks" style="margin-bottom:12px">
                        <li><?= Icon::get('check', 14) ?> <?= (int) $plan['maps_total'] ?> map frames</li>
                        <li><?= Icon::get('check', 14) ?> <?= (int) $plan['character_sets'] ?> character sets</li>
                        <li><?= Icon::get('check', 14) ?> <?= (int) $plan['reward_cards'] ?> hero cards</li>
                    </ul>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= Url::to('/billing') ?>">Compare plans</a>
                </div>
            </div>

            <button class="btn btn--primary btn--lg btn--block mt-2" type="submit">
                Continue <?= Icon::get('arrow-right', 17) ?>
            </button>
            <a class="btn btn--ghost btn--block mt-1" href="<?= Url::to('/') ?>">Cancel</a>
        </aside>
    </div>

</form>
