<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

// Initialize required classes
$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

Class_db::getInstance()->db_connect();

// Get parameters from URL
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
$userId = $_GET['user_id'] ?? null;
$debug_level = $_GET['debug'] ?? 'summary'; // summary, detailed, full

?>
<!DOCTYPE html>
<html>
<head>
    <title>Gamification runMonthly Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .config { background-color: #f0f8ff; }
        .weekly { background-color: #f0fff0; }
        .monthly { background-color: #fff8f0; }
        .calculation { background-color: #f8f0ff; }
        .user-data { border: 1px solid #666; margin: 5px 0; padding: 10px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .highlight { background-color: yellow; }
        .error { color: red; }
        .success { color: green; }
        pre { background-color: #f5f5f5; padding: 10px; overflow-x: auto; }
        .formula { font-family: monospace; background-color: #e8e8e8; padding: 2px 4px; }
    </style>
</head>
<body>

<h1>Gamification runMonthly Debug Tool</h1>

<form method="GET">
    <label>Year: <input type="number" name="year" value="<?= $year ?>" min="2020" max="2030"></label>
    <label>Month: <input type="number" name="month" value="<?= $month ?>" min="1" max="12"></label>
    <label>User ID (optional): <input type="number" name="user_id" value="<?= $userId ?>" placeholder="Leave empty for all users"></label>
    <label>Debug Level: 
        <select name="debug">
            <option value="summary" <?= $debug_level == 'summary' ? 'selected' : '' ?>>Summary</option>
            <option value="detailed" <?= $debug_level == 'detailed' ? 'selected' : '' ?>>Detailed</option>
            <option value="full" <?= $debug_level == 'full' ? 'selected' : '' ?>>Full Debug</option>
        </select>
    </label>
    <button type="submit">Debug</button>
</form>

<?php

echo "<h2>Debug Results for Year $year, Month $month</h2>";

try {
    // Create gamification instance and access private methods/properties using reflection
    $gamification = new Class_gamification();
    $reflection = new ReflectionClass($gamification);
    
    // Get configuration
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($gamification);
    
    echo '<div class="section config">';
    echo '<h3>📋 Current Configuration</h3>';
    $configKeys = ['weight_completed', 'weight_ontime', 'weight_late_penalty', 'tier_medalist_threshold', 
                   'tier_finisher_threshold', 'mbv_tier1_threshold', 'mbv_tier2_threshold', 
                   'mbv_tier1_multiplier', 'mbv_tier2_multiplier', 'mbv_tier3_multiplier',
                   'self_finding_points', 'point_scale_factor', 'productivity_base', 'wo_ontime_multiplier'];
    
    echo '<table>';
    echo '<tr><th>Configuration Key</th><th>Value</th><th>Type</th></tr>';
    foreach ($configKeys as $key) {
        $value = isset($config[$key]) ? $config[$key] : 'NOT SET';
        $type = is_float($value) ? 'float' : (is_int($value) ? 'int' : 'string');
        $highlight = in_array($key, ['weight_completed', 'weight_ontime']) ? 'highlight' : '';
        echo "<tr class='$highlight'><td>$key</td><td><strong>$value</strong></td><td>$type</td></tr>";
    }
    echo '</table>';
    echo '</div>';
    
    // Get private methods
    $getWeeksInMonthMethod = $reflection->getMethod('getWeeksInMonth');
    $getWeeksInMonthMethod->setAccessible(true);
    
    $getWeekDateRangeMethod = $reflection->getMethod('getWeekDateRange');
    $getWeekDateRangeMethod->setAccessible(true);
    
    $calculateWeeklyScoresMethod = $reflection->getMethod('calculateWeeklyScores');
    $calculateWeeklyScoresMethod->setAccessible(true);
    
    $getTradeRatioMethod = $reflection->getMethod('getTradeRatio');
    $getTradeRatioMethod->setAccessible(true);
    
    // Start debugging the monthly calculation
    $weeksInMonth = $getWeeksInMonthMethod->invoke($gamification, $year, $month);
    
    echo '<div class="section">';
    echo "<h3>📅 Month Overview</h3>";
    echo "<p><strong>Number of weeks in $year-$month:</strong> $weeksInMonth</p>";
    echo '</div>';
    
    $gmiMonthlyAggregated = array();
    
    // Process each week
    for ($week = 1; $week <= $weeksInMonth; $week++) {
        $weekDateRange = $getWeekDateRangeMethod->invoke($gamification, $year, $month, $week);
        $weekStartDate = $weekDateRange['start'];
        $weekEndDate = $weekDateRange['end'];
        
        echo '<div class="section weekly">';
        echo "<h3>📊 Week $week ($weekStartDate to $weekEndDate)</h3>";
        
        if ($debug_level == 'full') {
            echo "<p><strong>Date Range:</strong> $weekStartDate to $weekEndDate</p>";
        }
        
        // Get weekly scores with detailed debugging
        $gmiWeekly = $calculateWeeklyScoresMethod->invoke($gamification, $year, $month, $week, $weekStartDate, $weekEndDate);
        
        if (empty($gmiWeekly)) {
            echo "<p class='error'>No data found for this week</p>";
            echo '</div>';
            continue;
        }
        
        // Filter by user if specified
        if ($userId) {
            $gmiWeekly = array_filter($gmiWeekly, function($key) use ($userId) {
                return $key == $userId;
            }, ARRAY_FILTER_USE_KEY);
        }
        
        if ($debug_level == 'summary') {
            echo "<p><strong>Users with data:</strong> " . count($gmiWeekly) . "</p>";
        } else {
            echo "<p><strong>Users processed:</strong> " . count($gmiWeekly) . "</p>";
            
            foreach ($gmiWeekly as $weekUserId => $weekData) {
                echo '<div class="user-data">';
                echo "<h4>👤 User ID: $weekUserId</h4>";
                
                // Show raw counts
                echo '<table>';
                echo '<tr><th>Metric</th><th>PPM</th><th>WO</th><th>Total</th></tr>';
                echo '<tr><td>Total Tasks</td><td>' . $weekData['gmwPpmTotal'] . '</td><td>' . $weekData['gmwWoTotal'] . '</td><td>' . ($weekData['gmwPpmTotal'] + $weekData['gmwWoTotal']) . '</td></tr>';
                echo '<tr><td>Completed</td><td>' . $weekData['gmwPpmCompleted'] . '</td><td>' . $weekData['gmwWoCompleted'] . '</td><td>' . ($weekData['gmwPpmCompleted'] + $weekData['gmwWoCompleted']) . '</td></tr>';
                echo '<tr><td>On Time</td><td>' . $weekData['gmwPpmOnTime'] . '</td><td>' . $weekData['gmwWoOnTime'] . '</td><td>' . ($weekData['gmwPpmOnTime'] + $weekData['gmwWoOnTime']) . '</td></tr>';
                echo '<tr><td>Late</td><td>' . $weekData['gmwPpmLate'] . '</td><td>' . $weekData['gmwWoLate'] . '</td><td>' . ($weekData['gmwPpmLate'] + $weekData['gmwWoLate']) . '</td></tr>';
                echo '<tr><td>Within</td><td>' . $weekData['gmwPpmWithin'] . '</td><td>-</td><td>' . $weekData['gmwPpmWithin'] . '</td></tr>';
                echo '<tr><td>Self Finding</td><td>-</td><td>' . $weekData['gmwWoSelfFinding'] . '</td><td>' . $weekData['gmwWoSelfFinding'] . '</td></tr>';
                echo '</table>';
                
                if ($debug_level == 'detailed' || $debug_level == 'full') {
                    // Show detailed calculations
                    $allTotal = $weekData['gmwPpmTotal'] + $weekData['gmwWoTotal'];
                    $allCompleted = $weekData['gmwPpmCompleted'] + $weekData['gmwWoCompleted'];
                    $allOnTime = $weekData['gmwPpmOnTime'] + ($config['wo_ontime_multiplier'] * $weekData['gmwWoOnTime']) + $weekData['gmwPpmWithin'];
                    $allWithin = $weekData['gmwWoOnTime'] + $weekData['gmwPpmWithin'];
                    $allLate = $weekData['gmwPpmLate'] + $weekData['gmwWoLate'];
                    $mbv = $allOnTime - $allLate;
                    
                    // Determine tier multiplier
                    if ($mbv <= $config['mbv_tier1_threshold']) {
                        $tierDivider = $config['mbv_tier1_multiplier'];
                        $tierName = 'Tier 1';
                    } else if ($mbv <= $config['mbv_tier2_threshold']) {
                        $tierDivider = $config['mbv_tier2_multiplier'];
                        $tierName = 'Tier 2';
                    } else {
                        $tierDivider = $config['mbv_tier3_multiplier'];
                        $tierName = 'Tier 3';
                    }
                    
                    echo '<div class="calculation">';
                    echo '<h5>🧮 Point Calculations</h5>';
                    
                    echo '<table>';
                    echo '<tr><th>Calculation Step</th><th>Formula</th><th>Result</th></tr>';
                    
                    echo '<tr><td>MBV (Merit-Based Value)</td><td class="formula">allOnTime - allLate = ' . $allOnTime . ' - ' . $allLate . '</td><td><strong>' . $mbv . '</strong></td></tr>';
                    echo '<tr><td>Tier Classification</td><td class="formula">MBV ' . $mbv . ' → ' . $tierName . '</td><td><strong>Multiplier: ' . $tierDivider . '</strong></td></tr>';
                    
                    if ($allTotal > 0) {
                        $completedPoints = ($allCompleted / $allTotal) * $config['weight_completed'] * $config['point_scale_factor'];
                        $onTimePoints = (($allWithin / $allTotal) * $tierDivider) * $config['weight_ontime'] * $config['point_scale_factor'];
                        $latePoints = $allCompleted === 0 ? 0 : -(($allLate / $allCompleted) * $tierDivider) * $config['weight_late_penalty'] * $config['point_scale_factor'];
                        
                        echo '<tr class="highlight"><td>Completed Points</td><td class="formula">(' . $allCompleted . '/' . $allTotal . ') × ' . $config['weight_completed'] . ' × ' . $config['point_scale_factor'] . '</td><td><strong>' . round($completedPoints, 2) . '</strong></td></tr>';
                        echo '<tr class="highlight"><td>On-Time Points</td><td class="formula">((' . $allWithin . '/' . $allTotal . ') × ' . $tierDivider . ') × ' . $config['weight_ontime'] . ' × ' . $config['point_scale_factor'] . '</td><td><strong>' . round($onTimePoints, 2) . '</strong></td></tr>';
                        echo '<tr><td>Late Penalty</td><td class="formula">-((' . $allLate . '/' . $allCompleted . ') × ' . $tierDivider . ') × ' . $config['weight_late_penalty'] . ' × ' . $config['point_scale_factor'] . '</td><td><strong>' . round($latePoints, 2) . '</strong></td></tr>';
                        
                        $selfFindingPoints = $weekData['gmwWoSelfFinding'] * $config['self_finding_points'];
                        echo '<tr><td>Self Finding Points</td><td class="formula">' . $weekData['gmwWoSelfFinding'] . ' × ' . $config['self_finding_points'] . '</td><td><strong>' . $selfFindingPoints . '</strong></td></tr>';
                        
                        $totalPoints = $weekData['gmwPointTotal'];
                        echo '<tr><td colspan="2"><strong>Weekly Total Points</strong></td><td><strong>' . round($totalPoints, 2) . '</strong></td></tr>';
                    } else {
                        echo '<tr><td colspan="3" class="error">No tasks completed - no points calculated</td></tr>';
                    }
                    
                    echo '</table>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
        }
        
        // Aggregate into monthly totals
        foreach ($gmiWeekly as $weekUserId => $weeklyData) {
            if (!array_key_exists($weekUserId, $gmiMonthlyAggregated)) {
                $setInitialMethod = $reflection->getMethod('setInitialGmiMonthArr');
                $setInitialMethod->setAccessible(true);
                $gmiMonthlyAggregated[$weekUserId] = $setInitialMethod->invoke($gamification, $weekUserId, $year, $month, $weeklyData['siteId'], 0);
            }
            
            // Aggregate counts
            $gmiMonthlyAggregated[$weekUserId]['gmiPpmTotal'] += $weeklyData['gmwPpmTotal'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPpmCompleted'] += $weeklyData['gmwPpmCompleted'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPpmOnTime'] += $weeklyData['gmwPpmOnTime'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPpmLate'] += $weeklyData['gmwPpmLate'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPpmWithin'] += $weeklyData['gmwPpmWithin'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPpmAssist'] += $weeklyData['gmwPpmAssist'];
            $gmiMonthlyAggregated[$weekUserId]['gmiWoTotal'] += $weeklyData['gmwWoTotal'];
            $gmiMonthlyAggregated[$weekUserId]['gmiWoCompleted'] += $weeklyData['gmwWoCompleted'];
            $gmiMonthlyAggregated[$weekUserId]['gmiWoOnTime'] += $weeklyData['gmwWoOnTime'];
            $gmiMonthlyAggregated[$weekUserId]['gmiWoLate'] += $weeklyData['gmwWoLate'];
            $gmiMonthlyAggregated[$weekUserId]['gmiWoSelfFinding'] += $weeklyData['gmwWoSelfFinding'];
            $gmiMonthlyAggregated[$weekUserId]['gmiWoAssist'] += $weeklyData['gmwWoAssist'];
            
            // Accumulate points
            $gmiMonthlyAggregated[$weekUserId]['gmiPointCompleted'] += $weeklyData['gmwPointCompleted'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPointOnTime'] += $weeklyData['gmwPointOnTime'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPointLate'] += $weeklyData['gmwPointLate'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPointSelfFinding'] += $weeklyData['gmwPointSelfFinding'];
            $gmiMonthlyAggregated[$weekUserId]['gmiPointTotal'] += $weeklyData['gmwPointTotal'];
        }
        
        echo '</div>';
    }
    
    // Show monthly aggregated results
    echo '<div class="section monthly">';
    echo '<h3>📈 Monthly Aggregated Results</h3>';
    
    if (empty($gmiMonthlyAggregated)) {
        echo "<p class='error'>No monthly data to aggregate</p>";
    } else {
        // Filter by user if specified
        if ($userId) {
            $gmiMonthlyAggregated = array_filter($gmiMonthlyAggregated, function($key) use ($userId) {
                return $key == $userId;
            }, ARRAY_FILTER_USE_KEY);
        }
        
        echo "<p><strong>Total users with monthly data:</strong> " . count($gmiMonthlyAggregated) . "</p>";
        
        foreach ($gmiMonthlyAggregated as $monthUserId => $monthData) {
            echo '<div class="user-data">';
            echo "<h4>👤 User ID: $monthUserId - Monthly Summary</h4>";
            
            echo '<table>';
            echo '<tr><th>Metric</th><th>PPM</th><th>WO</th><th>Total</th></tr>';
            echo '<tr><td>Total Tasks</td><td>' . $monthData['gmiPpmTotal'] . '</td><td>' . $monthData['gmiWoTotal'] . '</td><td>' . ($monthData['gmiPpmTotal'] + $monthData['gmiWoTotal']) . '</td></tr>';
            echo '<tr><td>Completed</td><td>' . $monthData['gmiPpmCompleted'] . '</td><td>' . $monthData['gmiWoCompleted'] . '</td><td>' . ($monthData['gmiPpmCompleted'] + $monthData['gmiWoCompleted']) . '</td></tr>';
            echo '<tr><td>On Time</td><td>' . $monthData['gmiPpmOnTime'] . '</td><td>' . $monthData['gmiWoOnTime'] . '</td><td>' . ($monthData['gmiPpmOnTime'] + $monthData['gmiWoOnTime']) . '</td></tr>';
            echo '<tr><td>Late</td><td>' . $monthData['gmiPpmLate'] . '</td><td>' . $monthData['gmiWoLate'] . '</td><td>' . ($monthData['gmiPpmLate'] + $monthData['gmiWoLate']) . '</td></tr>';
            echo '</table>';
            
            echo '<div class="calculation">';
            echo '<h5>🏆 Monthly Point Summary</h5>';
            echo '<table>';
            echo '<tr><th>Point Type</th><th>Value</th></tr>';
            echo '<tr class="highlight"><td>Completed Points</td><td><strong>' . round($monthData['gmiPointCompleted'], 2) . '</strong></td></tr>';
            echo '<tr class="highlight"><td>On-Time Points</td><td><strong>' . round($monthData['gmiPointOnTime'], 2) . '</strong></td></tr>';
            echo '<tr><td>Late Penalty</td><td><strong>' . round($monthData['gmiPointLate'], 2) . '</strong></td></tr>';
            echo '<tr><td>Self Finding Points</td><td><strong>' . round($monthData['gmiPointSelfFinding'], 2) . '</strong></td></tr>';
            echo '<tr><td colspan="2"><hr></td></tr>';
            echo '<tr><td><strong>TOTAL MONTHLY POINTS</strong></td><td><strong>' . round($monthData['gmiPointTotal'], 2) . '</strong></td></tr>';
            echo '</table>';
            echo '</div>';
            
            echo '</div>';
        }
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error occurred during debugging: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

?>

<div class="section">
<h3>💡 Usage Tips</h3>
<ul>
    <li><strong>Summary Mode:</strong> Quick overview of users and basic counts</li>
    <li><strong>Detailed Mode:</strong> Shows calculation formulas and intermediate steps</li>
    <li><strong>Full Debug Mode:</strong> Complete detailed breakdown with all data</li>
    <li><strong>User Filter:</strong> Enter a specific User ID to see calculations for just that user</li>
    <li><strong>Weight Changes:</strong> Look for highlighted rows showing weight_completed and weight_ontime values</li>
</ul>
</div>

</body>
</html>
