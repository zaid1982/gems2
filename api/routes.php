<?php

declare(strict_types=1);

use Gfm\Http\Router;
use Gfm\Support\Config;

/**
 * Route table for the new front controller (api/index.php).
 *
 * Add migrated endpoints here one at a time. Existing api/*.php files keep
 * serving their current URLs, so this can grow incrementally.
 *
 * Example of migrating an endpoint to a handler class:
 *   $router->get('/work-orders', [new \Gfm\Controller\WorkOrderController(), 'index']);
 */

$router = new Router();

$router->get('/health', static fn (): array => [
    'status' => 'ok',
    'env' => Config::appEnv(),
    'time' => date('c'),
]);

$router->get('/version', static fn (): array => [
    'name' => 'gfm-gems',
    'api' => 'v2',
]);

return $router;
