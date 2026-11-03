<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use MenuQr\Db;
use MenuQr\Migrator;

try {
    $pdo = Db::waitUntilReady(120);
    $applied = (new Migrator($pdo, APP_ROOT . '/migrations'))->run();
    echo $applied === []
        ? "migrations: database is up to date\n"
        : 'migrations: applied ' . implode(', ', $applied) . "\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'migrations: failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
