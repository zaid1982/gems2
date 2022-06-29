<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaZone.php';

$apiName = 'fca_zone';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnFcaZone = new FcaZone();

try {
    DbMysql::connect();
    $fnFcaZone->checkJwt(apache_request_headers());
    $fnFcaZone->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnFcaZone->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnFcaZone->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            $result = $fnFcaZone->getList();
        } else if ($urlArr[1] === 'ref') {
            $result = $fnFcaZone->getRef();
        } else if (is_numeric($urlArr[1])) {
            $result = $fnFcaZone->get(intval($urlArr[1]));
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

        $fnFcaZone->insert($params);
        $fnFcaZone->updateVersion(33);
        $fnFcaZone->saveAudit(196, $fnFcaZone->fcaZoneName);
        $formData['errmsg'] = str_replace('__', $fnFcaZone->fcaZoneName, Constant::$fcaZone['add']);

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
        $fnFcaZone->update(intval($urlArr[1]), $params);
        $fnFcaZone->updateVersion(33);
        $fnFcaZone->saveAudit(197, $fnFcaZone->fcaZoneName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnFcaZone->fcaZoneName, Constant::$fcaZone['update']);

        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 1');
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnFcaZone->set(intval($urlArr[1]));
        $fnFcaZone->delete();
        $fnFcaZone->updateVersion(33);
        $fnFcaZone->saveAudit(198, $fnFcaZone->fcaZoneName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnFcaZone->fcaZoneName, Constant::$fcaZone['delete']);

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
        $fnFcaZone->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnFcaZone->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
