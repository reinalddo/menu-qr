<?php

use MenuQr\Csrf;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};
$last = count($categories) - 1;

?>
<h1 class="page-title"><?= e(t('nav.categories')) ?></h1>
<p class="muted"><?= e(t('categories.intro')) ?></p>

<section class="card">
    <h2><?= e(t('categories.new')) ?></h2>
    <form method="post" action="/admin/categories" class="form">
        <?= Csrf::field() ?>
        <label for="name"><?= e(t('categories.name')) ?></label>
        <input id="name" name="name" type="text" maxlength="120" required placeholder="<?= e(t('categories.name.placeholder')) ?>"
               value="<?= e($values['name']) ?>"<?= !empty($errors['name']) ? ' aria-invalid="true"' : '' ?>>
        <?php $fieldError('name'); ?>

        <label for="description"><?= e(t('categories.description')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <input id="description" name="description" type="text" maxlength="500" value="<?= e($values['description']) ?>">
        <?php $fieldError('description'); ?>

        <button type="submit" class="button button-primary form-submit"><?= e(t('categories.add')) ?></button>
    </form>
</section>

<?php if ($categories === []): ?>
    <p class="muted empty"><?= e(t('categories.empty')) ?></p>
<?php else: ?>
    <section class="card list-card">
        <ul class="item-list">
            <?php foreach ($categories as $index => $category): ?>
                <li class="item item-wrap" id="category-<?= e($category['id']) ?>">
                    <div class="item-main">
                        <span class="item-name"><?= e($category['name']) ?></span>
                        <span class="item-meta">
                            <?= e(t('categories.dish_count', ['count' => $category['dish_count']])) ?>
                            <?php if (!$category['is_visible']): ?><span class="badge badge-muted"><?= e(t('common.hidden')) ?></span><?php endif; ?>
                        </span>
                    </div>
                    <div class="item-actions">
                        <?php foreach (['up' => '↑', 'down' => '↓'] as $direction => $arrow): ?>
                            <form method="post" action="/admin/categories/<?= e($category['id']) ?>/move">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="direction" value="<?= $direction ?>">
                                <button type="submit" class="icon-button" title="<?= e(t('common.move_' . $direction)) ?>"
                                        aria-label="<?= e(t('common.move_' . $direction)) ?>"
                                        <?= ($direction === 'up' && $index === 0) || ($direction === 'down' && $index === $last) ? 'disabled' : '' ?>><?= $arrow ?></button>
                            </form>
                        <?php endforeach; ?>
                        <form method="post" action="/admin/categories/<?= e($category['id']) ?>/visibility">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="is_visible" value="<?= $category['is_visible'] ? '0' : '1' ?>">
                            <button type="submit" class="button button-small"><?= e($category['is_visible'] ? t('common.hide') : t('common.show')) ?></button>
                        </form>
                        <a class="button button-small" href="/admin/categories/<?= e($category['id']) ?>/edit"><?= e(t('common.edit')) ?></a>
                        <form method="post" action="/admin/categories/<?= e($category['id']) ?>/delete"
                              data-confirm="<?= e(t('categories.confirm_delete', ['name' => $category['name']])) ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="button button-small button-danger"><?= e(t('common.delete')) ?></button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
