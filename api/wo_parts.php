<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo_parts.php';
require_once 'function/f_wo_request.php';
require_once 'function/f_wo.php';
require_once 'function/f_part.php';
require_once 'function/f_item.php';

$api_name = 'api_wo_parts';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo_part = new Class_wo_parts();
$fn_wo_request = new Class_wo_request();
$fn_wo = new Class_wo();
$fn_part = new Class_part();
$fn_item = new Class_item();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo_part->__set('fn_general', $fn_general);
    $fn_wo_part->__set('constant', $constant);
    $fn_wo_request->__set('fn_general', $fn_general);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_part->__set('fn_general', $fn_general);
    $fn_item->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'wo_parts') {
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
            if ($urlArr[1] === 'list_by_pending') {
                $result = $fn_wo_part->getWoPartsByStatusList($urlArr[2], '(34, 38)');
            } else if ($urlArr[1] === 'list_check_out') {
                $result = $fn_wo_part->getWoPartsByStatusList($urlArr[2], '36');
            } else if ($urlArr[1] === 'wo_parts_mobile_list') {
                $result = $fn_wo_part->getWoPartsMobileList($urlArr[2]);
            } else if ($urlArr[1] === 'wo_parts_mobile_detail') {
                $result = $fn_wo_part->getWoPartsMobileDetail($urlArr[2]);
            } else {
                $result = $fn_wo_part->getWoPartsList($urlArr[1]);
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $param = $_POST;
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_wo_part->addWoPartsMobile($param, $userId);
        $item = $fn_item->getItem($param['itemId']);
        $fn_wo->__set('woTaskId', $param['woTaskId']);
        $wo = $fn_wo->get_wo_task();
        $fn_general->save_audit('168', $userId, 'Wo Task Part ID = '.$result.', Work Order No. = '.$wo['woTaskNo'].', Item description = '.$item['itemDescription'].', Quantity = '.$param['quantity']);
        $form_data['errmsg'] = 'Item Description successfully added into Request List';

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $fn_wo_part->updateWoParts($urlArr[1], $params);
        $woParts = $fn_wo_part->getWoParts($urlArr[1]);
        $woRequest = $fn_wo_request->getWoRequest($woParts['woTaskRequestId']);
        $fn_wo->__set('woTaskId', $woRequest['woTaskId']);
        $wo = $fn_wo->get_wo_task();
        $part = $fn_part->getPart($woParts['partId']);
        $item = $fn_item->getItem($part['itemId']);
        $fn_general->save_audit('169', $userId, 'Wo Task Part ID = '.$urlArr[1].', Work Order No. = '.$wo['woTaskNo'].', Item description = '.$item['itemDescription'].', Quantity = '.$params['quantity']);
        $form_data['errmsg'] = 'Item Requisition details successfully updated';

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $woParts = $fn_wo_part->getWoParts($urlArr[1]);
        $fn_wo_part->deleteWoParts($urlArr[1]);
        $woRequest = $fn_wo_request->getWoRequest($woParts['woTaskRequestId']);
        $fn_wo->__set('woTaskId', $woRequest['woTaskId']);
        $wo = $fn_wo->get_wo_task();
        $part = $fn_part->getPart($woParts['partId']);
        $item = $fn_item->getItem($part['itemId']);
        $fn_general->save_audit('170', $userId, 'Wo Task Part ID = '.$urlArr[1].', Work Order No. = '.$wo['woTaskNo'].', Item description = '.$item['itemDescription']);
        $form_data['errmsg'] = 'Item Requisition successfully deleted';

        Class_db::getInstance()->db_commit();
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



