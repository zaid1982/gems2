<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_contract_user.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_contractUser = new Class_contractUser();
$api_name = 'api_contract_user';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fn_contractUser->__set('fn_general', $fn_general);

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
        $contractUserId = filter_input(INPUT_GET, 'contractUserId');
        if (!is_null($contractUserId)) {
            $result = $fn_contractUser->get_contractUser($contractUserId);
        } else {
            $contractId = filter_input(INPUT_GET, 'contractId');
            $result = $fn_contractUser->get_contractUser_list($contractId);
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $contractId = filter_input(INPUT_POST, 'contractId');
        $locationCodeId = filter_input(INPUT_POST, 'locationCodeId');
        $userId = filter_input(INPUT_POST, 'userId');
        $assetGroupId = filter_input(INPUT_POST, 'assetGroupId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_contractUser->add_contractUser($contractId, $locationCodeId, $userId, $assetGroupId);
        $fn_general->save_audit('97', $jwt_data->userId, 'Contract User Id = ' . $result);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CONTRACT_USER_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $contractUserId = filter_input(INPUT_GET, 'contractUserId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $fn_contractUser->delete_contractUser($contractUserId);
        $fn_general->save_audit('99', $jwt_data->userId, 'Contract User Id = ' . $contractUserId);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CONTRACT_USER_DELETE;
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