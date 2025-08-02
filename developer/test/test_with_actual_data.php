<?php
// Test with current month to see if there are any completed work orders
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

try {
    Class_db::getInstance()->db_connect();
    
    // Test with current year/month
    $year = 2025;
    $month = 8; // August
    $userId = '1102';

    echo "Testing with current month ($year-$month) for user $userId...\n\n";
    
    // First, let's see if this user has ANY completed work orders
    $allCompleted = Class_db::getInstance()->db_select2('wo_task', 
        array(
            'wo_task_assigned_to' => $userId,
            'wo_task_status' => '16'
        ), 'wo_task_time_created DESC', '5', 0);
    
    echo "User $userId has " . count($allCompleted) . " completed work orders total.\n";
    
    if (count($allCompleted) > 0) {
        echo "Recent completed work orders:\n";
        foreach ($allCompleted as $wo) {
            echo "  - WO ID: " . $wo['wo_task_id'] . ", Assigned: " . $wo['wo_task_time_assigned'] . ", Completed: " . $wo['wo_task_time_verified'] . "\n";
        }
        
        // Try with the month that has data
        $recentWo = $allCompleted[0];
        $assignedDate = new DateTime($recentWo['wo_task_time_assigned']);
        $testYear = $assignedDate->format('Y');
        $testMonth = $assignedDate->format('n'); // n = month without leading zeros
        
        echo "\nTesting with month that has data: $testYear-$testMonth\n";
        
        $fn_general = new Class_general();
        $fn_gamification = new Class_gamification();
        $fn_general->__set('constant', new Class_constant());
        $fn_gamification->__set('constant', new Class_constant());
        $fn_gamification->__set('fn_general', $fn_general);
        
        $woDetails = $fn_gamification->getWoDetailsForGamification($testYear, $testMonth, $userId);
        echo "Found " . count($woDetails) . " work orders for $testYear-$testMonth\n";
        
        if (count($woDetails) > 0) {
            echo "First work order details:\n";
            $first = $woDetails[0];
            foreach ($first as $key => $value) {
                echo "  $key: $value\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
