<?php

use MenuQr\Colors;
use MenuQr\Csrf;
use MenuQr\Hours;

$fieldError = static function (string $field) use ($errors): void {
    require APP_ROOT . '/views/partials/field_error.php';
};
$invalid = static fn (string $field): string => !empty($errors[$field]) ? ' aria-invalid="true"' : '';
$checked = static fn (bool $value): string => $value ? ' checked' : '';

?>
<h1 class="page-title"><?= e(t('nav.customize')) ?></h1>
<p class="muted"><?= e(t('customize.intro')) ?></p>

<?php if ($errors !== []): ?>
    <p class="alert alert-error"><?= e(t('common.fix_errors')) ?></p>
<?php endif; ?>

<form method="post" action="/admin/customize" class="form">
    <?= Csrf::field() ?>

    <section class="card" id="hours">
        <h2><?= e(t('customize.hours')) ?></h2>
        <p class="help"><?= e(t('customize.hours.help')) ?></p>
        <?php $fieldError('hours'); ?>
        <div class="hours-editor" data-hours>
            <?php for ($day = 1; $day <= 7; $day++): ?>
                <?php $ranges = $hours[$day] ?? []; ?>
                <fieldset class="hours-day" data-hours-day>
                    <legend class="visually-hidden"><?= e(t('day.' . $day)) ?></legend>
                    <label class="check hours-toggle">
                        <input type="checkbox" name="hours[<?= $day ?>][open]" value="1"<?= $checked($ranges !== []) ?> data-hours-open>
                        <span><?= e(t('day.' . $day)) ?></span>
                    </label>
                    <div class="hours-ranges" data-hours-ranges<?= $ranges === [] ? ' hidden' : '' ?>>
                        <?php for ($slot = 0; $slot < Hours::MAX_RANGES; $slot++): ?>
                            <div class="hours-range">
                                <input type="time" name="hours[<?= $day ?>][<?= $slot ?>][from]" value="<?= e($ranges[$slot][0] ?? '') ?>"
                                       aria-label="<?= e(t('customize.hours.from', ['day' => t('day.' . $day)])) ?>">
                                <span aria-hidden="true">–</span>
                                <input type="time" name="hours[<?= $day ?>][<?= $slot ?>][to]" value="<?= e($ranges[$slot][1] ?? '') ?>"
                                       aria-label="<?= e(t('customize.hours.to', ['day' => t('day.' . $day)])) ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <span class="hours-closed muted" data-hours-closed<?= $ranges === [] ? '' : ' hidden' ?>><?= e(t('hours.closed')) ?></span>
                </fieldset>
            <?php endfor; ?>
        </div>
        <button type="button" class="button button-small" data-hours-copy><?= e(t('customize.hours.copy_monday')) ?></button>
    </section>

    <section class="card" id="currency">
        <h2><?= e(t('customize.second_currency')) ?></h2>
        <p class="help"><?= e(t('customize.second_currency.help')) ?></p>
        <label class="check">
            <input type="checkbox" name="second_currency_enabled" value="1"<?= $checked($values['second_currency_enabled']) ?>>
            <span><?= e(t('customize.second_currency.enable')) ?></span>
        </label>
        <div class="row">
            <div>
                <label for="second_currency_code"><?= e(t('install.currency_code')) ?></label>
                <input id="second_currency_code" name="second_currency_code" type="text" maxlength="3" placeholder="VES"
                       value="<?= e($values['second_currency_code']) ?>"<?= $invalid('second_currency_code') ?>>
            </div>
            <div>
                <label for="second_currency_symbol"><?= e(t('install.currency_symbol')) ?></label>
                <input id="second_currency_symbol" name="second_currency_symbol" type="text" maxlength="8" placeholder="Bs."
                       value="<?= e($values['second_currency_symbol']) ?>"<?= $invalid('second_currency_symbol') ?>>
            </div>
        </div>
        <?php $fieldError('second_currency_code'); ?>
        <?php $fieldError('second_currency_symbol'); ?>
        <div class="row">
            <div>
                <label for="second_currency_rate"><?= e(t('customize.rate')) ?></label>
                <input id="second_currency_rate" name="second_currency_rate" type="text" inputmode="decimal" placeholder="36,50"
                       value="<?= e($values['second_currency_rate']) ?>"<?= $invalid('second_currency_rate') ?>>
            </div>
            <div>
                <label for="second_currency_decimals"><?= e(t('restaurant.decimals')) ?></label>
                <select id="second_currency_decimals" name="second_currency_decimals">
                    <?php foreach (['2', '0'] as $decimals): ?>
                        <option value="<?= $decimals ?>"<?= (string) $values['second_currency_decimals'] === $decimals ? ' selected' : '' ?>><?= e(t('restaurant.decimals.' . $decimals)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <p class="help"><?= e(t('customize.rate.help')) ?></p>
        <?php $fieldError('second_currency_rate'); ?>
        <?php if ($rateUpdatedAt !== ''): ?>
            <p class="help"><?= e(t('customize.rate.updated', ['date' => date('d/m/Y H:i', strtotime($rateUpdatedAt))])) ?> (UTC)</p>
        <?php endif; ?>
    </section>

    <section class="card" id="notice">
        <h2><?= e(t('customize.notice')) ?></h2>
        <label class="check">
            <input type="checkbox" name="notice_enabled" value="1"<?= $checked($values['notice_enabled']) ?>>
            <span><?= e(t('customize.notice.enable')) ?></span>
        </label>
        <label for="notice_text"><?= e(t('customize.notice.text')) ?></label>
        <textarea id="notice_text" name="notice_text" rows="2" maxlength="200" placeholder="<?= e(t('customize.notice.placeholder')) ?>"<?= $invalid('notice_text') ?>><?= e($values['notice_text']) ?></textarea>
        <?php $fieldError('notice_text'); ?>
    </section>

    <section class="card" id="color">
        <h2><?= e(t('customize.color')) ?></h2>
        <p class="help"><?= e(t('customize.color.help')) ?></p>
        <div class="color-field">
            <input id="brand_color" name="brand_color" type="color" value="<?= e($values['brand_color']) ?>" list="brand-presets"<?= $invalid('brand_color') ?>>
            <label for="brand_color" class="color-label"><?= e(t('customize.color.pick')) ?></label>
        </div>
        <datalist id="brand-presets">
            <?php foreach (Colors::PRESETS as $preset): ?>
                <option value="<?= e($preset) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <?php $fieldError('brand_color'); ?>
    </section>

    <section class="card">
        <h2><?= e(t('customize.credit')) ?></h2>
        <label class="check">
            <input type="checkbox" name="show_powered_by" value="1"<?= $checked($values['show_powered_by']) ?>>
            <span><?= e(t('customize.credit.label')) ?></span>
        </label>
        <p class="help"><?= e(t('customize.credit.help')) ?></p>
    </section>

    <div class="sticky-actions">
        <button type="submit" class="button button-primary"><?= e(t('common.save')) ?></button>
        <a class="button button-ghost" href="/" target="_blank" rel="noopener"><?= e(t('nav.view_menu')) ?></a>
    </div>
</form>
