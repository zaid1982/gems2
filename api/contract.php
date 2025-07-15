<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_contract.php';
// NEW: Include f_ppm.php and f_task.php
require_once 'function/f_ppm.php'; //
require_once 'function/f_task.php'; // Required for Class_task dependency in Class_ppm

$api_name = 'api_contract'; //
$is_transaction = false; //
$form_data = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => ''); //
$result = ''; //

$constant = new Class_constant(); //
$fn_general = new Class_general(); //
$fn_login = new Class_login(); //
$fn_contract = new Class_contract(); //
$fn_ppm = new Class_ppm(); //
// NEW: Instantiate Class_task (dependency for Class_ppm)
$fn_task = new Class_task(); //


try {
    // Inject constants and fn_general into fn_general (standard setup)
    $fn_general->__set('constant', $constant);

    // CRUCIAL FIX: Inject fn_general into fn_login
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general); // FIX: This line was missing or misplaced

    // Inject constant and fn_general into fn_contract
    $fn_contract->__set('constant', $constant);
    $fn_contract->__set('fn_general', $fn_general);
    // Inject fn_ppm into fn_contract (for generate_ppm_tasks_for_contract_extension call)
    $fn_contract->set_fn_ppm($fn_ppm);

    // CRUCIAL FIX: Inject all necessary dependencies into fn_ppm
    // These mirror the injections typically done in ppm.php for its fn_ppm instance.
    $fn_ppm->__set('constant', $constant); //
    $fn_ppm->__set('fn_general', $fn_general); // FIX: This line was missing or misplaced
    $fn_ppm->__set('fn_task', $fn_task); // FIX: This injection was missing
    // If Class_ppm uses fn_email, you'd also need:
    // $fn_ppm->__set('fn_email', $fn_email); // Requires $fn_email = new Class_email(); at top

    // CRUCIAL FIX: Inject dependencies into fn_task
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general); // FIX: This injection was missing

    Class_db::getInstance()->db_connect(); //
    $request_method = $_SERVER['REQUEST_METHOD']; //
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = ' . $request_method); //

    $headers = apache_request_headers(); //
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']); //

    if ('GET' === $request_method) { //
        $contractId = filter_input(INPUT_GET, 'contractId');
        if (!is_null($contractId)) {
            $result = $fn_contract->get_contract($contractId);
        } else {
            $result = $fn_contract->get_contract_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    } else if ('POST' === $request_method) { //
        $contractName = filter_input(INPUT_POST, 'contractName');
        $contractDesc = filter_input(INPUT_POST, 'contractDesc');
        $contractDateStart = filter_input(INPUT_POST, 'contractDateStart');
        $contractDateEnd = filter_input(INPUT_POST, 'contractDateEnd');
        $siteId = filter_input(INPUT_POST, 'siteId');
        $contractStatus = filter_input(INPUT_POST, 'contractStatus');

        $params = array(
            'contractName' => $contractName,
            'contractDesc' => $contractDesc,
            'contractDateStart' => $contractDateStart,
            'contractDateEnd' => $contractDateEnd,
            'siteId' => $siteId,
            'contractStatus' => $contractStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_contract->add_contract($params); //
        $fn_general->updateVersion(7); //
        $fn_general->save_audit('26', $jwt_data->userId, 'Site = ' . $contractName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CONTRACT_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    } else if ('PUT' === $request_method) { //
        $contractId = filter_input(INPUT_GET, 'contractId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_contract->update_contract($contractId, $put_vars); //
            $fn_general->updateVersion(7); //
            $fn_general->save_audit('27', $jwt_data->userId, 'Site = ' . $put_vars['contractName']);
            $form_data['errmsg'] = $constant::SUC_CONTRACT_EDIT;
        } else if ($action === 'deactivate') {
            $contractName = $fn_contract->deactivate_contract($contractId); //
            $fn_general->updateVersion(7); //
            $fn_general->save_audit('28', $jwt_data->userId, 'Site = ' . $contractName);
            $form_data['errmsg'] = $constant::SUC_CONTRACT_DEACTIVATE;
        } else if ($action === 'activate') {
            $contractName = $fn_contract->activate_contract($contractId); //
            $fn_general->updateVersion(7); //
            $fn_general->save_audit('29', $jwt_data->userId, 'Site = ' . $contractName);
            $form_data['errmsg'] = $constant::SUC_CONTRACT_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid (' . $action . ')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    } else if ('DELETE' === $request_method) { //
        $contractId = filter_input(INPUT_GET, 'contractId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $contractName = $fn_contract->delete_contract($contractId); //
        $fn_general->updateVersion(7); //
        $fn_general->save_audit('30', $jwt_data->userId, 'Site = ' . $contractName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CONTRACT_DELETE;
        $form_data['success'] = true;
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method'); //
    }
    Class_db::getInstance()->db_close(); //
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close(); //
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4); //
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4); //
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT; //
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage()); //
}

echo json_encode($form_data); //