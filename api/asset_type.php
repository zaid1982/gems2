<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset_type.php';

$api_name = 'api_asset_type';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_assetType = new Class_assetType();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_assetType->__set('constant', $constant);
    $fn_assetType->__set('fn_general', $fn_general);


    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $assetTypeId = filter_input(INPUT_GET, 'assetTypeId');
        if (!is_null($assetTypeId)) {
            $result = $fn_assetType->get_assetType($assetTypeId);
        } else {
            $result = $fn_assetType->get_assetType_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $assetTypeName = filter_input(INPUT_POST, 'assetTypeName');
        $assetTypeDesc = filter_input(INPUT_POST, 'assetTypeDesc');
        $assetCategoryId = filter_input(INPUT_POST, 'assetCategoryId');
        $assetTypeStatus = filter_input(INPUT_POST, 'assetTypeStatus');

        $params = array(
            'assetTypeName'=>$assetTypeName,
            'assetTypeDesc'=>$assetTypeDesc,
            'assetCategoryId'=>$assetCategoryId,
            'assetTypeStatus'=>$assetTypeStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_assetType->add_assetType($params);
        $fn_general->updateVersion(11);
        $fn_general->save_audit('41', $jwt_data->userId, 'Asset Type = ' . $assetTypeName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_TYPE_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $assetTypeId = filter_input(INPUT_GET, 'assetTypeId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_assetType->update_assetType($assetTypeId, $put_vars);
            $fn_general->updateVersion(11);
            $fn_general->save_audit('42', $jwt_data->userId, 'Asset Type = ' . $put_vars['assetTypeName']);
            $form_data['errmsg'] = $constant::SUC_ASSET_TYPE_EDIT;
        }
        else if ($action === 'deactivate') {
            $assetTypeName = $fn_assetType->deactivate_assetType($assetTypeId);
            $fn_general->updateVersion(11);
            $fn_general->save_audit('43', $jwt_data->userId, 'Asset Type = ' . $assetTypeName);
            $form_data['errmsg'] = $constant::SUC_ASSET_TYPE_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $assetTypeName = $fn_assetType->activate_assetType($assetTypeId);
            $fn_general->updateVersion(11);
            $fn_general->save_audit('44', $jwt_data->userId, 'Asset Type = ' . $assetTypeName);
            $form_data['errmsg'] = $constant::SUC_ASSET_TYPE_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $assetTypeId = filter_input(INPUT_GET, 'assetTypeId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $assetTypeName = $fn_assetType->delete_assetType($assetTypeId);
        $fn_general->updateVersion(11);
        $fn_general->save_audit('45', $jwt_data->userId, 'Asset Type = ' . $assetTypeName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_TYPE_DELETE;
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