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
    
    echo "=== DEBUGGING GAMIFICATION ISSUE ===\n\n";
    
    // 1. Check imported work orders status
    echo "1. IMPORTED WORK ORDERS STATUS:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_imported,
            COUNT(CASE WHEN ppm_group_id IS NULL THEN 1 END) as null_ppm_group,
            COUNT(CASE WHEN ppm_group_id IS NOT NULL THEN 1 END) as has_ppm_group
        FROM wo_task 
        WHERE wo_task_is_imported = 1
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total imported WOs: " . $result['total_imported'] . "\n";
    echo "   NULL PPM Group: " . $result['null_ppm_group'] . "\n";
    echo "   Has PPM Group: " . $result['has_pmp_group'] . "\n\n";
    
    // 2. Check July 2025 imported work orders specifically
    echo "2. JULY 2025 IMPORTED WORK ORDERS:\n";
    $stmt = $pdo->query("
        SELECT 
            wo_task_id,
            wo_task_no,
            wo_task_assigned_to,
            ppm_group_id,
            wo_task_time_assigned,
            wo_task_status
        FROM wo_task 
        WHERE wo_task_is_imported = 1 
        AND wo_task_time_assigned >= '2025-07-01' 
        AND wo_task_time_assigned <= '2025-07-31'
        ORDER BY wo_task_time_assigned
    ");
    
    $julyImported = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($julyImported) . " imported WOs in July 2025:\n";
    foreach ($julyImported as $wo) {
        echo "   - ID: {$wo['wo_task_id']}, No: {$wo['wo_task_no']}, User: {$wo['wo_task_assigned_to']}, PPM: {$wo['ppm_group_id']}, Date: {$wo['wo_task_time_assigned']}\n";
    }
    echo "\n";
    
    // 3. Check gamification view for July 2025
    echo "3. GAMIFICATION VIEW RESULTS FOR JULY 2025:\n";
    $stmt = $pdo->query("
        SELECT 
            w.wo_task_assigned_to,
            w.site_id,
            w.ppm_group_id,
            COUNT(*) AS total,
            SUM(CASE WHEN w.wo_task_is_imported = 1 THEN 1 ELSE 0 END) as imported_count
        FROM wo_task w
        WHERE w.wo_task_time_assigned >= '2025-07-01' 
        AND w.wo_task_time_assigned <= '2025-07-31'
        AND w.wo_task_assigned_to IS NOT NULL
        GROUP BY w.wo_task_assigned_to, w.site_id, w.ppm_group_id
        ORDER BY total DESC
    ");
    
    $viewResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($viewResults) . " groupings in gamification view:\n";
    foreach ($viewResults as $result) {
        echo "   - User: {$result['wo_task_assigned_to']}, Site: {$result['site_id']}, PPM: {$result['ppm_group_id']}, Total: {$result['total']}, Imported: {$result['imported_count']}\n";
    }
    echo "\n";
    
    // 4. Check actual monthly gamification data
    echo "4. CHECK EXISTING GAMIFICATION DATA FOR JULY 2025:\n";
    $stmt = $pdo->query("
        SELECT * FROM gmi_weekly 
        WHERE gmw_year = 2025 AND gmw_month = 7
        ORDER BY gmw_user_id, gmw_week
    ");
    
    $gmiData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($gmiData) . " gamification records for July 2025:\n";
    foreach ($gmiData as $gmi) {
        echo "   - User: {$gmi['gmw_user_id']}, Week: {$gmi['gmw_week']}, WO Total: {$gmi['gmw_wo_total']}, WO Completed: {$gmi['gmw_wo_completed']}\n";
    }
    echo "\n";
    
    // 5. Test the exact view query that gamification uses
    echo "5. TESTING EXACT GAMIFICATION VIEW QUERY:\n";
    $weekStart = '2025-07-01';
    $weekEnd = '2025-07-07';
    echo "   Testing week: $weekStart to $weekEnd\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            w.wo_task_assigned_to as woTaskAssignedTo,
            w.site_id as siteId,
            w.ppm_group_id as ppmGroupId,
            COUNT(*) AS woTotal,
            SUM(IF(w.wo_task_status = 16, 1, 0)) AS woCompleted,
            SUM(IF(w.wo_task_is_imported = 1, 1, 0)) AS woImported
        FROM wo_task w
        WHERE w.wo_task_time_assigned >= ? AND w.wo_task_time_assigned <= ?
        AND w.wo_task_assigned_to IS NOT NULL
        GROUP BY w.wo_task_assigned_to, w.site_id, w.ppm_group_id
        ORDER BY woTotal DESC
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    
    $weekResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($weekResults) . " results for first week:\n";
    foreach ($weekResults as $result) {
        echo "   - User: {$result['woTaskAssignedTo']}, PPM: {$result['ppmGroupId']}, Total: {$result['woTotal']}, Completed: {$result['woCompleted']}, Imported: {$result['woImported']}\n";
    }
    
    // 6. Check what the runMonthly function would actually process
    echo "\n6. SIMULATING runMonthly FOR JULY 2025:\n";
    
    // Include the gamification class
    require_once('api/class/Class_db.php');
    require_once('api/function/f_gamification.php');
    
    try {
        $gamification = new Class_gamification();
        echo "   Gamification class loaded successfully\n";
        
        // This would be called by runMonthly
        // $result = $gamification->runMonthly(2025, 7);
        echo "   Note: Would call runMonthly(2025, 7) here\n";
        
    } catch (Exception $e) {
        echo "   Error loading gamification class: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== END DEBUG ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
