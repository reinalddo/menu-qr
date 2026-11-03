<?php

declare(strict_types=1);

namespace MenuQr;

final class Money
{
    public const FORMATS = ['1.234,56', '1,234.56'];

    /** Formats an amount with the restaurant's main currency. */
    public static function format(float|string $amount): string
    {
        $decimals = (int) Settings::get('currency_decimals', '2');
        $comma = Settings::get('number_format', '1,234.56') === '1.234,56';
        $number = number_format((float) $amount, $decimals, $comma ? ',' : '.', $comma ? '.' : ',');
        return Settings::get('currency_symbol', '$') . ' ' . $number;
    }

    /**
     * Reads a price typed as 12.50, 12,50, 1.234,56 or 1,234.56.
     * Returns null when it is not a valid amount.
     */
    public static function parse(string $input): ?string
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
            $value = str_replace($thousands, '', $value);
            $value = str_replace($decimal, '.', $value);
        } elseif ($lastComma !== false) {
            $value = str_replace(',', '.', $value);
        }

        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            return null;
        }
        return number_format((float) $value, 2, '.', '');
    }

    /** Value to put back into a price input, written the way the admin types it. */
    public static function input(float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }
        $comma = Settings::get('number_format', '1,234.56') === '1.234,56';
        return number_format((float) $amount, 2, $comma ? ',' : '.', '');
    }
}
