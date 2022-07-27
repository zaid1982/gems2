<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/AttTransaction.php';

$apiName = 'att_transaction';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnAttTransaction = new AttTransaction();

try {
    DbMysql::connect();
    $fnAttTransaction->checkJwt(apache_request_headers());
    $fnAttTransaction->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnAttTransaction->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnAttTransaction->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (isset($urlArr[1]) && $urlArr[1] === 'monthly' && isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3]) && isset($urlArr[4]) && isset($urlArr[5])) {
            $result = $fnAttTransaction->getMonthlySite(intval($urlArr[3]), intval($urlArr[4]), intval($urlArr[5]));
        } else if (isset($urlArr[1]) && $urlArr[1] === 'mobile' && isset($urlArr[2]) && $urlArr[2] === 'main_info') {
            $result = $fnAttTransaction->getMobileInfo();
        } else if (isset($urlArr[1]) && $urlArr[1] === 'mobile' && isset($urlArr[2]) && $urlArr[2] === 'calendar_daily_info' && isset($urlArr[3])) {
            $result = $fnAttTransaction->getMobileCalendarInfo($urlArr[3]);
        } else if (isset($urlArr[1]) && $urlArr[1] === 'mobile' && isset($urlArr[2]) && $urlArr[2] === 'calendar_dot' && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnAttTransaction->getMobileCalendarDot(intval($urlArr[3]), intval($urlArr[4]));
        } else {
            throw new Exception('[line: ' . __LINE__ . '] Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] Empty url parameter 1');
        }
        DbMysql::beginTransaction();
        $isTransaction = true;

        if ($urlArr[1] === 'check_in' && isset($urlArr[2]) && is_numeric($urlArr[2])) {
            $fnAttTransaction->checkIn(intval($urlArr[2]));
            $fnAttTransaction->saveAudit(211, $fnAttTransaction->attParticipantName.', '.$fnAttTransaction->attTransactionDate);
            $formData['errmsg'] = Constant::$attTransaction['checkIn'];
        }
        else if ($urlArr[1] === 'check_out' && isset($urlArr[2]) && is_numeric($urlArr[2])) {
            $fnAttTransaction->checkOut(intval($urlArr[2]));
            $fnAttTransaction->saveAudit(212, $fnAttTransaction->attParticipantName.', '.$fnAttTransaction->attTransactionDate);
            $formData['errmsg'] = Constant::$attTransaction['checkOut'];
        }
        else if (is_numeric($urlArr[1])) {
            $fnAttTransaction->update(intval($urlArr[1]), $params);
            $fnAttTransaction->saveAudit(212, $fnAttTransaction->attParticipantName.', '.$fnAttTransaction->attTransactionDate);
            $errorMsg = str_replace('_1', $fnAttTransaction->attParticipantName, Constant::$attTransaction['update']);
            $formData['errmsg'] = str_replace('_2', $fnAttTransaction->attTransactionDate, $errorMsg);
        }
        else {
            throw new Exception('[line: ' . __LINE__ . '] Wrong PUT Request - '.$urlArr[1]);
        }

        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else {
        throw new Exception('[line: ' . __LINE__ . '] Wrong Request Method');
    }
    DbMysql::close();
} catch (Exception $e) {
    try {
        if ($isTransaction) {
            DbMysql::rollback();
        }
        DbMysql::close();
    } catch (Exception $ex) {
        $fnAttTransaction->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnAttTransaction->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
