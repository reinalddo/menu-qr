<?php

use MenuQr\Csrf;
use MenuQr\I18n;

?>
<div class="simple-head">
    <span class="logo-mark" aria-hidden="true">QR</span>
    <div>
        <h1><?= e(t('login.title')) ?></h1>
        <p class="muted"><?= e(t('login.intro')) ?></p>
    </div>
</div>

<nav class="lang-switch" aria-label="<?= e(t('app.language')) ?>">
    <?php foreach (I18n::LOCALES as $code => $label): ?>
        <a href="/admin/login?lang=<?= e($code) ?>"<?= $code === I18n::locale() ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($error !== null): ?>
    <p class="alert alert-error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="/admin/login" class="form">
    <?= Csrf::field() ?>

    <label for="email"><?= e(t('install.email')) ?></label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" autofocus>

    <label for="password"><?= e(t('install.password')) ?></label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>

    <button type="submit" class="button button-primary form-submit"><?= e(t('login.submit')) ?></button>
</form>

<details class="help-box">
    <summary><?= e(t('login.forgot')) ?></summary>
    <p><?= e(t('login.forgot.text')) ?></p>
</details>
