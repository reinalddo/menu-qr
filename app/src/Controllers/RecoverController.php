<?php

declare(strict_types=1);

namespace MenuQr\Controllers;

use MenuQr\Codes;
use MenuQr\Config;
use MenuQr\Csrf;
use MenuQr\Db;
use MenuQr\Http;
use MenuQr\Installation;
use MenuQr\RateLimiter;
use MenuQr\Router;
use MenuQr\View;

/** Password reset enabled with MENUQR_RECOVERY=1; the code is shown in the logs. */
final class RecoverController
{
    private const LIMIT_SCOPE = 'recovery';

    public static function handle(): void
    {
        if (!Config::recoveryEnabled() || Codes::read(Codes::RECOVERY) === null || !Installation::isComplete()) {
            Router::notFound();
            return;
        }

        $email = '';
        $errors = [];

        if (Http::isPost()) {
            $email = Http::input('email');
            $errors = self::validate($email);
            if ($errors === []) {
                Db::pdo()->prepare('UPDATE admins SET password_hash = ? WHERE email = ?')
                    ->execute([password_hash(Http::input('password'), PASSWORD_DEFAULT), $email]);
                Codes::clear(Codes::RECOVERY);
                RateLimiter::clear(self::LIMIT_SCOPE);
                View::render('recover/done', ['title' => t('recover.done.title')]);
                return;
            }
        }

        View::render('recover/form', [
            'title' => t('recover.title'),
            'email' => $email,
            'errors' => $errors,
        ]);
    }

    /** @return array<string, string> */
    private static function validate(string $email): array
    {
        if (!Csrf::valid()) {
            return ['form' => t('error.csrf')];
        }
        if (RateLimiter::blocked(self::LIMIT_SCOPE)) {
            return ['form' => t('error.too_many_attempts')];
        }

        $exists = Db::pdo()->prepare('SELECT EXISTS(SELECT 1 FROM admins WHERE email = ?)');
        $exists->execute([$email]);
        if (!Codes::verify(Codes::RECOVERY, Http::input('recovery_code')) || !$exists->fetchColumn()) {
            RateLimiter::fail(self::LIMIT_SCOPE);
            return ['form' => t('recover.error.code_or_email')];
        }

        $password = Http::input('password');
        if (mb_strlen($password) < 10) {
            return ['password' => t('install.error.password_length')];
        }
        if ($password !== Http::input('password_confirm')) {
            return ['password_confirm' => t('install.error.password_match')];
        }
        return [];
    }
}
