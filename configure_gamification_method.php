<?php
/**
 * Configuration script to switch between weekly and monthly processing
 * This allows you to test and switch between different data collection methods
 */

require_once 'api/constant.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_db.php';

echo "=== GAMIFICATION PROCESSING METHOD CONFIGURATION ===\n\n";

try {
    $db = Class_db::getInstance();
    
    // Check current configuration
    $currentConfig = $db->db_select_single2('gmi_config', array('config_key' => 'use_weekly_processing', 'status' => '1'));
    
    if (empty($currentConfig)) {
        echo "⚠️  use_weekly_processing configuration not found in gmi_config table.\n";
        echo "Current method: Weekly processing (default)\n\n";
        
        echo "Would you like to add this configuration? (y/n): ";
        // For script purposes, we'll assume 'y'
        $answer = 'y';
        
        if ($answer === 'y') {
            // Add the configuration
            $configData = array(
                'config_key' => 'use_weekly_processing',
                'config_value' => '1',  // 1 = weekly, 0 = monthly
                'data_type' => 'int',
                'status' => '1',
                'description' => 'Whether to use weekly processing (1) or direct monthly processing (0)'
            );
            
            $db->db_insert('gmi_config', $configData);
            echo "✅ Configuration added successfully!\n";
        }
    } else {
        $useWeekly = intval($currentConfig['config_value']);
        echo "Current processing method: " . ($useWeekly ? 'Weekly Processing' : 'Direct Monthly Processing') . "\n\n";
    }
    
    echo "PROCESSING METHOD OPTIONS:\n";
    echo "=========================\n";
    echo "1. Weekly Processing (current method)\n";
    echo "   - Processes data week by week within the month\n";
    echo "   - More complex but provides weekly breakdown\n";
    echo "   - May have issues with week boundary calculations\n\n";
    
    echo "2. Direct Monthly Processing (alternative method)\n";
    echo "   - Processes all data for the month at once\n";
    echo "   - Simpler and more reliable\n";
    echo "   - Avoids week boundary calculation issues\n";
    echo "   - Recommended if you're experiencing data collection issues\n\n";
    
    echo "To switch to Direct Monthly Processing, run:\n";
    echo "UPDATE gmi_config SET config_value = '0' WHERE config_key = 'use_weekly_processing';\n\n";
    
    echo "To switch back to Weekly Processing, run:\n";
    echo "UPDATE gmi_config SET config_value = '1' WHERE config_key = 'use_weekly_processing';\n\n";
    
    echo "TESTING RECOMMENDATION:\n";
    echo "======================\n";
    echo "1. First, run the diagnostic script: php debug_gamification_data.php\n";
    echo "2. Compare data counts between weekly and monthly collection\n";
    echo "3. If there's a discrepancy, switch to direct monthly processing\n";
    echo "4. Test the gamification calculation with the new method\n";
    echo "5. Verify the results are correct\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== CONFIGURATION COMPLETE ===\n";
