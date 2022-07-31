<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/AttParticipant.php';
require_once 'class/AttTransaction.php';

$apiName = 'att_participant';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnAttParticipant = new AttParticipant();

try {
    DbMysql::connect();
    $fnAttParticipant->checkJwt(apache_request_headers());
    $fnAttParticipant->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnAttParticipant->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnAttParticipant->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnAttTransaction = new AttTransaction($fnAttParticipant->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'by_site' && isset ($urlArr[2]) && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnAttParticipant->getListSite(intval($urlArr[2]), intval($urlArr[3]), intval($urlArr[4]));
        } else if (is_numeric($urlArr[1])) {
            $result = $fnAttParticipant->get(intval($urlArr[1]));
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        DbMysql::beginTransaction();
        $isTransaction = true;

        $fnAttParticipant->insert($params);
        $now = new DateTime();
        $year = intval($now->format('Y'));
        $month = intval($now->format('n'));
        $fnAttTransaction->insertMonthly($fnAttParticipant->attParticipantId, $year, $month);
        $fnAttParticipant->saveAudit(191, $fnAttParticipant->attParticipantName);
        $errorMsg = str_replace('_1', $fnAttParticipant->attParticipantName, Constant::$attParticipant['add']);
        $formData['errmsg'] = str_replace('_2', $fnAttParticipant->attGroupName, $errorMsg);

        DbMysql::commit();
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

        $attParticipantOld = $fnAttParticipant->get(intval($urlArr[1]));
        $fnAttParticipant->update($attParticipantOld, $params);
        $attParticipantNew = $fnAttParticipant->get($fnAttParticipant->attParticipantId);
        if ($attParticipantOld['attGroupId'] !== $attParticipantNew['attGroupId'] || $attParticipantOld['attParticipantShiftMode'] !== $attParticipantNew['attParticipantShiftMode']
            || $attParticipantOld['attTypeId'] !== $attParticipantNew['attTypeId'] || $attParticipantOld['attParticipantHoliday'] !== $attParticipantNew['attParticipantHoliday']) {
            $now = new DateTime();
            $year = intval($now->format('Y'));
            $month = intval($now->format('n'));
            $fnAttTransaction->updateMonthly($fnAttParticipant->attParticipantId, $year, $month);
        }
        $fnAttParticipant->saveAudit(192, $fnAttParticipant->attParticipantName);
        $formData['errmsg'] = str_replace('__', $fnAttParticipant->attParticipantName, Constant::$attParticipant['update']);

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
        $fnAttParticipant->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnAttParticipant->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);