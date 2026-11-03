<?php

declare(strict_types=1);

namespace MenuQr;

/**
 * One-time codes stored in the data volume and shown only in the container
 * logs: "setup" opens the installer, "recovery" resets the admin password.
 */
final class Codes
{
    public const SETUP = 'setup';
    public const RECOVERY = 'recovery';

    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const LENGTH = 12;

    public static function read(string $type): ?string
    {
        $file = self::path($type);
        if (!is_file($file)) {
            return null;
        }
        $code = trim((string) file_get_contents($file));
        return $code === '' ? null : $code;
    }

    public static function ensure(string $type): string
    {
        return self::read($type) ?? self::create($type);
    }

    public static function create(string $type): string
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        $file = self::path($type);
        file_put_contents($file, $code, LOCK_EX);
        chmod($file, 0600);
        // The entrypoint runs as root; Apache (www-data) must be able to read and delete it.
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            chown($file, 'www-data');
        }
        return $code;
    }

    public static function verify(string $type, string $input): bool
    {
        $code = self::read($type);
        return $code !== null && hash_equals($code, self::normalize($input));
    }

    public static function clear(string $type): void
    {
        $file = self::path($type);
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function format(string $code): string
    {
        return implode('-', str_split($code, 4));
    }

    private static function normalize(string $input): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $input));
    }

    private static function path(string $type): string
    {
        return Config::dataDir() . '/setup/' . $type . '-code';
    }
}
