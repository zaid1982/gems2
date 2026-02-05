<?php
/**
 * API endpoint for uploading/updating part images
 * POST: Upload new image for a part
 */

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_item_image.php';

$api_name = 'api_part_image';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_item_image = new Class_item_image();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_item_image->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    // JWT Authentication
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $userId = $jwt_data->userId;

    if ('POST' === $request_method) {
        $param = $_POST;
        
        // Get part ID from URL or POST data
        $urlArr = explode('/', $_SERVER['REQUEST_URI']);
        foreach ($urlArr as $i=>$p) {
            if ($p === 'part_image') {
                break;
            }
            array_shift($urlArr);
        }
        
        $partId = isset($urlArr[1]) ? intval($urlArr[1]) : 0;
        if ($partId <= 0) {
            $partId = isset($param['partId']) ? intval($param['partId']) : 0;
        }
        if ($partId <= 0) {
            throw new Exception('[' . __LINE__ . '] - Invalid part ID');
        }

        // Get part details to find the item_id
        $partData = Class_db::getInstance()->db_select_single('ast_part', ['part_id' => $partId]);
        if (!$partData) {
            throw new Exception('[' . __LINE__ . '] - Part not found');
        }
        $itemId = $partData['item_id'];

        // Prepare upload data - expects base64 data from frontend
        if (!isset($param['data']) || empty($param['data'])) {
            throw new Exception('[' . __LINE__ . '] - No image data provided');
        }
        
        // Upload the document using existing function (doc_type 21 = image)
        $uploadId = $fn_general->uploadDocument($param, '21', $userId);
        
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        
        // Use existing item_image function to link image to item
        $itemImageId = $fn_item_image->addItemImage($itemId, $uploadId);
        
        $fn_general->save_audit('162', $userId, 'Part ID = '.$partId.', Item Image ID = '.$itemImageId);
        
        Class_db::getInstance()->db_commit();
        
        $form_data['result'] = [
            'uploadId' => $uploadId,
            'itemImageId' => $itemImageId,
            'message' => 'Image uploaded successfully'
        ];
        $form_data['errmsg'] = $constant::SUC_UPLOADED_IMAGE;
        $form_data['success'] = true;
    } else {
        throw new Exception('[' . __LINE__ . '] - Invalid request method');
    }

} catch (Exception $e) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollBack();
    }
    $form_data['error'] = $e->getMessage();
    $form_data['errmsg'] = 'Error on system. Please contact Administrator!';
    $fn_general->log_debug('API', $api_name, __LINE__, 'error = '.$e->getMessage());
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($form_data);
