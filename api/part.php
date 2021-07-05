<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_part.php';
require_once 'function/f_store.php';
require_once 'function/f_item.php';

$api_name = 'api_part';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_part = new Class_part();
$fn_store = new Class_store();
$fn_item = new Class_item();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_part->__set('fn_general', $fn_general);
    $fn_part->__set('constant', $constant);
    $fn_store->__set('fn_general', $fn_general);
    $fn_item->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'part') {
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
            if ($urlArr[1] === 'list_by_store') {
                $result = $fn_part->getPartList($urlArr[2]);
            } else if ($urlArr[1] === 'list_with_image') {
                $result = $fn_part->getPartListWithImage($urlArr[2]);
            } else if ($urlArr[1] === 'option_asset_group') {
                $result = $fn_part->getPartAssetGroupOption($userId);
            } else if ($urlArr[1] === 'option_item_type') {
                $result = $fn_part->getPartItemTypeOption($userId, $urlArr[2]);
            } else if ($urlArr[1] === 'option_item') {
                $result = $fn_part->getPartItemOption($userId, $urlArr[2]);
            } else if ($urlArr[1] === 'add_option_asset_group') {
                $result = $fn_part->getPartAddAssetGroupOption($urlArr[2]);
            } else if ($urlArr[1] === 'add_option_item_type') {
                $result = $fn_part->getPartAddItemTypeOption($urlArr[2], $urlArr[3]);
            } else if ($urlArr[1] === 'add_option_item') {
                $result = $fn_part->getPartAddItemOption($urlArr[2], $urlArr[3]);
            } else if ($urlArr[1] === 'purchase_option_asset_group') {
                $result = $fn_part->getPurchaseAssetGroupOption($userId, $urlArr[2]);
            } else if ($urlArr[1] === 'purchase_option_item_type') {
                $result = $fn_part->getPurchaseItemTypeOption($userId, $urlArr[2], $urlArr[3]);
            } else if ($urlArr[1] === 'purchase_option_part') {
                $result = $fn_part->getPurchasePartOption($userId, $urlArr[2], $urlArr[3]);
            } else if ($urlArr[1] === 'part_tree_category') {
                $result = $fn_part->getPartTreeCategory($userId, $urlArr[2]);
            } else if ($urlArr[1] === 'part_mobile_details') {
                $result = $fn_part->getPartMobile($urlArr[2]);
            } else if ($urlArr[1] === 'list_mobile_threshold') {
                $result = $fn_part->getPartListMobileThreshold($userId, $urlArr[2]);
            } else {
                $result = $fn_part->getPart($urlArr[1]);
            }
        } else {
            $result = $fn_part->getItemList();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $param = $_POST;
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_part->addPart($param);
        $store = $fn_store->getStore($param['storeId']);
        $item = $fn_item->getItem($param['itemId']);
        $fn_general->save_audit('163', $userId, 'Part ID = '.$result.', Store Name = '.$store['storeName'].', Item description = '.$item['itemDescription']);
        $form_data['errmsg'] = 'Item Description successfully added into store inventory. Please complete the part details before activate it.';

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

        if ($urlArr[1] === 'disable') {
            $fn_part->deactivatePart($urlArr[2]);
            $part = $fn_part->getPart($urlArr[2]);
            $item = $fn_item->getItem($part['itemId']);
            $store = $fn_store->getStore($part['storeId']);
            $fn_general->save_audit('165', $userId, 'Part ID = '.$urlArr[2].', Store Name = '.$store['storeName'].', Item description = '.$item['itemDescription']);
            $form_data['errmsg'] = $constant::SUC_DEACTIVATED;
        }
        else if ($urlArr[1] === 'enable') {
            $fn_part->activatePart($urlArr[2]);
            $part = $fn_part->getPart($urlArr[2]);
            $item = $fn_item->getItem($part['itemId']);
            $store = $fn_store->getStore($part['storeId']);
            $fn_general->save_audit('166', $userId, 'Part ID = '.$urlArr[2].', Store Name = '.$store['storeName'].', Item description = '.$item['itemDescription']);
            $form_data['errmsg'] = $constant::SUC_DEACTIVATED;
        }
        else {
            $fn_part->updatePart($urlArr[1], $params);
            $part = $fn_part->getPart($urlArr[1]);
            $item = $fn_item->getItem($part['itemId']);
            $store = $fn_store->getStore($part['storeId']);
            $fn_general->save_audit('164', $userId, 'Part ID = '.$urlArr[1].', Store Name = '.$store['storeName'].', Item description = '.$item['itemDescription']);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }

        Class_db::getInstance()->db_commit();
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



