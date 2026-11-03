<?php

use MenuQr\Csrf;
use MenuQr\I18n;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};
$invalid = static fn (string $field): string => !empty($errors[$field]) ? ' aria-invalid="true"' : '';

?>
<h1 class="page-title"><?= e(t('nav.account')) ?></h1>

<section class="card">
    <h2><?= e(t('account.profile')) ?></h2>
    <form method="post" action="/admin/account" class="form">
        <?= Csrf::field() ?>
        <label for="email"><?= e(t('install.email')) ?></label>
        <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required
               value="<?= e($values['email']) ?>"<?= $invalid('email') ?>>
        <?php $fieldError('email'); ?>

        <label for="language"><?= e(t('account.language')) ?></label>
        <select id="language" name="language"<?= $invalid('language') ?>>
            <?php foreach (I18n::LOCALES as $code => $label): ?>
                <option value="<?= e($code) ?>"<?= $code === $values['language'] ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="help"><?= e(t('account.language.help')) ?></p>

        <button type="submit" class="button button-primary form-submit"><?= e(t('common.save')) ?></button>
    </form>
</section>

<section class="card">
    <h2><?= e(t('account.password')) ?></h2>
    <form method="post" action="/admin/account/password" class="form">
        <?= Csrf::field() ?>
        <input type="text" name="username" autocomplete="username" value="<?= e($values['email']) ?>" hidden>

        <label for="current_password"><?= e(t('account.current_password')) ?></label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required<?= $invalid('current_password') ?>>
        <?php $fieldError('current_password'); ?>

        <label for="password"><?= e(t('recover.password')) ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required<?= $invalid('password') ?>>
        <p class="help"><?= e(t('install.password.help')) ?></p>
        <?php $fieldError('password'); ?>

        <label for="password_confirm"><?= e(t('install.password_confirm')) ?></label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="10" required<?= $invalid('password_confirm') ?>>
        <?php $fieldError('password_confirm'); ?>

        <button type="submit" class="button button-primary form-submit"><?= e(t('account.change_password')) ?></button>
    </form>
</section>
