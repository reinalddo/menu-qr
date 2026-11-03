<?php

declare(strict_types=1);

namespace MenuQr;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('menuqr_session');
        session_set_cookie_params([
            'lifetime' => 0,
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
}
