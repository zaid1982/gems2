<?php
/**
 * Test script to debug gamification configuration loading
 * Run this script to check if configuration values are being loaded correctly
 */

// Include necessary files
require_once 'api/constant.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_db.php';
require_once 'api/function/f_gamification.php';

try {
    echo "=== GAMIFICATION CONFIGURATION DEBUG ===\n\n";
    
    // First, check what's in the gmi_config table
    echo "1. Checking gmi_config table contents:\n";
    echo "=====================================\n";
    
    $db = Class_db::getInstance();
    $configData = $db->db_select2('gmi_config', array());
    
    if (empty($configData)) {
        echo "❌ No records found in gmi_config table!\n\n";
    } else {
        echo "✅ Found " . count($configData) . " records in gmi_config table:\n\n";
        
        foreach ($configData as $config) {
            $status = $config['status'] == '1' ? '✅ ACTIVE' : '❌ INACTIVE';
            echo "   Key: " . ($config['configKey'] ?? $config['config_key'] ?? 'N/A') . "\n";
            echo "   Value: " . ($config['configValue'] ?? $config['config_value'] ?? 'N/A') . "\n";
            echo "   Type: " . ($config['dataType'] ?? $config['data_type'] ?? 'N/A') . "\n";
            echo "   Status: $status\n";
            echo "   ---\n";
        }
    }
    
    // Now test the gamification class
    echo "\n2. Testing gamification class configuration loading:\n";
    echo "===================================================\n";
    
    $gamification = new Class_gamification();
    
    // Use reflection to access private config property
    $reflection = new ReflectionClass($gamification);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $loadedConfig = $configProperty->getValue($gamification);
    
    if (empty($loadedConfig)) {
        echo "❌ No configuration loaded by gamification class!\n";
    } else {
        echo "✅ Gamification class loaded " . count($loadedConfig) . " configuration values:\n\n";
        
        foreach ($loadedConfig as $key => $value) {
            echo "   $key = $value\n";
        }
    }
    
    // Test config refresh
    echo "\n3. Testing configuration refresh:\n";
    echo "=================================\n";
    
    echo "Before refresh - tier_medalist_threshold: " . 
         (isset($loadedConfig['tier_medalist_threshold']) ? $loadedConfig['tier_medalist_threshold'] : 'NOT SET') . "\n";
    
    $gamification->refreshConfig();
    
    $refreshedConfig = $configProperty->getValue($gamification);
    echo "After refresh - tier_medalist_threshold: " . 
         (isset($refreshedConfig['tier_medalist_threshold']) ? $refreshedConfig['tier_medalist_threshold'] : 'NOT SET') . "\n";
    
    // Check if specific important configs are present
    echo "\n4. Checking important configuration keys:\n";
    echo "=========================================\n";
    
    $importantKeys = [
        'tier_medalist_threshold',
        'tier_finisher_threshold', 
        'mbv_tier1_threshold',
        'mbv_tier2_threshold',
        'weight_completed',
        'weight_ontime',
        'self_finding_points',
        'point_scale_factor'
    ];
    
    foreach ($importantKeys as $key) {
        if (isset($refreshedConfig[$key])) {
            echo "   ✅ $key = " . $refreshedConfig[$key] . "\n";
        } else {
            echo "   ❌ $key = NOT FOUND (using default)\n";
        }
    }
    
    echo "\n=== DEBUG COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
