<?php
/**
 * Staging Database Schema Fix
 * Identifies and reports schema differences that cause WflTask errors
 */

require_once('../api/class/Constant.php');
require_once('../api/class/DbMysql.php');

// Database connection
$pdo = DbMysql::connect();

try {
    echo "=== STAGING DATABASE SCHEMA ANALYSIS ===\n\n";
    
    $tablesToCheck = [
        'wfl_flow' => ['flowId', 'flowName', 'flowDueDay', 'flowStatus'],
        'wfl_checkpoint' => ['checkpointId', 'checkpointName', 'checkpointType', 'roleId', 'groupId', 'checkpointDueDay', 'flowId'],
        'ref_role' => ['roleId', 'roleDesc'],
        'sys_user_group' => ['userGroupId', 'userId', 'groupId'],
        'sys_user_role' => ['userId', 'roleId', 'groupId', 'userRoleStatus']
    ];
    
    foreach ($tablesToCheck as $tableName => $expectedColumns) {
        echo "CHECKING TABLE: $tableName\n";
        
        try {
            // Get actual table structure
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $actualColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            echo "   Expected columns: " . implode(', ', $expectedColumns) . "\n";
            echo "   Actual columns: " . implode(', ', $actualColumns) . "\n";
            
            $missingColumns = array_diff($expectedColumns, $actualColumns);
            $extraColumns = array_diff($actualColumns, $expectedColumns);
            
            if (empty($missingColumns) && empty($extraColumns)) {
                echo "   ✅ Schema matches\n";
            } else {
                if (!empty($missingColumns)) {
                    echo "   ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
                }
                if (!empty($extraColumns)) {
                    echo "   ⚠️  Extra columns: " . implode(', ', $extraColumns) . "\n";
                }
            }
            
            // Try to get a sample record to see data structure
            $sampleRecord = $pdo->query("SELECT * FROM `$tableName` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($sampleRecord) {
                echo "   Sample record keys: " . implode(', ', array_keys($sampleRecord)) . "\n";
            } else {
                echo "   ⚠️  No records in table\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error checking table: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== RECOMMENDED ACTIONS ===\n";
    echo "1. Compare the schema differences above\n";
    echo "2. Export schema from working dev/local environment\n";
    echo "3. Apply missing schema changes to staging\n";
    echo "4. Ensure column names match exactly between environments\n";
    echo "5. Check for case sensitivity issues (MySQL vs MariaDB)\n";
    echo "6. Verify database collation and character set consistency\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

DbMysql::close();
?>
