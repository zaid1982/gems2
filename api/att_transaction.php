<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/AttTransaction.php';

$apiName = 'att_transaction';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnAttTransaction = new AttTransaction();

try {
    DbMysql::connect();
    $fnAttTransaction->checkJwt(apache_request_headers());
    $fnAttTransaction->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnAttTransaction->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnAttTransaction->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (isset($urlArr[1]) && $urlArr[1] === 'monthly' && isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3]) && isset($urlArr[4]) && isset($urlArr[5])) {
            $result = $fnAttTransaction->getMonthlySite(intval($urlArr[3]), intval($urlArr[4]), intval($urlArr[5]));
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
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
        $fnAttTransaction->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnAttTransaction->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
