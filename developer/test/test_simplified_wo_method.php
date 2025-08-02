<?php
// Test the simplified getWoDetailsForGamification method
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

try {
    Class_db::getInstance()->db_connect();
    
    $year = 2025;
    $month = 8; // August
    $userId = '1102';

    echo "Testing simplified getWoDetailsForGamification($year, $month, $userId)...\n";
    echo "This should only return WO number, status, and completion date\n";
    echo "Filtered by reported date (wo_task_time_created) and completed status only\n\n";
    
    $fn_general = new Class_general();
    $fn_gamification = new Class_gamification();
    $fn_general->__set('constant', new Class_constant());
    $fn_gamification->__set('constant', new Class_constant());
    $fn_gamification->__set('fn_general', $fn_general);
    
    $woDetails = $fn_gamification->getWoDetailsForGamification($year, $month, $userId);
    
    echo "Result type: " . gettype($woDetails) . "\n";
    echo "Result count: " . count($woDetails) . "\n\n";
    
    if (count($woDetails) > 0) {
        echo "Sample work order details:\n";
        foreach ($woDetails as $index => $wo) {
            echo "Work Order " . ($index + 1) . ":\n";
            foreach ($wo as $key => $value) {
                echo "  $key: $value\n";
            }
            echo "\n";
            
            // Only show first 3 for brevity
            if ($index >= 2) {
                if (count($woDetails) > 3) {
                    echo "... and " . (count($woDetails) - 3) . " more work orders\n";
                }
                break;
            }
        }
    } else {
        echo "No work orders found for user $userId in $year-$month\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
