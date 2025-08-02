<?php
/**
 * Database Query Tool Backend
 * Handles AJAX requests for database operations
 * 
 * Author: Generated for GEMS2 System
 * Date: August 2, 2025
 */

// Include database configuration
require_once('../api/class/Constant.php');

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
$host = Constant::$dbHost;
$username = Constant::$dbUserName;
$password = Constant::$dbUserPassword;
$database = Constant::$dbName;

// Response function
function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ] + ($data ?? []));
    exit;
}

// Security check - basic protection
function isSelectQuery($query) {
    $query = trim(strtoupper($query));
    $allowedStarts = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'];
    
    foreach ($allowedStarts as $start) {
        if (strpos($query, $start) === 0) {
            return true;
        }
    }
    return false;
}

function isDangerousQuery($query) {
    $query = strtoupper($query);
    $dangerous = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE'];
    
    foreach ($dangerous as $danger) {
        if (strpos($query, $danger) !== false) {
            return true;
        }
    }
    return false;
}

// Create PDO connection
try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch (PDOException $e) {
    sendResponse(false, null, "Database connection failed: " . $e->getMessage());
}

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'execute':
        handleExecuteQuery();
        break;
        
    case 'database_info':
        handleDatabaseInfo();
        break;
        
    case 'list_tables':
        handleListTables();
        break;
        
    case 'table_columns':
        handleTableColumns();
        break;
        
    default:
        sendResponse(false, null, "Invalid action specified");
}

function handleExecuteQuery() {
    global $pdo;
    
    $query = trim($_POST['query'] ?? '');
    
    if (empty($query)) {
        sendResponse(false, null, "No query provided");
    }
    
    // Security checks
    if (!isSelectQuery($query) && isDangerousQuery($query)) {
        sendResponse(false, null, "For security reasons, only SELECT, SHOW, DESCRIBE, and EXPLAIN queries are allowed");
    }
    
    try {
        $startTime = microtime(true);
        
        // Execute query
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 4);
        
        // Determine query type
        $queryType = strtoupper(trim(explode(' ', $query)[0]));
        
        $results = [];
        $affectedRows = 0;
        
        if ($queryType === 'SELECT' || $queryType === 'SHOW' || $queryType === 'DESCRIBE' || $queryType === 'DESC' || $queryType === 'EXPLAIN') {
            $results = $stmt->fetchAll();
            $affectedRows = count($results);
        } else {
            $affectedRows = $stmt->rowCount();
        }
        
        sendResponse(true, [
            'results' => $results,
            'query_type' => $queryType,
            'execution_time' => $executionTime,
            'affected_rows' => $affectedRows,
            'query' => $query
        ]);
        
    } catch (PDOException $e) {
        sendResponse(false, null, "Query error: " . $e->getMessage());
    } catch (Exception $e) {
        sendResponse(false, null, "Execution error: " . $e->getMessage());
    }
}

function handleDatabaseInfo() {
    global $pdo, $database;
    
    try {
        // Get database size and table count
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as table_count,
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = ?
            AND table_type = 'BASE TABLE'
        ");
        $stmt->execute([$database]);
        $dbStats = $stmt->fetch();
        
        // Get MySQL version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        
        sendResponse(true, [
            'info' => [
                'database_name' => $database,
                'table_count' => $dbStats['table_count'],
                'size_mb' => $dbStats['size_mb'],
                'version' => $version,
                'host' => Constant::$dbHost
            ]
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, null, "Error getting database info: " . $e->getMessage());
    }
}

function handleListTables() {
    global $pdo, $database;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                TABLE_NAME as name,
                IFNULL(TABLE_ROWS, 0) as `rows`,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 2) AS size_kb,
                CASE 
                    WHEN (DATA_LENGTH + INDEX_LENGTH) < 1024 THEN CONCAT(ROUND((DATA_LENGTH + INDEX_LENGTH), 0), ' B')
                    WHEN (DATA_LENGTH + INDEX_LENGTH) < 1048576 THEN CONCAT(ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1), ' KB')
                    WHEN (DATA_LENGTH + INDEX_LENGTH) < 1073741824 THEN CONCAT(ROUND((DATA_LENGTH + INDEX_LENGTH) / 1048576, 1), ' MB')
                    ELSE CONCAT(ROUND((DATA_LENGTH + INDEX_LENGTH) / 1073741824, 1), ' GB')
                END as size,
                ENGINE,
                TABLE_COLLATION,
                TABLE_COMMENT
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ?
            AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ");
        $stmt->execute([$database]);
        $tables = $stmt->fetchAll();
        
        // Format row numbers
        foreach ($tables as &$table) {
            if ($table['rows'] !== null) {
                $table['rows'] = number_format((int)$table['rows']);
            } else {
                $table['rows'] = '0';
            }
        }
        
        sendResponse(true, [
            'tables' => $tables
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, null, "Error listing tables: " . $e->getMessage());
    }
}

function handleTableColumns() {
    global $pdo, $database;
    
    $tableName = $_POST['table'] ?? '';
    
    if (empty($tableName)) {
        sendResponse(false, null, "No table name provided");
    }
    
    // Validate table name (security)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        sendResponse(false, null, "Invalid table name");
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLUMN_KEY,
                EXTRA,
                COLUMN_COMMENT,
                ORDINAL_POSITION
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $stmt->execute([$database, $tableName]);
        $columns = $stmt->fetchAll();
        
        sendResponse(true, [
            'columns' => $columns,
            'table_name' => $tableName
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, null, "Error getting table columns: " . $e->getMessage());
    }
}

// Additional utility functions for future enhancements

function getTableIndexes($tableName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$tableName`");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getTableForeignKeys($tableName) {
    global $pdo, $database;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$database, $tableName]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function validateQuery($query) {
    // Additional query validation can be added here
    $query = trim($query);
    
    // Check for empty query
    if (empty($query)) {
        return "Query cannot be empty";
    }
    
    // Check for multiple statements (basic check)
    if (substr_count($query, ';') > 1) {
        return "Multiple statements are not allowed";
    }
    
    return null; // Valid
}

// Log query execution (optional)
function logQuery($query, $executionTime, $success, $error = null) {
    $logFile = '../logs/query_tool_' . date('Y-m-d') . '.log';
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'query' => $query,
        'execution_time' => $executionTime,
        'success' => $success,
        'error' => $error,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Write log entry
    @file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

?>
