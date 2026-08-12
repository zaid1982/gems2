<?php
class Class_user_signature {
    private $constant; private $fn_general;
    function __set($name, $value) { $this->$name = $value; }

    public function getUserSignature($userId) {
        try {
            $userSignatureArr = Class_db::getInstance()->db_select('sys_user_signature', array('user_id'=>$userId));
            if (count($userSignatureArr) === 0) {
                return array();
            }
            $userSignature = $userSignatureArr[0];
            return array(
                'userId'=>$userSignature['user_id'],
                'signaturePath'=>$userSignature['signature_path'],
                'signatureLink'=> $this->fn_general->getUploadFile($userSignature['signature_path']),
                'updatedAt'=>$userSignature['updated_at']
            );
        } catch (Exception $ex) {
            throw new Exception('['.__LINE__.'] - '.$ex->getMessage());
        }
    }

    public function updateUserSignature($userId, $signatureId) {
        try {
            $doc = Class_db::getInstance()->db_select_single('document_upload', array('document_upload_id'=>$signatureId));
            $signature = array(
                'user_id'=>$userId,
                'signature_path'=>$doc['document_upload_id'],
                'signature_sha256'=>'',
                'mime_type'=>$doc['document_upload_mime_type'],
                'width'=>null,
                'height'=>null
            );
            $exists = Class_db::getInstance()->db_count('sys_user_signature', array('user_id'=>$userId));
            if ($exists == 0) {
                Class_db::getInstance()->db_insert('sys_user_signature', $signature);
            } else {
                Class_db::getInstance()->db_update('sys_user_signature', $signature, array('user_id'=>$userId));
            }
        } catch (Exception $ex) {
            throw new Exception('['.__LINE__.'] - '.$ex->getMessage());
        }
    }
}
