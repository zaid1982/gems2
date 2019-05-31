<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset_brand.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_assetBrand = new Class_assetBrand();
$api_name = 'api_asset_brand';
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
        $assetBrandId = filter_input(INPUT_GET, 'assetBrandId');
        if (!is_null($assetBrandId)) {
            $result = $fn_assetBrand->get_assetBrand($assetBrandId);
        } else {
            $result = $fn_assetBrand->get_assetBrand_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $assetBrandName = filter_input(INPUT_POST, 'assetBrandName');
        $assetBrandDesc = filter_input(INPUT_POST, 'assetBrandDesc');
        $assetBrandStatus = filter_input(INPUT_POST, 'assetBrandStatus');

        $params = array(
            'assetBrandName'=>$assetBrandName,
            'assetBrandDesc'=>$assetBrandDesc,
            'assetBrandStatus'=>$assetBrandStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_assetBrand->add_assetBrand($params);
        $fn_general->updateVersion(12);
        $fn_general->save_audit('46', $jwt_data->userId, 'Asset Brand = ' . $assetBrandName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_BRAND_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $assetBrandId = filter_input(INPUT_GET, 'assetBrandId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_assetBrand->update_assetBrand($assetBrandId, $put_vars);
            $fn_general->updateVersion(12);
            $fn_general->save_audit('47', $jwt_data->userId, 'Asset Brand = ' . $put_vars['assetBrandName']);
            $form_data['errmsg'] = $constant::SUC_ASSET_BRAND_EDIT;
        }
        else if ($action === 'deactivate') {
            $assetBrandName = $fn_assetBrand->deactivate_assetBrand($assetBrandId);
            $fn_general->updateVersion(12);
            $fn_general->save_audit('48', $jwt_data->userId, 'Asset Brand = ' . $assetBrandName);
            $form_data['errmsg'] = $constant::SUC_ASSET_BRAND_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $assetBrandName = $fn_assetBrand->activate_assetBrand($assetBrandId);
            $fn_general->updateVersion(12);
            $fn_general->save_audit('49', $jwt_data->userId, 'Asset Brand = ' . $assetBrandName);
            $form_data['errmsg'] = $constant::SUC_ASSET_BRAND_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $assetBrandId = filter_input(INPUT_GET, 'assetBrandId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $assetBrandName = $fn_assetBrand->delete_assetBrand($assetBrandId);
        $fn_general->updateVersion(12);
        $fn_general->save_audit('50', $jwt_data->userId, 'Asset Brand = ' . $assetBrandName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_BRAND_DELETE;
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