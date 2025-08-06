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
    $fn_ptw->__set('fn_task', $fn_task);
    $fn_ptw->__set('fn_email', $fn_email);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    
    // Get user site information for site filtering
    $user = Class_db::getInstance()->db_select('sys_user', array('user_id'=>$jwt_data->userId));
    if (count($user) == 0) {
        throw new Exception('[' . __LINE__ . '] - User not found');
    }
    $user_site_id = $user[0]['site_id'];
    
    switch($request_method) {
        case 'GET':
            $result = get_ptw_data($jwt_data->userId, $user_site_id);
            break;
        case 'POST':
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
    
    $is_transaction = true;
    Class_db::getInstance()->db_beginTransaction();
    
    try {
        // Validate required fields
        $required_fields = array(
            'ptwPermitDescription', 'ptwWorkArea', 'ptwWorkType', 
            'ptwValidFrom', 'ptwApplicantName'
        );
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                throw new Exception('[' . __LINE__ . '] - Required field missing: ' . $field);
            }
        }
        
        // Use the passed site_id parameter
        $site_id = $user_site_id;
        
        // Generate permit number
        $site_code = Class_db::getInstance()->db_select_col('sys_site', array('site_id'=>$site_id), 'site_code', null, 1);
        $running_no = Class_db::getInstance()->db_select_col('sys_site', array('site_id'=>$site_id), 'site_running_no', null, 1);
        $running_no = intval($running_no) + 1;
        $permit_number = $site_code . 'PTW' . str_pad($running_no, 4, '0', STR_PAD_LEFT);
        
        // Prepare permit data
        $permit_data = array(
            'ptw_permit_number' => $permit_number,
            'ptw_permit_description' => trim($_POST['ptwPermitDescription']),
            'ptw_work_area' => trim($_POST['ptwWorkArea']),
            'ptw_work_type' => $_POST['ptwWorkType'],
            'ptw_risk_level' => isset($_POST['ptwRiskLevel']) ? $_POST['ptwRiskLevel'] : 'LOW',
            'ptw_valid_from' => $_POST['ptwValidFrom'],
            'ptw_valid_to' => isset($_POST['ptwValidTo']) ? $_POST['ptwValidTo'] : $_POST['ptwValidFrom'],
            'ptw_contractor_company' => isset($_POST['ptwContractorCompany']) ? trim($_POST['ptwContractorCompany']) : '',
            'ptw_remarks' => isset($_POST['ptwRemarks']) ? trim($_POST['ptwRemarks']) : '',
            'ptw_applicant_name' => trim($_POST['ptwApplicantName']),
            'ptw_applicant_contact' => isset($_POST['ptwApplicantContact']) ? trim($_POST['ptwApplicantContact']) : '',
            'ptw_applicant_company_dept' => isset($_POST['ptwApplicantCompanyDept']) ? trim($_POST['ptwApplicantCompanyDept']) : '',
            'ptw_work_duration' => isset($_POST['ptwWorkDuration']) ? trim($_POST['ptwWorkDuration']) : '',
            'ptw_checklist_cold_work' => isset($_POST['ptwChecklistColdWork']) ? $_POST['ptwChecklistColdWork'] : null,
            'ptw_checklist_hot_work' => isset($_POST['ptwChecklistHotWork']) ? $_POST['ptwChecklistHotWork'] : null,
            'ptw_checklist_confined_space' => isset($_POST['ptwChecklistConfinedSpace']) ? $_POST['ptwChecklistConfinedSpace'] : null,
            'ptw_hazard_checklist' => isset($_POST['ptwHazardChecklist']) ? $_POST['ptwHazardChecklist'] : null,
            'ptw_declaration_checklist' => isset($_POST['ptwDeclarationChecklist']) ? $_POST['ptwDeclarationChecklist'] : null,
            'site_id' => $user_site_id,
            'created_by' => $user_id,
            'created_date' => date('Y-m-d H:i:s')
        );
        
        // Create permit
        $permit_id = $fn_ptw->create_permit($permit_data);
        
        // Update site running number
        Class_db::getInstance()->db_update('sys_site', array('site_running_no'=>strval($running_no)), array('site_id'=>$site_id));
        
        // Add workers if provided
        if (isset($_POST['workers']) && is_array($_POST['workers'])) {
            foreach ($_POST['workers'] as $worker) {
                if (!empty(trim($worker['workerName']))) {
                    $worker_data = array(
                        'ptw_permit_id' => $permit_id,
                        'worker_name' => trim($worker['workerName']),
                        'worker_ic_number' => isset($worker['workerIcNumber']) ? trim($worker['workerIcNumber']) : '',
                        'worker_phone_number' => isset($worker['workerPhoneNumber']) ? trim($worker['workerPhoneNumber']) : '',
                        'worker_company' => isset($worker['workerCompany']) ? trim($worker['workerCompany']) : '',
                        'worker_designation' => isset($worker['workerDesignation']) ? trim($worker['workerDesignation']) : '',
                        'worker_ptw_number' => isset($worker['workerPtwNumber']) ? trim($worker['workerPtwNumber']) : '',
                        'created_by' => $user_id,
                        'created_date' => date('Y-m-d H:i:s')
                    );
                    $fn_ptw->add_worker($worker_data);
                }
            }
        }
        
        // Submit permit for approval if requested
        if (isset($_POST['submit_for_approval']) && $_POST['submit_for_approval'] == 'true') {
            $fn_ptw->submit_for_approval($permit_id, $user_id);
        }
        
        Class_db::getInstance()->db_commit();
        $is_transaction = false;
        
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

?>
