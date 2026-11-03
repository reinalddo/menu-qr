<div class="simple-head">
    <span class="logo-mark logo-mark-ok" aria-hidden="true">✓</span>
    <h1><?= e(t('install.done.title')) ?></h1>
</div>

<p><?= e(t('install.done.text')) ?></p>
<p class="url-box"><?= e($menuUrl) ?></p>
<p class="muted"><?= e(t('install.done.next')) ?></p>

<a class="button button-primary" href="/admin"><?= e(t('install.done.go_admin')) ?></a>
<p class="center-link"><a href="/" target="_blank" rel="noopener"><?= e(t('install.done.view_menu')) ?></a></p>
