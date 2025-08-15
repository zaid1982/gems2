<?php
// Debug PTW API database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PTW API Debug Test ===\n";

try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    require_once 'api/function/f_general.php';
    require_once 'api/function/f_ptw.php';
    
    echo "✓ Files loaded successfully\n";
    
    // Test database connection
    $db = Class_db::getInstance();
    echo "✓ Database instance created\n";
    
    // Test query for permit ID 15
    $permits = $db->db_select('ptw_permit', array('ptw_permit_id' => 15));
    echo "✓ Query executed, found " . count($permits) . " permits\n";
    
    if (!empty($permits)) {
        echo "=== PERMIT DATA ===\n";
        echo json_encode($permits[0], JSON_PRETTY_PRINT) . "\n";
        
        // Test workers query
        $workers = $db->db_select('ptw_worker', array('ptw_permit_id' => 15));
        echo "✓ Found " . count($workers) . " workers\n";
        if (!empty($workers)) {
            echo "=== WORKERS DATA ===\n";
            echo json_encode($workers, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ No permit found with ID 15\n";
        
        // Check what permits exist
        $allPermits = $db->db_select('ptw_permit', array(), 'ptw_permit_id DESC LIMIT 5');
        echo "Available permits:\n";
        foreach ($allPermits as $permit) {
            echo "- ID: {$permit['ptw_permit_id']}, Number: {$permit['ptw_permit_number']}, Applicant: {$permit['ptw_applicant_name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
