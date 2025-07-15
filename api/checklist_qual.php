<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_checklist_qual.php';
require_once 'function/f_ppm.php';
require_once 'function/f_task.php';

$api_name = 'api_checklist_qual';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_checklistQual = new Class_checklistQual();
$fn_ppm = new Class_ppm(); 
$fn_task = new Class_task();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_checklistQual->__set('constant', $constant);
    $fn_checklistQual->__set('fn_general', $fn_general);

    // Inject Class_ppm's dependencies
    $fn_ppm->__set('constant', $constant);
    $fn_ppm->__set('fn_general', $fn_general);
    $fn_ppm->__set('fn_task', $fn_task);
    // $fn_ppm->__set('fn_email', $fn_email); // UNCOMMENT if Class_ppm uses fn_email

    // Inject fn_ppm into fn_checklistQual
    $fn_checklistQual->set_fn_ppm($fn_ppm);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $checklistQualId = filter_input(INPUT_GET, 'checklistQualId');
        $checklistId = filter_input(INPUT_GET, 'checklistId');
        if (!is_null($checklistQualId)) {
            $result = $fn_checklistQual->get_checklistQual($checklistQualId);
        } else if (!is_null($checklistId)) {
            $result = $fn_checklistQual->get_checklistQual_list($checklistId);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter GET invalid');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $checklistQualDesc = filter_input(INPUT_POST, 'checklistQualDesc');
        $checklistQualNumb = filter_input(INPUT_POST, 'checklistQualNumb');
        $frequencyId = filter_input(INPUT_POST, 'frequencyId');
        $checklistId = filter_input(INPUT_POST, 'checklistId');
        $checklistQualStatus = filter_input(INPUT_POST, 'checklistQualStatus');

        $params = array(
            'checklistQualDesc'=>$checklistQualDesc,
            'checklistQualNumb'=>$checklistQualNumb,
            'frequencyId'=>$frequencyId,
            'checklistId'=>$checklistId,
            'checklistQualStatus'=>$checklistQualStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_checklistQual->add_checklistQual($params);
        $fn_general->save_audit('70', $jwt_data->userId, 'Checklist Qual Id = ' . $result);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAL_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $checklistQualId = filter_input(INPUT_GET, 'checklistQualId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_checklistQual->update_checklistQual($checklistQualId, $put_vars);
            $fn_general->save_audit('71', $jwt_data->userId, 'Checklist Qual Id = ' . $checklistQualId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAL_EDIT;
        }
        else if ($action === 'deactivate') {
            $fn_checklistQual->deactivate_checklistQual($checklistQualId);
            $fn_general->save_audit('72', $jwt_data->userId, 'Checklist Qual Id = ' . $checklistQualId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAL_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $fn_checklistQual->activate_checklistQual($checklistQualId);
            $fn_general->save_audit('73', $jwt_data->userId, 'Checklist Qual Id = ' . $checklistQualId);
            $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAL_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $checklistQualId = filter_input(INPUT_GET, 'checklistQualId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $fn_checklistQual->delete_checklistQual($checklistQualId);
        $fn_general->save_audit('74', $jwt_data->userId, 'Checklist Qual Id = ' . $checklistQualId);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CHECKLIST_QUAL_DELETE;
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