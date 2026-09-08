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

$value = Lang::normalize($value ?? null);
?>
<div class="field">
    <label class="label" for="language">What language is the game printed in?</label>
    <select class="select" id="language" name="language">
        <?php foreach (Lang::LOCALES as $code => $name): ?>
            <option value="<?= H::e($code) ?>" <?= $value === $code ? 'selected' : '' ?>>
                <?= H::e($name) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="field__hint">
        The questions, the rules and the story are printed in this language. The Studio itself stays in English.
    </div>
</div>
