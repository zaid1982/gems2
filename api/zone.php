<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/Zone.php';
// Load composer autoload only when needed (for PhpSpreadsheet import/template features)
// Deferred loading to avoid errors when vendor is not installed
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';

$apiName = 'zone';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new Zone();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $urlArr = $fnMain->getUrlArr($_SERVER['REQUEST_URI'], $apiName);

    // Handle template download BEFORE any logging or connection to prevent output contamination
    if ('GET' === $requestMethod && isset($urlArr[1]) && $urlArr[1] === 'template') {
        // Load PhpSpreadsheet for template generation
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }
        // Clean all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        DbMysql::connect();
        $fnMain->checkJwt(apache_request_headers());
        $fnMain->downloadTemplate();
        exit;
    }

    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);

    DbMysql::connect();
    if (isset($urlArr[1]) && $urlArr[1] === 'ext') {
        array_shift($urlArr);
    } else {
        $fnMain->checkJwt(apache_request_headers());
    }


    if ('GET' === $requestMethod) {

        if (!isset ($urlArr[1])) {
            $result = $fnMain->getList();
        } else if ($urlArr[1] === 'ref') {
            $result = $fnMain->getRef();
        } else if ($urlArr[1] === 'list') {
            $result = $fnMain->getList2();
        } else if (is_numeric($urlArr[1])) {
            $result = $fnMain->get(intval($urlArr[1]));
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Wrong GET Request');
        }
        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('POST' === $requestMethod) {
        // Debug logging
        $fnMain->logDebug('API', $apiName, __LINE__, 'POST detected - urlArr: ' . json_encode($urlArr) . ', FILES keys: ' . json_encode(array_keys($_FILES)));
        
        // Check if this is an import request (must check FILES first since FormData empties $_POST)
        if (isset($_FILES['file']) && isset($urlArr[1]) && $urlArr[1] === 'import') {
            // Load PhpSpreadsheet for Excel import
            if (file_exists($vendorAutoload)) {
                require_once $vendorAutoload;
            }
            $fnMain->logDebug('API', $apiName, __LINE__, 'POST import request - File: ' . $_FILES['file']['name']);
            
            DbMysql::beginTransaction();
            $isTransaction = true;

            $importStats = $fnMain->importFromExcel($_FILES['file']);
            $fnMain->updateVersion(38);
            
            // Log audit for successful imports
            if ($importStats['success'] > 0) {
                $fnMain->saveAudit(218, "Imported {$importStats['success']} zones");
            }

            DbMysql::commit();
            
            $result = $importStats;
            $formData['errmsg'] = "Import completed: {$importStats['success']} added, {$importStats['failed']} failed, {$importStats['skipped']} skipped";
            $formData['result'] = $result;
            $formData['success'] = true;
        } else if (!empty($_POST)) {
            // Normal single zone insert (only if $_POST has data)
            $params = $_POST;
            DbMysql::beginTransaction();
            $isTransaction = true;

            $fnMain->insert($params);
            $fnMain->updateVersion(38);
            $fnMain->saveAudit(218, $fnMain->zoneName);
            $formData['errmsg'] = str_replace('__', $fnMain->zoneName, Constant::$zone['add']);

            DbMysql::commit();
            $formData['result'] = $result;
            $formData['success'] = true;
        } else {
            throw new Exception('[line: ' . __LINE__ . '] - Empty POST data or missing file for import');
        }
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
        $fnMain->update(intval($urlArr[1]), $params);
        $fnMain->updateVersion(38);
        $fnMain->saveAudit(219, $fnMain->zoneName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnMain->zoneName, Constant::$zone['update']);

        $formData['result'] = $result;
        $formData['success'] = true;
    }
    else if ('DELETE' === $requestMethod) {
        if (!isset ($urlArr[1])) {
            throw new Exception('[line: ' . __LINE__ . '] - Empty url parameter 1');
        }

        DbMysql::beginTransaction();
        $isTransaction = true;
        $fnMain->set(intval($urlArr[1]));
        $fnMain->delete();
        $fnMain->updateVersion(38);
        $fnMain->saveAudit(220, $fnMain->zoneName);
        DbMysql::commit();
        $formData['errmsg'] = str_replace('__', $fnMain->zoneName, Constant::$zone['delete']);

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
        $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
    }
    $formData['error'] = strpos($e->getMessage(), '] -') ? substr($e->getMessage(), strpos($e->getMessage(), '] -') + 4) : substr($e->getMessage(), strripos($e->getMessage(), '] ') + 2);
    $formData['errmsg'] = $e->getCode() === 31 ? $formData['error'] : Constant::$err['default'];
    $fnMain->logError('API', $apiName, __LINE__, $e->getMessage());
}

// End output buffering and send JSON response
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($formData);
