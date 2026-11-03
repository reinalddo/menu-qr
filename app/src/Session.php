<?php

declare(strict_types=1);

namespace MenuQr;

final class Session
{
    /** Matches session.gc_maxlifetime: the admin stays signed in on their phone for a week. */
    private const LIFETIME = 604800;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('menuqr_session');
        session_set_cookie_params([
            'lifetime' => self::LIFETIME,
            'path' => '/',
            'secure' => Http::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /** One-time message shown on the next page. */
    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int, array{type: string, message: string}> */
    public static function takeFlashes(): array
    {
        self::start();
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($flashes) ? $flashes : [];
    }
}
