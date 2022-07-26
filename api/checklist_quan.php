<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_checklist_quan.php';

$api_name = 'api_checklist_quan';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_checklistQuan = new Class_checklistQuan();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_checklistQuan->__set('constant', $constant);
    $fn_checklistQuan->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $checklistQuanId = filter_input(INPUT_GET, 'checklistQuanId');
        $checklistId = filter_input(INPUT_GET, 'checklistId');
        if (!is_null($checklistQuanId)) {
            $result = $fn_checklistQuan->get_checklistQuan($checklistQuanId);
        } else if (!is_null($checklistId)) {
            $result = $fn_checklistQuan->get_checklistQuan_list($checklistId);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter GET invalid');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $checklistQuanDesc = filter_input(INPUT_POST, 'checklistQuanDesc');
        $checklistQuanNumb = filter_input(INPUT_POST, 'checklistQuanNumb');
        $checklistQuanUnit = filter_input(INPUT_POST, 'checklistQuanUnit');
        $checklistQuanSetValues = filter_input(INPUT_POST, 'checklistQuanSetValues');
        $frequencyId = filter_input(INPUT_POST, 'frequencyId');
        $checklistId = filter_input(INPUT_POST, 'checklistId');
        $checklistQuanStatus = filter_input(INPUT_POST, 'checklistQuanStatus');

        $params = array(
            'checklistQuanDesc'=>$checklistQuanDesc,
            'checklistQuanNumb'=>$checklistQuanNumb,
            'checklistQuanUnit'=>$checklistQuanUnit,
            'checklistQuanSetValues'=>$checklistQuanSetValues,
            'frequencyId'=>$frequencyId,
            'checklistId'=>$checklistId,
            'checklistQuanStatus'=>$checklistQuanStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_checklistQuan->add_checklistQuan($params);
        $fn_general->save_audit('75', $jwt_data->userId, 'Checklist Quan Id = ' . $result);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAN_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $checklistQuanId = filter_input(INPUT_GET, 'checklistQuanId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_checklistQuan->update_checklistQuan($checklistQuanId, $put_vars);
            $fn_general->save_audit('76', $jwt_data->userId, 'Checklist Quan Id = ' . $checklistQuanId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAN_EDIT;
        }
        else if ($action === 'deactivate') {
            $fn_checklistQuan->deactivate_checklistQuan($checklistQuanId);
            $fn_general->save_audit('77', $jwt_data->userId, 'Checklist Quan Id = ' . $checklistQuanId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAN_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $fn_checklistQuan->activate_checklistQuan($checklistQuanId);
            $fn_general->save_audit('78', $jwt_data->userId, 'Checklist Quan Id = ' . $checklistQuanId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAN_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $checklistQuanId = filter_input(INPUT_GET, 'checklistQuanId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $fn_checklistQuan->delete_checklistQuan($checklistQuanId);
        $fn_general->save_audit('79', $jwt_data->userId, 'Checklist Quan Id = ' . $checklistQuanId);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAN_DELETE;
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