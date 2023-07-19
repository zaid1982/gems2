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

$fnMain = new AttTransaction();

try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (isset($urlArr[1]) && $urlArr[1] === 'monthly' && isset($urlArr[2]) && $urlArr[2] === 'site' && isset($urlArr[3]) && isset($urlArr[4]) && isset($urlArr[5])) {
            $result = $fnMain->getMonthlySheet(intval($urlArr[3]), 'g.siteId', intval($urlArr[4]), intval($urlArr[5]));
        } else if (isset($urlArr[1]) && $urlArr[1] === 'monthly' && isset($urlArr[2]) && $urlArr[2] === 'group' && isset($urlArr[3]) && isset($urlArr[4]) && isset($urlArr[5])) {
            $result = $fnMain->getMonthlySheet(intval($urlArr[3]), 'g.att_group_id', intval($urlArr[4]), intval($urlArr[5]));
        } else if (isset($urlArr[1]) && $urlArr[1] === 'daily' && isset($urlArr[2]) && isset($urlArr[3])) {
            $result = $fnMain->getDailyGroup(intval($urlArr[2]), $urlArr[3]);
        } else if (isset($urlArr[1]) && $urlArr[1] === 'mobile' && isset($urlArr[2]) && $urlArr[2] === 'main_info') {
            $result = $fnMain->getMobileInfo();
        } else if (isset($urlArr[1]) && $urlArr[1] === 'mobile' && isset($urlArr[2]) && $urlArr[2] === 'calendar_daily_info' && isset($urlArr[3])) {
            $result = $fnMain->getMobileCalendarInfo($urlArr[3]);
        } else if (isset($urlArr[1]) && $urlArr[1] === 'mobile' && isset($urlArr[2]) && $urlArr[2] === 'calendar_dot' && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnMain->getMobileCalendarDot(intval($urlArr[3]), intval($urlArr[4]));
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
            $fnMain->checkIn(intval($urlArr[2]), $params);
            $fnMain->saveAudit(211, $fnMain->attParticipantName.', '.$fnMain->attTransactionDate);
            $formData['errmsg'] = Constant::$attTransaction['checkIn'];
        }
        else if ($urlArr[1] === 'check_out' && isset($urlArr[2]) && is_numeric($urlArr[2])) {
            $fnMain->checkOut(intval($urlArr[2]), $params);
            $fnMain->saveAudit(212, $fnMain->attParticipantName.', '.$fnMain->attTransactionDate);
            $formData['errmsg'] = Constant::$attTransaction['checkOut'];
        }
        else if ($urlArr[1] === 'reschedule' && $urlArr[2] === 'site' && isset($urlArr[3]) && is_numeric($urlArr[3]) && isset($urlArr[4]) && is_numeric($urlArr[4]) && isset($urlArr[5]) && is_numeric($urlArr[5])) {
            $fnMain->rescheduleSite(intval($urlArr[3]), 'g.site_id', intval($urlArr[4]), intval($urlArr[5]));
            $siteName = $fnMain->getSiteName(intval($urlArr[3]));
            $fnMain->saveAudit(214, 'Site '.$siteName.', year '.$urlArr[4].', month '.$urlArr[5]);
            $errorMsg = str_replace('_1', $siteName, Constant::$attTransaction['rescheduleSite']);
            $errorMsg = str_replace('_2', $urlArr[4], $errorMsg);
            $formData['errmsg'] = str_replace('_3', $urlArr[5], $errorMsg);
        }
        else if ($urlArr[1] === 'reschedule' && $urlArr[2] === 'group' && isset($urlArr[3]) && is_numeric($urlArr[3]) && isset($urlArr[4]) && is_numeric($urlArr[4]) && isset($urlArr[5]) && is_numeric($urlArr[5])) {
            $fnMain->rescheduleSite(intval($urlArr[3]), 'g.att_group_id', intval($urlArr[4]), intval($urlArr[5]));
            $attGroupName = $fnMain->getAttGroupName(intval($urlArr[3]));
            $fnMain->saveAudit(214, 'Group '.$attGroupName.', year '.$urlArr[4].', month '.$urlArr[5]);
            $errorMsg = str_replace('_1', $attGroupName, Constant::$attTransaction['rescheduleGroup']);
            $errorMsg = str_replace('_2', $urlArr[4], $errorMsg);
            $formData['errmsg'] = str_replace('_3', $urlArr[5], $errorMsg);
        }
        else if (is_numeric($urlArr[1])) {
            $fnMain->update(intval($urlArr[1]), $params);
            $fnMain->saveAudit(213, $fnMain->attParticipantName.', '.$fnMain->attTransactionDate);
            $errorMsg = str_replace('_1', $fnMain->attParticipantName, Constant::$attTransaction['update']);
            $formData['errmsg'] = str_replace('_2', $fnMain->attTransactionDate, $errorMsg);
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
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
