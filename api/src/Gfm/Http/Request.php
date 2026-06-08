<?php

declare(strict_types=1);

namespace Gfm\Http;

/**
 * Thin accessor over the PHP request superglobals, so handlers don't reach into
 * $_GET/$_POST/$_SERVER directly and are easier to reason about.
 */
final class Request
{
    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return strtoupper(is_string($method) ? $method : 'GET');
    }

    /** Route path from PATH_INFO, falling back to ?route=, then "/". */
    public static function path(): string
    {
        $path = $_SERVER['PATH_INFO'] ?? ($_GET['route'] ?? '/');
        if (!is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }

    public static function query(string $key, ?string $default = null): ?string
    {
        return isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : $default;
    }

    public static function post(string $key, ?string $default = null): ?string
    {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : $default;
    }

    public static function bearerToken(): ?string
    {
        $auth = '';
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if ($auth === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = (string) $_SERVER['HTTP_AUTHORIZATION'];
        }

        return $auth !== '' ? $auth : null;
    }
}
