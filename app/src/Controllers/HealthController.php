<?php

declare(strict_types=1);

namespace MenuQr\Controllers;

use MenuQr\Db;

final class HealthController
{
    public static function show(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            Db::pdo()->query('SELECT 1');
            echo 'ok';
        } catch (\Throwable $exception) {
            http_response_code(503);
            echo 'database unavailable';
        }
    }
}
