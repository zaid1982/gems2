<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset_category.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_assetCategory = new Class_assetCategory();
$api_name = 'api_asset_category';
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
        $assetCategoryId = filter_input(INPUT_GET, 'assetCategoryId');
        if (!is_null($assetCategoryId)) {
            $result = $fn_assetCategory->get_assetCategory($assetCategoryId);
        } else {
            $result = $fn_assetCategory->get_assetCategory_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $assetCategoryName = filter_input(INPUT_POST, 'assetCategoryName');
        $assetCategoryDesc = filter_input(INPUT_POST, 'assetCategoryDesc');
        $assetGroupId = filter_input(INPUT_POST, 'assetGroupId');
        $assetCategoryStatus = filter_input(INPUT_POST, 'assetCategoryStatus');

        $params = array(
            'assetCategoryName'=>$assetCategoryName,
            'assetCategoryDesc'=>$assetCategoryDesc,
            'assetGroupId'=>$assetGroupId,
            'assetCategoryStatus'=>$assetCategoryStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_assetCategory->add_assetCategory($params);
        $fn_general->updateVersion(10);
        $fn_general->save_audit('36', $jwt_data->userId, 'Asset Category = ' . $assetCategoryName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_CATEGORY_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $assetCategoryId = filter_input(INPUT_GET, 'assetCategoryId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_assetCategory->update_assetCategory($assetCategoryId, $put_vars);
            $fn_general->updateVersion(10);
            $fn_general->save_audit('37', $jwt_data->userId, 'Asset Category = ' . $put_vars['assetCategoryName']);
            $form_data['errmsg'] = $constant::SUC_ASSET_CATEGORY_EDIT;
        }
        else if ($action === 'deactivate') {
            $assetCategoryName = $fn_assetCategory->deactivate_assetCategory($assetCategoryId);
            $fn_general->updateVersion(10);
            $fn_general->save_audit('38', $jwt_data->userId, 'Asset Category = ' . $assetCategoryName);
            $form_data['errmsg'] = $constant::SUC_ASSET_CATEGORY_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $assetCategoryName = $fn_assetCategory->activate_assetCategory($assetCategoryId);
            $fn_general->updateVersion(10);
            $fn_general->save_audit('39', $jwt_data->userId, 'Asset Category = ' . $assetCategoryName);
            $form_data['errmsg'] = $constant::SUC_ASSET_CATEGORY_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $assetCategoryId = filter_input(INPUT_GET, 'assetCategoryId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $assetCategoryName = $fn_assetCategory->delete_assetCategory($assetCategoryId);
        $fn_general->updateVersion(10);
        $fn_general->save_audit('40', $jwt_data->userId, 'Asset Category = ' . $assetCategoryName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_ASSET_CATEGORY_DELETE;
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