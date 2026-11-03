<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Auth;
use MenuQr\Csrf;
use MenuQr\Http;
use MenuQr\Installation;
use MenuQr\View;

final class AuthController
{
    public static function login(): void
    {
        if (!Installation::isComplete()) {
            Http::redirect('/install', 302);
        }
        if (Auth::admin() !== null) {
            Http::redirect('/admin', 302);
        }

        $email = '';
        $error = null;
        if (Http::isPost()) {
            $email = Http::input('email');
            $error = Csrf::valid() ? Auth::attempt($email, Http::input('password')) : t('error.csrf');
            if ($error === null) {
                Http::redirect('/admin');
            }
        }

        View::render('admin/login', [
            'title' => t('login.title'),
            'email' => $email,
            'error' => $error,
        ]);
    }

    public static function logout(): void
    {
        if (Csrf::valid()) {
            Auth::logout();
        }
        Http::redirect('/admin/login');
    }
}
