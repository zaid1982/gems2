<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');

$woTaskNo = trim((string) ($_GET['wo_task_no'] ?? $_POST['wo_task_no'] ?? ''));
if ($woTaskNo === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'wo_task_no is required']);
    exit;
}

$apiBasePath = dirname(__DIR__) . '/api';
$projectRoot = dirname(__DIR__);
chdir($apiBasePath);

require_once $apiBasePath . '/library/constant.php';
require_once $apiBasePath . '/function/f_general.php';
require_once $apiBasePath . '/function/db.php';

try {
Class_db::getInstance()->db_connect();
$task = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_no' => $woTaskNo), null, 0);
if (empty($task['wo_task_id'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'WO not found']);
    exit;
}

$uploads = Class_db::getInstance()->db_select('mw_wo_upload', array(
    'wo_task_id' => $task['wo_task_id'],
    'sys_upload.upload_status' => '1',
));

$resolved = [];
foreach ($uploads as $upload) {
    $relative = trim(str_replace('\\', '/', (string) $upload['upload_folder']), '/')
        . '/' . $upload['upload_filename'] . '.' . $upload['upload_extension'];
    $candidates = [
        $relative,
        $projectRoot . '/' . $relative,
        $apiBasePath . '/' . $relative,
        $projectRoot . '/api/' . $relative,
    ];
    $found = '';
    foreach ($candidates as $path) {
        if (is_file($path) && is_readable($path)) {
            $found = $path;
            break;
        }
    }
    $resolved[] = [
        'type' => $upload['wo_task_upload_type'],
        'upload_id' => $upload['upload_id'],
        'relative' => $relative,
        'found' => $found !== '',
        'found_path' => $found,
        'size' => $found !== '' ? filesize($found) : null,
    ];
}

$pdf = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id' => $task['pdf_id']), null, 0);
$pdfRelative = '';
$pdfAbsolute = '';
if (!empty($pdf['pdf_filename'])) {
    $pdfRelative = trim((string) $pdf['pdf_folder'], '/') . '/' . $pdf['pdf_filename'];
    $pdfCandidates = [
        $apiBasePath . '/' . $pdfRelative,
        $apiBasePath . '/pdf/wo/' . basename($pdf['pdf_filename']),
        dirname($apiBasePath . '/pdf/dummy') . '/wo/' . explode('/', (string) $pdf['pdf_folder'])[2] . '/' . $pdf['pdf_filename'],
    ];
    $folderCode = (string) floor(((int) $task['wo_task_id']) / 1000);
    $pdfCandidates[] = $apiBasePath . '/pdf/wo/' . $folderCode . '/' . $pdf['pdf_filename'];
    foreach ($pdfCandidates as $path) {
        if (is_file($path)) {
            $pdfAbsolute = $path;
            break;
        }
    }
}

$pdfText = '';
if ($pdfAbsolute !== '') {
    $pdfText = 'pdf_bytes=' . filesize($pdfAbsolute);
}

echo json_encode([
    'success' => true,
    'wo_task_no' => $task['wo_task_no'],
    'wo_task_id' => $task['wo_task_id'],
    'wo_task_is_pdf' => $task['wo_task_is_pdf'],
    'complaint' => $task['wo_task_complaint'],
    'repair_desc' => $task['wo_task_repair_desc'],
    'uploads' => $resolved,
    'pdf_file' => [
        'relative' => $pdfRelative,
        'found' => $pdfAbsolute !== '',
        'path' => $pdfAbsolute,
        'size' => $pdfAbsolute !== '' ? filesize($pdfAbsolute) : null,
        'mtime' => $pdfAbsolute !== '' ? date('Y-m-d H:i:s', filemtime($pdfAbsolute)) : null,
    ],
    'pdf_text_sample' => $pdfText,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
