<?php
/**
 * Import Data Validation API
 * Provides endpoints to validate and verify imported work order data
 */

// Ensure only JSON is emitted (suppress PHP notices/warnings)
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Create PDO connection
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        // Resolve config file path relative to this script (try common locations)
        $candidatePaths = [
            __DIR__ . '/library/config.ini',            // api/library/config.ini
            dirname(__DIR__) . '/library/config.ini',  // ../library/config.ini
            __DIR__ . '/../library/config.ini',        // api/../library/config.ini
            dirname(__DIR__) . '/config.ini',          // ../config.ini (fallback)
        ];
        $configPath = null;
        foreach ($candidatePaths as $p) {
            if (is_file($p)) { $configPath = $p; break; }
        }
        if ($configPath === null) {
            throw new Exception('Configuration file not found');
        }

        // Parse INI (support both sectioned and flat formats)
        $raw = parse_ini_file($configPath, true, INI_SCANNER_RAW);
        if ($raw === false) {
            throw new Exception('Unable to parse configuration');
        }
        $cfg = $raw['database'] ?? $raw; // prefer [database] section when present

        // Support multiple key styles
        $host = $cfg['DB_HOST'] ?? $cfg['dbhost'] ?? $cfg['host'] ?? '127.0.0.1';
        $name = $cfg['DB_NAME'] ?? $cfg['dbname'] ?? $cfg['database'] ?? '';
        $user = $cfg['DB_USER'] ?? $cfg['username'] ?? $cfg['user'] ?? '';
        $pass = $cfg['DB_PASS'] ?? $cfg['password'] ?? '';

        if ($name === '' || $user === '') {
            throw new Exception('Database credentials are incomplete');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'summary':
            echo json_encode(getSummaryStats());
            break;
            
        case 'sites':
            echo json_encode(getSites());
            break;
            
        case 'batches':
            echo json_encode(getImportBatches());
            break;
            
        case 'imported_wos':
            $siteId = $_GET['site_id'] ?? '';
            $batchId = $_GET['batch_id'] ?? '';
            echo json_encode(getImportedWorkOrders($siteId, $batchId));
            break;
            
        case 'workflow_data':
            echo json_encode(getWorkflowData());
            break;
            
        case 'import_log':
            echo json_encode(getImportLog());
            break;
            
        case 'integrity_check':
            echo json_encode(runIntegrityChecks());
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get summary statistics for imported data
 */
function getSummaryStats() {
    try {
        $pdo = getPDO();
        
        // Total imported work orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM wo_task WHERE wo_task_is_imported = 1");
        $totalImported = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Successful imports from log
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM wo_import_log WHERE status = 'success'");
        $successfulImports = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total batches
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM wo_import_batch");
        $totalBatches = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Last import
        $stmt = $pdo->query("SELECT MAX(imported_at) as last_import FROM wo_import_batch");
        $lastImport = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastImportFormatted = $lastImport['last_import'] ? date('M j, Y H:i', strtotime($lastImport['last_import'])) : null;
        
        return [
            'success' => true,
            'data' => [
                'total_imported' => (int)$totalImported['count'],
                'successful_imports' => (int)$successfulImports['count'],
                'total_batches' => (int)$totalBatches['count'],
                'last_import' => $lastImportFormatted
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get available sites
 */
function getSites() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT site_id, site_name FROM cli_site ORDER BY site_name");
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $sites
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get import batches
 */
function getImportBatches() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM wo_import_batch ORDER BY imported_at DESC LIMIT 20");
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($batches as &$batch) {
            $batch['imported_at'] = date('M j, Y H:i', strtotime($batch['imported_at']));
        }
        
        return [
            'success' => true,
            'data' => $batches
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get imported work orders with details
 */
function getImportedWorkOrders($siteId = '', $batchId = '') {
    try {
        $pdo = getPDO();
        
        // Build query with joins
        $sql = "SELECT 
                    wo.wo_task_id,
                    wo.wo_task_no,
                    wo.wo_task_external_ref,
                    wo.wo_task_complaint,
                    wo.wo_task_status,
                    wo.wo_task_time_created,
                    wo.transaction_id,
                    s.site_name,
                    log.batch_id
                FROM wo_task wo
                LEFT JOIN cli_site s ON wo.site_id = s.site_id
                LEFT JOIN wo_import_log log ON wo.wo_task_external_ref = log.external_ref
                WHERE wo.wo_task_is_imported = 1";
        
        $params = [];
        
        if (!empty($siteId)) {
            $sql .= " AND wo.site_id = ?";
            $params[] = $siteId;
        }
        
        if (!empty($batchId)) {
            $sql .= " AND log.batch_id = ?";
            $params[] = $batchId;
        }
        
        $sql .= " ORDER BY wo.wo_task_time_created DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $result
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get workflow transaction and task data for imported work orders
 */
function getWorkflowData() {
    try {
        $pdo = getPDO();
        
        $sql = "SELECT 
                    wt.transaction_id,
                    wt.transaction_no,
                    wt.flow_id,
                    wt.user_id,
                    wt.transaction_status,
                    wt.transaction_time_created,
                    task.task_status,
                    task.task_id,
                    wo.wo_task_no,
                    u.user_name
                FROM wfl_transaction wt
                LEFT JOIN wfl_task task ON wt.transaction_id = task.transaction_id
                LEFT JOIN wo_task wo ON wt.transaction_id = wo.transaction_id
                LEFT JOIN sys_user u ON wt.user_id = u.user_id
                WHERE wo.wo_task_is_imported = 1
                ORDER BY wt.transaction_time_created DESC
                LIMIT 50";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $result
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get import log entries
 */
function getImportLog() {
    try {
        $pdo = getPDO();
        
        $sql = "SELECT 
                    log.*,
                    batch.filename,
                    batch.site_id as batch_site_id
                FROM wo_import_log log
                LEFT JOIN wo_import_batch batch ON log.batch_id = batch.batch_id
                ORDER BY log.imported_at DESC
                LIMIT 100";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $result
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Run data integrity checks
 */
function runIntegrityChecks() {
    try {
        $pdo = getPDO();
        $checks = [];
        
        // Check 1: Orphaned work orders (imported WOs without workflow transactions)
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM wo_task wo 
            WHERE wo.wo_task_is_imported = 1 
            AND wo.transaction_id NOT IN (SELECT transaction_id FROM wfl_transaction)
        ");
        $orphanedWOs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $checks[] = [
            'check_name' => 'Orphaned Work Orders',
            'status' => $orphanedWOs['count'] == 0 ? 'pass' : 'warning',
            'message' => $orphanedWOs['count'] == 0 ? 
                'All imported work orders have valid workflow transactions' : 
                'Found work orders without valid workflow transactions',
            'count' => $orphanedWOs['count']
        ];
        
        // Check 2: Missing import log entries
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM wo_task wo 
            WHERE wo.wo_task_is_imported = 1 
            AND wo.wo_task_external_ref NOT IN (SELECT COALESCE(external_ref, '') FROM wo_import_log)
        ");
        $missingLogEntries = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $checks[] = [
            'check_name' => 'Missing Import Log Entries',
            'status' => $missingLogEntries['count'] == 0 ? 'pass' : 'warning',
            'message' => $missingLogEntries['count'] == 0 ? 
                'All imported work orders have log entries' : 
                'Found work orders without import log entries',
            'count' => $missingLogEntries['count']
        ];
        
        // Check 3: Duplicate external references
        $stmt = $pdo->query("
            SELECT wo_task_external_ref, COUNT(*) as count 
            FROM wo_task 
            WHERE wo_task_is_imported = 1 
            AND wo_task_external_ref IS NOT NULL 
            AND wo_task_external_ref != ''
            GROUP BY wo_task_external_ref 
            HAVING COUNT(*) > 1
        ");
        $duplicateRefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $checks[] = [
            'check_name' => 'Duplicate External References',
            'status' => count($duplicateRefs) == 0 ? 'pass' : 'warning',
            'message' => count($duplicateRefs) == 0 ? 
                'No duplicate external references found' : 
                'Found duplicate external references',
            'count' => count($duplicateRefs)
        ];
        
        // Check 4: Invalid user assignments
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM wo_task wo 
            WHERE wo.wo_task_is_imported = 1 
            AND (
                (wo.wo_task_assigned_to IS NOT NULL AND wo.wo_task_assigned_to NOT IN (SELECT user_id FROM sys_user))
                OR (wo.wo_task_created_by IS NOT NULL AND wo.wo_task_created_by NOT IN (SELECT user_id FROM sys_user))
                OR (wo.wo_task_verified_by IS NOT NULL AND wo.wo_task_verified_by NOT IN (SELECT user_id FROM sys_user))
            )
        ");
        $invalidUsers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $checks[] = [
            'check_name' => 'Invalid User Assignments',
            'status' => $invalidUsers['count'] == 0 ? 'pass' : 'warning',
            'message' => $invalidUsers['count'] == 0 ? 
                'All user assignments are valid' : 
                'Found work orders with invalid user assignments',
            'count' => $invalidUsers['count']
        ];
        
        // Check 5: Workflow task status consistency
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM wo_task wo
            INNER JOIN wfl_transaction wt ON wo.transaction_id = wt.transaction_id
            LEFT JOIN wfl_task task ON wt.transaction_id = task.transaction_id
            WHERE wo.wo_task_is_imported = 1 
            AND (wt.transaction_status != 5 OR task.task_status != 5)
        ");
        $inconsistentStatus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $checks[] = [
            'check_name' => 'Workflow Status Consistency',
            'status' => $inconsistentStatus['count'] == 0 ? 'pass' : 'warning',
            'message' => $inconsistentStatus['count'] == 0 ? 
                'All workflow statuses are consistent' : 
                'Found inconsistent workflow statuses',
            'count' => $inconsistentStatus['count']
        ];
        
        return [
            'success' => true,
            'data' => $checks
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
