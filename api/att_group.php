<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/AttGroup.php';

$apiName = 'att_group';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnAttGroup = new AttGroup();

try {
    DbMysql::connect();
    $fnAttGroup->checkJwt(apache_request_headers());
    $fnAttGroup->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnAttGroup->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod);
    $urlArr = $fnAttGroup->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            $result = $fnAttGroup->getAttGroupRef();
        }
        else if ($urlArr[1] === 'site') {
            if (isset ($urlArr[2])) {
                //Todo getAttSite($urlArr[2])
            } else {
                $result = $fnAttGroup->getAttSiteList();
            }
        }
        else {
            //Todo getAttGroup($urlArr[1])
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

        if ($urlArr[1] === 'activate_site') {
            $siteId = intval($urlArr[2]);
            $fnAttGroup->setSiteName($siteId);
            $fnAttGroup->activateAttSite($siteId);
            $fnAttGroup->updateVersion(6);
            
            $fnAttGroup->saveAudit(180, $fnAttGroup->siteName);
            $formData['errmsg'] = str_replace('__', $fnAttGroup->siteName, Constant::$attGroupSuc['enabled']);
        }
        else if ($urlArr[1] === 'deactivate_site') {
            $siteId = intval($urlArr[2]);
            $fnAttGroup->setSiteName($siteId);
            $fnAttGroup->deactivateAttSite($siteId);
            $fnAttGroup->updateVersion(6);
            $fnAttGroup->saveAudit(181, $fnAttGroup->siteName);
            $formData['errmsg'] = str_replace('__', $fnAttGroup->siteName, Constant::$attGroupSuc['disabled']);
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