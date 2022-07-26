<?php

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/Login.php';

set_error_handler(/**
 * @throws ErrorException
 */ function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});

$apiName = 'api_test_oop';
$isTransaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnGeneral = new General();
$fnLogin = new Login();

try {

   // print_r(apache_request_headers());
    $j = array('checkpointId'=>'NULL', 'roleId'=>'1');
    print_r(array_keys($j));
    echo('<br/>');
    print_r(array_values($j));
    echo('<br/>');

    DbMysql::connect();
    DbMysql::$isLogged = true;
    //$fnGeneral::checkJwt('bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnZW1zMlwvand0IiwidXNlcklkIjoiMSIsInVzZXJuYW1lIjoiYWRtaW4iLCJpYXQiOjE2NTM4MTgzNjEsImV4cCI6MTY1MzgxODM3MX0.T8zTs9tcwJYmpvdSzkZMENK3Re6sH4wpnxWhoZ5Cs80');
    //$requestMethod = $_SERVER['REQUEST_METHOD'];
    //$fnGeneral::logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod);

    $fnLogin->testLogin();
    DbMysql::close();
} catch (Exception $e) {
    try {
        if ($isTransaction) {
            DbMysql::rollback();
        }
        DbMysql::close();
    } catch (Exception $ex) {
    }
    echo $e->getMessage();
}