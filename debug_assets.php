<?php
// Debug Assets - Check what assets are available in the database
require_once 'api/class/Constant.php';
require_once 'api/class/General.php';
require_once 'api/class/DbMysql.php';

echo "<h2>Asset Database Diagnostic</h2>";
echo "<pre>";

try {
    DbMysql::connect();
    echo "Database connection: OK\n\n";
    
    // Check if ast_asset table exists
    $tableCheck = DbMysql::selectSqlAll("SHOW TABLES LIKE 'ast_asset'");
    if (empty($tableCheck)) {
        echo "ERROR: ast_asset table does not exist!\n";
        
        // Show available tables
        $tables = DbMysql::selectSqlAll("SHOW TABLES");
        echo "Available tables:\n";
        foreach ($tables as $table) {
            echo "- " . array_values($table)[0] . "\n";
        }
        exit;
    }
    
    echo "ast_asset table: EXISTS\n\n";
    
    // Check table structure
    echo "=== TABLE STRUCTURE ===\n";
    $structure = DbMysql::selectSqlAll("DESCRIBE ast_asset");
    foreach ($structure as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")" . ($col['Key'] ? " [" . $col['Key'] . "]" : "") . "\n";
    }
    echo "\n";
    
    // Count total assets
    $countResult = DbMysql::selectSqlAll("SELECT COUNT(*) as total FROM ast_asset");
    $totalAssets = $countResult[0]['total'];
    echo "=== ASSET COUNT ===\n";
    echo "Total assets in database: $totalAssets\n\n";
    
    if ($totalAssets > 0) {
        // Show first 20 assets
        echo "=== FIRST 20 ASSETS ===\n";
        $assets = DbMysql::selectSqlAll("SELECT asset_no, asset_name, asset_id FROM ast_asset ORDER BY asset_no LIMIT 20");
        
        echo "Asset No. | Asset Name | Asset ID\n";
        echo "----------|------------|----------\n";
        foreach ($assets as $asset) {
            printf("%-9s | %-10s | %s\n", 
                $asset['asset_no'] ?? 'NULL', 
                substr($asset['asset_name'] ?? 'NULL', 0, 10), 
                $asset['asset_id'] ?? 'NULL'
            );
        }
        echo "\n";
        
        // Check for your specific asset codes
        echo "=== SEARCHING FOR YOUR ASSETS ===\n";
        $searchAssets = ['APDM01', 'IT001', 'IT0010', 'IT002', 'IT003', 'IT004', 'IT005'];
        
        foreach ($searchAssets as $assetCode) {
            $result = DbMysql::select('ast_asset', ['asset_no' => $assetCode]);
            if (!empty($result)) {
                echo "✅ FOUND: $assetCode - " . $result['asset_name'] . " (ID: " . $result['asset_id'] . ")\n";
            } else {
                echo "❌ NOT FOUND: $assetCode\n";
            }
        }
        echo "\n";
        
        // Search for similar patterns
        echo "=== PATTERN MATCHING ===\n";
        $patterns = [
            'APDM' => "SELECT asset_no, asset_name FROM ast_asset WHERE asset_no LIKE 'APDM%' LIMIT 5",
            'IT' => "SELECT asset_no, asset_name FROM ast_asset WHERE asset_no LIKE 'IT%' LIMIT 5"
        ];
        
        foreach ($patterns as $pattern => $sql) {
            $result = DbMysql::selectSqlAll($sql);
            echo "Pattern '$pattern%' matches:\n";
            if (!empty($result)) {
                foreach ($result as $asset) {
                    echo "  - " . $asset['asset_no'] . " (" . $asset['asset_name'] . ")\n";
                }
            } else {
                echo "  No matches found\n";
            }
        }
    } else {
        echo "No assets found in database. The table is empty.\n";
        
        // Check if there are other asset-related tables
        echo "\n=== CHECKING FOR OTHER ASSET TABLES ===\n";
        $tables = DbMysql::selectSqlAll("SHOW TABLES LIKE '%asset%'");
        if (!empty($tables)) {
            echo "Found asset-related tables:\n";
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $count = DbMysql::selectSqlAll("SELECT COUNT(*) as total FROM $tableName")[0]['total'];
                echo "- $tableName ($count records)\n";
            }
        } else {
            echo "No other asset-related tables found.\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
