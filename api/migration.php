<?php

ini_set('max_execution_time', 0);

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';

$api_name = 'backdoor_assign';
$is_transaction = false;

$constant = new Class_constant();
$fn_general = new Class_general();

try {

    Class_db::getInstance()->db_connect();

    echo '-----------------<br/>';
    $assets = Class_db::getInstance()->db_select('vw_ppm_asset_backdoor', array('ast_asset.contract_id'=>'1', 'asset_status'=>'1', 'ast_asset.asset_type_id'=>'is not NULL',
        'ppm_checklist.checklist_id'=>'is not NULL', 'ppm_id'=>'is NULL', 'total_user'=>'is not NULL'), null, '200');
    foreach ($assets as $asset) {
        //var_dump($asset);
        Class_db::getInstance()->db_beginTransaction();

        Class_db::getInstance()->db_commit();
        echo '<br/>ppmId = '.$result['ppmId'].', ppmTaskNo = '.$result['ppmTaskNo'];
        echo '<br/>-----------------<br/>';
    }

    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    Class_db::getInstance()->db_rollback();
    Class_db::getInstance()->db_close();
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}