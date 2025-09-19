<?php
// Ensure JSON is not polluted by PHP notices/warnings
@ini_set('display_errors','0');
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

require_once 'function/db.php';
require_once 'function/f_general.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');
$db=Class_db::getInstance();
$out=['success'=>false,'result'=>null,'error'=>'','errors'=>null];

function csrf_token() {
  if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
  return $_SESSION['csrf_token'];
}
function require_csrf() {
  $hdr = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
  $tok = $hdr;
  if (!$tok && isset($_POST['csrf'])) $tok = $_POST['csrf'];
  if (!$tok) {
    $raw=file_get_contents('php://input'); $j=json_decode($raw,true); if (is_array($j) && isset($j['csrf'])) $tok=$j['csrf'];
  }
  if (!$tok || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tok)) { throw new Exception('Invalid CSRF token'); }
}

function sanitize($s){ if($s===null) return ''; $s=trim($s); return preg_replace('/[\x00-\x1F]/','',$s); }

try{
  $db->db_connect();
  $method = $_SERVER['REQUEST_METHOD'];
  $action = isset($_GET['action'])? $_GET['action'] : 'list';

  if ($action==='csrf') { echo json_encode(['success'=>true,'result'=>['token'=>csrf_token()]]); exit; }

  if ($method==='GET' && $action==='list') {
    $siteId = isset($_GET['site_id'])? strval($_GET['site_id']) : '';
    $q = isset($_GET['q'])? trim($_GET['q']) : '';
    $includeInactive = isset($_GET['include_inactive']) ? (strval($_GET['include_inactive']) === '1') : false;
    if ($siteId==='') throw new Exception('site_id required');
    // Class_db where builder expects all values as strings
    $where = ['site_id'=>$siteId];
    if (!$includeInactive) { $where['active'] = '1'; }
    if ($q!=='') {
      $esc = str_replace("'","\\'", $q);
      $where['w1'] = "(name like '%$esc%' OR email like '%$esc%' OR contact_no like '%$esc%' OR department like '%$esc%')";
    }
    $rows = $db->db_select('vm_host', $where, 'name ASC', '200');
    $out['success']=true; $out['result']=$rows; echo json_encode($out); exit;
  }

  if ($method==='POST' && $action==='create') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true); if(!is_array($data)) $data=$_POST;
    $siteId = sanitize(isset($data['site_id'])?$data['site_id']:'');
    $name = sanitize(isset($data['name'])?$data['name']:'');
    $email = sanitize(isset($data['email'])?$data['email']:'');
    $contact = sanitize(isset($data['contact_no'])?$data['contact_no']:'');
    $department = sanitize(isset($data['department'])?$data['department']:'');
    $errors=[];
    if(!preg_match('/^[0-9]+$/',$siteId)) $errors['site_id']='Invalid site';
    if($name===''||strlen($name)>100) $errors['name']='Name 1-100 chars';
    if($email!=='' && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i',$email)) $errors['email']='Invalid email';
    if($contact!=='' && !preg_match('/^[0-9\-\+\(\)\s]{3,50}$/',$contact)) $errors['contact_no']='Invalid contact';
    if(!empty($errors)) { $out['errors']=$errors; throw new Exception('Validation failed'); }
    $id = $db->db_insert('vm_host', [
      'site_id'=>$siteId,
      'name'=>$name,
      'email'=>$email,
      'contact_no'=>$contact,
      'department'=>$department,
      'active'=>'1'
    ]);
    $out['success']=true; $out['result']=['host_id'=>$id]; echo json_encode($out); exit;
  }

  if ($method==='POST' && $action==='update') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true); if(!is_array($data)) $data=$_POST;
    $id = isset($data['host_id'])? intval($data['host_id']) : 0;
    if ($id<=0) throw new Exception('host_id required');
  $fields = [];
  foreach(['name','email','contact_no','department','active'] as $k){ if(isset($data[$k])) $fields[$k]=strval(sanitize($data[$k])); }
    if(empty($fields)) throw new Exception('No changes');
  $db->db_update('vm_host', $fields, ['host_id'=>strval($id)]);
    $out['success']=true; $out['result']=['host_id'=>$id]; echo json_encode($out); exit;
  }

  if ($method==='POST' && $action==='delete') {
    require_csrf();
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true); if(!is_array($data)) $data=$_POST;
    $id = isset($data['host_id'])? intval($data['host_id']) : 0;
    if ($id<=0) throw new Exception('host_id required');
  // soft delete by setting active='0'
  $db->db_update('vm_host', ['active'=>'0'], ['host_id'=>strval($id)]);
    $out['success']=true; $out['result']=['host_id'=>$id]; echo json_encode($out); exit;
  }

  throw new Exception('Unsupported action or method');
} catch(Exception $ex){
  $out['error']=$ex->getMessage();
} finally {
  try { $db->db_close(); } catch(Exception $e){}
}
echo json_encode($out);
