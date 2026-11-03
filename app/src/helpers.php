<?php

declare(strict_types=1);

use MenuQr\I18n;

/** Escapes a value for HTML output. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL of a file in public/ with its modification time, so browsers reload it after an update. */
function asset(string $path): string
{
    $file = APP_ROOT . '/public' . $path;
    return $path . '?v=' . (is_file($file) ? filemtime($file) : APP_VERSION);
}

/** Returns the translated string for the active locale. */
function t(string $key, array $params = []): string
{
    return I18n::get($key, $params);
}
