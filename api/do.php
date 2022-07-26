<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_do.php';
require_once 'function/f_do_item.php';
require_once 'function/f_do_upload.php';
require_once 'function/f_part_sub.php';

$api_name = 'api_do';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_do = new Class_do();
$fn_do_item = new Class_do_item();
$fn_do_upload = new Class_do_upload();
$fn_part_sub = new Class_part_sub();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_do->__set('fn_general', $fn_general);
    $fn_do->__set('constant', $constant);
    $fn_do_item->__set('fn_general', $fn_general);
    $fn_do_upload->__set('fn_general', $fn_general);
    $fn_part_sub->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'do') {
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
            if ($urlArr[1] === 'list_mobile_check_in') {
                $result = $fn_do->getCheckInMobileList($userId);
            } else if ($urlArr[1] === 'check_in_mobile_details') {
                $result = $fn_do->getCheckInMobileDetails($urlArr[2]);
            } else {
                throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $params = $_POST;
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        if ($urlArr[1] === 'check_in_direct') {
            // ********** checking validity ********** \\
            $fn_general->checkEmptyParamsArray($params, array('storeId'));
            $fn_do->checkCheckIn($userId, $params['storeId']);

            // ********** upload DO attachment ********** \\
            $doUploadIds = array();
            $doUploads = $params['doUploads'];
            foreach ($doUploads as $doUpload) {
                $uploadId = $fn_general->uploadDocument($doUpload, 23, $userId);
                array_push($doUploadIds, $uploadId);
            }

            // ********** submit check in ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $doId = $fn_do->addDoMobile($userId, $params);
            $doItems = $params['doItems'];
            foreach ($doItems as $doItem) {
                $doItemId = $fn_do_item->addDoItemMobile($doId, $doItem);
                $doItem['doItemId'] = $doItemId;
                $fn_part_sub->addPartSubMobile($params['storeId'], $userId, $params['doNo'], $doItem);
            }
            foreach ($doUploadIds as $doUploadId) {
                $fn_do_upload->addDoUpload($doId, $doUploadId);
            }
            Class_db::getInstance()->db_commit();

            // ********** audit trail ********** \\
            $fn_general->save_audit('169', $userId, 'DO No. = '.$params['doNo']);
            $form_data['errmsg'] = 'Check In Parts successfully registered';
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