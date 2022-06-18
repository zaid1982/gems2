<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaTask.php';
require_once 'class/FcaTaskSection.php';
require_once 'class/Task.php';

$apiName = 'fca_task';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnFcaTask = new FcaTask();

try {
    DbMysql::connect();
    $fnFcaTask->checkJwt(apache_request_headers());
    $fnFcaTask->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnFcaTask->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnFcaTask->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnTask = new Task($fnFcaTask->userId, false);
    $fnFcaTaskSection = new FcaTaskSection($fnFcaTask->userId, false);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            //TODO - get all fca
        }
        else if ($urlArr[1] === 'submitted') {
            //TODO - get submitted task
        }
        else {
            //TODO - get current fca info
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;

        if (!isset ($urlArr[1])) {
            $paramImage1 = $fnFcaTask->arraySpliceAssoc($params, array('image1'));
            $image1Arr = $fnFcaTask->uploadPrepare($paramImage1, 25);
            $paramImage2 = $fnFcaTask->arraySpliceAssoc($params, array('image1'));
            $image2Arr = $fnFcaTask->uploadPrepare($paramImage2, 26);

            DbMysql::beginTransaction();
            $isTransaction = true;
            $curDates = new DateTime();
            $fnFcaTask->generateNo();
            $fnTask->createNew(5, $fnFcaTask->fcaTaskNo);
            $fnTask->set($fnTask->wflTaskNew['taskId']);
            $paramFcaTask = $fnFcaTask->arraySpliceAssoc($params, array('assetGroupId', 'fcaTaskBlock', 'fcaTaskLevel', 'fcaTaskArea', 'fcaTaskAssetNo', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation'));
            $paramFcaTask['fcaTaskImage1'] = $fnFcaTask->uploadSave($image1Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_1');
            $paramFcaTask['fcaTaskImage2'] = $fnFcaTask->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_2');
            $fnFcaTask->insert($paramFcaTask, $fnTask->transactionId);
            $fnFcaTaskSection->register($fnFcaTask->fcaTaskId);

            //TODO - submit task
            //TODO - send email fca_task
            //TODO - send notification
            //TODO - save audit
            DbMysql::commit();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
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
        $fnFcaTask->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnFcaTask->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);