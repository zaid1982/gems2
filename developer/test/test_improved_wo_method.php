<?php
// Test the improved getWoDetailsForGamification method with date and status filtering
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

try {
    // Initialize classes
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_gamification = new Class_gamification();

    // Set dependencies
    $fn_general->__set('constant', $constant);
    $fn_gamification->__set('constant', $constant);
    $fn_gamification->__set('fn_general', $fn_general);

    // Connect to database
    Class_db::getInstance()->db_connect();
    
    // Test with user 1102 and July 2024
    $year = 2024;
    $month = 7;
    $userId = '1102';

    echo "Testing improved getWoDetailsForGamification($year, $month, $userId)...\n";
    echo "This should only return completed work orders within July 2024\n\n";
    
    // Call the method
    $woDetails = $fn_gamification->getWoDetailsForGamification($year, $month, $userId);
    
    echo "Result type: " . gettype($woDetails) . "\n";
    echo "Result count: " . (is_array($woDetails) ? count($woDetails) : 'Not array') . "\n";
    
    if (is_array($woDetails) && count($woDetails) > 0) {
        echo "\nFirst few work orders:\n";
        foreach (array_slice($woDetails, 0, 3) as $i => $wo) {
            echo "WO " . ($i + 1) . ":\n";
            echo "  ID: " . $wo['woId'] . "\n";
            echo "  Number: " . $wo['woNo'] . "\n";
            echo "  Status: " . $wo['woStatus'] . "\n";
            echo "  Type: " . $wo['woType'] . "\n";
            echo "  Created: " . $wo['woCreateDate'] . "\n";
            echo "  Completed: " . $wo['woCompletedDate'] . "\n";
            echo "  On-Time Status: " . $wo['woOnTimeStatus'] . "\n";
            echo "\n";
        }
    } else {
        echo "No work orders found for this user/month/year combination.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
