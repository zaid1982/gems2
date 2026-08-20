<?php
/**
 * Home dashboard Excel export with SSE progress.
 *
 * Actions:
 *   GET action=generate&module=wo|ppm&...  → text/event-stream progress, then download token
 *   GET action=download&token=...          → streams .xlsx (JWT required)
 */

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo.php';
require_once 'function/f_ppm.php';

$api_name = 'api_dashboard_export';
date_default_timezone_set('Asia/Kuala_Lumpur');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo = new Class_wo();
$fn_ppm = new Class_ppm();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_ppm->__set('constant', $constant);
    $fn_ppm->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();

    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }

    $action = filter_input(INPUT_GET, 'action');
    if ($action === 'download') {
        handleDownload($jwt_data->userId);
        Class_db::getInstance()->db_close();
        exit;
    }

    if ($action !== 'generate') {
        throw new Exception('[' . __LINE__ . '] - Invalid action');
    }

    $module = strtolower(trim((string) filter_input(INPUT_GET, 'module')));
    if ($module !== 'wo' && $module !== 'ppm') {
        throw new Exception('[' . __LINE__ . '] - Invalid module');
    }

    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception('[' . __LINE__ . '] - PhpSpreadsheet not installed');
    }
    require_once $autoloadPath;

    $exportDir = resolveWritableExportDir();
    cleanupOldExports($exportDir);

    // Allow datatable helpers to return larger pages
    $_GET['exportMode'] = '1';

    startSse();
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');
    ignore_user_abort(false);

    sseEmit('progress', array(
        'phase' => 'init',
        'loaded' => 0,
        'total' => 0,
        'remaining' => 0,
        'message' => 'Preparing export…'
    ));

    $clientId = (string) filter_input(INPUT_GET, 'clientId');
    $siteId = (string) filter_input(INPUT_GET, 'siteId');
    $dateFrom = (string) filter_input(INPUT_GET, 'dateFrom');
    $dateTo = (string) filter_input(INPUT_GET, 'dateTo');
    $searchValue = (string) filter_input(INPUT_GET, 'search');
    $orderColumn = filter_input(INPUT_GET, 'orderColumn');
    $orderDir = filter_input(INPUT_GET, 'orderDir');
    $orderColumn = ($orderColumn === null || $orderColumn === '') ? null : intval($orderColumn);
    $orderDir = empty($orderDir) ? 'desc' : $orderDir;

    $pageSize = 500;
    $start = 0;
    $loaded = 0;
    $total = null;
    $excelRows = array();

    // Reference maps for readable export values
    $userMap = $fn_general->getUserFullName();
    $statusMap = $fn_general->getRefStatus();
    $siteMap = buildIdNameMap('cli_site', 'site_id', 'site_desc', 'site_name');
    $ppmGroupMap = $fn_general->getPpmGroupName();

    if ($module === 'wo') {
        $headersRow = array(
            'No', 'Date', 'Request No.', 'Work Order No.', 'Site', 'Location', 'Complaint Type',
            'Complainant', 'Complaint Description', 'Severity', 'Trade', 'PIC Name',
            'Repair Description', 'Rating', 'Progress', 'Zone Code', 'Zone Name',
            'Fixed By', 'Assigned By', 'Verified By', 'Complaint Time', 'Respond Duration',
            'Assigned Time', 'Executed Time', 'Verified Time', 'Assistants'
        );

        while (true) {
            if (connection_aborted()) {
                throw new Exception('[' . __LINE__ . '] - Export cancelled by client');
            }

            $page = $fn_wo->get_wo_dashboard_datatable(
                $clientId, $siteId, $dateFrom, $dateTo,
                $start, $pageSize, $searchValue, $orderColumn, $orderDir, ''
            );

            if ($total === null) {
                $total = intval($page['recordsFiltered']);
                if ($total === 0) {
                    sseEmit('empty', array('message' => 'No records to export for the current filters.'));
                    Class_db::getInstance()->db_close();
                    exit;
                }
            }

            $chunk = isset($page['data']) && is_array($page['data']) ? $page['data'] : array();
            foreach ($chunk as $row) {
                $loaded++;
                $siteIdRow = intval(isset($row['siteId']) ? $row['siteId'] : 0);
                $createdBy = intval(isset($row['woTaskCreatedBy']) ? $row['woTaskCreatedBy'] : 0);
                $assignedTo = intval(isset($row['woTaskAssignedTo']) ? $row['woTaskAssignedTo'] : 0);
                $fixedBy = intval(isset($row['woTaskFixedBy']) ? $row['woTaskFixedBy'] : 0);
                $assignedBy = intval(isset($row['woTaskAssignedBy']) ? $row['woTaskAssignedBy'] : 0);
                $verifiedBy = intval(isset($row['woTaskVerifiedBy']) ? $row['woTaskVerifiedBy'] : 0);
                $ppmGroupId = intval(isset($row['ppmGroupId']) ? $row['ppmGroupId'] : 0);
                $statusId = intval(isset($row['woTaskStatus']) ? $row['woTaskStatus'] : 0);
                $woNo = (isset($row['woTaskNo']) && $row['woTaskNo'] !== '-')
                    ? $row['woTaskNo']
                    : (isset($row['woTaskRequestNo']) ? $row['woTaskRequestNo'] : '');

                $excelRows[] = array(
                    $loaded,
                    substr((string) (isset($row['woTaskTimeCreated']) ? $row['woTaskTimeCreated'] : ''), 0, 10),
                    isset($row['woTaskRequestNo']) ? $row['woTaskRequestNo'] : '',
                    $woNo,
                    isset($siteMap[$siteIdRow]) ? $siteMap[$siteIdRow] : '',
                    isset($row['woTaskLocation']) ? $row['woTaskLocation'] : '',
                    isset($row['woTaskTypeDesc']) ? $row['woTaskTypeDesc'] : '',
                    isset($userMap[$createdBy]) ? $userMap[$createdBy] : '',
                    isset($row['woTaskComplaint']) ? $row['woTaskComplaint'] : '',
                    isset($row['woTaskSeverity']) ? $row['woTaskSeverity'] : '',
                    isset($ppmGroupMap[$ppmGroupId]) ? $ppmGroupMap[$ppmGroupId] : '',
                    isset($userMap[$assignedTo]) ? $userMap[$assignedTo] : '',
                    isset($row['woTaskRepairDesc']) ? $row['woTaskRepairDesc'] : '',
                    isset($row['woTaskRate']) ? $row['woTaskRate'] : '',
                    isset($statusMap[$statusId]) ? $statusMap[$statusId] : '',
                    isset($row['zoneCode']) ? $row['zoneCode'] : '',
                    isset($row['zoneName']) ? $row['zoneName'] : '',
                    isset($userMap[$fixedBy]) ? $userMap[$fixedBy] : '',
                    isset($userMap[$assignedBy]) ? $userMap[$assignedBy] : '',
                    isset($userMap[$verifiedBy]) ? $userMap[$verifiedBy] : '',
                    isset($row['woTaskTimeCreated']) ? $row['woTaskTimeCreated'] : '',
                    isset($row['durationResponded']) ? $row['durationResponded'] : '',
                    isset($row['woTaskTimeAssigned']) ? $row['woTaskTimeAssigned'] : '',
                    isset($row['woTaskTimeExecuted']) ? $row['woTaskTimeExecuted'] : '',
                    isset($row['woTaskTimeVerified']) ? $row['woTaskTimeVerified'] : '',
                    resolveAssistantNames(isset($row['assistants']) ? $row['assistants'] : '', $userMap)
                );
            }

            if (empty($chunk)) {
                $total = $loaded;
            }
            sseEmit('progress', array(
                'phase' => 'loading',
                'loaded' => $loaded,
                'total' => $total,
                'remaining' => max($total - $loaded, 0),
                'message' => 'Loading records…'
            ));

            if (empty($chunk) || $loaded >= $total) {
                break;
            }
            $start += $pageSize;
        }
    } else {
        $assetGroupMap = buildIdNameMap('ast_asset_group', 'asset_group_id', 'asset_group_name');
        $assetCategoryMap = buildIdNameMap('ast_asset_category', 'asset_category_id', 'asset_category_name');
        $assetTypeMap = buildIdNameMap('ast_asset_type', 'asset_type_id', 'asset_type_name');
        $isRoutine = filter_input(INPUT_GET, 'isRoutine');
        if ($isRoutine === null || $isRoutine === '') {
            $isRoutine = '0';
        }

        $headersRow = array(
            'No', 'PPM Task No.', 'Site', 'Schedule Date', 'Frequency', 'Document No.',
            'Asset No.', 'Asset Name', 'Asset Group', 'Asset Category', 'Asset Type',
            'Location Code', 'Location Description', 'Block', 'Level', 'Remark', 'Trade',
            'Executor', 'Reviewer', 'Verifier', 'Time Scan', 'Time Executed', 'Time Reviewed',
            'Time Verified', 'Lateness', 'Min Execution Time', 'Max Execution Time',
            'Execution Status', 'Progress'
        );

        while (true) {
            if (connection_aborted()) {
                throw new Exception('[' . __LINE__ . '] - Export cancelled by client');
            }

            $page = $fn_ppm->get_ppm_dashboard_datatable(
                $clientId, $siteId, $dateFrom, $dateTo,
                $start, $pageSize, $searchValue, $orderColumn, $orderDir, $isRoutine
            );

            if ($total === null) {
                $total = intval($page['recordsFiltered']);
                if ($total === 0) {
                    sseEmit('empty', array('message' => 'No records to export for the current filters.'));
                    Class_db::getInstance()->db_close();
                    exit;
                }
            }

            $chunk = isset($page['data']) && is_array($page['data']) ? $page['data'] : array();
            foreach ($chunk as $row) {
                $loaded++;
                $siteIdRow = intval(isset($row['siteId']) ? $row['siteId'] : 0);
                $assetGroupId = intval(isset($row['assetGroupId']) ? $row['assetGroupId'] : 0);
                $assetCategoryId = intval(isset($row['assetCategoryId']) ? $row['assetCategoryId'] : 0);
                $assetTypeId = intval(isset($row['assetTypeId']) ? $row['assetTypeId'] : 0);
                $ppmGroupId = intval(isset($row['ppmGroupId']) ? $row['ppmGroupId'] : 0);
                $executor = intval(isset($row['executor']) ? $row['executor'] : 0);
                $reviewer = intval(isset($row['reviewer']) ? $row['reviewer'] : 0);
                $verifier = intval(isset($row['verifier']) ? $row['verifier'] : 0);
                $statusId = intval(isset($row['ppmTaskStatus']) ? $row['ppmTaskStatus'] : 0);

                $excelRows[] = array(
                    $loaded,
                    isset($row['ppmTaskNo']) ? $row['ppmTaskNo'] : '',
                    isset($siteMap[$siteIdRow]) ? $siteMap[$siteIdRow] : '',
                    isset($row['ppmTaskStartDate']) ? $row['ppmTaskStartDate'] : '',
                    isset($row['frequency']) ? $row['frequency'] : '',
                    isset($row['documentNo']) ? $row['documentNo'] : '',
                    isset($row['assetNo']) ? $row['assetNo'] : '',
                    isset($row['assetName']) ? $row['assetName'] : '',
                    isset($assetGroupMap[$assetGroupId]) ? $assetGroupMap[$assetGroupId] : '',
                    isset($assetCategoryMap[$assetCategoryId]) ? $assetCategoryMap[$assetCategoryId] : '',
                    isset($assetTypeMap[$assetTypeId]) ? $assetTypeMap[$assetTypeId] : '',
                    isset($row['assetLocationCode']) ? $row['assetLocationCode'] : '',
                    isset($row['assetLocationDesc']) ? $row['assetLocationDesc'] : '',
                    isset($row['assetBlock']) ? $row['assetBlock'] : '',
                    isset($row['assetLevel']) ? $row['assetLevel'] : '',
                    isset($row['ppmTaskRemark']) ? $row['ppmTaskRemark'] : '',
                    isset($ppmGroupMap[$ppmGroupId]) ? $ppmGroupMap[$ppmGroupId] : '',
                    isset($userMap[$executor]) ? $userMap[$executor] : '',
                    isset($userMap[$reviewer]) ? $userMap[$reviewer] : '',
                    isset($userMap[$verifier]) ? $userMap[$verifier] : '',
                    isset($row['ppmTaskTimeStart']) ? $row['ppmTaskTimeStart'] : '',
                    isset($row['ppmTaskTimeServiced']) ? $row['ppmTaskTimeServiced'] : '',
                    isset($row['ppmTaskTimeChecked']) ? $row['ppmTaskTimeChecked'] : '',
                    isset($row['ppmTaskTimeVerified']) ? $row['ppmTaskTimeVerified'] : '',
                    isset($row['lateness']) ? $row['lateness'] : '',
                    isset($row['ppmMinExecTime']) ? $row['ppmMinExecTime'] : '',
                    isset($row['ppmMaxExecTime']) ? $row['ppmMaxExecTime'] : '',
                    isset($row['withinStatus']) ? $row['withinStatus'] : '',
                    isset($statusMap[$statusId]) ? $statusMap[$statusId] : ''
                );
            }

            if (empty($chunk)) {
                $total = $loaded;
            }
            sseEmit('progress', array(
                'phase' => 'loading',
                'loaded' => $loaded,
                'total' => $total,
                'remaining' => max($total - $loaded, 0),
                'message' => 'Loading records…'
            ));

            if (empty($chunk) || $loaded >= $total) {
                break;
            }
            $start += $pageSize;
        }
    }

    Class_db::getInstance()->db_close();

    $total = $loaded;
    sseEmit('progress', array(
        'phase' => 'writing',
        'loaded' => $loaded,
        'total' => $loaded,
        'remaining' => 0,
        'message' => 'Generating Excel file…'
    ));

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($module === 'wo' ? 'Work Orders' : 'PPM');
    $sheet->fromArray($headersRow, null, 'A1');

    $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headersRow)) . '1';
    $headerStyle = $sheet->getStyle($headerRange);
    $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
    $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('4338CA');
    $sheet->freezePane('A2');

    // Write in batches for memory / progress feedback
    $writeBatch = 1000;
    $rowNum = 2;
    $written = 0;
    $totalRows = count($excelRows);
    for ($i = 0; $i < $totalRows; $i += $writeBatch) {
        if (connection_aborted()) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            throw new Exception('[' . __LINE__ . '] - Export cancelled by client');
        }
        $batch = array_slice($excelRows, $i, $writeBatch);
        $sheet->fromArray($batch, null, 'A' . $rowNum);
        $rowNum += count($batch);
        $written += count($batch);
        sseEmit('progress', array(
            'phase' => 'writing',
            'loaded' => $written,
            'total' => $totalRows,
            'remaining' => max($totalRows - $written, 0),
            'message' => 'Writing Excel rows…'
        ));
    }
    unset($excelRows);

    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headersRow));
    if ($rowNum > 2) {
        $sheet->setAutoFilter('A1:' . $lastCol . ($rowNum - 1));
    }

    $token = bin2hex(random_bytes(16));
    $filename = ($module === 'wo' ? 'wo_list_' : 'ppm_list_') . date('Ymd_His') . '.xlsx';
    $tmpPath = $exportDir . '/' . $token . '.xlsx';
    $metaPath = $exportDir . '/' . $token . '.json';

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save($tmpPath);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $writer);

    file_put_contents($metaPath, json_encode(array(
        'userId' => strval($jwt_data->userId),
        'filename' => $filename,
        'module' => $module,
        'createdAt' => time(),
        'expiresAt' => time() + 3600,
        'records' => $totalRows
    )));

    sseEmit('progress', array(
        'phase' => 'done',
        'loaded' => $totalRows,
        'total' => $totalRows,
        'remaining' => 0,
        'message' => 'Export ready'
    ));

    sseEmit('done', array(
        'token' => $token,
        'filename' => $filename,
        'records' => $totalRows,
        'downloadUrl' => 'api/dashboard_export.php?action=download&token=' . urlencode($token)
    ));
    exit;
} catch (Exception $ex) {
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
    try {
        Class_db::getInstance()->db_close();
    } catch (Exception $ignore) {
    }

    $msg = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') !== false
        ? strpos($ex->getMessage(), '] - ') + 4
        : 0);

    if (headers_sent() || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/event-stream') !== false)
        || (isset($_GET['action']) && $_GET['action'] === 'generate')) {
        if (!headers_sent()) {
            startSse();
        }
        sseEmit('error', array('message' => $msg !== '' ? $msg : $constant::ERR_DEFAULT));
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'result' => '',
        'error' => $msg,
        'errmsg' => $constant::ERR_DEFAULT
    ));
    exit;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function startSse()
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', 0);
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    // Prime buffers so first events flush on some Apache setups
    echo ':' . str_repeat(' ', 2048) . "\n\n";
    @ob_flush();
    flush();
}

function sseEmit($event, $data)
{
    echo 'event: ' . $event . "\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    @ob_flush();
    flush();
}

function buildIdNameMap($table, $idCol, $nameCol, $fallbackCol = null)
{
    $map = array();
    $rows = Class_db::getInstance()->db_select($table, array(), null, null, 1);
    foreach ($rows as $row) {
        $id = intval($row[$idCol]);
        $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : '';
        if ($name === '' && $fallbackCol !== null && isset($row[$fallbackCol])) {
            $name = trim((string) $row[$fallbackCol]);
        }
        $map[$id] = $name;
    }
    return $map;
}

function resolveAssistantNames($assistants, $userMap)
{
    if ($assistants === '' || $assistants === null) {
        return '';
    }
    $names = array();
    foreach (explode(',', (string) $assistants) as $idRaw) {
        $id = intval(trim($idRaw));
        if ($id > 0 && isset($userMap[$id]) && $userMap[$id] !== '') {
            $names[] = $userMap[$id];
        }
    }
    return implode(', ', $names);
}

function exportDirCandidates()
{
    return array(
        __DIR__ . '/upload/exports',
        rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'gfm_dashboard_exports'
    );
}

function resolveWritableExportDir()
{
    foreach (exportDirCandidates() as $dir) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                continue;
            }
            @chmod($dir, 0777);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0777);
        }
        if (is_writable($dir)) {
            return $dir;
        }
    }
    throw new Exception('[export] - Export directory is not writable. Fix permissions on api/upload/exports (Apache runs as daemon on XAMPP).');
}

function cleanupOldExports($dir)
{
    $now = time();
    foreach (glob($dir . '/*.{xlsx,json}', GLOB_BRACE) ?: array() as $file) {
        if (is_file($file) && ($now - filemtime($file)) > 3600) {
            @unlink($file);
        }
    }
}

function handleDownload($userId)
{
    $token = preg_replace('/[^a-f0-9]/', '', strtolower((string) filter_input(INPUT_GET, 'token')));
    if ($token === '' || strlen($token) < 16) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'errmsg' => 'Invalid download token'));
        return;
    }

    $xlsxPath = null;
    $metaPath = null;
    foreach (exportDirCandidates() as $dir) {
        $candidateXlsx = $dir . '/' . $token . '.xlsx';
        $candidateMeta = $dir . '/' . $token . '.json';
        if (is_file($candidateXlsx) && is_file($candidateMeta)) {
            $xlsxPath = $candidateXlsx;
            $metaPath = $candidateMeta;
            break;
        }
    }
    if ($xlsxPath === null || $metaPath === null) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'errmsg' => 'Export file not found or expired'));
        return;
    }

    $meta = json_decode(file_get_contents($metaPath), true);
    if (!is_array($meta) || strval($meta['userId']) !== strval($userId)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'errmsg' => 'Not allowed to download this export'));
        return;
    }
    if (isset($meta['expiresAt']) && intval($meta['expiresAt']) < time()) {
        @unlink($xlsxPath);
        @unlink($metaPath);
        http_response_code(410);
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'errmsg' => 'Export expired'));
        return;
    }

    $filename = isset($meta['filename']) ? $meta['filename'] : ('export_' . $token . '.xlsx');
    $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($xlsxPath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($xlsxPath);

    // One-time download
    @unlink($xlsxPath);
    @unlink($metaPath);
}
