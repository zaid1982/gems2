<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset.php';
require_once 'function/f_asset_import.php';

$api_name = 'api_asset_import';
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
date_default_timezone_set('Asia/Kuala_Lumpur');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_asset = new Class_asset();
$fn_asset_import = new Class_asset_import();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_asset->__set('constant', $constant);
    $fn_asset->__set('fn_general', $fn_general);
    $fn_asset_import->constant = $constant;
    $fn_asset_import->fn_general = $fn_general;
    $fn_asset_import->fn_asset = $fn_asset;

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = getAssetImportHeaders();
    if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    $jwt_data = $fn_login->check_jwt($authHeader);

    $user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$jwt_data->userId), null, 1);
    $userSite = isset($user['site_id']) ? $user['site_id'] : '';
    $userRoles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$jwt_data->userId), 'role_id');

    $canImport = false;
    $isAdministrator = false;
    foreach ($userRoles as $roleId) {
        if (in_array((string)$roleId, array('1', '19'), true)) {
            $canImport = true;
        }
        if (in_array((string)$roleId, array('1', '10'), true)) {
            $isAdministrator = true;
        }
    }
    if (!$canImport) {
        throw new Exception('[' . __LINE__ . '] - Access denied to import assets');
    }

    $action = '';
    if ($request_method === 'GET') {
        $action = filter_input(INPUT_GET, 'action');
    } else if ($request_method === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : filter_input(INPUT_GET, 'action');
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }

    if ($action === 'download_template') {
        $contractId = filter_input(INPUT_GET, 'contractId');
        $fn_asset_import->download_template($contractId, $userSite, $isAdministrator);
    } else if ($action === 'preview_import') {
        if ($request_method !== 'POST') {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $contractId = isset($_POST['contractId']) ? $_POST['contractId'] : '';
        if (!isset($_FILES['import_file'])) {
            throw new Exception('[' . __LINE__ . '] - No file uploaded');
        }
        $form_data['result'] = $fn_asset_import->preview_import($_FILES['import_file'], $contractId, $userSite, $isAdministrator);
        $form_data['success'] = true;
    } else if ($action === 'execute_import') {
        if ($request_method !== 'POST') {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $contractId = isset($_POST['contractId']) ? $_POST['contractId'] : '';
        if (!isset($_FILES['import_file'])) {
            throw new Exception('[' . __LINE__ . '] - No file uploaded');
        }
        @set_time_limit(120);
        $result = $fn_asset_import->execute_import($_FILES['import_file'], $contractId, $jwt_data->userId, $userSite, $isAdministrator);
        $fn_general->save_audit('58', $jwt_data->userId, 'Bulk asset import: inserted='.$result['inserted'].' skipped='.$result['skipped'].' Contract Id = '.$contractId);
        $form_data['result'] = $result;
        $form_data['errmsg'] = $constant::SUC_ASSET_IMPORT;
        $form_data['success'] = true;
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
    }

    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    Class_db::getInstance()->db_close();
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') !== false ? strpos($ex->getMessage(), '] - ') + 4 : 0);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = $form_data['error'];
    } else {
        $form_data['errmsg'] = $form_data['error'] !== '' ? $form_data['error'] : $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}
echo json_encode($form_data);

function getAssetImportHeaders() {
    if (function_exists('apache_request_headers')) {
        return apache_request_headers();
    }
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    $headers = array();
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
}
