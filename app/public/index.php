<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use MenuQr\Controllers\Admin\AccountController;
use MenuQr\Controllers\Admin\AuthController;
use MenuQr\Controllers\Admin\AvailabilityController;
use MenuQr\Controllers\Admin\CategoriesController;
use MenuQr\Controllers\Admin\CustomizeController;
use MenuQr\Controllers\Admin\DashboardController;
use MenuQr\Controllers\Admin\DishesController;
use MenuQr\Controllers\Admin\QrController;
use MenuQr\Controllers\Admin\RestaurantController;
use MenuQr\Controllers\HealthController;
use MenuQr\Controllers\HomeController;
use MenuQr\Controllers\InstallController;
use MenuQr\Controllers\RecoverController;
use MenuQr\I18n;
use MenuQr\Router;
use MenuQr\View;

I18n::setLocale(I18n::detect());

$router = new Router();

// Public
$router->get('/', [HomeController::class, 'show']);
$router->get('/healthz', [HealthController::class, 'show']);
$router->get('/install', [InstallController::class, 'handle']);
$router->post('/install', [InstallController::class, 'handle']);
$router->get('/install/done', [InstallController::class, 'done']);
$router->get('/recover', [RecoverController::class, 'handle']);
$router->post('/recover', [RecoverController::class, 'handle']);

// Admin
$router->get('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);
$router->get('/admin', [DashboardController::class, 'show']);

$router->get('/admin/availability', [AvailabilityController::class, 'show']);
$router->post('/admin/dishes/{id}/availability', [AvailabilityController::class, 'toggle']);

$router->get('/admin/categories', [CategoriesController::class, 'index']);
$router->post('/admin/categories', [CategoriesController::class, 'store']);
$router->get('/admin/categories/{id}/edit', [CategoriesController::class, 'edit']);
$router->post('/admin/categories/{id}', [CategoriesController::class, 'update']);
$router->post('/admin/categories/{id}/move', [CategoriesController::class, 'move']);
$router->post('/admin/categories/{id}/visibility', [CategoriesController::class, 'visibility']);
$router->post('/admin/categories/{id}/delete', [CategoriesController::class, 'delete']);

$router->get('/admin/dishes', [DishesController::class, 'index']);
$router->get('/admin/dishes/new', [DishesController::class, 'create']);
$router->post('/admin/dishes', [DishesController::class, 'store']);
$router->get('/admin/dishes/{id}/edit', [DishesController::class, 'edit']);
$router->post('/admin/dishes/{id}', [DishesController::class, 'update']);
$router->post('/admin/dishes/{id}/move', [DishesController::class, 'move']);
$router->post('/admin/dishes/{id}/delete', [DishesController::class, 'delete']);

$router->get('/admin/restaurant', [RestaurantController::class, 'edit']);
$router->post('/admin/restaurant', [RestaurantController::class, 'update']);

$router->get('/admin/customize', [CustomizeController::class, 'edit']);
$router->post('/admin/customize', [CustomizeController::class, 'update']);
$router->post('/admin/customize/rate', [CustomizeController::class, 'updateRate']);

$router->get('/admin/qr', [QrController::class, 'show']);
$router->get('/admin/qr.svg', [QrController::class, 'svg']);
$router->get('/admin/qr.png', [QrController::class, 'png']);
$router->get('/admin/qr/print', [QrController::class, 'print']);

$router->get('/admin/account', [AccountController::class, 'edit']);
$router->post('/admin/account', [AccountController::class, 'updateProfile']);
$router->post('/admin/account/password', [AccountController::class, 'updatePassword']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
    }
    View::render('errors/500', ['title' => t('error.server')]);
}
