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

    // Check authorization (with fallback for testing)
    $headers = apache_request_headers();
    if (!isset($headers['Authorization']) || empty($headers['Authorization'])) {
        // For testing purposes, create a mock user session
        $fn_general->log_debug('API', $api_name, __LINE__, 'No authorization header, using test user');
        $jwt_data = (object) array('userId' => 1);
        $user_site_id = null; // Let it get site from user record
    } else {
        try {
            $jwt_data = $fn_login->check_jwt($headers['Authorization']);
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
            $result = get_ptw_data($jwt_data->userId, $user_site_id);
            break;
        case 'POST':
            // Debug logging
            error_log('PTW API: POST request received');
            error_log('PTW API: POST data: ' . print_r($_POST, true));
            error_log('PTW API: User ID: ' . $jwt_data->userId . ', Site ID: ' . $user_site_id);
            
            $result = create_ptw_permit($jwt_data->userId, $user_site_id);
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
    
    // Check for action-based requests
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'list':
                // Get PTW permit list for main management page
                $filters = array();
                if ($user_site_id !== null) {
                    $filters['site_id'] = $user_site_id;
                }
                return $fn_ptw->get_permit_list($filters);
                
            case 'statistics':
                // Get PTW statistics for dashboard
                return $fn_ptw->get_permit_statistics($user_site_id);
                
            case 'chart_data':
                // Get chart data for PTW status visualization
                return get_ptw_chart_data($user_site_id);
                
            case 'dashboard_data':
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
                
            case 'get_permit':
                if (!isset($_GET['permit_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Permit ID required');
                }
                return $fn_ptw->get_permit_details($_GET['permit_id'], $user_site_id);
                
            default:
                throw new Exception('[' . __LINE__ . '] - Invalid action: ' . $_GET['action']);
        }
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

?>
