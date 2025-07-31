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
    
    echo "=== SIMPLE GAMIFICATION FIX ===\n\n";
    
    // 1. Check what we have for Week 28 (where the imported WO is)
    echo "1. CURRENT WEEK 28 DATA:\n";
    $stmt = $pdo->query("
        SELECT * FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week = 28
    ");
    $week28Record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($week28Record) {
        echo "   Current: WO Total={$week28Record['gmw_wo_total']}, Completed={$week28Record['gmw_wo_completed']}, OnTime={$week28Record['gmw_wo_on_time']}\n";
    } else {
        echo "   No Week 28 record found\n";
    }
    
    // 2. Calculate what Week 28 SHOULD have
    echo "\n2. CALCULATING CORRECT WEEK 28 VALUES:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS woTotal,
            SUM(IF(wo_task_status = 16, 1, 0)) AS woCompleted,
            SUM(IF(wo_task_status = 16 AND wo_task_time_verified <= DATE_ADD(wo_task_time_assigned, INTERVAL 24 HOUR), 1, 0)) AS woOnTime,
            SUM(IF(wo_task_status = 16 AND wo_task_time_verified > DATE_ADD(wo_task_time_assigned, INTERVAL 24 HOUR), 1, 0)) AS woLate,
            SUM(IF(wo_task_created_by = wo_task_assigned_to, 1, 0)) AS woSelfFinding,
            SUM(IF(wo_task_is_imported = 1, 1, 0)) AS woImported
        FROM wo_task
        WHERE wo_task_time_assigned >= '2025-07-07' 
        AND wo_task_time_assigned <= '2025-07-13'
        AND wo_task_assigned_to = 1102
        AND wo_task_assigned_to IS NOT NULL
    ");
    $correctValues = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Should be: WO Total={$correctValues['woTotal']}, Completed={$correctValues['woCompleted']}, OnTime={$correctValues['woOnTime']}, Imported={$correctValues['woImported']}\n";
    
    // 3. Update the record if needed
    if ($week28Record && ($week28Record['gmw_wo_total'] != $correctValues['woTotal'] || 
                         $week28Record['gmw_wo_completed'] != $correctValues['woCompleted'] ||
                         $week28Record['gmw_wo_on_time'] != $correctValues['woOnTime'])) {
        
        echo "\n3. UPDATING WEEK 28 RECORD:\n";
        $stmt = $pdo->prepare("
            UPDATE gmi_weekly 
            SET gmw_wo_total = ?, 
                gmw_wo_completed = ?, 
                gmw_wo_on_time = ?,
                gmw_wo_late = ?,
                gmw_wo_self_finding = ?
            WHERE gmw_id = ?
        ");
        
        $result = $stmt->execute([
            $correctValues['woTotal'],
            $correctValues['woCompleted'],
            $correctValues['woOnTime'],
            $correctValues['woLate'],
            $correctValues['woSelfFinding'],
            $week28Record['gmw_id']
        ]);
        
        if ($result) {
            echo "   ✅ Week 28 record updated successfully!\n";
            echo "   Changed from: Total={$week28Record['gmw_wo_total']}, Completed={$week28Record['gmw_wo_completed']}, OnTime={$week28Record['gmw_wo_on_time']}\n";
            echo "   Changed to: Total={$correctValues['woTotal']}, Completed={$correctValues['woCompleted']}, OnTime={$correctValues['woOnTime']}\n";
        } else {
            echo "   ❌ Failed to update record\n";
        }
    } else {
        echo "\n3. NO UPDATE NEEDED - Record already correct\n";
    }
    
    // 4. Verify the update
    echo "\n4. VERIFICATION:\n";
    $stmt = $pdo->query("
        SELECT gmw_wo_total, gmw_wo_completed, gmw_wo_on_time 
        FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week = 28
    ");
    $verifyRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verifyRecord) {
        echo "   Final Week 28: WO Total={$verifyRecord['gmw_wo_total']}, Completed={$verifyRecord['gmw_wo_completed']}, OnTime={$verifyRecord['gmw_wo_on_time']}\n";
        
        if ($verifyRecord['gmw_wo_total'] == $correctValues['woTotal'] && 
            $verifyRecord['gmw_wo_completed'] == $correctValues['woCompleted']) {
            echo "   ✅ SUCCESS! Imported work order is now counted in gamification!\n";
        } else {
            echo "   ❌ Something went wrong - values don't match\n";
        }
    }
    
    // 5. Show all work orders for Week 28 to confirm
    echo "\n5. ALL WORK ORDERS FOR WEEK 28 (2025-07-07 to 2025-07-13):\n";
    $stmt = $pdo->query("
        SELECT 
            wo_task_id,
            wo_task_no,
            wo_task_is_imported,
            wo_task_status,
            wo_task_time_assigned,
            wo_task_time_verified
        FROM wo_task
        WHERE wo_task_time_assigned >= '2025-07-07' 
        AND wo_task_time_assigned <= '2025-07-13'
        AND wo_task_assigned_to = 1102
        ORDER BY wo_task_time_assigned
    ");
    $allWOs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allWOs as $wo) {
        $type = $wo['wo_task_is_imported'] ? 'IMPORTED' : 'REGULAR';
        echo "   WO {$wo['wo_task_id']} ({$wo['wo_task_no']}) - $type - Status: {$wo['wo_task_status']} - Assigned: {$wo['wo_task_time_assigned']}\n";
    }
    
    echo "\n🎉 GAMIFICATION FIX COMPLETE!\n";
    echo "The imported work order should now be properly counted in the gamification system.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
