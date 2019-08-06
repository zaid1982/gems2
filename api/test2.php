<?php
$begin = new DateTime('2019-01-01');
$end = new DateTime('2019-03-31');

echo($begin->format("D").'</br>');
$begin->modify( '+1 week' );
$begin->modify( '-1 day' );
$end->modify( '+1 day' );
//var_dump($begin);
echo($begin->format("Y-m-d").'</br>');
echo($end->format("Y-m-d").'</br>');

echo('--------------------------</br>');
$interval = new DateInterval('P1W');
$dateRange = new DatePeriod($begin, $interval, $end);
//var_dump($dateRange);
foreach($dateRange as $dates){
    //echo($dates->format("Y-m-d").'</br>');
    //echo($dates->format("D").'</br>');
    if ($dates->format("D") == 'Mon') {
        $dates->modify( '+6 day' );
    } else if ($dates->format("D") == 'Tue') {
        $dates->modify( '+5 day' );
    } else if ($dates->format("D") == 'Wed') {
        $dates->modify( '+4 day' );
    } else if ($dates->format("D") == 'Thu') {
        $dates->modify( '+3 day' );
    } else if ($dates->format("D") == 'Fri') {
        $dates->modify( '+2 day' );
    } else if ($dates->format("D") == 'Sat') {
        $dates->modify( '+1 day' );
    }
    echo($dates->format("Y-m-d").'</br>');
    $dates->modify( '-6 day' );
    echo($dates->format("Y-m-d").'</br>');
    echo($dates->format("D").'</br>');
}


echo('--------------------------</br>');