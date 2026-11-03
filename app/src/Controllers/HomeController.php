<?php

declare(strict_types=1);

namespace MenuQr\Controllers;

use MenuQr\Http;
use MenuQr\I18n;
use MenuQr\Installation;
use MenuQr\Settings;
use MenuQr\View;

final class HomeController
{
    public static function show(): void
    {
        if (!Installation::isComplete()) {
            Http::redirect('/install', 302);
        }

        // Placeholder until the public menu is built (phase 4).
        I18n::setLocale(Settings::get('menu_language', I18n::DEFAULT));
        View::render('home/placeholder', [
            'title' => Settings::get('restaurant_name'),
            'restaurantName' => Settings::get('restaurant_name'),
        ]);
    }
}
