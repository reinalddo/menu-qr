<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Models\Dishes;
use MenuQr\Money;
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
            'secondCurrency' => Settings::get('second_currency_enabled') === '1' ? [
                'code' => Settings::get('second_currency_code'),
                'rate' => Money::rateInput(Settings::get('second_currency_rate')),
                'updated_at' => Settings::get('second_currency_rate_updated_at'),
            ] : null,
        ]);
    }
}
