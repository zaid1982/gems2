<?php
/**
 * Test PTW API End-to-End
 * Tests the complete PTW form submission through the API
 */

// Simulate form submission
$_POST = array(
    'action' => 'create_permit',
    'description' => 'Testing end-to-end API form submission',
    'work_area' => 'Main Plant Area',
    'work_type' => 'HOT_WORK', // This should be mapped to "Hot Work"
    'valid_from' => '2025-08-07', // This should be converted to datetime
    'valid_to' => '2025-08-08',   // This should be converted to datetime
    'risk_level' => 'HIGH',
    'applicant_name' => 'Jane Smith',
    'applicant_contact' => '987-654-3210',
    'applicant_department' => 'Operations',
    'contractor_company' => 'XYZ Contractors',
    'remarks' => 'Critical maintenance work - requires hot work',
    'status' => 'PENDING_APPROVAL', // This should be mapped to PENDING_SUPERVISOR
    'workers' => json_encode([
        ['name' => 'Worker A', 'ic' => '111111111111'],
        ['name' => 'Worker B', 'ic' => '222222222222']
    ]),
    'checklist_data' => json_encode([
        'hot_work' => [
            'Fire extinguishers available and checked',
            'Hot work permit displayed',
            'Fire watch assigned',
            'Combustible materials removed'
        ],
        'general' => [
            'PPE worn correctly',
            'Work area secured',
            'Emergency procedures briefed'
        ]
    ])
);

// Simulate authorization header
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer fake_token_for_testing';

echo "=== PTW API End-to-End Test ===\n";
echo "Testing complete form submission with:\n";
echo "- Work Type: {$_POST['work_type']} (should map to Hot Work)\n";
echo "- Status: {$_POST['status']} (should map to PENDING_SUPERVISOR)\n";
echo "- Workers: " . count(json_decode($_POST['workers'], true)) . " workers\n";
echo "- Date format: {$_POST['valid_from']} (should convert to datetime)\n\n";

// Capture API output
ob_start();

try {
    // Mock JWT verification by creating a fake function
    function mock_jwt_check() {
        return (object) ['userId' => 1];
    }
    
    // Include the API but intercept JWT check
    $original_file = file_get_contents('api/ptw.php');
    
    // Create a temporary file with mocked JWT
    $temp_api = str_replace(
        '$jwt_data = $fn_login->check_jwt($headers[\'Authorization\']);',
        '$jwt_data = mock_jwt_check();',
        $original_file
    );
    
    // Also remove the Authorization header check for testing
    $temp_api = str_replace(
        'if (!isset($headers[\'Authorization\'])) {
        throw new Exception(\'[\' . __LINE__ . \'] - Parameter Authorization empty\');
    }',
        '// Authorization check bypassed for testing',
        $temp_api
    );
    
    file_put_contents('test_api_temp.php', $temp_api);
    
    // Execute the modified API
    include 'test_api_temp.php';
    
} catch (Exception $e) {
    echo "❌ API Error: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

// Clean up temp file
if (file_exists('test_api_temp.php')) {
    unlink('test_api_temp.php');
}

echo "=== API Response ===\n";
echo $output . "\n";

// Check database for created records
try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    
    Class_db::getInstance()->db_connect();
    
    $permits = Class_db::getInstance()->db_select(
        'ptw_permit', 
        [], 
        'ptw_permit_id, ptw_permit_number, ptw_work_type, ptw_status, ptw_valid_from, created_date', 
        'ptw_permit_id DESC', 
        3
    );
    
    echo "=== Recent PTW Records ===\n";
    foreach ($permits as $permit) {
        echo "ID: {$permit['ptw_permit_id']}\n";
        echo "Number: {$permit['ptw_permit_number']}\n";
        echo "Work Type: {$permit['ptw_work_type']}\n";
        echo "Status: {$permit['ptw_status']}\n";
        echo "Valid From: {$permit['ptw_valid_from']}\n";
        echo "Created: {$permit['created_date']}\n";
        echo "---\n";
    }
    
    // Check workers
    if (!empty($permits)) {
        $latest_permit_id = $permits[0]['ptw_permit_id'];
        $workers = Class_db::getInstance()->db_select(
            'ptw_worker',
            ['ptw_permit_id' => $latest_permit_id],
            'worker_name, worker_ic_number'
        );
        
        echo "Workers for latest permit:\n";
        foreach ($workers as $worker) {
            echo "- {$worker['worker_name']} (IC: {$worker['worker_ic_number']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
