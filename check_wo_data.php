<?php
// Test to find what data is available
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';

try {
    Class_db::getInstance()->db_connect();
    
    echo "Checking available work order data...\n\n";
    
    // Check what completed work orders exist in the database
    $completedWos = Class_db::getInstance()->db_select2('wo_task', 
        array('wo_task_status' => '16'), // Only completed
        'wo_task_time_created DESC', '5', 0);
    
    echo "Found " . count($completedWos) . " completed work orders:\n";
    foreach ($completedWos as $wo) {
        echo "- WO No: " . ($wo['wo_task_no'] ?: 'N/A') . 
             ", Created: " . $wo['wo_task_time_created'] . 
             ", Completed: " . ($wo['wo_task_time_verified'] ?: 'Not set') . 
             ", Assigned to: " . $wo['wo_task_assigned_to'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
