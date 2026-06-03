<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/NotiWeb.php';

$apiName = 'noti_web';
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

$fnMain = new NotiWeb();

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

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if (strtolower($urlArr[1]) === 'by_userid') {
            $result = $fnMain->getByUserId();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (isset ($urlArr[1]) && is_numeric($urlArr[1])) {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnMain->delete(intval($urlArr[1]));
            DbMysql::commit();
            $formData['errmsg'] = 'Notification successfully removed';
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETER Request');
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
    // DEBUG: expose raw error to caller — REMOVE after diagnosing 500
    $formData['error']  = $e->getMessage();
    $formData['errmsg'] = $e->getMessage();
    $formData['debug']  = array(
        'class' => get_class($e),
        'code'  => $e->getCode(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString()),
    );
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);

