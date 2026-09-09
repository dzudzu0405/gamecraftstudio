<?php
/**
 * Which language the printed game comes out in.
 *
 * Not the language of the Studio - the language of the cards, the rules and the
 * story, which is what the child at the table reads. Shared by the first screen
 * and step 1 so the two cannot drift apart.
 */

use App\Core\Helper as H;
use App\Services\Lang;

/*
 * Nothing is pre-selected on a new game: the buyer picks the language rather
 * than finding English already chosen for them. An existing project keeps
 * whatever it was saved with.
 */
$chosen = trim((string) ($value ?? ''));
$chosen = Lang::supported($chosen) ? Lang::normalize($chosen) : '';
?>
<div class="field">
    <label class="label" for="language">What language is the game printed in?</label>
    <select class="select" id="language" name="language" required>
        <option value="">Choose a language ...</option>
        <?php foreach (Lang::LOCALES as $code => $name): ?>
            <option value="<?= H::e($code) ?>" <?= $chosen === $code ? 'selected' : '' ?>>
                <?= H::e($name) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="field__hint">
        The questions, the rules and the story are printed in this language. The Studio itself stays in English.
    </div>
</div>
