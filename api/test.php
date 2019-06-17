<?php

function get_dates_day ($startDate, $endDate) {
    $newDates = array();
    $begin = new DateTime( $startDate );
    $end = new DateTime( $endDate );
    $end = $end->modify( '+1 day' );
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($begin, $interval ,$end);
    foreach($dateRange as $date){
        array_push($newDates, $date->format("Y-m-d"));
    }
    return $newDates;
}

function get_dates_week ($startDate, $endDate) {
    $newDates = array();
    $begin = new DateTime( $startDate );
    $begin = $begin->modify( '+1 week' );
    $begin = $begin->modify( '-1 day' );
    $end = new DateTime( $endDate );
    $end = $end->modify( '+1 day' );
    $interval = new DateInterval('P1W');
    $dateRange = new DatePeriod($begin, $interval ,$end);
    foreach($dateRange as $date){
        array_push($newDates, $date->format("Y-m-d"));
    }
    return $newDates;
}

function get_dates_month ($startDate, $endDate) {
    $newDates = array();
    $begin = new DateTime( $startDate );
    $begin = $begin->modify( '+1 month' );
    //$begin = $begin->modify( '-1 day' );
    $end = new DateTime( $endDate );
    $end = $end->modify( '+2 day' );
    $interval = new DateInterval('P1M');
    $dateRange = new DatePeriod($begin, $interval ,$end);
    foreach($dateRange as $date){
        $xx = $date->modify( '-1 day' );
        array_push($newDates, $xx->format("Y-m-d"));
    }
    return $newDates;
}

function get_dates_quarter ($startDate, $endDate) {
    $newDates = array();
    $begin = new DateTime( $startDate );
    $begin = $begin->modify( '+3 month' );
    //$begin = $begin->modify( '-1 day' );
    $end = new DateTime( $endDate );
    $end = $end->modify( '+2 day' );
    $interval = new DateInterval('P3M');
    $dateRange = new DatePeriod($begin, $interval ,$end);
    foreach($dateRange as $date){
        $xx = $date->modify( '-1 day' );
        array_push($newDates, $xx->format("Y-m-d"));
    }
    return $newDates;
}

function get_dates_year ($startDate, $endDate) {
    $newDates = array();
    $begin = new DateTime( $startDate );
    $begin = $begin->modify( '+1 year' );
    //$begin = $begin->modify( '-1 day' );
    $end = new DateTime( $endDate );
    $end = $end->modify( '+2 day' );
    $interval = new DateInterval('P1Y');
    $dateRange = new DatePeriod($begin, $interval ,$end);
    foreach($dateRange as $date){
        $xx = $date->modify( '-1 day' );
        array_push($newDates, $xx->format("Y-m-d"));
    }
    return $newDates;
}

$isYearly = true;
$isQuarterly = true;
$isMonthly = true;
$isWeekly = true;
$isDaily = false;

$ppmDateCycle = '2018-08-01';
$contractDateEnd = '2020-07-31';

$dailyDates = get_dates_day($ppmDateCycle, $contractDateEnd);
$weeklyDates = get_dates_week($ppmDateCycle, $contractDateEnd);
$monthlyDates = get_dates_month($ppmDateCycle, $contractDateEnd);
$quarterlyDates = get_dates_quarter($ppmDateCycle, $contractDateEnd);
$yearlyDates = get_dates_year($ppmDateCycle, $contractDateEnd);

$tempDays = array();
foreach($dailyDates as $dateStr){
    if ($isDaily) {
        array_push($tempDays, $dateStr);
    }
    if ($isWeekly && in_array($dateStr, $weeklyDates) && !in_array($dateStr, $tempDays)) {
        array_push($tempDays, $dateStr);
    }
    if ($isMonthly && in_array($dateStr, $monthlyDates) && !in_array($dateStr, $tempDays)) {
        array_push($tempDays, $dateStr);
    }
    if ($isQuarterly && in_array($dateStr, $quarterlyDates) && !in_array($dateStr, $tempDays)) {
        array_push($tempDays, $dateStr);
    }
    if ($isYearly && in_array($dateStr, $yearlyDates) && !in_array($dateStr, $tempDays)) {
        array_push($tempDays, $dateStr);
    }
}
echo count($dailyDates);
foreach($tempDays as $dateStr){
    if ($isWeekly && in_array($dateStr, $weeklyDates)) {
        echo 'W-';
    }
    if ($isMonthly && in_array($dateStr, $monthlyDates)) {
        echo 'M-';
    }
    if ($isQuarterly && in_array($dateStr, $quarterlyDates)) {
        echo 'Q-';
    }
    if ($isYearly && in_array($dateStr, $yearlyDates)) {
        echo 'Y-';
    }
    echo $dateStr . "<br>";
}