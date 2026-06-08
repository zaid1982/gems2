<?php

declare(strict_types=1);

namespace Gfm\Support;

/**
 * Minimal, dependency-free .env loader.
 *
 * Loads KEY=VALUE pairs from a .env file into the process environment
 * (getenv / $_ENV / $_SERVER) WITHOUT overwriting values that are already
 * set by the real environment. This keeps server-level configuration
 * authoritative while allowing a committed .env.example to document keys.
 *
 * It is intentionally defensive: a missing or malformed .env file never
 * throws, so wiring this into the request bootstrap can never take the
 * live application down.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $path ??= self::defaultPath();
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($key === '') {
                continue;
            }
            $value = self::stripQuotes($value);

            // Never clobber a value provided by the real environment.
            if (getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key])) {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }

        return $default;
    }

    private static function stripQuotes(string $value): string
    {
        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    private static function defaultPath(): string
    {
        // api/src/Gfm/Support/Env.php -> repo root is four levels up from api/.
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . '.env';
    }
}
