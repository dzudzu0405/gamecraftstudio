<?php
/**
 * Wizard progress bar. Expects: $project, $step, $labels
 *
 * $labels is keyed by the real step number, and a themed game has no step 3,
 * so the numbers shown are counted off separately from the keys.
 */
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$reached = (int) ($project['wizard_step'] ?? 1);
?>
<div class="section__head" style="margin-bottom:10px">
    <a class="btn btn--ghost btn--sm" href="<?= Url::to('/projects') ?>">
        <?= Icon::get('arrow-left', 14) ?> Projects
    </a>
    <h1 class="section__title" style="font-size:20px"><?= H::e($project['title']) ?></h1>
    <span class="badge badge--tier"><?= (int) $project['cells'] ?> spaces</span>
</div>

<nav class="stepbar" aria-label="Game creation steps">
    <?php $shown = 0; ?>
    <?php foreach ($labels as $n => $label): ?>
        <?php
        $shown++;
        $isActive = $n === $step;
        $isDone   = $n < $step || ($n < $reached && !$isActive);
        $cls = $isActive ? 'stepbar__item--active' : ($isDone ? 'stepbar__item--done' : '');
        $canGo = $n <= max($reached, $step);
        ?>
        <?php if ($canGo && !$isActive): ?>
            <a class="stepbar__item <?= $cls ?>" href="<?= Url::to('/create/' . (int) $project['id'] . '/step/' . $n) ?>">
                <span class="n"><?= $isDone ? '&#10003;' : $shown ?></span>
                <span><?= H::e($label) ?></span>
            </a>
        <?php else: ?>
            <span class="stepbar__item <?= $cls ?>" <?= $isActive ? 'aria-current="step"' : '' ?>>
                <span class="n"><?= $isDone ? '&#10003;' : $shown ?></span>
                <span><?= H::e($label) ?></span>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
