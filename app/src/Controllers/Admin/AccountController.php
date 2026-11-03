<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Db;
use MenuQr\Http;
use MenuQr\I18n;
use MenuQr\Session;

final class AccountController extends AdminController
{
    private const PAGE = '/admin/account';

    public static function edit(): void
    {
        $admin = self::admin();
        self::form($admin, [], []);
    }

    public static function updateProfile(): void
    {
        $admin = self::guardPost(self::PAGE);
        $values = ['email' => Http::input('email'), 'language' => Http::input('language')];
        $errors = [];

        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || strlen($values['email']) > 190) {
            $errors['email'] = t('install.error.email');
        } else {
            $taken = Db::pdo()->prepare('SELECT EXISTS(SELECT 1 FROM admins WHERE email = ? AND id <> ?)');
            $taken->execute([$values['email'], $admin['id']]);
            if ($taken->fetchColumn()) {
                $errors['email'] = t('account.error.email_taken');
            }
        }
        if (!isset(I18n::LOCALES[$values['language']])) {
            $errors['language'] = t('install.error.language');
        }

        if ($errors !== []) {
            self::form($admin, $values, $errors);
            return;
        }

        Db::pdo()->prepare('UPDATE admins SET email = ?, language = ? WHERE id = ?')
            ->execute([$values['email'], $values['language'], $admin['id']]);
        I18n::setLocale($values['language']);
        Session::flash('success', t('common.saved'));
        Http::redirect(self::PAGE);
    }

    public static function updatePassword(): void
    {
        $admin = self::guardPost(self::PAGE);
        $errors = [];

        $hash = Db::pdo()->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $hash->execute([$admin['id']]);
        if (!password_verify(Http::input('current_password'), (string) $hash->fetchColumn())) {
            $errors['current_password'] = t('account.error.current_password');
        }
        $password = Http::input('password');
        if (mb_strlen($password) < 10) {
            $errors['password'] = t('install.error.password_length');
        } elseif ($password !== Http::input('password_confirm')) {
            $errors['password_confirm'] = t('install.error.password_match');
        }

        if ($errors !== []) {
            self::form($admin, [], $errors);
            return;
        }

        Db::pdo()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
        Session::regenerate();
        Session::flash('success', t('account.password_changed'));
        Http::redirect(self::PAGE);
    }

    private static function form(array $admin, array $values, array $errors): void
    {
        self::render('account', 'account', [
            'title' => t('nav.account'),
            'values' => $values + ['email' => $admin['email'], 'language' => $admin['language']],
            'errors' => $errors,
        ]);
    }
}
