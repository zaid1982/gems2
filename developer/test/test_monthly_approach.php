<?php
/**
 * Alternative monthly data collection approach
 * This avoids the complexity of weekly processing and potential data loss
 */

require_once 'api/constant.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_db.php';

echo "=== TESTING ALTERNATIVE MONTHLY APPROACH ===\n\n";

function testMonthlyDataCollection($year, $month) {
    $db = Class_db::getInstance();
    
    // Simple monthly date range
    $monthStart = "$year-" . sprintf('%02d', $month) . "-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    echo "Testing monthly collection for $year-$month\n";
    echo "Date range: $monthStart to $monthEnd\n\n";
    
    $monthlyData = [];
    
    // Get PPM data for the month
    $ppmData = $db->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, 
        array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
    
    echo "PPM records found: " . count($ppmData) . "\n";
    
    foreach ($ppmData as $ppm) {
        $userId = intval($ppm['ppmTaskAssignedTo']);
        
        if (!isset($monthlyData[$userId])) {
            $monthlyData[$userId] = [
                'userId' => $userId,
                'siteId' => $ppm['siteId'],
                'ppmTotal' => 0,
                'ppmCompleted' => 0,
                'ppmOnTime' => 0,
                'ppmLate' => 0,
                'ppmWithin' => 0,
                'woTotal' => 0,
                'woCompleted' => 0,
                'woOnTime' => 0,
                'woLate' => 0,
                'woSelfFinding' => 0
            ];
        }
        
        $monthlyData[$userId]['ppmTotal'] += intval($ppm['ppmTotal']);
        $monthlyData[$userId]['ppmCompleted'] += intval($ppm['ppmCompleted']);
        $monthlyData[$userId]['ppmOnTime'] += intval($ppm['ppmOnTime']);
        $monthlyData[$userId]['ppmLate'] += intval($ppm['ppmLate']);
        $monthlyData[$userId]['ppmWithin'] += intval($ppm['ppmWithin']);
    }
    
    // Get WO data for the month
    $woData = $db->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
        array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
    
    echo "WO records found: " . count($woData) . "\n";
    
    foreach ($woData as $wo) {
        $userId = intval($wo['woTaskAssignedTo']);
        
        if (!isset($monthlyData[$userId])) {
            $monthlyData[$userId] = [
                'userId' => $userId,
                'siteId' => $wo['siteId'],
                'ppmTotal' => 0,
                'ppmCompleted' => 0,
                'ppmOnTime' => 0,
                'ppmLate' => 0,
                'ppmWithin' => 0,
                'woTotal' => 0,
                'woCompleted' => 0,
                'woOnTime' => 0,
                'woLate' => 0,
                'woSelfFinding' => 0
            ];
        }
        
        $monthlyData[$userId]['woTotal'] += intval($wo['woTotal']);
        $monthlyData[$userId]['woCompleted'] += intval($wo['woCompleted']);
        $monthlyData[$userId]['woOnTime'] += intval($wo['woOnTime']);
        $monthlyData[$userId]['woLate'] += intval($wo['woLate']);
        $monthlyData[$userId]['woSelfFinding'] += intval($wo['woSelfFinding']);
    }
    
    echo "Users with data: " . count($monthlyData) . "\n\n";
    
    // Show sample data for first few users
    $count = 0;
    foreach ($monthlyData as $userId => $data) {
        if ($count >= 3) break;
        echo "User $userId:\n";
        echo "  PPM: {$data['ppmTotal']} total, {$data['ppmCompleted']} completed\n";
        echo "  WO: {$data['woTotal']} total, {$data['woCompleted']} completed\n";
        $count++;
    }
    
    return $monthlyData;
}

try {
    // Test for August 2025
    $data = testMonthlyDataCollection(2025, 8);
    
    echo "\n=== RECOMMENDATION ===\n";
    echo "Consider switching to monthly data collection instead of weekly\n";
    echo "This approach:\n";
    echo "1. Avoids complex week boundary calculations\n";
    echo "2. Ensures all data within the month is captured\n";
    echo "3. Simplifies the logic and reduces potential errors\n";
    echo "4. Is more straightforward to debug and maintain\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
