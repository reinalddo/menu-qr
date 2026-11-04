<?php

declare(strict_types=1);

namespace MenuQr;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Weekly opening hours stored as JSON: {"1": [["12:00","15:00"], ["19:00","23:00"]], ..., "7": []}
 * Days follow ISO-8601 (1 = Monday). A range whose end is not after its start
 * crosses midnight, e.g. ["19:00","02:00"].
 */
final class Hours
{
    public const MAX_RANGES = 2;

    /** @return array<int, array<int, array{0: string, 1: string}>> */
    public static function load(): array
    {
        $decoded = json_decode(Settings::get('opening_hours', '{}'), true);
        $hours = [];
        for ($day = 1; $day <= 7; $day++) {
            $hours[$day] = [];
            foreach ((is_array($decoded) ? ($decoded[(string) $day] ?? []) : []) as $range) {
                if (is_array($range) && count($range) === 2 && self::validTime($range[0]) && self::validTime($range[1])) {
                    $hours[$day][] = [$range[0], $range[1]];
                }
            }
        }
        return $hours;
    }

    public static function isConfigured(array $hours): bool
    {
        foreach ($hours as $ranges) {
            if ($ranges !== []) {
                return true;
            }
        }
        return false;
    }

    public static function validTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    /**
     * @return array{open: bool, closes_at: ?string, opens_at: ?string, opens_in_days: ?int, opens_day: ?int}
     *         opens_in_days: 0 = later today, 1 = tomorrow, 2..7 = that many days ahead
     */
    public static function status(array $hours, string $timezone, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', self::zone($timezone));
        $now = $now->setTimezone(self::zone($timezone));
        $day = (int) $now->format('N');
        $minute = (int) $now->format('G') * 60 + (int) $now->format('i');
        $yesterday = $day === 1 ? 7 : $day - 1;

        foreach ($hours[$day] as [$start, $end]) {
            $from = self::minutes($start);
            $to = self::minutes($end);
            $open = $to > $from ? ($minute >= $from && $minute < $to) : $minute >= $from;
            if ($open) {
                return self::result(true, $end);
            }
        }
        foreach ($hours[$yesterday] as [$start, $end]) {
            if (self::minutes($end) <= self::minutes($start) && $minute < self::minutes($end)) {
                return self::result(true, $end);
            }
        }

        $later = array_filter($hours[$day], static fn (array $range): bool => self::minutes($range[0]) > $minute);
        if ($later !== []) {
            usort($later, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));
            return self::result(false, null, $later[0][0], 0, $day);
        }
        for ($ahead = 1; $ahead <= 7; $ahead++) {
            $next = ($day - 1 + $ahead) % 7 + 1;
            if ($hours[$next] !== []) {
                $starts = array_column($hours[$next], 0);
                sort($starts);
                return self::result(false, null, $starts[0], $ahead, $next);
            }
        }
        return self::result(false);
    }

    /** Text such as "Open now · closes at 23:00" for the menu header. */
    public static function label(array $status): string
    {
        if ($status['open']) {
            return t('hours.open_now') . ' · ' . t('hours.closes_at', ['time' => $status['closes_at']]);
        }
        if ($status['opens_at'] === null) {
            return t('hours.closed');
        }
        $when = match ($status['opens_in_days']) {
            0 => t('hours.opens_today', ['time' => $status['opens_at']]),
            1 => t('hours.opens_tomorrow', ['time' => $status['opens_at']]),
            default => t('hours.opens_on', ['day' => t('day.' . $status['opens_day']), 'time' => $status['opens_at']]),
        };
        return t('hours.closed') . ' · ' . $when;
    }

    /** "12:00 – 15:00, 19:00 – 23:00" or "Closed". */
    public static function dayText(array $ranges): string
    {
        if ($ranges === []) {
            return t('hours.closed');
        }
        return implode(', ', array_map(static fn (array $range): string => $range[0] . ' – ' . $range[1], $ranges));
    }

    private static function result(bool $open, ?string $closesAt = null, ?string $opensAt = null, ?int $inDays = null, ?int $opensDay = null): array
    {
        return ['open' => $open, 'closes_at' => $closesAt, 'opens_at' => $opensAt, 'opens_in_days' => $inDays, 'opens_day' => $opensDay];
    }

    private static function minutes(string $time): int
    {
        [$hour, $minute] = explode(':', $time);
        return (int) $hour * 60 + (int) $minute;
    }

    private static function zone(string $timezone): DateTimeZone
    {
        try {
            return new DateTimeZone($timezone);
        } catch (\Exception) {
            return new DateTimeZone('UTC');
        }
    }
}
