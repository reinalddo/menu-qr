<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Auth;
use MenuQr\Csrf;
use MenuQr\Http;
use MenuQr\Models\Dishes;

/** The everyday screen: mark dishes as sold out or available with one tap. */
final class AvailabilityController extends AdminController
{
    public static function show(): void
    {
        self::admin();
        $groups = [];
        foreach (Dishes::all() as $dish) {
            $groups[$dish['category_name']][] = $dish;
        }
        self::render('availability', 'availability', [
            'title' => t('nav.availability'),
            'groups' => $groups,
        ]);
    }

    public static function toggle(int $id): void
    {
        if (self::wantsJson()) {
            // fetch() from admin.js: answer with JSON instead of redirecting.
            if (Auth::admin() === null || !Csrf::valid()) {
                self::json(['ok' => false, 'error' => t('error.csrf')], 403);
                return;
            }
            $available = Http::input('available') === '1';
            $found = Dishes::setAvailable($id, $available);
            self::json(['ok' => $found, 'available' => $available], $found ? 200 : 404);
            return;
        }

        self::guardPost('/admin/availability');
        Dishes::setAvailable($id, Http::input('available') === '1');
        Http::redirect('/admin/availability#dish-' . $id);
    }
}
