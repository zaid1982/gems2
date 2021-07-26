<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_drawing.php';

$api_name = 'api_drawing';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_drawing = new Class_drawing();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_drawing->__set('constant', $constant);
    $fn_drawing->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'drawing') {
            break;
        }
        array_shift($urlArr);
    }

    if (isset($urlArr[1]) && $urlArr[1] === 'external') {
        array_shift($urlArr);
    } else {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
        }
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
        $userId = $jwt_data->userId;
    }

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'low') {
                $result = $fn_drawing->getDrawingList('1');
            } else {
                $result = $fn_drawing->getDrawing($urlArr[1]);
            }
        } else {
            $result = $fn_drawing->getDrawingList();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $param = $_POST;

        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'upload_dwg_drawing') {
                $result = $fn_general->uploadDocument($param, 19, $userId);
            }
            else if ($urlArr[1] === 'upload_pdf_drawing') {
                $result = $fn_general->uploadDocument($param, 20, $userId);
            } else {
                throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
            }
        } else {
            $param['drawingCreatedBy'] = $userId;
            $fn_drawing->addDrawing($param);
            $fn_general->save_audit('148', $userId, 'Drawing Title = '.$param['drawingTitle'].', Id No = '.$param['drawingIdNo'].', version = '.$param['drawingVersion']);
            $form_data['errmsg'] = $constant::SUC_SUBMITTED; 
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $putData = file_get_contents("php://input");
        parse_str($putData, $params);

        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'update_dwg_drawing') {
                $drawing = $fn_drawing->getDrawing($urlArr[2]);
                $drawingDwg = $fn_general->uploadDocument($params, 19, $userId);
                $fn_drawing->updateDrawing($urlArr[2], array('drawingDwg'=>$drawingDwg));
                $fn_general->deleteDocument($drawing['drawingDwg']);
            }
            else if ($urlArr[1] === 'update_pdf_drawing') {
                $drawing = $fn_drawing->getDrawing($urlArr[2]);
                $drawingPdf = $fn_general->uploadDocument($params, 20, $userId);
                $fn_drawing->updateDrawing($urlArr[2], array('drawingPdf'=>$drawingPdf));
                $fn_general->deleteDocument($drawing['drawingPdf']);
            }
            else {
                $fn_drawing->updateDrawing($urlArr[1], $params);
                $form_data['errmsg'] = $constant::SUC_SAVE;
                $fn_general->save_audit('149', $userId, 'Drawing Title = '.$params['drawingTitle'].', Id No = '.$params['drawingIdNo'].', version = '.$params['drawingVersion']);
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
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
