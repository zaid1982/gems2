<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Test - Step by Step</h1>";

echo "<h3>Step 1: Loading constant.php</h3>";
try {
    require_once 'api/library/constant.php';
    echo "✅ constant.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading constant.php: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 2: Loading db.php</h3>";
try {
    require_once 'api/function/db.php';
    echo "✅ db.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading db.php: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 3: Loading f_general.php</h3>";
try {
    require_once 'api/function/f_general.php';
    echo "✅ f_general.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading f_general.php: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 4: Loading f_gamification.php</h3>";
try {
    require_once 'api/function/f_gamification.php';
    echo "✅ f_gamification.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading f_gamification.php: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 5: Initializing classes</h3>";
try {
    $constant = new Class_constant();
    echo "✅ Class_constant initialized<br>";
    
    $fn_general = new Class_general();
    echo "✅ Class_general initialized<br>";
    
    $fn_general->__set('constant', $constant);
    echo "✅ Class_general constant set<br>";
} catch (Exception $e) {
    echo "❌ Error initializing classes: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 6: Database connection</h3>";
try {
    Class_db::getInstance()->db_connect();
    echo "✅ Database connected successfully<br>";
} catch (Exception $e) {
    echo "❌ Error connecting to database: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 7: Creating gamification instance</h3>";
try {
    $gamification = new Class_gamification();
    echo "✅ Class_gamification created<br>";
    
    $reflection = new ReflectionClass($gamification);
    echo "✅ Reflection class created<br>";
    
    // Test accessing config
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($gamification);
    echo "✅ Config loaded, found " . count($config) . " configuration items<br>";
    
    // Show a few config values
    if (isset($config['weight_completed'])) {
        echo "✅ weight_completed = " . $config['weight_completed'] . "<br>";
    }
    if (isset($config['weight_ontime'])) {
        echo "✅ weight_ontime = " . $config['weight_ontime'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error with gamification: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

echo "<h3>✅ All tests passed! The debug page should work now.</h3>";
echo '<p><a href="debug_runmonthly.php">Try the full debug page</a></p>';
?>
