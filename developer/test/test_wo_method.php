<?php
// Simple test endpoint to verify the getWoDetailsForGamification method works
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
    
    // Test parameters
    $year = 2024;
    $month = 1;
    $userId = '1';

    echo "Testing getWoDetailsForGamification($year, $month, $userId)...\n";
    
    // Call the method directly
    $woDetails = $fn_gamification->getWoDetailsForGamification($year, $month, $userId);
    
    echo "Result type: " . gettype($woDetails) . "\n";
    echo "Result count: " . (is_array($woDetails) ? count($woDetails) : 'Not array') . "\n";
    echo "Result data:\n";
    echo json_encode($woDetails, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
