<?php
// Simple Asset Database Check
require_once 'api/class/Constant.php';
require_once 'api/class/DbMysql.php';

echo "<h2>Simple Asset Check</h2>";
echo "<pre>";

try {
    DbMysql::connect();
    echo "Database connection: OK\n\n";
    
    // Count total assets
    $countResult = DbMysql::selectSqlAll("SELECT COUNT(*) as total FROM ast_asset");
    $totalAssets = $countResult[0]['total'];
    echo "=== ASSET COUNT ===\n";
    echo "Total assets in database: $totalAssets\n\n";
    
    if ($totalAssets > 0) {
        // Check table structure first
        echo "=== TABLE STRUCTURE ===\n";
        $structure = DbMysql::selectSqlAll("DESCRIBE ast_asset");
        foreach ($structure as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ")" . ($col['Key'] ? " [" . $col['Key'] . "]" : "") . "\n";
        }
        echo "\n";
        
        // Show first 5 assets with ALL columns
        echo "=== FIRST 5 ASSETS (ALL COLUMNS) ===\n";
        $assets = DbMysql::selectSqlAll("SELECT * FROM ast_asset ORDER BY asset_id LIMIT 5");
        
        foreach ($assets as $i => $asset) {
            echo "Asset $i:\n";
            foreach ($asset as $key => $value) {
                echo "  $key: " . ($value ?? 'NULL') . "\n";
            }
            echo "\n";
        }
        
        // Try different ways to get asset_no
        echo "=== TESTING ASSET_NO COLUMN ===\n";
        $testAsset = DbMysql::selectSqlAll("SELECT asset_id, asset_no, asset_name FROM ast_asset LIMIT 1");
        if (!empty($testAsset)) {
            echo "Raw query result:\n";
            var_dump($testAsset[0]);
        }
        echo "\n";
        
        // Check for your specific asset codes using direct SQL
        echo "=== CHECKING YOUR ASSET CODES (DIRECT SQL) ===\n";
        $searchAssets = ['APDM01', 'IT001', 'IT0010', 'IT002', 'IT003'];
        
        foreach ($searchAssets as $assetCode) {
            try {
                $result = DbMysql::selectSqlAll("SELECT asset_id, asset_no, asset_name FROM ast_asset WHERE asset_no = '$assetCode' LIMIT 1");
                if (!empty($result)) {
                    echo "✅ FOUND: $assetCode\n";
                    var_dump($result[0]);
                } else {
                    echo "❌ NOT FOUND: $assetCode\n";
                }
            } catch (Exception $e) {
                echo "❌ ERROR checking $assetCode: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "❌ No assets found in database. The ast_asset table is empty.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
