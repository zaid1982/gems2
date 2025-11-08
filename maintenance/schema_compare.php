<?php
/**
 * Database Schema Comparison Tool
 * Compares DEV vs PROD database schemas and generates migration SQL
 * 
 * Usage: Access via browser in maintenance mode
 */

// Prevent direct access in production
$maintenanceMode = true; // Set to false to disable
if (!$maintenanceMode) {
    die('Schema comparison tool is disabled. Enable maintenance mode first.');
}

// Database configurations
$databases = [
    'dev' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'gems2_dev', // Your dev database name
        'label' => 'Development'
    ],
    'prod' => [
        'host' => 'localhost', // Change to production host
        'user' => 'root',      // Change to production user
        'pass' => '',          // Change to production password
        'name' => 'gems2_prod', // Your production database name
        'label' => 'Production'
    ]
];

// Connect to both databases
function connectDb($config) {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);
    if ($conn->connect_error) {
        die("Connection failed to {$config['label']}: " . $conn->connect_error);
    }
    return $conn;
}

// Get all tables in database
function getTables($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Get table structure with detailed column information
function getTableStructure($conn, $table) {
    $structure = [];
    $result = $conn->query("SHOW FULL COLUMNS FROM `{$table}`");
    while ($row = $result->fetch_assoc()) {
        $structure[$row['Field']] = [
            'Type' => $row['Type'],
            'Collation' => $row['Collation'],
            'Null' => $row['Null'],
            'Key' => $row['Key'],
            'Default' => $row['Default'],
            'Extra' => $row['Extra'],
            'Comment' => $row['Comment']
        ];
    }
    return $structure;
}

// Get table indexes
function getTableIndexes($conn, $table) {
    $indexes = [];
    $result = $conn->query("SHOW INDEXES FROM `{$table}`");
    while ($row = $result->fetch_assoc()) {
        $keyName = $row['Key_name'];
        if (!isset($indexes[$keyName])) {
            $indexes[$keyName] = [
                'columns' => [],
                'unique' => $row['Non_unique'] == 0,
                'type' => $row['Index_type']
            ];
        }
        $indexes[$keyName]['columns'][] = $row['Column_name'];
    }
    return $indexes;
}

// Get foreign keys
function getForeignKeys($conn, $table, $database) {
    $fks = [];
    $sql = "SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                UPDATE_RULE,
                DELETE_RULE
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '{$database}'
                AND TABLE_NAME = '{$table}'
                AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $fks[$row['CONSTRAINT_NAME']] = [
            'column' => $row['COLUMN_NAME'],
            'ref_table' => $row['REFERENCED_TABLE_NAME'],
            'ref_column' => $row['REFERENCED_COLUMN_NAME']
        ];
    }
    return $fks;
}

// Compare two column definitions
function compareColumns($col1, $col2) {
    $diffs = [];
    
    if ($col1['Type'] !== $col2['Type']) {
        $diffs[] = "Type: {$col1['Type']} → {$col2['Type']}";
    }
    if ($col1['Null'] !== $col2['Null']) {
        $diffs[] = "Null: {$col1['Null']} → {$col2['Null']}";
    }
    if ($col1['Default'] !== $col2['Default']) {
        $d1 = $col1['Default'] === null ? 'NULL' : $col1['Default'];
        $d2 = $col2['Default'] === null ? 'NULL' : $col2['Default'];
        $diffs[] = "Default: {$d1} → {$d2}";
    }
    if ($col1['Extra'] !== $col2['Extra']) {
        $diffs[] = "Extra: {$col1['Extra']} → {$col2['Extra']}";
    }
    
    return $diffs;
}

// Generate SQL for column differences
function generateColumnSQL($table, $column, $devDef, $prodDef = null) {
    if ($prodDef === null) {
        // New column - ADD
        $null = $devDef['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = '';
        if ($devDef['Default'] !== null) {
            $default = " DEFAULT '{$devDef['Default']}'";
        } elseif ($devDef['Null'] === 'YES') {
            $default = " DEFAULT NULL";
        }
        $extra = $devDef['Extra'] ? " {$devDef['Extra']}" : '';
        $comment = $devDef['Comment'] ? " COMMENT '{$devDef['Comment']}'" : '';
        
        return "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$devDef['Type']} {$null}{$default}{$extra}{$comment};";
    } else {
        // Modified column - MODIFY
        $null = $devDef['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = '';
        if ($devDef['Default'] !== null) {
            $default = " DEFAULT '{$devDef['Default']}'";
        } elseif ($devDef['Null'] === 'YES') {
            $default = " DEFAULT NULL";
        }
        $extra = $devDef['Extra'] ? " {$devDef['Extra']}" : '';
        $comment = $devDef['Comment'] ? " COMMENT '{$devDef['Comment']}'" : '';
        
        return "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$devDef['Type']} {$null}{$default}{$extra}{$comment};";
    }
}

// Main comparison logic
$devConn = connectDb($databases['dev']);
$prodConn = connectDb($databases['prod']);

$devTables = getTables($devConn);
$prodTables = getTables($prodConn);

$report = [];
$sqlStatements = [];

// Find new tables (in dev but not in prod)
$newTables = array_diff($devTables, $prodTables);
foreach ($newTables as $table) {
    $report['new_tables'][] = $table;
    
    // Generate CREATE TABLE statement
    $result = $devConn->query("SHOW CREATE TABLE `{$table}`");
    $row = $result->fetch_assoc();
    $sqlStatements[] = $row['Create Table'] . ';';
}

// Find deleted tables (in prod but not in dev)
$deletedTables = array_diff($prodTables, $devTables);
foreach ($deletedTables as $table) {
    $report['deleted_tables'][] = $table;
    $sqlStatements[] = "-- WARNING: Table `{$table}` exists in PROD but not in DEV. Manual review required.";
    $sqlStatements[] = "-- DROP TABLE IF EXISTS `{$table}`;";
}

// Compare common tables
$commonTables = array_intersect($devTables, $prodTables);
foreach ($commonTables as $table) {
    $devStructure = getTableStructure($devConn, $table);
    $prodStructure = getTableStructure($prodConn, $table);
    
    $devIndexes = getTableIndexes($devConn, $table);
    $prodIndexes = getTableIndexes($prodConn, $table);
    
    $devFKs = getForeignKeys($devConn, $table, $databases['dev']['name']);
    $prodFKs = getForeignKeys($prodConn, $table, $databases['prod']['name']);
    
    $tableDiffs = [];
    
    // Compare columns
    $devColumns = array_keys($devStructure);
    $prodColumns = array_keys($prodStructure);
    
    // New columns
    $newColumns = array_diff($devColumns, $prodColumns);
    foreach ($newColumns as $column) {
        $tableDiffs['new_columns'][$column] = $devStructure[$column];
        $sqlStatements[] = generateColumnSQL($table, $column, $devStructure[$column]);
    }
    
    // Deleted columns
    $deletedColumns = array_diff($prodColumns, $devColumns);
    foreach ($deletedColumns as $column) {
        $tableDiffs['deleted_columns'][$column] = $prodStructure[$column];
        $sqlStatements[] = "-- WARNING: Column `{$table}`.`{$column}` exists in PROD but not in DEV.";
        $sqlStatements[] = "-- ALTER TABLE `{$table}` DROP COLUMN `{$column}`;";
    }
    
    // Modified columns
    $commonColumns = array_intersect($devColumns, $prodColumns);
    foreach ($commonColumns as $column) {
        $diffs = compareColumns($devStructure[$column], $prodStructure[$column]);
        if (!empty($diffs)) {
            $tableDiffs['modified_columns'][$column] = [
                'differences' => $diffs,
                'dev' => $devStructure[$column],
                'prod' => $prodStructure[$column]
            ];
            $sqlStatements[] = generateColumnSQL($table, $column, $devStructure[$column], $prodStructure[$column]);
        }
    }
    
    // Compare indexes
    $newIndexes = array_diff(array_keys($devIndexes), array_keys($prodIndexes));
    foreach ($newIndexes as $indexName) {
        if ($indexName === 'PRIMARY') continue; // Skip primary key for now
        $tableDiffs['new_indexes'][$indexName] = $devIndexes[$indexName];
        
        $columns = '`' . implode('`, `', $devIndexes[$indexName]['columns']) . '`';
        $unique = $devIndexes[$indexName]['unique'] ? 'UNIQUE ' : '';
        $sqlStatements[] = "ALTER TABLE `{$table}` ADD {$unique}INDEX `{$indexName}` ({$columns});";
    }
    
    // Compare foreign keys
    $newFKs = array_diff(array_keys($devFKs), array_keys($prodFKs));
    foreach ($newFKs as $fkName) {
        $fk = $devFKs[$fkName];
        $tableDiffs['new_foreign_keys'][$fkName] = $fk;
        
        $sqlStatements[] = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` " .
                          "FOREIGN KEY (`{$fk['column']}`) " .
                          "REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`);";
    }
    
    if (!empty($tableDiffs)) {
        $report['modified_tables'][$table] = $tableDiffs;
    }
}

$devConn->close();
$prodConn->close();

// Output HTML
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
        .subtitle { color: #7f8c8d; margin-bottom: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .summary-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-card h3 { font-size: 14px; color: #7f8c8d; margin-bottom: 10px; }
        .summary-card .number { font-size: 32px; font-weight: bold; }
        .summary-card.new .number { color: #27ae60; }
        .summary-card.modified .number { color: #f39c12; }
        .summary-card.deleted .number { color: #e74c3c; }
        
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        
        .table-list { list-style: none; }
        .table-list li { padding: 8px 0; border-bottom: 1px solid #ecf0f1; }
        .table-list li:last-child { border-bottom: none; }
        
        .table-details { margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #3498db; }
        .table-details h3 { color: #2c3e50; margin-bottom: 10px; }
        .table-details h4 { color: #34495e; margin: 15px 0 8px 0; font-size: 14px; }
        
        .column-diff { background: #fff; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 3px solid #f39c12; }
        .column-diff .col-name { font-weight: bold; color: #2c3e50; }
        .column-diff .diff-item { color: #7f8c8d; font-size: 13px; margin-left: 15px; }
        
        .new-item { border-left-color: #27ae60 !important; }
        .deleted-item { border-left-color: #e74c3c !important; background: #fee; }
        
        .sql-block { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 13px; margin-bottom: 20px; max-height: 500px; overflow-y: auto; }
        .sql-statement { margin: 8px 0; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; }
        .sql-comment { color: #95a5a6; }
        .sql-warning { color: #e74c3c; }
        
        .actions { margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-new { background: #d4edda; color: #155724; }
        .badge-modified { background: #fff3cd; color: #856404; }
        .badge-deleted { background: #f8d7da; color: #721c24; }
        
        textarea { width: 100%; min-height: 400px; font-family: 'Courier New', monospace; font-size: 13px; padding: 15px; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Schema Comparison</h1>
        <p class="subtitle">
            Comparing: <strong><?= $databases['dev']['label'] ?></strong> (<?= $databases['dev']['name'] ?>) 
            vs <strong><?= $databases['prod']['label'] ?></strong> (<?= $databases['prod']['name'] ?>)
        </p>
        
        <!-- Summary Cards -->
        <div class="summary">
            <div class="summary-card new">
                <h3>New Tables</h3>
                <div class="number"><?= count($newTables) ?></div>
            </div>
            <div class="summary-card modified">
                <h3>Modified Tables</h3>
                <div class="number"><?= count($report['modified_tables'] ?? []) ?></div>
            </div>
            <div class="summary-card deleted">
                <h3>Deleted Tables</h3>
                <div class="number"><?= count($deletedTables) ?></div>
            </div>
            <div class="summary-card">
                <h3>SQL Statements</h3>
                <div class="number" style="color: #3498db;"><?= count($sqlStatements) ?></div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="actions">
            <button class="btn btn-success" onclick="downloadSQL()">📥 Download SQL Script</button>
            <button class="btn" onclick="copySQL()">📋 Copy SQL to Clipboard</button>
            <a href="?refresh=1" class="btn">🔄 Refresh Comparison</a>
        </div>
        
        <!-- New Tables -->
        <?php if (!empty($newTables)): ?>
        <div class="section">
            <h2>✨ New Tables (<?= count($newTables) ?>)</h2>
            <p style="color: #7f8c8d; margin-bottom: 15px;">These tables exist in DEV but not in PROD</p>
            <ul class="table-list">
                <?php foreach ($newTables as $table): ?>
                <li><span class="badge badge-new">NEW</span> <strong><?= $table ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Deleted Tables -->
        <?php if (!empty($deletedTables)): ?>
        <div class="section">
            <h2>⚠️ Deleted Tables (<?= count($deletedTables) ?>)</h2>
            <p style="color: #e74c3c; margin-bottom: 15px;">⚠️ These tables exist in PROD but not in DEV - Manual review required!</p>
            <ul class="table-list">
                <?php foreach ($deletedTables as $table): ?>
                <li><span class="badge badge-deleted">DELETED</span> <strong><?= $table ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Modified Tables -->
        <?php if (!empty($report['modified_tables'])): ?>
        <div class="section">
            <h2>🔧 Modified Tables (<?= count($report['modified_tables']) ?>)</h2>
            <?php foreach ($report['modified_tables'] as $table => $diffs): ?>
            <div class="table-details">
                <h3>📋 <?= $table ?></h3>
                
                <?php if (!empty($diffs['new_columns'])): ?>
                <h4><span class="badge badge-new">NEW</span> New Columns (<?= count($diffs['new_columns']) ?>)</h4>
                <?php foreach ($diffs['new_columns'] as $col => $def): ?>
                <div class="column-diff new-item">
                    <div class="col-name"><?= $col ?></div>
                    <div class="diff-item">Type: <?= $def['Type'] ?> | Null: <?= $def['Null'] ?> | Default: <?= $def['Default'] ?? 'NULL' ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($diffs['deleted_columns'])): ?>
                <h4><span class="badge badge-deleted">DELETED</span> Deleted Columns (<?= count($diffs['deleted_columns']) ?>)</h4>
                <?php foreach ($diffs['deleted_columns'] as $col => $def): ?>
                <div class="column-diff deleted-item">
                    <div class="col-name"><?= $col ?></div>
                    <div class="diff-item">Type: <?= $def['Type'] ?> | Null: <?= $def['Null'] ?> | Default: <?= $def['Default'] ?? 'NULL' ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($diffs['modified_columns'])): ?>
                <h4><span class="badge badge-modified">MODIFIED</span> Modified Columns (<?= count($diffs['modified_columns']) ?>)</h4>
                <?php foreach ($diffs['modified_columns'] as $col => $info): ?>
                <div class="column-diff">
                    <div class="col-name"><?= $col ?></div>
                    <?php foreach ($info['differences'] as $diff): ?>
                    <div class="diff-item">• <?= $diff ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($diffs['new_indexes'])): ?>
                <h4><span class="badge badge-new">NEW</span> New Indexes (<?= count($diffs['new_indexes']) ?>)</h4>
                <?php foreach ($diffs['new_indexes'] as $idx => $info): ?>
                <div class="column-diff new-item">
                    <div class="col-name"><?= $idx ?></div>
                    <div class="diff-item">Columns: <?= implode(', ', $info['columns']) ?> | Unique: <?= $info['unique'] ? 'Yes' : 'No' ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($diffs['new_foreign_keys'])): ?>
                <h4><span class="badge badge-new">NEW</span> New Foreign Keys (<?= count($diffs['new_foreign_keys']) ?>)</h4>
                <?php foreach ($diffs['new_foreign_keys'] as $fk => $info): ?>
                <div class="column-diff new-item">
                    <div class="col-name"><?= $fk ?></div>
                    <div class="diff-item"><?= $info['column'] ?> → <?= $info['ref_table'] ?>(<?= $info['ref_column'] ?>)</div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Generated SQL -->
        <div class="section">
            <h2>📝 Generated SQL Statements</h2>
            <p style="color: #7f8c8d; margin-bottom: 15px;">
                Total: <?= count($sqlStatements) ?> statements | 
                Review carefully before executing on production!
            </p>
            <div class="sql-block" id="sqlBlock">
                <?php if (empty($sqlStatements)): ?>
                <div style="color: #27ae60;">✅ No schema differences found. Databases are in sync!</div>
                <?php else: ?>
                <?php foreach ($sqlStatements as $sql): ?>
                <div class="sql-statement <?= strpos($sql, '--') === 0 ? 'sql-comment' : '' ?> <?= strpos($sql, 'WARNING') !== false ? 'sql-warning' : '' ?>">
                    <?= htmlspecialchars($sql) ?>

                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <h3 style="margin-top: 20px;">Or edit SQL manually:</h3>
            <textarea id="sqlTextarea"><?php 
                echo "-- GEMS2 Database Migration Script\n";
                echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
                echo "-- Source: {$databases['dev']['name']} → Target: {$databases['prod']['name']}\n";
                echo "-- REVIEW CAREFULLY BEFORE EXECUTION!\n\n";
                foreach ($sqlStatements as $sql) {
                    echo $sql . "\n";
                }
            ?></textarea>
        </div>
    </div>
    
    <script>
        function copySQL() {
            const textarea = document.getElementById('sqlTextarea');
            textarea.select();
            document.execCommand('copy');
            alert('SQL copied to clipboard!');
        }
        
        function downloadSQL() {
            const textarea = document.getElementById('sqlTextarea');
            const blob = new Blob([textarea.value], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'schema_migration_' + new Date().toISOString().slice(0,10) + '.sql';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
