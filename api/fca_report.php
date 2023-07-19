<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaReport.php';
require_once 'pdf/tcpdf_include.php';

$apiName = 'fca_report';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new FcaReport();

try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        $result = $fnMain->getList();
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        $result = $fnMain->createPdf($params);

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->insert($result);
        $fnMain->saveAudit(209, $fnMain->fcaReportName);
        $formData['errmsg'] = str_replace('__', $fnMain->fcaReportName, Constant::$fcaReport['add']);
        DbMysql::commit();

        $formData['result'] = '';
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 1');
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->set(intval($urlArr[1]));
        $fnMain->delete();
        $fnMain->saveAudit(210, $fnMain->fcaReportName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnMain->fcaReportName, Constant::$fcaReport['delete']);

        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else {
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
