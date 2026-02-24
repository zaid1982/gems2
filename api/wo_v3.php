<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WoTask.php';
require_once 'class/WoMrfPdf.php';
require_once 'class/WflTask.php';
require_once 'class/Email.php';
require_once 'class/Noti.php';
require_once 'class/NotiWeb.php';
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
    $fnNotiWeb = new NotiWeb($fnMain->userId, Constant::$isLogged);

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
            if (isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3])) {
                $result = $fnMain->pendingAssignBySite(intval($urlArr[3]));
            } else {
                $result = $fnMain->pendingAssign();
            }
        } else if ($urlArr[1] === 'submitted_assign') {
            if (isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3])) {
                $result = $fnMain->submittedAssignBySite(intval($urlArr[3]));
            } else {
                $result = $fnMain->submittedAssign();
            }
        } else if ($urlArr[1] === 'submitted_assign_total') {
            if (isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3])) {
                $result = $fnMain->submittedAssignTotalBySite(intval($urlArr[3]));
            } else {
                $result = $fnMain->submittedAssignTotal();
            }
        } else if ($urlArr[1] === 'pending_verify') {
            if (isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3])) {
                $result = $fnMain->pendingVerifyBySite(intval($urlArr[3]));
            } else {
                $result = $fnMain->pendingVerify();
            }
        } else if ($urlArr[1] === 'submitted_verify') {
            if (isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3])) {
                $result = $fnMain->submittedVerifyBySite(intval($urlArr[3]));
            } else {
                $result = $fnMain->submittedVerify();
            }
        } else if ($urlArr[1] === 'submitted_verify_total') {
            if (isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3])) {
                $result = $fnMain->submittedVerifyTotalBySite(intval($urlArr[3]));
            } else {
                $result = $fnMain->submittedVerifyTotal();
            }
        } else if ($urlArr[1] === 'material_list' && isset ($urlArr[2])) {
            $result = $fnMain->materialList($urlArr[2]);
        } else if ($urlArr[1] === 'by_assetId') {
            $result = $fnMain->getByAssetId(intval($urlArr[2]));
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
        $fnTask->submit($bodyParams['complaint'], 9, 8, $fnMain->woTaskIsWr, $groupId);
        $fnMain->submitPublic($bodyParams, $fnTask->transactionId, $uploadId);
        DbMysql::commit();

        DbMysql::beginTransaction();
        foreach ($fnTask->getRecipients(true) as $recipient) {
            $fnEmail->prepare($recipient, 4, array('task_no'=>$fnMain->woTaskNo));
            $fnNoti->prepare($recipient, 5, array('task_no'=>$fnMain->woTaskNo));
            $fnNotiWeb->insert($fnMain->woTaskIsWr === 1 ? 2 : 1, $recipient, $fnMain->woTaskNo);
        }
        $fnMain->saveAudit(104, 'Work '.($fnMain->woTaskIsWr===1?'Request':'Order').' no. = '.$fnMain->woTaskNo);
        DbMysql::commit();
        $formData['errmsg'] = Constant::$wo['submitPublic'];
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        DbMysql::beginTransaction();
        $isTransaction = true;
        if ($urlArr[1] === 'update_by_admin' && isset($urlArr[2])) {
            $woTaskId = intval($urlArr[2]);
            $result = $fnMain->updateByAdmin($woTaskId, $bodyParams);
            if ($result) {
                $fnMain->saveAudit(129, $fnMain->auditRemark);
                $formData['errmsg'] = Constant::$wo['update'];
            }
        }
        else if ($urlArr[1] === 'submit_assign' && isset($urlArr[2])) {
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnMain->get($woTaskId);
            $fnTask->userId = $fnMain->userId;
            $fnMain->woTaskIsWr = $woTask['woTaskIsWr'];
            $fnTask->setByTransaction($woTask['transactionId']);
            $checkpointId = intval($fnTask->wflTask['checkpointId'] ?? 0);
            if (!in_array($checkpointId, array(12, 17), true)) {
                throw new Exception(Constant::$task['errAlreadySubmitted'], 31);
            }
            $fnTask->checkValidity($checkpointId);
            $fnTask->submit('', 10, 8, 0, 0, $bodyParams['woTaskAssignedTo']);
            $fnMain->submitAssign($bodyParams, $woTask['transactionId']);
            // After submitAssign, woTaskIsWr and woTaskNo may have been updated (self-finding WR → WO conversion)
            $woTaskNoAfter = !empty($fnMain->woTaskNo) ? $fnMain->woTaskNo : $woTask['woTaskNo'];
            $emailTemplateId = $fnMain->woTaskIsWr === 1 ? 11 : 5;
            $notiTextId = $fnMain->woTaskIsWr === 1 ? 12 : 6;
            $fnEmail->prepare($bodyParams['woTaskAssignedTo'], $emailTemplateId, array('task_no' => $woTaskNoAfter));
            $fnNoti->prepare($bodyParams['woTaskAssignedTo'], $notiTextId, array('task_no' => $woTaskNoAfter));
            $fnMain->saveAudit(129, 'Work ' . ($fnMain->woTaskIsWr === 1 ? 'Request' : 'Order') . ' no. = ' . $woTaskNoAfter);
            $formData['errmsg'] = Constant::$wo['assign'];
        }
        else if ($urlArr[1] === 'reject_complaint' && isset($urlArr[2])) {
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnMain->get($woTaskId);
            if ($woTask['woTaskTypeInit'] !== 6) {
                throw new Exception('[line: ' . __LINE__ . '] - This task is not Public Complaint type!', 31);
            }
            $fnTask->userId = $fnMain->userId;
            $fnMain->woTaskIsWr = $woTask['woTaskIsWr'];
            $fnTask->setByTransaction($woTask['transactionId']);
            $fnTask->checkValidity($fnMain->woTaskIsWr === 1 ? 17 : 12);
            $fnMain->rejectAssign($woTask['transactionId']);
            $fnTask->submit($bodyParams['remark'], 25, 25, 1);
            $woTaskPublic = DbMysql::select('wo_task_public', array('woTaskId'=>$woTaskId), true);
            $fnEmail->prepare(1, 10, array('task_no' => $woTask['woTaskNo'], 'comment'=>$bodyParams['remark']), $woTaskPublic['woTaskPublicName'], $woTaskPublic['woTaskPublicEmail']);
            $fnMain->saveAudit(122, 'Work ' . ($fnMain->woTaskIsWr === 1 ? 'Request' : 'Order') . ' no. = ' . $woTask['woTaskNo']);
            $formData['errmsg'] = Constant::$wo['reject'];
        }
        else if ($urlArr[1] === 'reassign' && isset($urlArr[2])) {
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnMain->get($woTaskId);
            $fnTask->userId = $fnMain->userId;
            $fnTask->setByTransaction($woTask['transactionId']);
            $fnTask->checkValidity(13, array(array('roleId'=>1, 'groupId'=>1), array('roleId'=>19, 'groupId'=>1)));
            $fnTask->submit('', 58, 8, 3, 0, $bodyParams['woTaskAssignedTo']);
            $fnMain->reassign($bodyParams, $woTask['transactionId']);
            $formData['errmsg'] = Constant::$wo['reassign'];
        }
        else if ($urlArr[1] === 'return_verify' && isset($urlArr[2])) { // only for self-finding and public complaint
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnMain->get($woTaskId);
            $fnTask->userId = $fnMain->userId;
            $fnMain->woTaskIsWr = $woTask['woTaskIsWr'];
            $fnTask->setByTransaction($woTask['transactionId']);
            $fnTask->checkValidity(16);
            $woTaskAssignedTo = DbMysql::selectColumn('wfl_task_assign', array('transactionId'=>$woTask['transactionId'], 'checkpointId'=>13, 'roleId'=>8), 'userId', true);
            $fnTask->submit($bodyParams['remark'], 20, 20, 1);
            $fnMain->returnVerify($woTask['transactionId']);
            $fnEmail->prepare($woTaskAssignedTo, 8, array('task_no' => $woTask['woTaskNo'], 'comment'=>$bodyParams['remark']));
            $fnNoti->prepare($woTaskAssignedTo, 9, array('task_no' => $woTask['woTaskNo'], 'comment'=>$bodyParams['remark']));
            $fnMain->saveAudit(120, 'Work Order no. = ' . $woTask['woTaskNo']);
            $formData['errmsg'] = Constant::$wo['returnVerify'];
        }
        else if ($urlArr[1] === 'submit_verify' && isset($urlArr[2])) { // only for self-finding and public complaint
            $woTaskId = intval($urlArr[2]);
            $woTask = $fnMain->get($woTaskId);
            $fnTask->userId = $fnMain->userId;
            $fnMain->woTaskIsWr = $woTask['woTaskIsWr'];
            $fnTask->setByTransaction($woTask['transactionId']);
            $fnTask->checkValidity(16);
            $woTaskAssignedTo = DbMysql::selectColumn('wfl_task_assign', array('transactionId'=>$woTask['transactionId'], 'checkpointId'=>13, 'roleId'=>8), 'userId', true);
            $fnTask->submit($bodyParams['remark']);
            $fnMain->submitVerify($woTask['transactionId'], $bodyParams['rating']);
            $fnEmail->prepare($woTaskAssignedTo, 9, array('task_no' => $woTask['woTaskNo']));
            $fnNoti->prepare($woTaskAssignedTo, 10, array('task_no' => $woTask['woTaskNo']));
            if ($woTask['woTaskTypeInit'] === 6) {
                $woTaskPublic = DbMysql::select('wo_task_public', array('woTaskId'=>$woTaskId), true);
                $fnEmail->prepare(1, 25, array('task_no' => $woTask['woTaskNo']), $woTaskPublic['woTaskPublicName'], $woTaskPublic['woTaskPublicEmail']);
            }
            $fnMain->saveAudit(121, 'Work Order no. = ' . $woTask['woTaskNo']);
            $formData['errmsg'] = Constant::$wo['submitVerify'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        DbMysql::commit();
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