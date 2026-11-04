<?php

use MenuQr\Csrf;
use MenuQr\I18n;

$nav = [
    'dashboard' => '/admin',
    'availability' => '/admin/availability',
    'dishes' => '/admin/dishes',
    'categories' => '/admin/categories',
    'restaurant' => '/admin/restaurant',
    'customize' => '/admin/customize',
    'qr' => '/admin/qr',
    'backup' => '/admin/backup',
    'account' => '/admin/account',
];

?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($title ?? '') ?> · <?= e($restaurantName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body class="page-admin">
    <header class="admin-header">
        <div class="admin-header-inner">
            <a class="admin-brand" href="/admin">
                <span class="logo-mark logo-mark-small" aria-hidden="true">QR</span>
                <span class="admin-brand-name"><?= e($restaurantName) ?></span>
            </a>
            <div class="admin-header-actions">
                <a class="button button-small" href="/" target="_blank" rel="noopener"><?= e(t('nav.view_menu')) ?></a>
                <form method="post" action="/admin/logout">
                    <?= Csrf::field() ?>
                    <button type="submit" class="button button-small button-ghost"><?= e(t('nav.logout')) ?></button>
                </form>
            </div>
        </div>
        <nav class="admin-nav" aria-label="<?= e(t('nav.label')) ?>">
            <?php foreach ($nav as $key => $href): ?>
                <a href="<?= e($href) ?>"<?= $section === $key ? ' aria-current="page"' : '' ?>><?= e(t('nav.' . $key)) ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <main class="admin-main">
        <?php foreach ($flashes as $flash): ?>
            <p class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endforeach; ?>
        <?= $content ?>
    </main>

    <script src="<?= e(asset('/assets/js/admin.js')) ?>"></script>
</body>
</html>
