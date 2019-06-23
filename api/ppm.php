<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_ppm.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/checklist.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_ppm = new Class_ppm();
$fn_pdf_checklist = new Class_pdf_checklist();
$api_name = 'api_ppm';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fn_pdf_checklist->__set('fn_general', $fn_general);
$fn_pdf_checklist->__set('checklistId', '1');
$fn_pdf_checklist->create_pdf();
/*try {
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
        $ppmId = filter_input(INPUT_GET, 'ppmId');
        $type = filter_input(INPUT_GET, 'type');
        if (!is_null($type)) {
            if ($type === 'checklist_by_type') {
                $result = $fn_ppm->get_ppm_from_asset_list();
            }
        } else if (!is_null($ppmId)) {
            //$result = $fn_asset->get_asset($ppmId);
        } else {
            //$result = $fn_asset->get_asset_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'assign_ppm_single') {
            $assetId = filter_input(INPUT_POST, 'assetId');
            $checklistId = filter_input(INPUT_POST, 'checklistId');
            $ppmDateCycle = filter_input(INPUT_POST, 'ppmDateCycle');

            $result = $fn_ppm->assign_ppm_single($assetId, $checklistId, $ppmDateCycle, $jwt_data->userId);
            $fn_general->save_audit('80', $jwt_data->userId, 'PPM Task No = ' . $result['ppmTaskNo']);
            $form_data['errmsg'] = $constant::SUC_PPM_SAVE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
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

echo json_encode($form_data);*/