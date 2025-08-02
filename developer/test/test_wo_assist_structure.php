<?php
// Test to check wo_task_assist table structure
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';

try {
    Class_db::getInstance()->db_connect();
    
    echo "Testing wo_task_assist table structure...\n\n";
    
    // Get a sample record to see the field structure
    $sample = Class_db::getInstance()->db_select2('wo_task_assist', array(), '', '1');
    
    if (!empty($sample)) {
        echo "Sample wo_task_assist record:\n";
        print_r($sample[0]);
    } else {
        echo "No records found in wo_task_assist table\n";
    }
    
    // Also check for user_id 1102 specifically
    echo "\nChecking records for user_id 1102:\n";
    $userRecords = Class_db::getInstance()->db_select2('wo_task_assist', array('user_id' => '1102'), '', '3');
    
    if (!empty($userRecords)) {
        foreach ($userRecords as $i => $record) {
            echo "Record " . ($i + 1) . ":\n";
            print_r($record);
            echo "\n";
        }
    } else {
        echo "No records found for user_id 1102\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
