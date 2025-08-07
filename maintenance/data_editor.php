<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Include database configuration
    require_once '../api/class/Constant.php';
    
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . Constant::$dbHost . ";dbname=" . Constant::$dbName . ";charset=utf8mb4",
        Constant::$dbUserName,
        Constant::$dbUserPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Get action from request
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Handle JSON input for complex operations
    $json_input = file_get_contents('php://input');
    if ($json_input) {
        $json_data = json_decode($json_input, true);
        if ($json_data) {
            $action = $json_data['action'] ?? $action;
        }
    }
    
    switch ($action) {
        case 'list_tables':
            listTables($pdo);
            break;
            
        case 'table_schema':
            getTableSchema($pdo, $_POST['table'] ?? '');
            break;
            
        case 'table_data':
            getTableData($pdo, $_POST);
            break;
            
        case 'save_changes':
            saveChanges($pdo, $json_data);
            break;
            
        case 'add_record':
            addRecord($pdo, $_POST);
            break;
            
        case 'delete_record':
            deleteRecord($pdo, $_POST);
            break;
            
        case 'database_info':
            getDatabaseInfo($pdo);
            break;
            
        case 'get_foreign_key_display':
            getForeignKeyDisplay($pdo, $_POST);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * List all tables in the database with row counts
 */
function listTables($pdo) {
    try {
        // Get tables with row counts
        $sql = "
            SELECT 
                TABLE_NAME as name,
                TABLE_ROWS as `rows`,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_mb,
                TABLE_COMMENT as comment
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ";
        
        $stmt = $pdo->query($sql);
        $tables = $stmt->fetchAll();
        
        // Get more accurate row counts for tables with 0 or very low estimates
        foreach ($tables as &$table) {
            if ($table['rows'] <= 1) {
                try {
                    $countStmt = $pdo->query("SELECT COUNT(*) as actual_count FROM `{$table['name']}`");
                    $actualCount = $countStmt->fetch();
                    $table['rows'] = $actualCount['actual_count'];
                } catch (Exception $e) {
                    // Keep original estimate if count fails
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'tables' => $tables,
            'count' => count($tables)
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error listing tables: ' . $e->getMessage());
    }
}

/**
 * Get table schema information
 */
function getTableSchema($pdo, $table) {
    if (empty($table)) {
        throw new Exception('Table name is required');
    }
    
    // Validate table name to prevent SQL injection
    if (!isValidTableName($pdo, $table)) {
        throw new Exception('Invalid table name');
    }
    
    try {
        // Get column information
        $sql = "
            SELECT 
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                EXTRA,
                COLUMN_KEY,
                DATA_TYPE,
                CHARACTER_MAXIMUM_LENGTH,
                NUMERIC_PRECISION,
                NUMERIC_SCALE,
                COLUMN_COMMENT
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table]);
        $columns = $stmt->fetchAll();
        
        // Get primary key information
        $sql = "
            SELECT COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table]);
        $primaryKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get foreign key information
        $sql = "
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table]);
        $foreignKeys = $stmt->fetchAll();
        
        // Create a more usable foreign key mapping
        $foreignKeyMap = [];
        foreach ($foreignKeys as $fk) {
            $foreignKeyMap[$fk['COLUMN_NAME']] = [
                'referenced_table' => $fk['REFERENCED_TABLE_NAME'],
                'referenced_column' => $fk['REFERENCED_COLUMN_NAME'],
                'constraint_name' => $fk['CONSTRAINT_NAME']
            ];
        }
        
        // Get table information
        $sql = "
            SELECT 
                TABLE_ROWS as row_count,
                AVG_ROW_LENGTH as avg_row_length,
                DATA_LENGTH as data_length,
                INDEX_LENGTH as index_length,
                TABLE_COMMENT as comment,
                ENGINE,
                TABLE_COLLATION as collation
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table]);
        $tableInfo = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'schema' => [
                'table_name' => $table,
                'columns' => $columns,
                'primary_key' => $primaryKeys,
                'foreign_keys' => $foreignKeys,
                'foreign_key_map' => $foreignKeyMap,
                'table_info' => $tableInfo
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error getting table schema: ' . $e->getMessage());
    }
}

/**
 * Get table data with pagination and search
 */
function getTableData($pdo, $params) {
    $table = $params['table'] ?? '';
    $page = max(1, intval($params['page'] ?? 1));
    $limit = max(1, min(1000, intval($params['limit'] ?? 25)));
    $search = trim($params['search'] ?? '');
    $sortColumn = trim($params['sort_column'] ?? '');
    $sortDirection = strtoupper(trim($params['sort_direction'] ?? 'ASC'));
    
    if (empty($table)) {
        throw new Exception('Table name is required');
    }
    
    // Validate table name
    if (!isValidTableName($pdo, $table)) {
        throw new Exception('Invalid table name');
    }
    
    // Validate sort direction
    if (!in_array($sortDirection, ['ASC', 'DESC'])) {
        $sortDirection = 'ASC';
    }
    
    try {
        $offset = ($page - 1) * $limit;
        
        // Build search condition
        $searchCondition = '';
        $searchParams = [];
        
        if (!empty($search)) {
            // Get column names for search
            $columnsStmt = $pdo->prepare("
                SELECT COLUMN_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ?
                AND DATA_TYPE IN ('varchar', 'text', 'char', 'longtext', 'mediumtext', 'tinytext')
            ");
            $columnsStmt->execute([$table]);
            $searchableColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($searchableColumns)) {
                $searchConditions = [];
                foreach ($searchableColumns as $column) {
                    $searchConditions[] = "`$column` LIKE ?";
                    $searchParams[] = "%$search%";
                }
                $searchCondition = 'WHERE ' . implode(' OR ', $searchConditions);
            }
        }
        
        // Build ORDER BY clause
        $orderByClause = '';
        if (!empty($sortColumn) && isValidColumn($pdo, $table, $sortColumn)) {
            $orderByClause = "ORDER BY `$sortColumn` $sortDirection";
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM `$table` $searchCondition";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($searchParams);
        $totalCount = $countStmt->fetch()['total'];
        
        // Get data with pagination and sorting
        $dataSql = "SELECT * FROM `$table` $searchCondition $orderByClause LIMIT $limit OFFSET $offset";
        $dataStmt = $pdo->prepare($dataSql);
        $dataStmt->execute($searchParams);
        $records = $dataStmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'records' => $records,
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit),
            'sort_column' => $sortColumn,
            'sort_direction' => $sortDirection
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error getting table data: ' . $e->getMessage());
    }
}

/**
 * Save multiple record changes
 */
function saveChanges($pdo, $data) {
    $table = $data['table'] ?? '';
    $changes = $data['changes'] ?? [];
    
    if (empty($table)) {
        throw new Exception('Table name is required');
    }
    
    if (!isValidTableName($pdo, $table)) {
        throw new Exception('Invalid table name');
    }
    
    if (empty($changes)) {
        throw new Exception('No changes provided');
    }
    
    try {
        // Get primary key columns
        $primaryKeys = getPrimaryKeys($pdo, $table);
        if (empty($primaryKeys)) {
            throw new Exception('Table must have a primary key for updates');
        }
        
        $pdo->beginTransaction();
        $updatedCount = 0;
        
        foreach ($changes as $rowIndex => $change) {
            if ($change['type'] === 'update' && !empty($change['changes'])) {
                $original = $change['original'];
                $updates = $change['changes'];
                
                // Build WHERE clause using primary key
                $whereConditions = [];
                $whereParams = [];
                foreach ($primaryKeys as $pk) {
                    $whereConditions[] = "`$pk` = ?";
                    $whereParams[] = $original[$pk];
                }
                $whereClause = implode(' AND ', $whereConditions);
                
                // Build SET clause
                $setConditions = [];
                $setParams = [];
                foreach ($updates as $column => $value) {
                    // Validate column exists
                    if (!isValidColumn($pdo, $table, $column)) {
                        throw new Exception("Invalid column: $column");
                    }
                    
                    $setConditions[] = "`$column` = ?";
                    $setParams[] = $value === '' ? null : $value;
                }
                
                if (!empty($setConditions)) {
                    $sql = "UPDATE `$table` SET " . implode(', ', $setConditions) . " WHERE $whereClause";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge($setParams, $whereParams));
                    $updatedCount++;
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'updated' => $updatedCount,
            'message' => "Successfully updated $updatedCount record(s)"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error saving changes: ' . $e->getMessage());
    }
}

/**
 * Add new record
 */
function addRecord($pdo, $params) {
    $table = $params['table'] ?? '';
    $data = $params['data'] ?? [];
    
    if (empty($table)) {
        throw new Exception('Table name is required');
    }
    
    if (!isValidTableName($pdo, $table)) {
        throw new Exception('Invalid table name');
    }
    
    try {
        // Get table columns to validate input
        $columnsStmt = $pdo->prepare("
            SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $columnsStmt->execute([$table]);
        $columns = $columnsStmt->fetchAll();
        
        // Filter out auto-increment columns
        $insertableColumns = [];
        foreach ($columns as $column) {
            if (!strpos($column['EXTRA'], 'auto_increment')) {
                $insertableColumns[] = $column['COLUMN_NAME'];
            }
        }
        
        // Build INSERT query
        $columnNames = implode('`, `', $insertableColumns);
        $placeholders = str_repeat('?,', count($insertableColumns) - 1) . '?';
        
        $sql = "INSERT INTO `$table` (`$columnNames`) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        // Prepare values
        $values = [];
        foreach ($insertableColumns as $column) {
            $values[] = $data[$column] ?? null;
        }
        
        $stmt->execute($values);
        
        echo json_encode([
            'success' => true,
            'inserted_id' => $pdo->lastInsertId(),
            'message' => 'Record added successfully'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error adding record: ' . $e->getMessage());
    }
}

/**
 * Delete record
 */
function deleteRecord($pdo, $params) {
    $table = $params['table'] ?? '';
    $primaryKeyData = $params['primary_key'] ?? [];
    
    if (empty($table)) {
        throw new Exception('Table name is required');
    }
    
    if (!isValidTableName($pdo, $table)) {
        throw new Exception('Invalid table name');
    }
    
    if (empty($primaryKeyData)) {
        throw new Exception('Primary key data is required');
    }
    
    try {
        // Get primary key columns
        $primaryKeys = getPrimaryKeys($pdo, $table);
        if (empty($primaryKeys)) {
            throw new Exception('Table must have a primary key for deletions');
        }
        
        // Build WHERE clause
        $whereConditions = [];
        $whereParams = [];
        foreach ($primaryKeys as $pk) {
            if (!isset($primaryKeyData[$pk])) {
                throw new Exception("Primary key value missing for: $pk");
            }
            $whereConditions[] = "`$pk` = ?";
            $whereParams[] = $primaryKeyData[$pk];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        $sql = "DELETE FROM `$table` WHERE $whereClause";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($whereParams);
        
        echo json_encode([
            'success' => true,
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Record deleted successfully'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error deleting record: ' . $e->getMessage());
    }
}

/**
 * Get database information
 */
function getDatabaseInfo($pdo) {
    try {
        // Get database name and host
        $dbInfo = $pdo->query("SELECT DATABASE() as db_name")->fetch();
        $hostInfo = $pdo->query("SELECT @@hostname as hostname")->fetch();
        
        // Get table count
        $tableCount = $pdo->query("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_TYPE = 'BASE TABLE'
        ")->fetch();
        
        // Get database size
        $sizeInfo = $pdo->query("
            SELECT 
                ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE()
        ")->fetch();
        
        echo json_encode([
            'success' => true,
            'info' => [
                'database_name' => $dbInfo['db_name'],
                'host' => $hostInfo['hostname'],
                'table_count' => $tableCount['count'],
                'size_mb' => $sizeInfo['size_mb']
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error getting database info: ' . $e->getMessage());
    }
}

/**
 * Validate table name exists in database
 */
function isValidTableName($pdo, $tableName) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = ?
        AND TABLE_TYPE = 'BASE TABLE'
    ");
    $stmt->execute([$tableName]);
    return $stmt->fetch()['count'] > 0;
}

/**
 * Validate column exists in table
 */
function isValidColumn($pdo, $tableName, $columnName) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = ? 
        AND COLUMN_NAME = ?
    ");
    $stmt->execute([$tableName, $columnName]);
    return $stmt->fetch()['count'] > 0;
}

/**
 * Get primary key columns for a table
 */
function getPrimaryKeys($pdo, $tableName) {
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = ? 
        AND CONSTRAINT_NAME = 'PRIMARY'
        ORDER BY ORDINAL_POSITION
    ");
    $stmt->execute([$tableName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Sanitize and validate user input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get display value for foreign key reference
 */
function getForeignKeyDisplay($pdo, $params) {
    $table = $params['table'] ?? '';
    $column = $params['column'] ?? '';
    $value = $params['value'] ?? '';
    
    if (empty($table) || empty($column) || empty($value)) {
        throw new Exception('Table, column, and value are required');
    }
    
    if (!isValidTableName($pdo, $table)) {
        throw new Exception('Invalid referenced table name');
    }
    
    if (!isValidColumn($pdo, $table, $column)) {
        throw new Exception('Invalid referenced column name');
    }
    
    try {
        // Try to find a suitable display column
        $displayColumns = ['name', 'title', 'description', 'label', 'text', 'user_name', 'site_name'];
        $availableColumns = [];
        
        // Get all columns for the referenced table
        $columnsStmt = $pdo->prepare("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $columnsStmt->execute([$table]);
        $allColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Find the best display column
        $displayColumn = $column; // Default to the key column itself
        
        foreach ($displayColumns as $preferred) {
            if (in_array($preferred, $allColumns)) {
                $displayColumn = $preferred;
                break;
            }
        }
        
        // If no preferred column found, use the second column if available
        if ($displayColumn === $column && count($allColumns) > 1) {
            $displayColumn = $allColumns[1];
        }
        
        // Get the display value
        $sql = "SELECT `$column` as key_value, `$displayColumn` as display_value FROM `$table` WHERE `$column` = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'key_value' => $result['key_value'],
                'display_value' => $result['display_value'],
                'display_column' => $displayColumn,
                'referenced_table' => $table
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Referenced record not found'
            ]);
        }
        
    } catch (Exception $e) {
        throw new Exception('Error getting foreign key display: ' . $e->getMessage());
    }
}

/**
 * Log activity for audit purposes
 */
function logActivity($action, $table, $details = '') {
    $logFile = 'data_editor_activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = "[$timestamp] IP: $ip | Action: $action | Table: $table | Details: $details | User-Agent: $userAgent" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
