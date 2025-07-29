<?php
header('Content-Type: text/plain');

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';

// Initialize required classes
$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

Class_db::getInstance()->db_connect();

echo "=== Testing different database query methods ===\n\n";

// Test 1: db_select with LIKE
echo "1. Using db_select with LIKE:\n";
try {
    $result1 = Class_db::getInstance()->db_select('gmi_config', array('config_key' => 'LIKE trade_ratio_%', 'status' => '1'));
    echo "Count: " . count($result1) . "\n";
    if (count($result1) > 0) {
        echo "First row keys: " . implode(', ', array_keys($result1[0])) . "\n";
        echo "First row: " . print_r($result1[0], true) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n2. Using db_select2 with LIKE:\n";
try {
    $result2 = Class_db::getInstance()->db_select2('gmi_config', array('config_key' => 'LIKE trade_ratio_%', 'status' => '1'));
    echo "Count: " . count($result2) . "\n";
    if (count($result2) > 0) {
        echo "First row keys: " . implode(', ', array_keys($result2[0])) . "\n";
        echo "First row: " . print_r($result2[0], true) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n3. Using db_select2 without conditions to get all gmi_config:\n";
try {
    $result3 = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'));
    echo "Total count: " . count($result3) . "\n";
    
    // Filter for trade_ratio manually
    $tradeRatios = [];
    foreach ($result3 as $row) {
        if (strpos($row['configKey'], 'trade_ratio') === 0) {
            $tradeRatios[] = $row;
        }
    }
    echo "Trade ratio count: " . count($tradeRatios) . "\n";
    if (count($tradeRatios) > 0) {
        echo "Trade ratio rows:\n";
        foreach ($tradeRatios as $tr) {
            echo "  {$tr['configKey']} = {$tr['configValue']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n4. Raw SQL query:\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gems", "root", "");
    $stmt = $pdo->query("SELECT config_key, config_value FROM gmi_config WHERE config_key LIKE 'trade_ratio%' AND status = '1'");
    $result4 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Raw SQL count: " . count($result4) . "\n";
    foreach ($result4 as $row) {
        echo "  {$row['config_key']} = {$row['config_value']}\n";
    }
} catch (Exception $e) {
    echo "Raw SQL Error: " . $e->getMessage() . "\n";
}
?>
