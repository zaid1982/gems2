<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';

$apiName = 'space_location.php';
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
    // Robust path parsing: accept both /api/space_location.php and rewrite /space_location styles
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: $requestUri;
    $pathParts = explode('/', trim($requestPath, '/'));
    $idx = -1;
    foreach ($pathParts as $i => $seg) {
        if ($seg === 'space_location.php' || $seg === 'space_location') { $idx = $i; break; }
    }
    if ($idx === -1) { throw new Exception('[line: ' . __LINE__ . '] - Wrong Request'); }
    $urlArr = array_slice($pathParts, $idx);

    DbMysql::connect();
    if (isset($urlArr[1]) && $urlArr[1] === 'ext') {
        array_shift($urlArr);
    } else {
        $fnMain->checkJwt(apache_request_headers());
    }

    if ('GET' === $requestMethod) {
        if (!isset($urlArr[1])) {
            // Optional status filter: 1 active, 0 inactive, '' all
            $where = array();
            if (isset($_GET['status']) && $_GET['status'] !== '') {
                $where['spaceLocationStatus'] = intval($_GET['status']) ? 1 : 0;
            }
            $result = DbMysql::selectAll('ref_space_location', $where, 0, false, 'spaceLocationName');
        } else {
            $id = intval($urlArr[1]);
            $row = DbMysql::select('ref_space_location', array('spaceLocationId'=>$id), true);
            if (empty($row)) { throw new Exception('Location not found', 31); }
            $result = $row;
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        $fnMain->checkMandatoryArray($bodyParams, array('name'));
        $name = trim($bodyParams['name']);
        $desc = isset($bodyParams['description']) ? $bodyParams['description'] : null;
        $status = isset($bodyParams['status']) ? (intval($bodyParams['status']) ? 1 : 0) : 1;

        if ($name === '') { throw new Exception('Location name required', 31); }
        if (DbMysql::count('ref_space_location', array('spaceLocationName'=>$name)) > 0) {
            throw new Exception('Location already exists', 31);
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $id = DbMysql::insert('ref_space_location', array(
            'spaceLocationName' => $name,
            'spaceLocationDesc' => $desc,
            'spaceLocationStatus' => $status,
            'createdBy' => $fnMain->userId,
            'createdAt' => 'NOW()'
        ));
        DbMysql::commit();
        $formData['result'] = DbMysql::select('ref_space_location', array('spaceLocationId'=>$id), true);
        $formData['errmsg'] = 'Location created';
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        if (!isset($urlArr[1])) { throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request'); }
        $id = intval($urlArr[1]);
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        $row = DbMysql::select('ref_space_location', array('spaceLocationId'=>$id), true);
        if (empty($row)) { throw new Exception('Location not found', 31); }

        $update = array();
        if (isset($bodyParams['name'])) {
            $name = trim($bodyParams['name']);
            if ($name === '') { throw new Exception('Location name required', 31); }
            if ($name !== $row['spaceLocationName'] && DbMysql::count('ref_space_location', array('spaceLocationName'=>$name, 'spaceLocationId'=>'<>|'.$id)) > 0) {
                throw new Exception('Location already exists', 31);
            }
            $update['spaceLocationName'] = $name;
        }
        if (array_key_exists('description', $bodyParams)) { $update['spaceLocationDesc'] = $bodyParams['description']; }
        if (array_key_exists('status', $bodyParams)) { $update['spaceLocationStatus'] = intval($bodyParams['status']) ? 1 : 0; }
        if (!empty($update)) { $update['updatedBy'] = $fnMain->userId; $update['updatedAt'] = 'NOW()'; }

        DbMysql::beginTransaction();
        $isTransaction = true;
        if (!empty($update)) { DbMysql::update('ref_space_location', $update, array('spaceLocationId'=>$id)); }
        DbMysql::commit();
        $formData['result'] = DbMysql::select('ref_space_location', array('spaceLocationId'=>$id), true);
        $formData['errmsg'] = 'Location updated';
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset($urlArr[1])) { throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETE Request'); }
        $id = intval($urlArr[1]);
        $row = DbMysql::select('ref_space_location', array('spaceLocationId'=>$id), true);
        if (empty($row)) { throw new Exception('Location not found', 31); }

        DbMysql::beginTransaction();
        $isTransaction = true;
        // Soft deactivate instead of hard delete to preserve FK integrity
        DbMysql::update('ref_space_location', array('spaceLocationStatus'=>0, 'updatedBy'=>$fnMain->userId, 'updatedAt'=>'NOW()'), array('spaceLocationId'=>$id));
        DbMysql::commit();
        $formData['errmsg'] = 'Location deactivated';
        $formData['success'] = true;
    }
    else {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request Method');
    }
    DbMysql::close();
} catch (Exception $e) {
    try {
        if ($isTransaction) { DbMysql::rollback(); }
        DbMysql::close();
    } catch (Exception $ex) { $fnMain->logError('API', $apiName, __LINE__, $e->getMessage()); }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
