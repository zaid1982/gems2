<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_item_type.php';

$api_name = 'api_item_type';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_item_type = new Class_item_type();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_item_type->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'item_type') {
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
            $result = $fn_item_type->getItemType($urlArr[1]);
        } else {
            $result = $fn_item_type->getItemTypeList();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $param = $_POST;

        $itemTypeId = $fn_item_type->addItemType($param);
        $fn_general->updateVersion(24);
        $fn_general->save_audit('150', $userId, 'Item Type ID = '.$itemTypeId.', Item Type description = '.$param['itemTypeDesc']);
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

        $fn_item_type->updateItemType($urlArr[1], $params);
        $form_data['errmsg'] = $constant::SUC_SAVE;
        $fn_general->updateVersion(24);
        $fn_general->save_audit('151', $userId, 'Item Type ID = '.$urlArr[1].', Item Type description = '.$params['itemTypeDesc']);

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

        $itemType = $fn_item_type->getItemType($urlArr[1]);
        $fn_item_type->deleteItemType($urlArr[1]);
        $fn_general->updateVersion(24);
        $fn_general->save_audit('152', $userId, 'Item Type ID = '.$urlArr[1].', Item Type description = '.$itemType['itemTypeDesc']);

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
