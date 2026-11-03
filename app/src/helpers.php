<?php

declare(strict_types=1);

use MenuQr\I18n;

/** Escapes a value for HTML output. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Returns the translated string for the active locale. */
function t(string $key, array $params = []): string
{
    return I18n::get($key, $params);
}
