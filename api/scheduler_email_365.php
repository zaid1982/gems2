<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_email.php';

$api_name = 'schedule_email_notification_365';
$is_transaction = false;

$fn_general = new Class_general();
$fn_email = new Class_email();

try {
    $fn_email->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $fn_general->log_debug('API', $api_name, __LINE__, 'Starting send_email_365 run');
    $fn_email->send_email_365();
    $fn_general->log_debug('API', $api_name, __LINE__, 'Completed send_email_365 run');
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    Class_db::getInstance()->db_close();
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}
