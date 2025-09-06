<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_user_signature.php';
require_once 'function/f_user.php';

$api_name = 'api_user_signature';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_user_signature = new Class_user_signature();
$fn_user = new Class_user();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_user_signature->__set('constant', $constant);
    $fn_user_signature->__set('fn_general', $fn_general);
    $fn_user->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'user_signature') {
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
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $fn_user_signature->getUserSignature($userId);
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $param = $_POST;
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        // ********** upload signature ********** \\
        $userId = $urlArr[1];
        $signatureId = $fn_general->uploadDocument($param, 22, $userId);
        $fn_user_signature->updateUserSignature($userId, $signatureId);

        // ********** audit trail ********** \\
        $user = $fn_user->getUserDao($userId);
        $fn_general->save_audit('173', $userId, 'Save signature for userId = '.$userId.', User Name = '.$user['userName'].', Full Name = '.$user['userFirstName']);
        
        $form_data['success'] = true;
        $form_data['errmsg'] = 'Your Signature successfully saved';
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close();
    $form_data['success'] = false;
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);