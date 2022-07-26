<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_store.php';

$api_name = 'api_item_type';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_store = new Class_store();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_store->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'store') {
            break;
        }
        array_shift($urlArr);
    }

    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
        if (!isset($headers['deviceid'])) {
            throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
        }
        $fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $userId = $jwt_data->userId;

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'purchase_option_store') {
                $result = $fn_store->getPurchaseStoreOption($userId);
            } else {
                $result = $fn_store->getStore($urlArr[1]);
            }
        } else {
            $result = $fn_store->getStoreList();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $param = $_POST;

        $storeId = $fn_store->addStore($param);
        $fn_general->updateVersion(26);
        $fn_general->save_audit('153', $userId, 'Store ID = '.$storeId.', Store name = '.$param['storeName']);
        $form_data['errmsg'] = $constant::SUC_SUBMITTED;

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);

        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        $fn_store->updateStore($urlArr[1], $params);
        $fn_general->updateVersion(26);
        $fn_general->save_audit('154', $userId, 'Store ID = '.$urlArr[1].', Store name = '.$params['storeName']);
        $form_data['errmsg'] = $constant::SUC_SAVE;

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        $store = $fn_store->getStore($urlArr[1]);
        $fn_store->deleteStore($urlArr[1]);
        $fn_general->updateVersion(26);
        $fn_general->save_audit('155', $userId, 'Store ID = '.$store['storeId'].', Store name = '.$store['storeName']);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_DELETE;
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
