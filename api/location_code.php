<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_location_code.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_locationCode = new Class_locationCode();
$api_name = 'api_location_code';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fn_locationCode->__set('fn_general', $fn_general);

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

    if ('GET' === $request_method) {
        $locationCodeId = filter_input(INPUT_GET, 'locationCodeId');
        if (!is_null($locationCodeId)) {
            $result = $fn_locationCode->get_locationCode($locationCodeId);
        } else {
            $contractId = filter_input(INPUT_GET, 'contractId');
            $result = $fn_locationCode->get_locationCode_list($contractId);
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $locationCodeName = filter_input(INPUT_POST, 'locationCodeName');
        $contractId = filter_input(INPUT_POST, 'contractId');
        $locationCodeStatus = filter_input(INPUT_POST, 'locationCodeStatus');

        $params = array(
            'locationCodeName'=>$locationCodeName,
            'contractId'=>$contractId,
            'locationCodeStatus'=>$locationCodeStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_locationCode->add_locationCode($params);
        $fn_general->updateVersion(16);
        $fn_general->save_audit('92', $jwt_data->userId, 'Location Code = ' . $locationCodeName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_LOCATION_CODE_ADD;
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