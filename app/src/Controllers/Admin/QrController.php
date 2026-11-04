<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Colors;
use MenuQr\Images;
use MenuQr\Qr;
use MenuQr\Settings;
use MenuQr\View;

final class QrController extends AdminController
{
    public static function show(): void
    {
        self::admin();
        self::render('qr', 'qr', [
            'title' => t('nav.qr'),
            'target' => Qr::target(),
            'menuUrl' => Settings::get('menu_url'),
            'localWarning' => RestaurantController::menuUrlWarning(Settings::get('menu_url')),
        ]);
    }

    public static function svg(): void
    {
        self::admin();
        self::send('image/svg+xml', Qr::svg(), 'svg');
    }

    public static function png(): void
    {
        self::admin();
        self::send('image/png', Qr::png(), 'png');
    }

    /** Printable table cards: one large card or four per page. */
    public static function print(): void
    {
        self::admin();
        $perPage = ($_GET['per_page'] ?? '1') === '4' ? 4 : 1;
        $brand = Colors::brand();
        View::render('admin/qr_print', [
            'title' => t('qr.print.title'),
            'perPage' => $perPage,
            'svg' => 'data:image/svg+xml;base64,' . base64_encode(Qr::svg()),
            'restaurantName' => Settings::get('restaurant_name'),
            'logo' => Images::url(Settings::get('logo') ?: null),
            'menuUrl' => preg_replace('#^https?://#', '', Settings::get('menu_url')),
            'brand' => $brand,
            'brandText' => Colors::textOn($brand),
        ], null);
    }

    private static function send(string $type, string $body, string $extension): void
    {
        header('Content-Type: ' . $type);
        header('Cache-Control: no-store');
        if (isset($_GET['download'])) {
            $name = preg_replace('/[^a-z0-9]+/', '-', strtolower(Settings::get('restaurant_name', 'menu'))) ?: 'menu';
            header('Content-Disposition: attachment; filename="qr-' . trim($name, '-') . '.' . $extension . '"');
        }
        echo $body;
    }
}
