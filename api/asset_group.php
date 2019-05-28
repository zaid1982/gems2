<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset_group.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_assetGroup = new Class_assetGroup();
$api_name = 'api_asset_group';
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
        $assetGroupId = filter_input(INPUT_GET, 'assetGroupId');
        if (!is_null($assetGroupId)) {
            $form_data['result'] = $fn_assetGroup->get_assetGroup($assetGroupId);
        } else {
            $result = $fn_assetGroup->get_assetGroup_list();
        }
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $assetGroupName = filter_input(INPUT_POST, 'assetGroupName');
        $assetGroupDesc = filter_input(INPUT_POST, 'assetGroupDesc');
        $assetGroupStatus = filter_input(INPUT_POST, 'assetGroupStatus');

        $params = array(
            'assetGroupName'=>$assetGroupName,
            'assetGroupDesc'=>$assetGroupDesc,
            'assetGroupStatus'=>$assetGroupStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_assetGroup->add_assetGroup($params);
        $fn_general->updateVersion(9);
        $fn_general->save_audit('31', $jwt_data->userId, 'Asset Group = ' . $assetGroupName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_GROUP_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $assetGroupId = filter_input(INPUT_GET, 'assetGroupId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_assetGroup->update_assetGroup($assetGroupId, $put_vars);
            $fn_general->updateVersion(9);
            $fn_general->save_audit('32', $jwt_data->userId, 'Asset Group = ' . $put_vars['assetGroupName']);
            $form_data['errmsg'] = $constant::SUC_ASSET_GROUP_EDIT;
        }
        else if ($action === 'deactivate') {
            $assetGroupName = $fn_assetGroup->deactivate_assetGroup($assetGroupId);
            $fn_general->updateVersion(9);
            $fn_general->save_audit('33', $jwt_data->userId, 'Asset Group = ' . $assetGroupName);
            $form_data['errmsg'] = $constant::SUC_ASSET_GROUP_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $assetGroupName = $fn_assetGroup->activate_assetGroup($assetGroupId);
            $fn_general->updateVersion(9);
            $fn_general->save_audit('34', $jwt_data->userId, 'Asset Group = ' . $assetGroupName);
            $form_data['errmsg'] = $constant::SUC_ASSET_GROUP_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $assetGroupId = filter_input(INPUT_GET, 'assetGroupId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $assetGroupName = $fn_assetGroup->delete_assetGroup($assetGroupId);
        $fn_general->updateVersion(9);
        $fn_general->save_audit('35', $jwt_data->userId, 'Asset Group = ' . $assetGroupName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_GROUP_DELETE;
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