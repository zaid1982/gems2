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
        $host=$this->sanitize($get('host_name')); if($host===''||strlen($host)>100) $errors['host_name']='Host required (1-100 chars)';
        $purpose=$this->sanitize($get('purpose')); if($purpose===''||strlen($purpose)>2000) $errors['purpose']='Purpose 1-2000 chars';
        $siteId=$get('site_id'); if(!preg_match('/^[0-9]+$/',strval($siteId))) $errors['site_id']='Invalid site id';
        return [$errors, compact('name','contact','ic','company','email','party','host','purpose','siteId')];
    }

    public function createVisit($payload, $userId=null){
        list($errors,$v)=$this->validateVisitPayload($payload);
        if(!empty($errors)) return ['success'=>false,'errors'=>$errors];
        // Ensure site exists to avoid FK violation
        $siteRows = Class_db::getInstance()->db_select('cli_site', ['site_id'=>$v['siteId']], null, '1');
        if (empty($siteRows)) {
            return ['success'=>false,'errors'=>['site_id'=>'Unknown site']];
        }
        $insert=[
            'site_id'=>(int)$v['siteId'],
            'name'=>$v['name'],
            'contact_no'=>$v['contact'],
            'ic_no'=>$v['ic'],
            'company'=>$v['company'],
            'email'=>$v['email'],
            'party_size'=>(int)$v['party'],
            'host_name'=>$v['host'],
            'purpose'=>$v['purpose'],
            'status'=>'CHECKED_IN',
            'created_via'=>isset($payload['created_via'])?$this->sanitize($payload['created_via']):'WEB_FORM',
            'created_by'=>$userId?:null
        ];
        $id=Class_db::getInstance()->db_insert('vm_visit',$insert);
        return ['success'=>true,'visit_id'=>$id];
    }

    public function listVisits($siteId,$limit=100){
        $cond=['site_id'=>$siteId];
        return Class_db::getInstance()->db_select('vm_visit',$cond,'arrived_at DESC',strval(intval($limit)));
    }
}
