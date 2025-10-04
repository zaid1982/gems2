<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/RefSpaceType.php';

$apiName = 'ref_space_type.php';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new RefSpaceType();

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
        if (!isset($urlArr[1])) {
            $filters = array();
            if (isset($_GET['status'])) { $filters['status'] = intval($_GET['status']); }
            if (isset($_GET['spaceCategoryId'])) { $filters['spaceCategoryId'] = intval($_GET['spaceCategoryId']); }
            $result = $fnMain->list($filters);
        } else {
            $result = $fnMain->get(intval($urlArr[1]));
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $bodyParams = $_POST;
        if (empty($bodyParams)) {
            $raw = file_get_contents("php://input");
            if (!empty($raw)) { $j = json_decode($raw, true); if (is_array($j)) { $bodyParams = $j; } }
        }
        DbMysql::beginTransaction();
        $isTransaction = true;
        $result = $fnMain->create($bodyParams);
        DbMysql::commit();
        $formData['errmsg'] = 'Type created';
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        DbMysql::beginTransaction();
        $isTransaction = true;
        $result = $fnMain->update(intval($urlArr[1]), $bodyParams);
        DbMysql::commit();
        $formData['errmsg'] = 'Type updated';
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->delete(intval($urlArr[1]));
        DbMysql::commit();
        $formData['errmsg'] = 'Type deleted';
        $formData['success'] = true;
    }
    else {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method');
    }
    DbMysql::close();
} catch (Exception $e) {
    try { if ($isTransaction) { DbMysql::rollback(); } DbMysql::close(); } catch (Exception $ex) { $fnMain->logError('API', $apiName, __LINE__, $e->getMessage()); }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
