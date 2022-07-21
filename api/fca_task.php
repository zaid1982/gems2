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

    $fnTask = new WflTask($fnFcaTask->userId, Constant::$isLogged);
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
            $result = $fnFcaTask->getObserveList();
        } else if ($urlArr[1] === 'recommend') {
            $result = $fnFcaTask->getRecommendList();
        } else if ($urlArr[1] === 'validate') {
            $result = $fnFcaTask->getValidateList();
        } else if ($urlArr[1] === 'correction') {
            $result = $fnFcaTask->getCorrectionList();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        if (!isset ($urlArr[1])) {
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
        }
        else if ($urlArr[1] === 'recommend' && isset ($urlArr[2])) {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnFcaTask->set(intval($urlArr[2]));
            $fnFcaTask->submitRecommend($params);
            $fnFcaTaskSection->update($fnFcaTask->fcaTaskId, $urlArr[1]);
            $fnTask->setByTransaction($fnFcaTask->fcaTask['transactionId']);
            $fnTask->checkValidity(52);
            $fnTask->submit($fnFcaTask->fcaTask['fcaTaskRecommendation'], 55, 56);
            DbMysql::commit();

            DbMysql::beginTransaction();
            foreach ($fnTask->getRecipients() as $recipient) {
                $fnEmail->prepare($recipient, 22, array('task_no'=>$fnFcaTask->fcaTaskNo, 'recommendation'=>$fnFcaTask->fcaTask['fcaTaskRecommendation']));
                $fnNoti->prepare($recipient, 23, array('task_no'=>$fnFcaTask->fcaTaskNo));
            }
            $fnFcaTask->saveAudit(194, $fnFcaTask->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitRecommend'];
        }
        else if ($urlArr[1] === 'correction' && isset ($urlArr[2])) {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $fnFcaTask->set(intval($urlArr[2]));
            $fnFcaTask->submitCorrection($params['fcaTaskValidation']);
            $fnFcaTaskSection->update($fnFcaTask->fcaTaskId, $urlArr[1]);
            $fnTask->setByTransaction($fnFcaTask->fcaTask['transactionId']);
            $fnTask->checkValidity(53);
            $fnTask->submit($fnFcaTask->fcaTask['fcaTaskValidation'], 56, 57, 1);
            $fnEmail->prepare($fnFcaTask->fcaTask['fcaTaskCreatedBy'], 23, array('task_no'=>$fnFcaTask->fcaTaskNo, 'validation'=>$fnFcaTask->fcaTask['fcaTaskValidation']));
            $fnNoti->prepare($fnFcaTask->fcaTask['fcaTaskCreatedBy'], 24, array('task_no'=>$fnFcaTask->fcaTaskNo));
            $fnFcaTask->saveAudit(204, $fnFcaTask->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitCorrection'];
        }
        else if ($urlArr[1] === 'validate' && isset ($urlArr[2])) {
            $image1Arr = !empty($params['image1']) ? $fnFcaTask->uploadPrepare($params['image1'], 25) : array();
            $image2Arr = !empty($params['image2']) ? $fnFcaTask->uploadPrepare($params['image2'], 26) : array();

            DbMysql::beginTransaction();
            $isTransaction = true;
            $curDates = new DateTime();
            $fnFcaTask->set(intval($urlArr[2]));
            $paramFcaTask = $fnFcaTask->arraySpliceAssoc($params, array('fcaTaskValidation'));
            if (!empty($image1Arr)) {
                $paramFcaTask['fcaTaskImageRectify1'] = $fnFcaTask->uploadSave($image1Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_1');
            }
            if (!empty($image2Arr)) {
                $paramFcaTask['fcaTaskImageRectify2'] = $fnFcaTask->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_2');
            }
            $fnFcaTask->submitValidate($paramFcaTask);
            $fnFcaTaskSection->update($fnFcaTask->fcaTaskId, $urlArr[1]);

            $fnTask->setByTransaction($fnFcaTask->fcaTask['transactionId']);
            $fnTask->checkValidity(53);
            $fnTask->submit($fnFcaTask->fcaTask['fcaTaskValidation'], 56, 19);
            $fnEmail->prepare($fnFcaTask->fcaTask['fcaTaskCreatedBy'], 26, array('task_no'=>$fnFcaTask->fcaTaskNo, 'validation'=>$fnFcaTask->fcaTask['fcaTaskValidation']));
            $fnNoti->prepare($fnFcaTask->fcaTask['fcaTaskCreatedBy'], 25, array('task_no'=>$fnFcaTask->fcaTaskNo));
            $fnFcaTask->saveAudit(195, $fnFcaTask->fcaTaskNo);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$fcaTask['submitValidate'];
        }
        else if ($urlArr[1] === 'resubmit' && isset ($urlArr[2])) {
            $image1Arr = !empty($params['image1']) ? $fnFcaTask->uploadPrepare($params['image1'], 25) : array();
            $image2Arr = !empty($params['image2']) ? $fnFcaTask->uploadPrepare($params['image2'], 26) : array();

            DbMysql::beginTransaction();
            $isTransaction = true;
            $curDates = new DateTime();
            $fnFcaTask->set(intval($urlArr[2]));
            $paramFcaTask = $fnFcaTask->arraySpliceAssoc($params, array('siteId', 'assetGroupId', 'fcaZoneId', 'fcaTaskArea', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation'));
            if (!empty($image1Arr)) {
                $paramFcaTask['fcaTaskImage1'] = $fnFcaTask->uploadSave($image1Arr, 'fca/' . $curDates->format("ym"), $fnFcaTask->fcaTaskNo . '_1');
            }
            if (!empty($image2Arr)) {
                $paramFcaTask['fcaTaskImage2'] = $fnFcaTask->uploadSave($image2Arr, 'fca/'.$curDates->format("ym"), $fnFcaTask->fcaTaskNo.'_2');
            }
            $fnFcaTask->resubmit($paramFcaTask);

            $fnTask->setByTransaction($fnFcaTask->fcaTask['transactionId']);
            $fnTask->checkValidity(54);
            $fnTask->submit($fnFcaTask->fcaTask['fcaTaskObservation'], 54, 55);
            $fnEmail->prepare($fnFcaTask->userId, 21, array('task_no'=>$fnFcaTask->fcaTaskNo, 'observation'=>$fnFcaTask->fcaTask['fcaTaskObservation']));
            $fnNoti->prepare($fnFcaTask->userId, 22, array('task_no'=>$fnFcaTask->fcaTaskNo));
            $fnFcaTask->saveAudit(205, $fnFcaTask->fcaTaskNo);
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
            $fnFcaTask->set(intval($urlArr[2]));
            $fnFcaTask->updateInReport(1);
            $fnFcaTask->saveAudit(206, $fnFcaTask->fcaTaskNo);
            $formData['errmsg'] = str_replace('__', $fnFcaTask->fcaTaskNo, Constant::$fcaTask['excludeReport']);
        }
        else if ($urlArr[1] === 'include_report') {
            $fnFcaTask->set(intval($urlArr[2]));
            $fnFcaTask->updateInReport(0);
            $fnFcaTask->saveAudit(207, $fnFcaTask->fcaTaskNo);
            $formData['errmsg'] = str_replace('__', $fnFcaTask->fcaTaskNo, Constant::$fcaTask['includeReport']);
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

        $fnFcaTask->set(intval($urlArr[1]));
        $fnFcaTask->delete();
        $fnFcaTask->saveAudit(208, $fnFcaTask->fcaTaskNo);
        $formData['errmsg'] = str_replace('__', $fnFcaTask->fcaTaskNo, Constant::$fcaTask['delete']);

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
        $fnFcaTask->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnFcaTask->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);