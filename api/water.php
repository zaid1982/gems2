<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/EnergyBase.php';
require_once 'class/EnergyCalculator.php';
require_once 'class/WaterReading.php';
require_once 'class/WaterBilling.php';

$apiName = 'water';
$isTransaction = false;
$formData = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';
date_default_timezone_set('Asia/Kuala_Lumpur');

$fnMain = new WaterReading();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = ' . $requestMethod . ', URL = ' . $_SERVER['REQUEST_URI']);

    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim($requestPath, '/'));
    $idx = -1;
    foreach ($pathParts as $i => $seg) {
        if ($seg === 'water.php' || $seg === 'water') {
            $idx = $i;
            break;
        }
    }
    if ($idx === -1) {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request');
    }
    $urlArr = array_slice($pathParts, $idx);

    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());

    $resource = isset($urlArr[1]) ? $urlArr[1] : '';
    $id = isset($urlArr[2]) && is_numeric($urlArr[2]) ? intval($urlArr[2]) : 0;

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body) || empty($body)) {
        $body = $_POST;
        if ($requestMethod === 'PUT' || $requestMethod === 'DELETE') {
            $raw = file_get_contents('php://input');
            $parsed = array();
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                $body = $parsed;
            }
        }
    }
    if (!is_array($body)) {
        $body = array();
    }

    $rdg = $fnMain;
    $billing = new WaterBilling();
    $billing->adopt($fnMain);

    $write = in_array($requestMethod, array('POST', 'PUT', 'DELETE'), true);
    if ($write) {
        DbMysql::beginTransaction();
        $isTransaction = true;
    }

    if ($requestMethod === 'GET') {
        if ($resource === 'me') {
            $result = array(
                'userId' => $fnMain->userId,
                'siteId' => $fnMain->userSite,
                'isAdmin' => $fnMain->isAdministrator(),
                'canRecord' => $fnMain->canRecord(),
                'canSetup' => $fnMain->canSetup(),
                'canView' => $fnMain->canView()
            );
        } else if ($resource === '' || $resource === 'site') {
            $result = $rdg->listSites();
        } else if ($resource === 'meter') {
            $result = $rdg->meters(
                isset($_GET['siteId']) ? intval($_GET['siteId']) : null,
                !empty($_GET['activeOnly'])
            );
        } else if ($resource === 'daily') {
            $result = $rdg->daily($_GET);
        } else if ($resource === 'monthly') {
            $result = $rdg->monthly($_GET);
        } else if ($resource === 'tariff') {
            $result = $billing->listTariff($_GET);
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'POST') {
        if ($resource === 'meter') {
            $result = $rdg->saveMeter($body);
            $formData['errmsg'] = Constant::$water['meterSaved'];
        } else if ($resource === 'reading') {
            $result = $rdg->saveReading($body);
            $formData['errmsg'] = Constant::$water['readingSaved'];
        } else if ($resource === 'tariff') {
            $result = $billing->saveTariff($body);
            $formData['errmsg'] = Constant::$water['tariffSaved'];
        } else if ($resource === 'bill') {
            $saved = $billing->saveActual($body);
            $result = $rdg->monthly($saved);
            $formData['errmsg'] = Constant::$water['billSaved'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'PUT') {
        if ($resource === 'meter' && $id) {
            $result = $rdg->saveMeter($body, $id);
            $formData['errmsg'] = Constant::$water['meterSaved'];
        } else if ($resource === 'tariff') {
            $result = $billing->saveTariff($body);
            $formData['errmsg'] = Constant::$water['tariffSaved'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'DELETE') {
        if ($resource === 'meter' && $id) {
            $rdg->deactivateMeter($id);
            $formData['errmsg'] = Constant::$water['meterRemoved'];
        } else if ($resource === 'reading' && $id) {
            $result = $rdg->deleteReading($id);
            $formData['errmsg'] = Constant::$water['readingRemoved'];
        } else if ($resource === 'tariff') {
            $result = $billing->deleteTariff(array_merge($_GET, $body));
            $formData['errmsg'] = Constant::$water['tariffRemoved'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong DELETE Request');
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
    $formData['error'] = $e->getMessage();
    $marker = strpos($formData['error'], '] -');
    if ($marker !== false) {
        $formData['error'] = substr($formData['error'], $marker + 4);
    }
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
