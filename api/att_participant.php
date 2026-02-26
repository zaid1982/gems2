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

$fnMain = new AttParticipant();

try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    $fnAttTransaction = new AttTransaction($fnMain->userId, Constant::$isLogged);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'by_site' && isset ($urlArr[2]) && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnMain->getListSite(intval($urlArr[2]), intval($urlArr[3]), intval($urlArr[4]));
        } else if ($urlArr[1] === 'by_group' && isset ($urlArr[2]) && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnMain->getListGroup(intval($urlArr[2]), intval($urlArr[3]), intval($urlArr[4]));
        } else if (is_numeric($urlArr[1])) {
            $result = $fnMain->get(intval($urlArr[1]));
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

        $fnMain->insert($params);
        $now = new DateTime();
        $year = intval($now->format('Y'));
        $month = intval($now->format('n'));
        $fnAttTransaction->insertMonthly($fnMain->attParticipantId, $year, $month, intval($params['generateType']));
        $fnMain->saveAudit(191, $fnMain->attParticipantName);
        $errorMsg = str_replace('_1', $fnMain->attParticipantName, Constant::$attParticipant['add']);
        $formData['errmsg'] = str_replace('_2', $fnMain->attGroupName, $errorMsg);

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

        $attParticipantOld = $fnMain->get(intval($urlArr[1]));
        $fnMain->update($attParticipantOld, $params);
        $attParticipantNew = $fnMain->get($fnMain->attParticipantId);
        if ($attParticipantOld['attGroupId'] !== $attParticipantNew['attGroupId'] || $attParticipantOld['attParticipantShiftMode'] !== $attParticipantNew['attParticipantShiftMode']
            || $attParticipantOld['attTypeId'] !== $attParticipantNew['attTypeId'] || $attParticipantOld['attParticipantHoliday'] !== $attParticipantNew['attParticipantHoliday']) {
            $now = new DateTime();
            $year = intval($now->format('Y'));
            $month = intval($now->format('n'));
            $transactionCount = DbMysql::count('att_transaction', array('attParticipantId'=>$fnMain->attParticipantId, 'year(attTransactionDate)'=>$year, 'month(attTransactionDate)'=>$month));
            if ($transactionCount === 0) {
                $fnAttTransaction->insertMonthly($fnMain->attParticipantId, $year, $month, intval($params['generateType']));
            } else {
                $fnAttTransaction->updateMonthly($fnMain->attParticipantId, $year, $month, intval($params['generateType']));
            }
        }
        $fnMain->saveAudit(192, $fnMain->attParticipantName);
        $formData['errmsg'] = str_replace('__', $fnMain->attParticipantName, Constant::$attParticipant['update']);

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