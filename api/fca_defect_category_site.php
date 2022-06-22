<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaDefectCategorySite.php';

$apiName = 'fca_defect_category_site';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnDefectCategorySite = new FcaDefectCategorySite();

try {
    DbMysql::connect();
    $fnDefectCategorySite->checkJwt(apache_request_headers());
    $fnDefectCategorySite->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnDefectCategorySite->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnDefectCategorySite->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            $result = $fnDefectCategorySite->getList();
        } else if ($urlArr[1] === 'ref') {
            $result = $fnDefectCategorySite->getRef();
        } else if ($urlArr[1] === 'grouped') {
            $result = $fnDefectCategorySite->getListGrouped();
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
        $fnDefectCategorySite->set($params);
        $fnDefectCategorySite->insert($params);
        $fnDefectCategorySite->saveAudit(202, $fnDefectCategorySite->siteName.' - '.$fnDefectCategorySite->fcaDefectCategoryName);
        DbMysql::commit();
        $errMsg = str_replace('___', $fnDefectCategorySite->siteName, Constant::$fcaDefectCategorySite['add']);
        $formData['errmsg'] = str_replace('__', $fnDefectCategorySite->fcaDefectCategoryName, $errMsg);

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
        $fnDefectCategorySite->set($columns);
        $fnDefectCategorySite->delete($columns);
        $fnDefectCategorySite->saveAudit(203, $fnDefectCategorySite->siteName.' - '.$fnDefectCategorySite->fcaDefectCategoryName);
        DbMysql::commit();
        $errMsg = str_replace('___', $fnDefectCategorySite->siteName, Constant::$fcaDefectCategorySite['delete']);
        $formData['errmsg'] = str_replace('__', $fnDefectCategorySite->fcaDefectCategoryName, $errMsg);

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
        $fnDefectCategorySite->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnDefectCategorySite->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
