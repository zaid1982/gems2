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
    
    echo "=== MANUAL GAMIFICATION RECALCULATION FOR JULY 2025 ===\n\n";
    
    // 1. Show current data
    echo "1. CURRENT GAMIFICATION DATA:\n";
    $stmt = $pdo->query("
        SELECT user_id, gmw_week, gmw_wo_total, gmw_wo_completed, gmw_wo_on_time
        FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week >= 27 AND gmw_week <= 31
        ORDER BY gmw_week
    ");
    $currentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($currentRecords as $record) {
        echo "   Week {$record['gmw_week']}: WO Total={$record['gmw_wo_total']}, Completed={$record['gmw_wo_completed']}, OnTime={$record['gmw_wo_on_time']}\n";
    }
    echo "\n";
    
    // 2. Calculate what the data SHOULD be
    echo "2. CALCULATING CORRECT VALUES:\n";
    
    $july2025Weeks = [
        ['week' => 27, 'start' => '2025-07-01', 'end' => '2025-07-06'],
        ['week' => 28, 'start' => '2025-07-07', 'end' => '2025-07-13'],
        ['week' => 29, 'start' => '2025-07-14', 'end' => '2025-07-20'],
        ['week' => 30, 'start' => '2025-07-21', 'end' => '2025-07-27'],
        ['week' => 31, 'start' => '2025-07-28', 'end' => '2025-07-31'],
    ];
    
    $correctedData = [];
    
    foreach ($july2025Weeks as $weekInfo) {
        echo "   Week {$weekInfo['week']} ({$weekInfo['start']} to {$weekInfo['end']}):\n";
        
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
            GROUP BY w.wo_task_assigned_to, w.site_id, w.pmp_group_id
        ");
        $stmt->execute([$weekInfo['start'], $weekInfo['end']]);
        
        $weekData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($weekData) > 0) {
            foreach ($weekData as $data) {
                echo "     User {$data['woTaskAssignedTo']}: Total={$data['woTotal']}, Completed={$data['woCompleted']}, OnTime={$data['woOnTime']}\n";
                
                // Store corrected data
                $correctedData[$weekInfo['week']] = [
                    'user_id' => $data['woTaskAssignedTo'],
                    'site_id' => $data['siteId'],
                    'wo_total' => $data['woTotal'],
                    'wo_completed' => $data['woCompleted'],
                    'wo_on_time' => $data['woOnTime'],
                    'wo_late' => $data['woLate'],
                    'wo_self_finding' => $data['woSelfFinding']
                ];
            }
        } else {
            echo "     No work orders found\n";
        }
    }
    echo "\n";
    
    // 3. Update the existing records
    echo "3. UPDATING GAMIFICATION RECORDS:\n";
    
    $pdo->beginTransaction();
    
    foreach ($correctedData as $week => $data) {
        // Check if record exists
        $stmt = $pdo->prepare("
            SELECT gmw_id FROM gmi_weekly 
            WHERE user_id = ? AND gmw_year = 2025 AND gmw_week = ?
        ");
        $stmt->execute([$data['user_id'], $week]);
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRecord) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE gmi_weekly 
                SET gmw_wo_total = ?, 
                    gmw_wo_completed = ?, 
                    gmw_wo_on_time = ?,
                    gmw_wo_late = ?,
                    gmw_wo_self_finding = ?
                WHERE gmw_id = ?
            ");
            $stmt->execute([
                $data['wo_total'],
                $data['wo_completed'], 
                $data['wo_on_time'],
                $data['wo_late'],
                $data['wo_self_finding'],
                $existingRecord['gmw_id']
            ]);
            echo "   Updated Week $week: WO Total={$data['wo_total']}, Completed={$data['wo_completed']}, OnTime={$data['wo_on_time']}\n";
        } else {
            echo "   No existing record found for Week $week\n";
        }
    }
    
    $pdo->commit();
    echo "\n";
    
    // 4. Show updated data
    echo "4. UPDATED GAMIFICATION DATA:\n";
    $stmt = $pdo->query("
        SELECT user_id, gmw_week, gmw_wo_total, gmw_wo_completed, gmw_wo_on_time
        FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week >= 27 AND gmw_week <= 31
        ORDER BY gmw_week
    ");
    $updatedRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($updatedRecords as $record) {
        echo "   Week {$record['gmw_week']}: WO Total={$record['gmw_wo_total']}, Completed={$record['gmw_wo_completed']}, OnTime={$record['gmw_wo_on_time']}\n";
    }
    
    // 5. Show the changes
    echo "\n5. CHANGES MADE:\n";
    foreach ($updatedRecords as $newRecord) {
        $oldRecord = null;
        foreach ($currentRecords as $old) {
            if ($old['gmw_week'] == $newRecord['gmw_week']) {
                $oldRecord = $old;
                break;
            }
        }
        
        if ($oldRecord) {
            $totalChange = $newRecord['gmw_wo_total'] - $oldRecord['gmw_wo_total'];
            $completedChange = $newRecord['gmw_wo_completed'] - $oldRecord['gmw_wo_completed'];
            $onTimeChange = $newRecord['gmw_wo_on_time'] - $oldRecord['gmw_wo_on_time'];
            
            if ($totalChange != 0 || $completedChange != 0 || $onTimeChange != 0) {
                echo "   Week {$newRecord['gmw_week']}: Total changed by $totalChange, Completed by $completedChange, OnTime by $onTimeChange\n";
            } else {
                echo "   Week {$newRecord['gmw_week']}: No changes needed\n";
            }
        }
    }
    
    echo "\n✅ GAMIFICATION UPDATE COMPLETE!\n";
    echo "✅ Imported work orders are now properly counted in the gamification system.\n";
    echo "✅ Check your gamification dashboard to see the updated scores.\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
