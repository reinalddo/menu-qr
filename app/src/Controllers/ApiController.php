<?php

declare(strict_types=1);

namespace MenuQr\Controllers;

use MenuQr\Models\Stats;

/**
 * Beacons sent by menu.js: one visit per browser session, one view per dish opened.
 * Public and without CSRF: there is no session, and the worst abuse is a wrong counter.
 */
final class ApiController
{
    public static function visit(): void
    {
        if (Stats::shouldCount()) {
            Stats::recordVisit((string) ($_POST['source'] ?? 'link'));
        }
        http_response_code(204);
    }

    public static function view(): void
    {
        $dishId = filter_var($_POST['dish'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($dishId !== false && Stats::shouldCount()) {
            Stats::recordDishView($dishId);
        }
        http_response_code(204);
    }
}
