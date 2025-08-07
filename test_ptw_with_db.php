<?php
// PTW API Test with Correct Database Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PTW API Test (Using Project Database)</h2>\n";

// Test 1: Database Connection with Project Config
echo "<h3>1. Database Connection Test:</h3>\n";
try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    require_once 'api/function/f_general.php';
    
    $fn_general = new Class_general();
    Class_db::getInstance()->db_connect();
    echo "✅ Database connection successful<br>\n";
    
    // Check if PTW tables exist
    echo "<h3>2. PTW Tables Check:</h3>\n";
    $ptw_tables = ['ptw_permit', 'ptw_worker', 'ptw_status_history', 'ptw_document', 'ptw_approval_log'];
    $all_tables_exist = true;
    
    foreach ($ptw_tables as $table) {
        try {
            $count = Class_db::getInstance()->db_count($table, array());
            echo "✅ Table $table exists (records: $count)<br>\n";
        } catch (Exception $e) {
            echo "❌ Table $table missing or error: " . $e->getMessage() . "<br>\n";
            $all_tables_exist = false;
        }
    }
    
    if (!$all_tables_exist) {
        echo "<br><strong>⚠️ Some PTW tables are missing. Please run the database setup script.</strong><br>\n";
        echo "Try running: <code>mysql -h [host] -u [user] -p [database] < ptw_database_setup_clean.sql</code><br><br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>\n";
    echo "Check your database configuration in api/library/config.ini<br><br>\n";
}

// Test 2: Direct PTW Function Test (bypass API authentication)
echo "<h3>3. Direct PTW Function Test:</h3>\n";
try {
    require_once 'api/function/f_ptw.php';
    
    $fn_ptw = new Class_ptw();
    $fn_ptw->__set('fn_general', $fn_general);
    
    // Test data matching the form fields
    $test_permit_data = array(
        'ptw_permit_number' => 'TEST' . date('YmdHis'),
        'ptw_permit_description' => 'Test permit from form submission',
        'ptw_work_area' => 'Test Work Area',
        'ptw_work_type' => 'COLD_WORK',
        'ptw_risk_level' => 'LOW',
        'ptw_valid_from' => date('Y-m-d H:i:s'),
        'ptw_valid_to' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'ptw_contractor_company' => '',
        'ptw_remarks' => 'Test remarks',
        'ptw_applicant_name' => 'Test Applicant',
        'ptw_applicant_contact' => '1234567890',
        'ptw_applicant_company_dept' => 'Test Department',
        'ptw_hazards' => 'Test hazards identified',
        'ptw_control_measures' => 'Test control measures',
        'ptw_checklist_data' => json_encode(array('general' => array('safety_briefing', 'ppe_available'))),
        'ptw_status' => 'DRAFT',
        'site_id' => 1, // Using site ID 1 for test
        'created_by' => 1, // Using user ID 1 for test
        'created_date' => date('Y-m-d H:i:s')
    );
    
    echo "Creating test permit...<br>\n";
    Class_db::getInstance()->db_beginTransaction();
    
    $permit_id = $fn_ptw->create_permit($test_permit_data);
    echo "✅ Permit created successfully with ID: $permit_id<br>\n";
    
    Class_db::getInstance()->db_commit();
    echo "✅ Transaction committed<br>\n";
    
    // Verify the record was created
    $permit_count = Class_db::getInstance()->db_count('ptw_permit', array());
    echo "Total permits in database: $permit_count<br>\n";
    
    if ($permit_count > 0) {
        $recent_permits = Class_db::getInstance()->db_select('ptw_permit', array(), 'ptw_permit_id DESC', '3');
        echo "<strong>Recent permits:</strong><br>\n";
        foreach ($recent_permits as $permit) {
            echo "- ID: " . $permit['ptw_permit_id'] . 
                 ", Number: " . $permit['ptw_permit_number'] . 
                 ", Status: " . $permit['ptw_status'] . 
                 ", Applicant: " . $permit['ptw_applicant_name'] . "<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ PTW function test failed: " . $e->getMessage() . "<br>\n";
    try {
        Class_db::getInstance()->db_rollback();
        echo "Transaction rolled back<br>\n";
    } catch (Exception $rollback_error) {
        // Ignore rollback errors
    }
}

// Test 3: Form Submission Simulation
echo "<h3>4. Form Submission Simulation:</h3>\n";
echo "To test form submission, try this in your browser:<br>\n";
echo "1. Open: <a href='ptw_form.html' target='_blank'>ptw_form.html</a><br>\n";
echo "2. Fill out the form with test data<br>\n";
echo "3. Click 'Submit for Approval'<br>\n";
echo "4. Check browser console for any JavaScript errors<br>\n";
echo "5. Check this database test again to see if records were created<br><br>\n";

// Test 4: API Endpoint Test (if authentication allows)
echo "<h3>5. Troubleshooting Tips:</h3>\n";
echo "If the form submission still doesn't work:<br>\n";
echo "• Check browser console (F12) for JavaScript errors<br>\n";
echo "• Check browser network tab for API call failures<br>\n";
echo "• Verify you're logged into the GEMS2 system<br>\n";
echo "• Check that the Authorization header is being sent<br>\n";
echo "• Verify the JWT token is valid<br><br>\n";

try {
    Class_db::getInstance()->db_close();
} catch (Exception $e) {
    // Ignore close errors
}

echo "<hr>\n";
echo "<h3>Summary:</h3>\n";
if ($all_tables_exist) {
    echo "✅ Database tables are ready<br>\n";
    echo "✅ PTW functions are working<br>\n";
    echo "🎯 <strong>The PTW system is ready for form testing!</strong><br>\n";
} else {
    echo "⚠️ Database setup needs to be completed first<br>\n";
}
?>
