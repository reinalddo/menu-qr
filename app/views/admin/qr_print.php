<?php

use MenuQr\I18n;

?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> · <?= e($restaurantName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/qr-print.css')) ?>">
    <style>:root { --brand: <?= e($brand) ?>; --brand-text: <?= e($brandText) ?>; }</style>
</head>
<body class="per-page-<?= (int) $perPage ?>">
    <div class="toolbar">
        <p><?= e(t('qr.print.help')) ?></p>
        <button type="button" data-print><?= e(t('qr.print.button')) ?></button>
    </div>
    <main class="sheet">
        <?php for ($i = 0; $i < $perPage; $i++): ?>
            <article class="card">
                <?php if ($logo): ?>
                    <img class="card-logo" src="<?= e($logo) ?>" alt="">
                <?php endif; ?>
                <h1 class="card-name"><?= e($restaurantName) ?></h1>
                <p class="card-call"><?= e(t('qr.print.call')) ?></p>
                <img class="card-qr" src="<?= e($svg) ?>" alt="<?= e(t('qr.alt')) ?>">
                <p class="card-url"><?= e($menuUrl) ?></p>
            </article>
        <?php endfor; ?>
    </main>
    <script src="<?= e(asset('/assets/js/qr-print.js')) ?>"></script>
</body>
</html>
