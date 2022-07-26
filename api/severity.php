<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_severity.php';

$api_name = 'api_severity';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_severity = new Class_severity();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_severity->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $severityId = filter_input(INPUT_GET, 'severityId');
        if (!is_null($severityId)) {
            $result = $fn_severity->get_severity($severityId);
        } else {
            $result = $fn_severity->get_severity_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $severityName = filter_input(INPUT_POST, 'severityName');
        $severityStatus = filter_input(INPUT_POST, 'severityStatus');

        $params = array(
            'severityName'=>$severityName,
            'severityStatus'=>$severityStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_severity->add_severity($params);
        $fn_general->updateVersion(22);
        $fn_general->save_audit('137', $jwt_data->userId, 'Severity = ' . $severityName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = 'Severity successfully added';
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $severityId = filter_input(INPUT_GET, 'severityId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_severity->update_severity($severityId, $put_vars);
            $fn_general->updateVersion(22);
            $fn_general->save_audit('138', $jwt_data->userId, 'Severity = ' . $put_vars['severityName']);
            $form_data['errmsg'] = 'Severity successfully saved';
        }
        else if ($action === 'deactivate') {
            $severityName = $fn_severity->deactivate_severity($severityId);
            $fn_general->updateVersion(22);
            $fn_general->save_audit('139', $jwt_data->userId, 'Severity = ' . $severityName);
            $form_data['errmsg'] = 'Severity successfully deactivated';
        }
        else if ($action === 'activate') {
            $severityName = $fn_severity->activate_severity($severityId);
            $fn_general->updateVersion(22);
            $fn_general->save_audit('140', $jwt_data->userId, 'Severity = ' . $severityName);
            $form_data['errmsg'] = 'Severity successfully activated';
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $severityId = filter_input(INPUT_GET, 'severityId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $severityName = $fn_severity->delete_severity($severityId);
        $fn_general->updateVersion(22);
        $fn_general->save_audit('141', $jwt_data->userId, 'Severity = ' . $severityName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = 'Severity successfully deleted';
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