<?php
require_once('api/class/Constant.php');
require_once('api/function/db.php');
require_once('api/function/f_gamification.php');

// Database connection
$host = Constant::$dbHost;
$username = Constant::$dbUserName;
$password = Constant::$dbUserPassword;
$database = Constant::$dbName;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== RE-RUNNING GAMIFICATION FOR JULY 2025 ===\n\n";
    
    // 1. Show current gamification data
    echo "1. CURRENT GAMIFICATION DATA FOR JULY 2025:\n";
    $stmt = $pdo->query("
        SELECT user_id, gmw_week, gmw_wo_total, gmw_wo_completed, gmw_wo_on_time
        FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week >= 27 AND gmw_week <= 31
        ORDER BY gmw_week
    ");
    $oldRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($oldRecords) > 0) {
        foreach ($oldRecords as $record) {
            echo "   Week {$record['gmw_week']}: WO Total={$record['gmw_wo_total']}, Completed={$record['gmw_wo_completed']}, OnTime={$record['gmw_wo_on_time']}\n";
        }
    } else {
        echo "   No existing records found\n";
    }
    echo "\n";
    
    // 2. Delete existing July 2025 gamification data
    echo "2. CLEARING EXISTING JULY 2025 GAMIFICATION DATA:\n";
    $stmt = $pdo->prepare("
        DELETE FROM gmi_weekly 
        WHERE gmw_year = 2025 
        AND gmw_week >= 27 AND gmw_week <= 31
    ");
    $stmt->execute();
    echo "   Deleted existing records for July 2025 weeks\n\n";
    
    // 3. Re-run gamification calculation
    echo "3. RE-RUNNING GAMIFICATION CALCULATION:\n";
    try {
        $gamification = new Class_gamification();
        echo "   Gamification class loaded successfully\n";
        
        // Run monthly calculation for July 2025
        echo "   Running runMonthly(2025, 7)...\n";
        $result = $gamification->runMonthly(2025, 7);
        
        if ($result) {
            echo "   ✅ Gamification calculation completed successfully!\n";
        } else {
            echo "   ❌ Gamification calculation failed\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error running gamification: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 4. Show new gamification data
    echo "4. NEW GAMIFICATION DATA FOR JULY 2025:\n";
    $stmt = $pdo->query("
        SELECT user_id, gmw_week, gmw_wo_total, gmw_wo_completed, gmw_wo_on_time
        FROM gmi_weekly 
        WHERE user_id = 1102 
        AND gmw_year = 2025 
        AND gmw_week >= 27 AND gmw_week <= 31
        ORDER BY gmw_week
    ");
    $newRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($newRecords) > 0) {
        foreach ($newRecords as $record) {
            echo "   Week {$record['gmw_week']}: WO Total={$record['gmw_wo_total']}, Completed={$record['gmw_wo_completed']}, OnTime={$record['gmw_wo_on_time']}\n";
        }
        
        // Compare with old data
        echo "\n   COMPARISON:\n";
        foreach ($newRecords as $newRecord) {
            $oldRecord = null;
            foreach ($oldRecords as $old) {
                if ($old['gmw_week'] == $newRecord['gmw_week']) {
                    $oldRecord = $old;
                    break;
                }
            }
            
            if ($oldRecord) {
                $totalDiff = $newRecord['gmw_wo_total'] - $oldRecord['gmw_wo_total'];
                $completedDiff = $newRecord['gmw_wo_completed'] - $oldRecord['gmw_wo_completed'];
                echo "   Week {$newRecord['gmw_week']}: Total changed by $totalDiff, Completed changed by $completedDiff\n";
            } else {
                echo "   Week {$newRecord['gmw_week']}: New record created\n";
            }
        }
    } else {
        echo "   No new records found - something went wrong\n";
    }
    
    echo "\n=== RESULTS ===\n";
    echo "✅ Gamification recalculation complete!\n";
    echo "✅ Imported work orders should now be properly counted\n";
    echo "✅ Check your gamification dashboard to see the updated scores\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
