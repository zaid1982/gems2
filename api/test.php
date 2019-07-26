<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';



$pricearray = Array
(
    Array
    (
        'price' => 1,
        'userId' => '14'
    ),
    Array
    (
        'price' => 4,
        'userId' => '15'
    ),
    Array
    (
        'price' => 0,
        'userId' => '16'
    )
);
$counts = array_column($pricearray, 'price');
$index = array_search(min($counts), $counts, true);
$technician = $pricearray[$index]['userId'];
$pricearray[$index]['price']++;
echo $index.'<br/>';
var_dump($pricearray);


$counts = array_column($pricearray, 'price');
$index = array_search(min($counts), $counts, true);
$technician = $pricearray[$index]['userId'];
$pricearray[$index]['price']++;
echo $index.'<br/>';
var_dump($pricearray);


$counts = array_column($pricearray, 'price');
$index = array_search(min($counts), $counts, true);
$technician = $pricearray[$index]['userId'];
$pricearray[$index]['price']++;
echo $index.'<br/>';
var_dump($pricearray);
echo '<br/>-----------------<br/>';



$minPrice = min(array_column($pricearray, 'price'));
echo $minPrice.'<br/>-----------------<br/>';

$tempDays[0] = '2019-07-11';
$currentMonth = array('year'=>substr($tempDays[0], 0, 4), 'month'=>strval(intval(substr($tempDays[0], 5, 2))));
var_dump($currentMonth);
echo '<br/>-----------------<br/>';

$technicians = array('15', '16');
$technicianKpi = array();
foreach ($technicians as $technician) {
    array_push($technicianKpi, array('userId'=>$technician, 'total'=>0));
}
var_dump($technicianKpi);
echo '<br/>-----------------<br/>';

$fn_general = new Class_general();

Class_db::getInstance()->db_connect();

$technicians = array('15','16');
$rows = Class_db::getInstance()->db_select('vw_technicians_ppm_monthly', array(), null, null, 0, array('technicians'=>implode(',',$technicians)));



echo '-----------------<br/>';
$neededKeys1 = array_keys(array_column($rows, 'ppm_year'), '2019');
foreach ($neededKeys1 as $neededKey) {
    echo $neededKey.'<br/>';
    //$rows[$neededKey]['total']++;
}
echo '-----------------<br/>';
$neededKeys2 = array_keys(array_column($rows, 'ppm_month'), '7');
foreach ($neededKeys2 as $neededKey) {
    echo $neededKey.'<br/>';
    //$rows[$neededKey]['total']++;
}

echo '-----------------<br/>';
$tests = array_intersect(array_keys(array_column($rows, 'ppm_year'), '2019'), array_keys(array_column($rows, 'ppm_month'), '7'));
foreach ($tests as $test) {
    echo $test.'<br/>';
}

echo '-----------------<br/>';
foreach ($rows as $row) {
    echo $row['ppm_year'].', '.$row['ppm_month'].', '.$row['ppm_task_assigned_to'].', '.$row['total'].'<br/>';
}
Class_db::getInstance()->db_close();