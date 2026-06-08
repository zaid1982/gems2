<?php

declare(strict_types=1);

/**
 * Front controller for the new routed API surface.
 *
 * This is additive and OPT-IN: the existing api/*.php endpoints are untouched
 * and keep serving their current URLs. To mount the router (e.g. under
 * /api/v2/) add a rewrite that sends the path to this file, for example in
 * .htaccess:
 *
 *   RewriteRule ^api/v2/(.*)$ api/index.php [QSA,L,E=PATH_INFO:/$1]
 *
 * Until then it can be hit directly: api/index.php?route=/health
 *
 * Every response uses the standard envelope and a single, central
 * try/catch — replacing the copy-pasted boilerplate in each legacy endpoint.
 */

require_once __DIR__ . '/bootstrap.php';

use Gfm\Domain\ErrorCode;
use Gfm\Http\ApiException;
use Gfm\Http\JsonResponse;
use Gfm\Http\Request;
use Gfm\Http\Router;

const GFM_GENERIC_ERROR = 'Error on system. Please contact Administrator!';

/** @var Router $router */
$router = require __DIR__ . '/routes.php';

try {
    $result = $router->dispatch(Request::method(), Request::path());
    $response = $result instanceof JsonResponse ? $result : JsonResponse::success($result);
} catch (ApiException $ex) {
    error_log('[gfm] api error: ' . $ex->getMessage());
    $errmsg = $ex->hasUserMessage() ? $ex->userMessage() : GFM_GENERIC_ERROR;
    $response = JsonResponse::failure($ex->getMessage(), $errmsg);
} catch (\Throwable $ex) {
    error_log('[gfm] unhandled error: ' . $ex->getMessage());
    $detail = ErrorCode::isUserFacing($ex->getCode()) ? $ex->getMessage() : '';
    $response = JsonResponse::failure($ex->getMessage(), $detail !== '' ? $detail : GFM_GENERIC_ERROR);
}

$response->send();
