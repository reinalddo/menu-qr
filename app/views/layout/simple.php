<?php

use MenuQr\I18n;

?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? 'menu-qr') ?></title>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body class="page-simple">
    <main class="simple-card">
        <?= $content ?>
    </main>
    <p class="simple-footer">menu-qr <?= e(APP_VERSION) ?></p>
</body>
</html>
