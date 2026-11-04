<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

spl_autoload_register(static function (string $class): void {
    $prefix = 'MenuQr\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Composer packages live outside app/ (/var/www/vendor), so the dev bind mount of app/ does not hide them.
$composerAutoload = dirname(APP_ROOT) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

require __DIR__ . '/helpers.php';
