<?php

use MenuQr\Csrf;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};

?>
<p class="breadcrumb"><a href="/admin/categories">← <?= e(t('nav.categories')) ?></a></p>
<h1 class="page-title"><?= e(t('categories.edit')) ?></h1>

<section class="card">
    <form method="post" action="/admin/categories/<?= e($category['id']) ?>" class="form">
        <?= Csrf::field() ?>
        <label for="name"><?= e(t('categories.name')) ?></label>
        <input id="name" name="name" type="text" maxlength="120" required
               value="<?= e($values['name']) ?>"<?= !empty($errors['name']) ? ' aria-invalid="true"' : '' ?>>
        <?php $fieldError('name'); ?>

        <label for="description"><?= e(t('categories.description')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <input id="description" name="description" type="text" maxlength="500" value="<?= e($values['description'] ?? '') ?>">
        <p class="help"><?= e(t('categories.description.help')) ?></p>
        <?php $fieldError('description'); ?>

        <label class="check">
            <input type="checkbox" name="is_visible" value="1"<?= !empty($values['is_visible']) ? ' checked' : '' ?>>
            <span><?= e(t('categories.visible')) ?></span>
        </label>

        <div class="actions">
            <button type="submit" class="button button-primary"><?= e(t('common.save')) ?></button>
            <a class="button" href="/admin/categories"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</section>
