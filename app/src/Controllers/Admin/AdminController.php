<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Auth;
use MenuQr\Csrf;
use MenuQr\Http;
use MenuQr\Session;
use MenuQr\Settings;
use MenuQr\View;

abstract class AdminController
{
    /** Signed-in admin, or a redirect to the login page. */
    protected static function admin(): array
    {
        return Auth::requireAdmin();
    }

    /** For POST actions: signed in and a valid CSRF token, otherwise back to $back. */
    protected static function guardPost(string $back): array
    {
        $admin = Auth::requireAdmin();
        if (!Csrf::valid()) {
            Session::flash('error', t('error.csrf'));
            Http::redirect($back);
        }
        return $admin;
    }

    protected static function render(string $template, string $section, array $data = []): void
    {
        View::render('admin/' . $template, $data + [
            'section' => $section,
            'restaurantName' => Settings::get('restaurant_name'),
            'flashes' => Session::takeFlashes(),
        ], 'layout/admin');
    }

    protected static function back(string $path, string $message, string $type = 'success'): never
    {
        Session::flash($type, $message);
        Http::redirect($path);
    }

    protected static function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    protected static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
