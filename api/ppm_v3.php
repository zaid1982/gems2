<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/Ppm.php';

$apiName = 'ppm_v3';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new Ppm();

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
            if ($urlArr[1] === 'listPpmGroup' && isset($urlArr[2])) {
                $result = $fnMain->getListPpmGroup(intval($urlArr[2]));
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
        if (isset ($urlArr[1]) && $urlArr[1] === 'ppmAssetGroup') {
            $bodyParams = json_decode(file_get_contents("php://input"), true);
            DbMysql::beginTransaction();
            $isTransaction = true;
            $ppmId = $fnMain->insertAssetGroup($bodyParams);
            $fnMain->saveAudit(222, 'ppmId = '.$ppmId);
            $formData['errmsg'] = Constant::$ppm['addGroup'];
            DbMysql::commit();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        if (isset ($urlArr[1]) && $urlArr[1] === 'ppmAssetGroup' && isset($urlArr[2])) {
            $ppmId = intval($urlArr[2]);
            $bodyParams = json_decode(file_get_contents("php://input"), true);
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnMain->update($ppmId, $bodyParams);
            $fnMain->saveAudit(222, 'ppmId = '.$ppmId);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$ppm['updateGroup'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETE Request');
        }
        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->delete(intval($urlArr[1]));
        //$fnMain->saveAudit(217, $urlArr[1]);
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


