<?php
// Test PTW API endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PTW API Test</h2>\n";

// Test 1: Check if API file can be loaded
echo "<h3>1. API File Loading Test:</h3>\n";
if (file_exists('api/ptw.php')) {
    echo "✅ api/ptw.php exists<br>\n";
} else {
    echo "❌ api/ptw.php missing<br>\n";
    exit;
}

// Test 2: Simulate POST request to PTW API
echo "<h3>2. API Endpoint Test:</h3>\n";

// Set up minimal test data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array(
    'ptwPermitDescription' => 'Test permit description',
    'ptwWorkArea' => 'Test work area',
    'ptwWorkType' => 'COLD_WORK',
    'ptwValidFrom' => '2024-01-01',
    'ptwApplicantName' => 'Test User',
    'submit_for_approval' => 'true'
);

// Mock headers for testing
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_token';

echo "Test data prepared:<br>\n";
echo "- Description: " . $_POST['ptwPermitDescription'] . "<br>\n";
echo "- Work Area: " . $_POST['ptwWorkArea'] . "<br>\n";
echo "- Work Type: " . $_POST['ptwWorkType'] . "<br>\n";
echo "- Valid From: " . $_POST['ptwValidFrom'] . "<br>\n";
echo "- Applicant: " . $_POST['ptwApplicantName'] . "<br>\n";

echo "<br><strong>Attempting to call API...</strong><br>\n";

// Capture output from API
ob_start();
try {
    include 'api/ptw.php';
    $api_output = ob_get_contents();
} catch (Exception $e) {
    $api_output = "Exception: " . $e->getMessage();
} catch (Error $e) {
    $api_output = "Error: " . $e->getMessage();
}
ob_end_clean();

echo "<h3>3. API Response:</h3>\n";
echo "<pre>" . htmlspecialchars($api_output) . "</pre>\n";

// Test 3: Check if any records were created
echo "<h3>4. Database Check After API Call:</h3>\n";
try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    require_once 'api/function/f_general.php';
    
    $fn_general = new Class_general();
    Class_db::getInstance()->db_connect();
    
    $permit_count = Class_db::getInstance()->db_count('ptw_permit', array());
    echo "PTW Permits in database: " . $permit_count . "<br>\n";
    
    if ($permit_count > 0) {
        $permits = Class_db::getInstance()->db_select('ptw_permit', array(), null, '5');
        echo "<strong>Recent permits:</strong><br>\n";
        foreach ($permits as $permit) {
            echo "- ID: " . $permit['ptw_permit_id'] . ", Number: " . $permit['ptw_permit_number'] . ", Status: " . $permit['ptw_status'] . "<br>\n";
        }
    }
    
    Class_db::getInstance()->db_close();
} catch (Exception $e) {
    echo "Database check error: " . $e->getMessage() . "<br>\n";
}

?>
