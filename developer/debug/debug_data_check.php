<?php
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

Class_db::getInstance()->db_connect();

echo "<h2>Checking Available Data for Gamification</h2>";

try {
    // Check if there's any GMI data first
    echo "<h3>1. Checking GMI Weekly Data</h3>";
    
    // First get sample data to see structure
    $gmiWeekly = Class_db::getInstance()->db_select2('gmi_weekly', array(), '', 10);
    
    if (!empty($gmiWeekly)) {
        echo "<p>Found " . count($gmiWeekly) . " weekly records.</p>";
        echo "<h4>Sample record structure:</h4>";
        echo "<pre>" . print_r($gmiWeekly[0], true) . "</pre>";
        
        // Now try with correct column names (just get data without ordering first)
        $gmiWeekly = Class_db::getInstance()->db_select2('gmi_weekly', array(), '', 10);
        
        echo "<table border='1'>";
        echo "<tr><th>Year</th><th>Week</th><th>User ID</th><th>PPM Total</th><th>WO Total</th><th>Points</th></tr>";
        foreach ($gmiWeekly as $record) {
            $year = $record['gmwYear'] ?? 'N/A';
            $week = $record['gmwWeek'] ?? 'N/A';
            $userId = $record['userId'] ?? 'N/A';
            $ppmTotal = $record['gmwPpmTotal'] ?? 'N/A';
            $woTotal = $record['gmwWoTotal'] ?? 'N/A';
            $points = $record['gmwPointTotal'] ?? 'N/A';
            
            echo "<tr>";
            echo "<td>$year</td>";
            echo "<td>$week</td>";
            echo "<td>$userId</td>";
            echo "<td>$ppmTotal</td>";
            echo "<td>$woTotal</td>";
            echo "<td>$points</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Calculate what month week 31 belongs to
        $latestRecord = $gmiWeekly[0];
        $latestYear = $latestRecord['gmwYear'] ?? date('Y');
        $latestWeek = $latestRecord['gmwWeek'] ?? 1;
        
        // Week 31 is typically in July/August
        $estimatedMonth = 7; // July for week 31
        
        echo "<p><strong>Available data:</strong> Year $latestYear, Week $latestWeek (estimated Month $estimatedMonth)</p>";
        echo "<p><a href='debug_runmonthly.php?year=$latestYear&month=$estimatedMonth&debug=detailed&user_id=" . $latestRecord['userId'] . "'>Debug with User " . $latestRecord['userId'] . "</a></p>";
    } else {
        echo "<p style='color: red;'>No GMI weekly data found!</p>";
    }
    
    echo "<h3>2. Checking Raw PPM/WO Data</h3>";
    // Check if there's raw PPM data
    $ppmData = Class_db::getInstance()->db_select2('ppm_transaction', array(), 'ppm_transaction_id DESC', 5);
    echo "<p>PPM transactions found: " . count($ppmData) . "</p>";
    
    if (!empty($ppmData)) {
        echo "<p>Latest PPM transaction: " . $ppmData[0]['dateCompleted'] . "</p>";
    }
    
    // Check WO data
    $woData = Class_db::getInstance()->db_select2('wo_transaction', array(), 'wo_transaction_id DESC', 5);
    echo "<p>WO transactions found: " . count($woData) . "</p>";
    
    if (!empty($woData)) {
        echo "<p>Latest WO transaction: " . $woData[0]['dateCompleted'] . "</p>";
    }
    
    echo "<h3>3. Test Manual Calculation</h3>";
    if (!empty($gmiWeekly)) {
        echo "<p>Let's test the gamification calculation with existing data...</p>";
        
        $gamification = new Class_gamification();
        
        // Test with the latest available data
        echo "<p><a href='debug_runmonthly.php?year=$latestYear&month=$latestMonth&debug=detailed&user_id=" . $latestRecord['userId'] . "'>Focus on User " . $latestRecord['userId'] . "</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
