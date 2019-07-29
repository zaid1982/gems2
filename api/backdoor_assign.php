<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_email.php';
require_once 'function/f_task.php';
require_once 'function/f_ppm.php';

$fn_general = new Class_general();
$fn_email = new Class_email();
$fn_task = new Class_task();
$fn_ppm = new Class_ppm();
$api_name = 'backdoor_assign';
$is_transaction = false;

$fn_email->__set('fn_general', $fn_general);

Class_db::getInstance()->db_connect();
try {
    //Class_db::getInstance()->db_beginTransaction();

    echo '-----------------<br/>';
    $yearDates = $fn_ppm->get_dates_year('2019-01-01', '2021-12-31', '2020-09-01');
    foreach ($yearDates as $yearDate) {
        echo $yearDate.'<br/>';
    }

    //Class_db::getInstance()->db_commit();
} catch (Exception $ex) {
    Class_db::getInstance()->db_rollback();
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}
Class_db::getInstance()->db_close();