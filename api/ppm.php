<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_ppm.php';
require_once 'function/f_email.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/ppm.php';

$api_name = 'api_ppm';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_email = new Class_email();
$fn_ppm = new Class_ppm();
$fn_pdf_ppm = new Class_pdf_ppm();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_ppm->__set('constant', $constant);
    $fn_ppm->__set('fn_general', $fn_general);
    $fn_ppm->__set('fn_task', $fn_task);
    $fn_ppm->__set('fn_email', $fn_email);
    $fn_pdf_ppm->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
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
                $contractId = filter_input(INPUT_GET, 'contractId');
                $result = $fn_ppm->get_ppm_from_asset_list($contractId);
            } else if ($type === 'scheduled_ppm') {
                $result = $fn_ppm->get_ppm_scheduled_list($ppmId);
            } else if ($type === 'total_ppm_task') {
                $month = filter_input(INPUT_GET, 'month');
                $year = filter_input(INPUT_GET, 'year');
                $clientId = filter_input(INPUT_GET, 'clientId');
                $contractId = filter_input(INPUT_GET, 'contractId');
                $result = $fn_ppm->get_total_ppm_task($month, $year, $clientId, $contractId);
            } else if ($type === 'total_ppm_late') {
                $month = filter_input(INPUT_GET, 'month');
                $year = filter_input(INPUT_GET, 'year');
                $clientId = filter_input(INPUT_GET, 'clientId');
                $contractId = filter_input(INPUT_GET, 'contractId');
                $result = $fn_ppm->get_total_ppm_late($month, $year, $clientId, $contractId);
            } else if ($type === 'perc_ppm_done') {
                $month = filter_input(INPUT_GET, 'month');
                $year = filter_input(INPUT_GET, 'year');
                $clientId = filter_input(INPUT_GET, 'clientId');
                $contractId = filter_input(INPUT_GET, 'contractId');
                $result = $fn_ppm->get_perc_ppm_done($month, $year, $clientId, $contractId);
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
            $ppmDateStart = filter_input(INPUT_POST, 'ppmDateStart');
            $ppmGroupId = filter_input(INPUT_POST, 'ppmGroupId');

            $result = $fn_ppm->assign_ppm_single($assetId, $checklistId, $ppmDateStart, $jwt_data->userId, $ppmGroupId);
            $fn_general->save_audit('80', $jwt_data->userId, 'PPM Task No = ' . $result['ppmTaskNo']);
            $form_data['errmsg'] = $constant::SUC_PPM_SAVE;
        }
        else if ($action === 'generate_pdf') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fn_pdf_ppm->__set('ppmTaskId', $ppmTaskId);
            $result = $fn_pdf_ppm->create_pdf();
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

echo json_encode($form_data);