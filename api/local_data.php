<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_reference.php';
require_once 'function/f_user.php';
require_once 'function/f_email.php';
require_once 'function/f_asset_brand.php';

$api_name = 'api_local_data';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_reference = new Class_reference();
$fn_user = new Class_user();
$fn_assetBrand = new Class_assetBrand();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_user->__set('constant', $constant);
    $fn_user->__set('fn_general', $fn_general);
    $fn_reference->__set('constant', $constant);
    $fn_reference->__set('fn_general', $fn_general);
    $fn_assetBrand->__set('constant', $constant);
    $fn_assetBrand->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    
    if ('GET' === $request_method) { 
        if (!isset($headers['Name']) || empty($headers['Name'])) {
            throw new Exception('[' . __LINE__ . '] - Parameter Name empty');
        }
        $name = $headers['Name'];    
            
        $result = array();
        switch ($name) {
            case 'gems_status':
                $result = $fn_reference->get_status();
                break;
            case 'gems_state':
                $result = $fn_reference->get_state();
                break;
            case 'gems_city':
                $result = $fn_reference->get_city();
                break;
            case 'gems_role':
                $result = $fn_reference->get_role();
                break;
            case 'gems_user':
                $result = $fn_user->get_users();
                break;
            case 'gems_flow':
                $result = $fn_reference->get_flow_list();
                break;
            case 'gems_checkpoint':
                $result = $fn_reference->get_checkpoint_list();
                break;
            case 'gems_designation':
                $result = $fn_reference->get_designation();
                break;
            case 'gems_group':
                $result = $fn_reference->get_group_list();
                break;
            case 'gems_assetBrandGroup':
                $result = $fn_assetBrand->get_assetBrand_groupList();
                break;
            case 'gems_frequency':
                $result = $fn_reference->get_frequency_list();
                break;
            case 'gems_auditModule':
                $result = $fn_reference->get_audit_module_list();
                break;
            case 'gems_auditAction':
                $result = $fn_reference->get_audit_action_list();
                break;
            default:
                throw new Exception('[' . __LINE__ . '] - Parameter name invalid ('.$name.')');
        }
                
        $form_data['result'] = $result;
        $form_data['success'] = true; 
        //$fn_general->log_debug('API', $api_name, __LINE__, 'Result = '.print_r($result, true));
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close();
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);
