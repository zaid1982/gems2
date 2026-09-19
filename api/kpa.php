<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/KpaBase.php';
require_once 'class/KpaCalculator.php';
require_once 'class/KpaStructure.php';
require_once 'class/KpaEvaluation.php';
require_once 'class/KpaReport.php';

$apiName = 'kpa';
$isTransaction = false;
$formData = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';
date_default_timezone_set('Asia/Kuala_Lumpur');

$fnMain = new KpaStructure();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = ' . $requestMethod . ', URL = ' . $_SERVER['REQUEST_URI']);

    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim($requestPath, '/'));
    $idx = -1;
    foreach ($pathParts as $i => $seg) {
        if ($seg === 'kpa.php' || $seg === 'kpa') {
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
    $action = isset($urlArr[3]) ? $urlArr[3] : (isset($urlArr[2]) && !is_numeric($urlArr[2]) ? $urlArr[2] : '');
    $subId = isset($urlArr[4]) && is_numeric($urlArr[4]) ? intval($urlArr[4]) : (isset($urlArr[3]) && is_numeric($urlArr[3]) ? intval($urlArr[3]) : 0);

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

    $str = $fnMain;
    $eval = new KpaEvaluation();
    $eval->adopt($fnMain);
    $rpt = new KpaReport();
    $rpt->adopt($fnMain);

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
                'canAdmin' => $fnMain->canAdmin(),
                'canEntry' => $fnMain->canEntry(),
                'canView' => $fnMain->canView(),
                'assignedPiIds' => $fnMain->assignedPiIds()
            );
        } else if ($resource === '' || $resource === 'site') {
            $result = $str->listSites();
        } else if ($resource === 'config') {
            $result = $str->getConfig(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'group') {
            $result = $str->listGroups(
                isset($_GET['siteId']) ? intval($_GET['siteId']) : null,
                !empty($_GET['activeOnly'])
            );
        } else if ($resource === 'pi' && $id && $action === 'param') {
            $result = $str->listParams($id);
        } else if ($resource === 'pi' && $id) {
            $result = $str->getPi($id);
        } else if ($resource === 'pi') {
            $result = $str->listPi(
                isset($_GET['siteId']) ? intval($_GET['siteId']) : null,
                !empty($_GET['activeOnly'])
            );
        } else if ($resource === 'structure' && $action === 'report') {
            $result = $str->structureReport(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'assignment' && $action === 'users') {
            $result = $str->listEntryUsers(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'assignment') {
            $result = $str->listAssignments(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'evaluation' && $action === 'defaults') {
            $result = $eval->newDefaults($_GET);
        } else if ($resource === 'evaluation' && $id) {
            $result = $eval->get($id);
        } else if ($resource === 'evaluation') {
            $result = $eval->list($_GET);
        } else if ($resource === 'evaluation_pi' && $id) {
            $result = $eval->getPi($id);
        } else if ($resource === 'report' && $action === 'summary') {
            $result = $rpt->summary($_GET);
        } else if ($resource === 'report' && $action === 'history') {
            $result = $rpt->history($_GET);
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'POST') {
        if ($resource === 'config') {
            $result = $str->saveConfig($body);
            $formData['errmsg'] = Constant::$kpa['configSaved'];
        } else if ($resource === 'group') {
            $result = $str->saveGroup($body);
            $formData['errmsg'] = Constant::$kpa['groupSaved'];
        } else if ($resource === 'pi' && $id && $action === 'param') {
            $result = $str->saveParam($id, $body);
            $formData['errmsg'] = Constant::$kpa['paramSaved'];
        } else if ($resource === 'pi' && $id && $action === 'test') {
            $result = $str->testFormula($id, $body);
            $formData['success'] = true;
        } else if ($resource === 'pi') {
            $result = $str->savePi($body);
            $formData['errmsg'] = Constant::$kpa['piSaved'];
        } else if ($resource === 'assignment') {
            $result = $str->saveAssignment($body);
            $formData['errmsg'] = Constant::$kpa['assignmentSaved'];
        } else if ($resource === 'evaluation') {
            $result = $eval->create($body);
            $formData['errmsg'] = Constant::$kpa['evaluationCreated'];
        } else if ($resource === 'evaluation_pi' && $id && $action === 'submit') {
            $result = $eval->submitPi($id);
            $formData['errmsg'] = Constant::$kpa['piSubmitted'];
        } else if ($resource === 'evaluation_pi' && $id && $action === 'reopen') {
            $result = $eval->reopenPi($id, $body);
            $formData['errmsg'] = Constant::$kpa['piReopened'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'PUT') {
        if ($resource === 'config') {
            $result = $str->saveConfig($body);
            $formData['errmsg'] = Constant::$kpa['configSaved'];
        } else if ($resource === 'group' && $id) {
            $result = $str->saveGroup($body, $id);
            $formData['errmsg'] = Constant::$kpa['groupSaved'];
        } else if ($resource === 'pi' && $id && $action === 'param' && $subId) {
            $result = $str->saveParam($id, $body, $subId);
            $formData['errmsg'] = Constant::$kpa['paramSaved'];
        } else if ($resource === 'pi' && $id) {
            $result = $str->savePi($body, $id);
            $formData['errmsg'] = Constant::$kpa['piSaved'];
        } else if ($resource === 'evaluation' && $id) {
            $result = $eval->update($id, $body);
            $formData['errmsg'] = Constant::$kpa['evaluationUpdated'];
        } else if ($resource === 'evaluation_pi' && $id && $action === 'params') {
            $result = $eval->savePiParams($id, $body);
            $formData['errmsg'] = Constant::$kpa['paramsSaved'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'DELETE') {
        if ($resource === 'group' && $id) {
            $str->deactivateGroup($id);
            $formData['errmsg'] = Constant::$kpa['groupRemoved'];
        } else if ($resource === 'pi' && $id && $action === 'param' && $subId) {
            $result = $str->removeParam($id, $subId);
            $formData['errmsg'] = Constant::$kpa['paramRemoved'];
        } else if ($resource === 'pi' && $id) {
            $str->deactivatePi($id);
            $formData['errmsg'] = Constant::$kpa['piRemoved'];
        } else if ($resource === 'assignment' && $id) {
            $result = $str->removeAssignment($id);
            $formData['errmsg'] = Constant::$kpa['assignmentRemoved'];
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
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

echo json_encode($formData);
