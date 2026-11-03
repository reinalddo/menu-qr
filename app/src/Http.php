<?php

declare(strict_types=1);

namespace MenuQr;

final class Http
{
    /**
     * Requests coming from a private address are treated as coming through a
     * proxy (Traefik or Docker's port forwarding), so forwarded headers apply.
     */
    public static function fromPrivateNetwork(): bool
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        return $forwarded === 'https' && self::fromPrivateNetwork();
    }

    public static function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        if (self::fromPrivateNetwork() && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // The proxy appends the address it saw, so the last entry is the reliable one.
            $parts = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
            $last = end($parts);
            if ($last !== false && filter_var($last, FILTER_VALIDATE_IP) !== false) {
                $ip = $last;
            }
        }
        return $ip;
    }

    /** Scheme and host the visitor used, e.g. https://menu.example.com */
    public static function baseUrl(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$|^\[[0-9a-fA-F:]+\](:\d+)?$/', $host)) {
            $host = 'localhost';
        }
        return (self::isHttps() ? 'https' : 'http') . '://' . $host;
    }

    public static function redirect(string $path, int $status = 303): never
    {
        header('Location: ' . $path, true, $status);
        exit;
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function input(string $name): string
    {
        $value = $_POST[$name] ?? '';
        return is_string($value) ? trim($value) : '';
    }
}
