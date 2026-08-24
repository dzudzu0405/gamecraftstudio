<?php
use App\Core\Helper as H;
use App\Core\Url;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= H::e($pageTitle ?? 'File in') ?> · GameCraft Studio</title>
<link rel="icon" href="<?= Url::asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= Url::asset('css/print.css') ?>?v=<?= GC_VERSION ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
