<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/WasteBase.php';
require_once 'class/WasteReference.php';
require_once 'class/WasteBalance.php';
require_once 'class/WasteTransaction.php';
require_once 'class/WasteGeneration.php';
require_once 'class/WasteOpeningBalance.php';
require_once 'class/WasteDashboard.php';
require_once 'class/WasteReport.php';

$apiName = 'waste';
$isTransaction = false;
$formData = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';
date_default_timezone_set('Asia/Kuala_Lumpur');

$fnMain = new WasteReference();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = ' . $requestMethod . ', URL = ' . $_SERVER['REQUEST_URI']);

    $requestUri = $_SERVER['REQUEST_URI'];
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: $requestUri;
    $pathParts = explode('/', trim($requestPath, '/'));
    $idx = -1;
    foreach ($pathParts as $i => $seg) {
        if ($seg === 'waste.php' || $seg === 'waste') {
            $idx = $i;
            break;
        }
    }
    if ($idx === -1) {
        throw new Exception('[line: ' . __LINE__ . '] - Wrong Request');
    }
    $urlArr = array_slice($pathParts, $idx);

    DbMysql::connect();
    if (isset($urlArr[1]) && $urlArr[1] === 'ext') {
        array_shift($urlArr);
    } else {
        $fnMain->checkJwt(apache_request_headers());
    }

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

    $ref = $fnMain;
    $txn = new WasteTransaction();
    $txn->adopt($fnMain);
    $gen = new WasteGeneration();
    $gen->adopt($fnMain);
    $ob = new WasteOpeningBalance();
    $ob->adopt($fnMain);
    $dash = new WasteDashboard();
    $dash->adopt($fnMain);
    $rpt = new WasteReport();
    $rpt->adopt($fnMain);
    $bal = new WasteBalance();
    $bal->adopt($fnMain);

    $write = in_array($requestMethod, array('POST', 'PUT', 'DELETE'), true);
    if ($write) {
        DbMysql::beginTransaction();
        $isTransaction = true;
    }

    if ($requestMethod === 'GET') {
        if ($resource === 'lookups') {
            $result = $ref->listLookups(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === '' || $resource === 'site') {
            $result = $ref->listSites();
        } else if ($resource === 'sw_code') {
            $siteId = isset($_GET['siteId']) ? intval($_GET['siteId']) : 0;
            $activeOnly = !empty($_GET['activeOnly']);
            $result = $ref->listSwCodes($siteId ?: null, $activeOnly);
        } else if ($resource === 'premise') {
            $result = $id ? $ref->getPremise($id) : $ref->listSites();
        } else if ($resource === 'profile') {
            $result = $ref->listProfiles(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'location') {
            $result = $ref->listLocations(isset($_GET['siteId']) ? intval($_GET['siteId']) : null, $_GET['type'] ?? null);
        } else if ($resource === 'ref_value') {
            $result = $ref->listRefValues($_GET['type'] ?? null, isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'transaction' && $action === 'duplicate_check') {
            $result = $txn->duplicateCheck($_GET);
        } else if ($resource === 'transaction') {
            $result = $id ? $txn->get($id) : $txn->list($_GET);
        } else if ($resource === 'generation' && $action === 'pending_summary') {
            $result = $gen->pendingSummary($_GET);
        } else if ($resource === 'generation') {
            $result = $id ? $gen->get($id) : $gen->list($_GET);
        } else if ($resource === 'balance') {
            $siteId = $bal->resolveSiteId($_GET['siteId'] ?? null);
            $swCodeId = intval($_GET['swCodeId'] ?? 0);
            $asAt = $bal->normalizeDate($_GET['asAt'] ?? '') ?: date('Y-m-d');
            $type = strtoupper($_GET['type'] ?? 'P');
            $qtyKg = isset($_GET['qtyKg']) ? floatval($_GET['qtyKg']) : $bal->toKg($_GET['qty'] ?? 0, $_GET['unit'] ?? 'KG');
            $excludeId = isset($_GET['excludeId']) ? intval($_GET['excludeId']) : null;
            $result = $bal->preview($siteId, $swCodeId, $asAt, $type, $qtyKg, $excludeId);
        } else if ($resource === 'opening_balance') {
            $result = $ob->list(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'dashboard') {
            $result = $dash->get($_GET);
        } else if ($resource === 'report') {
            $result = $id ? $rpt->get($id) : $rpt->list(isset($_GET['siteId']) ? intval($_GET['siteId']) : null);
        } else if ($resource === 'me') {
            $result = array(
                'userId' => $fnMain->userId,
                'siteId' => $fnMain->userSite,
                'isAdmin' => $fnMain->isAdministrator(),
                'canRecord' => $fnMain->canRecord(),
                'canAmendFinal' => $fnMain->canAmendFinal(),
                'canOpening' => $fnMain->canOpening(),
                'canReport' => $fnMain->canReport(),
                'canSetup' => $fnMain->canSetup(),
                'canGenerate' => $fnMain->canRecord(),
                'canDispose' => $fnMain->canRecord()
            );
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'POST') {
        if ($resource === 'premise') {
            $result = $ref->savePremise($body);
            $formData['errmsg'] = Constant::$waste['premiseSaved'];
        } else if ($resource === 'profile') {
            $result = $ref->saveProfile($body);
            $formData['errmsg'] = Constant::$waste['profileSaved'];
        } else if ($resource === 'location') {
            $result = $ref->saveLocation($body);
            $formData['errmsg'] = Constant::$waste['locationSaved'];
        } else if ($resource === 'ref_value') {
            $result = $ref->saveRefValue($body);
            $formData['errmsg'] = Constant::$waste['refSaved'];
        } else if ($resource === 'transaction' && $action === 'submit') {
            $result = $txn->submit($body);
            $formData['errmsg'] = Constant::$waste['submitted'];
        } else if ($resource === 'transaction' && $id && $action === 'finalise') {
            $result = $txn->finalise($id);
            $formData['errmsg'] = Constant::$waste['finalised'];
        } else if ($resource === 'transaction' && $id && $action === 'cancel') {
            $result = $txn->cancel($id, $body);
            $formData['errmsg'] = Constant::$waste['cancelled'];
        } else if ($resource === 'transaction' && $id && $action === 'amend') {
            $result = $txn->amend($id, $body);
            $formData['errmsg'] = Constant::$waste['amended'];
        } else if ($resource === 'transaction' && $id && $action === 'document') {
            $result = $txn->addDocument($id, $body);
            $formData['errmsg'] = Constant::$waste['documentAdded'];
        } else if ($resource === 'transaction') {
            $result = $txn->create($body);
            $formData['errmsg'] = Constant::$waste['draftSaved'];
        } else if ($resource === 'generation' && $id && $action === 'dispose') {
            $result = $gen->dispose($id, $body);
            $formData['errmsg'] = Constant::$waste['disposed'];
        } else if ($resource === 'generation') {
            $result = $gen->create($body);
            $formData['errmsg'] = Constant::$waste['generated'];
        } else if ($resource === 'opening_balance') {
            $result = $ob->save($body);
            $formData['errmsg'] = Constant::$waste['openingSaved'];
        } else if ($resource === 'report' && $action === 'preview') {
            $result = $rpt->preview($body);
        } else if ($resource === 'report' && $id && $action === 'submission') {
            $result = $rpt->recordSubmission($id, $body);
            $formData['errmsg'] = Constant::$waste['submissionSaved'];
        } else if ($resource === 'report') {
            $result = $rpt->generate($body);
            $formData['errmsg'] = Constant::$waste['reportGenerated'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong POST Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'PUT') {
        if ($resource === 'premise') {
            $result = $ref->savePremise($body);
            $formData['errmsg'] = Constant::$waste['premiseSaved'];
        } else if ($resource === 'profile' && $id) {
            $result = $ref->saveProfile($body, $id);
            $formData['errmsg'] = Constant::$waste['profileSaved'];
        } else if ($resource === 'location' && $id) {
            $result = $ref->saveLocation($body, $id);
            $formData['errmsg'] = Constant::$waste['locationSaved'];
        } else if ($resource === 'ref_value' && $id) {
            $result = $ref->saveRefValue($body, $id);
            $formData['errmsg'] = Constant::$waste['refSaved'];
        } else if ($resource === 'transaction' && $id) {
            $result = $txn->updateDraft($id, $body);
            $formData['errmsg'] = Constant::$waste['draftSaved'];
        } else if ($resource === 'generation' && $id) {
            $result = $gen->update($id, $body);
            $formData['errmsg'] = Constant::$waste['generationUpdated'];
        } else if ($resource === 'opening_balance') {
            $result = $ob->save($body, $id ?: null);
            $formData['errmsg'] = Constant::$waste['openingSaved'];
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong PUT Request');
        }
        DbMysql::commit();
        $formData['result'] = $result;
        $formData['success'] = true;
    } else if ($requestMethod === 'DELETE') {
        if ($resource === 'profile' && $id) {
            $ref->deactivateProfile($id);
            $formData['errmsg'] = Constant::$waste['profileRemoved'];
        } else if ($resource === 'location' && $id) {
            $ref->deactivateLocation($id);
            $formData['errmsg'] = Constant::$waste['locationRemoved'];
        } else if ($resource === 'ref_value' && $id) {
            $ref->deactivateRefValue($id);
            $formData['errmsg'] = Constant::$waste['refRemoved'];
        } else if ($resource === 'transaction' && $id && $action === 'document' && $subId) {
            $result = $txn->removeDocument($id, $subId);
            $formData['errmsg'] = Constant::$waste['documentRemoved'];
        } else if ($resource === 'generation' && $id) {
            $result = $gen->delete($id, $body);
            $formData['errmsg'] = Constant::$waste['generationDeleted'];
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
