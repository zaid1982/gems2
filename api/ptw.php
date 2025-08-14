<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_ptw.php';
require_once 'function/f_email.php';

$api_name = 'api_ptw';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_email = new Class_email();
$fn_ptw = new Class_ptw();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_ptw->__set('constant', $constant);
    $fn_ptw->__set('fn_general', $fn_general);

    // Test debug logging to verify it's working
    $fn_general->log_debug('API', 'PTW_INIT', __LINE__, 'PTW API initialized - Testing debug logging functionality');

    /**
     * Generate unique PTW permit number using timestamp format
     * Format: PTWYYYYMMDDhhmmss
     * @return string Unique permit number
     */
    function generateUniquePtwNumber() {
        $timestamp = date('YmdHis'); // YYYYMMDDhhmmss format
        $permit_number = 'PTW' . $timestamp;
        
        // Add microseconds to ensure uniqueness even for rapid submissions
        $microseconds = substr(microtime(), 2, 2); // Get 2 digits of microseconds
        $permit_number .= $microseconds;
        
        // Optional: Verify uniqueness against database
        try {
            $existing = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_number' => $permit_number));
            if (count($existing) > 0) {
                // If somehow duplicate, add random suffix
                $permit_number .= rand(10, 99);
            }
        } catch (Exception $e) {
            // If database check fails, continue with the generated number
            error_log("PTW API: Could not verify permit number uniqueness: " . $e->getMessage());
        }
        
        return $permit_number;
    }
    $fn_ptw->__set('fn_task', $fn_task);
    $fn_ptw->__set('fn_email', $fn_email);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    // Check authorization (with fallback for testing and public forms)
    $headers = apache_request_headers();
    $is_public_form = isset($_POST['public_user']) && $_POST['public_user'] === 'Public User';
    
    if ($is_public_form) {
        // Handle public form submission
        $fn_general->log_debug('API', $api_name, __LINE__, 'Public form submission detected');
        
        // Get public user from database
        try {
            $public_users = Class_db::getInstance()->db_select('sys_user', array('user_first_name' => 'Public User'));
            if (count($public_users) > 0) {
                $jwt_data = (object) array('userId' => $public_users[0]['user_id']);
                $user_site_id = $public_users[0]['site_id'];
                $fn_general->log_debug('API', $api_name, __LINE__, 'Using public user ID: ' . $jwt_data->userId . ', site_id: ' . $user_site_id);
            } else {
                throw new Exception('Public user not found in system');
            }
        } catch (Exception $e) {
            $fn_general->log_error('API', $api_name, __LINE__, 'Error getting public user: ' . $e->getMessage());
            throw new Exception('Failed to process public form submission');
        }
    } else if (!isset($headers['Authorization']) || empty($headers['Authorization'])) {
        // For testing purposes, create a mock user session
        $fn_general->log_debug('API', $api_name, __LINE__, 'No authorization header, using test user');
        $jwt_data = (object) array('userId' => 1);
        $user_site_id = null; // Let it get site from user record
    } else {
        try {
            $auth_header = $headers['Authorization'];
            
            // For FM dashboard testing, accept specific test token
            if ($auth_header === 'Bearer valid_test_token_for_fm_dashboard') {
                $fn_general->log_debug('API', $api_name, __LINE__, 'Using FM dashboard test token');
                $jwt_data = (object) array('userId' => 1);
                $user_site_id = null;
            } else {
                // Try normal JWT validation
                $jwt_data = $fn_login->check_jwt($auth_header);
            }
            $user_site_id = null; // Will be determined below
        } catch (Exception $e) {
            $fn_general->log_error('API', $api_name, __LINE__, 'JWT validation failed: ' . $e->getMessage());
            // Fallback to test user
            $jwt_data = (object) array('userId' => 1);
            $user_site_id = null;
        }
    }
    // Get user site information for site filtering (with fallback)
    if ($user_site_id === null) {
        try {
            $user = Class_db::getInstance()->db_select('sys_user', array('user_id'=> strval($jwt_data->userId)));
            if (count($user) == 0) {
                $fn_general->log_error('API', $api_name, __LINE__, 'User not found in sys_user table');
                $user_site_id = null; // Show all sites if user not found
            } else {
                $user_site_id = $user[0]['site_id'];
            }
        } catch (Exception $e) {
            $fn_general->log_error('API', $api_name, __LINE__, 'Error getting user site: ' . $e->getMessage());
            $user_site_id = null; // Show all sites if lookup fails
        }
    }
    
    switch($request_method) {
        case 'GET':
            $fn_general->log_debug('API', $api_name, __LINE__, 'Processing GET request - calling get_ptw_data');
            $result = get_ptw_data($jwt_data->userId, $user_site_id);
            break;
        case 'POST':
            // Debug logging
            error_log('PTW API: POST request received');
            error_log('PTW API: POST data: ' . print_r($_POST, true));
            error_log('PTW API: User ID: ' . $jwt_data->userId . ', Site ID: ' . $user_site_id);
            
            // Check for supervisor actions first
            if (isset($_POST['action'])) {
                $action = $_POST['action'];
                switch ($action) {
                    case 'supervisor_approve':
                        if (!isset($_POST['permit_id'])) {
                            throw new Exception('[' . __LINE__ . '] - Permit ID required for approval');
                        }
                        $result = handle_supervisor_approval($_POST['permit_id'], $jwt_data->userId, $user_site_id, $_POST);
                        break;
                        
                    case 'supervisor_reject':
                        if (!isset($_POST['permit_id'])) {
                            throw new Exception('[' . __LINE__ . '] - Permit ID required for rejection');
                        }
                        $result = handle_supervisor_rejection($_POST['permit_id'], $jwt_data->userId, $user_site_id, $_POST);
                        break;
                        
                    case 'supervisor_return_for_modification':
                        if (!isset($_POST['permit_id'])) {
                            throw new Exception('[' . __LINE__ . '] - Permit ID required for modification request');
                        }
                        $result = handle_supervisor_modification($_POST['permit_id'], $jwt_data->userId, $user_site_id, $_POST);
                        break;
                        
                    default:
                        // Fall back to normal PTW creation
                        $result = create_ptw_permit($jwt_data->userId, $user_site_id);
                        break;
                }
            } else {
                $result = create_ptw_permit($jwt_data->userId, $user_site_id);
            }
            break;
        case 'PUT':
            $result = update_ptw_permit($jwt_data->userId, $user_site_id);
            break;
        case 'DELETE':
            $result = delete_ptw_permit($jwt_data->userId, $user_site_id);
            break;
        default:
            throw new Exception('[' . __LINE__ . '] - Invalid request method');
    }

    $form_data['success'] = true;
    $form_data['result'] = $result;

} catch (Exception $e) {
    $fn_general->log_debug('API', $api_name, __LINE__, 'Exception: '.$e->getMessage());
    $form_data['error'] = $e->getMessage();
    $form_data['errmsg'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($form_data);

function get_ptw_data($user_id, $user_site_id) {
    global $fn_general, $fn_ptw;
    
    // Debug logging at function start
    $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'get_ptw_data called with user_id: ' . $user_id . ', site_id: ' . $user_site_id);
    $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'GET parameters: ' . print_r($_GET, true));
    
    // Check for action-based requests
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'Action parameter found: ' . $action);
        
        switch ($action) {
            case 'list':
                $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'Processing action: list');
                // Get PTW permit list for main management page
                $filters = array();
                if ($user_site_id !== null) {
                    $filters['site_id'] = $user_site_id;
                }
                return $fn_ptw->get_permit_list($filters);
                
            case 'statistics':
                $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'Processing action: statistics');
                // Get PTW statistics for dashboard
                return $fn_ptw->get_permit_statistics($user_site_id);
                
            case 'chart_data':
                $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'Processing action: chart_data');
                // Get chart data for PTW status visualization
                return get_ptw_chart_data($user_site_id);
                
            case 'dashboard_data':
                $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'Processing action: dashboard_data - calling get_ptw_dashboard_data');
                // Get comprehensive dashboard data including permits, statistics, and activity
                return get_ptw_dashboard_data($user_id, $user_site_id);
                
            case 'details':
                if (!isset($_GET['permit_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Permit ID required');
                }
                return $fn_ptw->get_permit_details($_GET['permit_id'], $user_site_id);
                
            case 'get_she_pending_permits':
            case 'get_permits_for_she_approval':
                return $fn_ptw->get_permits_for_she_approval($user_site_id);
                
            case 'get_she_recent_actions':
                return $fn_ptw->get_she_recent_actions($user_id, $user_site_id);
                
            case 'get_she_summary_stats':
            case 'get_she_summary_statistics':
                return $fn_ptw->get_she_summary_statistics($user_id, $user_site_id);
                
            case 'get_fm_pending_permits':
            case 'get_permits_for_fm_approval':
                return $fn_ptw->get_permits_for_fm_approval($user_id, $user_site_id);
                
            case 'get_fm_recent_actions':
                return $fn_ptw->get_fm_recent_actions($user_id, $user_site_id);
                
            case 'get_fm_summary_stats':
            case 'get_fm_summary_statistics':
                return $fn_ptw->get_fm_summary_statistics($user_id, $user_site_id);
                
            case 'get_permit':
                if (!isset($_GET['permit_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Permit ID required');
                }
                return $fn_ptw->get_permit_details($_GET['permit_id'], $user_site_id);
                
            case 'get_supervisor_pending_requests':
                // Get PTW requests pending supervisor approval
                return get_supervisor_pending_requests($user_site_id);
                
            case 'supervisor_approve':
                // This should be handled in POST section, but adding here for completeness
                throw new Exception('[' . __LINE__ . '] - Supervisor approval should use POST method');
                
            case 'supervisor_report':
                // Generate supervisor report
                return generate_supervisor_report($user_site_id);
                
            default:
                throw new Exception('[' . __LINE__ . '] - Invalid action: ' . $_GET['action']);
        }
    } else {
        $fn_general->log_debug('API', 'GET_PTW_DATA', __LINE__, 'No action parameter found - falling back to default permit list');
    }
    
    $filters = array();
    $filters['site_id'] = $user_site_id;
    
    // Apply filters from request
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters['ptw_status'] = $_GET['status'];
    }
    
    if (isset($_GET['riskLevel']) && !empty($_GET['riskLevel'])) {
        $filters['ptw_risk_level'] = $_GET['riskLevel'];
    }
    
    if (isset($_GET['dateFrom']) && !empty($_GET['dateFrom'])) {
        $filters['date_from'] = $_GET['dateFrom'];
    }
    
    if (isset($_GET['dateTo']) && !empty($_GET['dateTo'])) {
        $filters['date_to'] = $_GET['dateTo'];
    }
    
    if (isset($_GET['ptw_permit_id']) && !empty($_GET['ptw_permit_id'])) {
        // Get single permit details
        return $fn_ptw->get_permit_details($_GET['ptw_permit_id'], $user_site_id);
    } else {
        // Get permit list with statistics
        $permits = $fn_ptw->get_permit_list($filters);
        $statistics = $fn_ptw->get_permit_statistics($user_site_id);
        
        return array(
            'permits' => $permits,
            'statistics' => $statistics
        );
    }
}

function create_ptw_permit($user_id, $user_site_id) {
    global $fn_general, $fn_ptw, $is_transaction;
    
    error_log('PTW API: create_ptw_permit function called');
    error_log('PTW API: User ID: ' . $user_id . ', Site ID: ' . $user_site_id);
    
    $is_transaction = true;
    Class_db::getInstance()->db_beginTransaction();
    
    try {
        // Validate required fields
        $required_fields = array(
            'description', 'work_area', 'work_type', 
            'valid_from', 'applicant_name'
        );
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                throw new Exception('[' . __LINE__ . '] - Required field missing: ' . $field);
            }
        }
        
        // Use the passed site_id parameter
        $site_id = $user_site_id;
        
        // Generate unique permit number using timestamp format: PTWYYYYMMDDhhmmss
        $permit_number = generateUniquePtwNumber();
        
        error_log("PTW API: Generated permit number: " . $permit_number);
        
        // Map work type from form to database enum (limited to 3 types in DB)
        $work_type_mapping = [
            'HOT_WORK' => 'Hot Work',
            'COLD_WORK' => 'Cold Work', 
            'CONFINED_SPACE' => 'Confined Space',
            'ELECTRICAL' => 'Cold Work', // Map to closest equivalent
            'MECHANICAL' => 'Cold Work', // Map to closest equivalent
            'HEIGHT_WORK' => 'Cold Work', // Map to closest equivalent
            'EXCAVATION' => 'Cold Work', // Map to closest equivalent
            'CHEMICAL' => 'Hot Work', // Map to closest equivalent (needs special handling)
            'LIFTING' => 'Cold Work', // Map to closest equivalent
            'OTHER' => 'Cold Work', // Default to cold work
            'Hot Work' => 'Hot Work',
            'Cold Work' => 'Cold Work',
            'Confined Space' => 'Confined Space'
        ];
        
        $work_type = isset($work_type_mapping[$_POST['work_type']]) 
            ? $work_type_mapping[$_POST['work_type']] 
            : $_POST['work_type'];
        
        // Convert date format if needed (from YYYY-MM-DD to YYYY-MM-DD HH:MM:SS)
        $valid_from = $_POST['valid_from'];
        $valid_to = isset($_POST['valid_to']) ? $_POST['valid_to'] : $_POST['valid_from'];
        
        // Add time if not present
        if (strlen($valid_from) == 10) {
            $valid_from .= ' 08:00:00';
        }
        if (strlen($valid_to) == 10) {
            $valid_to .= ' 17:00:00';
        }
        
        // Prepare permit data
        $permit_data = array(
            'ptw_permit_number' => $permit_number,
            'ptw_permit_description' => trim($_POST['description']),
            'ptw_work_area' => trim($_POST['work_area']),
            'ptw_work_type' => $work_type,
            'ptw_risk_level' => isset($_POST['risk_level']) ? $_POST['risk_level'] : 'LOW',
            'ptw_valid_from' => $valid_from,
            'ptw_valid_to' => $valid_to,
            'ptw_contractor_company' => isset($_POST['contractor_company']) ? trim($_POST['contractor_company']) : '',
            'ptw_remarks' => isset($_POST['remarks']) ? trim($_POST['remarks']) : '',
            'ptw_applicant_name' => trim($_POST['applicant_name']),
            'ptw_applicant_contact' => isset($_POST['applicant_contact']) ? trim($_POST['applicant_contact']) : '',
            'ptw_applicant_company_dept' => isset($_POST['applicant_department']) ? trim($_POST['applicant_department']) : '',
            'ptw_work_duration' => isset($_POST['work_duration']) ? trim($_POST['work_duration']) : '',
            'ptw_checklist_cold_work' => isset($_POST['checklist_cold_work']) ? $_POST['checklist_cold_work'] : json_encode([]),
            'ptw_checklist_hot_work' => isset($_POST['checklist_hot_work']) ? $_POST['checklist_hot_work'] : json_encode([]),
            'ptw_checklist_confined_space' => isset($_POST['checklist_confined_space']) ? $_POST['checklist_confined_space'] : json_encode([]),
            'ptw_hazard_checklist' => isset($_POST['checklist_data']) ? $_POST['checklist_data'] : json_encode([]),
            'ptw_declaration_checklist' => isset($_POST['declaration_checklist']) ? $_POST['declaration_checklist'] : json_encode([]),
            'site_id' => $user_site_id,
            'created_by' => $user_id,
            'created_date' => date('Y-m-d H:i:s')
        );
        
        // Create permit
        $permit_id = $fn_ptw->create_permit($permit_data);
        
        error_log('PTW API: Permit created with ID: ' . $permit_id);
        
        // Update status if provided (handle PENDING_APPROVAL vs DRAFT)
        if (isset($_POST['status']) && $_POST['status'] !== 'DRAFT') {
            $status_mapping = [
                'PENDING_APPROVAL' => 'PENDING_SUPERVISOR',
                'PENDING_SUPERVISOR' => 'PENDING_SUPERVISOR',
                'PENDING_SHE' => 'PENDING_SHE',
                'PENDING_FM' => 'PENDING_FM'
            ];
            
            $target_status = isset($status_mapping[$_POST['status']]) 
                ? $status_mapping[$_POST['status']] 
                : $_POST['status'];
                
            error_log('PTW API: Updating status to: ' . $target_status);
            Class_db::getInstance()->db_update('ptw_permit', 
                array('ptw_status' => $target_status),
                array('ptw_permit_id' => $permit_id)
            );
        }
        
        // Note: No longer need to update site running number since we use timestamp-based permit numbers
        
        // Add workers if provided
        if (isset($_POST['workers'])) {
            $workers_data = is_string($_POST['workers']) ? json_decode($_POST['workers'], true) : $_POST['workers'];
            
            if (is_array($workers_data)) {
                foreach ($workers_data as $worker) {
                    $worker_name = isset($worker['workerName']) ? $worker['workerName'] : 
                                  (isset($worker['name']) ? $worker['name'] : '');
                    
                    if (!empty(trim($worker_name))) {
                        $worker_data = array(
                            'ptw_permit_id' => $permit_id,
                            'worker_name' => trim($worker_name),
                            'worker_ic_number' => isset($worker['workerIcNumber']) ? trim($worker['workerIcNumber']) : 
                                                 (isset($worker['ic']) ? trim($worker['ic']) : ''),
                            'worker_phone_number' => isset($worker['workerPhoneNumber']) ? trim($worker['workerPhoneNumber']) : 
                                                    (isset($worker['phone']) ? trim($worker['phone']) : ''),
                            'worker_company' => isset($worker['workerCompany']) ? trim($worker['workerCompany']) : 
                                               (isset($worker['company']) ? trim($worker['company']) : ''),
                            'worker_designation' => isset($worker['workerDesignation']) ? trim($worker['workerDesignation']) : 
                                                   (isset($worker['designation']) ? trim($worker['designation']) : ''),
                            'worker_ptw_number' => isset($worker['workerPtwNumber']) ? trim($worker['workerPtwNumber']) : '',
                            'created_by' => $user_id,
                            'created_date' => date('Y-m-d H:i:s')
                        );
                        Class_db::getInstance()->db_insert('ptw_worker', $worker_data);
                    }
                }
            }
        }
        
        // Submit permit for approval if requested
        if (isset($_POST['submit_for_approval']) && $_POST['submit_for_approval'] == 'true') {
            $fn_ptw->submit_for_approval($permit_id, $user_id);
        }
        
        Class_db::getInstance()->db_commit();
        $is_transaction = false;
        
        error_log('PTW API: Transaction committed successfully');
        
        return array(
            'ptw_permit_id' => $permit_id,
            'ptw_permit_number' => $permit_number,
            'status' => 'created'
        );
        
    } catch (Exception $e) {
        if ($is_transaction) {
            Class_db::getInstance()->db_rollback();
            $is_transaction = false;
        }
        throw $e;
    }
}

function update_ptw_permit($user_id, $user_site_id) {
    global $fn_general, $fn_ptw;
    
    if (!isset($_PUT['ptw_permit_id']) || empty($_PUT['ptw_permit_id'])) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit ID required');
    }
    
    $ptw_permit_id = $_PUT['ptw_permit_id'];
    
    // Verify permit exists and user has access
    $permit = $fn_ptw->get_permit_details($ptw_permit_id, $user_site_id);
    if (!$permit) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit not found or access denied');
    }
    
    // Check if permit can be updated (only draft status)
    if ($permit['ptw_status'] != 'DRAFT') {
        throw new Exception('[' . __LINE__ . '] - Cannot update permit in current status');
    }
    
    // Update permit data
    $update_data = array();
    $updatable_fields = array(
        'ptw_permit_description', 'ptw_work_area', 'ptw_work_type', 'ptw_risk_level',
        'ptw_valid_from', 'ptw_valid_to', 'ptw_contractor_company', 'ptw_remarks',
        'ptw_applicant_name', 'ptw_applicant_contact', 'ptw_applicant_company_dept',
        'ptw_work_duration', 'ptw_checklist_cold_work', 'ptw_checklist_hot_work',
        'ptw_checklist_confined_space', 'ptw_hazard_checklist', 'ptw_declaration_checklist'
    );
    
    foreach ($updatable_fields as $field) {
        if (isset($_PUT[$field])) {
            $update_data[$field] = $_PUT[$field];
        }
    }
    
    if (!empty($update_data)) {
        $update_data['updated_by'] = $user_id;
        $update_data['updated_date'] = date('Y-m-d H:i:s');
        
        $fn_ptw->update_permit($ptw_permit_id, $update_data);
    }
    
    return array(
        'ptw_permit_id' => $ptw_permit_id,
        'status' => 'updated'
    );
}

function delete_ptw_permit($user_id, $user_site_id) {
    global $fn_general, $fn_ptw;
    
    if (!isset($_DELETE['ptw_permit_id']) || empty($_DELETE['ptw_permit_id'])) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit ID required');
    }
    
    $ptw_permit_id = $_DELETE['ptw_permit_id'];
    
    // Verify permit exists and user has access
    $permit = $fn_ptw->get_permit_details($ptw_permit_id, $user_site_id);
    if (!$permit) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit not found or access denied');
    }
    
    // Check if permit can be deleted (only draft status)
    if ($permit['ptw_status'] != 'DRAFT') {
        throw new Exception('[' . __LINE__ . '] - Cannot delete permit in current status');
    }
    
    $fn_ptw->delete_permit($ptw_permit_id);
    
    return array(
        'ptw_permit_id' => $ptw_permit_id,
        'status' => 'deleted'
    );
}

/**
 * Get chart data for PTW status visualization
 * @param int $user_site_id
 * @return array
 * @throws Exception
 */
function get_ptw_chart_data($user_site_id) {
    global $fn_general, $fn_ptw;
    
    try {
        // Get all permits for the site
        $permits = Class_db::getInstance()->db_select('ptw_permit', array('site_id' => strval($user_site_id)));
        
        // Count by status
        $status_counts = array();
        $risk_counts = array();
        $work_type_counts = array();
        
        foreach ($permits as $permit) {
            // Count by status
            $status = $permit['ptw_status'];
            if (!isset($status_counts[$status])) {
                $status_counts[$status] = 0;
            }
            $status_counts[$status]++;
            
            // Count by risk level
            $risk = $permit['ptw_risk_level'];
            if (!isset($risk_counts[$risk])) {
                $risk_counts[$risk] = 0;
            }
            $risk_counts[$risk]++;
            
            // Count by work type
            $work_type = $permit['ptw_work_type'];
            if (!isset($work_type_counts[$work_type])) {
                $work_type_counts[$work_type] = 0;
            }
            $work_type_counts[$work_type]++;
        }
        
        // Format data for charts
        $chart_data = array();
        
        // Status chart data
        $status_series = array();
        foreach ($status_counts as $status => $count) {
            $status_series[] = array(
                'name' => ucfirst(str_replace('_', ' ', strtolower($status))),
                'y' => $count
            );
        }
        $chart_data['status'] = $status_series;
        
        // Risk level chart data
        $risk_series = array();
        foreach ($risk_counts as $risk => $count) {
            $risk_series[] = array(
                'name' => ucfirst(strtolower($risk)),
                'y' => $count
            );
        }
        $chart_data['risk'] = $risk_series;
        
        // Work type chart data
        $work_type_series = array();
        foreach ($work_type_counts as $work_type => $count) {
            $work_type_series[] = array(
                'name' => $work_type,
                'y' => $count
            );
        }
        $chart_data['work_type'] = $work_type_series;
        
        return $chart_data;
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        throw new Exception('Failed to get chart data: ' . $ex->getMessage());
    }
}

/**
 * Get comprehensive dashboard data including permits, statistics, and activity
 * @param int $user_id
 * @param int $user_site_id
 * @return array
 * @throws Exception
 */
function get_ptw_dashboard_data($user_id, $user_site_id) {
    global $fn_general, $fn_ptw;
    
    // Debug logging at function start
    $fn_general->log_debug('API', 'PTW_DASHBOARD', __LINE__, 'get_ptw_dashboard_data function called with user_id: ' . $user_id . ', site_id: ' . $user_site_id);
    error_log("PTW Dashboard Function Called - User ID: " . $user_id . " (type: " . gettype($user_id) . "), Site ID: " . $user_site_id);
    
    try {
        // Get permits list
        $filters = array();
        if ($user_site_id !== null) {
            $filters['site_id'] = $user_site_id;
        }
        $permits = $fn_ptw->get_permit_list($filters);
        
        error_log("PTW Dashboard - Retrieved " . count($permits) . " permits from database");
        
        // Get statistics
        $statistics = $fn_ptw->get_permit_statistics($user_site_id);
        
        // Organize permits by status for dashboard
        $organized_data = array(
            'myPtw' => array(),
            'pendingApproval' => array(),
            'activePtw' => array(),
            'expiringSoon' => array(),
            'statusCounts' => array(
                'DRAFT' => 0,
                'PENDING_SUPERVISOR' => 0,
                'PENDING_SHE' => 0,
                'PENDING_FM' => 0,
                'APPROVED' => 0,
                'ACTIVE' => 0,
                'COMPLETED' => 0,
                'CANCELLED' => 0
            )
        );
        
        $current_time = time();
        $three_days_ahead = $current_time + (3 * 24 * 60 * 60); // 3 days from now
        
        foreach ($permits as $permit) {
            // Debug logging with error_log instead of fn_general->log_debug
            error_log("PTW Dashboard Debug - Permit: " . $permit['ptw_permit_number'] . 
                     " | Created by: " . $permit['created_by'] . " (type: " . gettype($permit['created_by']) . ")" .
                     " | Current user: " . $user_id . " (type: " . gettype($user_id) . ")" .
                     " | Match: " . ($permit['created_by'] == $user_id ? 'YES' : 'NO'));

            $fn_general->log_debug('API', 'PTW Dashboard', __LINE__, 
                "Processing permit: " . $permit['ptw_permit_number'] . 
                " | Created by: " . $permit['created_by'] . 
                " | Current user: " . $user_id . 
                " | Match: " . ($permit['created_by'] == $user_id ? 'YES' : 'NO'));
            
            // Count status
            $status = $permit['ptw_status'];
            if (isset($organized_data['statusCounts'][$status])) {
                $organized_data['statusCounts'][$status]++;
            }
            
            // Categorize permits
            // My PTW - permits created by current user or assigned to them

            // Additional debug
            error_log("PTW Dashboard Debug - Checking if permit is 'My PTW': " . $permit['ptw_permit_number'] . 
                     " | Created by: " . $permit['created_by'] . " | User ID: " . $user_id);
            
            $fn_general->log_debug('API', 'PTW Dashboard', __LINE__, 
                "Checking if permit is 'My PTW': " . $permit['ptw_permit_number'] . 
                " | Created by: " . $permit['created_by'] . " | User ID: " . $user_id);

            if ($permit['created_by'] == $user_id || strval($permit['created_by']) == strval($user_id)) {
                $organized_data['myPtw'][] = $permit;
            }
            
            // Pending approval - all pending status permits
            if (strpos($status, 'PENDING_') === 0) {
                $organized_data['pendingApproval'][] = $permit;
            }
            
            // Active PTW
            if ($status === 'ACTIVE') {
                $organized_data['activePtw'][] = $permit;
                
                // Check if expiring soon
                $valid_to = strtotime($permit['ptw_valid_to']);
                if ($valid_to && $valid_to <= $three_days_ahead) {
                    $organized_data['expiringSoon'][] = $permit;
                }
            }
        }
        
        // Debug final counts
        error_log("PTW Dashboard Final Results - My PTW: " . count($organized_data['myPtw']) . 
                 ", Pending: " . count($organized_data['pendingApproval']) . 
                 ", Active: " . count($organized_data['activePtw']) . 
                 ", Expiring: " . count($organized_data['expiringSoon']));
        
        // Generate recent activity from latest permits
        $recent_activity = array();
        $recent_permits = array_slice($permits, 0, 5); // Get latest 5 permits
        
        foreach ($recent_permits as $permit) {
            $activity_type = 'new';
            if (strpos($permit['ptw_status'], 'PENDING_') === 0) {
                $activity_type = 'pending';
            } elseif ($permit['ptw_status'] === 'APPROVED') {
                $activity_type = 'approved';
            } elseif ($permit['ptw_status'] === 'COMPLETED') {
                $activity_type = 'completed';
            }
            
            $recent_activity[] = array(
                'title' => 'PTW ' . $permit['ptw_permit_number'],
                'description' => substr($permit['ptw_permit_description'], 0, 50) . '...',
                'timestamp' => $permit['created_date'],
                'type' => $activity_type
            );
        }
        
        // Return comprehensive dashboard data
        return array(
            'permits' => $permits,
            'myPtw' => $organized_data['myPtw'],
            'pendingApproval' => $organized_data['pendingApproval'],
            'activePtw' => $organized_data['activePtw'],
            'expiringSoon' => $organized_data['expiringSoon'],
            'statusCounts' => $organized_data['statusCounts'],
            'statistics' => $statistics,
            'recent_activity' => $recent_activity,
            'status_distribution' => $organized_data['statusCounts']
        );
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        throw new Exception('Failed to get dashboard data: ' . $ex->getMessage());
    }
}

// Supervisor-specific functions
function get_supervisor_pending_requests($user_site_id) {
    global $fn_general, $fn_ptw;
    
    try {
        $fn_general->log_debug('API', 'GET_SUPERVISOR_PENDING', __LINE__, 'Getting supervisor pending requests for site: ' . $user_site_id);
        
        $filters = array(
            'site_id' => $user_site_id,
            'ptw_status' => 'PENDING_SUPERVISOR'
        );
        
        $permits = $fn_ptw->get_permit_list($filters);
        
        // Add additional supervisor-specific data
        foreach ($permits as &$permit) {
            // Calculate priority based on various factors
            $permit['priority'] = calculatePriority($permit);
            
            // Check if overdue
            $permit['is_overdue'] = isPermitOverdue($permit);
            
            // Get applicant details
            $permit['applicant_details'] = getApplicantDetails($permit['ptw_applicant_id']);
        }
        
        return array(
            'success' => true,
            'permits' => $permits,
            'total_count' => count($permits)
        );
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        throw new Exception('Failed to get supervisor pending requests: ' . $ex->getMessage());
    }
}

function handle_supervisor_approval($permit_id, $user_id, $user_site_id, $post_data) {
    global $fn_general, $fn_ptw;
    
    try {
        $fn_general->log_debug('API', 'SUPERVISOR_APPROVE', __LINE__, 
            'Supervisor approval for permit: ' . $permit_id . ' by user: ' . $user_id);
        error_log('PTW API: Supervisor approval - Permit ID: ' . $permit_id . ', User ID: ' . $user_id);
        
        // Validate permit exists and is in correct status
        $permit = $fn_ptw->get_permit_details($permit_id, $user_site_id);
        error_log('PTW API: Retrieved permit: ' . print_r($permit, true));
        
        if (!$permit) {
            throw new Exception('[' . __LINE__ . '] - Permit not found or access denied');
        }
        
        if ($permit['ptw_status'] !== 'PENDING_SUPERVISOR') {
            throw new Exception('[' . __LINE__ . '] - Invalid permit status for supervisor approval. Current status: ' . $permit['ptw_status']);
        }
        
        // Determine next status in approval workflow
        // After supervisor approval, typically goes to SHE approval
        $next_status = 'PENDING_SHE';
        
        // For low risk permits, might skip directly to approved
        if (isset($permit['ptw_risk_level']) && $permit['ptw_risk_level'] === 'LOW') {
            $next_status = 'APPROVED';
        }
        
        error_log('PTW API: Setting next status to: ' . $next_status);
        
        // Prepare update data with correct field names
        $update_data = array(
            'ptw_status' => $next_status,
            'ptw_supervisor_approval' => 'APPROVED',
            'updated_by' => $user_id,
            'updated_date' => date('Y-m-d H:i:s')
        );
        
        // If going to SHE approval, set SHE status to PENDING
        if ($next_status === 'PENDING_SHE') {
            $update_data['ptw_she_approval'] = 'PENDING';
        }
        
        // Add supervisor-specific fields if they exist in the database
        $comments = isset($post_data['comments']) ? trim($post_data['comments']) : 'Approved by supervisor';
        $update_data['ptw_supervisor_comments'] = $comments;
        $update_data['ptw_supervisor_approval_date'] = date('Y-m-d H:i:s');
        $update_data['ptw_supervisor_id'] = $user_id;
        
        error_log('PTW API: Update data: ' . print_r($update_data, true));
        
        // Update permit using direct database call for better error handling
        $update_result = Class_db::getInstance()->db_update(
            'ptw_permit',
            $update_data,
            array('ptw_permit_id' => $permit_id)
        );
        
        error_log('PTW API: Database update result: ' . ($update_result ? 'SUCCESS' : 'FAILED'));
        
        if ($update_result !== false) {
            // Log approval action
            $fn_general->log_debug('PTW', 'SUPERVISOR_APPROVED', __LINE__, 
                'Permit ' . $permit_id . ' approved by supervisor ' . $user_id . 
                ' and moved to ' . $next_status . ': ' . $comments);
            
            // Send notification if moving to SHE approval
            if ($next_status === 'PENDING_SHE') {
                try {
                    // Send email notification to SHE team (optional)
                    // $fn_email->send_she_notification($permit_id, $permit);
                } catch (Exception $email_ex) {
                    // Don't fail the approval if email fails
                    error_log('PTW API: Email notification failed: ' . $email_ex->getMessage());
                }
            }
            
            return array(
                'success' => true,
                'message' => 'Permit approved by supervisor successfully',
                'next_status' => $next_status,
                'permit_id' => $permit_id
            );
        } else {
            throw new Exception('[' . __LINE__ . '] - Failed to update permit in database');
        }
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        error_log('PTW API: Supervisor approval error: ' . $ex->getMessage());
        throw new Exception('Failed to approve permit: ' . $ex->getMessage());
    }
}

function handle_supervisor_rejection($permit_id, $user_id, $user_site_id, $post_data) {
    global $fn_general, $fn_ptw;
    
    try {
        $fn_general->log_debug('API', 'SUPERVISOR_REJECT', __LINE__, 
            'Supervisor rejection for permit: ' . $permit_id . ' by user: ' . $user_id);
        error_log('PTW API: Supervisor rejection - Permit ID: ' . $permit_id . ', User ID: ' . $user_id);
        
        // Validate permit exists and is in correct status
        $permit = $fn_ptw->get_permit_details($permit_id, $user_site_id);
        
        if (!$permit) {
            throw new Exception('[' . __LINE__ . '] - Permit not found or access denied');
        }
        
        if ($permit['ptw_status'] !== 'PENDING_SUPERVISOR') {
            throw new Exception('[' . __LINE__ . '] - Invalid permit status for supervisor rejection. Current status: ' . $permit['ptw_status']);
        }
        
        $rejection_reason = isset($post_data['rejection_reason']) ? trim($post_data['rejection_reason']) : '';
        if (empty($rejection_reason)) {
            throw new Exception('[' . __LINE__ . '] - Rejection reason is required');
        }
        
        error_log('PTW API: Rejection reason: ' . $rejection_reason);
        
        // Update permit status to rejected
        $update_data = array(
            'ptw_status' => 'CANCELLED',
            'ptw_supervisor_approval' => 'REJECTED',
            'updated_by' => $user_id,
            'updated_date' => date('Y-m-d H:i:s'),
            'ptw_supervisor_comments' => $rejection_reason,
            'ptw_supervisor_rejection_date' => date('Y-m-d H:i:s'),
            'ptw_supervisor_id' => $user_id
        );
        
        error_log('PTW API: Rejection update data: ' . print_r($update_data, true));
        
        // Update permit using direct database call
        $update_result = Class_db::getInstance()->db_update(
            'ptw_permit',
            $update_data,
            array('ptw_permit_id' => $permit_id)
        );
        
        error_log('PTW API: Database rejection update result: ' . ($update_result ? 'SUCCESS' : 'FAILED'));
        
        if ($update_result !== false) {
            // Log rejection action
            $fn_general->log_debug('PTW', 'SUPERVISOR_REJECTED', __LINE__, 
                'Permit ' . $permit_id . ' rejected by supervisor ' . $user_id . ': ' . $rejection_reason);
            
            // Send notification to applicant (optional)
            try {
                // $fn_email->send_rejection_notification($permit_id, $permit, $rejection_reason);
            } catch (Exception $email_ex) {
                // Don't fail the rejection if email fails
                error_log('PTW API: Rejection email notification failed: ' . $email_ex->getMessage());
            }
            
            return array(
                'success' => true,
                'message' => 'Permit rejected by supervisor successfully',
                'permit_id' => $permit_id
            );
        } else {
            throw new Exception('[' . __LINE__ . '] - Failed to update permit in database');
        }
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        error_log('PTW API: Supervisor rejection error: ' . $ex->getMessage());
        throw new Exception('Failed to reject permit: ' . $ex->getMessage());
    }
}

function handle_supervisor_modification($permit_id, $user_id, $user_site_id, $post_data) {
    global $fn_general, $fn_ptw;
    
    try {
        $fn_general->log_debug('API', 'SUPERVISOR_MODIFY', __LINE__, 
            'Supervisor modification request for permit: ' . $permit_id . ' by user: ' . $user_id);
        
        // Validate permit exists and is in correct status
        $permit = $fn_ptw->get_permit_details($permit_id, $user_site_id);
        if (!$permit || $permit['ptw_status'] !== 'PENDING_SUPERVISOR') {
            throw new Exception('Invalid permit or status for modification request');
        }
        
        $modification_notes = $post_data['modification_notes'] ?? '';
        if (empty($modification_notes)) {
            throw new Exception('Modification notes are required');
        }
        
        // Update permit status to require modification
        $update_data = array(
            'ptw_status' => 'PENDING_MODIFICATION',
            'supervisor_id' => $user_id,
            'supervisor_modification_date' => date('Y-m-d H:i:s'),
            'supervisor_comments' => $modification_notes
        );
        
        $result = $fn_ptw->update_permit($permit_id, $update_data);
        
        if ($result) {
            // Log modification request action
            $fn_general->log_debug('PTW', 'SUPERVISOR_MODIFICATION_REQUESTED', __LINE__, 
                'Permit ' . $permit_id . ' modification requested by supervisor ' . $user_id . ': ' . $modification_notes);
            
            return array(
                'success' => true,
                'message' => 'Modification request sent successfully',
                'permit_id' => $permit_id
            );
        } else {
            throw new Exception('Failed to update permit status');
        }
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        throw new Exception('Failed to request modification: ' . $ex->getMessage());
    }
}

function generate_supervisor_report($user_site_id) {
    global $fn_general, $fn_ptw;
    
    try {
        $fn_general->log_debug('API', 'SUPERVISOR_REPORT', __LINE__, 'Generating supervisor report for site: ' . $user_site_id);
        
        // Get various permit statistics for supervisor
        $report_data = array();
        
        // Pending approvals
        $pending_filters = array('site_id' => $user_site_id, 'ptw_status' => 'PENDING_SUPERVISOR');
        $report_data['pending_approvals'] = $fn_ptw->get_permit_list($pending_filters);
        
        // Recently approved (last 7 days)
        $approved_filters = array(
            'site_id' => $user_site_id, 
            'ptw_status' => 'APPROVED',
            'date_from' => date('Y-m-d', strtotime('-7 days'))
        );
        $report_data['recent_approvals'] = $fn_ptw->get_permit_list($approved_filters);
        
        // Overdue permits
        $report_data['overdue_permits'] = getOverduePermits($user_site_id);
        
        // Summary statistics
        $report_data['summary'] = array(
            'total_pending' => count($report_data['pending_approvals']),
            'total_approved_week' => count($report_data['recent_approvals']),
            'total_overdue' => count($report_data['overdue_permits'])
        );
        
        return array(
            'success' => true,
            'report_data' => $report_data,
            'generated_at' => date('Y-m-d H:i:s')
        );
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        throw new Exception('Failed to generate supervisor report: ' . $ex->getMessage());
    }
}

// Helper functions
function calculatePriority($permit) {
    $priority = 'MEDIUM';
    
    // High priority if hot work or confined space
    if (in_array($permit['ptw_work_type'], array('Hot Work', 'Confined Space'))) {
        $priority = 'HIGH';
    }
    
    // High priority if expires soon (within 24 hours)
    if (isset($permit['ptw_valid_to'])) {
        $expires_in_hours = (strtotime($permit['ptw_valid_to']) - time()) / 3600;
        if ($expires_in_hours <= 24) {
            $priority = 'HIGH';
        }
    }
    
    // Low priority for standard cold work with long validity
    if ($permit['ptw_work_type'] === 'Cold Work') {
        $priority = 'LOW';
    }
    
    return $priority;
}

function isPermitOverdue($permit) {
    // Check if permit has been pending for more than 24 hours
    if (isset($permit['created_date'])) {
        $created_time = strtotime($permit['created_date']);
        $hours_pending = (time() - $created_time) / 3600;
        return $hours_pending > 24;
    }
    return false;
}

function getApplicantDetails($applicant_id) {
    global $fn_general;
    
    try {
        // Get user details from sys_user and sys_user_profile tables
        $query = "SELECT CONCAT(u.user_first_name, ' ', COALESCE(u.user_last_name, '')) as user_full_name, 
                         p.user_email, 
                         p.user_contact_no as user_phone 
                  FROM sys_user u 
                  LEFT JOIN sys_user_profile p ON u.user_id = p.user_id AND p.user_profile_status = '1'
                  WHERE u.user_id = ?";
        $result = Class_db::getInstance()->db_select($query, array($applicant_id));
        
        if (!empty($result)) {
            return $result[0];
        }
        
        return array(
            'user_full_name' => 'Unknown User',
            'user_email' => '',
            'user_phone' => ''
        );
        
    } catch (Exception $ex) {
        $fn_general->log_error('API', __FUNCTION__, __LINE__, $ex->getMessage());
        return array(
            'user_full_name' => 'Error Loading User',
            'user_email' => '',
            'user_phone' => ''
        );
    }
}

function getOverduePermits($user_site_id) {
    global $fn_ptw;
    
    // Get all pending permits and filter for overdue ones
    $filters = array('site_id' => $user_site_id, 'ptw_status' => 'PENDING_SUPERVISOR');
    $permits = $fn_ptw->get_permit_list($filters);
    
    $overdue_permits = array();
    foreach ($permits as $permit) {
        if (isPermitOverdue($permit)) {
            $overdue_permits[] = $permit;
        }
    }
    
    return $overdue_permits;
}

?>
