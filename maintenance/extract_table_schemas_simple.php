<?php
require_once __DIR__ . '/_require_auth.php';

/**
 * Simple Table Schema Extractor
 * 
 * This script extracts table schemas from the connected database
 * and generates CREATE TABLE SQL statements.
 * 
 * Author: Generated for GEMS2 System
 * Date: August 1, 2025
 */

// Include the database constants
require_once('../api/class/Constant.php');

// Database connection configuration
$host = Constant::$dbHost;
$username = Constant::$dbUserName;
$password = Constant::$dbUserPassword;
$database = Constant::$dbName;

echo "=== SIMPLE TABLE SCHEMA EXTRACTOR ===\n";
echo "Database: $database @ $host\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database successfully!\n\n";
    
    // Get all table names
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables:\n";
    foreach ($tables as $index => $table) {
        echo sprintf("%3d. %s\n", $index + 1, $table);
    }
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Create output file
    $outputFile = "table_schemas_simple_" . date('Y-m-d_H-i-s') . ".sql";
    $sqlOutput = "";
    
    // Add header
    $sqlOutput .= "-- Table Schemas for Database: $database\n";
    $sqlOutput .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlOutput .= "-- Host: $host\n\n";
    $sqlOutput .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlOutput .= "SET AUTOCOMMIT = 0;\n";
    $sqlOutput .= "START TRANSACTION;\n\n";
    
    $processedCount = 0;
    
    // Process each table
    foreach ($tables as $tableName) {
        echo "Extracting schema for: $tableName\n";
        
        try {
            // Get the CREATE TABLE statement
            $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['Create Table'])) {
                $createSQL = $result['Create Table'];
                
                // Add to output
                $sqlOutput .= "-- --------------------------------------------------------\n";
                $sqlOutput .= "-- Table: $tableName\n";
                $sqlOutput .= "-- --------------------------------------------------------\n\n";
                $sqlOutput .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $sqlOutput .= $createSQL . ";\n\n";
                
                // Show column information
                $stmt2 = $pdo->query("DESCRIBE `$tableName`");
                $columns = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                $sqlOutput .= "-- Columns (" . count($columns) . "):\n";
                foreach ($columns as $column) {
                    $sqlOutput .= "-- {$column['Field']}: {$column['Type']} ";
                    $sqlOutput .= ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " ";
                    if ($column['Default'] !== null) {
                        $sqlOutput .= "DEFAULT '{$column['Default']}' ";
                    }
                    if (!empty($column['Extra'])) {
                        $sqlOutput .= "{$column['Extra']} ";
                    }
                    $sqlOutput .= "\n";
                }
                $sqlOutput .= "\n";
                
                $processedCount++;
                echo "  ✓ Extracted successfully\n";
                
            } else {
                echo "  ✗ Failed to extract CREATE TABLE statement\n";
            }
            
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    $sqlOutput .= "COMMIT;\n";
    $sqlOutput .= "\n-- Export Summary:\n";
    $sqlOutput .= "-- Total tables: " . count($tables) . "\n";
    $sqlOutput .= "-- Successfully processed: $processedCount\n";
    $sqlOutput .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    
    // Write to file
    file_put_contents($outputFile, $sqlOutput);
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✓ Schema extraction completed!\n";
    echo "✓ Processed: $processedCount/" . count($tables) . " tables\n";
    echo "✓ Output file: $outputFile\n";
    echo "✓ File size: " . number_format(filesize($outputFile)) . " bytes\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ General error: " . $e->getMessage() . "\n";
}
?>
