<?php

declare(strict_types=1);

namespace MenuQr;

final class Config
{
    public static function dataDir(): string
    {
        return rtrim(self::env('MENUQR_DATA_DIR', '/var/www/data'), '/');
    }

    public static function secretsDir(): string
    {
        return rtrim(self::env('MENUQR_SECRETS_DIR', '/run/menuqr'), '/');
    }

    public static function dbHost(): string
    {
        return self::env('MENUQR_DB_HOST', 'db');
    }

    public static function dbName(): string
    {
        return self::env('MENUQR_DB_NAME', 'menuqr');
    }

    public static function dbUser(): string
    {
        return self::env('MENUQR_DB_USER', 'menuqr');
    }

    public static function dbPassword(): string
    {
        $file = self::secretsDir() . '/db_password';
        if (!is_readable($file)) {
            throw new \RuntimeException("Database password file not found: {$file}");
        }
        return trim((string) file_get_contents($file));
    }

    public static function recoveryEnabled(): bool
    {
        return in_array(strtolower(self::env('MENUQR_RECOVERY', '0')), ['1', 'true', 'yes', 'on'], true);
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
