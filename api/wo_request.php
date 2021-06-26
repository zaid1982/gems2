<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo_request.php';
require_once 'function/f_wo.php';
require_once 'function/f_task.php';
require_once 'function/f_email.php';

$api_name = 'api_wo_request';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo_request = new Class_wo_request();
$fn_wo = new Class_wo();
$fn_task = new Class_task();
$fn_email = new Class_email();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo_request->__set('fn_general', $fn_general);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_email->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'wo_request') {
            break;
        }
        array_shift($urlArr);
    }

    if (isset($urlArr[1]) && $urlArr[1] === 'external') {
        array_shift($urlArr);
    } else {
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
    }

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'pending_task') {
                $searchText = isset ($urlArr[2]) ? $urlArr[2] : '';
                $result = $fn_wo_request->getPendingTask($userId, $searchText);
            } else if ($urlArr[1] === 'request_details') {
                $result = $fn_wo_request->getWoRequestDetails($urlArr[2]);
            } else {
                $result = $fn_wo_request->getWoRequest($urlArr[1]);
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $param = $_POST;
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        $woTaskId = $urlArr[1];
        $fn_wo_request->checkRequestTask('submit_request', $woTaskId, $userId);
        // ********** submit request ********** \\
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $woTaskRequestNo = $fn_wo_request->createRequestNo($userId);
        $taskId = $fn_task->create_new_task('4', $userId, '8', '1', $woTaskRequestNo);
        $transactionId = $fn_task->getTransactionId($taskId);
        $fn_task->submit_task($taskId, $userId);
        $fn_wo_request->submitRequest($woTaskId, $transactionId, $woTaskRequestNo);
        Class_db::getInstance()->db_commit();

        // ********** email & notification ********** \\
        $fn_wo->__set('woTaskId', $woTaskId);
        $wo = $fn_wo->get_wo_task();
        $nextUsers = $fn_task->get_checkpoints_users('17', '42', $woTaskId);
        Class_db::getInstance()->db_beginTransaction();
        foreach ($nextUsers as $nextUser) {
            $fn_email->setup_email($nextUser, 16, array('task_no' => $wo['woTaskNo']));
            $fn_email->setup_mobile_notification($nextUser, 17, array('task_no' => $wo['woTaskNo']));
        }
        Class_db::getInstance()->db_commit();

        // ********** audit trail ********** \\
        $fn_general->save_audit('171', $userId, 'Work Order No. = '.$wo['woTaskNo'].', Part Request No. = '.$woTaskRequestNo);
        $form_data['errmsg'] = 'Request Parts successfully submitted for approval';

        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);


        $form_data['result'] = $result;
        $form_data['success'] = true;
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

