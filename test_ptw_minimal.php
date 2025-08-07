<?php
// Test PTW form submission with minimal data
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PTW Form Submission Test</h2>\n";

try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    require_once 'api/function/f_general.php';
    require_once 'api/function/f_ptw.php';
    
    $fn_general = new Class_general();
    $fn_ptw = new Class_ptw();
    $fn_ptw->__set('fn_general', $fn_general);
    
    Class_db::getInstance()->db_connect();
    echo "✅ Database connected<br>\n";
    
    // Test with minimal data that should match the form fields
    $minimal_permit_data = array(
        'ptw_permit_number' => 'TEST' . date('YmdHis'),
        'ptw_permit_description' => 'Test permit from form',
        'ptw_work_area' => 'Test Area',
        'ptw_work_type' => 'COLD_WORK',
        'ptw_risk_level' => 'LOW',
        'ptw_valid_from' => date('Y-m-d H:i:s'),
        'ptw_valid_to' => date('Y-m-d H:i:s', strtotime('+8 hours')),
        'ptw_applicant_name' => 'Test User',
        'ptw_status' => 'DRAFT',
        'site_id' => 1,
        'created_by' => 1,
        'created_date' => date('Y-m-d H:i:s')
    );
    
    echo "Creating minimal permit record...<br>\n";
    
    Class_db::getInstance()->db_beginTransaction();
    
    try {
        // Try direct database insert instead of using the PTW function
        $permit_id = Class_db::getInstance()->db_insert('ptw_permit', $minimal_permit_data);
        echo "✅ Direct database insert successful! Permit ID: $permit_id<br>\n";
        
        Class_db::getInstance()->db_commit();
        echo "✅ Transaction committed<br>\n";
        
        // Check if record exists
        $count = Class_db::getInstance()->db_count('ptw_permit', array());
        echo "Total permits in database: $count<br>\n";
        
        if ($count > 0) {
            $permits = Class_db::getInstance()->db_select('ptw_permit', array(), 'ptw_permit_id DESC', '1');
            $latest = $permits[0];
            echo "<strong>Latest permit:</strong><br>\n";
            echo "- ID: " . $latest['ptw_permit_id'] . "<br>\n";
            echo "- Number: " . $latest['ptw_permit_number'] . "<br>\n";
            echo "- Description: " . $latest['ptw_permit_description'] . "<br>\n";
            echo "- Status: " . $latest['ptw_status'] . "<br>\n";
            echo "- Created: " . $latest['created_date'] . "<br>\n";
        }
        
    } catch (Exception $e) {
        Class_db::getInstance()->db_rollback();
        echo "❌ Database insert failed: " . $e->getMessage() . "<br>\n";
        
        // Try to identify which columns are causing issues
        echo "<br><strong>Debugging column issues...</strong><br>\n";
        echo "The error suggests some columns don't exist in the PTW table.<br>\n";
        echo "This means the database setup script may not have run correctly,<br>\n";
        echo "or there's a mismatch between local and remote database schemas.<br>\n";
    }
    
    Class_db::getInstance()->db_close();
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "<br>\n";
}

echo "<hr>\n";
echo "<h3>Next Steps:</h3>\n";
echo "1. If this worked, your PTW form submission should now work<br>\n";
echo "2. Try submitting the form in your browser<br>\n";
echo "3. If it still fails, check the browser console for errors<br>\n";
echo "4. Make sure you're logged into the GEMS2 system<br>\n";

?>
