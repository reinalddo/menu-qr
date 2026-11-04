<h1 class="page-title"><?= e(t('nav.qr')) ?></h1>
<p class="muted"><?= e(t('qr.intro')) ?></p>

<?php if ($localWarning): ?>
    <p class="alert alert-warning">
        <?= e(t('restaurant.menu_url.warning')) ?>
        <a href="/admin/restaurant#menu_url"><?= e(t('dashboard.change_address')) ?></a>
    </p>
<?php endif; ?>

<section class="card qr-card">
    <img class="qr-image" src="/admin/qr.svg" alt="<?= e(t('qr.alt')) ?>" width="240" height="240">
    <div class="qr-details">
        <p class="help"><?= e(t('qr.points_to')) ?></p>
        <p class="url-box"><?= e($target) ?></p>
        <div class="actions">
            <a class="button button-primary" href="/admin/qr/print" target="_blank" rel="noopener"><?= e(t('qr.print_one')) ?></a>
            <a class="button" href="/admin/qr/print?per_page=4" target="_blank" rel="noopener"><?= e(t('qr.print_four')) ?></a>
        </div>
        <div class="actions">
            <a class="button button-small" href="/admin/qr.png?download=1"><?= e(t('qr.download_png')) ?></a>
            <a class="button button-small" href="/admin/qr.svg?download=1"><?= e(t('qr.download_svg')) ?></a>
        </div>
        <p class="help"><?= e(t('qr.tip')) ?></p>
    </div>
</section>
