<?php
// Database Performance Test Script
// Run this to test query performance before and after optimization

require_once 'api/class/Constant.php';
require_once 'api/class/General.php';
require_once 'api/class/DbMysql.php';

$fnMain = new General();
$fnMain->isLogged = Constant::$isLogged;
DbMysql::$isLogged = Constant::$isLogged;

try {
    DbMysql::connect();
    
    echo "<h1>Database Performance Test</h1>\n";
    echo "<pre>\n";
    
    // Test 1: Connection Speed
    echo "=== Test 1: Database Connection Speed ===\n";
    $start = microtime(true);
    $result = DbMysql::select('sys_user', array('user_id' => '1'));
    $time = microtime(true) - $start;
    echo "Connection test: " . round($time * 1000, 2) . "ms\n";
    if ($time > 0.1) echo "⚠️  WARNING: Slow connection detected\n";
    else echo "✅ Connection speed OK\n";
    echo "\n";
    
    // Test 2: Asset Query Performance
    echo "=== Test 2: Asset Query Performance ===\n";
    $start = microtime(true);
    $assets = DbMysql::selectAll('ast_asset', [], 0, false, 'asset_no', 'ASC');
    $time = microtime(true) - $start;
    echo "Asset query (" . count($assets) . " records): " . round($time * 1000, 2) . "ms\n";
    if ($time > 1.0) echo "⚠️  WARNING: Slow asset query detected\n";
    else echo "✅ Asset query speed OK\n";
    echo "\n";
    
    // Test 3: User Query Performance
    echo "=== Test 3: User Query Performance ===\n";
    $start = microtime(true);
    $users = DbMysql::selectAll('sys_user', [], 0, false, 'user_name', 'ASC');
    $time = microtime(true) - $start;
    echo "User query (" . count($users) . " records): " . round($time * 1000, 2) . "ms\n";
    if ($time > 1.0) echo "⚠️  WARNING: Slow user query detected\n";
    else echo "✅ User query speed OK\n";
    echo "\n";
    
    // Test 4: PPM Task Query Performance
    echo "=== Test 4: PPM Task Query Performance ===\n";
    $testTaskNos = ['PPM-2025-001', 'PPM-2025-002', 'PPM-2025-003'];
    $totalTime = 0;
    $foundCount = 0;
    
    foreach ($testTaskNos as $taskNo) {
        $start = microtime(true);
        $task = DbMysql::select('ppm_task', array('ppm_task_no' => $taskNo));
        $time = microtime(true) - $start;
        $totalTime += $time;
        if (!empty($task)) $foundCount++;
        echo "PPM task lookup '{$taskNo}': " . round($time * 1000, 2) . "ms\n";
    }
    
    $avgTime = $totalTime / count($testTaskNos);
    echo "Average PPM lookup time: " . round($avgTime * 1000, 2) . "ms\n";
    echo "Found {$foundCount}/" . count($testTaskNos) . " test tasks\n";
    if ($avgTime > 0.5) echo "⚠️  WARNING: Slow PPM task queries detected\n";
    else echo "✅ PPM task query speed OK\n";
    echo "\n";
    
    // Test 5: Index Analysis
    echo "=== Test 5: Index Analysis ===\n";
    
    // Check for critical indexes
    $criticalIndexes = [
        'ppm_task' => ['ppm_task_no', 'ppm_task_status', 'ppm_task_assigned_to'],
        'ast_asset' => ['asset_no'],
        'sys_user' => ['user_name']
    ];
    
    foreach ($criticalIndexes as $table => $columns) {
        echo "Checking indexes for table: {$table}\n";
        
        try {
            $indexes = DbMysql::selectAll("SHOW INDEX FROM {$table}", []);
            $indexedColumns = array_column($indexes, 'Column_name');
            
            foreach ($columns as $column) {
                if (in_array($column, $indexedColumns)) {
                    echo "  ✅ {$column} - indexed\n";
                } else {
                    echo "  ❌ {$column} - NOT indexed (PERFORMANCE ISSUE)\n";
                }
            }
        } catch (Exception $e) {
            echo "  Error checking indexes: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Test 6: Table Statistics
    echo "=== Test 6: Table Statistics ===\n";
    $tables = ['ppm_task', 'ast_asset', 'sys_user'];
    
    foreach ($tables as $table) {
        try {
            $result = DbMysql::select("SELECT COUNT(*) as row_count FROM {$table}", []);
            echo "{$table}: " . ($result['row_count'] ?? 'unknown') . " rows\n";
        } catch (Exception $e) {
            echo "{$table}: Error - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Recommendations
    echo "=== Recommendations ===\n";
    echo "1. Run the optimize_database.sql script to add missing indexes\n";
    echo "2. Check MySQL configuration (innodb_buffer_pool_size, query_cache_size)\n";
    echo "3. Consider enabling slow query log to identify problematic queries\n";
    echo "4. If using cloud database, check network latency and connection pooling\n";
    echo "5. Monitor database load during peak usage times\n";
    echo "\n";
    
    echo "=== Performance Test Completed ===\n";
    echo "Total test time: " . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . "ms\n";
    
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "Error running performance test: " . $e->getMessage() . "\n";
}
?>
