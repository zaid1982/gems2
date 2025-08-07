<?php
// Test version of PTW API for SHE dashboard - bypasses authentication
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_ptw.php';

$api_name = 'api_ptw_test';
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_ptw = new Class_ptw();

try {
    $fn_general->__set('constant', $constant);
    $fn_ptw->__set('constant', $constant);
    $fn_ptw->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];

    if ($request_method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch($action) {
            case 'get_permits_for_she_approval':
                $result = $fn_ptw->get_permits_for_she_approval(1); // site_id = 1
                break;
                
            case 'get_she_recent_actions':
                $result = $fn_ptw->get_she_recent_actions(1, 1); // user_id = 1, site_id = 1
                break;
                
            case 'get_she_summary_statistics':
                $result = $fn_ptw->get_she_summary_statistics(1, 1); // user_id = 1, site_id = 1
                break;
                
            default:
                throw new Exception('Invalid action: ' . $action);
        }
    } else {
        throw new Exception('Only GET method allowed');
    }

    $form_data['success'] = true;
    $form_data['result'] = $result;

} catch (Exception $ex) {
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
    $form_data['error'] = $ex->getMessage();
    $form_data['errmsg'] = $ex->getMessage();
}

header('Content-Type: application/json');
echo json_encode($form_data);
?>
