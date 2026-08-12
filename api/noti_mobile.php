<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/NotiMobile.php';

$apiName = 'noti_mobile';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!function_exists('gems_get_request_headers')) {
    function gems_get_request_headers (): array {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !isset($headers['Authorization'])) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        return $headers;
    }
}

$fnMain = new NotiMobile();

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
        $fnMain->checkJwt(gems_get_request_headers());
    }
    $fnMain = new NotiMobile($fnMain->userId, $fnMain->isLogged);

    if ('GET' === $requestMethod) {
        if (!isset($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if (strtolower($urlArr[1]) === 'by_userid') {
            $result = $fnMain->getByUserId();
        } else if (strtolower($urlArr[1]) === 'unread_count') {
            $result = $fnMain->getUnreadCount();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        if (isset($urlArr[1]) && is_numeric($urlArr[1]) && isset($urlArr[2]) && strtolower($urlArr[2]) === 'read') {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnMain->markRead(intval($urlArr[1]));
            DbMysql::commit();
            $formData['errmsg'] = 'Notification marked as read';
        } else if (isset($urlArr[1]) && strtolower($urlArr[1]) === 'read_all') {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnMain->markAllRead();
            DbMysql::commit();
            $formData['errmsg'] = 'All notifications marked as read';
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    } else {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method');
    }
    DbMysql::close();
} catch (Throwable $e) {
    try {
        if ($isTransaction) {
            DbMysql::rollback();
        }
        DbMysql::close();
    } catch (Throwable $ex) {
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = $e->getMessage();
    $formData['errmsg'] = $e->getMessage();
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
