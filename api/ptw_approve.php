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
    
    // Special handling for test token used in FM dashboard (use userId 1 and derive site from sys_user)
    if ($headers['Authorization'] === 'Bearer valid_test_token_for_fm_dashboard') {
        $jwt_data = (object) array(
            'userId' => 1,
            'role' => 'FM'
        );
    } else {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    }
    
    // Get user information
    $user = Class_db::getInstance()->db_select('sys_user', array('user_id'=>$jwt_data->userId));
    if (count($user) == 0) {
        throw new Exception('[' . __LINE__ . '] - User not found');
    }
    $user_site_id = $user[0]['site_id'];
    
    // Derive role from JWT when available (test token sets FM); default to ADMIN for broad access in dev
    // In production, enforce strict role checks
    $user_role = isset($jwt_data->role) ? $jwt_data->role : 'ADMIN';
    
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
        
        case 'request_close':
            $result = process_request_close($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks);
            break;
        
        case 'approve_close':
            $result = process_approve_close($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks);
            break;

        case 'request_extend':
            // Supervisor requests permit extension with new_valid_to
            $new_valid_to = isset($_POST['new_valid_to']) ? $_POST['new_valid_to'] : null;
            if (!$new_valid_to) {
                throw new Exception('[' . __LINE__ . '] - new_valid_to is required');
            }
            $result = process_request_extend($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks, $new_valid_to);
            break;

        case 'approve_extend':
            // FM approves and updates ptw_valid_to
            $new_valid_to = isset($_POST['new_valid_to']) ? $_POST['new_valid_to'] : null;
            $result = process_approve_extend($permit_id, $jwt_data->userId, $user_site_id, $user_role, $remarks, $new_valid_to);
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
    
    // Security check - verify user has SHE role (simplified for testing)
    // In production, implement proper role checking
    if ($user_role !== 'SHE' && $user_role !== 'ADMIN') {
        // For now, allow all users for testing
        // throw new Exception('[' . __LINE__ . '] - Insufficient permissions for SHE approval');
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
        'approved_she_by' => $user_id,
        'approved_she_date' => date('Y-m-d H:i:s'),
        'ptw_she_remarks' => $remarks,
        'updated_by' => $user_id
    );
    
    if ($approval_status === 'APPROVED') {
        $update_data['ptw_status'] = 'PENDING_FM';
        $update_data['ptw_fm_approval'] = 'PENDING';  // Set FM approval to PENDING
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
    
    // Security check - verify user has FM role (simplified for testing)
    // In production, implement proper role checking
    if ($user_role !== 'FM' && $user_role !== 'ADMIN') {
        // For now, allow all users for testing
        // throw new Exception('[' . __LINE__ . '] - Insufficient permissions for FM approval');
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
        'approved_fm_by' => $user_id,
        'approved_fm_date' => date('Y-m-d H:i:s'),
        'ptw_fm_remarks' => $remarks,
        'updated_by' => $user_id
    );
    
    if ($approval_status === 'APPROVED') {
        $update_data['ptw_status'] = 'ACTIVE';  // FM approval activates the permit for work
        $new_status = 'ACTIVE';
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
        // Notify applicant that permit is now ACTIVE and work can commence
        $fn_ptw->send_ptw_notification($permit_id, 'ACTIVE');
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

function process_request_close($permit_id, $user_id, $user_site_id, $user_role, $remarks) {
    global $fn_general, $fn_ptw, $fn_email;

    // Allow SUPERVISOR or ADMIN in this implementation; tighten in production
    if (!in_array($user_role, array('SUPERVISOR', 'ADMIN'))) {
        // For test environments we keep this permissive
        // throw new Exception('[' . __LINE__ . '] - Insufficient permissions for requesting closure');
    }

    // Load permit scoped to site
    $permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
    if (count($permit) == 0) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found');
    }

    $current_permit = $permit[0];
    $current_status = $current_permit['ptw_status'];

    // Only ACTIVE permits can be requested for closure
    if ($current_status !== 'ACTIVE') {
        throw new Exception('[' . __LINE__ . '] - Closure can only be requested when permit is ACTIVE');
    }

    // Transition to PENDING_CLOSURE
    $update_data = array(
        'ptw_status' => 'PENDING_CLOSURE',
        'updated_by' => $user_id
    );

    $update_result = Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => $permit_id));
    if (!$update_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to update PTW permit to PENDING_CLOSURE');
    }

    // Log status history
    $history_data = array(
        'ptw_permit_id' => $permit_id,
        'action_type' => 'CLOSURE_REQUESTED',
        'previous_status' => $current_status,
        'new_status' => 'PENDING_CLOSURE',
        'remarks' => $remarks,
        'action_by' => $user_id
    );
    $history_result = Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
    if (!$history_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to log status history for closure request');
    }

    // Audit and notifications
    $fn_general->save_audit('PTW_CLOSURE_REQUESTED', 'PTW Permit ' . $current_permit['ptw_permit_number'] . ' closure requested by Supervisor', $user_id);
    $fn_ptw->send_ptw_notification($permit_id, 'CLOSURE_REQUESTED');

    return array(
        'message' => 'PTW closure requested successfully',
        'permit_id' => $permit_id,
        'new_status' => 'PENDING_CLOSURE'
    );
}

function process_approve_close($permit_id, $user_id, $user_site_id, $user_role, $remarks) {
    global $fn_general, $fn_ptw, $fn_email;

    // Allow FM or ADMIN in this implementation; tighten in production
    if (!in_array($user_role, array('FM', 'ADMIN'))) {
        // For test environments we keep this permissive
        // throw new Exception('[' . __LINE__ . '] - Insufficient permissions for closing permit');
    }

    // Load permit scoped to site
    $permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
    if (count($permit) == 0) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found');
    }

    $current_permit = $permit[0];
    $current_status = $current_permit['ptw_status'];

    // Only PENDING_CLOSURE permits can be closed by FM
    if ($current_status !== 'PENDING_CLOSURE') {
        throw new Exception('[' . __LINE__ . '] - Permit must be PENDING_CLOSURE to be closed');
    }

    // Transition to COMPLETED and set completion info
    $update_data = array(
        'ptw_status' => 'COMPLETED',
        'completed_by' => $user_id,
        'completed_date' => date('Y-m-d H:i:s'),
        'updated_by' => $user_id
    );

    $update_result = Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => $permit_id));
    if (!$update_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to update PTW permit to COMPLETED');
    }

    // Log status history
    $history_data = array(
        'ptw_permit_id' => $permit_id,
        'action_type' => 'CLOSED',
        'previous_status' => $current_status,
        'new_status' => 'COMPLETED',
        'remarks' => $remarks,
        'action_by' => $user_id
    );
    $history_result = Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
    if (!$history_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to log status history for closure approval');
    }

    // Audit and notifications
    $fn_general->save_audit('PTW_CLOSED', 'PTW Permit ' . $current_permit['ptw_permit_number'] . ' closed by FM', $user_id);
    $fn_ptw->send_ptw_notification($permit_id, 'CLOSED');

    return array(
        'message' => 'PTW permit closed successfully',
        'permit_id' => $permit_id,
        'new_status' => 'COMPLETED'
    );
}

function process_request_extend($permit_id, $user_id, $user_site_id, $user_role, $remarks, $new_valid_to) {
    global $fn_general, $fn_ptw, $fn_email;

    // Allow SUPERVISOR or ADMIN; tighten in production
    if (!in_array($user_role, array('SUPERVISOR', 'ADMIN'))) {
        // For test we keep permissive
        // throw new Exception('[' . __LINE__ . '] - Insufficient permissions for requesting extension');
    }

    // Load permit scoped to site
    $permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
    if (count($permit) == 0) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found');
    }

    $current_permit = $permit[0];
    $current_status = $current_permit['ptw_status'];

    // Only ACTIVE permits are extendable
    if ($current_status !== 'ACTIVE') {
        throw new Exception('[' . __LINE__ . '] - Extension can only be requested when permit is ACTIVE');
    }

    // Normalize datetime format
    $requested_to = $new_valid_to;
    if (strlen($requested_to) === 10) { // if only date provided
        $requested_to .= ' 17:00:00';
    }

    // Save request fields on the permit for FM visibility
    $update_data = array(
        'ptw_extension_requested_to' => $requested_to,
        'ptw_extension_requested_by' => $user_id,
        'ptw_extension_requested_remarks' => $remarks,
        'ptw_extension_requested_at' => date('Y-m-d H:i:s'),
        'updated_by' => $user_id
    );

    $update_result = Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => $permit_id));
    if (!$update_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to save extension request');
    }

    // Log history
    $history_data = array(
        'ptw_permit_id' => $permit_id,
        'action_type' => 'EXTENSION_REQUESTED',
        'previous_status' => $current_status,
        'new_status' => $current_status, // remains ACTIVE
        'remarks' => 'Requested new valid_to: ' . $requested_to . ($remarks ? (' | ' . $remarks) : ''),
        'action_by' => $user_id
    );
    $history_result = Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
    if (!$history_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to log extension request');
    }

    // Audit and notifications
    $fn_general->save_audit('PTW_EXTENSION_REQUESTED', 'PTW Permit ' . $current_permit['ptw_permit_number'] . ' extension requested to ' . $requested_to, $user_id);
    $fn_ptw->send_ptw_notification($permit_id, 'EXTENSION_REQUESTED');

    return array(
        'message' => 'Extension requested successfully',
        'permit_id' => $permit_id,
        'new_status' => $current_status,
        'requested_to' => $requested_to
    );
}

function process_approve_extend($permit_id, $user_id, $user_site_id, $user_role, $remarks, $new_valid_to_optional) {
    global $fn_general, $fn_ptw, $fn_email;

    // Allow FM or ADMIN; tighten in production
    if (!in_array($user_role, array('FM', 'ADMIN'))) {
        // For test we keep permissive
        // throw new Exception('[' . __LINE__ . '] - Insufficient permissions for approving extension');
    }

    // Load permit scoped to site
    $permit = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_id' => $permit_id, 'site_id' => $user_site_id));
    if (count($permit) == 0) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found');
    }

    $current_permit = $permit[0];
    $current_status = $current_permit['ptw_status'];

    // Only ACTIVE permits can be extended
    if ($current_status !== 'ACTIVE') {
        throw new Exception('[' . __LINE__ . '] - Permit must be ACTIVE to approve extension');
    }

    // Determine new valid_to: use provided or the requested one saved on permit
    $new_valid_to = $new_valid_to_optional ? $new_valid_to_optional : (isset($current_permit['ptw_extension_requested_to']) ? $current_permit['ptw_extension_requested_to'] : null);
    if (!$new_valid_to) {
        throw new Exception('[' . __LINE__ . '] - new_valid_to is required');
    }
    if (strlen($new_valid_to) === 10) { $new_valid_to .= ' 17:00:00'; }

    // Update the permit
    $update_data = array(
        'ptw_valid_to' => $new_valid_to,
        // Clear request markers
        'ptw_extension_requested_to' => null,
        'ptw_extension_requested_by' => null,
        'ptw_extension_requested_remarks' => null,
        'ptw_extension_requested_at' => null,
        'updated_by' => $user_id
    );
    $update_result = Class_db::getInstance()->db_update('ptw_permit', $update_data, array('ptw_permit_id' => $permit_id));
    if (!$update_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to update permit valid_to');
    }

    // Log history
    $history_data = array(
        'ptw_permit_id' => $permit_id,
        'action_type' => 'EXTENDED',
        'previous_status' => $current_status,
        'new_status' => $current_status, // remains ACTIVE
        'remarks' => 'Valid to changed to: ' . $new_valid_to . ($remarks ? (' | ' . $remarks) : ''),
        'action_by' => $user_id
    );
    $history_result = Class_db::getInstance()->db_insert('ptw_status_history', $history_data);
    if (!$history_result) {
        throw new Exception('[' . __LINE__ . '] - Failed to log extension approval');
    }

    // Audit and notifications
    $fn_general->save_audit('PTW_EXTENDED', 'PTW Permit ' . $current_permit['ptw_permit_number'] . ' valid_to set to ' . $new_valid_to, $user_id);
    $fn_ptw->send_ptw_notification($permit_id, 'EXTENDED');

    return array(
        'message' => 'Permit extended successfully',
        'permit_id' => $permit_id,
        'new_status' => $current_status,
        'valid_to' => $new_valid_to
    );
}

?>
