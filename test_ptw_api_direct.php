<?php
// Quick test of PTW API to see if sys_site issue is resolved
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing PTW API ===\n";

// Simulate a POST request to the PTW API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array(
    'action' => 'create_permit',
    'description' => 'Test PTW submission',
    'work_area' => 'Test Area',
    'work_type' => 'HOT_WORK',
    'risk_level' => 'HIGH',
    'valid_from' => '2025-08-07',
    'valid_to' => '2025-08-08',
    'applicant_name' => 'Test User',
    'status' => 'PENDING_SUPERVISOR'
);

// Set test authorization header
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_token_123';

try {
    // Include the PTW API
    include('api/ptw.php');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
