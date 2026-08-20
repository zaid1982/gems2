<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');

$apiBasePath = dirname(__DIR__) . '/api';
chdir($apiBasePath);

require_once $apiBasePath . '/library/constant.php';
require_once $apiBasePath . '/function/db.php';
require_once $apiBasePath . '/function/f_general.php';
require_once $apiBasePath . '/function/f_login.php';
require_once $apiBasePath . '/function/f_wo.php';
require_once $apiBasePath . '/function/f_task.php';
require_once $apiBasePath . '/function/f_email.php';
require_once $apiBasePath . '/pdf/tcpdf_include.php';
require_once $apiBasePath . '/pdf/wo_jkr.php';

$rawNos = trim((string) ($_POST['wo_task_no'] ?? $_GET['wo_task_no'] ?? ''));
$rawIds = trim((string) ($_POST['wo_task_id'] ?? $_GET['wo_task_id'] ?? ''));

if ($rawNos === '' && $rawIds === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'wo_task_no or wo_task_id is required']);
    exit;
}

$fn_general = new Class_general();
$fn_pdf_wo = new Class_pdf_wo_jkr();
$fn_pdf_wo->__set('fn_general', $fn_general);

Class_db::getInstance()->db_connect();

$results = [];
$nos = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', $rawNos) ?: [])));
$ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $rawIds) ?: [])));

$tasks = [];
foreach ($nos as $no) {
    $row = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_no' => $no), null, 0);
    if (!empty($row['wo_task_id'])) {
        $tasks[] = $row;
    } else {
        $results[] = ['wo_task_no' => $no, 'success' => false, 'error' => 'WO not found'];
    }
}
foreach ($ids as $id) {
    $row = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id' => $id), null, 0);
    if (!empty($row['wo_task_id'])) {
        $tasks[] = $row;
    } else {
        $results[] = ['wo_task_id' => $id, 'success' => false, 'error' => 'WO not found'];
    }
}

$seen = [];
foreach ($tasks as $task) {
    $woTaskId = (int) $task['wo_task_id'];
    if (isset($seen[$woTaskId])) {
        continue;
    }
    $seen[$woTaskId] = true;

    try {
        Class_db::getInstance()->db_beginTransaction();
        $fn_pdf_wo->__set('woTaskId', $woTaskId);
        $created = $fn_pdf_wo->create_pdf();
        Class_db::getInstance()->db_commit();
        $results[] = [
            'success' => true,
            'wo_task_id' => $woTaskId,
            'wo_task_no' => $task['wo_task_no'],
            'pdf_id' => $created['pdfId'] ?? null,
        ];
    } catch (Throwable $e) {
        try {
            Class_db::getInstance()->db_rollback();
        } catch (Throwable $ignored) {
        }
        $results[] = [
            'success' => false,
            'wo_task_id' => $woTaskId,
            'wo_task_no' => $task['wo_task_no'] ?? '',
            'error' => $e->getMessage(),
        ];
    }
}

$ok = !in_array(false, array_column($results, 'success'), true);
echo json_encode(['success' => $ok, 'results' => $results], JSON_UNESCAPED_UNICODE);
