<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_client.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_client = new Class_client();
$api_name = 'api_client';
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
        $clientId = filter_input(INPUT_GET, 'clientId');
        if (!is_null($clientId)) {
            $form_data['result'] = $fn_client->get_client($clientId);
        } else {
            $result = $fn_client->get_client_list();
        }
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $clientName = filter_input(INPUT_POST, 'clientName');
        $clientDesc = filter_input(INPUT_POST, 'clientDesc');
        $clientStatus = filter_input(INPUT_POST, 'clientStatus');

        $params = array(
            'clientName'=>$clientName,
            'clientDesc'=>$clientDesc,
            'clientStatus'=>$clientStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_client->add_client($params);
        $fn_general->updateVersion(5);
        $fn_general->save_audit('7', $jwt_data->userId, 'Client = ' . $clientName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CLIENT_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $clientId = filter_input(INPUT_GET, 'clientId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_client->update_client($clientId, $put_vars);
            $fn_general->updateVersion(5);
            $fn_general->save_audit('8', $jwt_data->userId, 'Client = ' . $put_vars['clientName']);
            $form_data['errmsg'] = $constant::SUC_CLIENT_EDIT;
        }
        else if ($action === 'deactivate') {
            $clientName = $fn_client->deactivate_client($clientId);
            $fn_general->updateVersion(5);
            $fn_general->save_audit('9', $jwt_data->userId, 'Client = ' . $clientName);
            $form_data['errmsg'] = $constant::SUC_CLIENT_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $clientName = $fn_client->activate_client($clientId);
            $fn_general->updateVersion(5);
            $fn_general->save_audit('10', $jwt_data->userId, 'Client = ' . $clientName);
            $form_data['errmsg'] = $constant::SUC_CLIENT_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $clientId = filter_input(INPUT_GET, 'clientId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $clientName = $fn_client->delete_client($clientId);
        $fn_general->updateVersion(5);
        $fn_general->save_audit('11', $jwt_data->userId, 'Client = ' . $clientName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_CLIENT_DELETE;
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