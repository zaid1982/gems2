<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration (same as working db_query_tool.php)
require_once('../api/class/Constant.php');

// Enhanced database configuration using existing constants
$config = [
    'host' => Constant::$dbHost,
    'username' => Constant::$dbUserName,
    'password' => Constant::$dbUserPassword,
    'database' => Constant::$dbName,
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get action from POST data
$action = $_POST['action'] ?? '';

/**
 * Enhanced database operations
 */
class AdvancedDatabaseTool {
    private $pdo;
    private $config;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
    }
    
    /**
     * Get comprehensive database information
     */
    public function getDatabaseInfo() {
        try {
            // Basic database info
            $stmt = $this->pdo->prepare("SELECT DATABASE() as database_name");
            $stmt->execute();
            $dbInfo = $stmt->fetch();
            
            // Version info
            $stmt = $this->pdo->prepare("SELECT VERSION() as version");
            $stmt->execute();
            $versionInfo = $stmt->fetch();
            
            // Table count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?");
            $stmt->execute([$this->config['database']]);
            $tableCount = $stmt->fetch();
            
            // Database size
            $stmt = $this->pdo->prepare("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ");
            $stmt->execute([$this->config['database']]);
            $sizeInfo = $stmt->fetch();
            
            // Connection info
            $stmt = $this->pdo->prepare("SHOW STATUS LIKE 'Threads_connected'");
            $stmt->execute();
            $connectionInfo = $stmt->fetch();
            
            // Character set
            $stmt = $this->pdo->prepare("SELECT DEFAULT_CHARACTER_SET_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$this->config['database']]);
            $charsetInfo = $stmt->fetch();
            
            return [
                'success' => true,
                'info' => [
                    'database_name' => $dbInfo['database_name'],
                    'host' => $this->config['host'],
                    'version' => $versionInfo['version'],
                    'table_count' => $tableCount['table_count'],
                    'size_mb' => $sizeInfo['size_mb'] ?? 0,
                    'charset' => $charsetInfo['DEFAULT_CHARACTER_SET_NAME'] ?? 'unknown',
                    'connections' => $connectionInfo['Value'] ?? 0
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get enhanced table list with detailed information
     */
    public function getTableList() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    TABLE_NAME as name,
                    TABLE_TYPE as type,
                    ENGINE as engine,
                    COALESCE(`TABLE_ROWS`, 0) as row_count,
                    ROUND(COALESCE((DATA_LENGTH + INDEX_LENGTH), 0) / 1024, 2) as size_kb,
                    ROUND(COALESCE(DATA_LENGTH, 0) / 1024, 2) as data_kb,
                    ROUND(COALESCE(INDEX_LENGTH, 0) / 1024, 2) as index_kb,
                    TABLE_COLLATION as collation,
                    CREATE_TIME as created,
                    UPDATE_TIME as updated,
                    TABLE_COMMENT as comment
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ?
                ORDER BY TABLE_NAME
            ");
            $stmt->execute([$this->config['database']]);
            $tables = $stmt->fetchAll();
            
            // Format the data
            foreach ($tables as &$table) {
                $table['rows'] = number_format($table['row_count'] ?? 0);
                $table['size'] = $this->formatSize($table['size_kb'] * 1024);
                $table['data_size'] = $this->formatSize($table['data_kb'] * 1024);
                $table['index_size'] = $this->formatSize($table['index_kb'] * 1024);
                $table['created'] = $table['created'] ? date('Y-m-d H:i:s', strtotime($table['created'])) : null;
                $table['updated'] = $table['updated'] ? date('Y-m-d H:i:s', strtotime($table['updated'])) : null;
            }
            
            return [
                'success' => true,
                'tables' => $tables
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get detailed table columns with enhanced information
     */
    public function getTableColumns($tableName) {
        try {
            // Validate table name
            if (!$this->isValidTableName($tableName)) {
                throw new Exception("Invalid table name");
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COLUMN_NAME,
                    COLUMN_TYPE,
                    DATA_TYPE,
                    CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION,
                    NUMERIC_SCALE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    COLUMN_KEY,
                    EXTRA,
                    COLUMN_COMMENT,
                    ORDINAL_POSITION
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ");
            $stmt->execute([$this->config['database'], $tableName]);
            $columns = $stmt->fetchAll();
            
            // Get foreign key information
            $stmt = $this->pdo->prepare("
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $stmt->execute([$this->config['database'], $tableName]);
            $foreignKeys = $stmt->fetchAll();
            
            // Merge foreign key info
            $fkMap = [];
            foreach ($foreignKeys as $fk) {
                $fkMap[$fk['COLUMN_NAME']] = [
                    'table' => $fk['REFERENCED_TABLE_NAME'],
                    'column' => $fk['REFERENCED_COLUMN_NAME']
                ];
            }
            
            foreach ($columns as &$column) {
                if (isset($fkMap[$column['COLUMN_NAME']])) {
                    $column['FOREIGN_KEY'] = $fkMap[$column['COLUMN_NAME']];
                }
            }
            
            return [
                'success' => true,
                'columns' => $columns,
                'table_name' => $tableName
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Execute query with enhanced error handling and performance tracking
     */
    public function executeQuery($query) {
        try {
            $startTime = microtime(true);
            $query = trim($query);
            
            // Detect query type
            $queryType = $this->getQueryType($query);
            
            // Security checks
            if (!$this->isQuerySafe($query, $queryType)) {
                throw new Exception("Query contains potentially dangerous operations");
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            $executionTime = round(microtime(true) - $startTime, 4);
            $affectedRows = $stmt->rowCount();
            
            $result = [
                'success' => true,
                'query_type' => $queryType,
                'execution_time' => $executionTime,
                'affected_rows' => $affectedRows
            ];
            
            if ($queryType === 'SELECT' || $queryType === 'SHOW' || $queryType === 'DESCRIBE' || $queryType === 'EXPLAIN') {
                $results = $stmt->fetchAll();
                $result['results'] = $results;
                $result['row_count'] = count($results);
            } else {
                $result['results'] = [];
                $result['row_count'] = $affectedRows;
            }
            
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'query_type' => $this->getQueryType($query)
            ];
        }
    }
    
    /**
     * Get table indexes
     */
    public function getTableIndexes($tableName) {
        try {
            if (!$this->isValidTableName($tableName)) {
                throw new Exception("Invalid table name");
            }
            
            $stmt = $this->pdo->prepare("SHOW INDEXES FROM `$tableName`");
            $stmt->execute();
            $indexes = $stmt->fetchAll();
            
            return [
                'success' => true,
                'indexes' => $indexes
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get table creation script
     */
    public function getCreateTable($tableName) {
        try {
            if (!$this->isValidTableName($tableName)) {
                throw new Exception("Invalid table name");
            }
            
            $stmt = $this->pdo->prepare("SHOW CREATE TABLE `$tableName`");
            $stmt->execute();
            $result = $stmt->fetch();
            
            return [
                'success' => true,
                'create_script' => $result['Create Table'] ?? ''
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get database performance metrics
     */
    public function getPerformanceMetrics() {
        try {
            $metrics = [];
            
            // Query cache stats
            $stmt = $this->pdo->prepare("SHOW STATUS LIKE 'Qcache%'");
            $stmt->execute();
            $qcacheStats = $stmt->fetchAll();
            
            // Connection stats
            $stmt = $this->pdo->prepare("SHOW STATUS WHERE Variable_name IN ('Connections', 'Threads_connected', 'Threads_running', 'Uptime')");
            $stmt->execute();
            $connectionStats = $stmt->fetchAll();
            
            // InnoDB stats
            $stmt = $this->pdo->prepare("SHOW STATUS WHERE Variable_name LIKE 'Innodb_%' AND Variable_name IN ('Innodb_buffer_pool_pages_total', 'Innodb_buffer_pool_pages_free', 'Innodb_buffer_pool_read_requests', 'Innodb_buffer_pool_reads')");
            $stmt->execute();
            $innodbStats = $stmt->fetchAll();
            
            return [
                'success' => true,
                'metrics' => [
                    'query_cache' => $qcacheStats,
                    'connections' => $connectionStats,
                    'innodb' => $innodbStats
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get slow query log
     */
    public function getSlowQueries($limit = 10) {
        try {
            // Check if slow query log is enabled
            $stmt = $this->pdo->prepare("SHOW VARIABLES LIKE 'slow_query_log'");
            $stmt->execute();
            $slowLogEnabled = $stmt->fetch();
            
            if (!$slowLogEnabled || $slowLogEnabled['Value'] !== 'ON') {
                return [
                    'success' => false,
                    'error' => 'Slow query log is not enabled'
                ];
            }
            
            // Get slow query log file location
            $stmt = $this->pdo->prepare("SHOW VARIABLES LIKE 'slow_query_log_file'");
            $stmt->execute();
            $slowLogFile = $stmt->fetch();
            
            return [
                'success' => true,
                'slow_log_enabled' => true,
                'slow_log_file' => $slowLogFile['Value'] ?? 'Unknown'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Utility functions
     */
    private function formatSize($bytes) {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    private function getQueryType($query) {
        $query = strtoupper(trim($query));
        if (strpos($query, 'SELECT') === 0) return 'SELECT';
        if (strpos($query, 'INSERT') === 0) return 'INSERT';
        if (strpos($query, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($query, 'DELETE') === 0) return 'DELETE';
        if (strpos($query, 'CREATE') === 0) return 'CREATE';
        if (strpos($query, 'ALTER') === 0) return 'ALTER';
        if (strpos($query, 'DROP') === 0) return 'DROP';
        if (strpos($query, 'SHOW') === 0) return 'SHOW';
        if (strpos($query, 'DESCRIBE') === 0 || strpos($query, 'DESC') === 0) return 'DESCRIBE';
        if (strpos($query, 'EXPLAIN') === 0) return 'EXPLAIN';
        return 'OTHER';
    }
    
    private function isQuerySafe($query, $queryType) {
        $query = strtoupper($query);
        
        // Dangerous patterns
        $dangerousPatterns = [
            'DROP TABLE',
            'DROP DATABASE',
            'TRUNCATE',
            'DELETE.*WHERE.*1\s*=\s*1',
            'UPDATE.*WHERE.*1\s*=\s*1'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $query)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function isValidTableName($tableName) {
        // Basic validation - alphanumeric, underscore, dash
        return preg_match('/^[a-zA-Z0-9_-]+$/', $tableName);
    }
}

// Initialize the tool
$tool = new AdvancedDatabaseTool($pdo, $config);

// Route the request
switch ($action) {
    case 'database_info':
        echo json_encode($tool->getDatabaseInfo());
        break;
        
    case 'list_tables':
        echo json_encode($tool->getTableList());
        break;
        
    case 'table_columns':
        $tableName = $_POST['table'] ?? '';
        echo json_encode($tool->getTableColumns($tableName));
        break;
        
    case 'execute':
        $query = $_POST['query'] ?? '';
        echo json_encode($tool->executeQuery($query));
        break;
        
    case 'table_indexes':
        $tableName = $_POST['table'] ?? '';
        echo json_encode($tool->getTableIndexes($tableName));
        break;
        
    case 'create_table':
        $tableName = $_POST['table'] ?? '';
        echo json_encode($tool->getCreateTable($tableName));
        break;
        
    case 'performance_metrics':
        echo json_encode($tool->getPerformanceMetrics());
        break;
        
    case 'slow_queries':
        $limit = intval($_POST['limit'] ?? 10);
        echo json_encode($tool->getSlowQueries($limit));
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action specified',
            'available_actions' => [
                'database_info',
                'list_tables', 
                'table_columns',
                'execute',
                'table_indexes',
                'create_table',
                'performance_metrics',
                'slow_queries'
            ]
        ]);
        break;
}
?>
