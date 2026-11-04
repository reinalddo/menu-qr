<?php

declare(strict_types=1);

namespace MenuQr;

final class Money
{
    public const FORMATS = ['1.234,56', '1,234.56'];

    /** Formats an amount with the restaurant's main currency. */
    public static function format(float|string $amount): string
    {
        return Settings::get('currency_symbol', '$') . ' '
            . self::number((float) $amount, (int) Settings::get('currency_decimals', '2'));
    }

    public static function secondEnabled(): bool
    {
        return Settings::get('second_currency_enabled') === '1' && (float) Settings::get('second_currency_rate', '0') > 0;
    }

    /** The amount converted with the exchange rate, or null when the second currency is off. */
    public static function formatSecond(float|string $amount): ?string
    {
        if (!self::secondEnabled()) {
            return null;
        }
        $converted = (float) $amount * (float) Settings::get('second_currency_rate');
        return Settings::get('second_currency_symbol') . ' '
            . self::number($converted, (int) Settings::get('second_currency_decimals', '2'));
    }

    public static function number(float $value, int $decimals): string
    {
        $comma = Settings::get('number_format', '1,234.56') === '1.234,56';
        return number_format($value, $decimals, $comma ? ',' : '.', $comma ? '.' : ',');
    }

    /**
     * Reads a price typed as 12.50, 12,50, 1.234,56 or 1,234.56.
     * Returns null when it is not a valid amount.
     */
    public static function parse(string $input): ?string
    {
        $value = self::normalize($input);
        if ($value === null || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            return null;
        }
        return number_format((float) $value, 2, '.', '');
    }

    /** Exchange rate: up to 6 decimals and greater than zero. */
    public static function parseRate(string $input): ?string
    {
        $value = self::normalize($input);
        if ($value === null || !preg_match('/^\d{1,9}(\.\d{1,6})?$/', $value) || (float) $value <= 0) {
            return null;
        }
        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }

    /** Value to put back into a price input, written the way the admin types it. */
    public static function input(float|string|null $amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }
        $comma = Settings::get('number_format', '1,234.56') === '1.234,56';
        return number_format((float) $amount, $decimals, $comma ? ',' : '.', '');
    }

    /** Rate shown in an input without trailing zeros: 36,5 instead of 36,500000. */
    public static function rateInput(string $rate): string
    {
        if ($rate === '') {
            return '';
        }
        $comma = Settings::get('number_format', '1,234.56') === '1.234,56';
        return $comma ? str_replace('.', ',', $rate) : $rate;
    }

    private static function normalize(string $input): ?string
    {
        $value = str_replace([' ', "\u{00A0}"], '', trim($input));
        if ($value === '') {
            return null;
        }
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        if ($lastComma !== false && $lastDot !== false) {
            // The separator that appears last is the decimal one.
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $thousands = $decimal === ',' ? '.' : ',';
            $value = str_replace([$thousands, $decimal], ['', '.'], $value);
        } elseif ($lastComma !== false) {
            $value = str_replace(',', '.', $value);
        }
        return $value;
    }
}
