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

<section class="card" aria-labelledby="visits-title">
    <h2 id="visits-title"><?= e(t('stats.title')) ?></h2>
    <div class="stat-row">
        <div class="metric"><span class="metric-value"><?= e($stats['today']) ?></span><span class="metric-label"><?= e(t('stats.today')) ?></span></div>
        <div class="metric"><span class="metric-value"><?= e($stats['week']) ?></span><span class="metric-label"><?= e(t('stats.week')) ?></span></div>
        <div class="metric"><span class="metric-value"><?= e($stats['month']) ?></span><span class="metric-label"><?= e(t('stats.month')) ?></span></div>
        <div class="metric">
            <span class="metric-value"><?= $stats['month'] > 0 ? e((string) round($stats['qr'] * 100 / $stats['month'])) . '%' : '—' ?></span>
            <span class="metric-label"><?= e(t('stats.qr_share')) ?></span>
        </div>
    </div>

    <?php if ($stats['month'] === 0): ?>
        <p class="muted"><?= e(t('stats.empty')) ?></p>
    <?php else: ?>
        <?php $max = max(array_column($stats['daily'], 'views')); ?>
        <figure class="chart">
            <figcaption class="chart-caption"><?= e(t('stats.chart_title')) ?></figcaption>
            <div class="chart-plot">
                <span class="chart-max" aria-hidden="true"><?= e($max) ?></span>
                <div class="chart-bars">
                    <?php foreach ($stats['daily'] as $point): ?>
                        <?php $label = date('d/m', strtotime($point['day'])) . ' · ' . t('stats.visits_count', ['count' => $point['views']]); ?>
                        <span class="chart-col" tabindex="0" data-tip="<?= e($label) ?>" aria-label="<?= e($label) ?>">
                            <?php if ($point['views'] > 0): ?>
                                <span class="chart-bar" style="height: <?= e(max(3, round($point['views'] * 100 / $max))) ?>%"></span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="chart-axis" aria-hidden="true">
                <span><?= e(date('d/m', strtotime($stats['daily'][0]['day']))) ?></span>
                <span><?= e(t('stats.today')) ?></span>
            </div>
        </figure>
        <details class="chart-table">
            <summary><?= e(t('stats.as_table')) ?></summary>
            <table>
                <thead><tr><th scope="col"><?= e(t('stats.day')) ?></th><th scope="col"><?= e(t('stats.visits')) ?></th></tr></thead>
                <tbody>
                    <?php foreach (array_reverse($stats['daily']) as $point): ?>
                        <tr><td><?= e(date('d/m/Y', strtotime($point['day']))) ?></td><td><?= e($point['views']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>

        <?php if ($stats['top'] !== []): ?>
            <h3 class="subhead"><?= e(t('stats.top_dishes')) ?></h3>
            <?php $topMax = $stats['top'][0]['views']; ?>
            <ol class="top-list">
                <?php foreach ($stats['top'] as $dish): ?>
                    <li>
                        <span class="top-name"><?= e($dish['name']) ?></span>
                        <span class="top-track" aria-hidden="true"><span style="width: <?= e(round($dish['views'] * 100 / $topMax)) ?>%"></span></span>
                        <span class="top-value"><?= e($dish['views']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    <?php endif; ?>
    <p class="help"><?= e(t('stats.privacy')) ?></p>
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
