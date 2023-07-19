<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaTask.php';
require_once 'class/FcaTaskSection.php';
require_once 'class/WflTask.php';
require_once 'class/Email.php';
require_once 'class/Noti.php';

$apiName = 'fca_task';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new FcaTask();
try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnTask = new WflTask($fnMain->userId, Constant::$isLogged);
    $fnFcaTaskSection = new FcaTaskSection($fnMain->userId, Constant::$isLogged);
    $fnEmail = new Email($fnMain->userId, Constant::$isLogged);
    $fnNoti = new Noti($fnMain->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            $result = $fnMain->getList();
        } else if (is_numeric($urlArr[1])) {
            $result = $fnMain->get(intval($urlArr[1]));
        } else if ($urlArr[1] === 'progress' && isset ($urlArr[2])) {
            $result = $fnTask->getProgressTimeList(intval($urlArr[2]));
        } else if ($urlArr[1] === 'audit') {
            $result = $fnMain->getObserveList();
        } else if ($urlArr[1] === 'recommend') {
            $result = $fnMain->getRecommendList();
        } else if ($urlArr[1] === 'validate') {
            $result = $fnMain->getValidateList();
        } else if ($urlArr[1] === 'correction') {
            $result = $fnMain->getCorrectionList();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        if (!isset ($urlArr[1])) {
            $image1Arr = $fnMain->uploadPrepare($params['image1'], 25);
            $image2Arr = !empty($params['image2']) ? $fnMain->uploadPrepare($params['image2'], 26) : array();

            DbMysql::beginTransaction();
            $isTransaction = true;
            $curDates = new DateTime();
            $fnMain->generateNo($params['siteId']);
            $fnTask->createNew(5, $fnMain->fcaTaskNo);
            $fnTask->set($fnTask->wflTaskNew['taskId']);

            $paramFcaTask = $fnMain->arraySpliceAssoc($params, array('siteId', 'assetGroupId', 'fcaZoneId', 'fcaTaskArea', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation'));
            $paramFcaTask['fcaTaskImage1'] = $fnMain->uploadSave($image1Arr, 'fca/'.$curDates->format("ym"), $fnMain->fcaTaskNo.'_1');
            if (!empty($image2Arr)) {
                $paramFcaTask['fcaTaskImage2'] = $fnMain->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnMain->fcaTaskNo.'_2');
            }
            $fnMain->insert($paramFcaTask, $fnTask->transactionId);
            $fnFcaTaskSection->register($fnMain->fcaTaskId);

            $fnTask->submit($fnMain->fcaTask['fcaTaskObservation'], 54, 55);
            $fnEmail->prepare($fnMain->userId, 21, array('task_no'=>$fnMain->fcaTaskNo, 'observation'=>$fnMain->fcaTask['fcaTaskObservation']));
            $fnNoti->prepare($fnMain->userId, 22, array('task_no'=>$fnMain->fcaTaskNo));
            $fnMain->saveAudit(193, $fnMain->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitNew'];
        }
        else if ($urlArr[1] === 'recommend' && isset ($urlArr[2])) {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnMain->set(intval($urlArr[2]));
            $fnMain->submitRecommend($params);
            $fnFcaTaskSection->update($fnMain->fcaTaskId, $urlArr[1]);
            $fnTask->setByTransaction($fnMain->fcaTask['transactionId']);
            $fnTask->checkValidity(52);
            $fnTask->submit($fnMain->fcaTask['fcaTaskRecommendation'], 55, 56);
            DbMysql::commit();

            DbMysql::beginTransaction();
            foreach ($fnTask->getRecipients() as $recipient) {
                $fnEmail->prepare($recipient, 22, array('task_no'=>$fnMain->fcaTaskNo, 'recommendation'=>$fnMain->fcaTask['fcaTaskRecommendation']));
                $fnNoti->prepare($recipient, 23, array('task_no'=>$fnMain->fcaTaskNo));
            }
            $fnMain->saveAudit(194, $fnMain->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitRecommend'];
        }
        else if ($urlArr[1] === 'correction' && isset ($urlArr[2])) {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnMain->set(intval($urlArr[2]));
            $fnMain->submitCorrection($params['fcaTaskValidation']);
            $fnFcaTaskSection->update($fnMain->fcaTaskId, $urlArr[1]);
            $fnTask->setByTransaction($fnMain->fcaTask['transactionId']);
            $fnTask->checkValidity(53);
            $fnTask->submit($fnMain->fcaTask['fcaTaskValidation'], 56, 57, 1);
            $fnEmail->prepare($fnMain->fcaTask['fcaTaskCreatedBy'], 23, array('task_no'=>$fnMain->fcaTaskNo, 'validation'=>$fnMain->fcaTask['fcaTaskValidation']));
            $fnNoti->prepare($fnMain->fcaTask['fcaTaskCreatedBy'], 24, array('task_no'=>$fnMain->fcaTaskNo));
            $fnMain->saveAudit(204, $fnMain->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitCorrection'];
        }
        else if ($urlArr[1] === 'validate' && isset ($urlArr[2])) {
            $image1Arr = !empty($params['image1']) ? $fnMain->uploadPrepare($params['image1'], 25) : array();
            $image2Arr = !empty($params['image2']) ? $fnMain->uploadPrepare($params['image2'], 26) : array();

            DbMysql::beginTransaction();
            $isTransaction = true;
            $curDates = new DateTime();
            $fnMain->set(intval($urlArr[2]));
            $paramFcaTask = $fnMain->arraySpliceAssoc($params, array('fcaTaskValidation'));
            if (!empty($image1Arr)) {
                $paramFcaTask['fcaTaskImageRectify1'] = $fnMain->uploadSave($image1Arr, 'fca/'.$curDates->format("ym"), $fnMain->fcaTaskNo.'_1');
            }
            if (!empty($image2Arr)) {
                $paramFcaTask['fcaTaskImageRectify2'] = $fnMain->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnMain->fcaTaskNo.'_2');
            }
            $fnMain->submitValidate($paramFcaTask);
            $fnFcaTaskSection->update($fnMain->fcaTaskId, $urlArr[1]);

            $fnTask->setByTransaction($fnMain->fcaTask['transactionId']);
            $fnTask->checkValidity(53);
            $fnTask->submit($fnMain->fcaTask['fcaTaskValidation'], 56, 19);
            $fnEmail->prepare($fnMain->fcaTask['fcaTaskCreatedBy'], 26, array('task_no'=>$fnMain->fcaTaskNo, 'validation'=>$fnMain->fcaTask['fcaTaskValidation']));
            $fnNoti->prepare($fnMain->fcaTask['fcaTaskCreatedBy'], 25, array('task_no'=>$fnMain->fcaTaskNo));
            $fnMain->saveAudit(195, $fnMain->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitValidate'];
        }
        else if ($urlArr[1] === 'resubmit' && isset ($urlArr[2])) {
            $image1Arr = !empty($params['image1']) ? $fnMain->uploadPrepare($params['image1'], 25) : array();
            $image2Arr = !empty($params['image2']) ? $fnMain->uploadPrepare($params['image2'], 26) : array();

            DbMysql::beginTransaction();
            $isTransaction = true;
            $curDates = new DateTime();
            $fnMain->set(intval($urlArr[2]));
            $paramFcaTask = $fnMain->arraySpliceAssoc($params, array('siteId', 'assetGroupId', 'fcaZoneId', 'fcaTaskArea', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation'));
            if (!empty($image1Arr)) {
                $paramFcaTask['fcaTaskImage1'] = $fnMain->uploadSave($image1Arr, 'fca/' . $curDates->format("ym"), $fnMain->fcaTaskNo . '_1');
            }
            if (!empty($image2Arr)) {
                $paramFcaTask['fcaTaskImage2'] = $fnMain->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnMain->fcaTaskNo.'_2');
            }
            $fnMain->resubmit($paramFcaTask);

            $fnTask->setByTransaction($fnMain->fcaTask['transactionId']);
            $fnTask->checkValidity(54);
            $fnTask->submit($fnMain->fcaTask['fcaTaskObservation'], 54, 55);
            $fnEmail->prepare($fnMain->userId, 21, array('task_no'=>$fnMain->fcaTaskNo, 'observation'=>$fnMain->fcaTask['fcaTaskObservation']));
            $fnNoti->prepare($fnMain->userId, 22, array('task_no'=>$fnMain->fcaTaskNo));
            $fnMain->saveAudit(205, $fnMain->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['resubmit'];
        }
        else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 1');
        }
        DbMysql::beginTransaction();
        $isTransaction = true;

        if ($urlArr[1] === 'exclude_report') {
            $fnMain->set(intval($urlArr[2]));
            $fnMain->updateInReport(1);
            $fnMain->saveAudit(206, $fnMain->fcaTaskNo);
            $formData['errmsg'] = str_replace('__', $fnMain->fcaTaskNo, Constant::$fcaTask['excludeReport']);
        }
        else if ($urlArr[1] === 'include_report') {
            $fnMain->set(intval($urlArr[2]));
            $fnMain->updateInReport(0);
            $fnMain->saveAudit(207, $fnMain->fcaTaskNo);
            $formData['errmsg'] = str_replace('__', $fnMain->fcaTaskNo, Constant::$fcaTask['includeReport']);
        }
        else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }

        DbMysql::commit();
        $formData['result'] = $result;
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
        $fnMain->saveAudit(208, $fnMain->fcaTaskNo);
        $formData['errmsg'] = str_replace('__', $fnMain->fcaTaskNo, Constant::$fcaTask['delete']);

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
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);