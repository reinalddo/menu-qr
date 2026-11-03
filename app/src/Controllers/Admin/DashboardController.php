<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Models\Dishes;
use MenuQr\Settings;

final class DashboardController extends AdminController
{
    public static function show(): void
    {
        self::admin();
        self::render('dashboard', 'dashboard', [
            'title' => t('nav.dashboard'),
            'counts' => Dishes::counts(),
            'menuUrl' => Settings::get('menu_url'),
        ]);
    }
}
