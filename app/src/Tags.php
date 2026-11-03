<?php

declare(strict_types=1);

namespace MenuQr;

/** Fixed set of dish labels; their texts live in the language files as tag.<code>. */
final class Tags
{
    public const ALL = ['vegetarian', 'vegan', 'spicy', 'gluten_free', 'new', 'recommended'];

    /** @param mixed $input value of $_POST['tags'] */
    public static function fromInput(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }
        return array_values(array_intersect(self::ALL, array_map('strval', $input)));
    }
}
