<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WoTask.php';
require_once 'class/NotiWeb.php';

$apiName = 'wo_image_zip';
$isTransaction = false;
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new WoTask();

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
    $fnNotiWeb = new NotiWeb($fnMain->userId, Constant::$isLogged);

    if ('POST' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        $fnMain->logDebug('API', $apiName, __LINE__, 'params = '.json_encode($bodyParams));
        $notInfo = $fnMain->prepareImageZip($bodyParams);
        $fnNotiWeb->insert(4, $fnMain->userId, $notInfo);
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
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

exit();