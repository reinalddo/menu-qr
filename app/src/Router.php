<?php

declare(strict_types=1);

namespace MenuQr;

/**
 * Exact paths, plus numeric parameters written as {name}:
 * /admin/dishes/{id} calls the handler with (int) $id.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];
    /** @var array<string, array<string, callable>> regex => handler */
    private array $patterns = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $path = '/' . trim($path, '/');
        $lookup = $method === 'HEAD' ? 'GET' : $method;

        $match = $this->match($lookup, $path);
        if ($match !== null) {
            [$handler, $params] = $match;
            $handler(...$params);
            return;
        }

        foreach (array_keys($this->routes + $this->patterns) as $otherMethod) {
            if ($otherMethod !== $lookup && $this->match($otherMethod, $path) !== null) {
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

    private function add(string $method, string $path, callable $handler): void
    {
        if (!str_contains($path, '{')) {
            $this->routes[$method][$path] = $handler;
            return;
        }
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[1-9][0-9]{0,9})', $path) . '$#';
        $this->patterns[$method][$regex] = $handler;
    }

    /** @return array{0: callable, 1: int[]}|null */
    private function match(string $method, string $path): ?array
    {
        if (isset($this->routes[$method][$path])) {
            return [$this->routes[$method][$path], []];
        }
        foreach ($this->patterns[$method] ?? [] as $regex => $handler) {
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$handler, array_values(array_map('intval', $params))];
            }
        }
        return null;
    }
}
