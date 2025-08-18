<?php
/**
 * Test script for multiple work type support in PTW approval system
 */

// Include the approval API functions
require_once 'api/ptw_approval.php';

echo "<h2>Testing Multiple Work Type Support in PTW Approval</h2>\n";

// Test data with multiple work types
$testData = [
    'ptw_work_types' => 'cold_work,hot_work,confined_space',
    'ptw_checklist_cold_work' => '{"coldElectricalWork":"Electrical Work","coldLockOutTagOut":"Lock Out & Tagged Out","coldSpecialPrecautions":"Special safety measures required"}',
    'ptw_checklist_hot_work' => '{"hotWelding":"Welding","hotFirewatch":"Firewatch","hotGasMonitoring":"Gas monitoring"}',
    'ptw_checklist_confined_space' => '{"csRespiratoryAtmosphere":"Respiratory Atmosphere","csGasMonitoring":"Gas Monitoring Required","csEntryAttendant":"Entry Attendant Present"}'
];

echo "<h3>Input Data:</h3>\n";
echo "<pre>" . print_r($testData, true) . "</pre>\n";

// Test the enhanced transformation
$transformed = transformDatabaseToFrontend($testData);

echo "<h3>Transformed Data (relevant fields):</h3>\n";
$relevantFields = [
    'ptw_work_types',
    'work_types_selected',
    'cold_activities',
    'cold_precautions',
    'hot_activities', 
    'hot_precautions',
    'cs_activities',
    'cs_precautions'
];

foreach ($relevantFields as $field) {
    if (isset($transformed[$field])) {
        echo "<strong>{$field}:</strong> " . $transformed[$field] . "<br>\n";
    }
}

echo "<h3>Test parseChecklistData function:</h3>\n";
$testChecklist = '{"coldElectricalWork":"Electrical Work","coldLockOutTagOut":"Lock Out & Tagged Out","coldOthers":"Custom work"}';
echo "<strong>Input:</strong> " . $testChecklist . "<br>\n";
echo "<strong>Parsed:</strong> " . parseChecklistData($testChecklist) . "<br>\n";

echo "<h3>Test Results:</h3>\n";

// Verify multiple work types are handled
$hasMultipleTypes = !empty($transformed['cold_activities']) && !empty($transformed['hot_activities']) && !empty($transformed['cs_activities']);
echo "<strong>Multiple work types supported:</strong> " . ($hasMultipleTypes ? "✅ YES" : "❌ NO") . "<br>\n";

// Verify cold work data
$coldWorkParsed = !empty($transformed['cold_activities']);
echo "<strong>Cold work data parsed:</strong> " . ($coldWorkParsed ? "✅ YES" : "❌ NO") . "<br>\n";

// Verify hot work data  
$hotWorkParsed = !empty($transformed['hot_activities']);
echo "<strong>Hot work data parsed:</strong> " . ($hotWorkParsed ? "✅ YES" : "❌ NO") . "<br>\n";

// Verify confined space data
$confinedSpaceParsed = !empty($transformed['cs_activities']);
echo "<strong>Confined space data parsed:</strong> " . ($confinedSpaceParsed ? "✅ YES" : "❌ NO") . "<br>\n";

echo "<h3>Complete Transformed Data:</h3>\n";
echo "<pre>" . print_r($transformed, true) . "</pre>\n";
