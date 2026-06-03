<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once __DIR__ . '/function/f_ptw.php';
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
$current_user_roles = array();

if (!function_exists('gems_get_request_headers')) {
    function gems_get_request_headers (): array {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !isset($headers['Authorization'])) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        return $headers;
    }
}

// Centralized PTW role mappings (aliased from constants for easy use in this file)
$PTW_ROLE_ADMIN = Class_constant::ROLE_ADMIN;
$PTW_ROLE_SUPERVISOR = Class_constant::PTW_ROLE_SUPERVISOR;
$PTW_ROLE_SHE = Class_constant::PTW_ROLE_SHE;
$PTW_ROLE_FM = Class_constant::PTW_ROLE_FM;

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

    $fn_ptw->__set('fn_task', $fn_task);
    $fn_ptw->__set('fn_email', $fn_email);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    // Check authorization (with fallback for testing and public forms)
    $headers = gems_get_request_headers();
    $is_public_form = isset($_POST['public_user']) && $_POST['public_user'] === 'Public User';
    
    if ($is_public_form) {
        // Handle public form submission
        $fn_general->log_debug('API', $api_name, __LINE__, 'Public form submission detected');
        
        // Get public user from database
        try {
            $public_users = Class_db::getInstance()->db_select('sys_user', array('user_first_name' => 'Public User'));
            if (count($public_users) > 0) {
                $jwt_data = (object) array('userId' => $public_users[0]['user_id']);
                // For public forms, require explicit site_id input; do not assume user's site
                $provided_site_id = isset($_POST['site_id']) ? trim($_POST['site_id']) : '';
                if ($provided_site_id === '' || !preg_match('/^\d+$/', $provided_site_id)) {
                    $fn_general->log_error('API', $api_name, __LINE__, 'Public submission missing or invalid site_id');
                    throw new Exception('Public PTW submission requires a valid site_id');
                }
                $user_site_id = $provided_site_id;
                $fn_general->log_debug('API', $api_name, __LINE__, 'Using public user ID: ' . $jwt_data->userId . ', site_id (from request): ' . $user_site_id);
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
    // Fetch user roles for RBAC
    try {
        // vw_roles supports filter with params in last arg; ensure limit arg present as int
        $current_user_roles = Class_db::getInstance()->db_select('vw_roles', array(), null, null, 0, array('user_id' => strval($jwt_data->userId)));
    } catch (Exception $e) {
        $fn_general->log_error('API', $api_name, __LINE__, 'Failed to load roles: ' . $e->getMessage());
        $current_user_roles = array();
    }

    // Optional site override for local testing: ?site=XX
    if (isset($_GET['site']) && preg_match('/^\d+$/', $_GET['site'])) {
        $user_site_id = $_GET['site'];
        $fn_general->log_debug('API', $api_name, __LINE__, 'Overriding site_id from query param: ' . $user_site_id);
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
                    case 'update_permit':
                        // Flexible update: allow JWT (roles SUPERVISOR/SHE/FM/ADMIN) or public token t
                        $result = update_ptw_permit_flexible($jwt_data->userId, $user_site_id, $headers['Authorization'] ?? '');
                        break;
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

// ---- RBAC helpers ----
function ptw_roles_contains($roles, $expected) {
    if (!is_array($roles)) { return false; }
    $expected_lc = strtolower($expected);
    foreach ($roles as $r) {
        foreach ($r as $k => $v) {
            if (is_string($v) && strpos(strtolower($v), $expected_lc) !== false) {
                return true;
            }
        }
    }
    return false;
}

function ptw_has_any($roles, $names = array()) {
    foreach ($names as $n) {
        if (ptw_roles_contains($roles, $n)) { return true; }
    }
    return false;
}

function ptw_enforce($roles, $allowed_groups = array()) {
    // Admin bypass
    global $PTW_ROLE_ADMIN;
    if (ptw_has_any($roles, $PTW_ROLE_ADMIN)) { return; }
    if (empty($allowed_groups)) { return; }
    if (!ptw_has_any($roles, $allowed_groups)) {
        header('HTTP/1.1 403 Forbidden');
        throw new Exception('Insufficient permissions for this operation');
    }
}

function get_ptw_data($user_id, $user_site_id) {
    global $fn_general, $fn_ptw;
    global $current_user_roles;
    global $PTW_ROLE_SHE, $PTW_ROLE_FM, $PTW_ROLE_SUPERVISOR;
    
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
            
            case 'get_my_roles':
                // Expose current user's PTW role flags for UI guards
                global $PTW_ROLE_SHE, $PTW_ROLE_FM, $PTW_ROLE_SUPERVISOR, $PTW_ROLE_ADMIN;
                $resp = array(
                    'isAdmin' => ptw_has_any($current_user_roles, $PTW_ROLE_ADMIN),
                    'hasSupervisor' => ptw_has_any($current_user_roles, $PTW_ROLE_SUPERVISOR) || ptw_has_any($current_user_roles, $PTW_ROLE_ADMIN),
                    'hasSHE' => ptw_has_any($current_user_roles, $PTW_ROLE_SHE) || ptw_has_any($current_user_roles, $PTW_ROLE_ADMIN),
                    'hasFM' => ptw_has_any($current_user_roles, $PTW_ROLE_FM) || ptw_has_any($current_user_roles, $PTW_ROLE_ADMIN),
                    'raw' => $current_user_roles
                );
                return $resp;
                
            case 'details':
                if (!isset($_GET['permit_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Permit ID required');
                }
                return $fn_ptw->get_permit_details($_GET['permit_id'], $user_site_id);
                
            case 'get_she_pending_permits':
            case 'get_permits_for_she_approval':
                ptw_enforce($current_user_roles, $PTW_ROLE_SHE);
                return $fn_ptw->get_permits_for_she_approval($user_site_id);
                
            case 'get_she_recent_actions':
                ptw_enforce($current_user_roles, $PTW_ROLE_SHE);
                return $fn_ptw->get_she_recent_actions($user_id, $user_site_id);
                
            case 'get_she_summary_stats':
            case 'get_she_summary_statistics':
                ptw_enforce($current_user_roles, $PTW_ROLE_SHE);
                return $fn_ptw->get_she_summary_statistics($user_id, $user_site_id);
                
            case 'get_fm_pending_permits':
            case 'get_permits_for_fm_approval':
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                return $fn_ptw->get_permits_for_fm_approval($user_id, $user_site_id);
                
            case 'get_fm_extension_requests':
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                // ACTIVE permits that have extension request markers
                return $fn_ptw->get_extension_requests($user_site_id);

            case 'get_fm_recent_actions':
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                return $fn_ptw->get_fm_recent_actions($user_id, $user_site_id);

            case 'get_fm_cancellation_requests':
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                return $fn_ptw->get_cancellation_requests($user_site_id);

            case 'get_fm_suspension_requests':
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                return $fn_ptw->get_suspension_requests($user_site_id);

            case 'get_fm_suspended_permits':
                // Keep for completeness though UI shouldn't expose this; still restrict to FM
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                return $fn_ptw->get_suspended_permits($user_site_id);
                
            case 'get_fm_summary_stats':
            case 'get_fm_summary_statistics':
                ptw_enforce($current_user_roles, $PTW_ROLE_FM);
                return $fn_ptw->get_fm_summary_statistics($user_id, $user_site_id);
                
            case 'get_permit':
                if (!isset($_GET['permit_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Permit ID required');
                }
                return $fn_ptw->get_permit_details($_GET['permit_id'], $user_site_id);
                
            case 'get_supervisor_pending_requests':
                // Get PTW requests pending supervisor approval
                ptw_enforce($current_user_roles, $PTW_ROLE_SUPERVISOR);
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
    global $fn_general, $fn_ptw, $is_transaction, $api_name;
    
    error_log('PTW API: create_ptw_permit function called');
    error_log('PTW API: User ID: ' . $user_id . ', Site ID: ' . $user_site_id);
    
    $is_transaction = true;
    Class_db::getInstance()->db_beginTransaction();
    
    try {
        // Validate required fields
        $required_fields = array(
            'work_description', 'work_area', 'work_type', 
            'valid_from', 'applicant_name'
        );
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                throw new Exception('[' . __LINE__ . '] - Required field missing: ' . $field);
            }
        }
        
        // Use the passed site_id parameter
        $site_id = $user_site_id;
        
    // Do not assign final PTW number at creation; it will be assigned at FM approval.
    $permit_number = null;
    $request_number = null;
        
        // Handle multiple work types - store all selected types in additional data
        $selected_work_types = isset($_POST['work_types_selected']) ? $_POST['work_types_selected'] : '';
        $primary_work_type = isset($_POST['work_type']) ? $_POST['work_type'] : '';
        
        // Map primary work type from form to database enum (for backward compatibility)
        $work_type_mapping = [
            'HOT_WORK' => 'HOT_WORK',
            'COLD_WORK' => 'COLD_WORK', 
            'CONFINED_SPACE' => 'CONFINED_SPACE',
            'ELECTRICAL' => 'COLD_WORK', // Map to closest equivalent
            'MECHANICAL' => 'COLD_WORK', // Map to closest equivalent
            'HEIGHT_WORK' => 'COLD_WORK', // Map to closest equivalent
            'EXCAVATION' => 'COLD_WORK', // Map to closest equivalent
            'CHEMICAL' => 'HOT_WORK', // Map to closest equivalent (needs special handling)
            'LIFTING' => 'COLD_WORK', // Map to closest equivalent
            'OTHER' => 'COLD_WORK', // Default to cold work
            'Hot Work' => 'HOT_WORK',
            'Cold Work' => 'COLD_WORK',
            'Confined Space' => 'CONFINED_SPACE',
            // Add lowercase versions for form compatibility
            'hot_work' => 'HOT_WORK',
            'cold_work' => 'COLD_WORK',
            'confined_space' => 'CONFINED_SPACE'
        ];
        
        // Use primary work type for database enum field
        $work_type = isset($work_type_mapping[$primary_work_type]) 
            ? $work_type_mapping[$primary_work_type] 
            : 'COLD_WORK'; // Default fallback
            
        error_log("PTW API: Primary work type: $primary_work_type -> $work_type");
        error_log("PTW API: All selected work types: $selected_work_types");
        
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

        // Use original remarks as-is since we now store additional data in proper database fields
        $combined_remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

        // Prepare comprehensive permit data with all enhanced fields
        // Work description: form submits as work_description (legacy code expected description)
        $rawWorkDesc = '';
        if (isset($_POST['work_description'])) { $rawWorkDesc = $_POST['work_description']; }
        else if (isset($_POST['description'])) { $rawWorkDesc = $_POST['description']; }
        $permit_data = array(
            // 'ptw_permit_number' is now assigned at Supervisor approval stage; request number on initial submit
            'ptw_permit_description' => trim($rawWorkDesc),
            'ptw_work_area' => trim($_POST['work_area']),
            'ptw_work_type' => $work_type,
            'ptw_risk_level' => isset($_POST['risk_level']) ? $_POST['risk_level'] : 'LOW',
            'ptw_valid_from' => $valid_from,
            'ptw_valid_to' => $valid_to,
            'ptw_contractor_company' => isset($_POST['contractor_company']) ? trim($_POST['contractor_company']) : '',
            'ptw_remarks' => $combined_remarks,
            'ptw_applicant_name' => trim($_POST['applicant_name']),
            'ptw_applicant_contact' => isset($_POST['applicant_contact']) ? trim($_POST['applicant_contact']) : '',
            'ptw_applicant_company_dept' => isset($_POST['applicant_department']) ? trim($_POST['applicant_department']) : '',
            'ptw_hazards' => isset($_POST['hazards']) ? trim($_POST['hazards']) : '',
            'ptw_control_measures' => isset($_POST['control_measures']) ? trim($_POST['control_measures']) : '',
            'ptw_status' => 'DRAFT',
            'site_id' => $user_site_id,
            'created_by' => $user_id,
            'created_date' => date('Y-m-d H:i:s')
        );

        // Add enhanced applicant/contractor fields
        if (isset($_POST['contractor_supervisor']) && !empty(trim($_POST['contractor_supervisor']))) {
            $permit_data['ptw_contractor_supervisor'] = trim($_POST['contractor_supervisor']);
        }
        if (isset($_POST['contractor_name']) && !empty(trim($_POST['contractor_name']))) {
            $permit_data['ptw_contractor_name'] = trim($_POST['contractor_name']);
        }
        if (isset($_POST['contractor_designation']) && !empty(trim($_POST['contractor_designation']))) {
            $permit_data['ptw_contractor_designation'] = trim($_POST['contractor_designation']);
        }
        if (isset($_POST['contractor_date']) && !empty(trim($_POST['contractor_date']))) {
            $permit_data['ptw_contractor_date'] = trim($_POST['contractor_date']);
        }
        if (isset($_POST['staff_nric']) && !empty(trim($_POST['staff_nric']))) {
            $permit_data['ptw_staff_nric'] = trim($_POST['staff_nric']);
        }
        if (isset($_POST['supervisor_contact']) && !empty(trim($_POST['supervisor_contact']))) {
            $permit_data['ptw_supervisor_contact'] = trim($_POST['supervisor_contact']);
        }
        if (isset($_POST['identification_no']) && !empty(trim($_POST['identification_no']))) {
            $permit_data['ptw_identification_no'] = trim($_POST['identification_no']);
        }
        if (isset($_POST['level']) && !empty(trim($_POST['level']))) {
            $permit_data['ptw_level'] = trim($_POST['level']);
        }
        if (isset($_POST['work_duration']) && !empty(trim($_POST['work_duration']))) {
            $permit_data['ptw_work_duration'] = trim($_POST['work_duration']);
        }

        // Add multiple work types support
        if (isset($_POST['work_types_selected']) && !empty($_POST['work_types_selected'])) {
            $permit_data['ptw_work_types'] = $_POST['work_types_selected'];
        }

        // Add checklist data
        if (isset($_POST['checklist_data']) && !empty($_POST['checklist_data'])) {
            $permit_data['ptw_hazard_checklist'] = $_POST['checklist_data'];
        }
        if (isset($_POST['checklist_hot_work']) && !empty($_POST['checklist_hot_work'])) {
            $permit_data['ptw_checklist_hot_work'] = $_POST['checklist_hot_work'];
        }
        if (isset($_POST['checklist_cold_work']) && !empty($_POST['checklist_cold_work'])) {
            $permit_data['ptw_checklist_cold_work'] = $_POST['checklist_cold_work'];
        }
        if (isset($_POST['checklist_confined_space']) && !empty($_POST['checklist_confined_space'])) {
            $permit_data['ptw_checklist_confined_space'] = $_POST['checklist_confined_space'];
        }
        if (isset($_POST['declaration_checklist']) && !empty($_POST['declaration_checklist'])) {
            $permit_data['ptw_declaration_checklist'] = $_POST['declaration_checklist'];
        }
        if (isset($_POST['supporting_docs_checklist']) && !empty($_POST['supporting_docs_checklist'])) {
            $permit_data['ptw_supporting_docs_checklist'] = $_POST['supporting_docs_checklist'];
        }
        if (isset($_POST['certificate_numbers']) && !empty($_POST['certificate_numbers'])) {
            $permit_data['ptw_certificate_numbers'] = $_POST['certificate_numbers'];
        }
        if (isset($_POST['hazardous_activities']) && !empty($_POST['hazardous_activities'])) {
            $permit_data['ptw_hazardous_activities'] = $_POST['hazardous_activities'];
        }

        // Store complete form data for comprehensive processing
        if (isset($_POST['complete_form_data']) && !empty($_POST['complete_form_data'])) {
            // Validate JSON before storing
            $complete_data = json_decode($_POST['complete_form_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $permit_data['ptw_complete_form_data'] = $_POST['complete_form_data'];
            } else {
                $fn_general->log_debug('API', $api_name, __LINE__, 'Invalid JSON in complete_form_data: ' . json_last_error_msg());
            }
        }
        
        // Log the comprehensive permit data being inserted
        $fn_general->log_debug('API', $api_name, __LINE__, 'Creating permit with enhanced data: ' . json_encode($permit_data, JSON_PRETTY_PRINT));
        
        // Create permit with better error handling
        try {
            $permit_id = $fn_ptw->create_permit($permit_data);
            
            if (!$permit_id) {
                throw new Exception("Failed to create permit - no ID returned");
            }
            
            error_log('PTW API: Permit created successfully with ID: ' . $permit_id);
            
        } catch (Exception $e) {
            error_log('PTW API: Error creating permit: ' . $e->getMessage());
            throw $e;
        }
        
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
            if (!empty($target_status)) {
                Class_db::getInstance()->db_update('ptw_permit', 
                    array('ptw_status' => $target_status),
                    array('ptw_permit_id' => strval($permit_id))
                );
            }
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
                                                    (isset($worker['phone']) ? trim($worker['phone']) : 
                                                    (isset($worker['contact_number']) ? trim($worker['contact_number']) : '')),
                            'worker_company' => isset($worker['workerCompany']) ? trim($worker['workerCompany']) : 
                                               (isset($worker['company']) ? trim($worker['company']) : ''),
                            'worker_designation' => isset($worker['workerDesignation']) ? trim($worker['workerDesignation']) : 
                                                   (isset($worker['designation']) ? trim($worker['designation']) : ''),
                            'worker_role' => isset($worker['role']) ? trim($worker['role']) : '',
                            'worker_identification' => isset($worker['identification']) ? trim($worker['identification']) : '',
                            'is_certified' => isset($worker['is_certified']) ? (bool)$worker['is_certified'] : false,
                            'worker_ptw_number' => isset($worker['workerPtwNumber']) ? trim($worker['workerPtwNumber']) : '',
                            'created_by' => $user_id,
                            'created_date' => date('Y-m-d H:i:s')
                        );
                        Class_db::getInstance()->db_insert('ptw_worker', $worker_data);
                    }
                }
            }
        }
        
        // Commit initial creation (permit + workers) before cross-connection operations
        Class_db::getInstance()->db_commit();
        $is_transaction = false;
        error_log('PTW API: Transaction committed successfully (creation phase)');

        // If contractor signature is included (data URL), persist it as a PNG under the PTW permit folder
        try {
            if (isset($_POST['contractor_signature']) && is_string($_POST['contractor_signature']) && $_POST['contractor_signature'] !== '') {
                $dataUrl = $_POST['contractor_signature'];
                // Expected format: data:image/png;base64,<base64>
                if (strpos($dataUrl, 'data:image') === 0 && strpos($dataUrl, 'base64,') !== false) {
                    $parts = explode('base64,', $dataUrl, 2);
                    $meta = $parts[0];
                    $b64 = $parts[1];
                    $mime = 'image/png';
                    $ext = 'png';
                    if (preg_match('/data:(.*?);base64/', $meta, $m)) {
                        $mime = $m[1];
                        // Derive extension from mime when possible
                        if ($mime === 'image/jpeg' || $mime === 'image/jpg') { $ext = 'jpg'; }
                        elseif ($mime === 'image/png') { $ext = 'png'; }
                        elseif ($mime === 'image/svg+xml') { $ext = 'svg'; }
                    }

                    // Ensure destination folder exists: upload/ptw/<site>/<permit>
                    $baseDir = __DIR__ . '/../upload/ptw';
                    if (!is_dir($baseDir)) { @mkdir($baseDir, 0775, true); }
                    $siteDir = $baseDir . '/' . $user_site_id;
                    if (!is_dir($siteDir)) { @mkdir($siteDir, 0775, true); }
                    $permitDir = $siteDir . '/' . $permit_id;
                    if (!is_dir($permitDir)) { @mkdir($permitDir, 0775, true); }
                    @chmod($permitDir, 0777); // best-effort on dev

                    $filename = 'contractor_signature_' . date('Ymd_His') . '.' . $ext;
                    $fullPath = $permitDir . '/' . $filename;
                    $bytes = base64_decode($b64);
                    if ($bytes !== false && strlen($bytes) > 0) {
                        $ok = @file_put_contents($fullPath, $bytes);
                        if ($ok !== false) {
                            // Record in ptw_document for traceability
                            $relPath = 'upload/ptw/' . $user_site_id . '/' . $permit_id . '/' . $filename;
                            $docRow = array(
                                'ptw_permit_id' => $permit_id,
                                'document_type' => 'CONTRACTOR_SIGNATURE',
                                'document_name' => 'Contractor Signature',
                                'document_path' => $relPath,
                                'document_size' => strlen($bytes),
                                'document_mime_type' => $mime,
                                'uploaded_by' => $user_id
                            );
                            Class_db::getInstance()->db_insert('ptw_document', $docRow);
                        } else {
                            error_log('PTW API: Failed to write contractor signature to ' . $fullPath);
                        }
                    } else {
                        error_log('PTW API: Invalid base64 for contractor signature');
                    }
                }
            }
        } catch (Exception $sigEx) {
            error_log('PTW API: Contractor signature persist exception: ' . $sigEx->getMessage());
        }

        // Submit permit for approval if requested (post-commit to avoid FK issues)
        if (isset($_POST['submit_for_approval']) && $_POST['submit_for_approval'] == 'true') {
            try {
                $fn_ptw->submit_for_approval($permit_id, $user_id);
                // Read back request number assigned during submission
                try {
                    $row = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
                    if (!empty($row) && isset($row['ptw_request_no'])) {
                        $request_number = $row['ptw_request_no'];
                    }
                } catch (Exception $e) {
                    error_log('PTW API: failed to read back request number: ' . $e->getMessage());
                }
            } catch (Exception $e) {
                // Surface the error back to client
                throw $e;
            }
        }

        // Fetch or create a public token for view link
        $public_token = null;
        try { $public_token = $fn_ptw->get_or_create_public_token($permit_id); } catch (Exception $e) { /* non-fatal */ }

        return array(
            'ptw_permit_id' => $permit_id,
            'ptw_permit_number' => $permit_number,
            'ptw_request_number' => $request_number,
            'public_token' => $public_token,
            'status' => isset($_POST['submit_for_approval']) && $_POST['submit_for_approval'] == 'true' ? 'submitted' : 'created'
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
    // Parse PUT body into $_PUT for compatibility
    global $_PUT;
    if (!isset($_PUT) || !is_array($_PUT)) {
        $raw = file_get_contents('php://input');
        $parsed = [];
        // Try to parse as query string first
        if (!empty($raw)) {
            parse_str($raw, $parsed);
            if (empty($parsed)) {
                // Fallback: try JSON
                $json = json_decode($raw, true);
                if (is_array($json)) { $parsed = $json; }
            }
        }
        $_PUT = is_array($parsed) ? $parsed : [];
    }
    
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
        'ptw_checklist_confined_space', 'ptw_hazard_checklist', 'ptw_declaration_checklist',
        'ptw_supporting_docs_checklist', 'ptw_certificate_numbers'
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

/**
 * Flexible update that allows edits before FM final approval by roles (Supervisor/SHE/FM/Admin)
 * or by possession of a valid public token t (for contractor edits via QR link),
 * without altering current workflow stage. Replaces workers if provided and persists signature.
 */
function update_ptw_permit_flexible($user_id, $user_site_id, $authHeader = '') {
    global $fn_ptw, $fn_general, $current_user_roles;
    global $PTW_ROLE_ADMIN, $PTW_ROLE_SUPERVISOR, $PTW_ROLE_SHE, $PTW_ROLE_FM;

    // Expect POST with either permit_id or id; optional token t
    $permit_id = isset($_POST['permit_id']) ? intval($_POST['permit_id']) : 0;
    if ($permit_id <= 0 && isset($_POST['id']) && is_numeric($_POST['id'])) {
        $permit_id = intval($_POST['id']);
    }
    if ($permit_id <= 0) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit ID required');
    }

    // Load current permit from DB using site guard
    $permit = $fn_ptw->get_permit_details($permit_id, $user_site_id);
    if (!$permit) {
        throw new Exception('[' . __LINE__ . '] - PTW Permit not found or access denied');
    }

    // Determine if edit is allowed by status (business rule): allowed until FM final approval
    $status = $permit['ptw_status'] ?? 'DRAFT';
    $fmApproval = $permit['ptw_fm_approval'] ?? 'PENDING';
    $isFinalized = (strtoupper($status) === 'ACTIVE' || strtoupper($status) === 'APPROVED' || strtoupper($fmApproval) === 'APPROVED');
    if ($isFinalized) {
        throw new Exception('[' . __LINE__ . '] - Edits are not allowed after FM final approval');
    }

    // AuthZ: allow if user has any PTW role (Supervisor/SHE/FM/Admin) OR valid public token possession
    $isRoleAllowed = ptw_has_any($current_user_roles, array_merge($PTW_ROLE_ADMIN, $PTW_ROLE_SUPERVISOR, $PTW_ROLE_SHE, $PTW_ROLE_FM));

    $token = isset($_POST['t']) ? trim($_POST['t']) : (isset($_GET['t']) ? trim($_GET['t']) : '');
    $tokenOk = false;
    if (!$isRoleAllowed) {
        // Validate token against permit record
        try {
            $row = Class_db::getInstance()->db_select_single('ptw_permit', array('ptw_permit_id' => strval($permit_id)));
            if ($row && !empty($row['public_token']) && !empty($token) && hash_equals($row['public_token'], $token)) {
                // Check enabled/expiry/revocation with legacy tolerance
                $enabledRaw = $row['public_link_enabled'] ?? '1';
                $enabledOk = in_array(strtolower(strval($enabledRaw)), ['1','true','y','yes'], true);
                $revokedAt = $row['public_token_revoked_at'] ?? null;
                if ($revokedAt === '0000-00-00 00:00:00') { $revokedAt = null; }
                $expiresAt = $row['public_token_expires_at'] ?? null;
                if ($expiresAt === '0000-00-00 00:00:00') { $expiresAt = null; }
                $expired = false; if (!empty($expiresAt)) { $ts = strtotime($expiresAt); $expired = ($ts !== false && $ts < time()); }
                $tokenOk = $enabledOk && empty($revokedAt) && !$expired;
            }
        } catch (Exception $e) { $tokenOk = false; }
    }
    if (!$isRoleAllowed && !$tokenOk) {
        header('HTTP/1.1 403 Forbidden');
        throw new Exception('Forbidden');
    }

    // Build update map from incoming POST fields (support same names as create)
    // Normalise description key so downstream mapping works (support both work_description & description)
    if (isset($_POST['work_description']) && !isset($_POST['description'])) {
        $_POST['description'] = $_POST['work_description'];
    }
    $map = [
        'ptw_permit_description' => 'description',
        'ptw_work_area' => 'work_area',
        'ptw_work_type' => 'work_type',
        'ptw_risk_level' => 'risk_level',
        'ptw_valid_from' => 'valid_from',
        'ptw_valid_to' => 'valid_to',
        'ptw_contractor_company' => 'contractor_company',
        'ptw_remarks' => 'remarks',
        'ptw_applicant_name' => 'applicant_name',
        'ptw_applicant_contact' => 'applicant_contact',
        'ptw_applicant_company_dept' => 'applicant_department',
        'ptw_contractor_supervisor' => 'contractor_supervisor',
        'ptw_contractor_name' => 'contractor_name',
        'ptw_contractor_designation' => 'contractor_designation',
        'ptw_contractor_date' => 'contractor_date',
        'ptw_staff_nric' => 'staff_nric',
        'ptw_supervisor_contact' => 'supervisor_contact',
        'ptw_identification_no' => 'identification_no',
        'ptw_level' => 'level',
        'ptw_work_duration' => 'work_duration',
        'ptw_work_types' => 'work_types_selected',
        'ptw_hazard_checklist' => 'checklist_data',
        'ptw_checklist_hot_work' => 'checklist_hot_work',
        'ptw_checklist_cold_work' => 'checklist_cold_work',
        'ptw_checklist_confined_space' => 'checklist_confined_space',
        'ptw_declaration_checklist' => 'declaration_checklist',
        'ptw_supporting_docs_checklist' => 'supporting_docs_checklist',
        'ptw_certificate_numbers' => 'certificate_numbers',
        'ptw_hazardous_activities' => 'hazardous_activities'
    ];

    $update = [];
    foreach ($map as $dbField => $postKey) {
        if (isset($_POST[$postKey])) {
            $val = $_POST[$postKey];
            // Normalize date fields
            if (in_array($dbField, ['ptw_valid_from','ptw_valid_to'])) {
                if (strlen($val) === 10) { $val .= ($dbField === 'ptw_valid_from') ? ' 08:00:00' : ' 17:00:00'; }
            }
            $update[$dbField] = $val;
        }
    }

    $fn_general->log_debug('API', 'update_ptw_permit_flexible', __LINE__, 'Update fields count: ' . count($update) . ' for permit_id=' . $permit_id);

    // Never change ptw_status here; keep current workflow stage
    $rows_affected = 0;
    if (!empty($update)) {
        $update['updated_by'] = $user_id;
        $update['updated_date'] = date('Y-m-d H:i:s');
        $fn_general->log_debug('API', 'update_ptw_permit_flexible', __LINE__, 'Updating permit ' . $permit_id . ' with ' . count($update) . ' fields: ' . implode(', ', array_keys($update)));
        $rows_affected = $fn_ptw->update_permit($permit_id, $update);
    } else {
        $fn_general->log_debug('API', 'update_ptw_permit_flexible', __LINE__, 'WARNING: No update fields matched from POST data. POST keys: ' . implode(', ', array_keys($_POST)));
    }

    // Replace workers if provided
    if (isset($_POST['workers'])) {
        $workers_data = is_string($_POST['workers']) ? json_decode($_POST['workers'], true) : $_POST['workers'];
        if (is_array($workers_data)) {
            // Clear existing
            try { Class_db::getInstance()->db_delete('ptw_worker', array('ptw_permit_id' => strval($permit_id))); } catch (Exception $e) {}
            foreach ($workers_data as $worker) {
                $name = $worker['workerName'] ?? $worker['name'] ?? '';
                if (!empty(trim($name))) {
                    $row = array(
                        'ptw_permit_id' => $permit_id,
                        'worker_name' => trim($name),
                        'worker_ic_number' => $worker['workerIcNumber'] ?? ($worker['ic'] ?? ''),
                        'worker_phone_number' => $worker['workerPhoneNumber'] ?? ($worker['phone'] ?? ($worker['contact_number'] ?? '')),
                        'worker_company' => $worker['workerCompany'] ?? ($worker['company'] ?? ''),
                        'worker_designation' => $worker['workerDesignation'] ?? ($worker['designation'] ?? ''),
                        'worker_role' => $worker['role'] ?? '',
                        'worker_identification' => $worker['identification'] ?? '',
                        'is_certified' => isset($worker['is_certified']) ? (bool)$worker['is_certified'] : false,
                        'worker_ptw_number' => $worker['workerPtwNumber'] ?? '',
                        'created_by' => $user_id,
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    try { Class_db::getInstance()->db_insert('ptw_worker', $row); } catch (Exception $e) { /* continue */ }
                }
            }
        }
    }

    // Optional: contractor signature update (data URL)
    try {
        if (isset($_POST['contractor_signature']) && is_string($_POST['contractor_signature']) && $_POST['contractor_signature'] !== '') {
            $dataUrl = $_POST['contractor_signature'];
            if (strpos($dataUrl, 'data:image') === 0 && strpos($dataUrl, 'base64,') !== false) {
                $parts = explode('base64,', $dataUrl, 2);
                $meta = $parts[0];
                $b64 = $parts[1];
                $mime = 'image/png';
                $ext = 'png';
                if (preg_match('/data:(.*?);base64/', $meta, $m)) {
                    $mime = $m[1];
                    if ($mime === 'image/jpeg' || $mime === 'image/jpg') { $ext = 'jpg'; }
                    elseif ($mime === 'image/png') { $ext = 'png'; }
                }
                $baseDir = __DIR__ . '/../upload/ptw';
                if (!is_dir($baseDir)) { @mkdir($baseDir, 0775, true); }
                $siteDir = $baseDir . '/' . $user_site_id;
                if (!is_dir($siteDir)) { @mkdir($siteDir, 0775, true); }
                $permitDir = $siteDir . '/' . $permit_id;
                if (!is_dir($permitDir)) { @mkdir($permitDir, 0775, true); }
                @chmod($permitDir, 0777);
                $filename = 'contractor_signature_' . date('Ymd_His') . '.' . $ext;
                $fullPath = $permitDir . '/' . $filename;
                $bytes = base64_decode($b64);
                if ($bytes !== false && strlen($bytes) > 0) {
                    $ok = @file_put_contents($fullPath, $bytes);
                    if ($ok !== false) {
                        $relPath = 'upload/ptw/' . $user_site_id . '/' . $permit_id . '/' . $filename;
                        $docRow = array(
                            'ptw_permit_id' => $permit_id,
                            'document_type' => 'CONTRACTOR_SIGNATURE',
                            'document_name' => 'Contractor Signature',
                            'document_path' => $relPath,
                            'document_size' => strlen($bytes),
                            'document_mime_type' => $mime,
                            'uploaded_by' => $user_id
                        );
                        Class_db::getInstance()->db_insert('ptw_document', $docRow);
                    }
                }
            }
        }
    } catch (Exception $e) { /* non-fatal */ }

    return array('ptw_permit_id' => $permit_id, 'status' => 'updated', 'fields_updated' => count($update), 'rows_affected' => $rows_affected);
}

function delete_ptw_permit($user_id, $user_site_id) {
    global $fn_general, $fn_ptw;
    // Parse DELETE body into $_DELETE for compatibility
    global $_DELETE;
    if (!isset($_DELETE) || !is_array($_DELETE)) {
        $raw = file_get_contents('php://input');
        $parsed = [];
        if (!empty($raw)) {
            parse_str($raw, $parsed);
            if (empty($parsed)) {
                $json = json_decode($raw, true);
                if (is_array($json)) { $parsed = $json; }
            }
        }
        $_DELETE = is_array($_DELETE) ? $_DELETE : (is_array($parsed) ? $parsed : []);
    }
    
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
                'CANCELLED' => 0,
                'REJECTED' => 0
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
            
            $display_no = isset($permit['ptw_permit_number']) && $permit['ptw_permit_number'] !== ''
                ? $permit['ptw_permit_number']
                : (isset($permit['ptw_request_number']) ? $permit['ptw_request_number'] : '');
            $recent_activity[] = array(
                'title' => 'PTW ' . $display_no,
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
            // Add debug logging to see what fields are available
            $fn_general->log_error('API', __FUNCTION__, __LINE__, 
                "Permit fields available: " . implode(', ', array_keys($permit)));
            
            // Calculate priority based on various factors
            $permit['priority'] = calculatePriority($permit);
            
            // Check if overdue
            $permit['is_overdue'] = isPermitOverdue($permit);
            
            // Get applicant details from stored permit data
            $permit['applicant_details'] = array(
                'user_full_name' => isset($permit['ptw_applicant_name']) ? $permit['ptw_applicant_name'] : 'Unknown Applicant',
                'user_email' => '', // Not stored in PTW permits
                'user_phone' => isset($permit['ptw_applicant_contact']) ? $permit['ptw_applicant_contact'] : '',
                'company_dept' => isset($permit['ptw_applicant_company_dept']) ? $permit['ptw_applicant_company_dept'] : ''
            );
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
    // After supervisor approval, always go to SHE approval next
    $next_status = 'PENDING_SHE';
        
        error_log('PTW API: Setting next status to: ' . $next_status);
        
        // Assign permit number at supervisor approval stage if not already assigned (idempotent)
        $new_permit_number = '';
        try {
            if (empty($permit['ptw_permit_number'])) {
                $new_permit_number = $fn_ptw->assign_permit_number($permit_id, $user_site_id, $user_id);
                error_log('PTW API: Permit number assigned at supervisor approval: ' . $new_permit_number);
            } else {
                $new_permit_number = $permit['ptw_permit_number'];
            }
        } catch (Exception $numEx) {
            // Do not block approval; log error
            error_log('PTW API: Failed to assign permit number at supervisor stage: ' . $numEx->getMessage());
        }

        // Prepare update data with correct field names (do not overwrite permit number)
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
            
            // Matrix A2 Supervisor Approved -> notify SHE
            try { $fn_ptw->send_ptw_notification($permit_id, 'SUPERVISOR_APPROVED'); } catch (Exception $e) { /* non-fatal */ }
            
            // Compute display status using new mapper
            $display_status = 'New Request';
            try {
                $mapper = new Class_ptw();
                $display_status = $mapper->map_display_status(array_merge($permit, ['ptw_status' => $next_status, 'ptw_permit_number' => $new_permit_number]));
            } catch (Exception $e) {}
            return array(
                'success' => true,
                'message' => 'Permit approved by supervisor successfully',
                'next_status' => $next_status,
                'permit_id' => $permit_id,
                'ptw_permit_number' => $new_permit_number,
                'ptw_display_status' => $display_status
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
            'ptw_status' => 'REJECTED',
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
            
            // Matrix A3 Supervisor Rejected
            try { $fn_ptw->send_ptw_notification($permit_id, 'SUPERVISOR_REJECTED'); } catch (Exception $e) { /* non-fatal */ }
            
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
        // Validate applicant_id
        if (empty($applicant_id)) {
            $fn_general->log_error('API', __FUNCTION__, __LINE__, 'Empty applicant_id provided');
            return array(
                'user_full_name' => 'Invalid User ID',
                'user_email' => '',
                'user_phone' => ''
            );
        }
        
        // Try to get user basic info first using simple query
        $user_result = Class_db::getInstance()->db_select('sys_user', array('user_id' => $applicant_id));
        
        if (empty($user_result)) {
            $fn_general->log_error('API', __FUNCTION__, __LINE__, "No user found in sys_user for applicant_id: " . $applicant_id);
            return array(
                'user_full_name' => 'Unknown User',
                'user_email' => '',
                'user_phone' => ''
            );
        }
        
        $user = $user_result[0];
        
        // Build full name
        $full_name = trim($user['user_first_name'] . ' ' . (isset($user['user_last_name']) ? $user['user_last_name'] : ''));
        if (empty($full_name)) {
            $full_name = 'Unknown User';
        }
        
        // Try to get profile info
        $profile_result = Class_db::getInstance()->db_select('sys_user_profile', 
            array('user_id' => $applicant_id, 'user_profile_status' => '1'));
        
        $email = '';
        $phone = '';
        
        if (!empty($profile_result)) {
            $profile = $profile_result[0];
            $email = isset($profile['user_email']) ? $profile['user_email'] : '';
            $phone = isset($profile['user_contact_no']) ? $profile['user_contact_no'] : '';
        }
        
        $result = array(
            'user_full_name' => $full_name,
            'user_email' => $email,
            'user_phone' => $phone
        );
        
        $fn_general->log_error('API', __FUNCTION__, __LINE__, "Found user data: " . json_encode($result));
        return $result;
        
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
