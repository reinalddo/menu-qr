<?php

use MenuQr\Csrf;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};

?>
<div class="simple-head">
    <span class="logo-mark" aria-hidden="true">QR</span>
    <div>
        <h1><?= e(t('recover.title')) ?></h1>
        <p class="muted"><?= e(t('recover.intro')) ?></p>
    </div>
</div>

<?php if (!empty($errors['form'])): ?>
    <p class="alert alert-error"><?= e($errors['form']) ?></p>
<?php endif; ?>

<form method="post" action="/recover" class="form" novalidate>
    <?= Csrf::field() ?>

    <label for="recovery_code"><?= e(t('recover.recovery_code')) ?></label>
    <input id="recovery_code" name="recovery_code" type="text" autocomplete="off" autocapitalize="characters"
           spellcheck="false" placeholder="XXXX-XXXX-XXXX" required class="code-input">

    <label for="email"><?= e(t('recover.email')) ?></label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>">

    <label for="password"><?= e(t('recover.password')) ?></label>
    <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>
    <p class="help"><?= e(t('install.password.help')) ?></p>
    <?php $fieldError('password'); ?>

    <label for="password_confirm"><?= e(t('install.password_confirm')) ?></label>
    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="10" required>
    <?php $fieldError('password_confirm'); ?>

    <button type="submit" class="button button-primary"><?= e(t('recover.submit')) ?></button>
</form>
