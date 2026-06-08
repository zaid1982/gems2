<?php

declare(strict_types=1);

namespace Gfm\Security;

use Gfm\Support\Config;

/**
 * Protects maintenance/ PHP endpoints with a shared X-Api-Key secret.
 *
 * The key is configured via MAINTENANCE_API_KEY (.env) or [maintenance] api_key
 * in api/library/config.ini. CLI scripts are exempt so operators can still run
 * one-off maintenance PHP files from the shell.
 */
final class MaintenanceGuard
{
    public static function apiKey(): string
    {
        return Config::maintenanceApiKey();
    }

    public static function requireKey(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $expected = self::apiKey();
        if ($expected === '') {
            self::deny(
                503,
                'Maintenance tools are locked. Set MAINTENANCE_API_KEY in .env or [maintenance] api_key in api/library/config.ini.'
            );
        }

        $provided = self::extractProvidedKey();
        if ($provided === null || !hash_equals($expected, $provided)) {
            self::deny(401, 'Invalid or missing X-Api-Key.');
        }
    }

    public static function isValidKey(string $key): bool
    {
        $expected = self::apiKey();

        return $expected !== '' && hash_equals($expected, $key);
    }

    private static function extractProvidedKey(): ?string
    {
        $header = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($header !== '') {
            return $header;
        }

        if (isset($_GET['api_key']) && is_string($_GET['api_key']) && $_GET['api_key'] !== '') {
            return $_GET['api_key'];
        }

        return null;
    }

    private static function deny(int $status, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($accept, 'text/html') && !str_contains($accept, 'application/json')) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<!DOCTYPE html><html><head><title>Maintenance access denied</title></head><body>';
                echo '<h1>Access denied</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<p><a href="dashboard.html">Return to maintenance dashboard</a></p></body></html>';
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $message]);
            }
        }
        exit;
    }
}
