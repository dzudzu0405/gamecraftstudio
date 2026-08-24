<?php
use App\Core\Flash;
use App\Core\Helper as H;
use App\Core\Icon;

$messages = Flash::take();
if (!$messages) {
    return;
}

$icons = [
    'success' => 'check-circle',
    'error'   => 'alert',
    'warning' => 'alert',
    'info'    => 'info',
];
?>
<div class="flash-stack" data-flash-stack aria-live="polite">
    <?php foreach ($messages as $m): ?>
        <div class="flash flash--<?= H::e($m['type']) ?>">
            <?= Icon::get($icons[$m['type']] ?? 'info', 17) ?>
            <span><?= H::e($m['message']) ?></span>
            <button type="button" class="flash__close" data-flash-close aria-label="Dismiss">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
