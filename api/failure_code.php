<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_failure_code.php';

$api_name = 'api_failure_code';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_failureCode = new Class_failureCode();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_failureCode->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $failureCodeId = filter_input(INPUT_GET, 'failureCodeId');
        if (!is_null($failureCodeId)) {
            $result = $fn_failureCode->get_failureCode($failureCodeId);
        } else {
            $result = $fn_failureCode->get_failureCode_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $failureCodeName = filter_input(INPUT_POST, 'failureCodeName');
        $failureCodeStatus = filter_input(INPUT_POST, 'failureCodeStatus');

        $params = array(
            'failureCodeName'=>$failureCodeName,
            'failureCodeStatus'=>$failureCodeStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_failureCode->add_failureCode($params);
        $fn_general->updateVersion(23);
        $fn_general->save_audit('142', $jwt_data->userId, 'Failure Code = ' . $failureCodeName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = 'Failure Code successfully added';
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $failureCodeId = filter_input(INPUT_GET, 'failureCodeId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_failureCode->update_failureCode($failureCodeId, $put_vars);
            $fn_general->updateVersion(23);
            $fn_general->save_audit('143', $jwt_data->userId, 'Failure Code = ' . $put_vars['failureCodeName']);
            $form_data['errmsg'] = 'Failure Code successfully saved';
        }
        else if ($action === 'deactivate') {
            $failureCodeName = $fn_failureCode->deactivate_failureCode($failureCodeId);
            $fn_general->updateVersion(23);
            $fn_general->save_audit('144', $jwt_data->userId, 'Failure Code = ' . $failureCodeName);
            $form_data['errmsg'] = 'Failure Code successfully deactivated';
        }
        else if ($action === 'activate') {
            $failureCodeName = $fn_failureCode->activate_failureCode($failureCodeId);
            $fn_general->updateVersion(23);
            $fn_general->save_audit('145', $jwt_data->userId, 'Failure Code = ' . $failureCodeName);
            $form_data['errmsg'] = 'Failure Code successfully activated';
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $failureCodeId = filter_input(INPUT_GET, 'failureCodeId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $failureCodeName = $fn_failureCode->delete_failureCode($failureCodeId);
        $fn_general->updateVersion(23);
        $fn_general->save_audit('146', $jwt_data->userId, 'Failure Code = ' . $failureCodeName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = 'Failure Code successfully deleted';
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