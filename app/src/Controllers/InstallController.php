<?php

declare(strict_types=1);

namespace MenuQr\Controllers;

use DateTimeZone;
use MenuQr\Codes;
use MenuQr\Csrf;
use MenuQr\Db;
use MenuQr\Http;
use MenuQr\I18n;
use MenuQr\Installation;
use MenuQr\RateLimiter;
use MenuQr\Router;
use MenuQr\Session;
use MenuQr\Settings;
use MenuQr\View;

final class InstallController
{
    private const LIMIT_SCOPE = 'setup';
    private const DEFAULT_BRAND_COLOR = '#c2410c';

    public static function handle(): void
    {
        if (Installation::isComplete()) {
            Router::notFound();
            return;
        }

        if (Codes::read(Codes::SETUP) === null) {
            // The data volume was emptied after boot: create a new code and log it.
            $code = Codes::ensure(Codes::SETUP);
            error_log(Installation::banner(['Setup code: ' . Codes::format($code)]));
        }

        $values = [
            'language' => I18n::locale(),
            'restaurant_name' => '',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'email' => '',
        ];
        $errors = [];

        if (Http::isPost()) {
            foreach (array_keys($values) as $field) {
                $values[$field] = Http::input($field);
            }
            $values['currency_code'] = strtoupper($values['currency_code']);
            if (isset(I18n::LOCALES[$values['language']])) {
                I18n::setLocale($values['language']);
            }

            $errors = self::validate($values);
            if ($errors === []) {
                self::install($values, Http::input('password'));
                Session::start();
                Session::regenerate();
                $_SESSION['install_done'] = true;
                Http::redirect('/install/done');
            }
        }

        View::render('install/form', [
            'title' => t('install.title'),
            'values' => $values,
            'errors' => $errors,
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public static function done(): void
    {
        Session::start();
        if (empty($_SESSION['install_done'])) {
            Router::notFound();
            return;
        }
        unset($_SESSION['install_done']);

        I18n::setLocale(Settings::get('menu_language', I18n::DEFAULT));
        View::render('install/done', [
            'title' => t('install.done.title'),
            'menuUrl' => Settings::get('menu_url'),
        ]);
    }

    /** @return array<string, string> field => message */
    private static function validate(array $values): array
    {
        $errors = [];

        if (!Csrf::valid()) {
            return ['form' => t('error.csrf')];
        }
        if (RateLimiter::blocked(self::LIMIT_SCOPE)) {
            return ['form' => t('error.too_many_attempts')];
        }
        if (!Codes::verify(Codes::SETUP, Http::input('setup_code'))) {
            RateLimiter::fail(self::LIMIT_SCOPE);
            $errors['setup_code'] = t('install.error.code');
        }

        $nameLength = mb_strlen($values['restaurant_name']);
        if ($nameLength < 1 || $nameLength > 120) {
            $errors['restaurant_name'] = t('install.error.restaurant_name');
        }
        if (!isset(I18n::LOCALES[$values['language']])) {
            $errors['language'] = t('install.error.language');
        }
        if (!in_array($values['timezone'], DateTimeZone::listIdentifiers(), true)) {
            $errors['timezone'] = t('install.error.timezone');
        }
        if (!preg_match('/^[A-Z]{3}$/', $values['currency_code'])) {
            $errors['currency_code'] = t('install.error.currency_code');
        }
        $symbolLength = mb_strlen($values['currency_symbol']);
        if ($symbolLength < 1 || $symbolLength > 8) {
            $errors['currency_symbol'] = t('install.error.currency_symbol');
        }
        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || strlen($values['email']) > 190) {
            $errors['email'] = t('install.error.email');
        }

        $password = Http::input('password');
        if (mb_strlen($password) < 10) {
            $errors['password'] = t('install.error.password_length');
        } elseif ($password !== Http::input('password_confirm')) {
            $errors['password_confirm'] = t('install.error.password_match');
        }

        return $errors;
    }

    private static function install(array $values, string $password): void
    {
        $pdo = Db::pdo();
        $pdo->query("SELECT GET_LOCK('menuqr_install', 10)");
        try {
            if (Installation::isComplete()) {
                Http::redirect('/');
            }

            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO admins (email, password_hash, language) VALUES (?, ?, ?)')
                ->execute([$values['email'], password_hash($password, PASSWORD_DEFAULT), $values['language']]);

            Settings::setMany([
                'restaurant_name' => $values['restaurant_name'],
                'menu_language' => $values['language'],
                'timezone' => $values['timezone'],
                'currency_code' => $values['currency_code'],
                'currency_symbol' => $values['currency_symbol'],
                'currency_decimals' => 2,
                'number_format' => $values['language'] === 'es' ? '1.234,56' : '1,234.56',
                'menu_url' => Http::baseUrl(),
                'brand_color' => self::DEFAULT_BRAND_COLOR,
                'show_powered_by' => true,
                'installed_at' => gmdate('c'),
            ]);
            $pdo->commit();

            Codes::clear(Codes::SETUP);
            RateLimiter::clear(self::LIMIT_SCOPE);
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('menuqr_install')");
        }
    }
}
