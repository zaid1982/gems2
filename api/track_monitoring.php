<?php
ini_set('memory_limit','4096M');

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';

$api_name = 'api_track_monitoring';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        if ($type === 'track_monitoring_list') {
            $year = filter_input(INPUT_GET, 'year');
            $siteCode = filter_input(INPUT_GET, 'siteCode');
            $flowId = filter_input(INPUT_GET, 'flowId');
            $result = $fn_task->get_track_monitoring_list($siteCode, $flowId, $year);
        } else if ($type === 'transaction_history') {
            $transactionId = filter_input(INPUT_GET, 'transactionId');
            $result = $fn_task->get_task_history('', $transactionId);
        } else if ($type === 'transaction_history_train_station') {
            $transactionId = filter_input(INPUT_GET, 'transactionId');
            $flag = filter_input(INPUT_GET, 'flag');
            $result = $fn_task->get_transaction_history_train_station($transactionId, $flag);
        }
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