<?php
// Simple PTW System Readiness Test
echo "<h2>PTW System Readiness Check</h2>\n";

// 1. Check if core PTW files exist
$required_files = [
    'ptw_form.html',
    'ptw_management.html', 
    'ptw_database_setup.sql',
    'api/ptw.php',
    'api/ptw_approve.php',
    'function/f_ptw.php',
    'js/pages/ptw_form.js'
];

echo "<h3>1. Core Files Check:</h3>\n";
$all_files_exist = true;
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file - EXISTS<br>\n";
    } else {
        echo "❌ $file - MISSING<br>\n";
        $all_files_exist = false;
    }
}

// 2. Check PHP syntax
echo "<h3>2. PHP Syntax Check:</h3>\n";
$php_files = ['api/ptw.php', 'function/f_ptw.php', 'api/ptw_approve.php'];
$syntax_ok = true;
foreach ($php_files as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_code = 0;
        exec("php -l $file 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "✅ $file - SYNTAX OK<br>\n";
        } else {
            echo "❌ $file - SYNTAX ERROR: " . implode(' ', $output) . "<br>\n";
            $syntax_ok = false;
        }
    }
}

// 3. Check database connection (simple test)
echo "<h3>3. Database Connection:</h3>\n";
try {
    require_once 'api/library/constant.php';
    require_once 'api/function/db.php';
    require_once 'api/function/f_general.php';
    
    $fn_general = new Class_general();
    Class_db::getInstance()->db_connect();
    echo "✅ Database connection - SUCCESS<br>\n";
    
    // Test if we can query a basic table
    $result = Class_db::getInstance()->db_select('sys_user', array(), null, '1');
    echo "✅ Database query test - SUCCESS<br>\n";
    
    Class_db::getInstance()->db_close();
} catch (Exception $e) {
    echo "❌ Database connection - FAILED: " . $e->getMessage() . "<br>\n";
}

// 4. Check if PTW database structure ready
echo "<h3>4. Database Structure (PTW Tables):</h3>\n";
if (file_exists('ptw_database_setup.sql')) {
    echo "✅ Database setup script exists<br>\n";
    echo "📝 <strong>Note:</strong> Run the following SQL script to create PTW tables:<br>\n";
    echo "<code>mysql -u [username] -p [database_name] < ptw_database_setup.sql</code><br>\n";
} else {
    echo "❌ Database setup script missing<br>\n";
}

// 5. Overall readiness assessment
echo "<h3>5. Overall System Readiness:</h3>\n";
if ($all_files_exist && $syntax_ok) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>\n";
    echo "🎉 <strong>PTW System is READY for testing!</strong><br>\n";
    echo "<strong>Next steps:</strong><br>\n";
    echo "1. Execute the database setup script (ptw_database_setup.sql)<br>\n";
    echo "2. Access ptw_form.html in your browser<br>\n";
    echo "3. Test creating and submitting PTW permits<br>\n";
    echo "4. Test the approval workflow<br>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>\n";
    echo "⚠️ <strong>PTW System has issues that need to be resolved before testing</strong><br>\n";
    echo "Please fix the errors shown above first.<br>\n";
    echo "</div>\n";
}

echo "<br><hr><br>\n";
echo "<h3>Testing Instructions:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Database Setup:</strong> Import ptw_database_setup.sql into your MySQL database</li>\n";
echo "<li><strong>Form Testing:</strong> Open ptw_form.html and test form creation</li>\n";
echo "<li><strong>API Testing:</strong> Test form submission (Save Draft and Submit for Approval)</li>\n";
echo "<li><strong>Management Testing:</strong> Check ptw_management.html for permit listing</li>\n";
echo "<li><strong>Approval Testing:</strong> Test SHE and FM approval workflows</li>\n";
echo "</ol>\n";
?>
