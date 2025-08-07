<?php
/**
 * Test PTW Form Submission
 * Tests the actual form data format being sent by the JavaScript
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files (minimal set)
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_ptw.php';

date_default_timezone_set("Asia/Kuala_Lumpur");

// Initialize classes
$fn_general = new Class_general();
$fn_ptw = new Class_ptw();
$fn_ptw->__set('fn_general', $fn_general);

// Initialize database
try {
    Class_db::getInstance()->db_connect();
    echo "✅ Database connected successfully\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Simulate the form data being sent by JavaScript (fix enum values and JSON)
$_POST = array(
    'action' => 'create_permit',
    'description' => 'Test work description from form',
    'work_area' => 'Test Area 1',
    'work_type' => 'Hot Work', // Use correct enum value
    'valid_from' => '2025-08-07 08:00:00', // Use datetime format
    'valid_to' => '2025-08-08 17:00:00',   // Use datetime format
    'risk_level' => 'MEDIUM',
    'applicant_name' => 'John Doe',
    'applicant_contact' => '123-456-7890',
    'applicant_department' => 'Maintenance',
    'contractor_company' => 'ABC Company',
    'remarks' => 'Test remarks',
    'status' => 'PENDING_APPROVAL',
    'workers' => json_encode([
        ['name' => 'Worker 1', 'ic' => '123456789012'],
        ['name' => 'Worker 2', 'ic' => '123456789013']
    ]),
    'checklist_data' => json_encode([
        'hot_work' => ['Fire extinguishers available', 'Hot work permit displayed'],
        'general' => ['PPE worn', 'Work area secured']
    ])
);

// Simulate user authentication (normally from JWT)
$user_id = 1; // Admin user
$user_site_id = 1; // Default site

echo "\n=== Testing PTW Form Submission ===\n";
echo "User ID: $user_id\n";
echo "Site ID: $user_site_id\n";
echo "Action: " . $_POST['action'] . "\n";
echo "Description: " . $_POST['description'] . "\n";
echo "Work Type: " . $_POST['work_type'] . "\n";
echo "Status: " . $_POST['status'] . "\n\n";

// Test the create_permit function directly with form data format
try {
    // Prepare permit data in the format the form sends
    $permit_data = array(
        'ptw_permit_number' => 'TEST20250807form001',
        'ptw_permit_description' => $_POST['description'],
        'ptw_work_area' => $_POST['work_area'],
        'ptw_work_type' => $_POST['work_type'],
        'ptw_risk_level' => $_POST['risk_level'],
        'ptw_valid_from' => $_POST['valid_from'],
        'ptw_valid_to' => $_POST['valid_to'],
        'ptw_contractor_company' => $_POST['contractor_company'],
        'ptw_remarks' => $_POST['remarks'],
        'ptw_applicant_name' => $_POST['applicant_name'],
        'ptw_applicant_contact' => $_POST['applicant_contact'],
        'ptw_applicant_company_dept' => $_POST['applicant_department'],
        'ptw_work_duration' => '',
        'ptw_checklist_cold_work' => json_encode([]), // Empty JSON array instead of null
        'ptw_checklist_hot_work' => json_encode(['Fire extinguishers available', 'Hot work permit displayed']),
        'ptw_checklist_confined_space' => json_encode([]), // Empty JSON array instead of null
        'ptw_hazard_checklist' => $_POST['checklist_data'],
        'ptw_declaration_checklist' => json_encode([]), // Empty JSON array instead of null
        'site_id' => $user_site_id,
        'created_by' => $user_id,
        'created_date' => date('Y-m-d H:i:s')
    );
    
    echo "\n=== Creating PTW Permit ===\n";
    $permit_id = $fn_ptw->create_permit($permit_data);
    
    if ($permit_id) {
        echo "✅ PTW Permit created successfully!\n";
        echo "Permit ID: $permit_id\n";
        
        // Update the permit status
        Class_db::getInstance()->db_update('ptw_permit', 
            array('ptw_status' => $_POST['status']),
            array('ptw_permit_id' => $permit_id)
        );
        echo "✅ Status updated to: " . $_POST['status'] . "\n";
        
    } else {
        echo "❌ Failed to create PTW permit\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating permit: " . $e->getMessage() . "\n";
}

// Check if record was created
try {
    $permits = Class_db::getInstance()->db_select('ptw_permit', [], '*', 'ptw_permit_id DESC', 5);
    
    echo "\n=== Recent PTW Permits ===\n";
    foreach ($permits as $permit) {
        echo "ID: {$permit['ptw_permit_id']}\n";
        echo "Number: {$permit['ptw_permit_number']}\n";
        echo "Description: {$permit['ptw_permit_description']}\n";
        echo "Status: {$permit['ptw_status']}\n";
        echo "Created: {$permit['created_date']}\n";
        echo "---\n";
    }
    
    $total = Class_db::getInstance()->db_count('ptw_permit');
    echo "Total permits in database: $total\n";
    
} catch (Exception $e) {
    echo "❌ Failed to check records: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
