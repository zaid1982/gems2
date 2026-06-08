<?php
require_once __DIR__ . '/_require_auth.php';

/**
 * Advanced Database Schema Extractor with Analysis
 * 
 * This script provides comprehensive database schema extraction with:
 * - Detailed table analysis
 * - Relationship mapping
 * - Data statistics
 * - Performance insights
 * 
 * Author: Generated for GEMS2 System
 * Date: August 1, 2025
 */

// Configuration
$useLocalDB = false; // Set to true to use local XAMPP database instead of remote

if ($useLocalDB) {
    // Local XAMPP configuration
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'gems';
    echo "=== USING LOCAL DATABASE ===\n";
} else {
    // Production configuration
    require_once('../api/class/Constant.php');
    $host = Constant::$dbHost;
    $username = Constant::$dbUserName;
    $password = Constant::$dbUserPassword;
    $database = Constant::$dbName;
    echo "=== USING PRODUCTION DATABASE ===\n";
}

echo "Host: $host\n";
echo "Database: $database\n";
echo "User: $username\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✓ Database connection established!\n\n";
    
    // Get database information
    echo "Analyzing database structure...\n";
    
    // Get database size
    $stmt = $pdo->prepare("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
            COUNT(*) as table_count
        FROM information_schema.tables 
        WHERE table_schema = ?
        AND table_type = 'BASE TABLE'
    ");
    $stmt->execute([$database]);
    $dbInfo = $stmt->fetch();
    
    echo "Database size: {$dbInfo['size_mb']} MB\n";
    echo "Total tables: {$dbInfo['table_count']}\n\n";
    
    // Get all tables with detailed information
    $stmt = $pdo->prepare("
        SELECT 
            t.TABLE_NAME,
            t.ENGINE,
            t.TABLE_ROWS,
            t.TABLE_COLLATION,
            t.TABLE_COMMENT,
            ROUND((t.DATA_LENGTH + t.INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
            ROUND(t.DATA_LENGTH / 1024 / 1024, 2) AS data_mb,
            ROUND(t.INDEX_LENGTH / 1024 / 1024, 2) AS index_mb
        FROM information_schema.TABLES t
        WHERE t.TABLE_SCHEMA = ?
        AND t.TABLE_TYPE = 'BASE TABLE'
        ORDER BY (t.DATA_LENGTH + t.INDEX_LENGTH) DESC
    ");
    $stmt->execute([$database]);
    $tables = $stmt->fetchAll();
    
    // Create detailed output file
    $timestamp = date('Y-m-d_H-i-s');
    $outputFile = "database_schema_advanced_$timestamp.sql";
    $analysisFile = "database_analysis_$timestamp.txt";
    
    // Start building SQL output
    $sqlContent = generateSQLHeader($database, $host, count($tables));
    
    // Start building analysis
    $analysis = generateAnalysisHeader($database, $host, $dbInfo);
    
    echo "Processing tables...\n";
    echo str_repeat("-", 60) . "\n";
    
    $totalProcessed = 0;
    $relationships = [];
    
    foreach ($tables as $table) {
        $tableName = $table['TABLE_NAME'];
        echo sprintf("%-30s %8s rows %8.2f MB\n", 
            $tableName, 
            number_format($table['TABLE_ROWS']), 
            $table['size_mb']
        );
        
        try {
            // Get CREATE TABLE statement
            $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
            $createResult = $stmt->fetch();
            
            if ($createResult && isset($createResult['Create Table'])) {
                // Add to SQL output
                $sqlContent .= generateTableSQL($tableName, $createResult['Create Table'], $table);
                
                // Get column details for analysis
                $stmt = $pdo->prepare("
                    SELECT 
                        COLUMN_NAME,
                        COLUMN_TYPE,
                        IS_NULLABLE,
                        COLUMN_DEFAULT,
                        EXTRA,
                        COLUMN_COMMENT,
                        COLUMN_KEY
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION
                ");
                $stmt->execute([$database, $tableName]);
                $columns = $stmt->fetchAll();
                
                // Add to analysis
                $analysis .= generateTableAnalysis($tableName, $table, $columns);
                
                // Find relationships
                $tableRelationships = findRelationships($pdo, $database, $tableName);
                if ($tableRelationships) {
                    $relationships[$tableName] = $tableRelationships;
                }
                
                $totalProcessed++;
            }
            
        } catch (Exception $e) {
            echo "  ✗ Error processing $tableName: " . $e->getMessage() . "\n";
            $sqlContent .= "-- ERROR: Could not process table `$tableName`: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "✓ Processed $totalProcessed tables\n\n";
    
    // Add relationships to analysis
    if ($relationships) {
        $analysis .= generateRelationshipAnalysis($relationships);
    }
    
    // Get views, triggers, etc.
    $analysis .= analyzeViews($pdo, $database);
    $analysis .= analyzeTriggers($pdo, $database);
    $analysis .= analyzeIndexes($pdo, $database);
    
    // Finalize SQL content
    $sqlContent .= "\nCOMMIT;\n";
    $sqlContent .= "-- Export completed: " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Tables processed: $totalProcessed\n";
    
    // Write files
    file_put_contents($outputFile, $sqlContent);
    file_put_contents($analysisFile, $analysis);
    
    echo "✓ Schema extraction completed successfully!\n";
    echo "✓ SQL file: $outputFile (" . formatFileSize($outputFile) . ")\n";
    echo "✓ Analysis file: $analysisFile (" . formatFileSize($analysisFile) . ")\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Helper functions
function generateSQLHeader($database, $host, $tableCount) {
    return "-- Advanced Database Schema Export\n" .
           "-- Database: $database\n" .
           "-- Host: $host\n" .
           "-- Generated: " . date('Y-m-d H:i:s') . "\n" .
           "-- Tables: $tableCount\n" .
           "-- Generator: Advanced Schema Extractor v1.0\n\n" .
           "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" .
           "SET AUTOCOMMIT = 0;\n" .
           "START TRANSACTION;\n" .
           "SET time_zone = \"+00:00\";\n\n" .
           "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n" .
           "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n" .
           "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n" .
           "/*!40101 SET NAMES utf8mb4 */;\n\n" .
           "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;\n" .
           "USE `$database`;\n\n";
}

function generateAnalysisHeader($database, $host, $dbInfo) {
    return "DATABASE ANALYSIS REPORT\n" .
           str_repeat("=", 60) . "\n\n" .
           "Database: $database\n" .
           "Host: $host\n" .
           "Generated: " . date('Y-m-d H:i:s') . "\n" .
           "Total Size: {$dbInfo['size_mb']} MB\n" .
           "Total Tables: {$dbInfo['table_count']}\n\n" .
           "TABLE DETAILS:\n" .
           str_repeat("-", 60) . "\n\n";
}

function generateTableSQL($tableName, $createSQL, $tableInfo) {
    $output = "-- " . str_repeat("-", 70) . "\n";
    $output .= "-- Table: $tableName\n";
    $output .= "-- Engine: {$tableInfo['ENGINE']}\n";
    $output .= "-- Rows: " . number_format($tableInfo['TABLE_ROWS']) . "\n";
    $output .= "-- Size: {$tableInfo['size_mb']} MB (Data: {$tableInfo['data_mb']} MB, Index: {$tableInfo['index_mb']} MB)\n";
    if (!empty($tableInfo['TABLE_COMMENT'])) {
        $output .= "-- Comment: {$tableInfo['TABLE_COMMENT']}\n";
    }
    $output .= "-- " . str_repeat("-", 70) . "\n\n";
    $output .= "DROP TABLE IF EXISTS `$tableName`;\n";
    $output .= $createSQL . ";\n\n";
    
    return $output;
}

function generateTableAnalysis($tableName, $tableInfo, $columns) {
    $analysis = "TABLE: $tableName\n";
    $analysis .= "  Engine: {$tableInfo['ENGINE']}\n";
    $analysis .= "  Rows: " . number_format($tableInfo['TABLE_ROWS']) . "\n";
    $analysis .= "  Size: {$tableInfo['size_mb']} MB\n";
    $analysis .= "  Columns: " . count($columns) . "\n";
    
    // Analyze column types
    $columnTypes = [];
    $primaryKeys = [];
    $indexes = [];
    
    foreach ($columns as $column) {
        $type = preg_replace('/\(.*\)/', '', $column['COLUMN_TYPE']);
        $columnTypes[$type] = ($columnTypes[$type] ?? 0) + 1;
        
        if ($column['COLUMN_KEY'] === 'PRI') {
            $primaryKeys[] = $column['COLUMN_NAME'];
        }
        if (in_array($column['COLUMN_KEY'], ['MUL', 'UNI'])) {
            $indexes[] = $column['COLUMN_NAME'];
        }
    }
    
    $analysis .= "  Column Types: " . implode(', ', array_map(function($type, $count) {
        return "$type($count)";
    }, array_keys($columnTypes), $columnTypes)) . "\n";
    
    if ($primaryKeys) {
        $analysis .= "  Primary Key: " . implode(', ', $primaryKeys) . "\n";
    }
    
    if ($indexes) {
        $analysis .= "  Indexed Columns: " . implode(', ', $indexes) . "\n";
    }
    
    $analysis .= "\n";
    
    return $analysis;
}

function findRelationships($pdo, $database, $tableName) {
    $stmt = $pdo->prepare("
        SELECT 
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = ?
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute([$database, $tableName]);
    return $stmt->fetchAll();
}

function generateRelationshipAnalysis($relationships) {
    $analysis = "\nRELATIONSHIPS:\n";
    $analysis .= str_repeat("-", 60) . "\n\n";
    
    foreach ($relationships as $table => $relations) {
        if ($relations) {
            $analysis .= "TABLE: $table\n";
            foreach ($relations as $relation) {
                $analysis .= "  {$relation['COLUMN_NAME']} -> {$relation['REFERENCED_TABLE_NAME']}.{$relation['REFERENCED_COLUMN_NAME']}\n";
            }
            $analysis .= "\n";
        }
    }
    
    return $analysis;
}

function analyzeViews($pdo, $database) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as view_count
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'VIEW'
    ");
    $stmt->execute([$database]);
    $result = $stmt->fetch();
    
    return "\nVIEWS: {$result['view_count']}\n\n";
}

function analyzeTriggers($pdo, $database) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as trigger_count
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = ?
    ");
    $stmt->execute([$database]);
    $result = $stmt->fetch();
    
    return "TRIGGERS: {$result['trigger_count']}\n\n";
}

function analyzeIndexes($pdo, $database) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_indexes,
            COUNT(CASE WHEN NON_UNIQUE = 0 THEN 1 END) as unique_indexes,
            COUNT(CASE WHEN NON_UNIQUE = 1 THEN 1 END) as non_unique_indexes
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = ?
    ");
    $stmt->execute([$database]);
    $result = $stmt->fetch();
    
    return "INDEXES:\n" .
           "  Total: {$result['total_indexes']}\n" .
           "  Unique: {$result['unique_indexes']}\n" .
           "  Non-unique: {$result['non_unique_indexes']}\n\n";
}

function formatFileSize($filename) {
    $size = filesize($filename);
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

?>
