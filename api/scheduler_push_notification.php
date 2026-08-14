<?php

// Always run from api/ so relative config/log paths resolve (cron-safe).
chdir(__DIR__);

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_email.php';

$api_name = 'schedule_push_notification';
$is_transaction = false;

$fn_general = new Class_general();
$fn_email = new Class_email();

try {
    $fn_email->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $fn_email->send_push_notification();
    Class_db::getInstance()->db_close();
} catch (Throwable $ex) {
    if (class_exists('Class_db', false)) {
        try {
            Class_db::getInstance()->db_close();
        } catch (Throwable $ignored) {
        }
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'scheduler_push_notification FATAL: '.$ex->getMessage().PHP_EOL);
        exit(1);
    }
}