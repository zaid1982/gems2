<?php
require_once __DIR__ . '/_require_auth.php';

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

// Enhanced security check - allow safe operations
function isSafeQuery($query) {
    $query = trim($query);
    $upperQuery = strtoupper($query);
    
    // Allowed operations
    $allowedStarts = [
        'SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN',
        'INSERT', 'UPDATE', 'DELETE', 'ALTER'
    ];
    
    // Check if query starts with allowed operation
    $isAllowed = false;
    foreach ($allowedStarts as $start) {
        if (strpos($upperQuery, $start) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        return [
            'safe' => false,
            'reason' => 'Operation not allowed. Only SELECT, INSERT, UPDATE, DELETE, ALTER, SHOW, DESCRIBE, and EXPLAIN are permitted.'
        ];
    }
    
    // Critical operations to block
    $dangerousPatterns = [
        'DROP\s+DATABASE',
        'DROP\s+SCHEMA', 
        'DROP\s+TABLE',
        'TRUNCATE\s+TABLE',
        'CREATE\s+DATABASE',
        'CREATE\s+SCHEMA',
        'DROP\s+USER',
        'CREATE\s+USER',
        'GRANT\s+',
        'REVOKE\s+',
        'FLUSH\s+',
        'SHUTDOWN',
        'KILL\s+',
        'RESET\s+MASTER',
        'RESET\s+SLAVE',
        'CHANGE\s+MASTER',
        'LOAD\s+DATA\s+INFILE',
        'SELECT\s+.*\s+INTO\s+OUTFILE',
        'SELECT\s+.*\s+INTO\s+DUMPFILE'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match('/\b' . $pattern . '\b/i', $upperQuery)) {
            return [
                'safe' => false,
                'reason' => 'Dangerous operation detected: ' . str_replace('\\s+', ' ', $pattern) . '. This operation is blocked for security.'
            ];
        }
    }
    
    // Additional safety checks for DELETE and UPDATE without WHERE clause
    if (preg_match('/^DELETE\s+FROM\s+\w+\s*;?\s*$/i', $upperQuery)) {
        return [
            'safe' => false,
            'reason' => 'DELETE without WHERE clause is dangerous. Please add a WHERE condition to limit affected rows.'
        ];
    }
    
    if (preg_match('/^UPDATE\s+\w+\s+SET\s+.*\s*;?\s*$/i', $upperQuery) && !preg_match('/\bWHERE\b/i', $upperQuery)) {
        return [
            'safe' => false,
            'reason' => 'UPDATE without WHERE clause is dangerous. Please add a WHERE condition to limit affected rows.'
        ];
    }
    
    // Check for multiple statements (basic protection against SQL injection)
    $statements = explode(';', $query);
    $nonEmptyStatements = array_filter($statements, function($stmt) {
        return trim($stmt) !== '';
    });
    
    if (count($nonEmptyStatements) > 1) {
        return [
            'safe' => false,
            'reason' => 'Multiple statements detected. Please execute one statement at a time for security.'
        ];
    }
    
    return [
        'safe' => true,
        'reason' => 'Query passed security checks'
    ];
}

// Legacy function for backward compatibility
function isSelectQuery($query) {
    $check = isSafeQuery($query);
    return $check['safe'] && preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)/i', trim($query));
}

function isDangerousQuery($query) {
    $check = isSafeQuery($query);
    return !$check['safe'];
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

    case 'execute_file':
        handleExecuteSqlFile();
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

function isLocalhostRequest() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return $ip === '127.0.0.1' || $ip === '::1';
}

function isSqlFileExecutionEnabled() {
    // Explicit enable flag to avoid accidental production exposure.
    // Create this file temporarily when needed, then remove it.
    $flagPath = __DIR__ . '/.allow_sql_upload';
    return file_exists($flagPath);
}

function splitSqlStatements($sql) {
    // Splits SQL into statements, ignoring semicolons inside strings/comments.
    $statements = [];
    $buffer = '';
    $len = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if ($inLineComment) {
            $buffer .= $ch;
            if ($ch === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            $buffer .= $ch;
            if ($ch === '*' && $next === '/') {
                $buffer .= $next;
                $i++;
                $inBlockComment = false;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            // Start of comments
            if ($ch === '-' && $next === '-') {
                $buffer .= $ch . $next;
                $i++;
                $inLineComment = true;
                continue;
            }
            if ($ch === '#') {
                $buffer .= $ch;
                $inLineComment = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $buffer .= $ch . $next;
                $i++;
                $inBlockComment = true;
                continue;
            }
        }

        // Quote state transitions (handle escaped quotes)
        if (!$inDouble && !$inBacktick && $ch === "'" ) {
            // If already in single-quote and next is another single-quote, it's an escaped quote
            if ($inSingle && $next === "'") {
                $buffer .= $ch . $next;
                $i++;
                continue;
            }
            $inSingle = !$inSingle;
            $buffer .= $ch;
            continue;
        }
        if (!$inSingle && !$inBacktick && $ch === '"') {
            // basic toggle; MySQL also supports backslash escapes but this is good enough for migrations
            $inDouble = !$inDouble;
            $buffer .= $ch;
            continue;
        }
        if (!$inSingle && !$inDouble && $ch === '`') {
            $inBacktick = !$inBacktick;
            $buffer .= $ch;
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick && $ch === ';') {
            $stmt = trim($buffer);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $stmt = trim($buffer);
    if ($stmt !== '') {
        $statements[] = $stmt;
    }

    return $statements;
}

function isSafeMigrationStatement($statement) {
    $q = trim($statement);
    if ($q === '') return ['safe' => true, 'reason' => 'Empty'];
    $upper = strtoupper($q);

    // Allow only schema-migration oriented operations.
    $allowedStarts = [
        'SET',
        'ALTER',
        'CREATE TABLE',
        'CREATE INDEX',
        'CREATE UNIQUE INDEX',
        'CREATE VIEW',
        'CREATE OR REPLACE VIEW'
    ];

    $allowed = false;
    foreach ($allowedStarts as $start) {
        if (strpos($upper, $start) === 0) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        return ['safe' => false, 'reason' => 'Statement type not allowed for file execution'];
    }

    // Still block critical operations.
    $dangerousPatterns = [
        'DROP\s+DATABASE',
        'DROP\s+SCHEMA',
        'DROP\s+TABLE',
        'TRUNCATE\s+TABLE',
        'CREATE\s+DATABASE',
        'CREATE\s+SCHEMA',
        'DROP\s+USER',
        'CREATE\s+USER',
        'GRANT\s+',
        'REVOKE\s+',
        'FLUSH\s+',
        'SHUTDOWN',
        'KILL\s+',
        'RESET\s+MASTER',
        'RESET\s+SLAVE',
        'CHANGE\s+MASTER',
        'LOAD\s+DATA\s+INFILE',
        'INTO\s+OUTFILE',
        'INTO\s+DUMPFILE'
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (preg_match('/\b' . $pattern . '\b/i', $upper)) {
            return ['safe' => false, 'reason' => 'Dangerous operation detected'];
        }
    }

    return ['safe' => true, 'reason' => 'OK'];
}

function handleExecuteSqlFile() {
    global $pdo;

    if (!isLocalhostRequest()) {
        sendResponse(false, null, 'SQL file execution is only allowed from localhost.');
    }
    if (!isSqlFileExecutionEnabled()) {
        sendResponse(false, null, 'SQL file execution is disabled. Create maintenance/.allow_sql_upload to enable temporarily.');
    }

    if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
        sendResponse(false, null, 'No SQL file uploaded (field name: sqlFile).');
    }

    $file = $_FILES['sqlFile'];
    $name = $file['name'] ?? 'upload.sql';
    $size = (int)($file['size'] ?? 0);

    if ($size <= 0 || $size > 10 * 1024 * 1024) {
        sendResponse(false, null, 'SQL file size must be between 1 byte and 10MB.');
    }

    if (!preg_match('/\.sql$/i', $name)) {
        sendResponse(false, null, 'Only .sql files are allowed.');
    }

    $sql = file_get_contents($file['tmp_name']);
    if ($sql === false) {
        sendResponse(false, null, 'Failed to read uploaded file.');
    }

    $statements = splitSqlStatements($sql);
    if (count($statements) === 0) {
        sendResponse(false, null, 'No SQL statements found in file.');
    }

    $results = [];
    $startTime = microtime(true);

    foreach ($statements as $i => $stmt) {
        $safety = isSafeMigrationStatement($stmt);
        if (!$safety['safe']) {
            sendResponse(false, [
                'executed' => $results,
                'failed_at' => $i + 1,
                'statement' => substr($stmt, 0, 500)
            ], 'Security Error: ' . $safety['reason']);
        }

        try {
            // Use exec for DDL
            $pdo->exec($stmt);
            $results[] = [
                'no' => $i + 1,
                'ok' => true,
                'preview' => substr(preg_replace('/\s+/', ' ', $stmt), 0, 200)
            ];
        } catch (PDOException $e) {
            sendResponse(false, [
                'executed' => $results,
                'failed_at' => $i + 1,
                'statement' => $stmt
            ], 'SQL Error: ' . $e->getMessage());
        }
    }

    $elapsed = round(microtime(true) - $startTime, 4);
    sendResponse(true, [
        'file' => $name,
        'statement_count' => count($statements),
        'execution_time' => $elapsed,
        'executed' => $results
    ], null);
}

function handleExecuteQuery() {
    global $pdo;
    
    $query = trim($_POST['query'] ?? '');
    
    if (empty($query)) {
        sendResponse(false, null, "No query provided");
    }
    
    // Enhanced security checks
    $safetyCheck = isSafeQuery($query);
    if (!$safetyCheck['safe']) {
        sendResponse(false, null, "Security Error: " . $safetyCheck['reason']);
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
        $message = '';
        
        if (in_array($queryType, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'])) {
            // For queries that return data
            $results = $stmt->fetchAll();
            $affectedRows = count($results);
            $message = "Query executed successfully. Returned {$affectedRows} row(s).";
        } else {
            // For INSERT, UPDATE, DELETE, ALTER queries
            $affectedRows = $stmt->rowCount();
            
            switch ($queryType) {
                case 'INSERT':
                    $lastInsertId = $pdo->lastInsertId();
                    $message = "INSERT executed successfully. {$affectedRows} row(s) inserted.";
                    if ($lastInsertId) {
                        $message .= " Last insert ID: {$lastInsertId}";
                    }
                    break;
                case 'UPDATE':
                    $message = "UPDATE executed successfully. {$affectedRows} row(s) updated.";
                    break;
                case 'DELETE':
                    $message = "DELETE executed successfully. {$affectedRows} row(s) deleted.";
                    break;
                case 'ALTER':
                    $message = "ALTER executed successfully. Table structure modified.";
                    break;
                default:
                    $message = "Query executed successfully. {$affectedRows} row(s) affected.";
            }
        }
        
        sendResponse(true, [
            'query_type' => $queryType,
            'execution_time' => $executionTime,
            'affected_rows' => $affectedRows,
            'message' => $message,
            'results' => $results,
            'has_data' => !empty($results)
        ]);
        
    } catch (PDOException $e) {
        sendResponse(false, null, "SQL Error: " . $e->getMessage());
    } catch (Exception $e) {
        sendResponse(false, null, "Error: " . $e->getMessage());
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
