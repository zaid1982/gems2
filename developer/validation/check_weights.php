<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

Class_db::getInstance()->db_connect();

echo "<h2>Current Weight Configuration in Database</h2>";

try {
    // Just get some sample data to see the structure
    $allConfigs = Class_db::getInstance()->db_select2('gmi_config', array(), '', 5);
    echo "<p>Total configs found: " . count($allConfigs) . "</p>";
    
    if (!empty($allConfigs)) {
        echo "<h3>First config structure:</h3>";
        $firstConfig = $allConfigs[0];
        echo "<pre>" . print_r($firstConfig, true) . "</pre>";
        
        // The database columns are snake_case but results are camelCase
        echo "<p>Database uses snake_case columns, results are camelCase</p>";
        
        // Look for weight configs using snake_case for WHERE clause
        $weights1 = Class_db::getInstance()->db_select2('gmi_config', 
            array('config_key' => 'weight_completed'));
        $weights2 = Class_db::getInstance()->db_select2('gmi_config', 
            array('config_key' => 'weight_ontime'));
        
        $allWeights = array_merge($weights1, $weights2);

        echo "<h3>Weight Configuration:</h3>";
        if (!empty($allWeights)) {
            echo "<table border='1'>";
            echo "<tr><th>Config Key</th><th>Config Value</th><th>Data Type</th></tr>";
            foreach ($allWeights as $weight) {
                echo "<tr>";
                echo "<td>" . $weight['configKey'] . "</td>";
                echo "<td><strong>" . $weight['configValue'] . "</strong></td>";
                echo "<td>" . $weight['dataType'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>No weight configuration found! You need to set them first.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo '<p><a href="debug_runmonthly.php">Go to runMonthly debug page</a></p>';
?>
