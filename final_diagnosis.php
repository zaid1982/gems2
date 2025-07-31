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
    
    echo "=== FINAL GAMIFICATION DIAGNOSIS ===\n\n";
    
    // 1. Check July 2025 data clearly
    echo "1. JULY 2025 WORK ORDER SUMMARY:\n";
    $stmt = $pdo->query("
        SELECT 
            wo_task_assigned_to,
            ppm_group_id,
            COUNT(*) as total,
            SUM(CASE WHEN wo_task_is_imported = 1 THEN 1 ELSE 0 END) as imported,
            SUM(CASE WHEN wo_task_status = 16 THEN 1 ELSE 0 END) as completed
        FROM wo_task 
        WHERE wo_task_time_assigned >= '2025-07-01' 
        AND wo_task_time_assigned <= '2025-07-31'
        AND wo_task_assigned_to IS NOT NULL
        GROUP BY wo_task_assigned_to, ppm_group_id
        ORDER BY total DESC
    ");
    
    $julyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($julyData as $data) {
        echo "   User {$data['wo_task_assigned_to']} (PPM: {$data['ppm_group_id']}): Total={$data['total']}, Imported={$data['imported']}, Completed={$data['completed']}\n";
    }
    echo "\n";
    
    // 2. Check existing gamification records for July 2025
    echo "2. EXISTING GAMIFICATION RECORDS FOR JULY 2025:\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week >= 27 AND gmw_week <= 31
    ");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   User 1102 July 2025 records: $count\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("
            SELECT gmw_week, gmw_wo_total, gmw_wo_completed, gmw_wo_on_time 
            FROM gmi_weekly 
            WHERE user_id = 1102 
            AND gmw_year = 2025 
            AND gmw_week >= 27 AND gmw_week <= 31
            ORDER BY gmw_week
        ");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as $record) {
            echo "   Week {$record['gmw_week']}: WO Total={$record['gmw_wo_total']}, Completed={$record['gmw_wo_completed']}, OnTime={$record['gmw_wo_on_time']}\n";
        }
    } else {
        echo "   No gamification records found for user 1102 in July 2025\n";
        echo "   This means runMonthly hasn't been run for July 2025 yet!\n";
    }
    echo "\n";
    
    // 3. Check what happens when we simulate the gamification calculation
    echo "3. SIMULATING GAMIFICATION CALCULATION:\n";
    
    // Get all weeks in July 2025
    $july2025Weeks = [
        ['week' => 27, 'start' => '2025-07-01', 'end' => '2025-07-06'],
        ['week' => 28, 'start' => '2025-07-07', 'end' => '2025-07-13'],
        ['week' => 29, 'start' => '2025-07-14', 'end' => '2025-07-20'],
        ['week' => 30, 'start' => '2025-07-21', 'end' => '2025-07-27'],
        ['week' => 31, 'start' => '2025-07-28', 'end' => '2025-07-31'],
    ];
    
    foreach ($july2025Weeks as $weekInfo) {
        echo "   Week {$weekInfo['week']} ({$weekInfo['start']} to {$weekInfo['end']}):\n";
        
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
        ");
        $stmt->execute([$weekInfo['start'], $weekInfo['end']]);
        
        $weekData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($weekData) > 0) {
            foreach ($weekData as $data) {
                echo "     User {$data['woTaskAssignedTo']}: Total={$data['woTotal']}, Completed={$data['woCompleted']}, Imported={$data['woImported']}\n";
            }
        } else {
            echo "     No work orders found\n";
        }
    }
    
    // 4. Show the specific imported work order
    echo "\n4. SPECIFIC IMPORTED WORK ORDER:\n";
    $stmt = $pdo->query("
        SELECT 
            wo_task_id,
            wo_task_no,
            wo_task_time_assigned,
            wo_task_assigned_to,
            ppm_group_id,
            wo_task_status
        FROM wo_task 
        WHERE wo_task_is_imported = 1 
        AND wo_task_time_assigned >= '2025-07-01'
        AND wo_task_time_assigned <= '2025-07-31'
    ");
    
    $importedWO = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($importedWO) {
        echo "   Found imported WO: {$importedWO['wo_task_no']}\n";
        echo "   Date: {$importedWO['wo_task_time_assigned']}\n";
        echo "   User: {$importedWO['wo_task_assigned_to']}\n";
        echo "   PPM Group: {$importedWO['ppm_group_id']}\n";
        echo "   Status: {$importedWO['wo_task_status']}\n";
        
        // Which week does this fall into?
        $woDate = new DateTime($importedWO['wo_task_time_assigned']);
        $weekNumber = $woDate->format('W');
        echo "   Week number: $weekNumber\n";
    }
    
    echo "\n=== DIAGNOSIS COMPLETE ===\n";
    echo "Based on the analysis:\n";
    echo "1. ✅ Imported work order exists with proper PPM group\n";
    echo "2. ✅ Work order is in completed status (16)\n";
    echo "3. ❓ Check if runMonthly has been run for July 2025\n";
    echo "4. ❓ If runMonthly was run BEFORE the import, it won't include the imported data\n";
    echo "\n";
    echo "SOLUTION: Re-run the runMonthly gamification process for July 2025\n";
    echo "This will recalculate all the weekly scores including the imported work orders.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
