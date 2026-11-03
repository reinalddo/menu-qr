<?php

declare(strict_types=1);

namespace MenuQr;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $path = '/' . trim($path, '/');

        $lookup = $method === 'HEAD' ? 'GET' : $method;
        $handler = $this->routes[$lookup][$path] ?? null;

        if ($handler !== null) {
            $handler();
            return;
        }

        foreach ($this->routes as $routes) {
            if (isset($routes[$path])) {
                http_response_code(405);
                echo 'Method Not Allowed';
                return;
            }
        }

        self::notFound();
    }

    public static function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', ['title' => t('error.not_found')]);
    }
}
