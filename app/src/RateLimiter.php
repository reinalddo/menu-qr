<?php

declare(strict_types=1);

namespace MenuQr;

/**
 * Counts failed attempts per IP and in total. The total limit still caps
 * brute force when every request reaches the app from the same proxy address.
 */
final class RateLimiter
{
    private const WINDOW_MINUTES = 15;
    private const MAX_PER_IP = 5;
    private const MAX_TOTAL = 50;

    public static function blocked(string $scope): bool
    {
        $since = gmdate('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
        $pdo = Db::pdo();

        $perIp = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND ip_hash = ? AND attempted_at > ?');
        $perIp->execute([$scope, self::ipHash(), $since]);
        if ((int) $perIp->fetchColumn() >= self::MAX_PER_IP) {
            return true;
        }

        $total = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND attempted_at > ?');
        $total->execute([$scope, $since]);
        return (int) $total->fetchColumn() >= self::MAX_TOTAL;
    }

    public static function fail(string $scope): void
    {
        $pdo = Db::pdo();
        $pdo->prepare('INSERT INTO login_attempts (scope, ip_hash, attempted_at) VALUES (?, ?, UTC_TIMESTAMP())')
            ->execute([$scope, self::ipHash()]);
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    }

    public static function clear(string $scope): void
    {
        Db::pdo()->prepare('DELETE FROM login_attempts WHERE scope = ? AND ip_hash = ?')
            ->execute([$scope, self::ipHash()]);
    }

    private static function ipHash(): string
    {
        return hash('sha256', Http::clientIp());
    }
}
