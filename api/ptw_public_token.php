<?php
// Lightweight endpoint to (re)generate a public token for a permit.
// Expects POST: action=regenerate, permit_id
// Returns JSON: { success: bool, token: string|null, error?: string }

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_ptw.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');

$resp = [ 'success' => false, 'token' => null, 'error' => '' ];
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid method'); }
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action !== 'regenerate') { throw new Exception('Invalid action'); }
    $permit_id = isset($_POST['permit_id']) ? trim($_POST['permit_id']) : '';
    if ($permit_id === '' || !preg_match('/^\d+$/', $permit_id)) { throw new Exception('Invalid permit_id'); }

    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_ptw = new Class_ptw();
    $fn_ptw->__set('constant', $constant);
    $fn_ptw->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();

    // Fetch permit to get valid_to for expiry derivation
    $row = Class_db::getInstance()->db_select_single('ptw_permit', [ 'ptw_permit_id' => $permit_id ]);
    if (!$row) { throw new Exception('Permit not found'); }
    $valid_to = isset($row['ptw_valid_to']) ? $row['ptw_valid_to'] : null;

    $token = $fn_ptw->issue_public_token($permit_id, $valid_to);
    if (!$token) { throw new Exception('Failed to generate token'); }

    $resp['success'] = true;
    $resp['token'] = $token;
    $resp['result'] = ['token' => $token]; // standard structure consumed by mzAjaxRequest
} catch (Exception $e) {
    $resp['error'] = $e->getMessage();
}

echo json_encode($resp);
