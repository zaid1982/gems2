<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/AttGroup.php';

$apiName = 'att_group';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnAttGroup = new AttGroup();

try {
    DbMysql::connect();
    $fnAttGroup->checkJwt(apache_request_headers());
    $fnAttGroup->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnAttGroup->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnAttGroup->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            //TODO - get all att Group
        } else if ($urlArr[1] === 'ref') {
            $result = $fnAttGroup->getRef();
        } else if ($urlArr[1] === 'site') {
            if (isset ($urlArr[2])) {
                $result = $fnAttGroup->getAttSite(intval($urlArr[2]));
            } else {
                $result = $fnAttGroup->getAttSiteList();
            }
        } else if ($urlArr[1] === 'by_site') {
            $result = $fnAttGroup->getList(intval($urlArr[2]));
        } else if ($urlArr[1] === 'chart_site' && isset ($urlArr[2]) && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnAttGroup->getChartsSite(intval($urlArr[2]), intval($urlArr[3]), intval($urlArr[4]));
        } else if ($urlArr[1] === 'supervisor') {
            $result = $fnAttGroup->getSupervisorList();
        } else {
            $fnAttGroup->set(intval($urlArr[1]));
            $result = $fnAttGroup->attGroup;
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        DbMysql::beginTransaction();
        $isTransaction = true;

        $fnAttGroup->insert($params['data'], $params['maps']);
        $fnAttGroup->updateVersion(27);
        $fnAttGroup->saveAudit(182, $fnAttGroup->attGroupName);
        $formData['errmsg'] = str_replace('__', $fnAttGroup->attGroupName, Constant::$attGroup['add']);

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

        if ($urlArr[1] === 'activate_site') {
            $siteId = intval($urlArr[2]);
            $fnAttGroup->setSiteName($siteId);
            $fnAttGroup->activate($siteId);
            $fnAttGroup->updateVersion(6);
            $fnAttGroup->saveAudit(180, $fnAttGroup->siteName);
            $formData['errmsg'] = str_replace('__', $fnAttGroup->siteName, Constant::$attGroup['siteEnabled']);
        }
        else if ($urlArr[1] === 'deactivate_site') {
            $siteId = intval($urlArr[2]);
            $fnAttGroup->setSiteName($siteId);
            $fnAttGroup->deactivate($siteId);
            $fnAttGroup->updateVersion(6);
            $fnAttGroup->saveAudit(181, $fnAttGroup->siteName);
            $formData['errmsg'] = str_replace('__', $fnAttGroup->siteName, Constant::$attGroup['siteDisabled']);
        }
        else {
            $fnAttGroup->update(intval($urlArr[1]), $params['data'], $params['maps']);
            $fnAttGroup->updateVersion(27);
            $fnAttGroup->saveAudit(190, $fnAttGroup->attGroupName);
            $formData['errmsg'] = str_replace('__', $fnAttGroup->attGroupName, Constant::$attGroup['update']);
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
        $fnAttGroup->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnAttGroup->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);