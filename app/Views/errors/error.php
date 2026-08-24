<?php
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
?>
<div class="empty" style="max-width:520px;margin:60px auto">
    <div class="empty__icon"><?= Icon::get('alert', 40) ?></div>
    <div class="empty__title"><?= (int) $status ?> &middot; <?= H::e($title) ?></div>
    <?php if (!empty($message)): ?>
        <div class="empty__desc"><?= H::e($message) ?></div>
    <?php endif; ?>
    <a class="btn btn--primary" href="<?= Url::to('/') ?>"><?= Icon::get('home', 16) ?> Back to dashboard</a>
</div>
