<?php

declare(strict_types=1);

namespace MenuQr;

use PDO;
use PDOException;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        return self::$pdo ??= self::connect();
    }

    public static function connect(): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=3306;dbname=%s;charset=utf8mb4', Config::dbHost(), Config::dbName());
        $pdo = new PDO($dsn, Config::dbUser(), Config::dbPassword(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    }

    /** Retries the connection while the database container finishes starting. */
    public static function waitUntilReady(int $seconds): PDO
    {
        $deadline = time() + $seconds;
        while (true) {
            try {
                return self::$pdo = self::connect();
            } catch (PDOException $exception) {
                if (time() >= $deadline) {
                    throw $exception;
                }
                sleep(2);
            }
        }
    }
}
