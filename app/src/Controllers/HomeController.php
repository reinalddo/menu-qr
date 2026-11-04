<?php

declare(strict_types=1);

namespace MenuQr\Controllers;

use MenuQr\Colors;
use MenuQr\Hours;
use MenuQr\Http;
use MenuQr\I18n;
use MenuQr\Images;
use MenuQr\Installation;
use MenuQr\Models\Menu;
use MenuQr\Settings;
use MenuQr\View;

/** The public menu customers open from the QR code. */
final class HomeController
{
    public static function show(): void
    {
        if (!Installation::isComplete()) {
            Http::redirect('/install', 302);
        }

        I18n::setLocale(Settings::get('menu_language', I18n::DEFAULT));
        $settings = Settings::all();
        $hours = Hours::load();
        $brand = Colors::brand();
        $menuUrl = rtrim($settings['menu_url'] ?? Http::baseUrl(), '/');
        $shareImage = Images::url(($settings['cover'] ?? '') ?: (($settings['logo'] ?? '') ?: null));

        header('Cache-Control: no-cache');
        View::render('menu/index', [
            'title' => $settings['restaurant_name'] ?? '',
            's' => $settings,
            'menu' => Menu::load(),
            'hours' => $hours,
            'hoursConfigured' => Hours::isConfigured($hours),
            'status' => Hours::status($hours, $settings['timezone'] ?? 'UTC'),
            'today' => (int) (new \DateTimeImmutable('now', new \DateTimeZone($settings['timezone'] ?? 'UTC')))->format('N'),
            'brand' => $brand,
            'brandText' => Colors::textOn($brand),
            'brandInk' => Colors::ink($brand),
            'menuUrl' => $menuUrl,
            'shareImage' => $shareImage !== null ? $menuUrl . $shareImage : null,
        ], 'layout/menu');
    }
}
