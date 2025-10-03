<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/Space.php';

$apiName = 'space.php';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new Space();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    // Robust path parsing: accept both /api/space.php and rewrite /space styles
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: $requestUri;
    $pathParts = explode('/', trim($requestPath, '/'));
    $idx = -1;
    foreach ($pathParts as $i => $seg) {
        if ($seg === 'space.php' || $seg === 'space') { $idx = $i; break; }
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
        // Routes: list or detail, and reservation list
        if (!isset($urlArr[1])) {
            // list with optional filters via query string
            $filters = array();
            if (isset($_GET['siteId'])) { $filters['siteId'] = intval($_GET['siteId']); }
            if (isset($_GET['status'])) { $filters['status'] = $_GET['status']; }
            $result = $fnMain->getList($filters);
        } else if ($urlArr[1] === 'ref' && isset($urlArr[2])) {
            // GET /api/space.php/ref/{location|category|type} (legacy alias)
            $ref = strtolower($urlArr[2]);
            if ($ref === 'location') {
                $result = DbMysql::selectAll('ref_space_location', array('spaceLocationStatus'=>1), 0, false, 'spaceLocationName');
            } else if ($ref === 'category') {
                $result = DbMysql::selectAll('ref_space_category', array('spaceCategoryStatus'=>1), 0, false, 'spaceCategoryName');
            } else if ($ref === 'type') {
                $result = DbMysql::selectAll('ref_space_type', array('spaceTypeStatus'=>1), 0, false, 'spaceTypeName');
            } else {
                throw new Exception('[line: ' . __LINE__ . '] - Unknown reference requested');
            }
        } else if ($urlArr[1] === 'refs' && isset($urlArr[2])) {
                // GET /api/space.php/refs/{location|category|type}
                $ref = strtolower($urlArr[2]);
                if ($ref === 'location') {
                    $result = DbMysql::selectAll('ref_space_location', array('spaceLocationStatus'=>1), 0, false, 'spaceLocationName');
                } else if ($ref === 'category') {
                    $result = DbMysql::selectAll('ref_space_category', array('spaceCategoryStatus'=>1), 0, false, 'spaceCategoryName');
                } else if ($ref === 'type') {
                    $where = array('spaceTypeStatus'=>1);
                    if (isset($_GET['spaceCategoryId'])) { $where['spaceCategoryId'] = intval($_GET['spaceCategoryId']); }
                    $result = DbMysql::selectAll('ref_space_type', $where, 0, false, 'spaceTypeName');
                } else {
                    throw new Exception('[line: ' . __LINE__ . '] - Unknown reference requested');
                }
        } else if ($urlArr[1] === 'my_reservations') {
            // GET /api/space.php/my_reservations?from=...&to=...&status=...&siteId=...
            $from = isset($_GET['from']) ? new DateTime($_GET['from']) : null;
            $to = isset($_GET['to']) ? new DateTime($_GET['to']) : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $siteId = isset($_GET['siteId']) ? intval($_GET['siteId']) : null;
            $result = $fnMain->getMyReservations($from, $to, $status, $siteId);
        } else if (isset($urlArr[2]) && $urlArr[2] === 'reservation') {
            $spaceId = intval($urlArr[1]);
            $from = isset($_GET['from']) ? new DateTime($_GET['from']) : null;
            $to = isset($_GET['to']) ? new DateTime($_GET['to']) : null;
            $result = $fnMain->getReservations($spaceId, $from, $to);
        } else {
            $result = $fnMain->get(intval($urlArr[1]));
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        if (!isset($urlArr[1])) {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $result = $fnMain->create($bodyParams);
            DbMysql::commit();
            $formData['errmsg'] = str_replace('__', $result['spaceName'] ?? '', Constant::$space['add']);
        } else if (isset($urlArr[2]) && $urlArr[2] === 'reservation') {
            DbMysql::beginTransaction();
            $isTransaction = true;
            $result = $fnMain->reserve(intval($urlArr[1]), $bodyParams);
            DbMysql::commit();
            $formData['errmsg'] = Constant::$spaceReservation['reserve'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('PUT' === $requestMethod) {
        $bodyParams = json_decode(file_get_contents("php://input"), true);
        DbMysql::beginTransaction();
        $isTransaction = true;
        if (isset($urlArr[1]) && isset($urlArr[2]) && $urlArr[1] === 'reservation' && $urlArr[3] === 'cancel') {
            // PUT /api/space.php/reservation/{id}/cancel
            $reservationId = intval($urlArr[2]);
            $reason = isset($bodyParams['reason']) ? $bodyParams['reason'] : null;
            $result = $fnMain->cancelReservation($reservationId, $reason);
            $formData['errmsg'] = Constant::$spaceReservation['cancel'];
        } else if (isset($urlArr[1])) {
            $spaceId = intval($urlArr[1]);
            $result = $fnMain->update($spaceId, $bodyParams);
            $formData['errmsg'] = str_replace('__', $result['spaceName'] ?? '', Constant::$space['update']);
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETE Request');
        }
        $spaceId = intval($urlArr[1]);
        DbMysql::beginTransaction();
        $isTransaction = true;
        $space = $fnMain->get($spaceId);
        $fnMain->delete($spaceId);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $space['spaceName'] ?? '', Constant::$space['delete']);
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
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
