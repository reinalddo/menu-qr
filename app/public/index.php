<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use MenuQr\Controllers\HealthController;
use MenuQr\Controllers\HomeController;
use MenuQr\Controllers\InstallController;
use MenuQr\Controllers\RecoverController;
use MenuQr\I18n;
use MenuQr\Router;
use MenuQr\View;

I18n::setLocale(I18n::detect());

$router = new Router();
$router->get('/', [HomeController::class, 'show']);
$router->get('/healthz', [HealthController::class, 'show']);
$router->get('/install', [InstallController::class, 'handle']);
$router->post('/install', [InstallController::class, 'handle']);
$router->get('/install/done', [InstallController::class, 'done']);
$router->get('/recover', [RecoverController::class, 'handle']);
$router->post('/recover', [RecoverController::class, 'handle']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
    }
    View::render('errors/500', ['title' => t('error.server')]);
}
