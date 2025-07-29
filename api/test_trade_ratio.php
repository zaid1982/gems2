<?php
/**
 * Trade Ratio Testing API
 * Test the Trade Ratio calculation system with various scenarios
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include necessary files
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_gamification.php';

// Initialize required classes
$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

// Establish database connection
Class_db::getInstance()->db_connect();

try {
    $action = $_GET['action'] ?? 'run_tests';
    
    switch ($action) {
        case 'run_tests':
            runAllTests();
            break;
        case 'test_config':
            testConfiguration();
            break;
        case 'test_ratios':
            testTradeRatios();
            break;
        case 'simulate_calculation':
            simulateCalculation();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function runAllTests() {
    $results = [];
    
    // Test 1: Configuration Loading
    $results['config_test'] = testConfiguration();
    
    // Test 2: Trade Ratio Calculation
    $results['ratio_test'] = testTradeRatios();
    
    // Test 3: Simulation with Mock Data
    $results['simulation_test'] = simulateCalculation();
    
    // Test 4: Database Integration
    $results['database_test'] = testDatabaseIntegration();
    
    echo json_encode([
        'success' => true,
        'message' => 'All tests completed',
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function testConfiguration() {
    try {
        $gamification = new Class_gamification();
        
        // Initialize the fn_general property if needed
        $constant = new Class_constant();
        $fn_general = new Class_general();
        $fn_general->__set('constant', $constant);
        $gamification->__set('fn_general', $fn_general);
        
        // Test configuration loading
        $tests = [
            'config_loaded' => true,
            'trade_ratio_default' => null,
            'trade_ratio_group_7' => null,
            'trade_ratio_group_8' => null,
            'trade_ratio_group_9' => null,
            'other_configs' => []
        ];
        
        // Use reflection to access private config
        $reflection = new ReflectionClass($gamification);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($gamification);
        
        $tests['raw_config_dump'] = $config; // Add this for debugging
        $tests['trade_ratio_default'] = $config['trade_ratio_default'] ?? 'NOT_SET';
        $tests['trade_ratio_group_7'] = $config['trade_ratio_group_7'] ?? 'NOT_SET';
        $tests['trade_ratio_group_8'] = $config['trade_ratio_group_8'] ?? 'NOT_SET';
        $tests['trade_ratio_group_9'] = $config['trade_ratio_group_9'] ?? 'NOT_SET';
        
        // Get other important configs
        $tests['other_configs'] = [
            'tier_medalist_threshold' => $config['tier_medalist_threshold'] ?? 'NOT_SET',
            'mbv_tier1_multiplier' => $config['mbv_tier1_multiplier'] ?? 'NOT_SET',
            'point_scale_factor' => $config['point_scale_factor'] ?? 'NOT_SET'
        ];
        
        return [
            'success' => true,
            'message' => 'Configuration test completed',
            'data' => $tests
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Configuration test failed: ' . $e->getMessage()
        ];
    }
}

function testTradeRatios() {
    try {
        $gamification = new Class_gamification();
        
        // Initialize the fn_general property if needed
        $constant = new Class_constant();
        $fn_general = new Class_general();
        $fn_general->__set('constant', $constant);
        $gamification->__set('fn_general', $fn_general);
        
        // Use reflection to access private getTradeRatio method
        $reflection = new ReflectionClass($gamification);
        $method = $reflection->getMethod('getTradeRatio');
        $method->setAccessible(true);
        
        $tests = [
            'group_7_mechanical' => $method->invokeArgs($gamification, [7]),
            'group_8_electrical' => $method->invokeArgs($gamification, [8]),
            'group_9_civil' => $method->invokeArgs($gamification, [9]),
            'group_999_unknown' => $method->invokeArgs($gamification, [999]),
            'null_group' => $method->invokeArgs($gamification, [null]),
            'empty_group' => $method->invokeArgs($gamification, ['']),
        ];
        
        return [
            'success' => true,
            'message' => 'Trade ratio test completed',
            'data' => $tests
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Trade ratio test failed: ' . $e->getMessage()
        ];
    }
}

function simulateCalculation() {
    try {
        $gamification = new Class_gamification();
        
        // Initialize the fn_general property if needed
        $constant = new Class_constant();
        $fn_general = new Class_general();
        $fn_general->__set('constant', $constant);
        $gamification->__set('fn_general', $fn_general);
        
        // Simulate task completion scenarios
        $scenarios = [
            [
                'scenario' => 'Mechanical Department (Group 7)',
                'ppm_group_id' => 7,
                'base_completed_tasks' => 100,
                'expected_ratio' => 0.9,
                'expected_result' => 90 // 100 * 0.9
            ],
            [
                'scenario' => 'Electrical Department (Group 8)', 
                'ppm_group_id' => 8,
                'base_completed_tasks' => 100,
                'expected_ratio' => 1.1,
                'expected_result' => 110 // 100 * 1.1
            ],
            [
                'scenario' => 'Civil Department (Group 9)',
                'ppm_group_id' => 9,
                'base_completed_tasks' => 100,
                'expected_ratio' => 1.0,
                'expected_result' => 100 // 100 * 1.0
            ],
            [
                'scenario' => 'Unknown Group (Default)',
                'ppm_group_id' => 999,
                'base_completed_tasks' => 100,
                'expected_ratio' => 1.0,
                'expected_result' => 100 // 100 * 1.0 (default)
            ]
        ];
        
        // Use reflection to access private getTradeRatio method
        $reflection = new ReflectionClass($gamification);
        $method = $reflection->getMethod('getTradeRatio');
        $method->setAccessible(true);
        
        $results = [];
        foreach ($scenarios as $scenario) {
            $actualRatio = $method->invokeArgs($gamification, [$scenario['ppm_group_id']]);
            $actualResult = round($scenario['base_completed_tasks'] * $actualRatio);
            
            $results[] = [
                'scenario' => $scenario['scenario'],
                'ppm_group_id' => $scenario['ppm_group_id'],
                'base_tasks' => $scenario['base_completed_tasks'],
                'expected_ratio' => $scenario['expected_ratio'],
                'actual_ratio' => $actualRatio,
                'expected_result' => $scenario['expected_result'],
                'actual_result' => $actualResult,
                'ratio_match' => (abs($actualRatio - $scenario['expected_ratio']) < 0.001),
                'result_match' => ($actualResult == $scenario['expected_result'])
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Simulation test completed',
            'data' => $results
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Simulation test failed: ' . $e->getMessage()
        ];
    }
}

function testDatabaseIntegration() {
    try {
        // Test database configuration loading directly (without status filter first)
        $allConfigData = Class_db::getInstance()->db_select2('gmi_config', array());
        $configData = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'));
        
        $trade_ratios = [];
        $all_configs = [];
        foreach ($configData as $config) {
            $all_configs[$config['config_key']] = $config['config_value'];
            if (strpos($config['config_key'], 'trade_ratio') === 0) {
                $trade_ratios[$config['config_key']] = [
                    'value' => $config['config_value'],
                    'data_type' => $config['data_type'],
                    'status' => $config['status']
                ];
            }
        }
        
        // Test PPM groups
        $ppm_groups = Class_db::getInstance()->db_select2('ppm_group', array(), 'ppm_group_name ASC');
        
        return [
            'success' => true,
            'message' => 'Database integration test completed',
            'data' => [
                'total_all_configs' => count($allConfigData),
                'total_active_configs' => count($configData),
                'trade_ratio_configs' => $trade_ratios,
                'sample_all_configs' => array_slice($all_configs, 0, 10),
                'ppm_groups_count' => count($ppm_groups),
                'ppm_groups' => array_slice($ppm_groups, 0, 5) // First 5 groups
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database integration test failed: ' . $e->getMessage()
        ];
    }
}
?>
