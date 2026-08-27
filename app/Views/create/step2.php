<?php
/** Step 2: choose from the library, filtered by plan entitlement (FR-29) */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Services\Library;

echo View::partial('partials/stepbar', compact('project', 'step', 'labels'));

/** Renders one group of picture choices */
$renderPicker = function (string $name, array $items, $currentId, string $emptyMsg, int $variant = 1) {
    if (!$items) {
        echo '<div class="notice notice--warning">' . Icon::get('alert', 17) . '<span>' . H::e($emptyMsg) . '</span></div>';
        return;
    }
    echo '<div class="pick-grid">';
    foreach ($items as $item) {
        $checked = (int) $currentId === (int) $item['id'];
        $hasArt  = Library::hasRealImage($item, $variant);
        echo '<label class="pick">';
        echo '<input type="radio" name="' . H::e($name) . '" value="' . (int) $item['id'] . '"' . ($checked ? ' checked' : '') . '>';
        echo '<div class="pick__art"><img src="' . H::e(Library::imageFor($item, $variant)) . '" alt="" loading="lazy"></div>';
        echo '<div class="pick__label">' . H::e(H::truncate($item['name'], 34));
        if (!$hasArt) {
            echo ' <span class="badge badge--tier" style="font-size:9px">placeholder</span>';
        }
        echo '</div></label>';
    }
    echo '</div>';
};
?>

<form method="post" action="<?= Url::to('/create/' . (int) $project['id'] . '/step/2') ?>">
    <?= Csrf::field() ?>

    <div class="wizard">
        <div class="wizard__main">

            <!-- Map frame -->
            <div class="card mb-2">
                <div class="card__head">
                    <h3>Map frame</h3>
                    <span class="small muted"><?= count($maps) ?> frames with <?= (int) $project['cells'] ?> spaces</span>
                </div>
                <div class="card__body">
                    <p class="small muted mb-2">
                        The frame decides how the <?= (int) $project['cells'] ?> mission spaces are laid out on the
                        printed page. Your background image goes behind it at the next step.
                    </p>

                    <?php $renderPicker('map_item_id', $maps, $project['map_item_id'],
                        'No map frames with this space count are available on your plan.'); ?>

                    <?php if ($lockedMaps > 0): ?>
                        <div class="notice notice--info mt-2" style="margin-bottom:0">
                            <?= Icon::get('lock', 17) ?>
                            <span>
                                <b><?= (int) $lockedMaps ?> more map frames</b> are available on higher plans.
                                <a href="<?= Url::to('/billing') ?>">Compare plans</a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Character set -->
            <div class="card mb-2">
                <div class="card__head">
                    <h3>Character set</h3>
                    <span class="small muted"><?= (int) $plan['character_poses'] ?> poses per set</span>
                </div>
                <div class="card__body">
                    <p class="small muted mb-2">
                        The character you choose appears on the winner hero card.
                    </p>
                    <?php $renderPicker('character_item_id', $characters, $project['character_item_id'],
                        'No character sets are available on your plan.'); ?>
                </div>
            </div>

            <!-- Move cards -->
            <div class="card mb-2">
                <div class="card__head">
                    <h3>Card style</h3>
                    <span class="small muted">Used for the move cards and the mission cards</span>
                </div>
                <div class="card__body">
                    <?php $renderPicker('move_item_id', $moves, $project['move_item_id'],
                        'No move card designs are available on your plan.'); ?>
                </div>
            </div>

        </div>

        <aside class="wizard__side">
            <div class="card mb-2">
                <div class="card__body">
                    <div class="small muted mb-1">Map preview</div>
                    <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line)">
                        <img src="<?= Url::to('art/map/' . (int) $project['id'] . '.svg?w=800&h=550') ?>"
                             alt="Map preview" style="width:100%;display:block">
                    </div>
                    <div class="small faint mt-1">
                        A <?= (int) $project['cells'] ?>-space map. The background here is a placeholder;
                        you replace it with your own at step 3.
                    </div>
                </div>
            </div>

            <button class="btn btn--primary btn--lg btn--block" type="submit">
                Save and continue <?= Icon::get('arrow-right', 17) ?>
            </button>
            <a class="btn btn--ghost btn--block mt-1"
               href="<?= Url::to('/create/' . (int) $project['id'] . '/step/1') ?>">
                <?= Icon::get('arrow-left', 15) ?> Back
            </a>
        </aside>
    </div>
</form>
