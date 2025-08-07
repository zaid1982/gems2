<?php
// Simple test script to check PTW API functions

require_once 'api/function/db.php';
require_once 'api/function/f_general.php'; 
require_once 'api/function/f_ptw.php';

try {
    $fn_general = new Class_general();
    $fn_ptw = new Class_ptw();
    $fn_ptw->__set('fn_general', $fn_general);
    
    Class_db::getInstance()->db_connect();
    echo "Connected to database successfully!\n\n";
    
    // Test get_permits_for_she_approval
    echo "Testing get_permits_for_she_approval...\n";
    $permits = $fn_ptw->get_permits_for_she_approval(1);
    echo "Found " . count($permits) . " permits pending SHE approval:\n";
    foreach ($permits as $permit) {
        echo "- ID: {$permit['ptw_permit_id']}, Number: {$permit['ptw_permit_number']}, Description: {$permit['ptw_permit_description']}\n";
    }
    
    echo "\n";
    
    // Test get_she_summary_statistics
    echo "Testing get_she_summary_statistics...\n";
    $stats = $fn_ptw->get_she_summary_statistics(1, 1);
    echo "Statistics:\n";
    echo "- Pending: {$stats['pending']}\n";
    echo "- Approved: {$stats['approved']}\n"; 
    echo "- Rejected: {$stats['rejected']}\n";
    echo "- Total: {$stats['total']}\n";
    
    echo "\n";
    
    // Test get_she_recent_actions
    echo "Testing get_she_recent_actions...\n";
    $actions = $fn_ptw->get_she_recent_actions(1, 1);
    echo "Found " . count($actions) . " recent actions\n";
    
    echo "\nAll tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
