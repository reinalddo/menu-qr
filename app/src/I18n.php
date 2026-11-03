<?php

declare(strict_types=1);

namespace MenuQr;

final class I18n
{
    public const LOCALES = ['es' => 'Español', 'en' => 'English'];
    public const DEFAULT = 'es';
    private const COOKIE = 'menuqr_lang';

    private static string $locale = self::DEFAULT;
    /** @var array<string, string> */
    private static array $strings = [];

    public static function setLocale(string $locale): void
    {
        if (!isset(self::LOCALES[$locale])) {
            $locale = self::DEFAULT;
        }
        self::$locale = $locale;
        self::$strings = require APP_ROOT . '/lang/' . $locale . '.php';
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, array $params = []): string
    {
        $text = self::$strings[$key] ?? $key;
        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }
        return $text;
    }

    /** Locale for screens shown before anyone logs in: ?lang, then cookie, then browser. */
    public static function detect(): string
    {
        $requested = $_GET['lang'] ?? null;
        if (is_string($requested) && isset(self::LOCALES[$requested])) {
            setcookie(self::COOKIE, $requested, [
                'expires' => time() + 31536000,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => Http::isHttps(),
            ]);
            return $requested;
        }

        $cookie = $_COOKIE[self::COOKIE] ?? null;
        if (is_string($cookie) && isset(self::LOCALES[$cookie])) {
            return $cookie;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        foreach (explode(',', $accept) as $part) {
            $code = substr(trim($part), 0, 2);
            if (isset(self::LOCALES[$code])) {
                return $code;
            }
        }
        return self::DEFAULT;
    }
}
