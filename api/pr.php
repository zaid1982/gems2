<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_pr.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/pr.php';

$api_name = 'api_drawing';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_pr= new Class_pr();
$fn_pdf_pr = new Class_pdf_pr();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_pr->__set('constant', $constant);
    $fn_pr->__set('fn_general', $fn_general);
    $fn_pdf_pr->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'pr') {
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
            if ($urlArr[1] === 'new_request') {
                $result = $fn_pr->getPrTask($userId, '32');
            } else {
                throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $putData = file_get_contents("php://input");
        parse_str($putData, $params);
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'refresh_pdf') {
                $fn_pdf_pr->create_pdf($urlArr[2]);
            } else {
                throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
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
