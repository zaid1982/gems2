<?php
/**
 * Database Schema Extractor
 * 
 * This script extracts all table schemas from the connected database
 * and generates CREATE TABLE SQL statements for each table.
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

echo "=== DATABASE SCHEMA EXTRACTOR ===\n";
echo "Host: $host\n";
echo "Database: $database\n";
echo "User: $username\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✓ Database connection successful!\n\n";
    
    // Get all tables in the database
    $tablesQuery = "SELECT TABLE_NAME 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = :database 
                    AND TABLE_TYPE = 'BASE TABLE'
                    ORDER BY TABLE_NAME";
    
    $stmt = $pdo->prepare($tablesQuery);
    $stmt->execute(['database' => $database]);
    $tables = $stmt->fetchAll();
    
    echo "Found " . count($tables) . " tables in database '$database'\n\n";
    
    // Create output file
    $outputFile = "database_schema_" . date('Y-m-d_H-i-s') . ".sql";
    $output = fopen($outputFile, 'w');
    
    // Write header to output file
    fwrite($output, "-- Database Schema Export\n");
    fwrite($output, "-- Database: $database\n");
    fwrite($output, "-- Host: $host\n");
    fwrite($output, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($output, "-- Total Tables: " . count($tables) . "\n\n");
    fwrite($output, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($output, "SET AUTOCOMMIT = 0;\n");
    fwrite($output, "START TRANSACTION;\n");
    fwrite($output, "SET time_zone = \"+00:00\";\n\n");
    fwrite($output, "-- Database: `$database`\n");
    fwrite($output, "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;\n");
    fwrite($output, "USE `$database`;\n\n");
    
    $processedTables = 0;
    
    // Process each table
    foreach ($tables as $table) {
        $tableName = $table['TABLE_NAME'];
        echo "Processing table: $tableName\n";
        
        try {
            // Get CREATE TABLE statement
            $createTableQuery = "SHOW CREATE TABLE `$tableName`";
            $createStmt = $pdo->query($createTableQuery);
            $createResult = $createStmt->fetch();
            
            if ($createResult && isset($createResult['Create Table'])) {
                $createTableSQL = $createResult['Create Table'];
                
                // Write to output file
                fwrite($output, "-- --------------------------------------------------------\n");
                fwrite($output, "-- Table structure for table `$tableName`\n");
                fwrite($output, "-- --------------------------------------------------------\n\n");
                fwrite($output, "DROP TABLE IF EXISTS `$tableName`;\n");
                fwrite($output, $createTableSQL . ";\n\n");
                
                // Get table comment if exists
                $tableInfoQuery = "SELECT TABLE_COMMENT 
                                  FROM INFORMATION_SCHEMA.TABLES 
                                  WHERE TABLE_SCHEMA = :database 
                                  AND TABLE_NAME = :tableName";
                $infoStmt = $pdo->prepare($tableInfoQuery);
                $infoStmt->execute(['database' => $database, 'tableName' => $tableName]);
                $tableInfo = $infoStmt->fetch();
                
                if ($tableInfo && !empty($tableInfo['TABLE_COMMENT'])) {
                    fwrite($output, "-- Table Comment: " . $tableInfo['TABLE_COMMENT'] . "\n\n");
                }
                
                // Get column details for documentation
                $columnsQuery = "SELECT 
                                    COLUMN_NAME,
                                    COLUMN_TYPE,
                                    IS_NULLABLE,
                                    COLUMN_DEFAULT,
                                    EXTRA,
                                    COLUMN_COMMENT
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = :database 
                                AND TABLE_NAME = :tableName
                                ORDER BY ORDINAL_POSITION";
                
                $colStmt = $pdo->prepare($columnsQuery);
                $colStmt->execute(['database' => $database, 'tableName' => $tableName]);
                $columns = $colStmt->fetchAll();
                
                fwrite($output, "-- Columns in `$tableName`:\n");
                foreach ($columns as $column) {
                    $nullable = $column['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
                    $default = $column['COLUMN_DEFAULT'] !== null ? "DEFAULT '{$column['COLUMN_DEFAULT']}'" : '';
                    $extra = !empty($column['EXTRA']) ? $column['EXTRA'] : '';
                    $comment = !empty($column['COLUMN_COMMENT']) ? "COMMENT '{$column['COLUMN_COMMENT']}'" : '';
                    
                    fwrite($output, "-- {$column['COLUMN_NAME']}: {$column['COLUMN_TYPE']} $nullable $default $extra $comment\n");
                }
                fwrite($output, "\n");
                
                // Get indexes
                $indexQuery = "SHOW INDEX FROM `$tableName`";
                $indexStmt = $pdo->query($indexQuery);
                $indexes = $indexStmt->fetchAll();
                
                if ($indexes) {
                    fwrite($output, "-- Indexes for `$tableName`:\n");
                    $indexGroups = [];
                    foreach ($indexes as $index) {
                        $indexGroups[$index['Key_name']][] = $index;
                    }
                    
                    foreach ($indexGroups as $indexName => $indexData) {
                        $columns = array_map(function($idx) { return $idx['Column_name']; }, $indexData);
                        $isUnique = $indexData[0]['Non_unique'] == 0 ? 'UNIQUE ' : '';
                        $indexType = $indexData[0]['Index_type'];
                        
                        if ($indexName === 'PRIMARY') {
                            fwrite($output, "-- PRIMARY KEY (" . implode(', ', $columns) . ")\n");
                        } else {
                            fwrite($output, "-- {$isUnique}KEY `$indexName` (" . implode(', ', $columns) . ") USING $indexType\n");
                        }
                    }
                    fwrite($output, "\n");
                }
                
                // Get foreign keys
                $fkQuery = "SELECT 
                               CONSTRAINT_NAME,
                               COLUMN_NAME,
                               REFERENCED_TABLE_NAME,
                               REFERENCED_COLUMN_NAME,
                               UPDATE_RULE,
                               DELETE_RULE
                           FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                           WHERE TABLE_SCHEMA = :database 
                           AND TABLE_NAME = :tableName
                           AND REFERENCED_TABLE_NAME IS NOT NULL";
                
                $fkStmt = $pdo->prepare($fkQuery);
                $fkStmt->execute(['database' => $database, 'tableName' => $tableName]);
                $foreignKeys = $fkStmt->fetchAll();
                
                if ($foreignKeys) {
                    fwrite($output, "-- Foreign Keys for `$tableName`:\n");
                    foreach ($foreignKeys as $fk) {
                        fwrite($output, "-- CONSTRAINT `{$fk['CONSTRAINT_NAME']}` FOREIGN KEY (`{$fk['COLUMN_NAME']}`) REFERENCES `{$fk['REFERENCED_TABLE_NAME']}` (`{$fk['REFERENCED_COLUMN_NAME']}`) ON UPDATE {$fk['UPDATE_RULE']} ON DELETE {$fk['DELETE_RULE']}\n");
                    }
                    fwrite($output, "\n");
                }
                
                fwrite($output, str_repeat("-", 80) . "\n\n");
                
                $processedTables++;
                
            } else {
                echo "  ✗ Could not get CREATE TABLE statement for $tableName\n";
                fwrite($output, "-- ERROR: Could not extract CREATE TABLE for `$tableName`\n\n");
            }
            
        } catch (Exception $e) {
            echo "  ✗ Error processing table $tableName: " . $e->getMessage() . "\n";
            fwrite($output, "-- ERROR processing table `$tableName`: " . $e->getMessage() . "\n\n");
        }
    }
    
    // Get views
    echo "\nProcessing views...\n";
    $viewsQuery = "SELECT TABLE_NAME 
                   FROM INFORMATION_SCHEMA.TABLES 
                   WHERE TABLE_SCHEMA = :database 
                   AND TABLE_TYPE = 'VIEW'
                   ORDER BY TABLE_NAME";
    
    $stmt = $pdo->prepare($viewsQuery);
    $stmt->execute(['database' => $database]);
    $views = $stmt->fetchAll();
    
    if ($views) {
        echo "Found " . count($views) . " views\n";
        fwrite($output, "\n" . str_repeat("=", 80) . "\n");
        fwrite($output, "-- VIEWS\n");
        fwrite($output, str_repeat("=", 80) . "\n\n");
        
        foreach ($views as $view) {
            $viewName = $view['TABLE_NAME'];
            echo "Processing view: $viewName\n";
            
            try {
                $createViewQuery = "SHOW CREATE VIEW `$viewName`";
                $createStmt = $pdo->query($createViewQuery);
                $createResult = $createStmt->fetch();
                
                if ($createResult && isset($createResult['Create View'])) {
                    fwrite($output, "-- --------------------------------------------------------\n");
                    fwrite($output, "-- View structure for view `$viewName`\n");
                    fwrite($output, "-- --------------------------------------------------------\n\n");
                    fwrite($output, "DROP VIEW IF EXISTS `$viewName`;\n");
                    fwrite($output, $createResult['Create View'] . ";\n\n");
                }
            } catch (Exception $e) {
                echo "  ✗ Error processing view $viewName: " . $e->getMessage() . "\n";
                fwrite($output, "-- ERROR processing view `$viewName`: " . $e->getMessage() . "\n\n");
            }
        }
    } else {
        echo "No views found\n";
    }
    
    // Get triggers
    echo "\nProcessing triggers...\n";
    $triggersQuery = "SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE 
                      FROM INFORMATION_SCHEMA.TRIGGERS 
                      WHERE TRIGGER_SCHEMA = :database
                      ORDER BY TRIGGER_NAME";
    
    $stmt = $pdo->prepare($triggersQuery);
    $stmt->execute(['database' => $database]);
    $triggers = $stmt->fetchAll();
    
    if ($triggers) {
        echo "Found " . count($triggers) . " triggers\n";
        fwrite($output, "\n" . str_repeat("=", 80) . "\n");
        fwrite($output, "-- TRIGGERS\n");
        fwrite($output, str_repeat("=", 80) . "\n\n");
        
        foreach ($triggers as $trigger) {
            $triggerName = $trigger['TRIGGER_NAME'];
            echo "Processing trigger: $triggerName\n";
            
            try {
                $createTriggerQuery = "SHOW CREATE TRIGGER `$triggerName`";
                $createStmt = $pdo->query($createTriggerQuery);
                $createResult = $createStmt->fetch();
                
                if ($createResult && isset($createResult['SQL Original Statement'])) {
                    fwrite($output, "-- --------------------------------------------------------\n");
                    fwrite($output, "-- Trigger `$triggerName` on table `{$trigger['EVENT_OBJECT_TABLE']}`\n");
                    fwrite($output, "-- Event: {$trigger['EVENT_MANIPULATION']}\n");
                    fwrite($output, "-- --------------------------------------------------------\n\n");
                    fwrite($output, "DROP TRIGGER IF EXISTS `$triggerName`;\n");
                    fwrite($output, "DELIMITER ;;\n");
                    fwrite($output, $createResult['SQL Original Statement'] . ";;\n");
                    fwrite($output, "DELIMITER ;\n\n");
                }
            } catch (Exception $e) {
                echo "  ✗ Error processing trigger $triggerName: " . $e->getMessage() . "\n";
                fwrite($output, "-- ERROR processing trigger `$triggerName`: " . $e->getMessage() . "\n\n");
            }
        }
    } else {
        echo "No triggers found\n";
    }
    
    // Get procedures and functions
    echo "\nProcessing stored procedures and functions...\n";
    $routinesQuery = "SELECT ROUTINE_NAME, ROUTINE_TYPE 
                      FROM INFORMATION_SCHEMA.ROUTINES 
                      WHERE ROUTINE_SCHEMA = :database
                      ORDER BY ROUTINE_TYPE, ROUTINE_NAME";
    
    $stmt = $pdo->prepare($routinesQuery);
    $stmt->execute(['database' => $database]);
    $routines = $stmt->fetchAll();
    
    if ($routines) {
        echo "Found " . count($routines) . " stored procedures/functions\n";
        fwrite($output, "\n" . str_repeat("=", 80) . "\n");
        fwrite($output, "-- STORED PROCEDURES AND FUNCTIONS\n");
        fwrite($output, str_repeat("=", 80) . "\n\n");
        
        foreach ($routines as $routine) {
            $routineName = $routine['ROUTINE_NAME'];
            $routineType = $routine['ROUTINE_TYPE'];
            echo "Processing $routineType: $routineName\n";
            
            try {
                $createRoutineQuery = "SHOW CREATE $routineType `$routineName`";
                $createStmt = $pdo->query($createRoutineQuery);
                $createResult = $createStmt->fetch();
                
                $createKey = "Create " . ucfirst(strtolower($routineType));
                if ($createResult && isset($createResult[$createKey])) {
                    fwrite($output, "-- --------------------------------------------------------\n");
                    fwrite($output, "-- $routineType `$routineName`\n");
                    fwrite($output, "-- --------------------------------------------------------\n\n");
                    fwrite($output, "DROP $routineType IF EXISTS `$routineName`;\n");
                    fwrite($output, "DELIMITER ;;\n");
                    fwrite($output, $createResult[$createKey] . ";;\n");
                    fwrite($output, "DELIMITER ;\n\n");
                }
            } catch (Exception $e) {
                echo "  ✗ Error processing $routineType $routineName: " . $e->getMessage() . "\n";
                fwrite($output, "-- ERROR processing $routineType `$routineName`: " . $e->getMessage() . "\n\n");
            }
        }
    } else {
        echo "No stored procedures or functions found\n";
    }
    
    // Write footer
    fwrite($output, "\nCOMMIT;\n");
    fwrite($output, "\n-- Export completed: " . date('Y-m-d H:i:s') . "\n");
    fwrite($output, "-- Total tables processed: $processedTables\n");
    fwrite($output, "-- Total views: " . count($views) . "\n");
    fwrite($output, "-- Total triggers: " . count($triggers) . "\n");
    fwrite($output, "-- Total routines: " . count($routines) . "\n");
    
    fclose($output);
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✓ Schema extraction completed successfully!\n";
    echo "✓ Tables processed: $processedTables/" . count($tables) . "\n";
    echo "✓ Views processed: " . count($views) . "\n";
    echo "✓ Triggers processed: " . count($triggers) . "\n";
    echo "✓ Routines processed: " . count($routines) . "\n";
    echo "✓ Output file: $outputFile\n";
    echo "✓ File size: " . formatBytes(filesize($outputFile)) . "\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Format bytes to human readable format
 */
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

?>
