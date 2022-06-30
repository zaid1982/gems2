<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';

$apiName = 'document';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnGeneral = new General();

try {
    DbMysql::connect();
    $fnGeneral->checkJwt(apache_request_headers());
    $fnGeneral->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnGeneral->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnGeneral->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        } else if ($urlArr[1] === 'upload' && isset ($urlArr[2])) {
            $result = $fnGeneral->getUpload(intval($urlArr[2]));
        } else if ($urlArr[1] === 'upload_link' && isset ($urlArr[2])) {
            $result = $fnGeneral->getUploadLink(intval($urlArr[2]));
        } else if ($urlArr[1] === 'pdf_link' && isset ($urlArr[2])) {
            $result = $fnGeneral->getPdfLink(intval($urlArr[2]));
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    } else {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method');
    }
    DbMysql::close();
    DbMysql::close();
} catch (Exception $e) {
    try {
        if ($isTransaction) {
            DbMysql::rollback();
        }
        DbMysql::close();
    } catch (Exception $ex) {
        $fnGeneral->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnGeneral->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
