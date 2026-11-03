<?php

declare(strict_types=1);

namespace MenuQr;

final class Settings
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /** @return array<string, string> */
    public static function all(): array
    {
        if (self::$cache === null) {
            $rows = Db::pdo()->query('SELECT name, value FROM settings')->fetchAll(\PDO::FETCH_KEY_PAIR);
            self::$cache = array_map('strval', $rows);
        }
        return self::$cache;
    }

    public static function get(string $name, string $default = ''): string
    {
        return self::all()[$name] ?? $default;
    }

    /** @param array<string, string|int|float|bool> $values */
    public static function setMany(array $values): void
    {
        $statement = Db::pdo()->prepare(
            'INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        foreach ($values as $name => $value) {
            $statement->execute([$name, is_bool($value) ? ($value ? '1' : '0') : (string) $value]);
        }
        self::$cache = null;
    }
}
