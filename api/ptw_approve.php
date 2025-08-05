<?php
/**
 * PTW (Permit to Work) Approval Workflow API
 * Handles all workflow actions: approving, rejecting, closing, etc.
 * Following GEMS2 standards and patterns
 * 
 * @author GEMS2 Development Team
 * @created August 5, 2025
 */

require_once 'function/f_general.php';
require_once 'function/f_email.php';
require_once 'class/PtwPermit.php';

// Initialize response structure (GEMS2 standard)
$form_data = array();
$form_data['success'] = false;
$form_data['result'] = array();
$form_data['errmsg'] = '';

// Transaction flag for proper rollback handling
$is_transaction = false;

try {
    // GEMS2 Standard: JWT Authentication required for all API calls
    $jwt_data = checkJwt();
    $userId = $jwt_data->userId;
    $userRole = $jwt_data->roleId;
    $userSite = $jwt_data->siteId;
    
    // Get request method
    $request_method = $_SERVER['REQUEST_METHOD'];
    
    // Only PUT method allowed for workflow actions
    if ($request_method !== 'PUT') {
        throw new Exception('[' . __LINE__ . '] - Only PUT method allowed for workflow actions');
    }
    
    // Initialize function classes following GEMS2 pattern
    $fn_general = new General();
    $fn_email = new Email();
    $fn_ptw = new PtwPermit($userId, true);
    
    // Parse URL for RESTful endpoints
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Remove 'api' and 'ptw_approve.php' from path to get parameters
    $urlArr = array_slice($pathParts, array_search('ptw_approve.php', $pathParts) + 1);
    
    if (empty($urlArr[0]) || !is_numeric($urlArr[0])) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit ID required');
    }
    
    $ptwPermitId = intval($urlArr[0]);
    
    if (empty($urlArr[1])) {
        throw new Exception('[' . __LINE__ . '] - Action required');
    }
    
    $action = $urlArr[1];
    
    // Get PUT data
    parse_str(file_get_contents("php://input"), $putData);
    
    // Get current permit details for validation
    $permitDetails = $fn_ptw->getPtwDetails($ptwPermitId, $userId, $userRole);
    if (empty($permitDetails)) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found or access denied');
    }
    
    // GEMS2 Standard: Start transaction for all workflow actions
    DbMysql::beginTransaction();
    $is_transaction = true;
    
    $result = array();
    $auditMessage = '';
    $emailTemplate = null;
    $emailData = array(
        'ptw_number' => $permitDetails['ptwPermitNumber'],
        'work_area' => $permitDetails['ptwWorkArea'],
        'work_type' => $permitDetails['ptwWorkType']
    );
    
    switch ($action) {
        case 'supervisor_approve':
            // Supervisor Approval
            if (!in_array($userRole, ['3', '4'])) { // Supervisor or Engineer
                throw new Exception('[' . __LINE__ . '] - Only Supervisors can approve at this level', 403);
            }
            
            $remarks = $putData['remarks'] ?? '';
            $result = $fn_ptw->supervisorApprove($ptwPermitId, $userId, $remarks);
            $auditMessage = 'PTW Supervisor Approved: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 2; // Supervisor approved email template
            break;
            
        case 'supervisor_reject':
            // Supervisor Rejection
            if (!in_array($userRole, ['3', '4'])) { // Supervisor or Engineer
                throw new Exception('[' . __LINE__ . '] - Only Supervisors can reject at this level', 403);
            }
            
            $remarks = $putData['remarks'] ?? '';
            if (empty($remarks)) {
                throw new Exception('[' . __LINE__ . '] - Rejection remarks are required');
            }
            
            $result = $fn_ptw->supervisorReject($ptwPermitId, $userId, $remarks);
            $auditMessage = 'PTW Supervisor Rejected: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 3; // Supervisor rejected email template
            $emailData['rejection_reason'] = $remarks;
            break;
            
        case 'she_approve':
            // SHE Officer Approval
            if (!in_array($userRole, ['5'])) { // SHE Officer role
                throw new Exception('[' . __LINE__ . '] - Only SHE Officers can approve at this level', 403);
            }
            
            $remarks = $putData['remarks'] ?? '';
            $result = $fn_ptw->sheApprove($ptwPermitId, $userId, $remarks);
            $auditMessage = 'PTW SHE Approved: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 4; // SHE approved email template
            break;
            
        case 'she_reject':
            // SHE Officer Rejection
            if (!in_array($userRole, ['5'])) { // SHE Officer role
                throw new Exception('[' . __LINE__ . '] - Only SHE Officers can reject at this level', 403);
            }
            
            $remarks = $putData['remarks'] ?? '';
            if (empty($remarks)) {
                throw new Exception('[' . __LINE__ . '] - Rejection remarks are required');
            }
            
            $result = $fn_ptw->sheReject($ptwPermitId, $userId, $remarks);
            $auditMessage = 'PTW SHE Rejected: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 5; // SHE rejected email template
            $emailData['rejection_reason'] = $remarks;
            break;
            
        case 'fm_approve':
            // Facility Manager Approval
            if (!in_array($userRole, ['2'])) { // Manager role
                throw new Exception('[' . __LINE__ . '] - Only Facility Managers can approve at this level', 403);
            }
            
            $remarks = $putData['remarks'] ?? '';
            $result = $fn_ptw->fmApprove($ptwPermitId, $userId, $remarks);
            $auditMessage = 'PTW Facility Manager Approved: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 6; // FM approved email template
            break;
            
        case 'fm_reject':
            // Facility Manager Rejection
            if (!in_array($userRole, ['2'])) { // Manager role
                throw new Exception('[' . __LINE__ . '] - Only Facility Managers can reject at this level', 403);
            }
            
            $remarks = $putData['remarks'] ?? '';
            if (empty($remarks)) {
                throw new Exception('[' . __LINE__ . '] - Rejection remarks are required');
            }
            
            $result = $fn_ptw->fmReject($ptwPermitId, $userId, $remarks);
            $auditMessage = 'PTW Facility Manager Rejected: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 7; // FM rejected email template
            $emailData['rejection_reason'] = $remarks;
            break;
            
        case 'activate':
            // Activate permit (make it live for work to begin)
            if (!in_array($userRole, ['2', '3', '4'])) { // Manager, Supervisor, Engineer
                throw new Exception('[' . __LINE__ . '] - Insufficient permissions to activate PTW', 403);
            }
            
            $result = $fn_ptw->activatePermit($ptwPermitId, $userId);
            $auditMessage = 'PTW Activated: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 8; // PTW activated email template
            break;
            
        case 'start_work':
            // Record work start time
            if (!in_array($userRole, ['3', '4', '5'])) { // Supervisor, Engineer, Technician
                throw new Exception('[' . __LINE__ . '] - Insufficient permissions to start work', 403);
            }
            
            $result = $fn_ptw->startWork($ptwPermitId, $userId);
            $auditMessage = 'PTW Work Started: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 9; // Work started email template
            break;
            
        case 'complete_work':
            // Record work completion
            if (!in_array($userRole, ['3', '4', '5'])) { // Supervisor, Engineer, Technician
                throw new Exception('[' . __LINE__ . '] - Insufficient permissions to complete work', 403);
            }
            
            $workRemarks = $putData['workRemarks'] ?? '';
            $result = $fn_ptw->completeWork($ptwPermitId, $userId, $workRemarks);
            $auditMessage = 'PTW Work Completed: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 10; // Work completed email template
            break;
            
        case 'close':
            // Close permit (final step)
            if (!in_array($userRole, ['2', '3', '4'])) { // Manager, Supervisor, Engineer
                throw new Exception('[' . __LINE__ . '] - Insufficient permissions to close PTW', 403);
            }
            
            $closeRemarks = $putData['closeRemarks'] ?? '';
            $result = $fn_ptw->closePermit($ptwPermitId, $userId, $closeRemarks);
            $auditMessage = 'PTW Closed: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 11; // PTW closed email template
            break;
            
        case 'cancel':
            // Cancel permit
            if (!in_array($userRole, ['2', '3', '4'])) { // Manager, Supervisor, Engineer
                throw new Exception('[' . __LINE__ . '] - Insufficient permissions to cancel PTW', 403);
            }
            
            $cancelRemarks = $putData['cancelRemarks'] ?? '';
            if (empty($cancelRemarks)) {
                throw new Exception('[' . __LINE__ . '] - Cancellation remarks are required');
            }
            
            $result = $fn_ptw->cancelPermit($ptwPermitId, $userId, $cancelRemarks);
            $auditMessage = 'PTW Cancelled: ' . $permitDetails['ptwPermitNumber'];
            $emailTemplate = 12; // PTW cancelled email template
            $emailData['cancellation_reason'] = $cancelRemarks;
            break;
            
        case 'extend':
            // Extend permit validity
            if (!in_array($userRole, ['2', '3'])) { // Manager, Supervisor
                throw new Exception('[' . __LINE__ . '] - Insufficient permissions to extend PTW', 403);
            }
            
            $newValidTo = $putData['newValidTo'] ?? '';
            $extendRemarks = $putData['extendRemarks'] ?? '';
            
            if (empty($newValidTo)) {
                throw new Exception('[' . __LINE__ . '] - New expiry date is required');
            }
            
            if (strtotime($newValidTo) <= time()) {
                throw new Exception('[' . __LINE__ . '] - New expiry date must be in the future');
            }
            
            $result = $fn_ptw->extendPermit($ptwPermitId, $userId, $newValidTo, $extendRemarks);
            $auditMessage = 'PTW Extended: ' . $permitDetails['ptwPermitNumber'] . ' until ' . $newValidTo;
            $emailTemplate = 13; // PTW extended email template
            $emailData['new_expiry'] = $newValidTo;
            break;
            
        default:
            throw new Exception('[' . __LINE__ . '] - Invalid action: ' . $action);
    }
    
    // Log audit trail following GEMS2 standard
    $fn_general->save_audit('210', $userId, $auditMessage);
    
    // Send email notification if template specified
    if ($emailTemplate !== null) {
        // Send to permit requestor
        $fn_email->setup_email($permitDetails['ptwRequestedBy'], $emailTemplate, $emailData);
        
        // Send mobile notification
        $fn_email->setup_mobile_notification($permitDetails['ptwRequestedBy'], $emailTemplate + 100, array(
            'permit_no' => $permitDetails['ptwPermitNumber']
        ));
    }
    
    // GEMS2 Standard: Commit transaction
    DbMysql::commit();
    
    $form_data['result'] = $result;
    $form_data['errmsg'] = ucfirst(str_replace('_', ' ', $action)) . ' action completed successfully';
    $form_data['success'] = true;

} catch (Exception $ex) {
    // GEMS2 Standard: Proper error handling with transaction rollback
    if ($is_transaction) {
        DbMysql::rollback();
    }
    
    $form_data['success'] = false;
    $form_data['errmsg'] = $ex->getMessage();
    $form_data['error_code'] = $ex->getCode();
    
    // Log error for debugging
    error_log('[PTW Approve API Error] ' . $ex->getMessage() . ' - Line: ' . $ex->getLine() . ' - File: ' . $ex->getFile());
}

// GEMS2 Standard: Return JSON response
header('Content-Type: application/json');
echo json_encode($form_data);

// Close database connection
DbMysql::close();
?>
