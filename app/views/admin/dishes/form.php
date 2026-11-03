<?php

use MenuQr\Csrf;
use MenuQr\Images;
use MenuQr\Tags;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};
$invalid = static fn (string $field): string => !empty($errors[$field]) ? ' aria-invalid="true"' : '';
$action = $dish === null ? '/admin/dishes' : '/admin/dishes/' . (int) $dish['id'];
$sizes = $values['sizes'] ?: [['name' => '', 'price' => '']];
$photoUrl = Images::url($values['photo'] ?? null);

?>
<p class="breadcrumb"><a href="/admin/dishes?category=<?= e($values['category_id']) ?>">← <?= e(t('nav.dishes')) ?></a></p>
<h1 class="page-title"><?= e($title) ?></h1>

<?php if ($errors !== []): ?>
    <p class="alert alert-error"><?= e(t('common.fix_errors')) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>

    <section class="card">
        <label for="category_id"><?= e(t('dishes.category')) ?></label>
        <select id="category_id" name="category_id"<?= $invalid('category_id') ?>>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id']) ?>"<?= (int) $category['id'] === (int) $values['category_id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php $fieldError('category_id'); ?>

        <label for="name"><?= e(t('dishes.name')) ?></label>
        <input id="name" name="name" type="text" maxlength="150" required value="<?= e($values['name']) ?>"<?= $invalid('name') ?>>
        <?php $fieldError('name'); ?>

        <label for="description"><?= e(t('dishes.description')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <textarea id="description" name="description" rows="3" maxlength="1000"<?= $invalid('description') ?>><?= e($values['description'] ?? '') ?></textarea>
        <?php $fieldError('description'); ?>

        <label for="price"><?= e(t('dishes.price')) ?></label>
        <input id="price" name="price" type="text" inputmode="decimal" autocomplete="off" placeholder="0,00"
               value="<?= e($values['price']) ?>" class="input-price"<?= $invalid('price') ?>>
        <p class="help"><?= e(t('dishes.price.help')) ?></p>
        <?php $fieldError('price'); ?>
    </section>

    <section class="card">
        <h2><?= e(t('dishes.photo')) ?></h2>
        <div class="photo-field">
            <img class="photo-preview" src="<?= e($photoUrl ?? '') ?>" alt="" data-photo-preview<?= $photoUrl ? '' : ' hidden' ?>>
            <div>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-photo-input<?= $invalid('photo') ?>>
                <p class="help"><?= e(t('dishes.photo.help')) ?></p>
                <?php $fieldError('photo'); ?>
                <?php if ($photoUrl): ?>
                    <label class="check">
                        <input type="checkbox" name="remove_photo" value="1">
                        <span><?= e(t('dishes.photo.remove')) ?></span>
                    </label>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <h2><?= e(t('dishes.sizes')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></h2>
        <p class="help"><?= e(t('dishes.sizes.help')) ?></p>
        <div class="sizes" data-sizes>
            <?php foreach ($sizes as $size): ?>
                <div class="size-row" data-size-row>
                    <input name="size_name[]" type="text" maxlength="60" placeholder="<?= e(t('dishes.sizes.name')) ?>"
                           aria-label="<?= e(t('dishes.sizes.name')) ?>" value="<?= e($size['name']) ?>">
                    <input name="size_price[]" type="text" inputmode="decimal" placeholder="<?= e(t('dishes.price')) ?>"
                           aria-label="<?= e(t('dishes.price')) ?>" value="<?= e($size['price']) ?>" class="input-price">
                    <button type="button" class="icon-button" data-size-remove aria-label="<?= e(t('common.delete')) ?>">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-small" data-size-add>+ <?= e(t('dishes.sizes.add')) ?></button>
        <?php $fieldError('sizes'); ?>
    </section>

    <section class="card">
        <h2><?= e(t('dishes.tags')) ?></h2>
        <div class="chips">
            <?php foreach (Tags::ALL as $tag): ?>
                <label class="chip">
                    <input type="checkbox" name="tags[]" value="<?= e($tag) ?>"<?= in_array($tag, $values['tags'], true) ? ' checked' : '' ?>>
                    <span><?= e(t('tag.' . $tag)) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h2><?= e(t('dishes.options')) ?></h2>
        <label class="check">
            <input type="checkbox" name="is_available" value="1"<?= $values['is_available'] ? ' checked' : '' ?>>
            <span><?= e(t('dishes.available')) ?></span>
        </label>
        <label class="check">
            <input type="checkbox" name="is_visible" value="1"<?= $values['is_visible'] ? ' checked' : '' ?>>
            <span><?= e(t('dishes.visible')) ?></span>
        </label>
        <label class="check">
            <input type="checkbox" name="is_featured" value="1"<?= $values['is_featured'] ? ' checked' : '' ?>>
            <span><?= e(t('dishes.featured')) ?></span>
        </label>
    </section>

    <div class="sticky-actions">
        <button type="submit" class="button button-primary"><?= e(t('common.save')) ?></button>
        <?php if ($dish === null): ?>
            <button type="submit" name="next" value="new" class="button"><?= e(t('dishes.save_and_new')) ?></button>
        <?php endif; ?>
        <a class="button button-ghost" href="/admin/dishes?category=<?= e($values['category_id']) ?>"><?= e(t('common.cancel')) ?></a>
    </div>
</form>

<template id="size-row-template">
    <div class="size-row" data-size-row>
        <input name="size_name[]" type="text" maxlength="60" placeholder="<?= e(t('dishes.sizes.name')) ?>" aria-label="<?= e(t('dishes.sizes.name')) ?>">
        <input name="size_price[]" type="text" inputmode="decimal" placeholder="<?= e(t('dishes.price')) ?>" aria-label="<?= e(t('dishes.price')) ?>" class="input-price">
        <button type="button" class="icon-button" data-size-remove aria-label="<?= e(t('common.delete')) ?>">×</button>
    </div>
</template>
