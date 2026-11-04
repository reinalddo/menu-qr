<?php

use MenuQr\I18n;

$description = ($s['tagline'] ?? '') !== '' ? $s['tagline'] : t('menu.meta_description', ['name' => $s['restaurant_name'] ?? '']);

?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> · <?= e(t('menu.title')) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="<?= e($brand) ?>">
    <link rel="canonical" href="<?= e($menuUrl) ?>/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($title) ?> · <?= e(t('menu.title')) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($menuUrl) ?>/">
    <?php if ($shareImage): ?>
        <meta property="og:image" content="<?= e($shareImage) ?>">
        <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/menu.css')) ?>">
    <style>
        :root { --brand: <?= e($brand) ?>; --brand-text: <?= e($brandText) ?>; --brand-ink: <?= e($brandInk) ?>; }
    </style>
</head>
<body>
    <?= $content ?>
    <script src="<?= e(asset('/assets/js/menu.js')) ?>"></script>
</body>
</html>
