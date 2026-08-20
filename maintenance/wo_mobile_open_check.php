<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');

$woTaskNo = trim((string) ($_POST['wo_task_no'] ?? $_GET['wo_task_no'] ?? 'WOIN26081324347'));
$includePdf = (string) ($_POST['include_pdf'] ?? $_GET['include_pdf'] ?? '1') === '1';

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

function timed_call(string $label, callable $fn): array
{
    $started = microtime(true);
    try {
        $value = $fn();
        return [
            'label' => $label,
            'ok' => true,
            'ms' => (int) round((microtime(true) - $started) * 1000),
            'result_type' => is_array($value) ? 'array' : gettype($value),
            'result_count' => is_array($value) ? count($value) : null,
        ];
    } catch (Throwable $e) {
        return [
            'label' => $label,
            'ok' => false,
            'ms' => (int) round((microtime(true) - $started) * 1000),
            'error' => $e->getMessage(),
        ];
    }
}

try {
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_wo = new Class_wo();
    $fn_pdf_wo = new Class_pdf_wo_jkr();
    $fn_general->__set('constant', $constant);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_pdf_wo->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();

    $task = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_no' => $woTaskNo), null, 0);
    if (empty($task['wo_task_id'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'WO not found']);
        exit;
    }

    $woTaskId = (int) $task['wo_task_id'];
    $fn_wo->__set('woTaskId', $woTaskId);

    $calls = [];
    $calls[] = timed_call('section_status (legacy m_wo)', fn () => $fn_wo->get_section_status_m());
    $calls[] = timed_call('section_assign / wo_v2 (screen open)', fn () => $fn_wo->getSectionStatusV2M((string) $woTaskId));
    $calls[] = timed_call('complaint_details (section A)', fn () => $fn_wo->get_complaint_details_m());
    $calls[] = timed_call('repair_images (section C)', fn () => $fn_wo->get_wo_repair_images_m());
    $calls[] = timed_call('execution_info', fn () => $fn_wo->getExecutionInfo((string) $woTaskId));

    if ($includePdf) {
        $calls[] = timed_call('preview_pdf create_pdf (View Form)', function () use ($fn_pdf_wo, $woTaskId) {
            $fn_pdf_wo->__set('woTaskId', $woTaskId);
            return $fn_pdf_wo->create_pdf();
        });
        $calls[] = timed_call('getPdf url', function () use ($fn_general, $task) {
            return $fn_general->getPdf($task['pdf_id']);
        });
    }

    $slow = array_values(array_filter($calls, fn ($row) => !$row['ok'] || ($row['ms'] ?? 0) >= 5000));

    echo json_encode([
        'success' => true,
        'wo_task_no' => $task['wo_task_no'],
        'wo_task_id' => $woTaskId,
        'wo_task_status' => $task['wo_task_status'],
        'wo_task_is_pdf' => $task['wo_task_is_pdf'],
        'pdf_id' => $task['pdf_id'],
        'calls' => $calls,
        'would_hang' => $slow !== [],
        'slow_or_failed' => $slow,
        'note' => 'Mobile screen waits on section_assign. View Form always runs preview_pdf/create_pdf. http.get has no timeout, so a hung API keeps the spinner.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
