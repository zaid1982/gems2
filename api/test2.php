<?php
$begin = new DateTime('2019-01-01');
$end = new DateTime('2021-12-31');

echo($begin->format("D").'</br>');
$begin->modify( '+1 year' );
$end->modify( '+2 day' );
//var_dump($begin);
echo($begin->format("Y-m-d").'</br>');
echo($end->format("Y-m-d").'</br>');

echo('--------------------------</br>');
$interval = new DateInterval('P1Y');
$dateRange = new DatePeriod($begin, $interval, $end);
//var_dump($dateRange);
foreach($dateRange as $dates){
    $dates->modify('-1 day');
    echo($dates->format("Y-m-d").'</br>');
    $dates->modify('-1 year');
    $dates->modify('+1 day');
    echo($dates->format("Y-m-d").'</br>');
    echo('***********</br>');
    //echo($dates->format("D").'</br>');
}


echo('--------------------------</br>');