<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset_model.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_assetModel = new Class_assetModel();
$api_name = 'api_asset_model';
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
        $assetModelId = filter_input(INPUT_GET, 'assetModelId');
        if (!is_null($assetModelId)) {
            $result = $fn_assetModel->get_assetModel($assetModelId);
        } else {
            $result = $fn_assetModel->get_assetModel_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $assetModelName = filter_input(INPUT_POST, 'assetModelName');
        $assetModelDesc = filter_input(INPUT_POST, 'assetModelDesc');
        $assetBrandId = filter_input(INPUT_POST, 'assetBrandId');
        $assetTypeId = filter_input(INPUT_POST, 'assetTypeId');
        $assetModelStatus = filter_input(INPUT_POST, 'assetModelStatus');

        $params = array(
            'assetModelName'=>$assetModelName,
            'assetModelDesc'=>$assetModelDesc,
            'assetBrandId'=>$assetBrandId,
            'assetTypeId'=>$assetTypeId,
            'assetModelStatus'=>$assetModelStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_assetModel->add_assetModel($params);
        $fn_general->updateVersion(13);
        $fn_general->save_audit('51', $jwt_data->userId, 'Asset Model = ' . $assetModelName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_MODEL_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $assetModelId = filter_input(INPUT_GET, 'assetModelId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_assetModel->update_assetModel($assetModelId, $put_vars);
            $fn_general->updateVersion(13);
            $fn_general->save_audit('52', $jwt_data->userId, 'Asset Model = ' . $put_vars['assetModelName']);
            $form_data['errmsg'] = $constant::SUC_ASSET_MODEL_EDIT;
        }
        else if ($action === 'deactivate') {
            $assetModelName = $fn_assetModel->deactivate_assetModel($assetModelId);
            $fn_general->updateVersion(13);
            $fn_general->save_audit('53', $jwt_data->userId, 'Asset Model = ' . $assetModelName);
            $form_data['errmsg'] = $constant::SUC_ASSET_MODEL_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $assetModelName = $fn_assetModel->activate_assetModel($assetModelId);
            $fn_general->updateVersion(13);
            $fn_general->save_audit('54', $jwt_data->userId, 'Asset Model = ' . $assetModelName);
            $form_data['errmsg'] = $constant::SUC_ASSET_MODEL_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $assetModelId = filter_input(INPUT_GET, 'assetModelId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $assetModelName = $fn_assetModel->delete_assetModel($assetModelId);
        $fn_general->updateVersion(13);
        $fn_general->save_audit('55', $jwt_data->userId, 'Asset Model = ' . $assetModelName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_MODEL_DELETE;
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