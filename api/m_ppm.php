<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
//require_once 'function/f_task.php';
require_once 'function/f_ppm.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
//$fn_task = new Class_task();
$fn_ppm = new Class_ppm();
$api_name = 'api_m_ppm';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

try {
    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    //$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if (!isset($headers['Deviceid'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
    }
    $fn_login->check_device_id($jwt_data->userId, $headers['Deviceid']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        if ($type === 'pending_task') {
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId);
        } else if ($type === 'calendar_list') {
            $date = filter_input(INPUT_GET, 'date');
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId, $date);
        } else if ($type === 'calendar_dot') {
            $month = filter_input(INPUT_GET, 'month');
            $year = filter_input(INPUT_GET, 'year');
            $result = $fn_ppm->get_calendar_task_dot($jwt_data->userId, $month, $year);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter type empty');
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