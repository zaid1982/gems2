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
    $assets = Class_db::getInstance()->db_select('vw_ppm_asset_backdoor', array('ast_asset.contract_id'=>'1', 'asset_status'=>'1', 'ast_asset.asset_type_id'=>'is not NULL',
        'ppm_checklist.checklist_id'=>'is not NULL', 'ppm_id'=>'is NULL'), null, '1');
    foreach ($assets as $asset) {
        var_dump($asset);
        $result = $fn_ppm->assign_ppm_single($asset['asset_id'], $asset['checklist_id'], '2019-08-01', '1');
        echo '<br/>ppmId = '.$result['ppmId'].', ppmTaskNo = '.$result['ppmTaskNo'];
        echo '<br/>-----------------<br/>';
    }

    //Class_db::getInstance()->db_commit();
} catch (Exception $ex) {
    Class_db::getInstance()->db_rollback();
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}
Class_db::getInstance()->db_close();