<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_email.php';


$constant = new Class_constant();
$fn_general = new Class_general();
$fn_email = new Class_email();

try {
    $fn_general->__set('constant', $constant);
    $fn_email->__set('fn_general', $fn_general);

    $fn_email->setup_email('594', 11, array('task_no' => '128948912u98481'), true);
} catch (Exception $ex) {

}