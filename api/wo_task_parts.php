<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WoTaskParts.php';
require_once 'class/WoTaskRequest.php';
require_once 'class/AstPart.php';

$apiName = 'wo_task_parts';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new WoTaskParts();

try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnWoTaskRequest = new WoTaskRequest($fnMain->userId, Constant::$isLogged);
    $fnAstPart = new AstPart($fnMain->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'list' && isset($urlArr[2]) && $urlArr[2] === 'm' && isset($urlArr[3])) {
            $result = $fnMain->getListMobile($urlArr[3]);
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        if (isset($urlArr[1]) && is_numeric($urlArr[1])) {
            $bodyParams = json_decode(file_get_contents("php://input"), true);
            DbMysql::beginTransaction();
            $isTransaction = true;
            $woTaskRequestId = intval($urlArr[1]);
            $woTaskPartsId = $fnMain->insert($woTaskRequestId, $bodyParams);
            $fnWoTaskRequest->set($woTaskRequestId);
            $itemDescription = $fnAstPart->getItemDescription($bodyParams['partId']);
            $fnMain->saveAudit(168, 'woTaskPartsId = '.$woTaskPartsId.', request no. = '.$fnWoTaskRequest->woTaskRequestNo.', item name = '.$itemDescription.', quantity = '.$bodyParams['woTaskPartsQuantity'].', remark = '.$bodyParams['woTaskPartsRemark']);
            DbMysql::commit();
            $formData['errmsg'] = str_replace('__', $itemDescription, Constant::$woTaskParts['add']);
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        if (!isset ($urlArr[1]) || !is_numeric($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        DbMysql::beginTransaction();
        $isTransaction = true;
        $woTaskPartsId = intval($urlArr[1]);
        $fnMain->update($woTaskPartsId, $bodyParams);
        $woTaskPart = $fnMain->get($woTaskPartsId);
        $itemDescription = $fnAstPart->getItemDescription($woTaskPart['partId']);
        $fnWoTaskRequest->set($woTaskPart['woTaskRequestId']);
        $fnMain->saveAudit(169, 'woTaskPartsId = '.$woTaskPartsId.', request no. = '.$fnWoTaskRequest->woTaskRequestNo.', item name = '.$itemDescription.', quantity = '.$bodyParams['woTaskPartsQuantity'].', remark = '.$bodyParams['woTaskPartsRemark']);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $itemDescription, Constant::$woTaskParts['update']);
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1]) || !is_numeric($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        DbMysql::beginTransaction();
        $isTransaction = true;
        $woTaskPartsId = intval($urlArr[1]);
        $woTaskPart = $fnMain->get($woTaskPartsId);
        $fnMain->delete($woTaskPartsId);
        $itemDescription = $fnAstPart->getItemDescription($woTaskPart['partId']);
        $fnWoTaskRequest->set($woTaskPart['woTaskRequestId']);
        $fnMain->saveAudit(170, 'woTaskPartsId = '.$woTaskPartsId.', request no. = '.$fnWoTaskRequest->woTaskRequestNo.', item name = '.$itemDescription);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $itemDescription, Constant::$woTaskParts['update']);
    } else {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method');
    }
    DbMysql::close();
} catch (Exception $e) {
    try {
        if ($isTransaction) {
            DbMysql::rollback();
        }
        DbMysql::close();
    } catch (Exception $ex) {
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
