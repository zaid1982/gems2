<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_reference.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_reference = new Class_reference();
$api_name = 'api_designation';
$is_transaction = false;
$form_data = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';

try {
    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    //$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = ' . $request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $designationId = filter_input(INPUT_GET, 'designationId');
        if (!is_null($designationId)) {
            $form_data['result'] = $fn_reference->get_designation($designationId);
        } else {
            $result = $fn_reference->get_designation();
        }
        $form_data['success'] = true;
    } else if ('POST' === $request_method) {
        $designationDesc = filter_input(INPUT_POST, 'designationDesc');
        $clientId = filter_input(INPUT_POST, 'clientId');
        $designationStatus = filter_input(INPUT_POST, 'designationStatus');

        $params = array(
            'designationDesc' => $designationDesc,
            'designationStatus' => $designationStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_reference->add_designation($params);
        $fn_general->updateVersion(4);
        $fn_general->save_audit('12', $jwt_data->userId, 'Designation = ' . $designationDesc);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_DESIGNATION_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    } else if ('PUT' === $request_method) {
        $designationId = filter_input(INPUT_GET, 'designationId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_reference->update_designation($designationId, $put_vars);
            $fn_general->updateVersion(4);
            $fn_general->save_audit('13', $jwt_data->userId, 'Designation = ' . $put_vars['designationDesc']);
            $form_data['errmsg'] = $constant::SUC_DESIGNATION_EDIT;
        } else if ($action === 'deactivate') {
            $designationDesc = $fn_reference->deactivate_designation($designationId);
            $fn_general->updateVersion(4);
            $fn_general->save_audit('14', $jwt_data->userId, 'Designation = ' . $designationDesc);
            $form_data['errmsg'] = $constant::SUC_DESIGNATION_DEACTIVATE;
        } else if ($action === 'activate') {
            $designationDesc = $fn_reference->activate_designation($designationId);
            $fn_general->updateVersion(4);
            $fn_general->save_audit('15', $jwt_data->userId, 'Designation = ' . $designationDesc);
            $form_data['errmsg'] = $constant::SUC_DESIGNATION_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid (' . $action . ')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    } else if ('DELETE' === $request_method) {
        $designationId = filter_input(INPUT_GET, 'designationId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $designationName = $fn_reference->delete_designation($designationId);
        $fn_general->updateVersion(4);
        $fn_general->save_audit('16', $jwt_data->userId, 'Designation = ' . $designationName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_DESIGNATION_DELETE;
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