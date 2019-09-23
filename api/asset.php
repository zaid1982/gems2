<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset.php';

$api_name = 'api_asset';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_asset = new Class_asset();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_asset->__set('constant', $constant);
    $fn_asset->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $assetId = filter_input(INPUT_GET, 'assetId');
        $type = filter_input(INPUT_GET, 'type');
        $contractId = filter_input(INPUT_GET, 'contractId');
        if (!is_null($type)) {
            if ($type === 'total_asset') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $result = $fn_asset->get_total_asset($clientId, $siteId);
            }
        } else if (!is_null($assetId)) {
            $result = $fn_asset->get_asset($assetId);
        } else if (!is_null($contractId)) {
            $result = $fn_asset->get_asset_list($contractId);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $contractId = filter_input(INPUT_POST, 'contractId');
        $assetGroupId = filter_input(INPUT_POST, 'assetGroupId');
        $assetCategoryId = filter_input(INPUT_POST, 'assetCategoryId');
        $assetTypeId = filter_input(INPUT_POST, 'assetTypeId');
        $ppmGroupId = filter_input(INPUT_POST, 'ppmGroupId');

        $params = array(
            'contractId'=>$contractId,
            'assetGroupId'=>$assetGroupId,
            'assetCategoryId'=>$assetCategoryId,
            'assetTypeId'=>$assetTypeId,
            'ppmGroupId'=>$ppmGroupId
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_asset->create_asset($params);
        $fn_general->save_audit('56', $jwt_data->userId, 'Asset Id = ' . $result);

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $assetId = filter_input(INPUT_GET, 'assetId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'save') {
            $fn_asset->save_asset($assetId, $put_vars);
            $fn_general->save_audit('57', $jwt_data->userId, 'Asset Id = ' . $assetId);
            $form_data['errmsg'] = $constant::SUC_ASSET_SAVE;
        }
        else if ($action === 'submit') {
            $fn_asset->submit_asset($assetId, $put_vars, $jwt_data->userId);
            $fn_general->save_audit('58', $jwt_data->userId, 'Asset Id = ' . $assetId);
            $form_data['errmsg'] = $constant::SUC_ASSET_REGISTER;
        }
        else if ($action === 'update') {
            $fn_asset->update_asset($assetId, $put_vars);
            $fn_general->save_audit('59', $jwt_data->userId, 'Asset Id = ' . $assetId);
            $form_data['errmsg'] = $constant::SUC_ASSET_EDIT;
        }
        else if ($action === 'deactivate') {
            $fn_asset->deactivate_asset($assetId);
            $fn_general->save_audit('60', $jwt_data->userId, 'Asset Id = ' . $assetId);
            $form_data['errmsg'] = $constant::SUC_ASSET_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $fn_asset->activate_asset($assetId);
            $fn_general->save_audit('61', $jwt_data->userId, 'Asset Id = ' . $assetId);
            $form_data['errmsg'] = $constant::SUC_ASSET_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $assetId = filter_input(INPUT_GET, 'assetId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $assetName = $fn_asset->delete_asset($assetId);
        $fn_general->save_audit('62', $jwt_data->userId, 'Asset = ' . $assetName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_DELETE;
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