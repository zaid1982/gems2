<?php
// Test the new PTW permit number generation
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('api/library/constant.php');
require_once('api/function/db.php');

// Recreate the function for testing
function generateUniquePtwNumber() {
    $timestamp = date('YmdHis'); // YYYYMMDDhhmmss format
    $permit_number = 'PTW' . $timestamp;
    
    // Add microseconds to ensure uniqueness even for rapid submissions
    $microseconds = substr(microtime(), 2, 2); // Get 2 digits of microseconds
    $permit_number .= $microseconds;
    
    // Optional: Verify uniqueness against database
    try {
        $existing = Class_db::getInstance()->db_select('ptw_permit', array('ptw_permit_number' => $permit_number));
        if (count($existing) > 0) {
            // If somehow duplicate, add random suffix
            $permit_number .= rand(10, 99);
        }
    } catch (Exception $e) {
        // If database check fails, continue with the generated number
        error_log("PTW API: Could not verify permit number uniqueness: " . $e->getMessage());
    }
    
    return $permit_number;
}

echo "=== PTW Permit Number Generation Test ===\n";

// Generate several test permit numbers
for ($i = 1; $i <= 10; $i++) {
    $permit_number = generateUniquePtwNumber();
    echo "Test $i: $permit_number\n";
    
    // Small delay to show timestamp progression
    usleep(100000); // 0.1 second delay
}

echo "\n=== Format Explanation ===\n";
echo "Format: PTWYYYYMMDDhhmmssXX\n";
echo "Where:\n";
echo "- PTW = Prefix\n";
echo "- YYYY = Year (4 digits)\n";
echo "- MM = Month (2 digits)\n";
echo "- DD = Day (2 digits)\n";
echo "- hh = Hour (2 digits, 24-hour format)\n";
echo "- mm = Minute (2 digits)\n";
echo "- ss = Second (2 digits)\n";
echo "- XX = Microseconds (2 digits) for uniqueness\n";

$example = generateUniquePtwNumber();
echo "\nExample: $example\n";
echo "This ensures each permit number is unique and sortable by creation time.\n";
?>
