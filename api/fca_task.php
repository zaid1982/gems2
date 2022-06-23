<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaTask.php';
require_once 'class/FcaTaskSection.php';
require_once 'class/Task.php';
require_once 'class/Email.php';
require_once 'class/Noti.php';

$apiName = 'fca_task';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnFcaTask = new FcaTask();
try {
    DbMysql::connect();
    $fnFcaTask->logDebug('API', $apiName, __LINE__, 'Request method = , URL = '.$_SERVER['REQUEST_URI']);
    $fnFcaTask->checkJwt(apache_request_headers());
    $fnFcaTask->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnFcaTask->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnFcaTask->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnTask = new Task($fnFcaTask->userId, Constant::$isLogged);
    $fnFcaTaskSection = new FcaTaskSection($fnFcaTask->userId, Constant::$isLogged);
    $fnEmail = new Email($fnFcaTask->userId, Constant::$isLogged);
    $fnNoti = new Noti($fnFcaTask->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            $result = $fnFcaTask->getList();
        } else if (is_numeric($urlArr[1])) {
            $result = $fnFcaTask->get(intval($urlArr[1]));
        } else if ($urlArr[1] === 'progress' && isset ($urlArr[2])) {
            $result = $fnTask->getProgressTimeList(intval($urlArr[2]));
        } else if ($urlArr[1] === 'audit') {
            $result = $fnFcaTask->getSubmittedList(51);
        } else if ($urlArr[1] === 'recommend' && $urlArr[2] === 'new') {
            //TODO - get new recommend task
        } else if ($urlArr[1] === 'recommend' && $urlArr[2] === 'submitted') {
            //TODO - get submitted recommend task
        } else if ($urlArr[1] === 'validate' && $urlArr[2] === 'new') {
            //TODO - get new validate task
        } else if ($urlArr[1] === 'validate' && $urlArr[2] === 'submitted') {
            //TODO - get submitted validate task
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        if (isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $image1Arr = $fnFcaTask->uploadPrepare($params['image1'], 25);
        $image2Arr = !empty($params['image2']) ? $fnFcaTask->uploadPrepare($params['image2'], 26) : array();

        DbMysql::beginTransaction();
        $isTransaction = true;
        $curDates = new DateTime();
        $fnFcaTask->generateNo($params['siteId']);
        $fnTask->createNew(5, $fnFcaTask->fcaTaskNo);
        $fnTask->set($fnTask->wflTaskNew['taskId']);

        $paramFcaTask = $fnFcaTask->arraySpliceAssoc($params, array('siteId', 'assetGroupId', 'fcaZoneId', 'fcaTaskArea', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation'));
        $paramFcaTask['fcaTaskImage1'] = $fnFcaTask->uploadSave($image1Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_1');
        if (!empty($image2Arr)) {
            $paramFcaTask['fcaTaskImage2'] = $fnFcaTask->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_2');
        }
        $fnFcaTask->insert($paramFcaTask, $fnTask->transactionId);
        $fnFcaTaskSection->register($fnFcaTask->fcaTaskId);

        $fnTask->submit($fnFcaTask->fcaTask['fcaTaskObservation'], 54, 55);
        $fnEmail->prepare($fnFcaTask->userId, 21, array('task_no'=>$fnFcaTask->fcaTaskNo, 'observation'=>$fnFcaTask->fcaTask['fcaTaskObservation']));
        $fnNoti->prepare($fnFcaTask->userId, 22, array('task_no'=>$fnFcaTask->fcaTaskNo));
        $fnFcaTask->saveAudit(193, $fnFcaTask->fcaTaskNo);
        DbMysql::commit();
        $formData['errmsg'] = Constant::$fcaTask['submitNew'];

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
        $fnFcaTask->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnFcaTask->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);