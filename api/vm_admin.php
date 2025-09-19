<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

$constant=new Class_constant();
$fn_general=new Class_general();
$fn_login=new Class_login();
$db=Class_db::getInstance();

header('Content-Type: application/json');
$out=['success'=>false,'result'=>null,'errors'=>null,'error'=>''];

function csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function require_csrf() {
  $hdr = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
  $body = '';
  if (empty($hdr)) {
    // also allow body field 'csrf'
    $raw = file_get_contents('php://input');
    if ($raw) { $j = json_decode($raw, true); if (is_array($j) && isset($j['csrf'])) { $body = $j['csrf']; } }
  }
  $tok = $hdr ?: $body;
  if (!$tok || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tok)) {
    throw new Exception('Invalid CSRF token');
  }
}

function column_exists($table, $column){
  $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c";
  $arr = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [':t'=>$table, ':c'=>$column], 'c', 0);
  return isset($arr[0]) && intval($arr[0])>0;
}

function status_values(){
  $sql = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vm_visit' AND COLUMN_NAME='status'";
  $rows = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [], 'COLUMN_TYPE', 0);
  if (empty($rows)) return ['CHECKED_IN','CHECKED_OUT','CANCELLED'];
  $ct = $rows[0];
  if (preg_match("/enum\((.*)\)/i", $ct, $m)) {
    $raw = $m[1];
    $vals = array_map(function($v){ return trim($v, "'\" "); }, explode(',', $raw));
    return $vals;
  }
  return ['CHECKED_IN','CHECKED_OUT','CANCELLED'];
}

try {
  $db->db_connect();
  $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action'])?$_POST['action']:'list');

  if ($action === 'csrf') {
    $out['success']=true; $out['result']=['token'=>csrf_token()]; echo json_encode($out); exit;
  }

  if ($action === 'meta') {
    $out['success']=true; $out['result']=[
      'statuses'=>status_values(),
      'has_archived_at'=>column_exists('vm_visit','archived_at'),
      'has_deleted_at'=>column_exists('vm_visit','deleted_at')
    ]; echo json_encode($out); exit;
  }

  if ($action === 'list') {
    $site_id = isset($_GET['site_id'])? strval($_GET['site_id']): '';
    $status = isset($_GET['status'])? strval($_GET['status']): '';
    $search = isset($_GET['search'])? strval($_GET['search']): '';
    $dateStart = isset($_GET['date_start'])? strval($_GET['date_start']): '';
    $dateEnd = isset($_GET['date_end'])? strval($_GET['date_end']): '';
    $page = isset($_GET['page'])? max(1, intval($_GET['page'])) : 1;
    $size = isset($_GET['size'])? max(1, intval($_GET['size'])) : 25;

    // Default to today
    if ($dateStart==='') { $dateStart = date('Y-m-d'); }
    if ($dateEnd==='') { $dateEnd = $dateStart; }
    $where = [];
    $where['arrived_at'] = ">=".$dateStart." 00:00:00"; // using get_whereAnd_str format
    $where['w1'] = "arrived_at <= '".$dateEnd." 23:59:59'";
    if ($site_id!=='') { $where['site_id'] = $site_id; }
    if ($status!=='' && strtoupper($status)!=='ALL') { $where['status'] = $status; }
    if (column_exists('vm_visit','deleted_at')) { $where['deleted_at']='is NULL'; }
    if ($search!=='') {
      $esc = str_replace("'","\\'", $search);
      $w = "(name like '%$esc%' OR contact_no like '%$esc%' OR ic_no like '%$esc%' OR company like '%$esc%' OR email like '%$esc%' OR host_name like '%$esc%' OR purpose like '%$esc%')";
      $where['w2'] = $w;
    }
    $offset = ($page-1)*$size;
    $rows = $db->db_select('vm_visit', $where, 'arrived_at DESC', $offset.','.$size);
    $total = intval($db->db_count('vm_visit', $where));
    $out['success']=true; $out['result']=['rows'=>$rows,'total'=>$total,'page'=>$page,'size'=>$size]; echo json_encode($out); exit;
  }

  if ($action === 'detail') {
    $id = isset($_GET['visit_id'])? strval($_GET['visit_id']): '';
    if ($id==='') throw new Exception('visit_id required');
    $row = $db->db_select_single('vm_visit', ['visit_id'=>$id]);
    $out['success']=true; $out['result']=$row; echo json_encode($out); exit;
  }

  if ($action === 'archive') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true);
    $id = isset($data['visit_id'])? strval($data['visit_id']): '';
    if ($id==='') throw new Exception('visit_id required');
    $statuses = status_values();
    $target = in_array('ARCHIVED',$statuses)? 'ARCHIVED' : (in_array('CANCELLED',$statuses)? 'CANCELLED' : 'CHECKED_OUT');
    $set = ['status'=>$target];
    if (column_exists('vm_visit','archived_at')) { $set['archived_at']='Now()'; }
    $db->db_update('vm_visit', $set, ['visit_id'=>$id]);
    $out['success']=true; $out['result']=['visit_id'=>$id,'status'=>$target]; echo json_encode($out); exit;
  }

  if ($action === 'checkout') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true);
    $id = isset($data['visit_id'])? strval($data['visit_id']): '';
    if ($id==='') throw new Exception('visit_id required');
    $statuses = status_values();
    $target = in_array('CHECKED_OUT', $statuses) ? 'CHECKED_OUT' : (in_array('ARCHIVED',$statuses)? 'ARCHIVED' : 'CHECKED_OUT');
    $set = ['status'=>$target];
    if (column_exists('vm_visit','checked_out_at')) { $set['checked_out_at'] = 'Now()'; }
    $db->db_update('vm_visit', $set, ['visit_id'=>$id]);
    $out['success']=true; $out['result']=['visit_id'=>$id,'status'=>$target]; echo json_encode($out); exit;
  }

  if ($action === 'cancel') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true);
    $id = isset($data['visit_id'])? strval($data['visit_id']): '';
    if ($id==='') throw new Exception('visit_id required');
    $statuses = status_values();
    $target = in_array('CANCELLED', $statuses) ? 'CANCELLED' : (in_array('ARCHIVED',$statuses)? 'ARCHIVED' : 'CHECKED_OUT');
    $set = ['status'=>$target];
    if (column_exists('vm_visit','cancelled_at')) { $set['cancelled_at'] = 'Now()'; }
    $db->db_update('vm_visit', $set, ['visit_id'=>$id]);
    $out['success']=true; $out['result']=['visit_id'=>$id,'status'=>$target]; echo json_encode($out); exit;
  }

  if ($action === 'delete') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true);
    $id = isset($data['visit_id'])? strval($data['visit_id']): '';
    if ($id==='') throw new Exception('visit_id required');
    if (column_exists('vm_visit','deleted_at')) {
      $db->db_update('vm_visit', ['deleted_at'=>'Now()'], ['visit_id'=>$id]);
    } else {
      $db->db_delete('vm_visit', ['visit_id'=>$id]);
    }
    $out['success']=true; $out['result']=['visit_id'=>$id]; echo json_encode($out); exit;
  }

  throw new Exception('Unsupported action');
} catch(Exception $ex){
  $out['error']=$ex->getMessage();
} finally {
  try { $db->db_close(); } catch(Exception $e){}
}
echo json_encode($out);
