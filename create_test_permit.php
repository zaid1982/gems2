<?php
// Create test PTW permit data
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';

try {
    Class_db::getInstance()->db_connect();
    
    // Check if permits table exists and has data
    $permits = Class_db::getInstance()->db_select('ptw_permit', array(), 'ptw_permit_id DESC', 5);
    
    echo "Existing permits:\n";
    print_r($permits);
    
    // If no permits exist, create a test one
    if (empty($permits)) {
        echo "\nCreating test permit...\n";
        
        $test_permit = array(
            'ptw_permit_number' => 'PTW' . date('YmdHis') . '01',
            'ptw_work_description' => 'Test work for SHE approval',
            'ptw_location' => 'Test Location',
            'ptw_work_start_date' => date('Y-m-d'),
            'ptw_work_start_time' => '08:00:00',
            'ptw_work_end_date' => date('Y-m-d'),
            'ptw_work_end_time' => '17:00:00',
            'ptw_risk_level' => 'MEDIUM',
            'ptw_status' => 'PENDING_SHE',
            'site_id' => 1,
            'created_by' => 1,
            'created_date' => date('Y-m-d H:i:s')
        );
        
        $permit_id = Class_db::getInstance()->db_insert('ptw_permit', $test_permit);
        echo "Created permit with ID: " . $permit_id . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
