<?php
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

try {
    echo "<h2>Testing runMonthly Duplicate Prevention</h2>";
    
    $gamification = new Class_gamification();
    
    // Get current date for testing
    $year = date('Y');
    $month = date('n');
    
    echo "<p><strong>Testing with Year: $year, Month: $month</strong></p>";
    
    // Count records before
    $db = Class_db::getInstance();
    $beforeWeekly = $db->db_select_col('gmi_weekly', array(), 'COUNT(*) as count', null, 1);
    $beforeMonthly = $db->db_select_col('gmi_monthly', array(), 'COUNT(*) as count', null, 1);
    
    echo "<p>Records before runMonthly:</p>";
    echo "<ul>";
    echo "<li>Weekly records: " . ($beforeWeekly['count'] ?? 0) . "</li>";
    echo "<li>Monthly records: " . ($beforeMonthly['count'] ?? 0) . "</li>";
    echo "</ul>";
    
    // Run monthly calculation
    echo "<p>Running runMonthly for $year-$month...</p>";
    $result = $gamification->runMonthly($year, $month);
    echo "<p style='color: green;'>✅ runMonthly completed successfully!</p>";
    
    // Count records after first run
    $afterWeekly1 = $db->db_select_col('gmi_weekly', array(), 'COUNT(*) as count', null, 1);
    $afterMonthly1 = $db->db_select_col('gmi_monthly', array(), 'COUNT(*) as count', null, 1);
    
    echo "<p>Records after first runMonthly:</p>";
    echo "<ul>";
    echo "<li>Weekly records: " . ($afterWeekly1['count'] ?? 0) . " (+". (($afterWeekly1['count'] ?? 0) - ($beforeWeekly['count'] ?? 0)) .")</li>";
    echo "<li>Monthly records: " . ($afterMonthly1['count'] ?? 0) . " (+". (($afterMonthly1['count'] ?? 0) - ($beforeMonthly['count'] ?? 0)) .")</li>";
    echo "</ul>";
    
    // Run monthly calculation AGAIN to test duplicate prevention
    echo "<p>Running runMonthly AGAIN for $year-$month to test duplicate prevention...</p>";
    $result2 = $gamification->runMonthly($year, $month);
    echo "<p style='color: green;'>✅ Second runMonthly completed successfully!</p>";
    
    // Count records after second run
    $afterWeekly2 = $db->db_select_col('gmi_weekly', array(), 'COUNT(*) as count', null, 1);
    $afterMonthly2 = $db->db_select_col('gmi_monthly', array(), 'COUNT(*) as count', null, 1);
    
    echo "<p>Records after second runMonthly:</p>";
    echo "<ul>";
    echo "<li>Weekly records: " . ($afterWeekly2['count'] ?? 0) . " (+". (($afterWeekly2['count'] ?? 0) - ($afterWeekly1['count'] ?? 0)) .")</li>";
    echo "<li>Monthly records: " . ($afterMonthly2['count'] ?? 0) . " (+". (($afterMonthly2['count'] ?? 0) - ($afterMonthly1['count'] ?? 0)) .")</li>";
    echo "</ul>";
    
    // Check for duplicates
    $duplicateWeekly = $db->db_select("SELECT user_id, gmw_year, gmw_month, gmw_week, COUNT(*) as count 
                                      FROM gmi_weekly 
                                      WHERE gmw_year = $year AND gmw_month = $month 
                                      GROUP BY user_id, gmw_year, gmw_month, gmw_week 
                                      HAVING COUNT(*) > 1", array());
    
    $duplicateMonthly = $db->db_select("SELECT user_id, gmi_year, gmi_month, COUNT(*) as count 
                                       FROM gmi_monthly 
                                       WHERE gmi_year = $year AND gmi_month = $month 
                                       GROUP BY user_id, gmi_year, gmi_month 
                                       HAVING COUNT(*) > 1", array());
    
    echo "<h3>Duplicate Check Results:</h3>";
    
    if (empty($duplicateWeekly)) {
        echo "<p style='color: green;'>✅ No duplicate weekly records found!</p>";
    } else {
        echo "<p style='color: red;'>❌ Found " . count($duplicateWeekly) . " duplicate weekly records:</p>";
        echo "<pre>" . print_r($duplicateWeekly, true) . "</pre>";
    }
    
    if (empty($duplicateMonthly)) {
        echo "<p style='color: green;'>✅ No duplicate monthly records found!</p>";
    } else {
        echo "<p style='color: red;'>❌ Found " . count($duplicateMonthly) . " duplicate monthly records:</p>";
        echo "<pre>" . print_r($duplicateMonthly, true) . "</pre>";
    }
    
    echo "<h3>Summary:</h3>";
    if (($afterWeekly2['count'] - $afterWeekly1['count']) == 0 && ($afterMonthly2['count'] - $afterMonthly1['count']) == 0) {
        echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS! Second runMonthly did not create duplicate records.</p>";
        echo "<p>The fix is working correctly - existing records are being updated instead of creating new ones.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ ISSUE! Second runMonthly created additional records.</p>";
        echo "<p>This indicates the duplicate prevention is not working properly.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
