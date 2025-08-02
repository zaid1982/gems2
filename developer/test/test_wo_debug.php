<?php
// Test with comprehensive debug logging
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

try {
    Class_db::getInstance()->db_connect();
    
    $year = 2025;
    $month = 8; // August
    $userId = '1102';

    echo "=== Testing getWoDetailsForGamification with Debug Logging ===\n";
    echo "Year: $year, Month: $month, User ID: $userId\n\n";
    
    $fn_general = new Class_general();
    $fn_gamification = new Class_gamification();
    $fn_general->__set('constant', new Class_constant());
    $fn_gamification->__set('constant', new Class_constant());
    $fn_gamification->__set('fn_general', $fn_general);
    
    // Call the method - this will output debug logs
    $woDetails = $fn_gamification->getWoDetailsForGamification($year, $month, $userId);
    
    echo "\n=== FINAL RESULT ===\n";
    echo "Found " . count($woDetails) . " work orders\n\n";
    
    if (count($woDetails) > 0) {
        foreach ($woDetails as $index => $wo) {
            echo "WO #" . ($index + 1) . ":\n";
            echo "  Number: {$wo['woNo']}\n";
            echo "  Status: {$wo['woStatus']}\n";
            echo "  Completed: {$wo['woCompletedDate']}\n\n";
        }
    } else {
        echo "No work orders found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
