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
        DbMysql::beginTransaction();
        $isTransaction = true;

        $result = $fnFcaReport->createPdf($params);
        //$fnFcaReport->insert($params);
        //$fnFcaReport->saveAudit(196, $fnFcaReport->fcaReportName);
        //$formData['errmsg'] = str_replace('__', $fnFcaReport->fcaReportName, Constant::$fcaReport['add']);
        $formData['errmsg'] = 'Good';

        DbMysql::commit();
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
