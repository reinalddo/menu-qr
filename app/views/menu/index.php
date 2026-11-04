<?php

use MenuQr\Hours;
use MenuQr\Images;
use MenuQr\Money;

require_once APP_ROOT . '/views/partials/icon.php';

$tagEmoji = ['vegetarian' => '🌿', 'vegan' => '🌱', 'spicy' => '🌶️', 'gluten_free' => '🌾', 'new' => '✨', 'recommended' => '⭐'];
$cover = Images::url(($s['cover'] ?? '') ?: null);
$logo = Images::url(($s['logo'] ?? '') ?: null);
$whatsapp = $s['whatsapp'] ?? '';
$noticeOn = ($s['notice_enabled'] ?? '') === '1' && ($s['notice_text'] ?? '') !== '';

/** Main-currency price with the converted one below, or the price list of each size. */
$cardPrice = static function (array $dish): string {
    $amount = $dish['sizes'] !== [] ? min(array_column($dish['sizes'], 'price')) : $dish['price'];
    $main = Money::format($amount);
    $html = '<span class="price-main">' . e($dish['sizes'] !== [] ? t('menu.from', ['price' => $main]) : $main) . '</span>';
    $second = Money::formatSecond($amount);
    if ($second !== null) {
        $html .= '<span class="price-second">' . e($dish['sizes'] !== [] ? t('menu.from', ['price' => $second]) : $second) . '</span>';
    }
    return $html;
};
$tagList = static function (array $tags) use ($tagEmoji): string {
    if ($tags === []) {
        return '';
    }
    $html = '<span class="tags">';
    foreach ($tags as $tag) {
        $html .= '<span class="tag tag-' . e($tag) . '"><span aria-hidden="true">' . $tagEmoji[$tag] . '</span> ' . e(t('tag.' . $tag)) . '</span>';
    }
    return $html . '</span>';
};

?>
<header class="hero<?= $cover ? ' hero-with-cover' : '' ?>">
    <?php if ($cover): ?>
        <img class="hero-cover" src="<?= e($cover) ?>" alt="" fetchpriority="high">
    <?php endif; ?>
    <div class="hero-body">
        <?php if ($logo): ?>
            <img class="hero-logo" src="<?= e($logo) ?>" alt="<?= e(t('menu.logo_of', ['name' => $s['restaurant_name']])) ?>" width="88" height="88">
        <?php endif; ?>
        <h1 class="hero-name"><?= e($s['restaurant_name']) ?></h1>
        <?php if (($s['tagline'] ?? '') !== ''): ?>
            <p class="hero-tagline"><?= e($s['tagline']) ?></p>
        <?php endif; ?>
        <?php if ($hoursConfigured): ?>
            <a class="status <?= $status['open'] ? 'status-open' : 'status-closed' ?>" href="#info">
                <span class="status-dot" aria-hidden="true"></span><?= e(Hours::label($status)) ?>
            </a>
        <?php endif; ?>
        <?php if ($whatsapp !== '' || ($s['maps_url'] ?? '') !== '' || ($s['instagram'] ?? '') !== ''): ?>
            <nav class="contact" aria-label="<?= e(t('menu.contact')) ?>">
                <?php if ($whatsapp !== ''): ?>
                    <a class="contact-link" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>WhatsApp</a>
                <?php endif; ?>
                <?php if (($s['maps_url'] ?? '') !== ''): ?>
                    <a class="contact-link" href="<?= e($s['maps_url']) ?>" target="_blank" rel="noopener"><?= icon('pin') ?><?= e(t('menu.directions')) ?></a>
                <?php endif; ?>
                <?php if (($s['instagram'] ?? '') !== ''): ?>
                    <a class="contact-link" href="https://instagram.com/<?= e($s['instagram']) ?>" target="_blank" rel="noopener"><?= icon('camera') ?>Instagram</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</header>

<?php if ($noticeOn): ?>
    <aside class="notice" role="note"><?= icon('megaphone') ?><p><?= e($s['notice_text']) ?></p></aside>
<?php endif; ?>

<?php if ($menu['categories'] === []): ?>
    <main class="menu-empty">
        <p class="menu-empty-title"><?= e(t('menu.empty.title')) ?></p>
        <p><?= e(t('menu.empty.text')) ?></p>
    </main>
<?php else: ?>
    <div class="menu-tools">
        <label class="search">
            <?= icon('search') ?>
            <span class="visually-hidden"><?= e(t('menu.search')) ?></span>
            <input type="search" placeholder="<?= e(t('menu.search_placeholder')) ?>" autocomplete="off" data-search>
        </label>
    </div>

    <nav class="tabs" aria-label="<?= e(t('menu.categories')) ?>" data-tabs>
        <?php foreach ($menu['categories'] as $category): ?>
            <a href="#cat-<?= e($category['id']) ?>" data-tab="cat-<?= e($category['id']) ?>"><?= e($category['name']) ?></a>
        <?php endforeach; ?>
    </nav>

    <main class="menu">
        <?php if ($menu['featured'] !== []): ?>
            <section class="featured" aria-labelledby="featured-title" data-featured>
                <h2 class="section-title" id="featured-title"><?= e(t('menu.recommended')) ?></h2>
                <div class="featured-row">
                    <?php foreach ($menu['featured'] as $dish): ?>
                        <?php $photo = Images::url($dish['photo'], true); ?>
                        <button type="button" class="featured-card" data-open="dish-<?= e($dish['id']) ?>" data-dish-id="<?= e($dish['id']) ?>">
                            <?php if ($photo): ?>
                                <img src="<?= e($photo) ?>" alt="" loading="lazy" width="480" height="360">
                            <?php else: ?>
                                <span class="featured-placeholder" aria-hidden="true">🍽️</span>
                            <?php endif; ?>
                            <span class="featured-name"><?= e($dish['name']) ?></span>
                            <span class="featured-price"><?= $cardPrice($dish) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php foreach ($menu['categories'] as $category): ?>
            <section class="category" id="cat-<?= e($category['id']) ?>" data-category>
                <h2 class="section-title"><?= e($category['name']) ?></h2>
                <?php if (($category['description'] ?? '') !== ''): ?>
                    <p class="category-description"><?= e($category['description']) ?></p>
                <?php endif; ?>
                <ul class="dishes">
                    <?php foreach ($category['dishes'] as $dish): ?>
                        <?php
                        $available = (bool) $dish['is_available'];
                        $photo = Images::url($dish['photo'], true);
                        $search = mb_strtolower($dish['name'] . ' ' . ($dish['description'] ?? ''));
                        ?>
                        <li class="dish<?= $available ? '' : ' is-sold-out' ?>" data-dish data-search-text="<?= e($search) ?>">
                            <button type="button" class="dish-button" data-open="dish-<?= e($dish['id']) ?>" data-dish-id="<?= e($dish['id']) ?>">
                                <span class="dish-text">
                                    <span class="dish-line">
                                        <span class="dish-name"><?= e($dish['name']) ?></span>
                                        <span class="dish-leader" aria-hidden="true"></span>
                                        <span class="dish-price"><?= $cardPrice($dish) ?></span>
                                    </span>
                                    <?php if (($dish['description'] ?? '') !== ''): ?>
                                        <span class="dish-description"><?= e($dish['description']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!$available): ?>
                                        <span class="sold-out-badge"><?= e(t('menu.sold_out')) ?></span>
                                    <?php endif; ?>
                                    <?= $tagList($dish['tags']) ?>
                                </span>
                                <?php if ($photo): ?>
                                    <img class="dish-photo" src="<?= e($photo) ?>" alt="" loading="lazy" width="96" height="96">
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>

        <p class="no-results" data-no-results hidden><?= e(t('menu.no_results')) ?></p>
    </main>

    <?php foreach ($menu['categories'] as $category): ?>
        <?php foreach ($category['dishes'] as $dish): ?>
            <?php $big = Images::url($dish['photo']); ?>
            <template id="dish-<?= e($dish['id']) ?>">
                <?php if ($big): ?>
                    <img class="sheet-photo" src="<?= e($big) ?>" alt="" width="1200" height="900">
                <?php endif; ?>
                <div class="sheet-body">
                    <h2 class="sheet-title" id="sheet-title"><?= e($dish['name']) ?></h2>
                    <?php if (!$dish['is_available']): ?>
                        <span class="sold-out-badge"><?= e(t('menu.sold_out')) ?></span>
                    <?php endif; ?>
                    <?= $tagList($dish['tags']) ?>
                    <?php if (($dish['description'] ?? '') !== ''): ?>
                        <p class="sheet-description"><?= nl2br(e($dish['description'])) ?></p>
                    <?php endif; ?>
                    <?php if ($dish['sizes'] !== []): ?>
                        <ul class="sheet-sizes">
                            <?php foreach ($dish['sizes'] as $size): ?>
                                <?php $second = Money::formatSecond($size['price']); ?>
                                <li>
                                    <span><?= e($size['name']) ?></span>
                                    <span class="dish-leader" aria-hidden="true"></span>
                                    <span class="sheet-size-price">
                                        <span class="price-main"><?= e(Money::format($size['price'])) ?></span>
                                        <?php if ($second !== null): ?><span class="price-second"><?= e($second) ?></span><?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sheet-price"><?= $cardPrice($dish) ?></p>
                    <?php endif; ?>
                </div>
            </template>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <dialog class="sheet" aria-labelledby="sheet-title" data-sheet>
        <button type="button" class="sheet-close" data-close aria-label="<?= e(t('menu.close')) ?>"><?= icon('close') ?></button>
        <div class="sheet-content" data-sheet-content></div>
    </dialog>
<?php endif; ?>

<footer class="info" id="info">
    <?php if ($hoursConfigured): ?>
        <section class="info-block">
            <h2 class="info-title"><?= icon('clock') ?><?= e(t('menu.hours')) ?></h2>
            <dl class="hours">
                <?php for ($day = 1; $day <= 7; $day++): ?>
                    <div class="hours-row<?= $day === $today ? ' is-today' : '' ?>">
                        <dt><?= e(t('day.' . $day)) ?></dt>
                        <dd><?= e(Hours::dayText($hours[$day])) ?></dd>
                    </div>
                <?php endfor; ?>
            </dl>
        </section>
    <?php endif; ?>
    <?php if (($s['address'] ?? '') !== ''): ?>
        <section class="info-block">
            <h2 class="info-title"><?= icon('pin') ?><?= e(t('menu.address')) ?></h2>
            <p><?= e($s['address']) ?></p>
            <?php if (($s['maps_url'] ?? '') !== ''): ?>
                <a class="info-link" href="<?= e($s['maps_url']) ?>" target="_blank" rel="noopener"><?= e(t('menu.directions')) ?> →</a>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if (($s['show_powered_by'] ?? '1') === '1'): ?>
        <p class="powered">
            <?= e(t('menu.powered_by')) ?> <a href="https://github.com/reinalddo/menu-qr" target="_blank" rel="noopener">menu-qr</a>
        </p>
    <?php endif; ?>
</footer>
