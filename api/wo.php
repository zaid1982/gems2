<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo.php';

$api_name = 'api_asset';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo = new Class_wo();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);

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
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');
        if (!is_null($type)) {
            if ($type === 'dashboard_list') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_task_dashboard_list($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_site_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_site_status($clientId, $year, $month);
            }
            else if ($type === 'total_by_site_type') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_site_type($clientId, $year, $month);
            }
            else if ($type === 'total_by_type') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_type($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_status($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_group') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_group($clientId, $siteId, $year, $month);
            }
            else if ($type === 'report_wo_summary') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_report_wo_summary($clientId, $year, $month);
            }
            else if ($type === 'report_wo_pending_list') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_task_dashboard_list('', $siteId, $year, $month, true);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
            }
        } else if (!is_null($woTaskId)) {
            $result = $fn_wo->get_asset($woTaskId);
        } else {
            //$result = $fn_wo->get_wo_task_list();
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