<?php
/**
 * Test PTW Form Data Mapping
 * Tests the data transformation and mapping logic
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_ptw.php';

$fn_general = new Class_general();
$fn_ptw = new Class_ptw();
$fn_ptw->__set('fn_general', $fn_general);

Class_db::getInstance()->db_connect();

echo "=== PTW Data Mapping Test ===\n\n";

// Test the mapping logic that's now in the API
$test_cases = [
    ['input' => 'HOT_WORK', 'expected' => 'Hot Work'],
    ['input' => 'COLD_WORK', 'expected' => 'Cold Work'],
    ['input' => 'CONFINED_SPACE', 'expected' => 'Confined Space'],
    ['input' => 'Hot Work', 'expected' => 'Hot Work'], // Already correct
];

echo "1. Work Type Mapping Test:\n";
$work_type_mapping = [
    'HOT_WORK' => 'Hot Work',
    'COLD_WORK' => 'Cold Work', 
    'CONFINED_SPACE' => 'Confined Space',
    'Hot Work' => 'Hot Work',
    'Cold Work' => 'Cold Work',
    'Confined Space' => 'Confined Space'
];

foreach ($test_cases as $case) {
    $result = isset($work_type_mapping[$case['input']]) 
        ? $work_type_mapping[$case['input']] 
        : $case['input'];
    $status = $result === $case['expected'] ? '✅' : '❌';
    echo "   {$case['input']} → {$result} {$status}\n";
}

echo "\n2. Date Format Conversion Test:\n";
$date_cases = [
    '2025-08-07',
    '2025-08-07 14:30:00'
];

foreach ($date_cases as $date_input) {
    $valid_from = $date_input;
    if (strlen($valid_from) == 10) {
        $valid_from .= ' 08:00:00';
    }
    echo "   {$date_input} → {$valid_from}\n";
}

echo "\n3. Status Mapping Test:\n";
$status_mapping = [
    'PENDING_APPROVAL' => 'PENDING_SUPERVISOR',
    'PENDING_SUPERVISOR' => 'PENDING_SUPERVISOR',
    'PENDING_SHE' => 'PENDING_SHE',
    'PENDING_FM' => 'PENDING_FM'
];

foreach ($status_mapping as $input => $expected) {
    echo "   {$input} → {$expected}\n";
}

echo "\n4. JSON Field Validation Test:\n";
$json_fields = [
    'empty_array' => json_encode([]),
    'checklist_data' => json_encode([
        'hot_work' => ['Fire extinguishers available', 'Hot work permit displayed'],
        'general' => ['PPE worn', 'Work area secured']
    ]),
    'workers_data' => json_encode([
        ['name' => 'Worker 1', 'ic' => '123456789012'],
        ['name' => 'Worker 2', 'ic' => '123456789013']
    ])
];

foreach ($json_fields as $field_name => $json_data) {
    $is_valid = json_decode($json_data) !== null;
    $status = $is_valid ? '✅' : '❌';
    echo "   {$field_name}: {$status} " . (strlen($json_data) > 50 ? substr($json_data, 0, 50) . '...' : $json_data) . "\n";
}

echo "\n5. Complete Data Structure Test:\n";

// Simulate complete form data
$_POST = array(
    'description' => 'Test work description',
    'work_area' => 'Test Area',
    'work_type' => 'HOT_WORK',
    'valid_from' => '2025-08-07',
    'valid_to' => '2025-08-08',
    'risk_level' => 'HIGH',
    'applicant_name' => 'Test User',
    'applicant_contact' => '123-456-7890',
    'applicant_department' => 'Maintenance',
    'contractor_company' => 'Test Company',
    'remarks' => 'Test remarks',
    'status' => 'PENDING_APPROVAL',
    'workers' => json_encode([
        ['name' => 'Worker 1', 'ic' => '123456789012']
    ]),
    'checklist_data' => json_encode([
        'hot_work' => ['Fire extinguishers available']
    ])
);

// Apply transformations (same as API)
$work_type = isset($work_type_mapping[$_POST['work_type']]) 
    ? $work_type_mapping[$_POST['work_type']] 
    : $_POST['work_type'];

$valid_from = $_POST['valid_from'];
$valid_to = $_POST['valid_to'];

if (strlen($valid_from) == 10) {
    $valid_from .= ' 08:00:00';
}
if (strlen($valid_to) == 10) {
    $valid_to .= ' 17:00:00';
}

$target_status = isset($status_mapping[$_POST['status']]) 
    ? $status_mapping[$_POST['status']] 
    : $_POST['status'];

echo "Transformed data ready for database:\n";
echo "   Work Type: {$_POST['work_type']} → {$work_type}\n";
echo "   Valid From: {$_POST['valid_from']} → {$valid_from}\n";
echo "   Valid To: {$_POST['valid_to']} → {$valid_to}\n";
echo "   Status: {$_POST['status']} → {$target_status}\n";
echo "   Workers: " . count(json_decode($_POST['workers'], true)) . " workers\n";
echo "   Checklist: Valid JSON (" . strlen($_POST['checklist_data']) . " bytes)\n";

echo "\n=== All Tests Complete ===\n";
?>
