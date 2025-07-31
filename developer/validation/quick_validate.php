<?php
/**
 * Quick Import Validation Check
 * Simple script to validate the latest import data
 */

require_once 'api/class/Constant.php';

$config = [
    'DB_HOST' => Constant::$dbHost,
    'DB_NAME' => Constant::$dbName,
    'DB_USER' => Constant::$dbUserName,
    'DB_PASS' => Constant::$dbUserPassword,
];


try {
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}", 
        $config['DB_USER'], 
        $config['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 Import Data Validation Report</h1>\n";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .stats { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>\n";
    
    // Quick Stats
    echo "<div class='stats'>\n";
    echo "<h2>📊 Import Summary</h2>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM wo_task WHERE wo_task_is_imported = 1");
    $totalImported = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM wo_import_batch");
    $totalBatches = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // $stmt = $pdo->query("SELECT MAX(imported_at) as last_import FROM wo_import_batch");
    // $lastImport = $stmt->fetch(PDO::FETCH_ASSOC)['last_import'];
    
    echo "<p><strong>Total Imported Work Orders:</strong> <span class='success'>$totalImported</span></p>\n";
    echo "<p><strong>Total Import Batches:</strong> <span class='success'>$totalBatches</span></p>\n";
    // echo "<p><strong>Last Import:</strong> " . ($lastImport ? date('M j, Y H:i:s', strtotime($lastImport)) : 'None') . "</p>\n";
    echo "</div>\n";
    
    // Recent Imported WOs
    echo "<h2>📋 Recent Imported Work Orders (Last 10)</h2>\n";
    $stmt = $pdo->query("
        SELECT 
            wo.wo_task_no,
            wo.wo_task_external_ref,
            wo.wo_task_complaint,
            wo.wo_task_status,
            wo.wo_task_time_created,
            wo.transaction_id,
            s.site_name,
            u.user_name as assigned_to_name,
            u.user_first_name,
            u.user_last_name
        FROM wo_task wo
        LEFT JOIN cli_site s ON wo.site_id = s.site_id
        LEFT JOIN sys_user u ON wo.wo_task_assigned_to = u.user_id
        WHERE wo.wo_task_is_imported = 1
        ORDER BY wo.wo_task_time_created DESC
        LIMIT 10
    ");
    
    $recentWOs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recentWOs)) {
        echo "<table>\n";
        echo "<tr><th>WO Number</th><th>External Ref</th><th>Complaint</th><th>Site</th><th>Assigned To</th><th>Status</th><th>Transaction ID</th><th>Created</th></tr>\n";
        foreach ($recentWOs as $wo) {
            // Build assigned technician name
            $assignedTo = 'Not Assigned';
            if (!empty($wo['assigned_to_name'])) {
                $assignedTo = $wo['assigned_to_name'];
                if (!empty($wo['user_first_name']) || !empty($wo['user_last_name'])) {
                    $fullName = trim($wo['user_first_name'] . ' ' . $wo['user_last_name']);
                    if (!empty($fullName)) {
                        $assignedTo = $fullName . ' (' . $wo['assigned_to_name'] . ')';
                    }
                }
            }
            
            echo "<tr>";
            echo "<td><strong>{$wo['wo_task_no']}</strong></td>";
            echo "<td>{$wo['wo_task_external_ref']}</td>";
            echo "<td>" . substr($wo['wo_task_complaint'], 0, 40) . "...</td>";
            echo "<td>{$wo['site_name']}</td>";
            echo "<td><em>$assignedTo</em></td>";
            echo "<td>{$wo['wo_task_status']}</td>";
            echo "<td>{$wo['transaction_id']}</td>";
            echo "<td>{$wo['wo_task_time_created']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p class='warning'>No imported work orders found.</p>\n";
    }
    
    // Workflow Validation
    echo "<h2>⚙️ Workflow Integration Check</h2>\n";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(wo.wo_task_id) as wo_count,
            COUNT(wt.transaction_id) as transaction_count,
            COUNT(task.task_id) as task_count
        FROM wo_task wo
        LEFT JOIN wfl_transaction wt ON wo.transaction_id = wt.transaction_id
        LEFT JOIN wfl_task task ON wt.transaction_id = task.transaction_id
        WHERE wo.wo_task_is_imported = 1
    ");
    
    $workflowCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>Component</th><th>Count</th><th>Status</th></tr>\n";
    echo "<tr><td>Imported Work Orders</td><td>{$workflowCheck['wo_count']}</td><td class='success'>✓</td></tr>\n";
    echo "<tr><td>Workflow Transactions</td><td>{$workflowCheck['transaction_count']}</td><td class='" . ($workflowCheck['transaction_count'] == $workflowCheck['wo_count'] ? 'success' : 'error') . "'>" . ($workflowCheck['transaction_count'] == $workflowCheck['wo_count'] ? '✓' : '✗') . "</td></tr>\n";
    echo "<tr><td>Workflow Tasks</td><td>{$workflowCheck['task_count']}</td><td class='" . ($workflowCheck['task_count'] == $workflowCheck['wo_count'] ? 'success' : 'error') . "'>" . ($workflowCheck['task_count'] == $workflowCheck['wo_count'] ? '✓' : '✗') . "</td></tr>\n";
    echo "</table>\n";
    
    // Import Log Summary
    echo "<h2>📝 Import Log Summary</h2>\n";
    
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM wo_import_log
        GROUP BY status
    ");
    
    $logSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($logSummary)) {
        echo "<table>\n";
        echo "<tr><th>Status</th><th>Count</th></tr>\n";
        foreach ($logSummary as $status) {
            $class = $status['status'] === 'success' ? 'success' : 'error';
            echo "<tr><td class='$class'>{$status['status']}</td><td>{$status['count']}</td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p class='warning'>No import log entries found.</p>\n";
    }
    
    // Data Integrity Quick Checks
    echo "<h2>🔧 Data Integrity Quick Checks</h2>\n";
    
    $checks = [];
    
    // Check for orphaned work orders
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM wo_task wo 
        WHERE wo.wo_task_is_imported = 1 
        AND wo.transaction_id NOT IN (SELECT transaction_id FROM wfl_transaction)
    ");
    $orphaned = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $checks[] = ['name' => 'Orphaned Work Orders', 'count' => $orphaned, 'status' => $orphaned == 0 ? 'pass' : 'warning'];
    
    // Check for missing external refs
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM wo_task 
        WHERE wo_task_is_imported = 1 
        AND (wo_task_external_ref IS NULL OR wo_task_external_ref = '')
    ");
    $missingRefs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $checks[] = ['name' => 'Missing External References', 'count' => $missingRefs, 'status' => $missingRefs == 0 ? 'pass' : 'warning'];
    
    // Check for duplicate external refs
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM (
            SELECT wo_task_external_ref 
            FROM wo_task 
            WHERE wo_task_is_imported = 1 
            AND wo_task_external_ref IS NOT NULL 
            AND wo_task_external_ref != ''
            GROUP BY wo_task_external_ref 
            HAVING COUNT(*) > 1
        ) as duplicates
    ");
    $duplicates = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $checks[] = ['name' => 'Duplicate External References', 'count' => $duplicates, 'status' => $duplicates == 0 ? 'pass' : 'warning'];
    
    echo "<table>\n";
    echo "<tr><th>Check</th><th>Count</th><th>Status</th></tr>\n";
    foreach ($checks as $check) {
        $class = $check['status'] === 'pass' ? 'success' : 'warning';
        $icon = $check['status'] === 'pass' ? '✓' : '⚠️';
        echo "<tr><td>{$check['name']}</td><td>{$check['count']}</td><td class='$class'>$icon</td></tr>\n";
    }
    echo "</table>\n";
    
    echo "<div class='stats'>\n";
    echo "<h3>✅ Validation Complete</h3>\n";
    echo "<p>For detailed validation, use the full <a href='validate_import.html'>Import Validation Dashboard</a></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>
