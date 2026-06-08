<?php

declare(strict_types=1);

namespace Gfm\Http;

/**
 * Minimal HTTP method + path router.
 *
 * Endpoints are migrated into this router one at a time. Existing
 * api/*.php files keep working untouched, so the front controller can be
 * adopted incrementally without breaking the Flutter app or web UI.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$this->normalize($path)] = $handler;
    }

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function has(string $method, string $path): bool
    {
        return isset($this->routes[strtoupper($method)][$this->normalize($path)]);
    }

    /**
     * @param array<string, mixed> $args
     * @throws ApiException when no route matches
     */
    public function dispatch(string $method, string $path, array $args = []): mixed
    {
        $method = strtoupper($method);
        $path = $this->normalize($path);
        if (!isset($this->routes[$method][$path])) {
            throw ApiException::userFacing('Route not found: ' . $method . ' ' . $path, \Gfm\Domain\ErrorCode::NOT_FOUND);
        }

        return ($this->routes[$method][$path])($args);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
