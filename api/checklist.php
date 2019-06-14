<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_checklist.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_checklist = new Class_checklist();
$api_name = 'api_checklist';
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

    if ('GET' === $request_method) {
        $checklistId = filter_input(INPUT_GET, 'checklistId');
        $type = filter_input(INPUT_GET, 'type');
        $assetTypeId = filter_input(INPUT_GET, 'assetTypeId');
        if (!is_null($type)) {
            if ($type === 'checklist_by_type') {
                $result = $fn_checklist->get_checklist_by_type();
            }
        } else if (!is_null($checklistId)) {
            $result = $fn_checklist->get_checklist($checklistId);
        } else {
            $result = $fn_checklist->get_checklist_list($assetTypeId);
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $assetTypeId = filter_input(INPUT_POST, 'assetTypeId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_checklist->create_checklist($assetTypeId);
        $fn_general->save_audit('63', $jwt_data->userId, 'Checklist Id = ' . $result);

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $checklistId = filter_input(INPUT_GET, 'checklistId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'save') {
            $fn_checklist->save_checklist($checklistId, $put_vars);
            $fn_general->save_audit('64', $jwt_data->userId, 'Checklist Id = ' . $checklistId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_SAVE;
        }
        else if ($action === 'submit') {
            $fn_checklist->submit_checklist($checklistId, $put_vars, $jwt_data->userId);
            $fn_general->save_audit('65', $jwt_data->userId, 'Checklist Id = ' . $checklistId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_REGISTER;
        }
        else if ($action === 'update') {
            $fn_checklist->update_checklist($checklistId, $put_vars);
            $fn_general->save_audit('66', $jwt_data->userId, 'Checklist Id = ' . $checklistId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_EDIT;
        }
        else if ($action === 'deactivate') {
            $fn_checklist->deactivate_checklist($checklistId);
            $fn_general->save_audit('67', $jwt_data->userId, 'Checklist Id = ' . $checklistId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $fn_checklist->activate_checklist($checklistId);
            $fn_general->save_audit('68', $jwt_data->userId, 'Checklist Id = ' . $checklistId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $checklistId = filter_input(INPUT_GET, 'checklistId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $fn_checklist->delete_checklist($checklistId);
        $fn_general->save_audit('69', $jwt_data->userId, 'Checklist Id = ' . $checklistId);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CHECKLIST_DELETE;
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
