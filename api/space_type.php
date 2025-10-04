<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';

$apiName = 'space_type.php';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new General();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);

    $requestUri = $_SERVER['REQUEST_URI'];
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: $requestUri;
    $pathParts = explode('/', trim($requestPath, '/'));
    $idx = -1;
    foreach ($pathParts as $i => $seg) { if ($seg === 'space_type.php' || $seg === 'space_type') { $idx = $i; break; } }
    if ($idx === -1) { throw new Exception('[line: ' . __LINE__ . '] - Wrong Request'); }
    $urlArr = array_slice($pathParts, $idx);

    DbMysql::connect();
    if (isset($urlArr[1]) && $urlArr[1] === 'ext') { array_shift($urlArr); } else { $fnMain->checkJwt(apache_request_headers()); }

    if ('GET' === $requestMethod) {
        if (!isset($urlArr[1])) {
            $where = array();
            if (isset($_GET['status']) && $_GET['status'] !== '') { $where['spaceTypeStatus'] = intval($_GET['status']) ? 1 : 0; }
            if (isset($_GET['spaceCategoryId']) && $_GET['spaceCategoryId'] !== '') { $where['spaceCategoryId'] = intval($_GET['spaceCategoryId']); }
            $result = DbMysql::selectAll('ref_space_type', $where, 0, false, 'spaceTypeName');
        } else {
            $id = intval($urlArr[1]);
            $row = DbMysql::select('ref_space_type', array('spaceTypeId'=>$id), true);
            if (empty($row)) { throw new Exception('Type not found', 31); }
            $result = $row;
        }
        $formData['result'] = $result; $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $body = json_decode(file_get_contents("php://input"), true);
        $fnMain->checkMandatoryArray($body, array('name', 'spaceCategoryId'));
        $name = trim($body['name']); $desc = $body['description'] ?? null; $status = isset($body['status']) ? (intval($body['status']) ? 1 : 0) : 1; $catId = intval($body['spaceCategoryId']);
        if ($name === '') { throw new Exception('Type name required', 31); }
        $cat = DbMysql::select('ref_space_category', array('spaceCategoryId'=>$catId, 'spaceCategoryStatus'=>1), true); if (empty($cat)) { throw new Exception('Invalid category', 31); }
        if (DbMysql::count('ref_space_type', array('spaceCategoryId'=>$catId, 'spaceTypeName'=>$name)) > 0) { throw new Exception('Type already exists for this category', 31); }
        DbMysql::beginTransaction(); $isTransaction = true;
        $id = DbMysql::insert('ref_space_type', array('spaceCategoryId'=>$catId, 'spaceTypeName'=>$name, 'spaceTypeDesc'=>$desc, 'spaceTypeStatus'=>$status, 'createdBy'=>$fnMain->userId, 'createdAt'=>'NOW()'));
        DbMysql::commit();
        $formData['result'] = DbMysql::select('ref_space_type', array('spaceTypeId'=>$id), true);
        $formData['errmsg'] = 'Type created'; $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        if (!isset($urlArr[1])) { throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request'); }
        $id = intval($urlArr[1]); $body = json_decode(file_get_contents("php://input"), true);
        $row = DbMysql::select('ref_space_type', array('spaceTypeId'=>$id), true); if (empty($row)) { throw new Exception('Type not found', 31); }
        $update = array();
        if (isset($body['name'])) { $name = trim($body['name']); if ($name === '') { throw new Exception('Type name required', 31);} if ($name !== $row['spaceTypeName'] && DbMysql::count('ref_space_type', array('spaceCategoryId'=>$row['spaceCategoryId'], 'spaceTypeName'=>$name, 'spaceTypeId'=>'<>|'.$id))>0) { throw new Exception('Type already exists for this category', 31);} $update['spaceTypeName']=$name; }
        if (array_key_exists('description', $body)) { $update['spaceTypeDesc'] = $body['description']; }
        if (array_key_exists('status', $body)) { $update['spaceTypeStatus'] = intval($body['status']) ? 1 : 0; }
        if (array_key_exists('spaceCategoryId', $body)) { $catId = intval($body['spaceCategoryId']); $cat = DbMysql::select('ref_space_category', array('spaceCategoryId'=>$catId, 'spaceCategoryStatus'=>1), true); if (empty($cat)) { throw new Exception('Invalid category', 31);} $update['spaceCategoryId']=$catId; }
        if (!empty($update)) { $update['updatedBy']=$fnMain->userId; $update['updatedAt']='NOW()'; }
        DbMysql::beginTransaction(); $isTransaction = true; if (!empty($update)) { DbMysql::update('ref_space_type', $update, array('spaceTypeId'=>$id)); } DbMysql::commit();
        $formData['result'] = DbMysql::select('ref_space_type', array('spaceTypeId'=>$id), true);
        $formData['errmsg'] = 'Type updated'; $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset($urlArr[1])) { throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETE Request'); }
        $id = intval($urlArr[1]); $row = DbMysql::select('ref_space_type', array('spaceTypeId'=>$id), true); if (empty($row)) { throw new Exception('Type not found', 31); }
        DbMysql::beginTransaction(); $isTransaction = true; DbMysql::update('ref_space_type', array('spaceTypeStatus'=>0, 'updatedBy'=>$fnMain->userId, 'updatedAt'=>'NOW()'), array('spaceTypeId'=>$id)); DbMysql::commit();
        $formData['errmsg'] = 'Type deactivated'; $formData['success'] = true;
    }
    else { throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method'); }

    DbMysql::close();
} catch (Exception $e) {
    try { if ($isTransaction) { DbMysql::rollback(); } DbMysql::close(); } catch (Exception $ex) { $fnMain->logError('API', $apiName, __LINE__, $e->getMessage()); }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
