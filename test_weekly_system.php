<?php
/**
 * Comprehensive test script for Weekly Gamification System
 * This script will test the complete weekly calculation system
 */

require_once('api/function/f_gamification.php');

echo "=== Weekly Gamification System Test ===\n\n";

// Test 1: Check if gmi_weekly table exists and has correct structure
echo "Test 1: Checking gmi_weekly table structure...\n";
try {
    $conn = new PDO("mysql:host=localhost;dbname=gems", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->query("DESCRIBE gmi_weekly");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Table exists with columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Check week calculation function
echo "Test 2: Testing week calculation function...\n";
$gamification = new Class_gamification();

// Test some dates
$testDates = [
    '2024-01-01', // Week 1 of 2024
    '2024-01-07', // Week 1 of 2024
    '2024-01-08', // Week 2 of 2024
    '2024-12-30', // Last week of 2024
    '2025-01-01'  // Week 1 of 2025
];

foreach ($testDates as $date) {
    $week = date('W', strtotime($date));
    echo "  Date: $date → Week: $week\n";
}
echo "\n";

// Test 3: Check existing data in gmi_weekly
echo "Test 3: Checking existing data in gmi_weekly...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM gmi_weekly");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Current records in gmi_weekly: {$result['total']}\n";
    
    if ($result['total'] > 0) {
        $stmt = $conn->query("
            SELECT gmw_year, gmw_week, COUNT(*) as records 
            FROM gmi_weekly 
            GROUP BY gmw_year, gmw_week 
            ORDER BY gmw_year DESC, gmw_week DESC 
            LIMIT 5
        ");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Recent entries:\n";
        foreach ($recent as $entry) {
            echo "  Year: {$entry['gmw_year']}, Week: {$entry['gmw_week']}, Records: {$entry['records']}\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Check if database views exist
echo "Test 4: Checking if required database views exist...\n";
$requiredViews = [
    'vw_gamification_ppm_daily',
    'vw_gamification_ppm_assist_daily', 
    'vw_gamification_wo_daily',
    'vw_gamification_wo_assist_daily'
];

foreach ($requiredViews as $view) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM information_schema.views WHERE table_schema = 'gems' AND table_name = '$view'");
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            echo "✓ View $view exists\n";
        } else {
            echo "✗ View $view missing\n";
        }
    } catch (Exception $e) {
        echo "✗ Error checking $view: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 5: Test a small week calculation run
echo "Test 5: Testing weekly calculation for current month...\n";
try {
    $currentMonth = date('Y-m');
    echo "Running calculation for month: $currentMonth\n";
    
    // Test with fake siteId for testing
    $result = $gamification->runMonthly($currentMonth, 1);
    
    if ($result['success']) {
        echo "✓ Calculation completed successfully!\n";
        echo "  Message: {$result['message']}\n";
        
        // Check if data was inserted
        $stmt = $conn->prepare("
            SELECT COUNT(*) as new_records 
            FROM gmi_weekly 
            WHERE gmw_year = ? AND gmw_week >= ?
        ");
        $year = date('Y');
        $currentWeek = date('W');
        $stmt->execute([$year, $currentWeek]);
        $newRecords = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  New records created: {$newRecords['new_records']}\n";
    } else {
        echo "✗ Calculation failed: {$result['message']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error during calculation: " . $e->getMessage() . "\n\n";
}

// Test 6: Verify data integrity
echo "Test 6: Verifying data integrity...\n";
try {
    // Check for duplicate entries
    $stmt = $conn->query("
        SELECT gmw_year, gmw_week, user_id, site_id, COUNT(*) as duplicates
        FROM gmi_weekly 
        GROUP BY gmw_year, gmw_week, user_id, site_id
        HAVING COUNT(*) > 1
        LIMIT 5
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "✓ No duplicate records found\n";
    } else {
        echo "✗ Found duplicate records:\n";
        foreach ($duplicates as $dup) {
            echo "  Year: {$dup['gmw_year']}, Week: {$dup['gmw_week']}, User: {$dup['user_id']}, Duplicates: {$dup['duplicates']}\n";
        }
    }
    
    // Check data ranges
    $stmt = $conn->query("
        SELECT 
            MIN(gmw_year) as min_year, MAX(gmw_year) as max_year,
            MIN(gmw_week) as min_week, MAX(gmw_week) as max_week,
            MIN(gmw_ppm_total) as min_ppm, MAX(gmw_ppm_total) as max_ppm,
            MIN(gmw_wo_total) as min_wo, MAX(gmw_wo_total) as max_wo
        FROM gmi_weekly
    ");
    $ranges = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ranges['min_year']) {
        echo "✓ Data ranges:\n";
        echo "  Years: {$ranges['min_year']} - {$ranges['max_year']}\n";
        echo "  Weeks: {$ranges['min_week']} - {$ranges['max_week']}\n";
        echo "  PPM Total: {$ranges['min_ppm']} - {$ranges['max_ppm']}\n";
        echo "  WO Total: {$ranges['min_wo']} - {$ranges['max_wo']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error during integrity check: " . $e->getMessage() . "\n\n";
}

// Test 7: Sample data preview
echo "Test 7: Sample data preview...\n";
try {
    $stmt = $conn->query("
        SELECT 
            gmw_year, gmw_week, user_id, site_id,
            gmw_ppm_total, gmw_ppm_completed, gmw_ppm_ontime,
            gmw_wo_total, gmw_wo_completed, gmw_wo_ontime,
            gmw_point_total
        FROM gmi_weekly 
        ORDER BY gmw_year DESC, gmw_week DESC, gmw_point_total DESC
        LIMIT 5
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($samples)) {
        echo "✓ Top 5 recent records by points:\n";
        foreach ($samples as $sample) {
            echo sprintf("  %d-W%02d | User:%d Site:%d | PPM:%d/%d(%d) WO:%d/%d(%d) | Points:%.1f\n",
                $sample['gmw_year'], $sample['gmw_week'], 
                $sample['user_id'], $sample['site_id'],
                $sample['gmw_ppm_completed'], $sample['gmw_ppm_total'], $sample['gmw_ppm_ontime'],
                $sample['gmw_wo_completed'], $sample['gmw_wo_total'], $sample['gmw_wo_ontime'],
                $sample['gmw_point_total']
            );
        }
    } else {
        echo "ℹ No data found in gmi_weekly table\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error during data preview: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
echo "To run weekly calculations manually, use:\n";
echo "  \$gamification = new Class_gamification();\n";
echo "  \$result = \$gamification->runMonthly('2024-12'); // for December 2024\n";
echo "\n";
?>
