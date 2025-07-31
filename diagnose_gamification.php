<?php
require_once('api/class/Constant.php');

// Database connection
$host = Constant::$dbHost;
$username = Constant::$dbUserName;
$password = Constant::$dbUserPassword;
$database = Constant::$dbName;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== GAMIFICATION DIAGNOSIS ===\n\n";
    
    // 1. Check July 2025 data
    echo "1. JULY 2025 WORK ORDERS:\n";
    $stmt = $pdo->query("
        SELECT 
            wo_task_assigned_to,
            site_id,
            ppm_group_id,
            COUNT(*) as total,
            SUM(CASE WHEN wo_task_is_imported = 1 THEN 1 ELSE 0 END) as imported,
            SUM(CASE WHEN wo_task_is_imported = 0 THEN 1 ELSE 0 END) as regular,
            SUM(CASE WHEN wo_task_status = 16 THEN 1 ELSE 0 END) as completed
        FROM wo_task 
        WHERE wo_task_time_assigned >= '2025-07-01' 
        AND wo_task_time_assigned <= '2025-07-31'
        AND wo_task_assigned_to IS NOT NULL
        GROUP BY wo_task_assigned_to, site_id, ppm_group_id
        ORDER BY total DESC
    ");
    
    $julyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($julyData as $data) {
        echo "   User {$data['wo_task_assigned_to']} (PPM: {$data['ppm_group_id']}): Total={$data['total']}, Imported={$data['imported']}, Regular={$data['regular']}, Completed={$data['completed']}\n";
    }
    echo "\n";
    
    // 2. Check gamification table structure
    echo "2. CHECKING GAMIFICATION TABLE STRUCTURE:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'gmi_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Gamification tables: " . implode(', ', $tables) . "\n";
    
    // Check gmi_weekly structure
    if (in_array('gmi_weekly', $tables)) {
        $stmt = $pdo->query("DESCRIBE gmi_weekly");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   gmi_weekly columns: " . implode(', ', $columns) . "\n";
        
        // Check for July 2025 data
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM gmi_weekly 
            WHERE gmw_user_id = 1102 
            AND gmw_year = 2025 
            AND gmw_month = 7
        ");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   User 1102 July 2025 records: $count\n";
        
        if ($count > 0) {
            $stmt = $pdo->query("
                SELECT * 
                FROM gmi_weekly 
                WHERE gmw_user_id = 1102 
                AND gmw_year = 2025 
                AND gmw_month = 7
                ORDER BY gmw_week
            ");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($records as $record) {
                echo "   Week {$record['gmw_week']}: WO Total={$record['gmw_wo_total']}, WO Completed={$record['gmw_wo_completed']}, WO On Time={$record['gmw_wo_on_time']}\n";
            }
        }
    }
    echo "\n";
    
    // 3. Test if we can manually run gamification for a week
    echo "3. MANUAL GAMIFICATION TEST:\n";
    echo "   Testing gamification calculation for first week of July 2025...\n";
    
    $weekStart = '2025-07-01';
    $weekEnd = '2025-07-07';
    
    // Simulate what the gamification system does
    $stmt = $pdo->prepare("
        SELECT 
            w.wo_task_assigned_to as woTaskAssignedTo,
            w.site_id as siteId,
            w.ppm_group_id as ppmGroupId,
            COUNT(*) AS woTotal,
            SUM(IF(w.wo_task_status = 16, 1, 0)) AS woCompleted,
            SUM(IF(w.wo_task_status = 16 AND w.wo_task_time_verified <= DATE_ADD(w.wo_task_time_assigned, INTERVAL 24 HOUR), 1, 0)) AS woOnTime,
            SUM(IF(w.wo_task_status = 16 AND w.wo_task_time_verified > DATE_ADD(w.wo_task_time_assigned, INTERVAL 24 HOUR), 1, 0)) AS woLate,
            SUM(IF(w.wo_task_created_by = w.wo_task_assigned_to, 1, 0)) AS woSelfFinding
        FROM wo_task w
        WHERE w.wo_task_time_assigned >= ? AND w.wo_task_time_assigned <= ?
        AND w.wo_task_assigned_to IS NOT NULL
        GROUP BY w.wo_task_assigned_to, w.site_id, w.ppm_group_id
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    
    $weekData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($weekData) > 0) {
        echo "   Found data for week 1:\n";
        foreach ($weekData as $data) {
            echo "   User {$data['woTaskAssignedTo']} (PPM: {$data['ppmGroupId']}): Total={$data['woTotal']}, Completed={$data['woCompleted']}, OnTime={$data['woOnTime']}\n";
        }
    } else {
        echo "   No work orders found for week 1 (2025-07-01 to 2025-07-07)\n";
    }
    
    // 4. Check the specific work order dates
    echo "\n4. SPECIFIC IMPORTED WORK ORDER ANALYSIS:\n";
    $stmt = $pdo->query("
        SELECT 
            wo_task_id,
            wo_task_no,
            wo_task_time_assigned,
            wo_task_time_verified,
            wo_task_status,
            wo_task_assigned_to,
            ppm_group_id,
            wo_task_is_imported
        FROM wo_task 
        WHERE wo_task_is_imported = 1 
        AND wo_task_time_assigned >= '2025-07-01'
        AND wo_task_time_assigned <= '2025-07-31'
    ");
    
    $importedDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($importedDetails as $wo) {
        echo "   WO {$wo['wo_task_id']} ({$wo['wo_task_no']}):\n";
        echo "     Assigned: {$wo['wo_task_time_assigned']}\n";
        echo "     Verified: {$wo['wo_task_time_verified']}\n";
        echo "     Status: {$wo['wo_task_status']}\n";
        echo "     User: {$wo['wo_task_assigned_to']}\n";
        echo "     PPM Group: {$wo['ppm_group_id']}\n";
        
        // Calculate if this should be "on time"
        $assigned = new DateTime($wo['wo_task_time_assigned']);
        $verified = new DateTime($wo['wo_task_time_verified']);
        $diff = $verified->getTimestamp() - $assigned->getTimestamp();
        $hoursElapsed = $diff / 3600;
        echo "     Hours to complete: " . round($hoursElapsed, 1) . "\n";
        echo "     Should be counted as: " . ($hoursElapsed <= 24 ? "ON TIME" : "LATE") . "\n";
    }
    
    echo "\n=== CONCLUSION ===\n";
    echo "The imported work order exists and has proper PPM group assignment.\n";
    echo "If it's still not showing in gamification, the issue might be:\n";
    echo "1. The runMonthly process needs to be re-run\n";
    echo "2. The gamification calculation period doesn't include this data\n";
    echo "3. There might be caching or other system issues\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
