<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_asset_group.php';
require_once 'function/f_item_type.php';
require_once 'function/f_part.php';
require_once 'function/f_wo_parts.php';
require_once 'function/f_material_return.php';
require_once 'pdf/tcpdf_include.php';

$api_name = 'api_m_wo';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_assetGroup = new Class_assetGroup();
$fn_itemType = new Class_item_type();
$fn_part = new Class_part();
$fn_woParts = new Class_wo_parts();
$fn_materialReturn = new Class_material_return();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_assetGroup->__set('constant', $constant);
    $fn_assetGroup->__set('fn_general', $fn_general);
    $fn_itemType->__set('fn_general', $fn_general);
    $fn_part->__set('fn_general', $fn_general);
    $fn_woParts->__set('fn_general', $fn_general);
    $fn_materialReturn->__set('fn_general', $fn_general);
    $fn_materialReturn->__set('constant', $constant);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    // Support both path-based routing (/return_eligible_items/1) and query params (?action=return_eligible_items&id=1)
    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'm_inventory' || strpos($param, 'm_inventory.php') !== false) {
            break;
        }
        array_shift($urlArr);
    }
    
    // Remove query string from last element if present
    if (!empty($urlArr)) {
        $lastIndex = count($urlArr) - 1;
        if (strpos($urlArr[$lastIndex], '?') !== false) {
            $urlArr[$lastIndex] = substr($urlArr[$lastIndex], 0, strpos($urlArr[$lastIndex], '?'));
        }
    }

    $headers = apache_request_headers();
    
    if (isset($urlArr[1]) && $urlArr[1] === 'external') {
        array_shift($urlArr);
    } else {
        // Check for Authorization header (case-insensitive)
        if (isset($headers['Authorization'])) {
            $jwt_data = $fn_login->check_jwt($headers['Authorization']);
        } else if (isset($headers['authorization'])) {
            $jwt_data = $fn_login->check_jwt($headers['authorization']);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty - '.json_encode($headers));
        }
        $userId = $jwt_data->userId;
    }

    if (isset($headers['deviceid']) || isset($headers['deviceId'])) {
        $deviceId = isset($headers['deviceid']) ? $headers['deviceid'] : $headers['deviceId'];
        $fn_login->check_device_id($userId, $deviceId);
    }
    
    if ('GET' === $request_method) {
        // Support both URL path format and query parameter format
        $action = isset($urlArr[1]) && !empty($urlArr[1]) ? $urlArr[1] : (isset($_GET['action']) ? $_GET['action'] : '');
        
        if (empty($action)) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        
        if ($action === 'asset_group_list') {
            $result = $fn_assetGroup->get_assetGroup_list();
        } else if ($action === 'item_type_list') {
            $assetGroupId = isset ($urlArr[2]) ? $urlArr[2] : (isset($_GET['id']) ? $_GET['id'] : '');
            $result = $fn_itemType->getItemTypeList($assetGroupId);
        } else if ($action === 'part_list') {
            $woTaskId = isset ($urlArr[2]) ? $urlArr[2] : (isset($_GET['woTaskId']) ? $_GET['woTaskId'] : '');
            $itemTypeId = isset ($urlArr[3]) ? $urlArr[3] : (isset($_GET['itemTypeId']) ? $_GET['itemTypeId'] : '');
            $result = $fn_part->getPartListMobile($woTaskId, $itemTypeId);
        } else if ($action === 'wo_parts_list') {
            $woTaskId = isset($urlArr[2]) ? $urlArr[2] : (isset($_GET['woTaskId']) ? $_GET['woTaskId'] : '');
            $result = $fn_woParts->getWoPartsMobileList($woTaskId);
        } 
        // ========== Material Returns Endpoints ==========
        else if ($action === 'return_eligible_items') {
            // Get technician's return-eligible items
            $technicianUserId = isset($urlArr[2]) ? $urlArr[2] : (isset($_GET['id']) ? $_GET['id'] : $userId);
            $result = $fn_materialReturn->getReturnEligibleItems($technicianUserId);
        } else if ($action === 'storekeeper_pending_returns') {
            // Get all pending returns for storekeeper
            $result = $fn_materialReturn->getStorekeeperPendingReturns();
        } else if ($action === 'return_detail') {
            // Get specific return details
            $returnId = isset($urlArr[2]) ? $urlArr[2] : (isset($_GET['id']) ? $_GET['id'] : '');
            if (empty($returnId)) {
                throw new Exception('[' . __LINE__ . '] - Return ID required');
            }
            $result = $fn_materialReturn->getReturnDetail($returnId);
        } else if ($action === 'return_history') {
            // Get return history with optional filters
            $filters = array();
            if (isset($_GET['userId'])) $filters['userId'] = $_GET['userId'];
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            if (isset($_GET['dateFrom'])) $filters['dateFrom'] = $_GET['dateFrom'];
            if (isset($_GET['dateTo'])) $filters['dateTo'] = $_GET['dateTo'];
            $result = $fn_materialReturn->getReturnHistory($filters);
        } else if ($action === 'return_statistics') {
            // Get return statistics
            $technicianUserId = isset($_GET['userId']) ? $_GET['userId'] : null;
            $result = $fn_materialReturn->getReturnStatistics($technicianUserId);
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    } 
    else if ('POST' === $request_method) {
        $postData = file_get_contents("php://input");
        $params = json_decode($postData, true);
        
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        
        if ($urlArr[1] === 'request_return') {
            // Submit return request from technician
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            
            $returnId = $fn_materialReturn->submitReturnRequest($userId, $params);
            
            // Get return details for audit
            $returnDetail = $fn_materialReturn->getReturnDetail($returnId);
            $fn_general->save_audit('190', $userId, 'Return request submitted - Return ID: '.$returnId.', Item: '.$returnDetail['itemDescription'].', Quantity: '.$params['quantityReturned']);
            
            Class_db::getInstance()->db_commit();
            $form_data['result'] = array('returnId' => $returnId);
            $form_data['errmsg'] = 'Return request submitted successfully. Waiting for storekeeper confirmation.';
            $form_data['success'] = true;
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
    }
    else if ('PUT' === $request_method) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);
        
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        
        if ($urlArr[1] === 'confirm_return') {
            // Confirm return receipt by storekeeper
            if (!isset($urlArr[2])) {
                throw new Exception('[' . __LINE__ . '] - Return ID required');
            }
            
            $returnId = $urlArr[2];
            
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            
            $updateResult = $fn_materialReturn->confirmReturnReceipt($returnId, $userId);
            
            // Get return details for audit
            $returnDetail = $fn_materialReturn->getReturnDetail($returnId);
            $fn_general->save_audit('191', $userId, 'Return confirmed - Return ID: '.$returnId.', Item: '.$returnDetail['itemDescription'].', Quantity: '.$updateResult['returnedQuantity'].', New Available: '.$updateResult['newAvailable']);
            
            Class_db::getInstance()->db_commit();
            $form_data['result'] = $updateResult;
            $form_data['errmsg'] = 'Return confirmed successfully. Inventory updated with '.$updateResult['returnedQuantity'].' items. New available stock: '.$updateResult['newAvailable'];
            $form_data['success'] = true;
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
    }
    else {
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