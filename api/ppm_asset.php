<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/PpmAsset.php';

$apiName = 'ppm_asset';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new PpmAsset();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    DbMysql::connect();
    if (isset($urlArr[1]) && $urlArr[1] === 'ext') {
        array_shift($urlArr);
    } else {
        $fnMain->checkJwt(apache_request_headers());
    }

    if ('GET' === $requestMethod) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'list' && isset($urlArr[2])) {
                $result = $fnMain->getList(intval($urlArr[2]));
            } else if ($urlArr[1] === 'listSelection' && isset($urlArr[2]) && isset($urlArr[3])) {
                $result = $fnMain->getListSelection(intval($urlArr[2]), intval($urlArr[3]));
            } else if (is_numeric($urlArr[1])) {
                $result = $fnMain->get(intval($urlArr[1]));
            } else {
                throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
            }
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->insertBatch($bodyParams);
        $fnMain->saveAudit(226, json_encode($bodyParams['listAsset']));
        $formData['errmsg'] = $fnMain->errMsg;
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->update(intval($urlArr[1]), $bodyParams);
        //$fnMain->saveAudit(218, $urlArr[1]);
        DbMysql::commit();
        $formData['errmsg'] = Constant::$ppm['updateGroup'];
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETE Request');
        }
        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->delete(intval($urlArr[1]));
        $fnMain->saveAudit(227, 'ppmAssetId = '.$urlArr[1]);
        DbMysql::commit();
        $formData['errmsg'] = Constant::$ppm['deleteGroup'];
        $formData['success'] = true;
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


