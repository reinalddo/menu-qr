<?php

declare(strict_types=1);

namespace MenuQr\Models;

use DateTimeImmutable;
use DateTimeZone;
use MenuQr\Auth;
use MenuQr\Db;
use MenuQr\Settings;

/**
 * Daily counters without cookies or IP addresses: one row per day and source,
 * and one per day and dish. Days follow the restaurant's time zone.
 */
final class Stats
{
    public const SOURCES = ['qr', 'link'];

    /** Link previews and crawlers would inflate the numbers every time the menu is shared. */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|slack|discord|'
        . 'embedly|vkshare|pinterest|curl|wget|python|java\/|go-http|headless|lighthouse|monitor|uptime/i';

    public static function shouldCount(): bool
    {
        $agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($agent === '' || preg_match(self::BOT_PATTERN, $agent)) {
            return false;
        }
        // The admin checking their own menu is not a customer. Only look when a session cookie exists,
        // so customers never get one.
        return !(isset($_COOKIE['menuqr_session']) && Auth::admin() !== null);
    }

    public static function recordVisit(string $source): void
    {
        Db::pdo()->prepare(
            'INSERT INTO visit_counts (day, source, views) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE views = views + 1'
        )->execute([self::today(), in_array($source, self::SOURCES, true) ? $source : 'link']);
    }

    /** @return bool false when the dish does not exist or is hidden */
    public static function recordDishView(int $dishId): bool
    {
        $exists = Db::pdo()->prepare('SELECT EXISTS(SELECT 1 FROM dishes WHERE id = ? AND is_visible = 1)');
        $exists->execute([$dishId]);
        if (!$exists->fetchColumn()) {
            return false;
        }
        Db::pdo()->prepare(
            'INSERT INTO dish_view_counts (day, dish_id, views) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE views = views + 1'
        )->execute([self::today(), $dishId]);
        return true;
    }

    /**
     * @return array{today: int, week: int, month: int, qr: int, link: int,
     *               daily: array<int, array{day: string, views: int}>,
     *               top: array<int, array{name: string, views: int}>}
     */
    public static function summary(int $days = 30): array
    {
        $today = new DateTimeImmutable(self::today());
        $from = $today->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
        $weekFrom = $today->modify('-6 days')->format('Y-m-d');

        $statement = Db::pdo()->prepare('SELECT day, source, views FROM visit_counts WHERE day >= ?');
        $statement->execute([$from]);

        $daily = [];
        for ($date = new DateTimeImmutable($from); $date <= $today; $date = $date->modify('+1 day')) {
            $daily[$date->format('Y-m-d')] = 0;
        }
        $result = ['today' => 0, 'week' => 0, 'month' => 0, 'qr' => 0, 'link' => 0];
        foreach ($statement as $row) {
            $views = (int) $row['views'];
            $daily[$row['day']] = ($daily[$row['day']] ?? 0) + $views;
            $result['month'] += $views;
            $result[$row['source'] === 'qr' ? 'qr' : 'link'] += $views;
            if ($row['day'] >= $weekFrom) {
                $result['week'] += $views;
            }
            if ($row['day'] === $today->format('Y-m-d')) {
                $result['today'] += $views;
            }
        }
        $result['daily'] = array_map(
            static fn (string $day, int $views): array => ['day' => $day, 'views' => $views],
            array_keys($daily),
            $daily
        );

        $top = Db::pdo()->prepare(
            'SELECT d.name, SUM(v.views) AS views FROM dish_view_counts v JOIN dishes d ON d.id = v.dish_id
             WHERE v.day >= ? GROUP BY v.dish_id, d.name ORDER BY views DESC, d.name LIMIT 5'
        );
        $top->execute([$from]);
        $result['top'] = array_map(
            static fn (array $row): array => ['name' => $row['name'], 'views' => (int) $row['views']],
            $top->fetchAll()
        );
        return $result;
    }

    private static function today(): string
    {
        try {
            $zone = new DateTimeZone(Settings::get('timezone', 'UTC'));
        } catch (\Exception) {
            $zone = new DateTimeZone('UTC');
        }
        return (new DateTimeImmutable('now', $zone))->format('Y-m-d');
    }
}
