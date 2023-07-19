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

$fnMain = new AttGroup();

try {
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        if ($urlArr[1] === 'ref') {
            $result = $fnMain->getRef();
        } else if ($urlArr[1] === 'site') {
            if (isset ($urlArr[2])) {
                $result = $fnMain->getAttSite(intval($urlArr[2]));
            } else {
                $result = $fnMain->getAttSiteList();
            }
        } else if ($urlArr[1] === 'by_site') {
            $result = $fnMain->getList(intval($urlArr[2]));
        } else if ($urlArr[1] === 'chart_site' && isset ($urlArr[2]) && isset($urlArr[3]) && isset($urlArr[4])) {
            $result = $fnMain->getChartsSite(intval($urlArr[2]), intval($urlArr[3]), intval($urlArr[4]));
        } else if ($urlArr[1] === 'supervisor') {
            $result = $fnMain->getSupervisorList();
        } else {
            $fnMain->set(intval($urlArr[1]));
            $result = $fnMain->attGroup;
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        DbMysql::beginTransaction();
        $isTransaction = true;

        $fnMain->insert($params['data'], $params['maps']);
        $fnMain->updateVersion(27);
        $fnMain->saveAudit(182, $fnMain->attGroupName);
        $formData['errmsg'] = str_replace('__', $fnMain->attGroupName, Constant::$attGroup['add']);

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
            $fnMain->setSiteName($siteId);
            $fnMain->activate($siteId);
            $fnMain->updateVersion(6);
            $fnMain->saveAudit(180, $fnMain->siteName);
            $formData['errmsg'] = str_replace('__', $fnMain->siteName, Constant::$attGroup['siteEnabled']);
        }
        else if ($urlArr[1] === 'deactivate_site') {
            $siteId = intval($urlArr[2]);
            $fnMain->setSiteName($siteId);
            $fnMain->deactivate($siteId);
            $fnMain->updateVersion(6);
            $fnMain->saveAudit(181, $fnMain->siteName);
            $formData['errmsg'] = str_replace('__', $fnMain->siteName, Constant::$attGroup['siteDisabled']);
        }
        else {
            $fnMain->update(intval($urlArr[1]), $params['data'], $params['maps']);
            $fnMain->updateVersion(27);
            $fnMain->saveAudit(190, $fnMain->attGroupName);
            $formData['errmsg'] = str_replace('__', $fnMain->attGroupName, Constant::$attGroup['update']);
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