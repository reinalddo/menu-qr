<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Colors;
use MenuQr\Hours;
use MenuQr\Http;
use MenuQr\Money;
use MenuQr\Session;
use MenuQr\Settings;

/** Opening hours, second currency, notice of the day, brand color and the footer credit. */
final class CustomizeController extends AdminController
{
    private const PAGE = '/admin/customize';

    public static function edit(): void
    {
        self::admin();
        self::form(self::current(), Hours::load(), []);
    }

    public static function update(): void
    {
        self::guardPost(self::PAGE);

        $values = [
            'second_currency_enabled' => Http::input('second_currency_enabled') === '1',
            'second_currency_code' => strtoupper(Http::input('second_currency_code')),
            'second_currency_symbol' => Http::input('second_currency_symbol'),
            'second_currency_rate' => Http::input('second_currency_rate'),
            'second_currency_decimals' => Http::input('second_currency_decimals'),
            'notice_enabled' => Http::input('notice_enabled') === '1',
            'notice_text' => Http::input('notice_text'),
            'brand_color' => strtolower(Http::input('brand_color')),
            'show_powered_by' => Http::input('show_powered_by') === '1',
        ];
        [$hours, $errors] = self::readHours($_POST['hours'] ?? []);

        $rate = Money::parseRate($values['second_currency_rate']);
        if ($values['second_currency_enabled'] || $values['second_currency_rate'] !== '') {
            if (!preg_match('/^[A-Z]{3}$/', $values['second_currency_code'])) {
                $errors['second_currency_code'] = t('install.error.currency_code');
            }
            $symbolLength = mb_strlen($values['second_currency_symbol']);
            if ($symbolLength < 1 || $symbolLength > 8) {
                $errors['second_currency_symbol'] = t('install.error.currency_symbol');
            }
            if ($rate === null) {
                $errors['second_currency_rate'] = t('customize.error.rate');
            }
        }
        if (!in_array($values['second_currency_decimals'], ['0', '2'], true)) {
            $values['second_currency_decimals'] = '2';
        }
        if (mb_strlen($values['notice_text']) > 200) {
            $errors['notice_text'] = t('customize.error.notice');
        }
        if ($values['notice_enabled'] && $values['notice_text'] === '') {
            $errors['notice_text'] = t('customize.error.notice_empty');
        }
        if (!Colors::valid($values['brand_color'])) {
            $errors['brand_color'] = t('customize.error.color');
        }

        if ($errors !== []) {
            self::form($values, $hours, $errors);
            return;
        }

        $values['second_currency_rate'] = (string) $rate;
        if ($rate !== null && $rate !== Settings::get('second_currency_rate')) {
            $values['second_currency_rate_updated_at'] = gmdate('c');
        }
        $values['opening_hours'] = json_encode((object) array_filter($hours), JSON_UNESCAPED_SLASHES);
        Settings::setMany($values);
        Session::flash('success', t('common.saved'));
        Http::redirect(self::PAGE);
    }

    /** Quick form on the dashboard: only the exchange rate changes. */
    public static function updateRate(): void
    {
        self::guardPost('/admin');
        $rate = Money::parseRate(Http::input('second_currency_rate'));
        if ($rate === null) {
            self::back('/admin', t('customize.error.rate'), 'error');
        }
        Settings::setMany(['second_currency_rate' => $rate, 'second_currency_rate_updated_at' => gmdate('c')]);
        self::back('/admin', t('customize.rate_saved'));
    }

    /**
     * @return array{0: array<int, array<int, array{0: string, 1: string}>>, 1: array<string, string>}
     */
    private static function readHours(mixed $input): array
    {
        $hours = [];
        $errors = [];
        for ($day = 1; $day <= 7; $day++) {
            $hours[$day] = [];
            $row = is_array($input) && is_array($input[$day] ?? null) ? $input[$day] : [];
            if (($row['open'] ?? '') !== '1') {
                continue;
            }
            for ($slot = 0; $slot < Hours::MAX_RANGES; $slot++) {
                $from = trim((string) ($row[$slot]['from'] ?? ''));
                $to = trim((string) ($row[$slot]['to'] ?? ''));
                if ($from === '' && $to === '') {
                    continue;
                }
                if (!Hours::validTime($from) || !Hours::validTime($to) || $from === $to) {
                    $errors['hours'] = t('customize.error.hours', ['day' => t('day.' . $day)]);
                }
                $hours[$day][] = [$from, $to];
            }
            if ($hours[$day] === [] && !isset($errors['hours'])) {
                $errors['hours'] = t('customize.error.hours_empty', ['day' => t('day.' . $day)]);
            }
        }
        return [$hours, $errors];
    }

    private static function current(): array
    {
        return [
            'second_currency_enabled' => Settings::get('second_currency_enabled') === '1',
            'second_currency_code' => Settings::get('second_currency_code'),
            'second_currency_symbol' => Settings::get('second_currency_symbol'),
            'second_currency_rate' => Money::rateInput(Settings::get('second_currency_rate')),
            'second_currency_decimals' => Settings::get('second_currency_decimals', '2'),
            'notice_enabled' => Settings::get('notice_enabled') === '1',
            'notice_text' => Settings::get('notice_text'),
            'brand_color' => Colors::brand(),
            'show_powered_by' => Settings::get('show_powered_by', '1') === '1',
        ];
    }

    private static function form(array $values, array $hours, array $errors): void
    {
        self::render('customize', 'customize', [
            'title' => t('nav.customize'),
            'values' => $values,
            'hours' => $hours,
            'errors' => $errors,
            'rateUpdatedAt' => Settings::get('second_currency_rate_updated_at'),
        ]);
    }
}
