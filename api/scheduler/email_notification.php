<?php

require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_email.php';

$fn_general = new Class_general();
$fn_email = new Class_email();
$api_name = 'schedule_email_notification';
$is_transaction = false;

$fn_email->__set('fn_general', $fn_general);

try {
    Class_db::getInstance()->db_connect();
    $fn_email->send_email();
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    Class_db::getInstance()->db_close();
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}