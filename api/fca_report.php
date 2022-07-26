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

$fnFcaReport = new FcaReport();

try {
    DbMysql::connect();
    $fnFcaReport->checkJwt(apache_request_headers());
    $fnFcaReport->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnFcaReport->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnFcaReport->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        $result = $fnFcaReport->getList();
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        $result = $fnFcaReport->createPdf($params);

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnFcaReport->insert($result);
        $fnFcaReport->saveAudit(209, $fnFcaReport->fcaReportName);
        $formData['errmsg'] = str_replace('__', $fnFcaReport->fcaReportName, Constant::$fcaReport['add']);
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
        $fnFcaReport->set(intval($urlArr[1]));
        $fnFcaReport->delete();
        $fnFcaReport->saveAudit(210, $fnFcaReport->fcaReportName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnFcaReport->fcaReportName, Constant::$fcaReport['delete']);

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
        $fnFcaReport->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnFcaReport->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
