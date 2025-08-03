<?php
// Test script for the enhanced security system

// Include database configuration
require_once('../api/class/Constant.php');

/**
 * Standalone security validation function for testing
 */
function isQuerySafe($query) {
    $query = trim($query);
    
    // Remove comments and normalize whitespace
    $query = preg_replace('/--.*$/m', '', $query);
    $query = preg_replace('/\/\*.*?\*\//s', '', $query);
    $query = preg_replace('/\s+/', ' ', $query);
    $query = trim($query);
    
    // Convert to uppercase for checking
    $upperQuery = strtoupper($query);
    
    // Block dangerous operations
    $dangerousPatterns = [
        '/\bDROP\s+(DATABASE|SCHEMA)\b/',
        '/\bDROP\s+TABLE\b/',
        '/\bTRUNCATE\s+TABLE\b/',
        '/\bCREATE\s+USER\b/',
        '/\bGRANT\b/',
        '/\bREVOKE\b/',
        '/\bSHUTDOWN\b/',
        '/\bRESTART\b/',
        '/\bLOAD\s+DATA\b/',
        '/\bINTO\s+OUTFILE\b/',
        '/\bINTO\s+DUMPFILE\b/',
        '/\bEXEC\b/',
        '/\bEXECUTE\b/',
        '/\bCALL\b/',
        '/\bxp_cmdshell\b/',
        '/\bsp_\w+\b/'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $upperQuery)) {
            return false;
        }
    }
    
    // Check for multiple statements (basic check)
    if (substr_count($query, ';') > 1) {
        return false;
    }
    
    // For DELETE and UPDATE operations, ensure they have WHERE clauses
    if (preg_match('/^\s*(DELETE|UPDATE)\s+/i', $query)) {
        if (!preg_match('/\bWHERE\s+/i', $query)) {
            return false;
        }
    }
    
    // Basic SQL injection patterns
    $injectionPatterns = [
        '/\'\s*OR\s*\'\w*\'\s*=\s*\'\w*\'/',
        '/\'\s*OR\s*1\s*=\s*1/',
        '/\'\s*UNION\s+SELECT/',
        '/\'\s*;\s*DROP\s+TABLE/',
        '/\'\s*;\s*DELETE\s+FROM/',
        '/\'\s*;\s*INSERT\s+INTO/'
    ];
    
    foreach ($injectionPatterns as $pattern) {
        if (preg_match($pattern, $query)) {
            return false;
        }
    }
    
    return true;
}

echo "<h2>Testing Enhanced Security System</h2>";

// Test safe queries
$safeQueries = [
    "SELECT * FROM GEMS2..SysUser WHERE UserID = 1",
    "INSERT INTO test_table (name, value) VALUES ('test', 'value')",
    "UPDATE test_table SET value = 'new_value' WHERE id = 1",
    "DELETE FROM test_table WHERE id = 1",
    "ALTER TABLE test_table ADD COLUMN new_col VARCHAR(50)",
    "SHOW TABLES",
    "DESCRIBE SysUser"
];

// Test dangerous queries
$dangerousQueries = [
    "DROP DATABASE GEMS2",
    "DROP TABLE SysUser",
    "TRUNCATE TABLE SysUser",
    "DELETE FROM SysUser",  // No WHERE clause
    "UPDATE SysUser SET UserName = 'hacked'",  // No WHERE clause
    "INSERT INTO SysUser VALUES (999, 'hacker', 'password'); DROP TABLE SysUser;",  // Multiple statements
    "SELECT * FROM SysUser; DELETE FROM SysUser WHERE 1=1;",  // Multiple statements
    "EXEC xp_cmdshell('dir')",  // SQL injection attempt
    "SELECT * FROM SysUser WHERE 1=1 OR '1'='1'--"  // SQL injection attempt
];

echo "<h3>Testing Safe Queries (Should Pass):</h3>";
foreach ($safeQueries as $query) {
    $isSafe = isQuerySafe($query);
    $status = $isSafe ? "<span style='color: green;'>✓ SAFE</span>" : "<span style='color: red;'>✗ BLOCKED</span>";
    echo "<div><strong>Query:</strong> " . htmlspecialchars($query) . "</div>";
    echo "<div><strong>Status:</strong> $status</div><br>";
}

echo "<h3>Testing Dangerous Queries (Should Block):</h3>";
foreach ($dangerousQueries as $query) {
    $isSafe = isQuerySafe($query);
    $status = $isSafe ? "<span style='color: red;'>✗ ALLOWED (ERROR!)</span>" : "<span style='color: green;'>✓ BLOCKED</span>";
    echo "<div><strong>Query:</strong> " . htmlspecialchars($query) . "</div>";
    echo "<div><strong>Status:</strong> $status</div><br>";
}
?>
