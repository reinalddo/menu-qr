<?php

declare(strict_types=1);

namespace MenuQr;

final class Auth
{
    private const LIMIT_SCOPE = 'login';

    /** @var array<string, mixed>|null|false false = not loaded yet */
    private static array|null|false $admin = false;

    /** @return array<string, mixed>|null */
    public static function admin(): ?array
    {
        if (self::$admin === false) {
            Session::start();
            self::$admin = null;
            $id = $_SESSION['admin_id'] ?? null;
            if (is_int($id)) {
                $statement = Db::pdo()->prepare('SELECT id, email, language FROM admins WHERE id = ?');
                $statement->execute([$id]);
                self::$admin = $statement->fetch() ?: null;
            }
        }
        return self::$admin;
    }

    /** Sends visitors without a session to the login page and sets the admin's language. */
    public static function requireAdmin(): array
    {
        $admin = self::admin();
        if ($admin === null) {
            Http::redirect('/admin/login', 302);
        }
        I18n::setLocale((string) $admin['language']);
        return $admin;
    }

    /** @return string|null error message, or null when signed in */
    public static function attempt(string $email, string $password): ?string
    {
        if (RateLimiter::blocked(self::LIMIT_SCOPE)) {
            return t('error.too_many_attempts');
        }

        $statement = Db::pdo()->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
        $statement->execute([$email]);
        $row = $statement->fetch();

        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            RateLimiter::fail(self::LIMIT_SCOPE);
            return t('login.error.credentials');
        }

        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
            Db::pdo()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
        }

        RateLimiter::clear(self::LIMIT_SCOPE);
        self::login((int) $row['id']);
        return null;
    }

    public static function login(int $adminId): void
    {
        Session::start();
        Session::regenerate();
        $_SESSION['admin_id'] = $adminId;
        self::$admin = false;
        Db::pdo()->prepare('UPDATE admins SET last_login_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$adminId]);
    }

    public static function logout(): void
    {
        Session::start();
        $_SESSION = [];
        Session::regenerate();
        self::$admin = false;
    }
}
