<?php

/**
 * WO PDF diagnostic endpoint — use on server when generate PDF returns HTTP 500.
 *
 * GET /api/pdf/wo_pdf_debug.php?woTaskId=184940&key=<maintenance api_key from config.ini>
 * GET /api/pdf/wo_pdf_debug.php?woTaskId=184940&variant=lama&key=<maintenance api_key>
 *
 * variant=lama  → api/pdf/wo_lama.php (old GFM logo design)
 * variant=jkr   → api/pdf/wo.php (current / default)
 *
 * Remove or restrict access after troubleshooting.
 */

header('Content-Type: application/json; charset=utf-8');

function wo_pdf_debug_json($payload, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

register_shutdown_function(function () {
    $err = error_get_last();
    if (!$err || !in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        return;
    }
    wo_pdf_debug_json(array(
        'success' => false,
        'stage' => 'fatal',
        'error' => $err['message'],
        'file' => $err['file'],
        'line' => $err['line'],
    ), 500);
});

set_exception_handler(function ($ex) {
    wo_pdf_debug_json(array(
        'success' => false,
        'stage' => 'uncaught_exception',
        'error' => $ex->getMessage(),
        'file' => $ex->getFile(),
        'line' => $ex->getLine(),
        'trace' => explode("\n", $ex->getTraceAsString()),
    ), 500);
});

error_reporting(E_ALL);
ini_set('display_errors', '0');

$apiBasePath = dirname(__DIR__);
$configPath = $apiBasePath.'/library/config.ini';
if (!is_file($configPath)) {
    wo_pdf_debug_json(array('success' => false, 'error' => 'config.ini not found'), 500);
}

$config = parse_ini_file($configPath, true);
$expectedKey = isset($config['maintenance']['api_key']) ? $config['maintenance']['api_key'] : '';
$key = isset($_GET['key']) ? trim($_GET['key']) : '';

if ($expectedKey === '' || $key === '' || !hash_equals($expectedKey, $key)) {
    wo_pdf_debug_json(array(
        'success' => false,
        'error' => 'Invalid or missing key. Pass key=<maintenance api_key from config.ini>.',
    ), 403);
}

$woTaskId = filter_input(INPUT_GET, 'woTaskId', FILTER_VALIDATE_INT);
if (empty($woTaskId)) {
    wo_pdf_debug_json(array(
        'success' => false,
        'error' => 'Missing or invalid woTaskId query parameter.',
    ), 400);
}

$variant = isset($_GET['variant']) ? strtolower(trim($_GET['variant'])) : 'jkr';
if (!in_array($variant, array('jkr', 'lama'), true)) {
    wo_pdf_debug_json(array(
        'success' => false,
        'error' => 'Invalid variant. Use variant=jkr or variant=lama.',
    ), 400);
}
$pdfSource = $variant === 'lama' ? __DIR__.'/wo_lama.php' : __DIR__.'/wo.php';

chdir($apiBasePath);

try {
    require_once $apiBasePath.'/library/constant.php';
    require_once $apiBasePath.'/function/db.php';
    require_once $apiBasePath.'/function/f_general.php';
    require_once $apiBasePath.'/pdf/tcpdf_include.php';
    require_once $pdfSource;

    if (!class_exists('TCPDF')) {
        throw new RuntimeException('TCPDF class not loaded. Check api/pdf/tcpdf_include.php paths on this server.');
    }
    if ($variant === 'jkr' && !class_exists('ArahanSiasatanPdf')) {
        throw new RuntimeException('ArahanSiasatanPdf not loaded. Deploy api/pdf/arahan_siasatan_pdf.php.');
    }
    if ($variant === 'lama' && !class_exists('MYPDF_wo')) {
        throw new RuntimeException('MYPDF_wo not loaded. Check api/pdf/wo_lama.php.');
    }
    if (!class_exists('Class_pdf_wo')) {
        throw new RuntimeException('Class_pdf_wo not loaded from '.$pdfSource);
    }

    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_general->__set('constant', $constant);

    Class_db::getInstance()->db_connect();

    $pdf = new Class_pdf_wo();
    $pdf->__set('fn_general', $fn_general);
    $pdf->__set('woTaskId', $woTaskId);

    $result = $pdf->create_pdf();

    Class_db::getInstance()->db_close();

    wo_pdf_debug_json(array(
        'success' => true,
        'variant' => $variant,
        'source' => basename($pdfSource),
        'woTaskId' => $woTaskId,
        'result' => $result,
        'php_version' => PHP_VERSION,
    ));
} catch (Throwable $ex) {
    if (class_exists('Class_db', false)) {
        try {
            Class_db::getInstance()->db_close();
        } catch (Throwable $ignored) {
        }
    }

    wo_pdf_debug_json(array(
        'success' => false,
        'stage' => 'exception',
        'variant' => $variant,
        'source' => basename($pdfSource),
        'woTaskId' => $woTaskId,
        'error' => $ex->getMessage(),
        'file' => $ex->getFile(),
        'line' => $ex->getLine(),
        'trace' => array_slice(explode("\n", $ex->getTraceAsString()), 0, 15),
        'php_version' => PHP_VERSION,
    ), 500);
}
