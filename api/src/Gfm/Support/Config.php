<?php

declare(strict_types=1);

namespace Gfm\Support;

/**
 * Central, typed configuration resolver.
 *
 * Resolution order for every value:
 *   1. Environment variable (.env or real server environment)
 *   2. Legacy api/library/config.ini (kept for backward compatibility)
 *   3. Safe default
 *
 * This lets the project move secrets out of source control without a
 * "big bang" change: existing deployments that still rely on config.ini
 * keep working untouched, while new deployments can set environment
 * variables instead.
 */
final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $ini = null;

    public static function appEnv(): string
    {
        return (string) (Env::get('APP_ENV') ?? self::ini('database', 'environment') ?? 'production');
    }

    public static function isDebug(): bool
    {
        $value = Env::get('APP_DEBUG');
        if ($value === null) {
            return false;
        }

        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }

    public static function dbHost(): string
    {
        return (string) (Env::get('DB_HOST') ?? self::ini('database', 'dbhost') ?? 'localhost');
    }

    public static function dbName(): string
    {
        return (string) (Env::get('DB_NAME') ?? self::ini('database', 'dbname') ?? '');
    }

    public static function dbUser(): string
    {
        return (string) (Env::get('DB_USER') ?? self::ini('database', 'username') ?? 'root');
    }

    public static function dbPassword(): string
    {
        $value = Env::get('DB_PASS');
        if ($value !== null) {
            return $value;
        }
        $ini = self::ini('database', 'password');

        return $ini !== null ? (string) $ini : '';
    }

    public static function dbPort(): int
    {
        return (int) (Env::get('DB_PORT') ?? self::ini('database', 'dbport') ?? 3306);
    }

    public static function logDir(): string
    {
        return (string) (Env::get('LOG_DIR') ?? self::ini('database', 'log_dir') ?? '');
    }

    /** Public base URL of the API (used for building absolute asset links). */
    public static function appUrl(): string
    {
        return (string) (Env::get('APP_URL') ?? self::ini('app', 'url') ?? '');
    }

    /**
     * JWT signing secret. Falls back to the historical hardcoded key so the
     * application keeps validating already-issued tokens after deploy, but
     * emits a one-time warning so operators are nudged to set a real secret.
     */
    public static function jwtSecret(): string
    {
        $value = Env::get('JWT_SECRET') ?? self::ini('jwt', 'secret');
        if ($value !== null && $value !== '') {
            return $value;
        }

        static $warned = false;
        if (!$warned) {
            $warned = true;
            error_log('[gfm] WARNING: JWT_SECRET is not configured; falling back to the legacy key. Set JWT_SECRET in .env immediately.');
        }

        return 'gems2';
    }

    /**
     * Lifetime (seconds) of newly issued access tokens. Default preserves the
     * historical effective lifetime so existing clients are not logged out.
     */
    public static function jwtTtl(): int
    {
        return (int) (Env::get('JWT_TTL') ?? self::ini('jwt', 'ttl') ?? 86400);
    }

    /**
     * Validation leeway (seconds). Default is intentionally generous so tokens
     * issued by the previous implementation keep validating during rollout.
     * Tighten this (e.g. 60) once the client fleet has cycled to new tokens.
     */
    public static function jwtLeeway(): int
    {
        return (int) (Env::get('JWT_LEEWAY') ?? self::ini('jwt', 'leeway') ?? 86400);
    }

    /** Lifetime (seconds) of refresh tokens. Default 30 days. */
    public static function jwtRefreshTtl(): int
    {
        return (int) (Env::get('JWT_REFRESH_TTL') ?? self::ini('jwt', 'refresh_ttl') ?? 2592000);
    }

    /**
     * Shared secret for maintenance/ operator tools (X-Api-Key header).
     * When empty, all maintenance PHP endpoints deny web access.
     */
    public static function maintenanceApiKey(): string
    {
        return (string) (Env::get('MAINTENANCE_API_KEY') ?? self::ini('maintenance', 'api_key') ?? '');
    }

    /** @return array{host:string,port:int,security:string,username:string,password:string,from:string,envelope_from:string,timeout:int} */
    public static function smtp(): array
    {
        return [
            'host' => (string) (Env::get('SMTP_HOST') ?? self::ini('smtp', 'host') ?? ''),
            'port' => (int) (Env::get('SMTP_PORT') ?? self::ini('smtp', 'port') ?? 587),
            'security' => (string) (Env::get('SMTP_SECURITY') ?? self::ini('smtp', 'security') ?? 'STARTTLS'),
            'username' => (string) (Env::get('SMTP_USER') ?? self::ini('smtp', 'm_username') ?? ''),
            'password' => (string) (Env::get('SMTP_PASS') ?? self::ini('smtp', 'm_password') ?? ''),
            'from' => (string) (Env::get('MAIL_FROM') ?? self::ini('mail', 'mail_from') ?? ''),
            'envelope_from' => (string) (Env::get('MAIL_ENVELOPE_FROM') ?? self::ini('mail', 'mail_envelope_from') ?? ''),
            'timeout' => (int) (Env::get('SMTP_TIMEOUT') ?? self::ini('smtp', 'timeout') ?? 30),
        ];
    }

    private static function ini(string $section, string $key): mixed
    {
        if (self::$ini === null) {
            self::$ini = self::loadIni();
        }

        if (isset(self::$ini[$section]) && is_array(self::$ini[$section]) && array_key_exists($key, self::$ini[$section])) {
            return self::$ini[$section][$key];
        }

        // Some legacy code stores keys flat (no section); support that too.
        if (array_key_exists($key, self::$ini)) {
            return self::$ini[$key];
        }

        return null;
    }

    /** @return array<string, mixed> */
    private static function loadIni(): array
    {
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'config.ini';
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }
        $parsed = @parse_ini_file($path, true, INI_SCANNER_TYPED);

        return is_array($parsed) ? $parsed : [];
    }

    /** Test seam: reset the cached ini so tests can swap configuration. */
    public static function resetCache(): void
    {
        self::$ini = null;
    }
}
