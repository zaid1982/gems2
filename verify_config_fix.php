<?php
/**
 * Simple test to verify that configuration refresh is working
 * This script will help you test the fix
 */

require_once 'api/constant.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_db.php';
require_once 'api/function/f_gamification.php';

echo "=== TESTING GAMIFICATION CONFIG REFRESH ===\n\n";

try {
    // Create gamification instance
    $gamification = new Class_gamification();
    
    // Test 1: Show current config values
    echo "Test 1: Getting current configuration values\n";
    echo "============================================\n";
    
    // Use reflection to access private getConfig method
    $reflection = new ReflectionClass($gamification);
    $getConfigMethod = $reflection->getMethod('getConfig');
    $getConfigMethod->setAccessible(true);
    
    $testKeys = [
        'tier_medalist_threshold',
        'tier_finisher_threshold', 
        'weight_completed',
        'weight_ontime',
        'point_scale_factor'
    ];
    
    echo "Current values:\n";
    foreach ($testKeys as $key) {
        $value = $getConfigMethod->invoke($gamification, $key, 'NOT_FOUND');
        echo "   $key = $value\n";
    }
    
    // Test 2: Refresh configuration and check again
    echo "\nTest 2: Refreshing configuration\n";
    echo "================================\n";
    
    echo "Calling refreshConfig()...\n";
    $gamification->refreshConfig();
    
    echo "Values after refresh:\n";
    foreach ($testKeys as $key) {
        $value = $getConfigMethod->invoke($gamification, $key, 'NOT_FOUND');
        echo "   $key = $value\n";
    }
    
    // Test 3: Simulate what happens during runMonthly
    echo "\nTest 3: Simulating runMonthly configuration refresh\n";
    echo "===================================================\n";
    
    echo "This is what will happen when runMonthly is called:\n";
    echo "1. refreshConfig() will be called automatically\n";
    echo "2. Latest configuration values will be loaded from gmi_config table\n";
    echo "3. Calculations will use the refreshed values\n";
    
    // Show the actual gmi_config table status
    echo "\nChecking gmi_config table:\n";
    $db = Class_db::getInstance();
    $activeConfigs = $db->db_select2('gmi_config', array('status' => '1'));
    echo "Found " . count($activeConfigs) . " active configuration records\n";
    
    if (count($activeConfigs) > 0) {
        echo "✅ Configuration will be loaded from database\n";
    } else {
        echo "⚠️  No active configurations found - will use defaults\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "\nHow to verify the fix:\n";
    echo "1. Update a value in the gmi_config table\n";
    echo "2. Run the gamification calculation\n";
    echo "3. The new value should be used in calculations\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
