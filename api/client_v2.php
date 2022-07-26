<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/CliClient.php';

$apiName = 'client';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnClient = new CliClient();

try {
    DbMysql::connect();
    $fnClient->checkJwt(apache_request_headers());
    $fnClient->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnClient->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnClient->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            //TODO - get all client
        } else if ($urlArr[1] === 'ref') {
            $result = $fnClient->getRef();
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
        $fnClient->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnClient->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
