<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Wires up Composer autoloading (with a dependency-free fallback so the new
 * Gfm\ classes resolve even before `composer dump-autoload` has run),
 * loads the .env file, and applies env-gated error reporting.
 *
 * This file is intentionally defensive: it must never throw, because it is
 * included near the top of the live request path (function/db.php).
 */

if (defined('GFM_BOOTSTRAPPED')) {
    return;
}
define('GFM_BOOTSTRAPPED', true);
define('GFM_BASE_PATH', dirname(__DIR__));
define('GFM_API_PATH', __DIR__);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

// Fallback PSR-4 autoloader for the Gfm\ namespace. Harmless if Composer's
// autoloader already registered the same mapping.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Gfm\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/Gfm/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

try {
    \Gfm\Support\Env::load();
    \Gfm\Support\ErrorHandler::register();
} catch (\Throwable $e) {
    // Bootstrap must not break the request; fall back to legacy behaviour.
    error_log('[gfm] bootstrap warning: ' . $e->getMessage());
}
