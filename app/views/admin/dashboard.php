<h1 class="page-title"><?= e(t('dashboard.hello')) ?></h1>

<section class="stat-grid" aria-label="<?= e(t('dashboard.summary')) ?>">
    <a class="stat" href="/admin/categories">
        <span class="stat-value"><?= e($counts['categories']) ?></span>
        <span class="stat-label"><?= e(t('nav.categories')) ?></span>
    </a>
    <a class="stat" href="/admin/dishes">
        <span class="stat-value"><?= e($counts['dishes']) ?></span>
        <span class="stat-label"><?= e(t('nav.dishes')) ?></span>
    </a>
    <a class="stat<?= $counts['sold_out'] > 0 ? ' stat-alert' : '' ?>" href="/admin/availability">
        <span class="stat-value"><?= e($counts['sold_out']) ?></span>
        <span class="stat-label"><?= e(t('dashboard.sold_out')) ?></span>
    </a>
    <a class="stat" href="/admin/dishes">
        <span class="stat-value"><?= e($counts['hidden']) ?></span>
        <span class="stat-label"><?= e(t('dashboard.hidden')) ?></span>
    </a>
</section>

<?php if ($counts['categories'] === 0): ?>
    <section class="card card-highlight">
        <h2><?= e(t('dashboard.start.title')) ?></h2>
        <ol class="steps">
            <li><?= e(t('dashboard.start.step1')) ?></li>
            <li><?= e(t('dashboard.start.step2')) ?></li>
            <li><?= e(t('dashboard.start.step3')) ?></li>
        </ol>
        <a class="button button-primary" href="/admin/categories"><?= e(t('dashboard.start.cta')) ?></a>
    </section>
<?php elseif ($counts['dishes'] === 0): ?>
    <section class="card card-highlight">
        <h2><?= e(t('dashboard.no_dishes.title')) ?></h2>
        <p><?= e(t('dashboard.no_dishes.text')) ?></p>
        <a class="button button-primary" href="/admin/dishes/new"><?= e(t('dishes.new')) ?></a>
    </section>
<?php endif; ?>

<?php if ($secondCurrency !== null): ?>
    <section class="card">
        <h2><?= e(t('dashboard.rate.title', ['code' => $secondCurrency['code']])) ?></h2>
        <form method="post" action="/admin/customize/rate" class="form inline-form">
            <?= \MenuQr\Csrf::field() ?>
            <label for="second_currency_rate" class="visually-hidden"><?= e(t('customize.rate')) ?></label>
            <input id="second_currency_rate" name="second_currency_rate" type="text" inputmode="decimal" required
                   value="<?= e($secondCurrency['rate']) ?>" class="input-price">
            <button type="submit" class="button button-primary"><?= e(t('dashboard.rate.update')) ?></button>
        </form>
        <?php if ($secondCurrency['updated_at'] !== ''): ?>
            <p class="help"><?= e(t('customize.rate.updated', ['date' => date('d/m/Y H:i', strtotime($secondCurrency['updated_at']))])) ?> (UTC)</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card">
    <h2><?= e(t('dashboard.menu_address')) ?></h2>
    <p class="url-box"><?= e($menuUrl) ?></p>
    <div class="actions">
        <a class="button" href="/" target="_blank" rel="noopener"><?= e(t('nav.view_menu')) ?></a>
        <a class="button" href="/admin/qr"><?= e(t('nav.qr')) ?></a>
        <a class="button" href="/admin/restaurant#menu_url"><?= e(t('dashboard.change_address')) ?></a>
    </div>
</section>
