<?php
use App\Core\Helper as H;
use App\Core\Url;

$pageTitle = $pageTitle ?? 'Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= H::e($pageTitle) ?> · <?= H::e($appName ?? 'GameCraft Studio') ?></title>
<meta name="description" content="Design and export printable adventure board games for kids.">
<meta name="csrf-token" content="<?= H::e(\App\Core\Csrf::token()) ?>">
<link rel="icon" href="<?= Url::asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= Url::asset('css/app.css') ?>?v=<?= GC_VERSION ?>">
</head>
<body>

<div class="app">
    <?= \App\Core\View::partial('partials/sidebar') ?>

    <div class="main">
        <?= \App\Core\View::partial('partials/topbar') ?>

        <main class="content">
            <?= $content ?>
        </main>
    </div>
</div>

<?= \App\Core\View::partial('partials/flash') ?>

<script src="<?= Url::asset('js/app.js') ?>?v=<?= GC_VERSION ?>" defer></script>
</body>
</html>
