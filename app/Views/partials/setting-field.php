<?php
/**
 * The adventure picker: twenty to choose from, or write your own.
 *
 * Shared by the first screen and step 1 of the wizard so the two cannot drift
 * apart. $value is whatever is stored - one of the twenty, something the buyer
 * typed, or nothing at all.
 *
 * The "write your own" box is posted alongside the select and only read when
 * the select says to, so the field still works with JavaScript turned off:
 * the box is simply always visible then.
 */

use App\Core\Helper as H;
use App\Models\Project;

$value  = trim((string) ($value ?? ''));
$listed = Project::isListedSetting($value);
$custom = $listed ? '' : $value;
?>
<div class="field" data-setting>
    <label class="label" for="setting">What kind of adventure is it?</label>

    <select class="select" id="setting" name="setting" data-setting-select>
        <option value="">Choose an adventure ...</option>
        <?php foreach (array_keys(Project::SETTINGS) as $name): ?>
            <option value="<?= H::e($name) ?>" <?= $value === $name ? 'selected' : '' ?>>
                <?= H::e($name) ?>
            </option>
        <?php endforeach; ?>
        <option value="<?= Project::SETTING_OTHER ?>" <?= $custom !== '' ? 'selected' : '' ?>>
            Other - write my own
        </option>
    </select>

    <div class="field mt-1" data-setting-other <?= $custom !== '' ? '' : 'hidden' ?>>
        <input class="input" type="text" name="setting_other" maxlength="120"
               value="<?= H::e($custom) ?>"
               placeholder="a prehistoric valley with volcanoes, ...">
        <div class="field__hint">Describe where your adventure happens, in your own words.</div>
    </div>
</div>
