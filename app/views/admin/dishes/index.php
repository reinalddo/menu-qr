<?php

use MenuQr\Csrf;
use MenuQr\Images;
use MenuQr\Money;

// Position limits per category, to disable ↑ on the first dish and ↓ on the last.
$firstLast = [];
foreach ($dishes as $dish) {
    $firstLast[$dish['category_id']]['first'] ??= $dish['id'];
    $firstLast[$dish['category_id']]['last'] = $dish['id'];
}

?>
<div class="page-head">
    <h1 class="page-title"><?= e(t('nav.dishes')) ?></h1>
    <?php if ($categories !== []): ?>
        <a class="button button-primary" href="/admin/dishes/new<?= $filter ? '?category=' . e($filter) : '' ?>">+ <?= e(t('dishes.new')) ?></a>
    <?php endif; ?>
</div>

<?php if ($categories === []): ?>
    <section class="card empty">
        <p><?= e(t('dishes.error.no_categories')) ?></p>
        <a class="button button-primary" href="/admin/categories"><?= e(t('categories.new')) ?></a>
    </section>
<?php else: ?>
    <form method="get" action="/admin/dishes" class="filter-bar" data-auto-submit>
        <label for="category" class="visually-hidden"><?= e(t('dishes.category')) ?></label>
        <select id="category" name="category">
            <option value="0"><?= e(t('dishes.all_categories')) ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id']) ?>"<?= (int) $category['id'] === $filter ? ' selected' : '' ?>>
                    <?= e($category['name']) ?> (<?= e($category['dish_count']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="button button-small"><?= e(t('common.filter')) ?></button></noscript>
    </form>

    <?php if ($dishes === []): ?>
        <p class="muted empty"><?= e(t('dishes.empty')) ?></p>
    <?php else: ?>
        <section class="card list-card">
            <ul class="item-list">
                <?php $currentCategory = null; ?>
                <?php foreach ($dishes as $dish): ?>
                    <?php if (!$filter && $dish['category_id'] !== $currentCategory): ?>
                        <?php $currentCategory = $dish['category_id']; ?>
                        <li class="item-group"><?= e($dish['category_name']) ?></li>
                    <?php endif; ?>
                    <li class="item item-wrap" id="dish-<?= e($dish['id']) ?>">
                        <a class="item-main item-with-thumb" href="/admin/dishes/<?= e($dish['id']) ?>/edit">
                            <?php $thumb = Images::url($dish['photo'], true); ?>
                            <?php if ($thumb): ?>
                                <img class="thumb" src="<?= e($thumb) ?>" alt="" loading="lazy" width="56" height="56">
                            <?php else: ?>
                                <span class="thumb thumb-empty" aria-hidden="true"></span>
                            <?php endif; ?>
                            <span>
                                <span class="item-name<?= $dish['is_available'] ? '' : ' is-sold-out' ?>"><?= e($dish['name']) ?></span>
                                <span class="item-meta">
                                    <?= e($dish['min_size_price'] !== null
                                        ? t('dishes.price_from', ['price' => Money::format($dish['min_size_price'])])
                                        : Money::format($dish['price'])) ?>
                                    <?php if (!$dish['is_available']): ?><span class="badge badge-warning"><?= e(t('availability.sold_out')) ?></span><?php endif; ?>
                                    <?php if (!$dish['is_visible']): ?><span class="badge badge-muted"><?= e(t('dishes.hidden.badge')) ?></span><?php endif; ?>
                                    <?php if ($dish['is_featured']): ?><span class="badge badge-brand"><?= e(t('dishes.featured.badge')) ?></span><?php endif; ?>
                                </span>
                            </span>
                        </a>
                        <div class="item-actions">
                            <?php foreach (['up' => '↑', 'down' => '↓'] as $direction => $arrow): ?>
                                <?php $edge = $firstLast[$dish['category_id']][$direction === 'up' ? 'first' : 'last'] === $dish['id']; ?>
                                <form method="post" action="/admin/dishes/<?= e($dish['id']) ?>/move">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="direction" value="<?= $direction ?>">
                                    <input type="hidden" name="category" value="<?= e($filter) ?>">
                                    <button type="submit" class="icon-button" title="<?= e(t('common.move_' . $direction)) ?>"
                                            aria-label="<?= e(t('common.move_' . $direction)) ?>"<?= $edge ? ' disabled' : '' ?>><?= $arrow ?></button>
                                </form>
                            <?php endforeach; ?>
                            <a class="button button-small" href="/admin/dishes/<?= e($dish['id']) ?>/edit"><?= e(t('common.edit')) ?></a>
                            <form method="post" action="/admin/dishes/<?= e($dish['id']) ?>/delete"
                                  data-confirm="<?= e(t('dishes.confirm_delete', ['name' => $dish['name']])) ?>">
                                <?= Csrf::field() ?>
                                <button type="submit" class="button button-small button-danger"><?= e(t('common.delete')) ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
<?php endif; ?>
