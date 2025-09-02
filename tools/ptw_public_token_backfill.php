<?php
// One-off backfill: generate public tokens for existing permits missing one.
// Run from CLI or browser (protect on production). Best to execute once after applying migration.

require_once __DIR__ . '/../api/library/constant.php';
require_once __DIR__ . '/../api/function/db.php';
require_once __DIR__ . '/../api/function/f_general.php';

header('Content-Type: text/plain');

date_default_timezone_set('Asia/Kuala_Lumpur');

try {
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_general->__set('constant', $constant);
    Class_db::getInstance()->db_connect();
} catch (Exception $e) {
    http_response_code(500);
    echo "Init failed: " . $e->getMessage() . "\n";
    exit;
}

function make_token($lenBytes = 32) {
    return bin2hex(random_bytes($lenBytes));
}

try {
    // Try to detect if schema exists
    $db = Class_db::getInstance();
    $sample = $db->db_select('ptw_permit', array(), 'ptw_permit_id DESC LIMIT 1');
    if (!isset($sample[0]['public_token'])) {
        echo "Schema missing (public_token). Apply migration first.\n";
        exit;
    }

    $batch = isset($_GET['batch']) ? intval($_GET['batch']) : 500;
    $days = isset($_GET['ttl_days']) ? intval($_GET['ttl_days']) : 365;

    // Select permits with NULL/empty token
    $permits = $db->db_select('ptw_permit', array('public_token' => "is NULL"), 'ptw_permit_id ASC');
    // Also include empty string
    $permits_empty = $db->db_select('ptw_permit', array('public_token' => ''), 'ptw_permit_id ASC');
    $permits = array_merge($permits ?: [], $permits_empty ?: []);

    $count = 0;
    foreach ($permits as $p) {
        if ($count >= $batch) break;
        $permit_id = $p['ptw_permit_id'];
        $valid_to = isset($p['ptw_valid_to']) ? $p['ptw_valid_to'] : null;
        $token = make_token();
        $expires_at = $valid_to ? date('Y-m-d H:i:s', strtotime($valid_to . ' +30 days')) : date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $db->db_update('ptw_permit', array(
            'public_token' => $token,
            'public_token_expires_at' => $expires_at,
            'public_link_enabled' => '1',
            'public_token_revoked_at' => null,
            'updated_date' => date('Y-m-d H:i:s')
        ), array('ptw_permit_id' => $permit_id));
        $count++;
    }

    echo "Backfill complete. Updated: {$count} record(s).\n";
    echo "Params: batch={$batch}, ttl_days={$days}\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}
