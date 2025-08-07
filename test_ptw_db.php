<?php
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';

echo "Testing PTW Database Setup...\n";
echo "=============================\n";

try {
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_general->__set('constant', $constant);

    // Connect to database
    Class_db::getInstance()->db_connect();
    echo "✓ Database connection successful\n\n";
    
    // Test if PTW tables exist by checking information_schema
    $tables = ['ptw_permit', 'ptw_worker', 'ptw_status_history', 'ptw_document', 'ptw_approval_log'];
    echo "Checking PTW Tables:\n";
    echo "===================\n";
    
    foreach($tables as $table) {
        $result = Class_db::getInstance()->db_select('information_schema.tables', array(
            'table_schema'=>'gems',
            'table_name'=>$table
        ));
        if($result && count($result) > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' NOT found\n";
        }
    }
    
    echo "\nChecking PTW Sequence:\n";
    echo "====================\n";
    
    // Test if sys_sequence was updated for PTW
    $result = Class_db::getInstance()->db_select('sys_sequence', array('sequence_name'=>'PTW'));
    if($result && count($result) > 0) {
        $row = $result[0];
        echo "✓ PTW sequence exists: " . $row['sequence_prefix'] . " (length: " . $row['sequence_length'] . ", value: " . $row['sequence_value'] . ")\n";
    } else {
        echo "✗ PTW sequence NOT found\n";
    }
    
    echo "\nChecking Required Columns:\n";
    echo "=========================\n";
    
    // Check if user_designation column was added
    $result = Class_db::getInstance()->db_select('information_schema.columns', array(
        'table_schema'=>'gems',
        'table_name'=>'sys_user',
        'column_name'=>'user_designation'
    ));
    if($result && count($result) > 0) {
        echo "✓ sys_user.user_designation column exists\n";
    } else {
        echo "✗ sys_user.user_designation column NOT found\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "PTW Database Setup Verification Complete!\n";
    echo "You can now start adding PTW permits.\n";
    echo str_repeat("=", 50) . "\n";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database setup and try again.\n";
}
?>
