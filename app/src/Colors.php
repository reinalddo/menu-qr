<?php

declare(strict_types=1);

namespace MenuQr;

/** Derives readable colors from the restaurant's brand color. */
final class Colors
{
    public const DEFAULT = '#c2410c';
    public const PRESETS = ['#c2410c', '#b91c1c', '#be185d', '#7c3aed', '#1d4ed8', '#0f766e', '#15803d', '#a16207', '#1c1917'];

    public static function brand(): string
    {
        $color = strtolower(Settings::get('brand_color', self::DEFAULT));
        return self::valid($color) ? $color : self::DEFAULT;
    }

    public static function valid(string $color): bool
    {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) === 1;
    }

    /** White or near-black, whichever reads better on top of $color. */
    public static function textOn(string $color): string
    {
        return self::contrast($color, '#ffffff') >= self::contrast($color, '#1c1917') ? '#ffffff' : '#1c1917';
    }

    /** The brand color darkened until it reads as text on white (WCAG AA, 4.5:1). */
    public static function ink(string $color): string
    {
        $rgb = self::rgb($color);
        for ($step = 0; $step < 20 && self::contrast(self::hex($rgb), '#ffffff') < 4.5; $step++) {
            $rgb = array_map(static fn (int $channel): int => (int) round($channel * 0.9), $rgb);
        }
        return self::hex($rgb);
    }

    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $color): float
    {
        $channels = array_map(static function (int $value): float {
            $c = $value / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($color));
        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /** @return int[] */
    private static function rgb(string $color): array
    {
        return array_map('hexdec', str_split(ltrim($color, '#'), 2));
    }

    private static function hex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}
