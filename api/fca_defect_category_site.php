<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaDefectCategorySite.php';

$apiName = 'fca_defect_category_site';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new FcaDefectCategorySite();

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
            $result = $fnMain->getList();
        } else if ($urlArr[1] === 'ref') {
            $result = $fnMain->getRef();
        } else if ($urlArr[1] === 'grouped') {
            $result = $fnMain->getListGrouped();
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $params = $_POST;
        if (isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->set($params);
        $fnMain->insert($params);
        $fnMain->saveAudit(202, $fnMain->siteName.' - '.$fnMain->fcaDefectCategoryName);
        DbMysql::commit();
        $errMsg = str_replace('___', $fnMain->siteName, Constant::$fcaDefectCategorySite['add']);
        $formData['errmsg'] = str_replace('__', $fnMain->fcaDefectCategoryName, $errMsg);

        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 1');
        }
        if (!isset ($urlArr[2])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 2');
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $columns = array('siteId'=>intval($urlArr[1]), 'fcaDefectCategoryId'=>intval($urlArr[2]));
        $fnMain->set($columns);
        $fnMain->delete($columns);
        $fnMain->saveAudit(203, $fnMain->siteName.' - '.$fnMain->fcaDefectCategoryName);
        DbMysql::commit();
        $errMsg = str_replace('___', $fnMain->siteName, Constant::$fcaDefectCategorySite['delete']);
        $formData['errmsg'] = str_replace('__', $fnMain->fcaDefectCategoryName, $errMsg);

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
