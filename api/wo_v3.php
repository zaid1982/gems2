<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WoTask.php';
require_once 'class/WoMrfPdf.php';
require_once 'pdf/tcpdf_include.php';

$apiName = 'wo_v3';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnWoTask = new WoTask();

try {
    DbMysql::connect();
    $fnWoTask->checkJwt(apache_request_headers());
    $fnWoTask->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnWoTask->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnWoTask->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnWoMrfPdf = new WoMrfPdf($fnWoTask->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'preview_mrf_pdf' && isset ($urlArr[2])) {
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnWoTask->get($woTaskId);
            $pdfMrfId = $woTask['pdfIdMrf'];
            if (empty($pdfMrfId) || $woTask['woTaskMrfGenerate'] === 0) {
                $pdfMrfId = $fnWoMrfPdf->createPdf($woTaskId);
            }
            $result = $fnWoTask->getPdfLink($pdfMrfId);
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
        $fnWoTask->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnWoTask->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);