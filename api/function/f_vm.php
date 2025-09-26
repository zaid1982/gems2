<?php
class Class_vm {
    private $constant; private $fn_general; private $fn_login;
    public function __set($n,$v){$this->$n=$v;}

    private function sanitize($s){
        if ($s===null) return '';
        $s = trim($s);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',$s);
        return $s;
    }

    public function validateVisitPayload($data){
        $errors=[];
        $get=function($k) use ($data){return array_key_exists($k,$data)?$data[$k]:null;};
        $name=$this->sanitize($get('name')); if($name===''||strlen($name)>100) $errors['name']='Name required (1-100 chars)';
        $contact=$this->sanitize($get('contact_no')); if($contact==='') $errors['contact_no']='Contact required';
        elseif(!preg_match('/^[0-9\-\+\(\)\s]{3,50}$/',$contact)) $errors['contact_no']='Invalid contact format';
        $ic=$this->sanitize($get('ic_no')); if($ic===''||!preg_match('/^[0-9]{6,20}$/',$ic)) $errors['ic_no']='IC/ID must be 6-20 digits';
        $company=$this->sanitize($get('company')); if(strlen($company)>100) $errors['company']='Max 100 chars';
        $email=$this->sanitize($get('email')); if($email!=='' && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i',$email)) $errors['email']='Invalid email';
        $party=$get('party_size'); if(!preg_match('/^[0-9]{1,3}$/',strval($party))||intval($party)<1||intval($party)>999) $errors['party_size']='Party size 1-999';
        // host can now be provided via host_id (preferred) or legacy host_name text during transition
        $hostIdRaw = $get('host_id');
        $hostId = null;
        if($hostIdRaw!==null && $hostIdRaw!=='') {
            if(!preg_match('/^[0-9]+$/', strval($hostIdRaw))) {
                $errors['host_id'] = 'host_id must be numeric';
            } else {
                $hostId = intval($hostIdRaw);
            }
        }
        $host=$this->sanitize($get('host_name'));
        if($hostId===null){ // only enforce text host when host_id not supplied
            if($host===''||strlen($host)>100) $errors['host_name']='Host required (1-100 chars)';
        } else { // if host_id supplied, host_name is optional but if provided must be <=100
            if($host!=='' && strlen($host)>100) $errors['host_name']='Max 100 chars';
        }
        $purpose=$this->sanitize($get('purpose')); if($purpose===''||strlen($purpose)>2000) $errors['purpose']='Purpose 1-2000 chars';
        $siteId=$get('site_id'); if(!preg_match('/^[0-9]+$/',strval($siteId))) $errors['site_id']='Invalid site id';
        return [$errors, compact('name','contact','ic','company','email','party','host','hostId','purpose','siteId')];
    }

    public function createVisit($payload, $userId=null){
        list($errors,$v)=$this->validateVisitPayload($payload);
        if(!empty($errors)) return ['success'=>false,'errors'=>$errors];
        // Ensure site exists to avoid FK violation
        $siteRows = Class_db::getInstance()->db_select('cli_site', ['site_id'=>$v['siteId']], null, '1');
        if (empty($siteRows)) {
            return ['success'=>false,'errors'=>['site_id'=>'Unknown site']];
        }
        // Resolve host via host_id if provided
        $resolvedHostName = $v['host'];
        $hostIdToStore = null;
        if(isset($v['hostId']) && $v['hostId']!==null){
            // fetch host row and ensure it exists & belongs to same site
            $hostRow = Class_db::getInstance()->db_select_single('vm_host', ['host_id'=>strval($v['hostId'])]);
            if(empty($hostRow)){
                return ['success'=>false,'errors'=>['host_id'=>'Host not found']];
            }
            if(strval($hostRow['site_id']) !== strval($v['siteId'])){
                return ['success'=>false,'errors'=>['host_id'=>'Host does not belong to provided site']];
            }
            $hostIdToStore = intval($v['hostId']);
            $resolvedHostName = $hostRow['name']; // canonical name
        }
        $insert=[
            'site_id'=>(int)$v['siteId'],
            'name'=>$v['name'],
            'contact_no'=>$v['contact'],
            'ic_no'=>$v['ic'],
            'company'=>$v['company'],
            'email'=>$v['email'],
            'party_size'=>(int)$v['party'],
            'host_name'=>$resolvedHostName,
            'purpose'=>$v['purpose'],
            'status'=>'CHECKED_IN',
            'created_via'=>isset($payload['created_via'])?$this->sanitize($payload['created_via']):'WEB_FORM',
            'created_by'=>$userId?:null
        ];
        // Add host_id column if it exists in schema (safe during transition)
        try {
            if($hostIdToStore!==null){
                static $hasHostIdCol = null;
                if($hasHostIdCol===null){
                    $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vm_visit' AND COLUMN_NAME='host_id'";
                    $cols = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [], 'c', 0);
                    $hasHostIdCol = !empty($cols) && intval($cols[0])>0;
                }
                if($hasHostIdCol){ $insert['host_id']=$hostIdToStore; }
            }
        } catch(Exception $e){ /* ignore detection errors */ }
        $id=Class_db::getInstance()->db_insert('vm_visit',$insert);

        // Notify host via email (best-effort; failure doesn't block submission)
        try {
            $recipient = '';
            // If host_id was supplied and resolved earlier, prefer its email
            if(isset($v['hostId']) && $v['hostId']!==null){
                $hostRowForEmail = Class_db::getInstance()->db_select_single('vm_host', ['host_id'=>strval($v['hostId'])]);
                if(!empty($hostRowForEmail) && !empty($hostRowForEmail['email']) && filter_var($hostRowForEmail['email'], FILTER_VALIDATE_EMAIL)){
                    $recipient = $hostRowForEmail['email'];
                }
            }
            // Fallback: match by site + host name
            if($recipient===''){
                $rows = Class_db::getInstance()->db_select('vm_host', ['site_id'=>strval($v['siteId']), 'name'=>$resolvedHostName, 'active'=>'1'], 'host_id ASC', '1');
                if(!empty($rows) && !empty($rows[0]['email']) && filter_var($rows[0]['email'], FILTER_VALIDATE_EMAIL)){
                    $recipient = $rows[0]['email'];
                }
            }
            if($recipient!==''){
                if (!class_exists('Class_email')) { require_once __DIR__ . '/f_email.php'; }
                $mailer = new Class_email();
                $safe = function($s){ return htmlspecialchars(strval($s), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
                // Optional site name look-up
                $siteName = '';
                try {
                    $siteRow = Class_db::getInstance()->db_select_single('cli_site', ['site_id'=>strval($v['siteId'])]);
                    if(!empty($siteRow) && !empty($siteRow['site_name'])){ $siteName = $siteRow['site_name']; }
                } catch(Exception $e) { /* ignore */ }
                $subject = 'Visitor arrival notice';
                $htmlBody = '<html><body>'
                    . '<p>Dear ' . $safe($resolvedHostName) . ',</p>'
                    . '<p>A visitor has arrived and is looking for you.</p>'
                    . '<ul>'
                    . '<li><strong>Name:</strong> ' . $safe($v['name']) . '</li>'
                    . '<li><strong>IC/ID:</strong> ' . $safe($v['ic']) . '</li>'
                    . '<li><strong>Company:</strong> ' . $safe($v['company']) . '</li>'
                    . '<li><strong>Contact:</strong> ' . $safe($v['contact']) . '</li>'
                    . '<li><strong>Party size:</strong> ' . intval($v['party']) . '</li>'
                    . '<li><strong>Purpose:</strong> ' . $safe($v['purpose']) . '</li>'
                    . '<li><strong>Site:</strong> ' . $safe($siteName !== '' ? $siteName : $v['siteId']) . '</li>'
                    . '<li><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</li>'
                    . '</ul>'
                    . '<p>Thank you.</p>'
                    . '</body></html>';
                $mailer->send_email_express($recipient, $subject, $htmlBody);
            }
        } catch(Exception $e) {
            // Best-effort: do not block the submission; log if general helper available
            try { if(isset($this->fn_general)) { $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Host email send failed: ' . $e->getMessage()); } } catch(Exception $ee) {}
        }

        return ['success'=>true,'visit_id'=>$id];
    }

    public function listVisits($siteId,$limit=100){
        $cond=['site_id'=>$siteId];
        return Class_db::getInstance()->db_select('vm_visit',$cond,'arrived_at DESC',strval(intval($limit)));
    }
}
