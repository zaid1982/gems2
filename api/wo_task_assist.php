<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo_task_assist.php';
require_once 'function/f_wo.php';

$api_name = 'api_wo_v2';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_woTaskAssist = new Class_wo_task_assist();
$fn_woTask = new Class_wo();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_woTaskAssist->__set('constant', $constant);
    $fn_woTaskAssist->__set('fn_general', $fn_general);
    $fn_woTask->__set('constant', $constant);
    $fn_woTask->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'wo_v2') {
            break;
        }
        array_shift($urlArr);
    }

    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
        if (!isset($headers['deviceid'])) {
            throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
        }
        $fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $userId = $jwt_data->userId;

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'dropdown_list') {
                $result = $fn_woTaskAssist->getWoAssistantDropdownM($urlArr[2]);
            } else if ($urlArr[1] === 'assistant_list') {
                $result = $fn_woTaskAssist->getWoAssistantListM($urlArr[2]);
            } else {
                throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $params = $_POST;
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $fn_woTaskAssist->addWoTaskAssist($params);
        $woTask = $fn_woTask->getWoTask($params['woTaskId']);
        $userFullNameArr = $fn_general->getUserFullName();
        $assistant = $params['assistant'];
        $fn_general->save_audit('184', $userId, 'Work Order no. = '.$woTask['woTaskNo'].', assistant = '.$userFullNameArr[$assistant]);
        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_WO_ADD_ASSISTANT;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {

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
