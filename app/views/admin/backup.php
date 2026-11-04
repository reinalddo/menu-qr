<?php

use MenuQr\Csrf;

?>
<h1 class="page-title"><?= e(t('nav.backup')) ?></h1>
<p class="muted"><?= e(t('backup.intro')) ?></p>

<section class="card">
    <h2><?= e(t('backup.export')) ?></h2>
    <p><?= e(t('backup.export.text')) ?></p>
    <form method="get" action="/admin/backup/export" class="form">
        <label class="check">
            <input type="checkbox" name="stats" value="1" checked>
            <span><?= e(t('backup.export.stats')) ?></span>
        </label>
        <button type="submit" class="button button-primary form-submit"><?= e(t('backup.export.button')) ?></button>
    </form>
    <p class="help"><?= e(t('backup.export.help')) ?></p>
</section>

<section class="card card-danger">
    <h2><?= e(t('backup.import')) ?></h2>
    <p><?= e(t('backup.import.text')) ?></p>
    <form method="post" action="/admin/backup/import" enctype="multipart/form-data" class="form"
          data-confirm="<?= e(t('backup.import.confirm_dialog')) ?>">
        <?= Csrf::field() ?>
        <label for="backup"><?= e(t('backup.import.file')) ?></label>
        <input id="backup" name="backup" type="file" accept=".zip,application/zip" required>

        <label for="confirm"><?= e(t('backup.import.confirm', ['word' => t('backup.confirm_word')])) ?></label>
        <input id="confirm" name="confirm" type="text" autocomplete="off" autocapitalize="characters" required
               placeholder="<?= e(t('backup.confirm_word')) ?>">

        <button type="submit" class="button button-danger-solid form-submit"><?= e(t('backup.import.button')) ?></button>
    </form>
    <p class="help"><?= e(t('backup.import.help')) ?></p>
</section>
