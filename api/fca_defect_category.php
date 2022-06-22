<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/FcaDefectCategory.php';

$apiName = 'fca_defect_category';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$fnDefectCategory = new FcaDefectCategory();

try {
    DbMysql::connect();
    $fnDefectCategory->checkJwt(apache_request_headers());
    $fnDefectCategory->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnDefectCategory->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    $urlArr = $fnDefectCategory->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    if ('GET' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            $result = $fnDefectCategory->getList();
        } else if ($urlArr[1] === 'ref') {
            $result = $fnDefectCategory->getRef();
        } else if (is_numeric($urlArr[1])) {
            $result = $fnDefectCategory->get(intval($urlArr[1]));
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
        $fnDefectCategory->insert($params);
        $fnDefectCategory->updateVersion(28);
        $fnDefectCategory->saveAudit(199, $fnDefectCategory->fcaDefectCategoryName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnDefectCategory->fcaDefectCategoryName, Constant::$fcaDefectCategory['add']);

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
        $fnDefectCategory->update(intval($urlArr[1]), $params);
        $fnDefectCategory->updateVersion(28);
        $fnDefectCategory->saveAudit(200, $fnDefectCategory->fcaDefectCategoryName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnDefectCategory->fcaDefectCategoryName, Constant::$fcaDefectCategory['update']);

        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 1');
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnDefectCategory->set(intval($urlArr[1]));
        $fnDefectCategory->delete();
        $fnDefectCategory->updateVersion(28);
        $fnDefectCategory->saveAudit(201, $fnDefectCategory->fcaDefectCategoryName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnDefectCategory->fcaDefectCategoryName, Constant::$fcaDefectCategory['delete']);

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
        $fnDefectCategory->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['errmsg'] = $e->getCode() === 31 ? substr($e->getMessage(), strpos($e->getMessage(), '] ') + 2) : Constant::$err['default'];
    $fnDefectCategory->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
