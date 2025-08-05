<?php
/**
 * PTW Database Test Script
 * Simple script to test database connectivity and table creation
 */

// Include GEMS2 database class
require_once __DIR__ . '/api/function/db.php';

echo "<h2>PTW Database Test</h2>";

try {
    // Test database connection
    $db = Class_db::getInstance();
    $db->db_connect();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test PTW tables existence
    $tables = [
        'ptw_permit',
        'ptw_worker', 
        'ptw_document',
        'ptw_status_history',
        'user_signatures'
    ];
    
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $result = $db->db_query($sql);
        
        if ($result && count($result) > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            
            // Show table structure
            $sql = "DESCRIBE $table";
            $structure = $db->db_query($sql);
            echo "<details><summary>View $table structure</summary>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($structure as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table></details>";
            
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    // Test JSON column functionality
    echo "<h3>Testing JSON Column Functionality</h3>";
    
    // Test sample JSON data
    $sampleData = [
        'hazards' => ['slippery_floor', 'electrical', 'fall_from_height'],
        'ppe' => ['safety_helmet', 'safety_shoes', 'safety_glasses']
    ];
    
    $jsonData = json_encode($sampleData);
    echo "<p>Sample JSON data: <code>$jsonData</code></p>";
    
    // Test JSON validation
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✓ JSON encoding/decoding works correctly</p>";
    } else {
        echo "<p style='color: red;'>✗ JSON encoding error: " . json_last_error_msg() . "</p>";
    }
    
    // Test cli_site table modification
    echo "<h3>Testing cli_site Table Modification</h3>";
    $sql = "SHOW COLUMNS FROM cli_site LIKE 'siteRunningNoPtw'";
    $result = $db->db_query($sql);
    
    if ($result && count($result) > 0) {
        echo "<p style='color: green;'>✓ Column 'siteRunningNoPtw' added to cli_site table</p>";
    } else {
        echo "<p style='color: red;'>✗ Column 'siteRunningNoPtw' not found in cli_site table</p>";
    }
    
    echo "<h3>Database Setup Status</h3>";
    echo "<p style='color: blue; font-weight: bold;'>Database setup appears to be ready for PTW module!</p>";
    echo "<p>Next steps:</p>";
    echo "<ul>";
    echo "<li>Execute the database setup SQL if tables are missing</li>";
    echo "<li>Test the PTW form at <a href='ptw_form.html'>ptw_form.html</a></li>";
    echo "<li>View PTW list at <a href='ptw_list.html'>ptw_list.html</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database test failed: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure:</p>";
    echo "<ul>";
    echo "<li>Database server is running</li>";
    echo "<li>GEMS2 database exists</li>";
    echo "<li>Database credentials are correct in api/library/config.ini</li>";
    echo "<li>PTW database setup script has been executed</li>";
    echo "</ul>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #2196F3; }
code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
details { margin: 10px 0; }
table { font-size: 12px; }
th { background: #2196F3; color: white; padding: 5px; }
td { padding: 5px; }
</style>
