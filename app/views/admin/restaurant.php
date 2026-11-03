<?php

use MenuQr\Csrf;
use MenuQr\I18n;
use MenuQr\Images;
use MenuQr\Money;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};
$invalid = static fn (string $field): string => !empty($errors[$field]) ? ' aria-invalid="true"' : '';
$input = static function (string $field, string $type = 'text', array $attributes = []) use ($values, $invalid): string {
    $extra = '';
    foreach ($attributes as $name => $value) {
        $extra .= ' ' . $name . '="' . e($value) . '"';
    }
    return '<input id="' . $field . '" name="' . $field . '" type="' . $type . '" value="' . e($values[$field] ?? '') . '"' . $extra . $invalid($field) . '>';
};

?>
<h1 class="page-title"><?= e(t('nav.restaurant')) ?></h1>

<?php if ($errors !== []): ?>
    <p class="alert alert-error"><?= e(t('common.fix_errors')) ?></p>
<?php endif; ?>

<form method="post" action="/admin/restaurant" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>

    <section class="card">
        <h2><?= e(t('restaurant.section.identity')) ?></h2>

        <label for="restaurant_name"><?= e(t('install.restaurant_name')) ?></label>
        <?= $input('restaurant_name', 'text', ['maxlength' => '120', 'required' => 'required']) ?>
        <?php $fieldError('restaurant_name'); ?>

        <label for="tagline"><?= e(t('restaurant.tagline')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <?= $input('tagline', 'text', ['maxlength' => '160', 'placeholder' => t('restaurant.tagline.placeholder')]) ?>
        <?php $fieldError('tagline'); ?>

        <?php foreach (['logo', 'cover'] as $kind): ?>
            <?php $url = Images::url(${$kind} ?: null); ?>
            <label for="<?= $kind ?>"><?= e(t('restaurant.' . $kind)) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
            <div class="photo-field">
                <img class="photo-preview photo-preview-<?= $kind ?>" src="<?= e($url ?? '') ?>" alt="" data-photo-preview<?= $url ? '' : ' hidden' ?>>
                <div>
                    <input id="<?= $kind ?>" name="<?= $kind ?>" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-photo-input<?= $invalid($kind) ?>>
                    <p class="help"><?= e(t('restaurant.' . $kind . '.help')) ?></p>
                    <?php $fieldError($kind); ?>
                    <?php if ($url): ?>
                        <label class="check">
                            <input type="checkbox" name="remove_<?= $kind ?>" value="1">
                            <span><?= e(t('restaurant.remove_image')) ?></span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2><?= e(t('restaurant.section.contact')) ?></h2>

        <label for="address"><?= e(t('restaurant.address')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <?= $input('address', 'text', ['maxlength' => '255']) ?>
        <?php $fieldError('address'); ?>

        <label for="maps_url"><?= e(t('restaurant.maps_url')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <?= $input('maps_url', 'url', ['maxlength' => '500', 'placeholder' => 'https://maps.app.goo.gl/...']) ?>
        <p class="help"><?= e(t('restaurant.maps_url.help')) ?></p>
        <?php $fieldError('maps_url'); ?>

        <label for="whatsapp"><?= e(t('restaurant.whatsapp')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <?= $input('whatsapp', 'tel', ['maxlength' => '20', 'placeholder' => '584121234567', 'inputmode' => 'tel']) ?>
        <p class="help"><?= e(t('restaurant.whatsapp.help')) ?></p>
        <?php $fieldError('whatsapp'); ?>

        <label for="instagram"><?= e(t('restaurant.instagram')) ?> <span class="optional"><?= e(t('common.optional')) ?></span></label>
        <?= $input('instagram', 'text', ['maxlength' => '100', 'placeholder' => '@mirestaurante']) ?>
        <?php $fieldError('instagram'); ?>
    </section>

    <section class="card">
        <h2><?= e(t('restaurant.section.regional')) ?></h2>

        <label for="menu_language"><?= e(t('restaurant.menu_language')) ?></label>
        <select id="menu_language" name="menu_language"<?= $invalid('menu_language') ?>>
            <?php foreach (I18n::LOCALES as $code => $label): ?>
                <option value="<?= e($code) ?>"<?= $code === $values['menu_language'] ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="help"><?= e(t('restaurant.menu_language.help')) ?></p>

        <label for="timezone"><?= e(t('install.timezone')) ?></label>
        <select id="timezone" name="timezone"<?= $invalid('timezone') ?>>
            <?php foreach ($timezones as $zone): ?>
                <option value="<?= e($zone) ?>"<?= $zone === $values['timezone'] ? ' selected' : '' ?>><?= e($zone) ?></option>
            <?php endforeach; ?>
        </select>
        <?php $fieldError('timezone'); ?>

        <div class="row">
            <div>
                <label for="currency_code"><?= e(t('install.currency_code')) ?></label>
                <?= $input('currency_code', 'text', ['maxlength' => '3', 'required' => 'required']) ?>
            </div>
            <div>
                <label for="currency_symbol"><?= e(t('install.currency_symbol')) ?></label>
                <?= $input('currency_symbol', 'text', ['maxlength' => '8', 'required' => 'required']) ?>
            </div>
        </div>
        <?php $fieldError('currency_code'); ?>
        <?php $fieldError('currency_symbol'); ?>

        <div class="row">
            <div>
                <label for="currency_decimals"><?= e(t('restaurant.decimals')) ?></label>
                <select id="currency_decimals" name="currency_decimals">
                    <?php foreach (['2' => '12,50', '0' => '12'] as $decimals => $example): ?>
                        <option value="<?= $decimals ?>"<?= (string) $values['currency_decimals'] === (string) $decimals ? ' selected' : '' ?>>
                            <?= e(t('restaurant.decimals.' . $decimals)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="number_format"><?= e(t('restaurant.number_format')) ?></label>
                <select id="number_format" name="number_format">
                    <?php foreach (Money::FORMATS as $format): ?>
                        <option value="<?= e($format) ?>"<?= $format === $values['number_format'] ? ' selected' : '' ?>><?= e($format) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 id="menu_url_title"><?= e(t('restaurant.menu_url')) ?></h2>
        <p class="help"><?= e(t('restaurant.menu_url.help')) ?></p>
        <?= $input('menu_url', 'url', ['maxlength' => '500', 'required' => 'required', 'aria-labelledby' => 'menu_url_title']) ?>
        <?php $fieldError('menu_url'); ?>
        <?php if ($menuUrlWarning && empty($errors['menu_url'])): ?>
            <p class="alert alert-warning"><?= e(t('restaurant.menu_url.warning')) ?></p>
        <?php endif; ?>
    </section>

    <div class="sticky-actions">
        <button type="submit" class="button button-primary"><?= e(t('common.save')) ?></button>
    </div>
</form>
