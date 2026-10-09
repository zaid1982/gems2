<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/DashboardSummary.php';

$apiName = 'dashboard';
$formData = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
date_default_timezone_set('Asia/Kuala_Lumpur');
ini_set('display_errors', '0');

$fnMain = new DashboardSummary();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = ' . $requestMethod . ', URL = ' . $_SERVER['REQUEST_URI']);
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: $_SERVER['REQUEST_URI'];
    $requestPath = str_replace('dashboard.php', 'dashboard', $requestPath);
    $urlArr = $fnMain->getUrlArr($requestPath, $apiName);

    DbMysql::connect();
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
    if (empty($headers['Authorization']) && empty($headers['authorization']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
    }
    $fnMain->checkJwt($headers);

    if ($requestMethod !== 'GET') {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method');
    }

    $resource = isset($urlArr[1]) ? $urlArr[1] : '';
    if ($resource === 'me') {
        $result = $fnMain->me();
    } else if ($resource === 'wo') {
        $result = $fnMain->workOrders($_GET);
    } else if ($resource === 'ppm') {
        $result = $fnMain->ppm($_GET);
    } else if ($resource === 'assets') {
        $result = $fnMain->assets($_GET);
    } else if ($resource === 'ptw') {
        $result = $fnMain->ptw($_GET);
    } else if ($resource === 'waste') {
        $result = $fnMain->waste($_GET);
    } else if ($resource === 'licenses') {
        $result = $fnMain->licenses($_GET);
    } else {
        throw new Exception('Unknown dashboard section.', 31);
    }

    $formData['result'] = $result;
    $formData['success'] = true;
    DbMysql::close();
} catch (Exception $e) {
    try {
        DbMysql::close();
    } catch (Exception $ex) {
        $fnMain->logError('API', $apiName, __LINE__, $ex->getMessage());
    }
    $message = $e->getMessage();
    $clean = $message;
    $marker = strripos($message, '] ');
    if ($marker !== false) {
        $clean = trim(substr($message, $marker + 2));
    }
    $authFailed = stripos($message, 'Expired token') !== false
        || stripos($message, 'Signature verification failed') !== false
        || stripos($message, 'Wrong number of segments') !== false
        || stripos($message, 'Parameter Authorization empty') !== false
        || stripos($message, 'Invalid header encoding') !== false
        || stripos($message, 'Invalid claims encoding') !== false
        || stripos($message, 'Invalid signature encoding') !== false
        || stripos($message, 'Algorithm not allowed') !== false;
    if ($authFailed) {
        $formData['error'] = 'Expired token';
        $formData['errmsg'] = 'Expired token';
    } else if ($e->getCode() === 31) {
        $formData['error'] = $clean;
        $formData['errmsg'] = $clean;
    } else {
        $formData['error'] = Constant::$err['default'];
        $formData['errmsg'] = Constant::$err['default'];
    }
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
