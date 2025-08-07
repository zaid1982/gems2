<?php
// Direct PTW database test - bypass authentication
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Direct PTW Database Test</h2>\n";

try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    require_once 'api/function/f_general.php';
    require_once 'api/function/f_ptw.php';
    
    echo "1. Loading dependencies... ✅<br>\n";
    
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_ptw = new Class_ptw();
    
    // Set up dependencies
    $fn_ptw->__set('constant', $constant);
    $fn_ptw->__set('fn_general', $fn_general);
    
    echo "2. Connecting to database...<br>\n";
    Class_db::getInstance()->db_connect();
    echo "   Database connected ✅<br>\n";
    
    echo "3. Testing direct permit creation...<br>\n";
    
    // Test data
    $permit_data = array(
        'ptw_permit_number' => 'TEST001PTW001',
        'ptw_permit_description' => 'Test permit for debugging',
        'ptw_work_area' => 'Test Area',
        'ptw_work_type' => 'COLD_WORK',
        'ptw_risk_level' => 'LOW',
        'ptw_valid_from' => '2024-08-07 08:00:00',
        'ptw_valid_to' => '2024-08-07 17:00:00',
        'ptw_contractor_company' => 'Test Company',
        'ptw_remarks' => 'Test remarks',
        'ptw_applicant_name' => 'Test Applicant',
        'ptw_applicant_contact' => '123456789',
        'ptw_applicant_company_dept' => 'Test Dept',
        'ptw_hazards' => 'Test hazards',
        'ptw_control_measures' => 'Test control measures',
        'ptw_checklist_data' => '{"general": ["safety_briefing", "ppe_available"]}',
        'ptw_status' => 'DRAFT',
        'site_id' => 1,
        'created_by' => 1,
        'created_date' => date('Y-m-d H:i:s')
    );
    
    echo "4. Creating permit record...<br>\n";
    
    Class_db::getInstance()->db_beginTransaction();
    
    try {
        $permit_id = $fn_ptw->create_permit($permit_data);
        echo "   Permit created with ID: " . $permit_id . " ✅<br>\n";
        
        Class_db::getInstance()->db_commit();
        echo "   Transaction committed ✅<br>\n";
        
    } catch (Exception $e) {
        Class_db::getInstance()->db_rollback();
        echo "   Error creating permit: " . $e->getMessage() . " ❌<br>\n";
        throw $e;
    }
    
    echo "5. Verifying database records...<br>\n";
    
    $permit_count = Class_db::getInstance()->db_count('ptw_permit', array());
    echo "   Total permits in database: " . $permit_count . "<br>\n";
    
    if ($permit_count > 0) {
        $permits = Class_db::getInstance()->db_select('ptw_permit', array(), 'ptw_permit_id DESC', '3');
        echo "   Recent permits:<br>\n";
        foreach ($permits as $permit) {
            echo "   - ID: " . $permit['ptw_permit_id'] . 
                 ", Number: " . $permit['ptw_permit_number'] . 
                 ", Status: " . $permit['ptw_status'] . 
                 ", Created: " . $permit['created_date'] . "<br>\n";
        }
    }
    
    Class_db::getInstance()->db_close();
    echo "<br><strong>✅ Direct database test SUCCESSFUL!</strong><br>\n";
    
} catch (Exception $e) {
    echo "<br><strong>❌ Test FAILED:</strong> " . $e->getMessage() . "<br>\n";
    echo "<strong>Stack trace:</strong><br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<hr>\n";
echo "<h3>Next Steps for Form Testing:</h3>\n";
echo "1. If this test worked, the issue is likely with JWT authentication in the API<br>\n";
echo "2. Check browser console for AJAX errors when clicking 'Submit for Approval'<br>\n";
echo "3. Verify the Authorization header is being sent correctly<br>\n";
echo "4. Check if user session/login is active<br>\n";
?>
