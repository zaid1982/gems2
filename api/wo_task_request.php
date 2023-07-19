<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WoTaskRequest.php';
require_once 'class/WoMrfPdf.php';
require_once 'pdf/tcpdf_include.php';

$apiName = 'wo_task_request';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new WoTaskRequest();

try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnWoMrfPdf = new WoMrfPdf($fnMain->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'preview_mrf_pdf' && isset ($urlArr[2])) {
            $woTaskRequestId = intval($urlArr[2]);
            $woTaskRequest = $fnMain->get($woTaskRequestId);
            $pdfMrfId = $woTaskRequest['woTaskRequestMrfPdf'];
            if (empty($pdfMrfId) || $woTaskRequest['woTaskRequestMrfGenerate'] === 1) {
                $pdfMrfId = $fnWoMrfPdf->createPdf($woTaskRequestId);
            }
            $result = $fnMain->getPdfLink($pdfMrfId);
        }
        else if ($urlArr[1] === 'get_mrf_pdf_link' && isset ($urlArr[2])) {
            $pdfMrfId = intval($urlArr[2]);
            $result = $fnMain->getPdfLink($pdfMrfId);
        } else if ($urlArr[1] === 'list_mrf' && isset ($urlArr[2]) && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnMain->getListMrf(intval($urlArr[2]), intval($urlArr[3]), intval($urlArr[4]));
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
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);