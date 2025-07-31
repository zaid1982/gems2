<?php
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

Class_db::getInstance()->db_connect();

// Get parameters
$userId = $_GET['user_id'] ?? null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Existing Gamification Data Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .config { background-color: #f0f8ff; }
        .data { background-color: #f0fff0; }
        .calculation { background-color: #f8f0ff; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .highlight { background-color: yellow; }
        .formula { font-family: monospace; background-color: #e8e8e8; padding: 2px 4px; }
    </style>
</head>
<body>

<h1>🎯 Existing Gamification Data Analysis</h1>

<form method="GET">
    <label>Filter by User ID (optional): <input type="number" name="user_id" value="<?= $userId ?>" placeholder="Leave empty for all users"></label>
    <button type="submit">Filter</button>
</form>

<?php

try {
    // Get configuration first
    $gamification = new Class_gamification();
    $reflection = new ReflectionClass($gamification);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($gamification);
    
    echo '<div class="section config">';
    echo '<h3>📋 Current Weight Configuration</h3>';
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th><th>Impact</th></tr>';
    echo '<tr class="highlight"><td><strong>weight_completed</strong></td><td><strong>' . $config['weight_completed'] . '</strong></td><td>How much completion rate affects points</td></tr>';
    echo '<tr class="highlight"><td><strong>weight_ontime</strong></td><td><strong>' . $config['weight_ontime'] . '</strong></td><td>How much on-time rate affects points</td></tr>';
    echo '<tr><td>weight_late_penalty</td><td>' . $config['weight_late_penalty'] . '</td><td>Penalty for late tasks</td></tr>';
    echo '<tr><td>point_scale_factor</td><td>' . $config['point_scale_factor'] . '</td><td>Multiplier for final points</td></tr>';
    echo '<tr><td>mbv_tier1_threshold</td><td>' . $config['mbv_tier1_threshold'] . '</td><td>Threshold for tier classification</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Get source PPM and WO data that feeds into calculations
    echo '<div class="section data">';
    echo '<h3>📋 Source Data - PPM and WO Tasks</h3>';
    echo '<p>This shows the actual task data used in gamification calculations</p>';
    
    // Get PPM data
    echo '<h4>🔧 PPM Tasks</h4>';
    $ppmData = Class_db::getInstance()->db_select2('vw_ppm_list', array(), 'ppm_task_start_date DESC', '20');
    
    if (!empty($ppmData)) {
        echo '<p>Showing latest 20 PPM tasks (Total in system: ' . Class_db::getInstance()->db_count('ppm_task') . ')</p>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Task ID</th>';
        echo '<th>Asset</th>';
        echo '<th>Assigned User</th>';
        echo '<th>Due Date</th>';
        echo '<th>Completed Date</th>';
        echo '<th>Status</th>';
        echo '<th>On Time?</th>';
        echo '<th>Site</th>';
        echo '</tr>';
        
        foreach ($ppmData as $ppm) {
            $dueDate = $ppm['ppmTaskStartDate'] ?? '';
            $completedDate = $ppm['ppmTaskTimeServiced'] ?? '';
            $onTime = '';
            
            if ($completedDate && $dueDate) {
                $onTime = (strtotime($completedDate) <= strtotime($dueDate)) ? '✅ Yes' : '❌ No';
            } else if ($dueDate && !$completedDate) {
                $onTime = (strtotime($dueDate) >= time()) ? '⏳ Pending' : '❌ Overdue';
            }
            
            echo '<tr>';
            echo '<td>' . $ppm['ppmTaskId'] . '</td>';
            echo '<td>' . ($ppm['assetName'] ?? 'N/A') . '</td>';
            echo '<td>' . ($ppm['userFirstName'] ?? 'Unassigned') . '</td>';
            echo '<td>' . ($dueDate ? date('Y-m-d', strtotime($dueDate)) : 'No due date') . '</td>';
            echo '<td>' . ($completedDate ? date('Y-m-d', strtotime($completedDate)) : 'Not completed') . '</td>';
            echo '<td>' . ($ppm['statusDesc'] ?? 'Unknown') . '</td>';
            echo '<td>' . $onTime . '</td>';
            echo '<td>' . ($ppm['siteName'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p style="color: red;">No PPM tasks found!</p>';
    }
    
    // Get WO data
    echo '<h4>🔨 Work Order Tasks</h4>';
    $woData = Class_db::getInstance()->db_select2('wo_task', array(), 'wo_task_time_created DESC', '20');
    
    if (!empty($woData)) {
        echo '<p>Showing latest 20 WO tasks (Total in system: ' . Class_db::getInstance()->db_count('wo_task') . ')</p>';
        echo '<table>';
        echo '<tr>';
        echo '<th>WO ID</th>';
        echo '<th>Description</th>';
        echo '<th>Assigned User</th>';
        echo '<th>Due Date</th>';
        echo '<th>Completed Date</th>';
        echo '<th>Status</th>';
        echo '<th>On Time?</th>';
        echo '<th>Self Finding?</th>';
        echo '<th>Site</th>';
        echo '</tr>';
        
        foreach ($woData as $wo) {
            $dueDate = $wo['woTaskDueDate'] ?? '';
            $completedDate = $wo['woTaskTimeExecuted'] ?? '';
            $onTime = '';
            
            if ($completedDate && $dueDate) {
                $onTime = (strtotime($completedDate) <= strtotime($dueDate)) ? '✅ Yes' : '❌ No';
            } else if ($dueDate && !$completedDate) {
                $onTime = (strtotime($dueDate) >= time()) ? '⏳ Pending' : '❌ Overdue';
            }
            
            $selfFinding = ($wo['woTaskType'] == '2') ? '✅ Yes' : '❌ No';
            
            echo '<tr>';
            echo '<td>' . $wo['woTaskId'] . '</td>';
            echo '<td>' . substr($wo['woTaskDesc'] ?? 'No description', 0, 50) . '</td>';
            echo '<td>User ' . ($wo['woTaskAssignedTo'] ?? 'Unassigned') . '</td>';
            echo '<td>' . ($dueDate ? date('Y-m-d', strtotime($dueDate)) : 'No due date') . '</td>';
            echo '<td>' . ($completedDate ? date('Y-m-d', strtotime($completedDate)) : 'Not completed') . '</td>';
            echo '<td>' . ($wo['woTaskStatus'] ?? 'Unknown') . '</td>';
            echo '<td>' . $onTime . '</td>';
            echo '<td>' . $selfFinding . '</td>';
            echo '<td>Site ' . ($wo['siteId'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p style="color: red;">No Work Order tasks found!</p>';
    }
    
    // Summary of source data
    echo '<div class="section config">';
    echo '<h4>📊 Source Data Summary</h4>';
    $totalPpm = Class_db::getInstance()->db_count('ppm_task');
    $totalWo = Class_db::getInstance()->db_count('wo_task');
    $completedPpm = Class_db::getInstance()->db_count('ppm_task', array('ppm_task_time_serviced' => '>1970-01-01'));
    $completedWo = Class_db::getInstance()->db_count('wo_task', array('wo_task_time_executed' => '>1970-01-01'));
    
    echo '<table>';
    echo '<tr><th>Task Type</th><th>Total</th><th>Completed</th><th>Completion Rate</th></tr>';
    echo '<tr><td>PPM Tasks</td><td>' . $totalPpm . '</td><td>' . $completedPpm . '</td><td>' . ($totalPpm > 0 ? round(($completedPpm/$totalPpm)*100, 1) : 0) . '%</td></tr>';
    echo '<tr><td>Work Orders</td><td>' . $totalWo . '</td><td>' . $completedWo . '</td><td>' . ($totalWo > 0 ? round(($completedWo/$totalWo)*100, 1) : 0) . '%</td></tr>';
    echo '<tr style="font-weight: bold;"><td>TOTAL</td><td>' . ($totalPpm + $totalWo) . '</td><td>' . ($completedPpm + $completedWo) . '</td><td>' . (($totalPpm + $totalWo) > 0 ? round((($completedPpm + $completedWo)/($totalPpm + $totalWo))*100, 1) : 0) . '%</td></tr>';
    echo '</table>';
    echo '<p><strong>Note:</strong> This source data is processed by the gamification system to calculate the weekly points shown in the sections above.</p>';
    echo '</div>';
    
    echo '</div>';
    
    // Get ALL weekly data for complete table
    $allWeeklyData = Class_db::getInstance()->db_select2('gmi_weekly', array(), 'gmw_year DESC, gmw_week DESC, user_id ASC');
    
    echo '<div class="section data">';
    echo '<h3>📋 Complete Data Table - All Gamification Records</h3>';
    
    if (!empty($allWeeklyData)) {
        echo '<p>Total records in gmi_weekly table: <strong>' . count($allWeeklyData) . '</strong></p>';
        
        echo '<table>';
        echo '<tr>';
        echo '<th>User ID</th>';
        echo '<th>Year</th>';
        echo '<th>Week</th>';
        echo '<th>PPM Tasks</th>';
        echo '<th>WO Tasks</th>';
        echo '<th>Total Tasks</th>';
        echo '<th>Completed</th>';
        echo '<th>On Time</th>';
        echo '<th>Completion %</th>';
        echo '<th>On Time %</th>';
        echo '<th>Productivity %</th>';
        echo '<th>Points (Before)</th>';
        echo '<th>Points (Final)</th>';
        echo '<th>MBV</th>';
        echo '<th>Tier</th>';
        echo '</tr>';
        
        // Variables for summary calculations
        $totalRecords = 0;
        $totalTasks = 0;
        $totalCompleted = 0;
        $totalOnTime = 0;
        $totalPointsBefore = 0;
        $totalPointsFinal = 0;
        $totalMbv = 0;
        $userCounts = array();
        $yearWeekCombos = array();
        
        foreach ($allWeeklyData as $data) {
            $taskSum = $data['gmwPpmTotal'] + $data['gmwWoTotal'];
            $completedSum = $data['gmwPpmCompleted'] + $data['gmwWoCompleted'];
            $onTimeSum = $data['gmwPpmOnTime'] + $data['gmwWoOnTime'];
            
            $completionPercent = $taskSum > 0 ? round(($completedSum / $taskSum) * 100, 1) : 0;
            $onTimePercent = $taskSum > 0 ? round(($onTimeSum / $taskSum) * 100, 1) : 0;
            $productivityPercent = round($data['gmwProductivityLevel'], 1);
            
            // Highlight current user if filtered
            $rowClass = ($userId && $data['userId'] == $userId) ? ' style="background-color: #ffffcc;"' : '';
            
            echo '<tr' . $rowClass . '>';
            echo '<td>' . $data['userId'] . '</td>';
            echo '<td>' . $data['gmwYear'] . '</td>';
            echo '<td>' . $data['gmwWeek'] . '</td>';
            echo '<td>' . $data['gmwPpmTotal'] . '</td>';
            echo '<td>' . $data['gmwWoTotal'] . '</td>';
            echo '<td><strong>' . $taskSum . '</strong></td>';
            echo '<td>' . $completedSum . '</td>';
            echo '<td>' . $onTimeSum . '</td>';
            echo '<td>' . $completionPercent . '%</td>';
            echo '<td>' . $onTimePercent . '%</td>';
            echo '<td>' . $productivityPercent . '%</td>';
            echo '<td>' . round($data['gmwPointBeforeMinus']) . '</td>';
            echo '<td><strong>' . round($data['gmwPointTotal']) . '</strong></td>';
            echo '<td>' . round($data['gmwMbv'], 2) . '</td>';
            echo '<td>' . $data['gmwPpmTierName'] . '</td>';
            echo '</tr>';
            
            // Accumulate for summary
            $totalRecords++;
            $totalTasks += $taskSum;
            $totalCompleted += $completedSum;
            $totalOnTime += $onTimeSum;
            $totalPointsBefore += $data['gmwPointBeforeMinus'];
            $totalPointsFinal += $data['gmwPointTotal'];
            $totalMbv += $data['gmwMbv'];
            
            // Track unique users and year-week combinations
            if (!isset($userCounts[$data['userId']])) {
                $userCounts[$data['userId']] = 0;
            }
            $userCounts[$data['userId']]++;
            
            $yearWeek = $data['gmwYear'] . '-W' . $data['gmwWeek'];
            if (!isset($yearWeekCombos[$yearWeek])) {
                $yearWeekCombos[$yearWeek] = 0;
            }
            $yearWeekCombos[$yearWeek]++;
        }
        
        // Summary row
        $avgCompletionPercent = $totalTasks > 0 ? round(($totalCompleted / $totalTasks) * 100, 1) : 0;
        $avgOnTimePercent = $totalTasks > 0 ? round(($totalOnTime / $totalTasks) * 100, 1) : 0;
        $avgPointsBefore = $totalRecords > 0 ? round($totalPointsBefore / $totalRecords) : 0;
        $avgPointsFinal = $totalRecords > 0 ? round($totalPointsFinal / $totalRecords) : 0;
        $avgMbv = $totalRecords > 0 ? round($totalMbv / $totalRecords, 2) : 0;
        
        echo '<tr style="background-color: #e6f3ff; font-weight: bold; border-top: 3px solid #333;">';
        echo '<td colspan="3">TOTALS/AVERAGES</td>';
        echo '<td colspan="2">Total Tasks:</td>';
        echo '<td><strong>' . $totalTasks . '</strong></td>';
        echo '<td>' . $totalCompleted . '</td>';
        echo '<td>' . $totalOnTime . '</td>';
        echo '<td>' . $avgCompletionPercent . '%</td>';
        echo '<td>' . $avgOnTimePercent . '%</td>';
        echo '<td>Avg:</td>';
        echo '<td>' . $avgPointsBefore . '</td>';
        echo '<td><strong>' . $avgPointsFinal . '</strong></td>';
        echo '<td>' . $avgMbv . '</td>';
        echo '<td>' . count($userCounts) . ' users</td>';
        echo '</tr>';
        
        echo '</table>';
        
        // Summary statistics
        echo '<div class="section config">';
        echo '<h4>📊 Summary Statistics</h4>';
        echo '<table>';
        echo '<tr><th>Metric</th><th>Value</th><th>Details</th></tr>';
        echo '<tr><td>Total Records</td><td><strong>' . $totalRecords . '</strong></td><td>Individual week calculations</td></tr>';
        echo '<tr><td>Unique Users</td><td><strong>' . count($userCounts) . '</strong></td><td>' . implode(', ', array_keys($userCounts)) . '</td></tr>';
        echo '<tr><td>Date Range</td><td><strong>' . count($yearWeekCombos) . '</strong> weeks</td><td>' . min(array_keys($yearWeekCombos)) . ' to ' . max(array_keys($yearWeekCombos)) . '</td></tr>';
        echo '<tr><td>Total Tasks Processed</td><td><strong>' . $totalTasks . '</strong></td><td>All PPM + WO tasks</td></tr>';
        echo '<tr><td>Overall Completion Rate</td><td><strong>' . $avgCompletionPercent . '%</strong></td><td>' . $totalCompleted . ' completed out of ' . $totalTasks . '</td></tr>';
        echo '<tr><td>Overall On-Time Rate</td><td><strong>' . $avgOnTimePercent . '%</strong></td><td>' . $totalOnTime . ' on time out of ' . $totalTasks . '</td></tr>';
        echo '<tr><td>Average Points (Final)</td><td><strong>' . $avgPointsFinal . '</strong></td><td>After all calculations and penalties</td></tr>';
        echo '<tr><td>Total Points Awarded</td><td><strong>' . round($totalPointsFinal) . '</strong></td><td>Sum of all final points</td></tr>';
        echo '<tr><td>Average MBV</td><td><strong>' . $avgMbv . '</strong></td><td>Merit-Based Value across all records</td></tr>';
        echo '</table>';
        
        // Most active users
        if (count($userCounts) > 0) {
            arsort($userCounts);
            echo '<h4>👥 Most Active Users</h4>';
            echo '<table>';
            echo '<tr><th>User ID</th><th>Records Count</th><th>Percentage</th></tr>';
            foreach (array_slice($userCounts, 0, 5, true) as $user => $count) {
                $percentage = round(($count / $totalRecords) * 100, 1);
                echo '<tr><td>User ' . $user . '</td><td>' . $count . '</td><td>' . $percentage . '%</td></tr>';
            }
            echo '</table>';
        }
        
        echo '</div>';
        
    } else {
        echo '<p style="color: red;">No data found in gmi_weekly table!</p>';
    }
    
    echo '</div>';
    
    // Get existing weekly data for detailed analysis (existing section)
    $whereClause = array();
    if ($userId) {
        $whereClause['user_id'] = $userId;
    }
    
    $weeklyData = Class_db::getInstance()->db_select2('gmi_weekly', $whereClause, '', 10);
    
    echo '<div class="section data">';
    echo '<h3>� Detailed Analysis - Individual Record Breakdown</h3>';
    
    if (!empty($weeklyData)) {
        echo '<p>Found ' . count($weeklyData) . ' weekly calculations</p>';
        
        foreach ($weeklyData as $data) {
            echo '<div class="calculation">';
            echo '<h4>👤 User ' . $data['userId'] . ' - Year ' . $data['gmwYear'] . ', Week ' . $data['gmwWeek'] . '</h4>';
            
            echo '<table>';
            echo '<tr><th>Metric</th><th>PPM</th><th>WO</th><th>Total</th></tr>';
            echo '<tr><td>Total Tasks</td><td>' . $data['gmwPpmTotal'] . '</td><td>' . $data['gmwWoTotal'] . '</td><td>' . ($data['gmwPpmTotal'] + $data['gmwWoTotal']) . '</td></tr>';
            echo '<tr><td>Completed</td><td>' . $data['gmwPpmCompleted'] . '</td><td>' . $data['gmwWoCompleted'] . '</td><td>' . ($data['gmwPpmCompleted'] + $data['gmwWoCompleted']) . '</td></tr>';
            echo '<tr><td>On Time</td><td>' . $data['gmwPpmOnTime'] . '</td><td>' . $data['gmwWoOnTime'] . '</td><td>' . ($data['gmwPpmOnTime'] + $data['gmwWoOnTime']) . '</td></tr>';
            echo '<tr><td>Late</td><td>' . $data['gmwPpmLate'] . '</td><td>' . $data['gmwWoLate'] . '</td><td>' . ($data['gmwPpmLate'] + $data['gmwWoLate']) . '</td></tr>';
            echo '</table>';
            
            echo '<h5>🧮 Point Calculation Breakdown</h5>';
            echo '<table>';
            echo '<tr><th>Point Type</th><th>Calculated Points</th><th>Formula Used</th></tr>';
            
            $totalTasks = $data['gmwPpmTotal'] + $data['gmwWoTotal'];
            $totalCompleted = $data['gmwPpmCompleted'] + $data['gmwWoCompleted'];
            $completionRate = $totalTasks > 0 ? ($totalCompleted / $totalTasks) : 0;
            
            echo '<tr class="highlight"><td>Completion Points</td><td><strong>' . round($data['gmwPointCompleted']) . '</strong></td><td class="formula">(' . $totalCompleted . '/' . $totalTasks . ') × ' . $config['weight_completed'] . ' × ' . $config['point_scale_factor'] . ' = ' . round($completionRate * $config['weight_completed'] * $config['point_scale_factor']) . '</td></tr>';
            echo '<tr class="highlight"><td>On-Time Points</td><td><strong>' . round($data['gmwPointOnTime']) . '</strong></td><td class="formula">Calculated using weight_ontime: ' . $config['weight_ontime'] . '</td></tr>';
            echo '<tr><td>Late Penalty</td><td><strong>' . round($data['gmwPointLate']) . '</strong></td><td class="formula">Late penalty applied</td></tr>';
            echo '<tr><td>Self Finding</td><td><strong>' . round($data['gmwPointSelfFinding']) . '</strong></td><td class="formula">' . $data['gmwWoSelfFinding'] . ' × ' . $config['self_finding_points'] . '</td></tr>';
            echo '<tr><td colspan="3"><hr></td></tr>';
            echo '<tr><td><strong>Before Productivity</strong></td><td><strong>' . round($data['gmwPointBeforeMinus']) . '</strong></td><td>Sum of all points</td></tr>';
            echo '<tr><td><strong>Productivity Deduction</strong></td><td><strong>-' . round($data['gmwPointLessProductive']) . '</strong></td><td>Based on ' . round($data['gmwProductivityLevel'], 2) . '% productivity</td></tr>';
            echo '<tr><td><strong>FINAL TOTAL</strong></td><td><strong>' . round($data['gmwPointTotal']) . '</strong></td><td>Points after all calculations</td></tr>';
            echo '</table>';
            
            echo '<p><strong>MBV (Merit-Based Value):</strong> ' . $data['gmwMbv'] . '</p>';
            echo '<p><strong>Tier:</strong> ' . $data['gmwPpmTierName'] . ' (' . $data['gmwTierPoint'] . ' multiplier)</p>';
            
            echo '</div>';
        }
        
        echo '<div class="section">';
        echo '<h3>💡 How to See Weight Changes in Action</h3>';
        echo '<ol>';
        echo '<li><strong>Current weights:</strong> completion=' . $config['weight_completed'] . ', ontime=' . $config['weight_ontime'] . '</li>';
        echo '<li><strong>To see different weights:</strong> Change the configuration and run the monthly calculation again</li>';
        echo '<li><strong>The formulas above show</strong> how completion and on-time rates are multiplied by these weights</li>';
        echo '<li><strong>Higher completion weight</strong> = more points for task completion</li>';
        echo '<li><strong>Higher on-time weight</strong> = more points for timely completion</li>';
        echo '</ol>';
        echo '</div>';
        
    } else {
        echo '<p style="color: red;">No weekly calculation data found!</p>';
        echo '<p>This means either:</p>';
        echo '<ul>';
        echo '<li>The runMonthly calculation hasn\'t been executed yet</li>';
        echo '<li>No task data exists for processing</li>';
        echo '<li>The gamification system needs to be triggered</li>';
        echo '</ul>';
    }
    
    echo '</div>';
    
    // Source Data Section
    echo '<div class="section">';
    echo '<h2>📊 Source Data Used in Calculations</h2>';
    echo '<p>This shows the <strong>exact same queries and data</strong> that runMonthly() uses for gamification calculations.</p>';
    
    // Get the current calculation period from the most recent gmi_weekly entry
    $currentPeriod = Class_db::getInstance()->db_select('gmi_weekly', array(), 'gmw_year DESC, gmw_week DESC', '1');
    $currentYear = !empty($currentPeriod) ? $currentPeriod[0]['gmw_year'] : date('Y');
    $currentMonth = date('n'); // Use current month since we don't store month in gmi_weekly
    $currentWeek = !empty($currentPeriod) ? $currentPeriod[0]['gmw_week'] : 1;
    
    echo "<p><strong>Current Calculation Period:</strong> Year $currentYear, Month $currentMonth, Week $currentWeek</p>";
    
    // Calculate the week date range using the exact same logic as runMonthly
    $firstDay = new DateTime("$currentYear-$currentMonth-01");
    $lastDay = new DateTime($firstDay->format('Y-m-t'));
    
    // Calculate week start (Monday) and end (Sunday) - same as getWeekDateRange()
    $weekStart = clone $firstDay;
    $weekStart->modify('+' . (($currentWeek - 1) * 7) . ' days');
    
    // Adjust to start of week (Monday)
    $dayOfWeek = $weekStart->format('N'); // 1 = Monday, 7 = Sunday
    if ($dayOfWeek != 1) {
        $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
    }
    
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+6 days'); // Sunday
    
    // Ensure we don't go beyond the month boundaries
    if ($weekStart < $firstDay) {
        $weekStart = $firstDay;
    }
    if ($weekEnd > $lastDay) {
        $weekEnd = $lastDay;
    }
    
    $weekStartDate = $weekStart->format('Y-m-d');
    $weekEndDate = $weekEnd->format('Y-m-d');
    
    echo "<p><strong>Date Range (exact calculation):</strong> $weekStartDate to $weekEndDate</p>";
    
    // PPM data - EXACT same query as runMonthly uses
    echo "<h3>PPM Data (vw_gamification_ppm_daily)</h3>";
    echo "<p><em>Exact same query: Class_db::getInstance()->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, array('dateStart'=>'$weekStartDate', 'dateEnd'=>'$weekEndDate'))</em></p>";
    try {
        $gmiPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, 
            array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
        
        if (!empty($gmiPpm)) {
            echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
            echo "<tr><th>User ID (ppmTaskAssignedTo)</th><th>Site ID</th><th>PPM Group ID</th><th>PPM Total</th><th>PPM Completed</th><th>PPM On Time</th><th>PPM Late</th><th>PPM Within</th></tr>";
            foreach ($gmiPpm as $ppm) {
                echo "<tr>";
                echo "<td>" . ($ppm['ppmTaskAssignedTo'] ?? '') . "</td>";
                echo "<td>" . ($ppm['siteId'] ?? '') . "</td>";
                echo "<td>" . ($ppm['ppmGroupId'] ?? '') . "</td>";
                echo "<td>" . ($ppm['ppmTotal'] ?? 0) . "</td>";
                echo "<td>" . ($ppm['ppmCompleted'] ?? 0) . "</td>";
                echo "<td>" . ($ppm['ppmOnTime'] ?? 0) . "</td>";
                echo "<td>" . ($ppm['ppmLate'] ?? 0) . "</td>";
                echo "<td>" . ($ppm['ppmWithin'] ?? 0) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Found " . count($gmiPpm) . " PPM records</strong> - These feed directly into gmwPpmTotal, gmwPpmCompleted, etc.</p>";
        } else {
            echo "<p style='color: orange;'>No PPM data found for current period</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error loading PPM data: " . $e->getMessage() . "</p>";
    }
    
    // PPM Assist data - EXACT same query as runMonthly uses  
    echo "<h3>PPM Assist Data (vw_gamification_ppm_assist_daily)</h3>";
    echo "<p><em>Exact same query: Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_daily', array(), '', '', 0, array('dateStart'=>'$weekStartDate', 'dateEnd'=>'$weekEndDate'))</em></p>";
    try {
        $gmiPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_daily', array(), '', '', 0, 
            array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
        
        if (!empty($gmiPpmAssist)) {
            echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
            echo "<tr><th>User ID</th><th>Site ID</th><th>PPM Group ID</th><th>PPM Total</th><th>PPM Completed</th><th>PPM On Time</th><th>PPM Late</th><th>PPM Within</th></tr>";
            foreach ($gmiPpmAssist as $assist) {
                echo "<tr>";
                echo "<td>" . ($assist['userId'] ?? '') . "</td>";
                echo "<td>" . ($assist['siteId'] ?? '') . "</td>";
                echo "<td>" . ($assist['ppmGroupId'] ?? '') . "</td>";
                echo "<td>" . ($assist['ppmTotal'] ?? 0) . "</td>";
                echo "<td>" . ($assist['ppmCompleted'] ?? 0) . "</td>";
                echo "<td>" . ($assist['ppmOnTime'] ?? 0) . "</td>";
                echo "<td>" . ($assist['ppmLate'] ?? 0) . "</td>";
                echo "<td>" . ($assist['ppmWithin'] ?? 0) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Found " . count($gmiPpmAssist) . " PPM assist records</strong> - These add to gmwPpmAssist and also to total counts</p>";
        } else {
            echo "<p style='color: gray;'>No PPM assist data for current period</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error loading PPM assist data: " . $e->getMessage() . "</p>";
    }
    
    // WO data - EXACT same query as runMonthly uses
    echo "<h3>WO Data (vw_gamification_wo_daily)</h3>";
    echo "<p><em>Exact same query: Class_db::getInstance()->db_select2('vw_gamification_wo_daily', array(), '', '', 0, array('dateStart'=>'$weekStartDate', 'dateEnd'=>'$weekEndDate'))</em></p>";
    try {
        $gmiWo = Class_db::getInstance()->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
            array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
        
        if (!empty($gmiWo)) {
            echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
            echo "<tr><th>User ID (woTaskAssignedTo)</th><th>Site ID</th><th>PPM Group ID</th><th>WO Total</th><th>WO Completed</th><th>WO On Time</th><th>WO Late</th><th>WO Self Finding</th></tr>";
            foreach ($gmiWo as $wo) {
                echo "<tr>";
                echo "<td>" . ($wo['woTaskAssignedTo'] ?? '') . "</td>";
                echo "<td>" . ($wo['siteId'] ?? '') . "</td>";
                echo "<td>" . ($wo['ppmGroupId'] ?? '') . "</td>";
                echo "<td>" . ($wo['woTotal'] ?? 0) . "</td>";
                echo "<td>" . ($wo['woCompleted'] ?? 0) . "</td>";
                echo "<td>" . ($wo['woOnTime'] ?? 0) . "</td>";
                echo "<td>" . ($wo['woLate'] ?? 0) . "</td>";
                echo "<td>" . ($wo['woSelfFinding'] ?? 0) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Found " . count($gmiWo) . " WO records</strong> - These feed directly into gmwWoTotal, gmwWoCompleted, etc.</p>";
        } else {
            echo "<p style='color: orange;'>No WO data found for current period</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error loading WO data: " . $e->getMessage() . "</p>";
    }
    
    // WO Assist data - EXACT same query as runMonthly uses
    echo "<h3>WO Assist Data (vw_gamification_wo_assist_daily)</h3>";
    echo "<p><em>Exact same query: Class_db::getInstance()->db_select2('vw_gamification_wo_assist_daily', array(), '', '', 0, array('dateStart'=>'$weekStartDate', 'dateEnd'=>'$weekEndDate'))</em></p>";
    try {
        $gmiWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_daily', array(), '', '', 0, 
            array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
        
        if (!empty($gmiWoAssist)) {
            echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
            echo "<tr><th>User ID</th><th>Site ID</th><th>PPM Group ID</th><th>WO Total</th><th>WO Completed</th><th>WO On Time</th><th>WO Late</th></tr>";
            foreach ($gmiWoAssist as $assist) {
                echo "<tr>";
                echo "<td>" . ($assist['userId'] ?? '') . "</td>";
                echo "<td>" . ($assist['siteId'] ?? '') . "</td>";
                echo "<td>" . ($assist['ppmGroupId'] ?? '') . "</td>";
                echo "<td>" . ($assist['woTotal'] ?? 0) . "</td>";
                echo "<td>" . ($assist['woCompleted'] ?? 0) . "</td>";
                echo "<td>" . ($assist['woOnTime'] ?? 0) . "</td>";
                echo "<td>" . ($assist['woLate'] ?? 0) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Found " . count($gmiWoAssist) . " WO assist records</strong> - These add to gmwWoAssist totals</p>";
        } else {
            echo "<p style='color: gray;'>No WO assist data for current period</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error loading WO assist data: " . $e->getMessage() . "</p>";
    }
    
    // Trade Ratio Calculation Demo
    echo "<h3>⚖️ Trade Ratio Calculations in Action</h3>";
    echo "<p>This shows exactly how trade ratios are applied to the source data above.</p>";
    
    try {
        // Get current trade ratio configuration - same method as gamification_config.php
        $tradeRatios = array();
        $allConfigs = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'));
        
        foreach ($allConfigs as $config) {
            // Filter for trade_ratio keys manually (same as API does)
            if (strpos($config['configKey'], 'trade_ratio') === 0) {
                $tradeRatios[$config['configKey']] = $config['configValue'];
            }
        }
        
        // Show trade ratio configuration
        echo "<h4>📊 Current Trade Ratio Configuration</h4>";
        echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
        echo "<tr><th>PPM Group ID / Trade Type</th><th>Trade Ratio Multiplier</th><th>Effect on Task Counts</th></tr>";
        foreach ($tradeRatios as $key => $ratio) {
            $effect = floatval($ratio) > 1 ? 'Increases task value' : ($ratio < 1 ? 'Reduces task value' : 'No change');
            echo "<tr><td>$key</td><td><strong>$ratio</strong></td><td>$effect</td></tr>";
        }
        echo "</table>";
        
        // Apply trade ratios to actual data and show the transformation
        echo "<h4>🔄 Data Transformation with Trade Ratios</h4>";
        echo "<p>This shows how the raw counts are multiplied by trade ratios:</p>";
        
        if (!empty($gmiPpm)) {
            echo "<h5>PPM Data Transformation:</h5>";
            echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
            echo "<tr><th>User ID</th><th>PPM Group</th><th>Raw Total</th><th>Trade Ratio</th><th>Final Total</th><th>Raw Completed</th><th>Final Completed</th><th>Raw On Time</th><th>Final On Time</th></tr>";
            
            foreach ($gmiPpm as $ppm) {
                $ppmGroupId = intval($ppm['ppmGroupId'] ?? 0);
                $tradeRatioKey = "trade_ratio_group_$ppmGroupId";
                $tradeRatio = isset($tradeRatios[$tradeRatioKey]) ? floatval($tradeRatios[$tradeRatioKey]) : 1.0;
                
                $rawTotal = intval($ppm['ppmTotal']);
                $rawCompleted = intval($ppm['ppmCompleted']);
                $rawOnTime = intval($ppm['ppmOnTime']);
                
                $finalTotal = round($rawTotal * $tradeRatio);
                $finalCompleted = round($rawCompleted * $tradeRatio);
                $finalOnTime = round($rawOnTime * $tradeRatio);
                
                echo "<tr>";
                echo "<td>" . $ppm['ppmTaskAssignedTo'] . "</td>";
                echo "<td>Group $ppmGroupId</td>";
                echo "<td>$rawTotal</td>";
                echo "<td><strong>$tradeRatio</strong></td>";
                echo "<td><strong>$finalTotal</strong></td>";
                echo "<td>$rawCompleted</td>";
                echo "<td><strong>$finalCompleted</strong></td>";
                echo "<td>$rawOnTime</td>";
                echo "<td><strong>$finalOnTime</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        if (!empty($gmiWo)) {
            echo "<h5>WO Data Transformation:</h5>";
            echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
            echo "<tr><th>User ID</th><th>PPM Group</th><th>Raw Total</th><th>Trade Ratio</th><th>Final Total</th><th>Raw Completed</th><th>Final Completed</th><th>Raw On Time</th><th>Final On Time</th></tr>";
            
            foreach ($gmiWo as $wo) {
                $ppmGroupId = intval($wo['ppmGroupId'] ?? 0);
                $tradeRatioKey = "trade_ratio_group_$ppmGroupId";
                $tradeRatio = isset($tradeRatios[$tradeRatioKey]) ? floatval($tradeRatios[$tradeRatioKey]) : 1.0;
                
                $rawTotal = intval($wo['woTotal']);
                $rawCompleted = intval($wo['woCompleted']);
                $rawOnTime = intval($wo['woOnTime']);
                
                $finalTotal = round($rawTotal * $tradeRatio);
                $finalCompleted = round($rawCompleted * $tradeRatio);
                $finalOnTime = round($rawOnTime * $tradeRatio);
                
                echo "<tr>";
                echo "<td>" . $wo['woTaskAssignedTo'] . "</td>";
                echo "<td>Group $ppmGroupId</td>";
                echo "<td>$rawTotal</td>";
                echo "<td><strong>$tradeRatio</strong></td>";
                echo "<td><strong>$finalTotal</strong></td>";
                echo "<td>$rawCompleted</td>";
                echo "<td><strong>$finalCompleted</strong></td>";
                echo "<td>$rawOnTime</td>";
                echo "<td><strong>$finalOnTime</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show the exact code logic
        echo "<h4>💻 Exact Code Logic from runMonthly()</h4>";
        echo "<div style='background-color: #f5f5f5; padding: 10px; font-family: monospace; border: 1px solid #ccc;'>";
        echo "// For each PPM record:<br>";
        echo "\$userId = intval(\$ppm['ppmTaskAssignedTo']);<br>";
        echo "\$ppmGroupId = intval(\$ppm['ppmGroupId'] ?? 0);<br>";
        echo "\$tradeRatio = \$this->getTradeRatio(\$ppmGroupId);<br>";
        echo "<br>";
        echo "// Apply trade ratio to counts:<br>";
        echo "\$gmiWeekly[\$userId]['gmwPpmTotal'] += round(intval(\$ppm['ppmTotal']) * \$tradeRatio);<br>";
        echo "\$gmiWeekly[\$userId]['gmwPpmCompleted'] += round(intval(\$ppm['ppmCompleted']) * \$tradeRatio);<br>";
        echo "\$gmiWeekly[\$userId]['gmwPpmOnTime'] += round(intval(\$ppm['ppmOnTime']) * \$tradeRatio);<br>";
        echo "</div>";
        
        echo "<h4>🎯 Key Trade Ratio Insights:</h4>";
        echo "<ul>";
        echo "<li><strong>Trade ratios are applied to ALL counts</strong> - total, completed, on-time, late, etc.</li>";
        echo "<li><strong>Each PPM Group has its own multiplier</strong> - different trade types get different weights</li>";
        echo "<li><strong>Default trade ratio is 1.0</strong> - groups without specific ratios are unchanged</li>";
        echo "<li><strong>Results are rounded</strong> - round() function ensures integer counts</li>";
        echo "<li><strong>This affects final points</strong> - more weighted tasks = higher point potential</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error showing trade ratio calculations: " . $e->getMessage() . "</p>";
    }

    // Summary of actual calculation inputs using exact variable names from runMonthly
    echo "<h3>📈 Exact Data Processing Summary</h3>";
    try {
        echo "<table border='1' style='margin: 10px 0; border-collapse: collapse;'>";
        echo "<tr><th>Variable in runMonthly()</th><th>Data Source</th><th>Records Found</th><th>Purpose in Calculation</th></tr>";
        echo "<tr><td>\$gmiPpm</td><td>vw_gamification_ppm_daily</td><td>" . count($gmiPpm ?? []) . "</td><td>Main PPM task data → gmwPpmTotal, gmwPpmCompleted, etc.</td></tr>";
        echo "<tr><td>\$gmiPpmAssist</td><td>vw_gamification_ppm_assist_daily</td><td>" . count($gmiPpmAssist ?? []) . "</td><td>PPM assist data → adds to gmwPpmAssist + all PPM totals</td></tr>";
        echo "<tr><td>\$gmiWo</td><td>vw_gamification_wo_daily</td><td>" . count($gmiWo ?? []) . "</td><td>Main WO task data → gmwWoTotal, gmwWoCompleted, etc.</td></tr>";
        echo "<tr><td>\$gmiWoAssist</td><td>vw_gamification_wo_assist_daily</td><td>" . count($gmiWoAssist ?? []) . "</td><td>WO assist data → adds to gmwWoAssist totals</td></tr>";
        echo "</table>";
        
        echo "<p><strong>� Exact Processing Flow from runMonthly():</strong></p>";
        echo "<ol>";
        echo "<li><strong>Date Range Calculation:</strong> getWeekDateRange(\$year, \$month, \$week) → <code>$weekStartDate to $weekEndDate</code></li>";
        echo "<li><strong>Data Queries:</strong> Each db_select2() call with dateStart/dateEnd parameters</li>";
        echo "<li><strong>User Aggregation:</strong> \$userId = intval(\$ppm['ppmTaskAssignedTo']) - groups by user</li>";
        echo "<li><strong>Trade Ratio Application:</strong> \$tradeRatio = \$this->getTradeRatio(\$ppmGroupId) - multiplies counts</li>";
        echo "<li><strong>Weekly Accumulation:</strong> \$gmiWeekly[\$userId]['gmwPpmTotal'] += round(intval(\$ppm['ppmTotal']) * \$tradeRatio)</li>";
        echo "<li><strong>Point Calculation:</strong> Counts converted to points using configuration weights</li>";
        echo "<li><strong>Storage:</strong> Results stored in gmi_weekly table</li>";
        echo "</ol>";
        
        echo "<p><strong>🎯 Key Insights:</strong></p>";
        echo "<ul>";
        echo "<li><strong>These are the exact 4 queries</strong> that runMonthly() executes</li>";
        echo "<li><strong>Date filtering</strong> uses the precise getWeekDateRange() logic</li>";
        echo "<li><strong>Trade ratios</strong> are applied to each PPM group before aggregation</li>";
        echo "<li><strong>User aggregation</strong> groups data by ppmTaskAssignedTo/woTaskAssignedTo</li>";
        echo "<li><strong>This filtered/processed data</strong> becomes the gmi_weekly scores shown above</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error calculating summary: " . $e->getMessage() . "</p>";
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<h3>❌ Error</h3>';
    echo '<p>Error: ' . $e->getMessage() . '</p>';
    echo '</div>';
}

?>

</body>
</html>
