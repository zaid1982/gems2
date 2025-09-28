<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_email.php';
require_once __DIR__.'/function/f_vm.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
$constant=new Class_constant();
$fn_general=new Class_general();
$fn_login=new Class_login();
$fn_vm=new Class_vm();
$form=['success'=>false,'result'=>null,'errors'=>null,'error'=>''];
try {
  $fn_vm->__set('constant',$constant); $fn_vm->__set('fn_general',$fn_general);
  Class_db::getInstance()->db_connect();
  $method=$_SERVER['REQUEST_METHOD'];
  switch($method){
    case 'POST':
  $raw=file_get_contents('php://input');
  $data=json_decode($raw,true);
  if(!is_array($data)) $data=$_POST; // fallback form encoded
  // Support multipart/form-data submissions with file uploads
  $files = isset($_FILES) && is_array($_FILES) ? $_FILES : null;
        $userId=null; // public form for now
  $res=$fn_vm->createVisit($data,$userId,$files);
        if(!$res['success']){ $form['errors']=$res['errors']; break; }
        $form['success']=true; $form['result']=$res; $form['message']=Class_constant::SUC_VM_VISIT_SUBMITTED; break;
    case 'GET':
        if(!isset($_GET['site_id'])) throw new Exception('site_id required');
        $siteId=$_GET['site_id'];
        $limit=isset($_GET['limit'])?$_GET['limit']:100;
        $form['success']=true; $form['result']=$fn_vm->listVisits($siteId,$limit); break;
    default: throw new Exception('Unsupported method');
  }
} catch(Exception $ex){
  $form['error']=$ex->getMessage();
}
header('Content-Type: application/json'); echo json_encode($form);
