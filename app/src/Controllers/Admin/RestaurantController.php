<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use DateTimeZone;
use MenuQr\Http;
use MenuQr\I18n;
use MenuQr\ImageException;
use MenuQr\Images;
use MenuQr\Money;
use MenuQr\Session;
use MenuQr\Settings;

final class RestaurantController extends AdminController
{
    private const PAGE = '/admin/restaurant';
    private const TEXT_FIELDS = [
        'restaurant_name', 'tagline', 'address', 'maps_url', 'whatsapp', 'instagram',
        'menu_language', 'timezone', 'currency_code', 'currency_symbol', 'currency_decimals',
        'number_format', 'menu_url',
    ];

    public static function edit(): void
    {
        self::admin();
        $values = [];
        foreach (self::TEXT_FIELDS as $field) {
            $values[$field] = Settings::get($field);
        }
        self::form($values, []);
    }

    public static function update(): void
    {
        self::guardPost(self::PAGE);
        $values = [];
        foreach (self::TEXT_FIELDS as $field) {
            $values[$field] = Http::input($field);
        }
        $values['currency_code'] = strtoupper($values['currency_code']);
        $values['whatsapp'] = (string) preg_replace('/\D+/', '', $values['whatsapp']);
        $values['instagram'] = self::instagramUser($values['instagram']);
        $values['menu_url'] = rtrim($values['menu_url'], '/');

        $errors = self::validate($values);

        $newImages = [];
        if ($errors === []) {
            foreach (['logo', 'cover'] as $kind) {
                try {
                    $stored = Images::store($_FILES[$kind] ?? null, $kind);
                    if ($stored !== null) {
                        $newImages[$kind] = $stored;
                    }
                } catch (ImageException $exception) {
                    $errors[$kind] = $exception->getMessage();
                }
            }
            if ($errors !== []) {
                array_map([Images::class, 'delete'], $newImages);
            }
        }

        if ($errors !== []) {
            self::form($values, $errors);
            return;
        }

        $images = [];
        foreach (['logo', 'cover'] as $kind) {
            $old = Settings::get($kind) ?: null;
            if (isset($newImages[$kind])) {
                $images[$kind] = $newImages[$kind];
            } elseif (Http::input('remove_' . $kind) === '1') {
                $images[$kind] = '';
            }
            if (array_key_exists($kind, $images) && $old !== null) {
                Images::delete($old);
            }
        }

        Settings::setMany($values + $images);
        Session::flash('success', t('common.saved'));
        Http::redirect(self::PAGE);
    }

    /** @return array<string, string> */
    private static function validate(array $values): array
    {
        $errors = [];
        $length = static fn (string $field, int $min, int $max): bool
            => mb_strlen($values[$field]) >= $min && mb_strlen($values[$field]) <= $max;

        if (!$length('restaurant_name', 1, 120)) {
            $errors['restaurant_name'] = t('install.error.restaurant_name');
        }
        if (!$length('tagline', 0, 160)) {
            $errors['tagline'] = t('restaurant.error.tagline');
        }
        if (!$length('address', 0, 255)) {
            $errors['address'] = t('restaurant.error.address');
        }
        if ($values['maps_url'] !== '' && !self::isHttpUrl($values['maps_url'], true)) {
            $errors['maps_url'] = t('restaurant.error.maps_url');
        }
        if ($values['whatsapp'] !== '' && !preg_match('/^\d{8,15}$/', $values['whatsapp'])) {
            $errors['whatsapp'] = t('restaurant.error.whatsapp');
        }
        if ($values['instagram'] !== '' && !preg_match('/^[A-Za-z0-9._]{1,30}$/', $values['instagram'])) {
            $errors['instagram'] = t('restaurant.error.instagram');
        }
        if (!isset(I18n::LOCALES[$values['menu_language']])) {
            $errors['menu_language'] = t('install.error.language');
        }
        if (!in_array($values['timezone'], DateTimeZone::listIdentifiers(), true)) {
            $errors['timezone'] = t('install.error.timezone');
        }
        if (!preg_match('/^[A-Z]{3}$/', $values['currency_code'])) {
            $errors['currency_code'] = t('install.error.currency_code');
        }
        if (!$length('currency_symbol', 1, 8)) {
            $errors['currency_symbol'] = t('install.error.currency_symbol');
        }
        if (!in_array($values['currency_decimals'], ['0', '2'], true)) {
            $errors['currency_decimals'] = t('restaurant.error.decimals');
        }
        if (!in_array($values['number_format'], Money::FORMATS, true)) {
            $errors['number_format'] = t('restaurant.error.number_format');
        }
        if (!self::isHttpUrl($values['menu_url'], false)) {
            $errors['menu_url'] = t('restaurant.error.menu_url');
        }
        return $errors;
    }

    private static function isHttpUrl(string $url, bool $allowPath): bool
    {
        if (strlen($url) > 500 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }
        return $allowPath || (($parts['path'] ?? '') === '' && empty($parts['query']) && empty($parts['fragment']));
    }

    /** Accepts @name, name or a profile link and keeps only the user name. */
    private static function instagramUser(string $input): string
    {
        $input = trim($input);
        if (preg_match('#instagram\.com/([A-Za-z0-9._]+)#i', $input, $matches)) {
            return $matches[1];
        }
        return ltrim($input, '@');
    }

    /** The QR code only works for customers when it points to a public domain. */
    public static function menuUrlWarning(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        return $host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')
            || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false;
    }

    private static function form(array $values, array $errors): void
    {
        self::render('restaurant', 'restaurant', [
            'title' => t('nav.restaurant'),
            'values' => $values,
            'errors' => $errors,
            'logo' => Settings::get('logo'),
            'cover' => Settings::get('cover'),
            'timezones' => DateTimeZone::listIdentifiers(),
            'menuUrlWarning' => self::menuUrlWarning($values['menu_url']),
        ]);
    }
}
