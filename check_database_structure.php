<?php
require_once 'api/class/Constant.php';

// Script to check database structure
try {
    $pdo = new PDO(
        "mysql:host=" . Constant::$dbHost . ";port=3306;dbname=" . Constant::$dbName . ";charset=utf8",
        Constant::$dbUserName,
        Constant::$dbUserPassword,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    echo "<h2>Database Structure Check</h2>";
    
    // List all tables
    echo "<h3>Available Tables:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "<p>- $table</p>";
    }
    
    // Check for PTW-related tables
    echo "<h3>PTW-Related Tables:</h3>";
    $ptwTables = array_filter($tables, function($table) {
        return stripos($table, 'ptw') !== false;
    });
    
    if (empty($ptwTables)) {
        echo "<p style='color: red;'>No PTW-related tables found!</p>";
        
        // Check for tables that might store form data
        echo "<h3>Possible Form/Data Tables:</h3>";
        $possibleTables = array_filter($tables, function($table) {
            return stripos($table, 'form') !== false || 
                   stripos($table, 'permit') !== false || 
                   stripos($table, 'declaration') !== false ||
                   stripos($table, 'contractor') !== false;
        });
        
        if (!empty($possibleTables)) {
            foreach ($possibleTables as $table) {
                echo "<p>- $table</p>";
            }
        } else {
            echo "<p>No obvious form/permit tables found</p>";
        }
    } else {
        foreach ($ptwTables as $table) {
            echo "<h4>Table: $table</h4>";
            $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h3>All Tables with Column Count:</h3>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '" . Constant::$dbName . "'")->fetchColumn();
        echo "<p>$table - $count columns</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
