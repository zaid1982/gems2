<?php

declare(strict_types=1);

namespace Gfm\Support;

/**
 * Env-gated error reporting.
 *
 * Production default preserves the historical behaviour (notices and
 * deprecations suppressed, errors not displayed). When APP_DEBUG=true the
 * full error surface is enabled so latent bugs become visible during the
 * refactor without changing anything for live traffic.
 */
final class ErrorHandler
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        if (Config::isDebug()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');

            return;
        }

        // Match the legacy per-endpoint setting exactly (warnings stay enabled).
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        ini_set('display_errors', '0');
    }
}
