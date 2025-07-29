<?php
header('Content-Type: text/plain');

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_gamification.php';

// Initialize required classes
$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

Class_db::getInstance()->db_connect();

echo "=== Testing Gamification Configuration Loading ===\n\n";

try {
    // Create gamification instance
    $gamification = new Class_gamification();
    
    // Access the config using reflection since it's private
    $reflection = new ReflectionClass($gamification);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($gamification);
    
    echo "Configuration loaded:\n";
    echo "weight_completed: " . (isset($config['weight_completed']) ? $config['weight_completed'] : 'NOT FOUND') . "\n";
    echo "weight_ontime: " . (isset($config['weight_ontime']) ? $config['weight_ontime'] : 'NOT FOUND') . "\n";
    echo "weight_late_penalty: " . (isset($config['weight_late_penalty']) ? $config['weight_late_penalty'] : 'NOT FOUND') . "\n";
    echo "point_scale_factor: " . (isset($config['point_scale_factor']) ? $config['point_scale_factor'] : 'NOT FOUND') . "\n";
    echo "trade_ratio_default: " . (isset($config['trade_ratio_default']) ? $config['trade_ratio_default'] : 'NOT FOUND') . "\n";
    
    echo "\nTotal config items loaded: " . count($config) . "\n";
    
    if (count($config) < 10) {
        echo "\nAll loaded config:\n";
        foreach ($config as $key => $value) {
            echo "  $key = $value\n";
        }
    }
    
    // Test getConfig method using reflection
    $getConfigMethod = $reflection->getMethod('getConfig');
    $getConfigMethod->setAccessible(true);
    
    echo "\nTesting getConfig method:\n";
    echo "getConfig('weight_completed', 0.3): " . $getConfigMethod->invoke($gamification, 'weight_completed', 0.3) . "\n";
    echo "getConfig('weight_ontime', 0.7): " . $getConfigMethod->invoke($gamification, 'weight_ontime', 0.7) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Current Database Values ===\n";
try {
    $result = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'));
    foreach ($result as $row) {
        if (in_array($row['configKey'], ['weight_completed', 'weight_ontime', 'weight_late_penalty', 'point_scale_factor'])) {
            echo $row['configKey'] . " = " . $row['configValue'] . " (" . $row['dataType'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error querying database: " . $e->getMessage() . "\n";
}
?>
