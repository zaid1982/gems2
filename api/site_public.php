<?php
// Public Site Info API (no auth) - returns site_name and site_code by site_id
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

try {
    require_once __DIR__ . '/library/constant.php';
    require_once __DIR__ . '/function/db.php';
    require_once __DIR__ . '/function/f_general.php';

    $fn_general = new Class_general();
    Class_db::getInstance()->db_connect();
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'init_failed',
        'errmsg' => 'Failed to initialize: ' . $e->getMessage()
    ]);
    exit;
}

try {
    ob_clean();
    $id = $_GET['id'] ?? $_GET['site_id'] ?? '';
    if ($id === '' || !preg_match('/^\d+$/', (string)$id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'invalid_param',
            'errmsg' => 'Parameter site_id is required and must be numeric'
        ]);
        ob_end_flush();
        exit;
    }

    $db = Class_db::getInstance();
    $siteRow = null;
    try { $siteRow = $db->db_select_single('cli_site', ['site_id' => strval($id)]); } catch (Exception $e1) { $siteRow = null; }
    if (empty($siteRow)) { try { $siteRow = $db->db_select_single('cli_site', ['siteId' => strval($id)]); } catch (Exception $e2) { $siteRow = null; } }
    if (empty($siteRow)) { try { $siteRow = $db->db_select_single('sys_site', ['site_id' => strval($id)]); } catch (Exception $e3) { $siteRow = null; } }

    if (empty($siteRow)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'not_found',
            'errmsg' => 'Site not found'
        ]);
        ob_end_flush();
        exit;
    }

    $name = $siteRow['site_name'] ?? ($siteRow['siteName'] ?? '');
    $code = $siteRow['site_code'] ?? ($siteRow['siteCode'] ?? '');

    echo json_encode([
        'success' => true,
        'result' => [
            'site_id' => intval($id),
            'site_name' => $name,
            'site_code' => $code
        ],
        'error' => '',
        'errmsg' => ''
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'errmsg' => $e->getMessage()
    ]);
} finally {
    ob_end_flush();
}

