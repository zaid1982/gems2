<?php
/**
 * Diagnostic script to investigate gamification data collection issues
 * This will help identify why WO and PPM tickets are being missed
 */

require_once 'api/constant.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_db.php';
require_once 'api/function/f_gamification.php';

echo "=== GAMIFICATION DATA COLLECTION DIAGNOSTIC ===\n\n";

try {
    $gamification = new Class_gamification();
    $db = Class_db::getInstance();
    
    // Test parameters - adjust these for your specific case
    $year = 2025;
    $month = 8; // August
    
    echo "Investigating data collection for $year-$month\n";
    echo "==============================================\n\n";
    
    // 1. Check total PPM and WO data available for the month using simple date range
    echo "1. CHECKING TOTAL AVAILABLE DATA FOR THE MONTH:\n";
    echo "-----------------------------------------------\n";
    
    $monthStart = "$year-" . sprintf('%02d', $month) . "-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart)); // Last day of month
    
    echo "Month date range: $monthStart to $monthEnd\n\n";
    
    // Check PPM data using simple date filter
    $ppmDataSimple = $db->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, 
        array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
    echo "PPM records (simple month range): " . count($ppmDataSimple) . "\n";
    
    // Check WO data using simple date filter  
    $woDataSimple = $db->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
        array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
    echo "WO records (simple month range): " . count($woDataSimple) . "\n\n";
    
    // 2. Check what the weekly processing would collect
    echo "2. CHECKING WEEKLY PROCESSING DATA COLLECTION:\n";
    echo "---------------------------------------------\n";
    
    // Use reflection to access private methods
    $reflection = new ReflectionClass($gamification);
    $getWeeksInMonthMethod = $reflection->getMethod('getWeeksInMonth');
    $getWeeksInMonthMethod->setAccessible(true);
    $getWeekDateRangeMethod = $reflection->getMethod('getWeekDateRange');
    $getWeekDateRangeMethod->setAccessible(true);
    
    $weeksInMonth = $getWeeksInMonthMethod->invoke($gamification, $year, $month);
    echo "Weeks in month: $weeksInMonth\n\n";
    
    $totalPpmWeekly = 0;
    $totalWoWeekly = 0;
    
    for ($week = 1; $week <= $weeksInMonth; $week++) {
        $range = $getWeekDateRangeMethod->invoke($gamification, $year, $month, $week);
        $weekStart = $range['start'];
        $weekEnd = $range['end'];
        
        echo "Week $week: $weekStart to $weekEnd\n";
        
        // Get PPM data for this week
        $ppmWeek = $db->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, 
            array('dateStart' => $weekStart, 'dateEnd' => $weekEnd));
        
        // Get WO data for this week
        $woWeek = $db->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
            array('dateStart' => $weekStart, 'dateEnd' => $weekEnd));
        
        echo "   PPM records: " . count($ppmWeek) . "\n";
        echo "   WO records: " . count($woWeek) . "\n";
        
        $totalPpmWeekly += count($ppmWeek);
        $totalWoWeekly += count($woWeek);
        
        // Check if week extends beyond month
        if ($weekStart < $monthStart || $weekEnd > $monthEnd) {
            echo "   ⚠️  Week extends beyond month boundaries!\n";
        }
        echo "\n";
    }
    
    echo "SUMMARY:\n";
    echo "--------\n";
    echo "Total PPM (simple month): " . count($ppmDataSimple) . "\n";
    echo "Total PPM (weekly sum): $totalPpmWeekly\n";
    echo "Total WO (simple month): " . count($woDataSimple) . "\n";
    echo "Total WO (weekly sum): $totalWoWeekly\n\n";
    
    // 3. Check for data discrepancies
    if (count($ppmDataSimple) != $totalPpmWeekly) {
        echo "❌ PPM DATA MISMATCH DETECTED!\n";
        echo "   Difference: " . (count($ppmDataSimple) - $totalPpmWeekly) . " records\n";
    } else {
        echo "✅ PPM data collection looks consistent\n";
    }
    
    if (count($woDataSimple) != $totalWoWeekly) {
        echo "❌ WO DATA MISMATCH DETECTED!\n";
        echo "   Difference: " . (count($woDataSimple) - $totalWoWeekly) . " records\n";
    } else {
        echo "✅ WO data collection looks consistent\n";
    }
    
    // 4. Check for specific user data
    echo "\n3. CHECKING USER-SPECIFIC DATA:\n";
    echo "------------------------------\n";
    
    if (!empty($ppmDataSimple)) {
        $samplePpm = $ppmDataSimple[0];
        $sampleUserId = $samplePpm['ppmTaskAssignedTo'];
        echo "Sample user ID from PPM: $sampleUserId\n";
        
        // Count this user's data in simple vs weekly
        $userPpmSimple = array_filter($ppmDataSimple, function($item) use ($sampleUserId) {
            return $item['ppmTaskAssignedTo'] == $sampleUserId;
        });
        echo "User $sampleUserId PPM records (simple): " . count($userPpmSimple) . "\n";
    }
    
    // 5. Check view definitions (if accessible)
    echo "\n4. RECOMMENDATIONS:\n";
    echo "------------------\n";
    echo "1. Check if vw_gamification_ppm_daily and vw_gamification_wo_daily views have date filters\n";
    echo "2. Verify that dateStart and dateEnd parameters are working correctly in the views\n";
    echo "3. Check if there are any WHERE clauses in the views that might exclude data\n";
    echo "4. Consider checking the underlying tables directly\n\n";
    
    // 6. Quick fix suggestion
    echo "5. POTENTIAL SOLUTIONS:\n";
    echo "----------------------\n";
    echo "If weekly processing is missing data, consider:\n";
    echo "A. Switch back to monthly processing for data collection\n";
    echo "B. Fix week boundary calculations\n";
    echo "C. Add overlap handling between weeks\n";
    echo "D. Add data validation after collection\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
