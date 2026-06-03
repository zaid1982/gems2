<?php
require_once 'api/class/Constant.php';

function parse_create_table($sql) {
    $tables = [];
    // Enhanced regex to handle common SQL variations
    preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=([^;]+);/s', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $tableName = $match[1];
        $content = $match[2];
        $options = $match[3];
        
        $tables[$tableName] = [
            'content' => $content,
            'options' => preg_replace('/AUTO_INCREMENT=\d+ ?/', '', $options)
        ];
        
        // Split by comma followed by newline, or comma followed by spaces and backtick
        $lines = preg_split('/,\n\s*|(?<=.),\s+(?=`)/', trim($content));
        $columns = [];
        $keys = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (strpos($line, 'PRIMARY KEY') === 0 || strpos($line, 'KEY') === 0 || strpos($line, 'CONSTRAINT') === 0 || strpos($line, 'UNIQUE KEY') === 0 || strpos($line, 'FULLTEXT KEY') === 0) {
                $keys[] = rtrim($line, ',');
            } else if (preg_match('/^`([^`]+)`/', $line, $colMatch)) {
                $columns[$colMatch[1]] = rtrim($line, ',');
            }
        }
        $tables[$tableName]['columns'] = $columns;
        $tables[$tableName]['keys'] = $keys;
    }
    return $tables;
}

function normalize_create_table($createTableSql) {
    if (preg_match('/CREATE TABLE `[^`]+` \((.*)\) ENGINE=(.*)/s', $createTableSql, $match)) {
        $content = $match[1];
        $options = $match[2];
        
        $lines = preg_split('/,\n\s*/', trim($content));
        $columns = [];
        $keys = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (strpos($line, 'PRIMARY KEY') === 0 || strpos($line, 'KEY') === 0 || strpos($line, 'CONSTRAINT') === 0 || strpos($line, 'UNIQUE KEY') === 0 || strpos($line, 'FULLTEXT KEY') === 0) {
                $keys[] = rtrim($line, ',');
            } else if (preg_match('/^`([^`]+)`/', $line, $colMatch)) {
                $columns[$colMatch[1]] = rtrim($line, ',');
            }
        }
        return [
            'columns' => $columns,
            'keys' => $keys,
            'options' => preg_replace('/AUTO_INCREMENT=\d+ ?/', '', $options)
        ];
    }
    return null;
}

$dumpFile = 'docs/jkr-db/gems_jkr_staging_prod.sql';
if (!file_exists($dumpFile)) {
    die("Dump file not found: $dumpFile\n");
}
$dumpSql = file_get_contents($dumpFile);
$dumpTables = parse_create_table($dumpSql);

$mysqli = new mysqli(Constant::$dbHost, Constant::$dbUserName, Constant::$dbUserPassword, Constant::$dbName);
if ($mysqli->connect_error) {
    die(json_encode(['error' => 'Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error]));
}

$currentTables = [];
$res = $mysqli->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_array()) {
        $tableName = $row[0];
        $res2 = $mysqli->query("SHOW CREATE TABLE `$tableName` ");
        if ($res2) {
            $row2 = $res2->fetch_assoc();
            $currentTables[$tableName] = normalize_create_table($row2['Create Table']);
        }
    }
}

$summary = [
    'dump_tables' => count($dumpTables),
    'current_tables' => count($currentTables),
    'missing_tables_in_dump' => 0,
    'extra_tables_in_dump' => 0,
    'tables_with_column_diffs' => 0,
    'missing_columns_in_dump' => 0,
    'changed_columns_in_dump' => 0,
    'extra_columns_in_dump' => 0,
    'tables_with_key_diffs' => 0,
    'missing_keys_in_dump' => 0,
    'changed_keys_in_dump' => 0,
    'extra_keys_in_dump' => 0,
    'table_option_diffs' => 0
];

$diffs = [];

// Tables in Dump but not in DB
foreach ($dumpTables as $name => $data) {
    if (!isset($currentTables[$name])) {
        $summary['extra_tables_in_dump']++;
        if (count($diffs) < 50) $diffs[] = "Table $name exists in dump but missing in DB";
    }
}

// Tables in DB (and comparison)
foreach ($currentTables as $name => $currData) {
    if (!isset($dumpTables[$name])) {
        $summary['missing_tables_in_dump']++;
        if (count($diffs) < 50) $diffs[] = "Table $name exists in DB but missing in dump";
        continue;
    }
    
    $dumpData = $dumpTables[$name];
    
    // Compare options (simple string compare after normalization)
    $currOpt = trim($currData['options']);
    $dumpOpt = trim($dumpData['options']);
    if ($currOpt !== $dumpOpt) {
        $summary['table_option_diffs']++;
        if (count($diffs) < 50) $diffs[] = "Table $name: Option diff. DB: $currOpt, Dump: $dumpOpt";
    }
    
    // Compare columns
    $colDiffFound = false;
    foreach ($currData['columns'] as $colName => $def) {
        if (!isset($dumpData['columns'][$colName])) {
            $summary['missing_columns_in_dump']++;
            $colDiffFound = true;
            if (count($diffs) < 50) $diffs[] = "Table $name: Column $colName missing in dump";
        } else if ($currData['columns'][$colName] !== $dumpData['columns'][$colName]) {
            $summary['changed_columns_in_dump']++;
            $colDiffFound = true;
            if (count($diffs) < 50) $diffs[] = "Table $name: Column $colName diff. DB: {$currData['columns'][$colName]}, Dump: {$dumpData['columns'][$colName]}";
        }
    }
    foreach ($dumpData['columns'] as $colName => $def) {
        if (!isset($currData['columns'][$colName])) {
            $summary['extra_columns_in_dump']++;
            $colDiffFound = true;
            if (count($diffs) < 50) $diffs[] = "Table $name: Column $colName exists in dump but not in DB";
        }
    }
    if ($colDiffFound) $summary['tables_with_column_diffs']++;
    
    // Compare keys
    $keyDiffFound = false;
    foreach ($currData['keys'] as $k) {
        if (!in_array($k, $dumpData['keys'])) {
            $summary['missing_keys_in_dump']++;
            $keyDiffFound = true;
            if (count($diffs) < 50) $diffs[] = "Table $name: Key '$k' missing or different in dump";
        }
    }
    foreach ($dumpData['keys'] as $k) {
        if (!in_array($k, $currData['keys'])) {
            $summary['extra_keys_in_dump']++;
            $keyDiffFound = true;
            if (count($diffs) < 50) $diffs[] = "Table $name: Key '$k' extra in dump";
        }
    }
    if ($keyDiffFound) $summary['tables_with_key_diffs']++;
}

echo json_encode($summary, JSON_PRETTY_PRINT) . "\n\n";
echo "Example differences (up to 50):\n";
foreach ($diffs as $d) echo "- $d\n";

