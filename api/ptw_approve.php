<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_ptw.php';
require_once 'function/f_email.php';

$api_name = 'api_ptw_approve';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_email = new Class_email();
$fn_ptw = new Class_ptw();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_ptw->__set('constant', $constant);
    $fn_ptw->__set('fn_general', $fn_general);
    $fn_ptw->__set('fn_email', $fn_email);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    if ($request_method !== 'POST') {
        throw new Exception('[' . __LINE__ . '] - Only POST method allowed');
    }

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    
    // Get user information
    $user = Class_db::getInstance()->db_select('sys_user', array('user_id'=>$jwt_data->userId));
    if (count($user) == 0) {
        throw new Exception('[' . __LINE__ . '] - User not found');
    }
    
    $user_site_id = $user[0]['site_id'];
    $user_role = $user[0]['user_role']; // Assuming user role is stored in user table
    
    // Validate required parameters
    if (!isset($_POST['action']) || empty($_POST['action'])) {
        throw new Exception('[' . __LINE__ . '] - Action parameter required');
    }
    
    if (!isset($_POST['permit_id']) || empty($_POST['permit_id'])) {
        throw new Exception('[' . __LINE__ . '] - Permit ID required');
    }
    
    $action = $_POST['action'];
    $permit_id = $_POST['permit_id'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    
    $is_transaction = true;
    Class_db::getInstance()->db_beginTransaction();
    
    switch ($action) {
        case 'she_approve':
            $result = process_she_approval($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks, 'APPROVED');
            break;
            
        case 'she_reject':
            $result = process_she_approval($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks, 'REJECTED');
            break;
            
        case 'fm_approve':
            $result = process_fm_approval($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks, 'APPROVED');
            break;
            
        case 'fm_reject':
            $result = process_fm_approval($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks, 'REJECTED');
            break;
            
        default:
            throw new Exception('[' . __LINE__ . '] - Invalid action: ' . $action);
    }
    
    Class_db::getInstance()->db_commit();
    $is_transaction = false;
    
    $form_data['success'] = true;
    $form_data['result'] = $result;

} catch (Exception $e) {
    $fn_general->log_debug('API', $api_name, __LINE__, 'Exception: '.$e->getMessage());
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
        $is_transaction = false;
    }
    $form_data['error'] = $e->getMessage();
    $form_data['errmsg'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($form_data);

function process_she_approval($permit_id, $user_id, $user_site_id, $user_role, $remarks, $approval_status) {
    global $fn_general, $fn_ptw, $fn_email;
    
    // Security check - verify user has SHE role
    if ($user_role !== 'SHE' && $user_role !== 'ADMIN') {
        throw new Exception('[' . __LINE__ . '] - Insufficient permissions for SHE approval');
    }
    
    // Get current permit details
    $permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
    if (count($permit) == 0) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found');
    }
    
    $current_permit = $permit[0];
    
    // Validate current status - must be supervisor approved and SHE pending
    if ($current_permit['ptw_supervisor_approval'] !== 'APPROVED') {
        throw new Exception('[' . __LINE__ . '] - PTW must be approved by supervisor first');
    }
    
    if ($current_permit['ptw_she_approval'] !== 'PENDING') {
        throw new Exception('[' . __LINE__ . '] - PTW is not pending SHE approval');
    }
    
    $current_status = $current_permit['ptw_status'];
    
    // Update permit with SHE approval
    $update_data = array(
        'ptw_she_approval' => $approval_status,
        'ptw_she_approved_by' => $user_id,
        'ptw_she_approved_date' => date('Y-m-d H:i:s'),
        'ptw_she_remarks' => $remarks,
        'updated_by' => $user_id
    );
    
    if ($approval_status === 'APPROVED') {
        $update_data['ptw_status'] = 'PENDING_FM';
        $new_status = 'PENDING_FM';
        $action_type = 'SHE_APPROVED';
    } else {
        $update_data['ptw_status'] = 'CANCELLED';
        $new_status = 'CANCELLED';
        $action_type = 'SHE_REJECTED';
    }
    
    // Update the permit
    $update_result = Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => $permit_id));
    if (!$update_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to update PTW permit');
    }
    
    // Log the action in status history
    $history_data = array(
        'ptw_permit_id' => $permit_id,
        'action_type' => $action_type,
        'previous_status' => $current_status,
        'new_status' => $new_status,
        'remarks' => $remarks,
        'action_by' => $user_id
    );
    
    $history_result = Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
    if (!$history_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to log status history');
    }
    
    // Log audit trail
    $fn_general->save_audit('PTW_SHE_' . strtoupper($approval_status), 'PTW Permit ' . $current_permit['ptw_permit_number'] . ' ' . strtolower($approval_status) . ' by SHE', $user_id);
    
    // Send notifications
    if ($approval_status === 'APPROVED') {
        // Notify FM group about new permit for approval
        $fn_ptw->send_ptw_notification($permit_id, 'FM_APPROVAL_NEEDED');
    } else {
        // Notify about rejection
        $fn_ptw->send_ptw_notification($permit_id, 'REJECTED');
    }
    
    return array(
        'message' => 'PTW permit ' . strtolower($approval_status) . ' by SHE successfully',
        'permit_id' => $permit_id,
        'new_status' => $new_status
    );
}

function process_fm_approval($permit_id, $user_id, $user_site_id, $user_role, $remarks, $approval_status) {
    global $fn_general, $fn_ptw, $fn_email;
    
    // Security check - verify user has FM role
    if ($user_role !== 'FM' && $user_role !== 'ADMIN') {
        throw new Exception('[' . __LINE__ . '] - Insufficient permissions for FM approval');
    }
    
    // Get current permit details
    $permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
    if (count($permit) == 0) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found');
    }
    
    $current_permit = $permit[0];
    
    // Validate current status - must be SHE approved and FM pending
    if ($current_permit['ptw_she_approval'] !== 'APPROVED') {
        throw new Exception('[' . __LINE__ . '] - PTW must be approved by SHE first');
    }
    
    if ($current_permit['ptw_fm_approval'] !== 'PENDING') {
        throw new Exception('[' . __LINE__ . '] - PTW is not pending FM approval');
    }
    
    $current_status = $current_permit['ptw_status'];
    
    // Update permit with FM approval
    $update_data = array(
        'ptw_fm_approval' => $approval_status,
        'ptw_fm_approved_by' => $user_id,
        'ptw_fm_approved_date' => date('Y-m-d H:i:s'),
        'ptw_fm_remarks' => $remarks,
        'updated_by' => $user_id
    );
    
    if ($approval_status === 'APPROVED') {
        $update_data['ptw_status'] = 'APPROVED';
        $new_status = 'APPROVED';
        $action_type = 'FM_APPROVED';
    } else {
        $update_data['ptw_status'] = 'CANCELLED';
        $new_status = 'CANCELLED';
        $action_type = 'FM_REJECTED';
    }
    
    // Update the permit
    $update_result = Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => $permit_id));
    if (!$update_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to update PTW permit');
    }
    
    // Log the action in status history
    $history_data = array(
        'ptw_permit_id' => $permit_id,
        'action_type' => $action_type,
        'previous_status' => $current_status,
        'new_status' => $new_status,
        'remarks' => $remarks,
        'action_by' => $user_id
    );
    
    $history_result = Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
    if (!$history_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to log status history');
    }
    
    // Log audit trail
    $fn_general->save_audit('PTW_FM_' . strtoupper($approval_status), 'PTW Permit ' . $current_permit['ptw_permit_number'] . ' ' . strtolower($approval_status) . ' by FM', $user_id);
    
    // Send notifications
    if ($approval_status === 'APPROVED') {
        // Notify applicant of final approval
        $fn_ptw->send_ptw_notification($permit_id, 'APPROVED');
    } else {
        // Notify about rejection
        $fn_ptw->send_ptw_notification($permit_id, 'REJECTED');
    }
    
    return array(
        'message' => 'PTW permit ' . strtolower($approval_status) . ' by FM successfully',
        'permit_id' => $permit_id,
        'new_status' => $new_status
    );
}

?>
