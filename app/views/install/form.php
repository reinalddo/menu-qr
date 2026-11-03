<?php

use MenuQr\Csrf;
use MenuQr\I18n;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};
$invalid = static fn (string $field): string => !empty($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';

?>
<div class="simple-head">
    <span class="logo-mark" aria-hidden="true">QR</span>
    <div>
        <h1><?= e(t('install.title')) ?></h1>
        <p class="muted"><?= e(t('install.intro')) ?></p>
    </div>
</div>

<nav class="lang-switch" aria-label="<?= e(t('app.language')) ?>">
    <?php foreach (I18n::LOCALES as $code => $label): ?>
        <a href="/install?lang=<?= e($code) ?>"<?= $code === I18n::locale() ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!empty($errors['form'])): ?>
    <p class="alert alert-error"><?= e($errors['form']) ?></p>
<?php endif; ?>

<form method="post" action="/install" class="form" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="language" value="<?= e(I18n::locale()) ?>">

    <fieldset>
        <legend><?= e(t('install.section.code')) ?></legend>
        <label for="setup_code"><?= e(t('install.setup_code')) ?></label>
        <input id="setup_code" name="setup_code" type="text" autocomplete="off" autocapitalize="characters"
               spellcheck="false" placeholder="XXXX-XXXX-XXXX" required class="code-input"<?= $invalid('setup_code') ?>>
        <p class="help"><?= e(t('install.setup_code.help')) ?></p>
        <?php $fieldError('setup_code'); ?>
    </fieldset>

    <fieldset>
        <legend><?= e(t('install.section.restaurant')) ?></legend>

        <label for="restaurant_name"><?= e(t('install.restaurant_name')) ?></label>
        <input id="restaurant_name" name="restaurant_name" type="text" maxlength="120" required
               value="<?= e($values['restaurant_name']) ?>"<?= $invalid('restaurant_name') ?>>
        <?php $fieldError('restaurant_name'); ?>

        <label for="timezone"><?= e(t('install.timezone')) ?></label>
        <select id="timezone" name="timezone" data-detect-timezone="<?= $values['timezone'] === 'UTC' ? '1' : '0' ?>"<?= $invalid('timezone') ?>>
            <?php foreach ($timezones as $zone): ?>
                <option value="<?= e($zone) ?>"<?= $zone === $values['timezone'] ? ' selected' : '' ?>><?= e($zone) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="help"><?= e(t('install.timezone.help')) ?></p>
        <?php $fieldError('timezone'); ?>

        <div class="row">
            <div>
                <label for="currency_code"><?= e(t('install.currency_code')) ?></label>
                <input id="currency_code" name="currency_code" type="text" maxlength="3" required autocapitalize="characters"
                       value="<?= e($values['currency_code']) ?>"<?= $invalid('currency_code') ?>>
            </div>
            <div>
                <label for="currency_symbol"><?= e(t('install.currency_symbol')) ?></label>
                <input id="currency_symbol" name="currency_symbol" type="text" maxlength="8" required
                       value="<?= e($values['currency_symbol']) ?>"<?= $invalid('currency_symbol') ?>>
            </div>
        </div>
        <p class="help"><?= e(t('install.currency_code.help')) ?></p>
        <?php $fieldError('currency_code'); ?>
        <?php $fieldError('currency_symbol'); ?>
    </fieldset>

    <fieldset>
        <legend><?= e(t('install.section.admin')) ?></legend>

        <label for="email"><?= e(t('install.email')) ?></label>
        <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required
               value="<?= e($values['email']) ?>"<?= $invalid('email') ?>>
        <?php $fieldError('email'); ?>

        <label for="password"><?= e(t('install.password')) ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required<?= $invalid('password') ?>>
        <p class="help"><?= e(t('install.password.help')) ?></p>
        <?php $fieldError('password'); ?>

        <label for="password_confirm"><?= e(t('install.password_confirm')) ?></label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="10" required<?= $invalid('password_confirm') ?>>
        <?php $fieldError('password_confirm'); ?>
    </fieldset>

    <button type="submit" class="button button-primary"><?= e(t('install.submit')) ?></button>
</form>

<script src="<?= e(asset('/assets/js/install.js')) ?>"></script>
