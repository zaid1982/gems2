<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WoTask.php';
require_once 'class/WoMrfPdf.php';
require_once 'class/WflTask.php';
require_once 'class/Email.php';
require_once 'class/Noti.php';
require_once 'pdf/tcpdf_include.php';

$apiName = 'wo_v3';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
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
    $fnTask = new WflTask($fnMain->userId, Constant::$isLogged);
    $fnEmail = new Email($fnMain->userId, Constant::$isLogged);
    $fnNoti = new Noti($fnMain->userId, Constant::$isLogged);
    $fnWoMrfPdf = new WoMrfPdf($fnMain->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'preview_mrf_pdf' && isset ($urlArr[2])) {
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnMain->get($woTaskId);
            $pdfMrfId = $woTask['pdfIdMrf'];
            if (empty($pdfMrfId) || $woTask['woTaskMrfGenerate'] === 0) {
                $pdfMrfId = $fnWoMrfPdf->createPdf($woTaskId);
            }
            $result = $fnMain->getPdfLink($pdfMrfId);
        } else if ($urlArr[1] === 'pending_assign') {
            $result = $fnMain->pendingAssign();
        } else {
            $result = $fnMain->get(intval($urlArr[1]));
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) { // public complaint
        if (isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        if (!isset($bodyParams['siteId'])) {
            throw new Exception('[line: ' . __LINE__ . '] - Invalid siteId');
        }
        $siteId = intval($bodyParams['siteId']);
        $fnMain->userId = DbMysql::selectColumn('sys_user', array('userFirstName'=>Constant::$publicUser, 'siteId'=>$siteId), 'userId', 1);
        $fnMain->logDebug('API', $apiName, __LINE__, 'userId = '.$fnMain->userId);
        $groupId = DbMysql::selectColumn('sys_user_role', array('userId'=>$fnMain->userId, 'roleId'=>6), 'groupId', 1);
        if (DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'groupId', 1) !== $groupId) {
            throw new Exception('[line: ' . __LINE__ . '] - Invalid groupId - '.$groupId);
        }

        $imageArr = array();
        if (!empty($bodyParams['image'])) {
            $imageArr = $fnMain->uploadPrepare($bodyParams['image'], 9);
        }
        DbMysql::beginTransaction();
        $isTransaction = true;
        $curDates = new DateTime();
        $fnMain->generateNo($siteId);
        $fnMain->logDebug('API', $apiName, __LINE__, 'woTaskNo = '.$fnMain->woTaskNo);
        $uploadId = null;
        if (!empty($bodyParams['image'])) {
            $uploadId = $fnMain->uploadSave($imageArr, 'wo/'.$curDates->format("ym"), $fnMain->woTaskNo.'_1');
        }
        $fnTask->userId = $fnMain->userId;
        $fnTask->createNew(2, $fnMain->woTaskNo, 11);
        $fnTask->set($fnTask->wflTaskNew['taskId']);
        $fnTask->submit($bodyParams['complaint'], 8, 9, $fnMain->woTaskIsWr, $groupId);
        $fnMain->submitPublic($bodyParams, $fnTask->transactionId, $uploadId);
        DbMysql::commit();

        DbMysql::beginTransaction();
        foreach ($fnTask->getRecipients(true) as $recipient) {
            $fnEmail->prepare($recipient, 4, array('task_no'=>$fnMain->woTaskNo));
            $fnNoti->prepare($recipient, 5, array('task_no'=>$fnMain->woTaskNo));
        }
        $fnMain->saveAudit(104, 'Work '.($fnMain->woTaskIsWr===1?'Request':'Order').' no. = '.$fnMain->woTaskNo);
        DbMysql::commit();
        $formData['errmsg'] = Constant::$wo['submitPublic'];
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