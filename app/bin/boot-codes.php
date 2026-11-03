<?php

declare(strict_types=1);

/*
 * Runs on every start, before Apache. Prints the setup code while the app is
 * not installed, or a recovery code when MENUQR_RECOVERY=1.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use MenuQr\Codes;
use MenuQr\Config;
use MenuQr\Installation;

$domain = (string) getenv('APP_DOMAIN');
$port = (string) (getenv('APP_PORT') ?: '8101');
$address = $domain !== '' ? 'https://' . $domain : 'http://<server-ip>:' . $port;

if (!Installation::isComplete()) {
    Codes::clear(Codes::RECOVERY);
    $code = Codes::format(Codes::ensure(Codes::SETUP));
    echo Installation::banner([
        'menu-qr is not installed yet.',
        "Open {$address}/install",
        "Setup code: {$code}",
        '',
        'menu-qr todavía no está instalado.',
        "Abre {$address}/install",
        "Código de instalación: {$code}",
    ]);
    return;
}

Codes::clear(Codes::SETUP);

if (!Config::recoveryEnabled()) {
    Codes::clear(Codes::RECOVERY);
    return;
}

$code = Codes::format(Codes::ensure(Codes::RECOVERY));
echo Installation::banner([
    'Password recovery is enabled (MENUQR_RECOVERY=1).',
    "Open {$address}/recover",
    "Recovery code: {$code}",
    'Set MENUQR_RECOVERY=0 again when you are done.',
    '',
    'La recuperación de contraseña está activada (MENUQR_RECOVERY=1).',
    "Abre {$address}/recover",
    "Código de recuperación: {$code}",
    'Al terminar, vuelve a poner MENUQR_RECOVERY=0.',
]);
