<?php
require_once __DIR__ . '/_require_auth.php';

/**
 * WO Export — Back-office Excel generator for Work Order data.
 *
 * Usage:
 *   GET  ?action=sites          → returns { success, data: [ {site_id, site_name, client_name} ] }
 *   GET  ?action=clients        → returns { success, data: [ {client_id, client_name} ] }
 *   POST ?action=export         → streams .xlsx download
 *        body: siteIds (comma-separated), dateFrom, dateTo
 *   POST ?action=count          → returns { success, count }
 *        body: siteIds, dateFrom, dateTo
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/wo_export_errors.log');

// Allow CORS for same-origin maintenance tools
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- DB connection (same pattern as other maintenance tools) ---
require_once(__DIR__ . '/../api/class/Constant.php');

$config = [
    'host'     => Constant::$dbHost,
    'username' => Constant::$dbUserName,
    'password' => Constant::$dbUserPassword,
    'database' => Constant::$dbName,
    'charset'  => 'utf8mb4',
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
} catch (PDOException $e) {
    sendJson(false, null, 'Database connection failed: ' . $e->getMessage());
}

// --- Routing ---
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'clients':
        handleClients();
        break;
    case 'sites':
        handleSites();
        break;
    case 'count':
        handleCount();
        break;
    case 'export':
        handleExport();
        break;
    default:
        sendJson(false, null, 'Invalid action. Use: clients, sites, count, export');
}

// ====================================================================
// Handlers
// ====================================================================

function handleClients()
{
    global $pdo;
    $stmt = $pdo->query("
        SELECT client_id, client_name
        FROM   cli_client
        WHERE  client_status = '1'
        ORDER BY client_name
    ");
    sendJson(true, $stmt->fetchAll());
}

function handleSites()
{
    global $pdo;
    $clientId = $_GET['clientId'] ?? '';
    $sql = "
        SELECT s.site_id, s.site_name, s.site_code, c.client_name
        FROM   cli_site s
        JOIN   cli_client c ON c.client_id = s.client_id
        WHERE  s.site_status = '1'
    ";
    $params = [];
    if (!empty($clientId)) {
        $sql .= " AND s.client_id = ?";
        $params[] = $clientId;
    }
    $sql .= " ORDER BY c.client_name, s.site_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    sendJson(true, $stmt->fetchAll());
}

function handleCount()
{
    global $pdo;
    $siteIds  = parseSiteIds($_POST['siteIds'] ?? '');
    $dateFrom = $_POST['dateFrom'] ?? '';
    $dateTo   = $_POST['dateTo']   ?? '';

    if (empty($siteIds)) {
        return sendJson(false, null, 'Please select at least one site.');
    }

    list($where, $params) = buildWhere($siteIds, $dateFrom, $dateTo);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM wo_task wt WHERE $where");
    $stmt->execute($params);
    $row = $stmt->fetch();
    sendJson(true, ['count' => (int) $row['cnt']]);
}

function handleExport()
{
    global $pdo;

    // Load PhpSpreadsheet
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        return sendJson(false, null, 'PhpSpreadsheet not installed. Run composer install.');
    }
    require_once $autoloadPath;

    $siteIds  = parseSiteIds($_POST['siteIds'] ?? '');
    $dateFrom = $_POST['dateFrom'] ?? '';
    $dateTo   = $_POST['dateTo']   ?? '';

    if (empty($siteIds)) {
        return sendJson(false, null, 'Please select at least one site.');
    }

  try {

    // --- Reference lookups ---
    $siteMap     = buildMap($pdo, "SELECT site_id, site_name FROM cli_site");
    $userMap     = buildMap($pdo, "SELECT user_id, CONCAT(user_first_name,' ',user_last_name) AS name FROM sys_user", 'user_id', 'name');
    $severityMap = buildMap($pdo, "SELECT severity_id, severity_name FROM ref_severity", 'severity_id', 'severity_name');
    $statusMap   = buildMap($pdo, "SELECT status_id, status_desc FROM ref_status", 'status_id', 'status_desc');
    $ppmGroupMap = buildMap($pdo, "SELECT ppm_group_id, ppm_group_name FROM ppm_group", 'ppm_group_id', 'ppm_group_name');

    $woTypeMap = [
        0 => '',
        1 => 'Complaint',
        2 => 'PPM',
        3 => 'Ad-hoc',
        4 => 'Request',
    ];

    // --- Fetch data ---
    list($where, $params) = buildWhere($siteIds, $dateFrom, $dateTo);
    $stmt = $pdo->prepare("
        SELECT wt.*,
               a.asset_no,
               (SELECT GROUP_CONCAT(wa.user_id) FROM wo_task_assist wa WHERE wa.wo_task_id = wt.wo_task_id) AS assistants
        FROM   wo_task wt
        LEFT JOIN ast_asset a ON a.asset_id = wt.asset_id
        WHERE  $where
        ORDER BY wt.wo_task_time_created DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // --- Build spreadsheet ---
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Work Orders');

    // Headers
    $headers = [
        'A' => 'Date',
        'B' => 'Request No.',
        'C' => 'Work Order No.',
        'D' => 'Site',
        'E' => 'Location',
        'F' => 'WO Type',
        'G' => 'Complaint Type / Trade',
        'H' => 'Complainant',
        'I' => 'Complaint Description',
        'J' => 'Severity',
        'K' => 'Repair Description',
        'L' => 'Rating',
        'M' => 'Status',
        'N' => 'Assigned To (PIC)',
        'O' => 'Fixed By',
        'P' => 'Assigned By',
        'Q' => 'Verified By',
        'R' => 'Assistants',
        'S' => 'Complaint Time',
        'T' => 'Respond Duration',
        'U' => 'Assigned Time',
        'V' => 'Executed Time',
        'W' => 'Verified Time',
        'X' => 'KPI Response',
        'Y' => 'KPI Mitigation',
        'Z' => 'Asset No.',
    ];

    $col = 'A';
    foreach ($headers as $letter => $title) {
        $sheet->setCellValue($letter . '1', $title);
    }

    // Style header row
    $headerRange = 'A1:Z1';
    $headerStyle = $sheet->getStyle($headerRange);
    $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
    $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('4338CA');
    $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $headerStyle->getBorders()->getAllBorders()
        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Column widths
    $widths = [
        'A'=>18,'B'=>18,'C'=>20,'D'=>25,'E'=>25,'F'=>14,'G'=>25,'H'=>22,
        'I'=>40,'J'=>14,'K'=>40,'L'=>10,'M'=>18,'N'=>22,'O'=>22,'P'=>22,
        'Q'=>22,'R'=>30,'S'=>20,'T'=>18,'U'=>20,'V'=>20,'W'=>20,'X'=>14,
        'Y'=>14,'Z'=>18,
    ];
    foreach ($widths as $c => $w) {
        $sheet->getColumnDimension($c)->setWidth($w);
    }

    $sheet->freezePane('A2');

    // Data rows
    $rowNum = 2;
    foreach ($rows as $r) {
        $woNo = ($r['wo_task_is_wr'] === '1' && ($r['wo_task_wr_confirm'] ?? '') !== '1') ? '-' : ($r['wo_task_no'] ?? '');
        $severity = $severityMap[(int)($r['wo_task_severity'] ?? 0)] ?? '';
        $status   = $statusMap[(int)($r['wo_task_status'] ?? 0)] ?? '';
        $woType   = $woTypeMap[(int)($r['wo_task_type'] ?? 0)] ?? '';
        $trade    = $ppmGroupMap[(int)($r['ppm_group_id'] ?? 0)] ?? '';
        $site     = $siteMap[(int)($r['site_id'] ?? 0)] ?? '';
        $assignedTo = $userMap[(int)($r['wo_task_assigned_to'] ?? 0)] ?? '';
        $fixedBy    = $userMap[(int)($r['wo_task_fixed_by'] ?? 0)] ?? '';
        $assignedBy = $userMap[(int)($r['wo_task_assigned_by'] ?? 0)] ?? '';
        $verifiedBy = $userMap[(int)($r['wo_task_verified_by'] ?? 0)] ?? '';
        $createdBy  = $userMap[(int)($r['wo_task_created_by'] ?? 0)] ?? '';

        // Resolve assistants
        $assistantNames = '';
        if (!empty($r['assistants'])) {
            $ids = array_filter(array_map('intval', explode(',', $r['assistants'])));
            $names = [];
            foreach ($ids as $id) {
                if (isset($userMap[$id])) $names[] = $userMap[$id];
            }
            $assistantNames = implode(', ', $names);
        }

        // Respond duration
        $timeCreated  = $r['wo_task_time_created'] ?? '';
        $timeAssigned = $r['wo_task_time_assigned'] ?? '';
        $timeWrChecked = $r['wo_task_time_wr_checked'] ?? '';
        $respondTo = ($r['wo_task_is_wr'] === '1') ? $timeWrChecked : $timeAssigned;
        $respondDuration = timeDiffStr($timeCreated, $respondTo);

        // KPI Response
        $kpiResponse = '';
        $respondMinutes = timeDiffMinutes($timeCreated, $respondTo);
        $sev = (int)($r['wo_task_severity'] ?? 0);
        if ($respondMinutes !== null) {
            if ($sev === 5 || $sev === 4) {
                $kpiResponse = $respondMinutes <= 15 ? 'Success' : 'Fail';
            } elseif ($sev === 3) {
                $kpiResponse = $respondMinutes <= 30 ? 'Success' : 'Fail';
            }
        }

        // KPI Mitigation
        $kpiMitigation = '';
        $timeExecuted = $r['wo_task_time_executed'] ?? '';
        $mitigateHours = timeDiffHours($timeCreated, $timeExecuted);
        if ($mitigateHours !== null) {
            if ($sev === 5) {
                $kpiMitigation = $mitigateHours <= 3 ? 'Success' : 'Fail';
            } elseif ($sev === 4) {
                $kpiMitigation = $mitigateHours <= 24 ? 'Success' : 'Fail';
            } elseif ($sev === 3) {
                $kpiMitigation = $mitigateHours <= 168 ? 'Success' : 'Fail';
            }
        }

        $sheet->setCellValue("A{$rowNum}", fmtDate($timeCreated));
        $sheet->setCellValue("B{$rowNum}", $r['wo_task_request_no'] ?? '');
        $sheet->setCellValue("C{$rowNum}", $woNo);
        $sheet->setCellValue("D{$rowNum}", $site);
        $sheet->setCellValue("E{$rowNum}", $r['wo_task_location'] ?? '');
        $sheet->setCellValue("F{$rowNum}", $woType);
        $sheet->setCellValue("G{$rowNum}", $trade);
        $sheet->setCellValue("H{$rowNum}", $createdBy);
        $sheet->setCellValue("I{$rowNum}", $r['wo_task_complaint'] ?? '');
        $sheet->setCellValue("J{$rowNum}", $severity);
        $sheet->setCellValue("K{$rowNum}", $r['wo_task_repair_desc'] ?? '');
        $sheet->setCellValue("L{$rowNum}", !empty($r['wo_task_rate']) ? $r['wo_task_rate'] . ' / 5' : '');
        $sheet->setCellValue("M{$rowNum}", $status);
        $sheet->setCellValue("N{$rowNum}", $assignedTo);
        $sheet->setCellValue("O{$rowNum}", $fixedBy);
        $sheet->setCellValue("P{$rowNum}", $assignedBy);
        $sheet->setCellValue("Q{$rowNum}", $verifiedBy);
        $sheet->setCellValue("R{$rowNum}", $assistantNames);
        $sheet->setCellValue("S{$rowNum}", fmtDate($timeCreated));
        $sheet->setCellValue("T{$rowNum}", $respondDuration);
        $sheet->setCellValue("U{$rowNum}", fmtDate($timeAssigned));
        $sheet->setCellValue("V{$rowNum}", fmtDate($timeExecuted));
        $sheet->setCellValue("W{$rowNum}", fmtDate($r['wo_task_time_verified'] ?? ''));
        $sheet->setCellValue("X{$rowNum}", $kpiResponse);
        $sheet->setCellValue("Y{$rowNum}", $kpiMitigation);
        $sheet->setCellValue("Z{$rowNum}", $r['asset_no'] ?? '');

        // Alternating row colour
        if ($rowNum % 2 === 0) {
            $sheet->getStyle("A{$rowNum}:Z{$rowNum}")
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F1F5F9');
        }

        $rowNum++;
    }

    // Auto-filter
    if ($rowNum > 2) {
        $sheet->setAutoFilter("A1:Z" . ($rowNum - 1));
    }

    // --- Summary sheet ---
    $summarySheet = $spreadsheet->createSheet();
    $summarySheet->setTitle('Export Info');
    $summarySheet->setCellValue('A1', 'Export Date');
    $summarySheet->setCellValue('B1', date('Y-m-d H:i:s'));
    $summarySheet->setCellValue('A2', 'Date Range');
    $summarySheet->setCellValue('B2', ($dateFrom ?: 'All') . ' → ' . ($dateTo ?: 'All'));
    $summarySheet->setCellValue('A3', 'Total Records');
    $summarySheet->setCellValue('B3', count($rows));
    $summarySheet->setCellValue('A4', 'Sites');
    // List selected sites
    $siteNames = [];
    foreach ($siteIds as $sid) {
        $siteNames[] = $siteMap[(int) $sid] ?? "Site #{$sid}";
    }
    $summarySheet->setCellValue('B4', implode(', ', $siteNames));
    $summarySheet->getColumnDimension('A')->setWidth(18);
    $summarySheet->getColumnDimension('B')->setWidth(60);
    $summarySheet->getStyle('A1:A4')->getFont()->setBold(true);

    $spreadsheet->setActiveSheetIndex(0);

    // --- Write to temp file first (avoids output buffer conflicts) ---
    $filename = 'wo_export_' . date('Ymd_His') . '.xlsx';
    $tmpFile  = '/tmp/wo_export_' . uniqid() . '.tmp.xlsx';

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tmpFile);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    $pdo = null;

    $fileSize = filesize($tmpFile);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: max-age=0');
    readfile($tmpFile);
    unlink($tmpFile);
    exit;

  } catch (\Throwable $ex) {
    error_log('WO Export error: ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine());
    return sendJson(false, null, 'Export failed: ' . $ex->getMessage());
  }
}

// ====================================================================
// Helpers
// ====================================================================

function parseSiteIds(string $raw): array
{
    return array_values(array_filter(array_map('intval', explode(',', $raw))));
}

function buildWhere(array $siteIds, string $dateFrom, string $dateTo): array
{
    $conditions = [];
    $params     = [];

    // Site filter
    $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
    $conditions[] = "wt.site_id IN ($placeholders)";
    $params = array_merge($params, $siteIds);

    // Date range
    if (!empty($dateFrom)) {
        $conditions[] = "DATE(wt.wo_task_time_created) >= ?";
        $params[] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $conditions[] = "DATE(wt.wo_task_time_created) <= ?";
        $params[] = $dateTo;
    }

    $where = implode(' AND ', $conditions);
    return [$where, $params];
}

function buildMap(PDO $pdo, string $sql, string $keyCol = null, string $valCol = null): array
{
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    if (empty($rows)) return [];
    if ($keyCol === null) {
        $cols = array_keys($rows[0]);
        $keyCol = $cols[0];
        $valCol = $cols[1] ?? $cols[0];
    }
    $map = [];
    foreach ($rows as $row) {
        $map[(int) $row[$keyCol]] = $row[$valCol];
    }
    return $map;
}

function fmtDate(?string $dt): string
{
    if (empty($dt)) return '';
    return str_replace('-', '/', $dt);
}

function timeDiffStr(?string $from, ?string $to): string
{
    if (empty($from) || empty($to)) return '';
    try {
        $d1 = new DateTime($from);
        $d2 = new DateTime($to);
        $diff = $d1->diff($d2);
        $parts = [];
        if ($diff->days > 0) $parts[] = $diff->days . 'd';
        if ($diff->h > 0)    $parts[] = $diff->h . 'h';
        if ($diff->i > 0)    $parts[] = $diff->i . 'm';
        return implode(' ', $parts) ?: '0m';
    } catch (\Exception $e) {
        return '';
    }
}

function timeDiffMinutes(?string $from, ?string $to): ?float
{
    if (empty($from) || empty($to)) return null;
    try {
        $d1 = new DateTime($from);
        $d2 = new DateTime($to);
        return abs($d2->getTimestamp() - $d1->getTimestamp()) / 60;
    } catch (\Exception $e) {
        return null;
    }
}

function timeDiffHours(?string $from, ?string $to): ?float
{
    if (empty($from) || empty($to)) return null;
    try {
        $d1 = new DateTime($from);
        $d2 = new DateTime($to);
        return abs($d2->getTimestamp() - $d1->getTimestamp()) / 3600;
    } catch (\Exception $e) {
        return null;
    }
}

function sendJson(bool $success, $data = null, string $error = '')
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
