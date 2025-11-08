<?php
/**
 * Database Schema Comparison Tool - JSON Version
 * Compares DEV vs PROD database schemas from JSON files
 * 
 * Usage: 
 * 1. Export schemas using extract_table_schemas.php
 * 2. Place JSON files in maintenance folder
 * 3. Access this tool via browser
 */

// Configuration
$devJsonFile = __DIR__ . '/schema_dev.json';
$prodJsonFile = __DIR__ . '/schema_prod.json';

// Check if files exist
$errors = [];
if (!file_exists($devJsonFile)) {
    $errors[] = "DEV schema file not found: {$devJsonFile}";
}
if (!file_exists($prodJsonFile)) {
    $errors[] = "PROD schema file not found: {$prodJsonFile}";
}

if (!empty($errors)) {
    ?>
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Schema Compare - Missing Files</title>
    <style>body{font-family:sans-serif;padding:40px;background:#f5f7fa;}
    .error{background:#fee;border-left:4px solid #e74c3c;padding:20px;margin:10px 0;border-radius:6px;}
    .info{background:#e8f4f8;border-left:4px solid #3498db;padding:20px;margin:10px 0;border-radius:6px;}
    code{background:#2c3e50;color:#ecf0f1;padding:2px 6px;border-radius:3px;}
    </style></head><body>
    <h1>❌ Missing Schema Files</h1>
    <?php foreach ($errors as $error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <div class="info">
        <h3>📝 How to generate schema JSON files:</h3>
        <ol>
            <li>Run <code>extract_table_schemas.php</code> on DEV database → save as <code>schema_dev.json</code></li>
            <li>Run <code>extract_table_schemas.php</code> on PROD database → save as <code>schema_prod.json</code></li>
            <li>Place both files in: <code><?= __DIR__ ?>/</code></li>
            <li>Refresh this page</li>
        </ol>
    </div>
    </body></html>
    <?php
    exit;
}

// Load JSON files
$devData = json_decode(file_get_contents($devJsonFile), true);
$prodData = json_decode(file_get_contents($prodJsonFile), true);

if (!$devData || !$prodData) {
    die('Error: Invalid JSON format in schema files');
}

// Extract table information
$devTables = $devData['tables'] ?? [];
$prodTables = $prodData['tables'] ?? [];

$devTableNames = array_keys($devTables);
$prodTableNames = array_keys($prodTables);

// Initialize report
$report = [
    'new_tables' => [],
    'deleted_tables' => [],
    'modified_tables' => [],
    'identical_tables' => []
];
$sqlStatements = [];

// Find new tables (in dev but not in prod)
$newTables = array_diff($devTableNames, $prodTableNames);
foreach ($newTables as $table) {
    $report['new_tables'][] = $table;
    $sqlStatements[] = "-- New table: {$table}";
    $sqlStatements[] = generateCreateTableSQL($table, $devTables[$table]);
    $sqlStatements[] = "";
}

// Find deleted tables (in prod but not in dev)
$deletedTables = array_diff($prodTableNames, $devTableNames);
foreach ($deletedTables as $table) {
    $report['deleted_tables'][] = $table;
    $sqlStatements[] = "-- WARNING: Table `{$table}` exists in PROD but not in DEV. Manual review required.";
    $sqlStatements[] = "-- DROP TABLE IF EXISTS `{$table}`;";
    $sqlStatements[] = "";
}

// Compare common tables
$commonTables = array_intersect($devTableNames, $prodTableNames);
foreach ($commonTables as $table) {
    $devTable = $devTables[$table];
    $prodTable = $prodTables[$table];
    
    $tableDiffs = compareTable($table, $devTable, $prodTable);
    
    if (!empty($tableDiffs['differences'])) {
        $report['modified_tables'][$table] = $tableDiffs;
        
        // Generate SQL for this table's changes
        foreach ($tableDiffs['sql'] as $sql) {
            $sqlStatements[] = $sql;
        }
        $sqlStatements[] = "";
    } else {
        $report['identical_tables'][] = $table;
    }
}

// Helper Functions

function compareTable($tableName, $devTable, $prodTable) {
    $differences = [];
    $sql = [];
    
    $devColumns = [];
    $prodColumns = [];
    
    // Build column maps
    foreach ($devTable['columns'] as $col) {
        $devColumns[$col['COLUMN_NAME']] = $col;
    }
    foreach ($prodTable['columns'] as $col) {
        $prodColumns[$col['COLUMN_NAME']] = $col;
    }
    
    $devColNames = array_keys($devColumns);
    $prodColNames = array_keys($prodColumns);
    
    // New columns
    $newCols = array_diff($devColNames, $prodColNames);
    foreach ($newCols as $colName) {
        $col = $devColumns[$colName];
        $differences['new_columns'][$colName] = $col;
        $sql[] = "-- Add column: {$tableName}.{$colName}";
        $sql[] = generateAddColumnSQL($tableName, $colName, $col);
    }
    
    // Deleted columns
    $deletedCols = array_diff($prodColNames, $devColNames);
    foreach ($deletedCols as $colName) {
        $col = $prodColumns[$colName];
        $differences['deleted_columns'][$colName] = $col;
        $sql[] = "-- WARNING: Column `{$tableName}`.`{$colName}` exists in PROD but not in DEV.";
        $sql[] = "-- ALTER TABLE `{$tableName}` DROP COLUMN `{$colName}`;";
    }
    
    // Modified columns
    $commonCols = array_intersect($devColNames, $prodColNames);
    foreach ($commonCols as $colName) {
        $devCol = $devColumns[$colName];
        $prodCol = $prodColumns[$colName];
        
        $colDiffs = compareColumn($devCol, $prodCol);
        if (!empty($colDiffs)) {
            $differences['modified_columns'][$colName] = [
                'differences' => $colDiffs,
                'dev' => $devCol,
                'prod' => $prodCol
            ];
            $sql[] = "-- Modify column: {$tableName}.{$colName}";
            $sql[] = generateModifyColumnSQL($tableName, $colName, $devCol);
        }
    }
    
    // Compare indexes
    $devIndexes = buildIndexMap($devTable['indexes'] ?? []);
    $prodIndexes = buildIndexMap($prodTable['indexes'] ?? []);
    
    $newIndexes = array_diff(array_keys($devIndexes), array_keys($prodIndexes));
    foreach ($newIndexes as $idxName) {
        if ($idxName === 'PRIMARY') continue; // Handle primary keys separately
        $differences['new_indexes'][$idxName] = $devIndexes[$idxName];
        $sql[] = generateAddIndexSQL($tableName, $idxName, $devIndexes[$idxName]);
    }
    
    // Compare foreign keys
    $devFKs = buildFKMap($devTable['foreign_keys'] ?? []);
    $prodFKs = buildFKMap($prodTable['foreign_keys'] ?? []);
    
    $newFKs = array_diff(array_keys($devFKs), array_keys($prodFKs));
    foreach ($newFKs as $fkName) {
        $fk = $devFKs[$fkName];
        $differences['new_foreign_keys'][$fkName] = $fk;
        $sql[] = generateAddFKSQL($tableName, $fkName, $fk);
    }
    
    return [
        'differences' => $differences,
        'sql' => $sql
    ];
}

function compareColumn($devCol, $prodCol) {
    $diffs = [];
    
    // Compare type
    if (normalizeType($devCol['COLUMN_TYPE']) !== normalizeType($prodCol['COLUMN_TYPE'])) {
        $diffs[] = "Type: {$prodCol['COLUMN_TYPE']} → {$devCol['COLUMN_TYPE']}";
    }
    
    // Compare nullable
    if ($devCol['IS_NULLABLE'] !== $prodCol['IS_NULLABLE']) {
        $diffs[] = "Nullable: {$prodCol['IS_NULLABLE']} → {$devCol['IS_NULLABLE']}";
    }
    
    // Compare default
    $devDefault = $devCol['COLUMN_DEFAULT'];
    $prodDefault = $prodCol['COLUMN_DEFAULT'];
    if ($devDefault !== $prodDefault) {
        $d1 = $prodDefault === null ? 'NULL' : $prodDefault;
        $d2 = $devDefault === null ? 'NULL' : $devDefault;
        $diffs[] = "Default: {$d1} → {$d2}";
    }
    
    // Compare extra (auto_increment, etc.)
    if (($devCol['EXTRA'] ?? '') !== ($prodCol['EXTRA'] ?? '')) {
        $diffs[] = "Extra: {$prodCol['EXTRA']} → {$devCol['EXTRA']}";
    }
    
    // Compare character set
    if (isset($devCol['CHARACTER_SET_NAME']) && isset($prodCol['CHARACTER_SET_NAME'])) {
        if ($devCol['CHARACTER_SET_NAME'] !== $prodCol['CHARACTER_SET_NAME']) {
            $diffs[] = "Charset: {$prodCol['CHARACTER_SET_NAME']} → {$devCol['CHARACTER_SET_NAME']}";
        }
    }
    
    // Compare collation
    if (isset($devCol['COLLATION_NAME']) && isset($prodCol['COLLATION_NAME'])) {
        if ($devCol['COLLATION_NAME'] !== $prodCol['COLLATION_NAME']) {
            $diffs[] = "Collation: {$prodCol['COLLATION_NAME']} → {$devCol['COLLATION_NAME']}";
        }
    }
    
    return $diffs;
}

function normalizeType($type) {
    // Normalize type for comparison (handle synonyms)
    $type = strtolower(trim($type));
    $type = str_replace('integer', 'int', $type);
    return $type;
}

function buildIndexMap($indexes) {
    $map = [];
    foreach ($indexes as $idx) {
        $name = $idx['INDEX_NAME'] ?? $idx['Key_name'] ?? '';
        if (empty($name)) continue;
        
        if (!isset($map[$name])) {
            $map[$name] = [
                'columns' => [],
                'unique' => ($idx['NON_UNIQUE'] ?? $idx['Non_unique'] ?? 1) == 0,
                'type' => $idx['INDEX_TYPE'] ?? $idx['Index_type'] ?? 'BTREE'
            ];
        }
        $map[$name]['columns'][] = $idx['COLUMN_NAME'] ?? $idx['Column_name'] ?? '';
    }
    return $map;
}

function buildFKMap($fks) {
    $map = [];
    foreach ($fks as $fk) {
        $name = $fk['CONSTRAINT_NAME'] ?? '';
        if (empty($name) || $name === 'PRIMARY') continue;
        
        $map[$name] = [
            'column' => $fk['COLUMN_NAME'] ?? '',
            'ref_table' => $fk['REFERENCED_TABLE_NAME'] ?? '',
            'ref_column' => $fk['REFERENCED_COLUMN_NAME'] ?? '',
            'update_rule' => $fk['UPDATE_RULE'] ?? 'RESTRICT',
            'delete_rule' => $fk['DELETE_RULE'] ?? 'RESTRICT'
        ];
    }
    return $map;
}

function generateCreateTableSQL($tableName, $tableData) {
    $sql = "CREATE TABLE `{$tableName}` (\n";
    $columnDefs = [];
    $keys = [];
    
    foreach ($tableData['columns'] as $col) {
        $columnDefs[] = '  ' . buildColumnDefinition($col);
    }
    
    // Add indexes
    foreach ($tableData['indexes'] ?? [] as $idx) {
        $idxName = $idx['INDEX_NAME'] ?? $idx['Key_name'] ?? '';
        if ($idxName === 'PRIMARY') {
            $colName = $idx['COLUMN_NAME'] ?? $idx['Column_name'] ?? '';
            $keys[] = "  PRIMARY KEY (`{$colName}`)";
        }
    }
    
    $sql .= implode(",\n", array_merge($columnDefs, $keys));
    
    $engine = $tableData['table_info']['ENGINE'] ?? 'InnoDB';
    $charset = $tableData['table_info']['TABLE_COLLATION'] ?? 'utf8mb4_general_ci';
    
    $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET=" . explode('_', $charset)[0] . " COLLATE={$charset};";
    
    return $sql;
}

function buildColumnDefinition($col) {
    $name = $col['COLUMN_NAME'];
    $type = $col['COLUMN_TYPE'];
    $null = $col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
    
    $def = "`{$name}` {$type} {$null}";
    
    // Add default
    if ($col['COLUMN_DEFAULT'] !== null) {
        $default = $col['COLUMN_DEFAULT'];
        if (strtoupper($default) === 'CURRENT_TIMESTAMP' || strtoupper($default) === 'NULL') {
            $def .= " DEFAULT {$default}";
        } else {
            $def .= " DEFAULT '{$default}'";
        }
    } elseif ($col['IS_NULLABLE'] === 'YES' && !($col['EXTRA'] ?? '')) {
        $def .= " DEFAULT NULL";
    }
    
    // Add extra (auto_increment, etc.)
    if (!empty($col['EXTRA'])) {
        $def .= " " . strtoupper($col['EXTRA']);
    }
    
    // Add comment
    if (!empty($col['COLUMN_COMMENT'])) {
        $comment = str_replace("'", "''", $col['COLUMN_COMMENT']);
        $def .= " COMMENT '{$comment}'";
    }
    
    return $def;
}

function generateAddColumnSQL($table, $colName, $col) {
    $def = buildColumnDefinition($col);
    return "ALTER TABLE `{$table}` ADD COLUMN {$def};";
}

function generateModifyColumnSQL($table, $colName, $col) {
    $def = buildColumnDefinition($col);
    return "ALTER TABLE `{$table}` MODIFY COLUMN {$def};";
}

function generateAddIndexSQL($table, $indexName, $index) {
    $columns = '`' . implode('`, `', $index['columns']) . '`';
    $unique = $index['unique'] ? 'UNIQUE ' : '';
    return "ALTER TABLE `{$table}` ADD {$unique}INDEX `{$indexName}` ({$columns});";
}

function generateAddFKSQL($table, $fkName, $fk) {
    $sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` ";
    $sql .= "FOREIGN KEY (`{$fk['column']}`) ";
    $sql .= "REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`)";
    
    if (isset($fk['update_rule']) && $fk['update_rule'] !== 'RESTRICT') {
        $sql .= " ON UPDATE {$fk['update_rule']}";
    }
    if (isset($fk['delete_rule']) && $fk['delete_rule'] !== 'RESTRICT') {
        $sql .= " ON DELETE {$fk['delete_rule']}";
    }
    
    return $sql . ";";
}

// Calculate statistics
$stats = [
    'new_tables' => count($report['new_tables']),
    'deleted_tables' => count($report['deleted_tables']),
    'modified_tables' => count($report['modified_tables']),
    'identical_tables' => count($report['identical_tables']),
    'total_dev_tables' => count($devTableNames),
    'total_prod_tables' => count($prodTableNames),
    'sql_statements' => count($sqlStatements)
];

// Count detailed changes
$totalNewColumns = 0;
$totalModifiedColumns = 0;
$totalDeletedColumns = 0;
$totalNewIndexes = 0;
$totalNewFKs = 0;

foreach ($report['modified_tables'] as $tableDiffs) {
    $totalNewColumns += count($tableDiffs['differences']['new_columns'] ?? []);
    $totalModifiedColumns += count($tableDiffs['differences']['modified_columns'] ?? []);
    $totalDeletedColumns += count($tableDiffs['differences']['deleted_columns'] ?? []);
    $totalNewIndexes += count($tableDiffs['differences']['new_indexes'] ?? []);
    $totalNewFKs += count($tableDiffs['differences']['new_foreign_keys'] ?? []);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Comparison - GEMS2</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        .subtitle { color: #7f8c8d; margin-bottom: 30px; font-size: 14px; }
        .file-info { background: #e8f4f8; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        .file-info strong { color: #2c3e50; }
        
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .summary-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-card h3 { font-size: 12px; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase; }
        .summary-card .number { font-size: 32px; font-weight: bold; }
        .summary-card.new .number { color: #27ae60; }
        .summary-card.modified .number { color: #f39c12; }
        .summary-card.deleted .number { color: #e74c3c; }
        .summary-card.info .number { color: #3498db; }
        .summary-card.success .number { color: #16a085; }
        
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        
        .table-list { list-style: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px; }
        .table-list li { padding: 8px 12px; border-radius: 4px; background: #f8f9fa; border-left: 3px solid #3498db; }
        
        .table-details { margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #3498db; }
        .table-details h3 { color: #2c3e50; margin-bottom: 10px; font-size: 18px; }
        .table-details h4 { color: #34495e; margin: 15px 0 8px 0; font-size: 14px; }
        
        .column-diff { background: #fff; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 3px solid #f39c12; font-size: 13px; }
        .column-diff .col-name { font-weight: bold; color: #2c3e50; margin-bottom: 4px; }
        .column-diff .col-type { color: #7f8c8d; font-family: 'Courier New', monospace; font-size: 12px; }
        .column-diff .diff-list { margin-top: 6px; }
        .column-diff .diff-item { color: #e67e22; font-size: 12px; margin-left: 15px; }
        
        .new-item { border-left-color: #27ae60 !important; }
        .deleted-item { border-left-color: #e74c3c !important; background: #fee; }
        
        .sql-block { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 13px; margin-bottom: 20px; max-height: 600px; overflow-y: auto; }
        .sql-statement { margin: 8px 0; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; }
        .sql-comment { color: #95a5a6; }
        .sql-warning { color: #e74c3c; font-weight: bold; }
        
        .actions { margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 5px; }
        .badge-new { background: #d4edda; color: #155724; }
        .badge-modified { background: #fff3cd; color: #856404; }
        .badge-deleted { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        textarea { width: 100%; min-height: 400px; font-family: 'Courier New', monospace; font-size: 13px; padding: 15px; border: 1px solid #ddd; border-radius: 6px; }
        
        .no-changes { text-align: center; padding: 40px; color: #27ae60; }
        .no-changes i { font-size: 48px; margin-bottom: 15px; }
        
        .detail-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0; }
        .detail-stat { background: #ecf0f1; padding: 10px; border-radius: 4px; text-align: center; }
        .detail-stat .label { font-size: 11px; color: #7f8c8d; text-transform: uppercase; }
        .detail-stat .value { font-size: 20px; font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Schema Comparison (JSON Mode)</h1>
        <p class="subtitle">
            Comparing: <strong>DEV</strong> vs <strong>PROD</strong> schemas from JSON files
        </p>
        
        <div class="file-info">
            <strong>📁 Source Files:</strong><br>
            DEV: <?= basename($devJsonFile) ?> (<?= number_format(filesize($devJsonFile)) ?> bytes, <?= date('Y-m-d H:i:s', filemtime($devJsonFile)) ?>)<br>
            PROD: <?= basename($prodJsonFile) ?> (<?= number_format(filesize($prodJsonFile)) ?> bytes, <?= date('Y-m-d H:i:s', filemtime($prodJsonFile)) ?>)
        </div>
        
        <!-- Summary Cards -->
        <div class="summary">
            <div class="summary-card new">
                <h3>New Tables</h3>
                <div class="number"><?= $stats['new_tables'] ?></div>
            </div>
            <div class="summary-card modified">
                <h3>Modified Tables</h3>
                <div class="number"><?= $stats['modified_tables'] ?></div>
            </div>
            <div class="summary-card deleted">
                <h3>Deleted Tables</h3>
                <div class="number"><?= $stats['deleted_tables'] ?></div>
            </div>
            <div class="summary-card success">
                <h3>Identical Tables</h3>
                <div class="number"><?= $stats['identical_tables'] ?></div>
            </div>
            <div class="summary-card info">
                <h3>DEV Tables</h3>
                <div class="number"><?= $stats['total_dev_tables'] ?></div>
            </div>
            <div class="summary-card info">
                <h3>PROD Tables</h3>
                <div class="number"><?= $stats['total_prod_tables'] ?></div>
            </div>
        </div>
        
        <!-- Detailed Statistics -->
        <div class="section">
            <h2>📊 Detailed Changes Summary</h2>
            <div class="detail-stats">
                <div class="detail-stat">
                    <div class="label">New Columns</div>
                    <div class="value" style="color: #27ae60;"><?= $totalNewColumns ?></div>
                </div>
                <div class="detail-stat">
                    <div class="label">Modified Columns</div>
                    <div class="value" style="color: #f39c12;"><?= $totalModifiedColumns ?></div>
                </div>
                <div class="detail-stat">
                    <div class="label">Deleted Columns</div>
                    <div class="value" style="color: #e74c3c;"><?= $totalDeletedColumns ?></div>
                </div>
                <div class="detail-stat">
                    <div class="label">New Indexes</div>
                    <div class="value" style="color: #3498db;"><?= $totalNewIndexes ?></div>
                </div>
                <div class="detail-stat">
                    <div class="label">New Foreign Keys</div>
                    <div class="value" style="color: #9b59b6;"><?= $totalNewFKs ?></div>
                </div>
                <div class="detail-stat">
                    <div class="label">SQL Statements</div>
                    <div class="value" style="color: #34495e;"><?= $stats['sql_statements'] ?></div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <?php if (!empty($sqlStatements)): ?>
        <div class="actions">
            <button class="btn btn-success" onclick="downloadSQL()">📥 Download SQL Script</button>
            <button class="btn" onclick="copySQL()">📋 Copy SQL to Clipboard</button>
            <a href="?" class="btn">🔄 Refresh Comparison</a>
        </div>
        <?php endif; ?>
        
        <!-- New Tables -->
        <?php if (!empty($report['new_tables'])): ?>
        <div class="section">
            <h2>✨ New Tables (<?= count($report['new_tables']) ?>)</h2>
            <p style="color: #7f8c8d; margin-bottom: 15px;">These tables exist in DEV but not in PROD</p>
            <ul class="table-list">
                <?php foreach ($report['new_tables'] as $table): ?>
                <li><span class="badge badge-new">NEW</span> <strong><?= htmlspecialchars($table) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Deleted Tables -->
        <?php if (!empty($report['deleted_tables'])): ?>
        <div class="section">
            <h2>⚠️ Deleted Tables (<?= count($report['deleted_tables']) ?>)</h2>
            <p style="color: #e74c3c; margin-bottom: 15px;">⚠️ These tables exist in PROD but not in DEV - Manual review required!</p>
            <ul class="table-list">
                <?php foreach ($report['deleted_tables'] as $table): ?>
                <li style="border-left-color: #e74c3c;"><span class="badge badge-deleted">DELETED</span> <strong><?= htmlspecialchars($table) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Modified Tables -->
        <?php if (!empty($report['modified_tables'])): ?>
        <div class="section">
            <h2>🔧 Modified Tables (<?= count($report['modified_tables']) ?>)</h2>
            <?php foreach ($report['modified_tables'] as $table => $tableDiffs): ?>
            <div class="table-details">
                <h3>📋 <?= htmlspecialchars($table) ?></h3>
                
                <?php if (!empty($tableDiffs['differences']['new_columns'])): ?>
                <h4><span class="badge badge-new">NEW</span> New Columns (<?= count($tableDiffs['differences']['new_columns']) ?>)</h4>
                <?php foreach ($tableDiffs['differences']['new_columns'] as $colName => $col): ?>
                <div class="column-diff new-item">
                    <div class="col-name"><?= htmlspecialchars($colName) ?></div>
                    <div class="col-type">
                        Type: <?= htmlspecialchars($col['COLUMN_TYPE']) ?> | 
                        Nullable: <?= $col['IS_NULLABLE'] ?> | 
                        Default: <?= $col['COLUMN_DEFAULT'] ?? 'NULL' ?>
                        <?php if ($col['EXTRA']): ?>| Extra: <?= htmlspecialchars($col['EXTRA']) ?><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($tableDiffs['differences']['deleted_columns'])): ?>
                <h4><span class="badge badge-deleted">DELETED</span> Deleted Columns (<?= count($tableDiffs['differences']['deleted_columns']) ?>)</h4>
                <?php foreach ($tableDiffs['differences']['deleted_columns'] as $colName => $col): ?>
                <div class="column-diff deleted-item">
                    <div class="col-name"><?= htmlspecialchars($colName) ?></div>
                    <div class="col-type">
                        Type: <?= htmlspecialchars($col['COLUMN_TYPE']) ?> | 
                        Nullable: <?= $col['IS_NULLABLE'] ?> | 
                        Default: <?= $col['COLUMN_DEFAULT'] ?? 'NULL' ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($tableDiffs['differences']['modified_columns'])): ?>
                <h4><span class="badge badge-modified">MODIFIED</span> Modified Columns (<?= count($tableDiffs['differences']['modified_columns']) ?>)</h4>
                <?php foreach ($tableDiffs['differences']['modified_columns'] as $colName => $info): ?>
                <div class="column-diff">
                    <div class="col-name"><?= htmlspecialchars($colName) ?></div>
                    <div class="diff-list">
                        <?php foreach ($info['differences'] as $diff): ?>
                        <div class="diff-item">• <?= htmlspecialchars($diff) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($tableDiffs['differences']['new_indexes'])): ?>
                <h4><span class="badge badge-new">NEW</span> New Indexes (<?= count($tableDiffs['differences']['new_indexes']) ?>)</h4>
                <?php foreach ($tableDiffs['differences']['new_indexes'] as $idxName => $idx): ?>
                <div class="column-diff new-item">
                    <div class="col-name"><?= htmlspecialchars($idxName) ?></div>
                    <div class="col-type">
                        Columns: <?= htmlspecialchars(implode(', ', $idx['columns'])) ?> | 
                        Unique: <?= $idx['unique'] ? 'Yes' : 'No' ?> | 
                        Type: <?= htmlspecialchars($idx['type']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($tableDiffs['differences']['new_foreign_keys'])): ?>
                <h4><span class="badge badge-new">NEW</span> New Foreign Keys (<?= count($tableDiffs['differences']['new_foreign_keys']) ?>)</h4>
                <?php foreach ($tableDiffs['differences']['new_foreign_keys'] as $fkName => $fk): ?>
                <div class="column-diff new-item">
                    <div class="col-name"><?= htmlspecialchars($fkName) ?></div>
                    <div class="col-type">
                        <?= htmlspecialchars($fk['column']) ?> → 
                        <?= htmlspecialchars($fk['ref_table']) ?>(<?= htmlspecialchars($fk['ref_column']) ?>)
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- No Changes Message -->
        <?php if (empty($sqlStatements)): ?>
        <div class="section">
            <div class="no-changes">
                <div style="font-size: 48px; margin-bottom: 15px;">✅</div>
                <h2>No Schema Differences Found!</h2>
                <p>DEV and PROD databases are in perfect sync.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Generated SQL -->
        <?php if (!empty($sqlStatements)): ?>
        <div class="section">
            <h2>📝 Generated SQL Migration Script</h2>
            <p style="color: #7f8c8d; margin-bottom: 15px;">
                Total: <?= count($sqlStatements) ?> statements | 
                ⚠️ Review carefully before executing on production!
            </p>
            
            <h3 style="margin-top: 20px; margin-bottom: 10px;">SQL Script:</h3>
            <textarea id="sqlTextarea"><?php 
                echo "-- GEMS2 Database Migration Script\n";
                echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
                echo "-- Source: DEV → Target: PROD\n";
                echo "-- DEV Tables: {$stats['total_dev_tables']} | PROD Tables: {$stats['total_prod_tables']}\n";
                echo "-- Changes: {$stats['new_tables']} new, {$stats['modified_tables']} modified, {$stats['deleted_tables']} deleted\n";
                echo "-- ⚠️ REVIEW CAREFULLY BEFORE EXECUTION!\n";
                echo "-- ⚠️ BACKUP YOUR DATABASE FIRST!\n\n";
                
                foreach ($sqlStatements as $sql) {
                    echo $sql . "\n";
                }
            ?></textarea>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copySQL() {
            const textarea = document.getElementById('sqlTextarea');
            if (textarea) {
                textarea.select();
                document.execCommand('copy');
                alert('✅ SQL copied to clipboard!');
            }
        }
        
        function downloadSQL() {
            const textarea = document.getElementById('sqlTextarea');
            if (textarea) {
                const blob = new Blob([textarea.value], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'schema_migration_' + new Date().toISOString().slice(0,10) + '_' + new Date().getTime() + '.sql';
                a.click();
                window.URL.revokeObjectURL(url);
            }
        }
    </script>
</body>
</html>
