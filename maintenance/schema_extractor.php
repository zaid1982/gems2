<?php
// Database Schema Extractor and Comparison Tool
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Read database configuration
function getDatabaseConfig() {
    $configFile = '../api/library/config.ini';
    if (file_exists($configFile)) {
        $config = parse_ini_file($configFile, true);
        return [
            'host' => $config['database']['dbhost'] ?? 'localhost',
            'name' => $config['database']['dbname'] ?? 'gems',
            'user' => $config['database']['username'] ?? 'root',
            'pass' => $config['database']['password'] ?? ''
        ];
    }
    return [
        'host' => 'localhost',
        'name' => 'gems',
        'user' => 'root',
        'pass' => ''
    ];
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $dbConfig = getDatabaseConfig();
    $pdo = new PDO(
        "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    switch ($action) {
        case 'extract_schema':
            extractFullSchema($pdo);
            break;
        
        case 'get_tables':
            getTables($pdo);
            break;
        
        case 'get_table_details':
            getTableDetails($pdo);
            break;
        
        case 'compare_schemas':
            compareSchemas();
            break;
        
        case 'export_schema':
            exportSchema($pdo);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function extractFullSchema($pdo) {
    $schema = [
        'database_info' => getDatabaseInfo($pdo),
        'tables' => [],
        'extraction_time' => date('Y-m-d H:i:s'),
        'total_tables' => 0,
        'total_columns' => 0,
        'total_indexes' => 0,
        'total_foreign_keys' => 0
    ];
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $schema['total_tables'] = count($tables);
    
    foreach ($tables as $tableName) {
        $tableSchema = getCompleteTableSchema($pdo, $tableName);
        $schema['tables'][$tableName] = $tableSchema;
        
        // Update counters
        $schema['total_columns'] += count($tableSchema['columns']);
        $schema['total_indexes'] += count($tableSchema['indexes']);
        $schema['total_foreign_keys'] += count($tableSchema['foreign_keys']);
    }
    
    echo json_encode([
        'success' => true,
        'schema' => $schema,
        'summary' => [
            'database' => $schema['database_info']['database_name'],
            'tables' => $schema['total_tables'],
            'columns' => $schema['total_columns'],
            'indexes' => $schema['total_indexes'],
            'foreign_keys' => $schema['total_foreign_keys'],
            'extraction_time' => $schema['extraction_time']
        ]
    ]);
}

function getDatabaseInfo($pdo) {
    $info = [];
    
    // Database name and charset
    $result = $pdo->query("SELECT DATABASE() as db_name")->fetch();
    $info['database_name'] = $result['db_name'];
    
    // Database charset and collation
    $result = $pdo->query("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME 
                          FROM information_schema.SCHEMATA 
                          WHERE SCHEMA_NAME = DATABASE()")->fetch();
    $info['charset'] = $result['DEFAULT_CHARACTER_SET_NAME'];
    $info['collation'] = $result['DEFAULT_COLLATION_NAME'];
    
    // MySQL version
    $result = $pdo->query("SELECT VERSION() as version")->fetch();
    $info['mysql_version'] = $result['version'];
    
    // Database size
    $result = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                          FROM information_schema.TABLES 
                          WHERE table_schema = DATABASE()")->fetch();
    $info['size_mb'] = $result['size_mb'];
    
    return $info;
}

function getCompleteTableSchema($pdo, $tableName) {
    $schema = [
        'table_name' => $tableName,
        'columns' => getTableColumns($pdo, $tableName),
        'indexes' => getTableIndexes($pdo, $tableName),
        'foreign_keys' => getTableForeignKeys($pdo, $tableName),
        'table_info' => getTableInfo($pdo, $tableName),
        'constraints' => [] // getTableConstraints($pdo, $tableName) - temporarily disabled
    ];
    
    return $schema;
}

function getTableColumns($pdo, $tableName) {
    $sql = "SELECT 
                COLUMN_NAME,
                ORDINAL_POSITION,
                COLUMN_DEFAULT,
                IS_NULLABLE,
                DATA_TYPE,
                CHARACTER_MAXIMUM_LENGTH,
                NUMERIC_PRECISION,
                NUMERIC_SCALE,
                CHARACTER_SET_NAME,
                COLLATION_NAME,
                COLUMN_TYPE,
                COLUMN_KEY,
                EXTRA,
                COLUMN_COMMENT
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    
    return $stmt->fetchAll();
}

function getTableIndexes($pdo, $tableName) {
    $sql = "SELECT 
                INDEX_NAME,
                NON_UNIQUE,
                SEQ_IN_INDEX,
                COLUMN_NAME,
                COLLATION,
                CARDINALITY,
                SUB_PART,
                PACKED,
                NULLABLE,
                INDEX_TYPE,
                COMMENT
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    $indexes = $stmt->fetchAll();
    
    // Group by index name
    $groupedIndexes = [];
    foreach ($indexes as $index) {
        $indexName = $index['INDEX_NAME'];
        if (!isset($groupedIndexes[$indexName])) {
            $groupedIndexes[$indexName] = [
                'index_name' => $indexName,
                'non_unique' => $index['NON_UNIQUE'],
                'index_type' => $index['INDEX_TYPE'],
                'comment' => $index['COMMENT'],
                'columns' => []
            ];
        }
        $groupedIndexes[$indexName]['columns'][] = [
            'column_name' => $index['COLUMN_NAME'],
            'seq_in_index' => $index['SEQ_IN_INDEX'],
            'collation' => $index['COLLATION'],
            'cardinality' => $index['CARDINALITY'],
            'sub_part' => $index['SUB_PART']
        ];
    }
    
    return array_values($groupedIndexes);
}

function getTableForeignKeys($pdo, $tableName) {
    $sql = "SELECT 
                kcu.CONSTRAINT_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = DATABASE() 
            AND kcu.TABLE_NAME = ?
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    
    return $stmt->fetchAll();
}

function getTableInfo($pdo, $tableName) {
    $sql = "SELECT 
                ENGINE,
                ROW_FORMAT,
                TABLE_ROWS,
                AVG_ROW_LENGTH,
                DATA_LENGTH,
                MAX_DATA_LENGTH,
                INDEX_LENGTH,
                DATA_FREE,
                AUTO_INCREMENT,
                CREATE_TIME,
                UPDATE_TIME,
                CHECK_TIME,
                TABLE_COLLATION,
                CHECKSUM,
                CREATE_OPTIONS,
                TABLE_COMMENT
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    
    return $stmt->fetch();
}

function getTableConstraints($pdo, $tableName) {
    $sql = "SELECT 
                CONSTRAINT_NAME,
                CONSTRAINT_TYPE
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY CONSTRAINT_TYPE, CONSTRAINT_NAME";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    
    return $stmt->fetchAll();
}

function getTables($pdo) {
    $sql = "SELECT 
                TABLE_NAME,
                ENGINE,
                TABLE_ROWS,
                DATA_LENGTH,
                INDEX_LENGTH,
                CREATE_TIME,
                UPDATE_TIME,
                TABLE_COMMENT
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            ORDER BY TABLE_NAME";
    
    $tables = $pdo->query($sql)->fetchAll();
    
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'total_count' => count($tables)
    ]);
}

function getTableDetails($pdo) {
    $tableName = $_POST['table_name'] ?? $_GET['table_name'] ?? '';
    
    if (empty($tableName)) {
        throw new Exception('Table name is required');
    }
    
    $details = getCompleteTableSchema($pdo, $tableName);
    
    echo json_encode([
        'success' => true,
        'table_details' => $details
    ]);
}

function compareSchemas() {
    $schema1 = json_decode($_POST['schema1'] ?? '', true);
    $schema2 = json_decode($_POST['schema2'] ?? '', true);
    
    if (!$schema1 || !$schema2) {
        throw new Exception('Two valid schemas are required for comparison');
    }
    
    $comparison = [
        'summary' => [
            'schema1_info' => [
                'database' => $schema1['database_info']['database_name'] ?? 'Unknown',
                'tables' => count($schema1['tables'] ?? []),
                'extraction_time' => $schema1['extraction_time'] ?? 'Unknown'
            ],
            'schema2_info' => [
                'database' => $schema2['database_info']['database_name'] ?? 'Unknown',
                'tables' => count($schema2['tables'] ?? []),
                'extraction_time' => $schema2['extraction_time'] ?? 'Unknown'
            ]
        ],
        'differences' => [],
        'missing_tables' => [
            'in_schema1' => [],
            'in_schema2' => []
        ],
        'table_differences' => []
    ];
    
    $tables1 = array_keys($schema1['tables'] ?? []);
    $tables2 = array_keys($schema2['tables'] ?? []);
    
    // Find missing tables
    $comparison['missing_tables']['in_schema1'] = array_diff($tables2, $tables1);
    $comparison['missing_tables']['in_schema2'] = array_diff($tables1, $tables2);
    
    // Compare common tables
    $commonTables = array_intersect($tables1, $tables2);
    
    foreach ($commonTables as $tableName) {
        $tableDiff = compareTable(
            $schema1['tables'][$tableName],
            $schema2['tables'][$tableName],
            $tableName
        );
        
        if (!empty($tableDiff['differences'])) {
            $comparison['table_differences'][$tableName] = $tableDiff;
        }
    }
    
    echo json_encode([
        'success' => true,
        'comparison' => $comparison
    ]);
}

function compareTable($table1, $table2, $tableName) {
    $differences = [
        'table_name' => $tableName,
        'differences' => [],
        'column_differences' => [],
        'index_differences' => [],
        'foreign_key_differences' => []
    ];
    
    // Compare columns
    $columns1 = array_column($table1['columns'], null, 'COLUMN_NAME');
    $columns2 = array_column($table2['columns'], null, 'COLUMN_NAME');
    
    $columnNames1 = array_keys($columns1);
    $columnNames2 = array_keys($columns2);
    
    // Missing columns
    $missingInSchema1 = array_diff($columnNames2, $columnNames1);
    $missingInSchema2 = array_diff($columnNames1, $columnNames2);
    
    if (!empty($missingInSchema1)) {
        $differences['column_differences'][] = [
            'type' => 'missing_in_schema1',
            'columns' => $missingInSchema1
        ];
    }
    
    if (!empty($missingInSchema2)) {
        $differences['column_differences'][] = [
            'type' => 'missing_in_schema2',
            'columns' => $missingInSchema2
        ];
    }
    
    // Compare common columns
    $commonColumns = array_intersect($columnNames1, $columnNames2);
    foreach ($commonColumns as $columnName) {
        $col1 = $columns1[$columnName];
        $col2 = $columns2[$columnName];
        
        $columnDiff = [];
        $fieldsToCompare = ['DATA_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'COLUMN_TYPE', 'EXTRA'];
        
        foreach ($fieldsToCompare as $field) {
            if ($col1[$field] !== $col2[$field]) {
                $columnDiff[$field] = [
                    'schema1' => $col1[$field],
                    'schema2' => $col2[$field]
                ];
            }
        }
        
        if (!empty($columnDiff)) {
            $differences['column_differences'][] = [
                'type' => 'column_modified',
                'column_name' => $columnName,
                'differences' => $columnDiff
            ];
        }
    }
    
    // Compare indexes (simplified)
    $indexes1 = array_column($table1['indexes'], 'index_name');
    $indexes2 = array_column($table2['indexes'], 'index_name');
    
    $missingIndexes1 = array_diff($indexes2, $indexes1);
    $missingIndexes2 = array_diff($indexes1, $indexes2);
    
    if (!empty($missingIndexes1)) {
        $differences['index_differences'][] = [
            'type' => 'missing_in_schema1',
            'indexes' => $missingIndexes1
        ];
    }
    
    if (!empty($missingIndexes2)) {
        $differences['index_differences'][] = [
            'type' => 'missing_in_schema2',
            'indexes' => $missingIndexes2
        ];
    }
    
    return $differences;
}

function exportSchema($pdo) {
    $format = $_POST['format'] ?? 'json';
    
    // Get full schema
    ob_start();
    extractFullSchema($pdo);
    $schemaJson = ob_get_clean();
    $schemaData = json_decode($schemaJson, true);
    
    if (!$schemaData['success']) {
        throw new Exception('Failed to extract schema');
    }
    
    $schema = $schemaData['schema'];
    
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="schema_' . $schema['database_info']['database_name'] . '_' . date('Y-m-d_H-i-s') . '.json"');
            echo json_encode($schema, JSON_PRETTY_PRINT);
            break;
            
        case 'sql':
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="schema_' . $schema['database_info']['database_name'] . '_' . date('Y-m-d_H-i-s') . '.sql"');
            echo generateSQLSchema($schema);
            break;
            
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="schema_' . $schema['database_info']['database_name'] . '_' . date('Y-m-d_H-i-s') . '.csv"');
            echo generateCSVSchema($schema);
            break;
            
        default:
            throw new Exception('Invalid export format');
    }
    exit;
}

function generateSQLSchema($schema) {
    $sql = "-- Database Schema Export\n";
    $sql .= "-- Database: " . $schema['database_info']['database_name'] . "\n";
    $sql .= "-- Generated: " . $schema['extraction_time'] . "\n\n";
    
    foreach ($schema['tables'] as $tableName => $tableData) {
        $sql .= "-- Table: $tableName\n";
        $sql .= "-- Columns: " . count($tableData['columns']) . "\n";
        $sql .= "-- Indexes: " . count($tableData['indexes']) . "\n";
        $sql .= "-- Foreign Keys: " . count($tableData['foreign_keys']) . "\n\n";
        
        foreach ($tableData['columns'] as $column) {
            $sql .= sprintf("-- %s: %s %s %s\n",
                $column['COLUMN_NAME'],
                $column['COLUMN_TYPE'],
                $column['IS_NULLABLE'] === 'NO' ? 'NOT NULL' : 'NULL',
                $column['COLUMN_DEFAULT'] ? "DEFAULT '{$column['COLUMN_DEFAULT']}'" : ''
            );
        }
        $sql .= "\n";
    }
    
    return $sql;
}

function generateCSVSchema($schema) {
    $csv = "Table,Column,Position,Type,Nullable,Default,Key,Extra,Comment\n";
    
    foreach ($schema['tables'] as $tableName => $tableData) {
        foreach ($tableData['columns'] as $column) {
            $csv .= sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $tableName,
                $column['COLUMN_NAME'],
                $column['ORDINAL_POSITION'],
                $column['COLUMN_TYPE'],
                $column['IS_NULLABLE'],
                $column['COLUMN_DEFAULT'] ?? '',
                $column['COLUMN_KEY'],
                $column['EXTRA'],
                $column['COLUMN_COMMENT']
            );
        }
    }
    
    return $csv;
}
?>
