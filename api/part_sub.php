<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_part_sub.php';

$api_name = 'api_part_sub';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_part_sub = new Class_part_sub();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_part_sub->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'part_sub') {
            break;
        }
        array_shift($urlArr);
    }

    if (isset($urlArr[1]) && $urlArr[1] === 'external') {
        array_shift($urlArr);
    } else {
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
    }

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'list_available') {
                $result = $fn_part_sub->getPartSubList('(46,38,51)', $urlArr[2]);
            }
        } else {
            $result = $fn_part_sub->getPartSubList();
        }
        $form_data['result'] = $result;
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

