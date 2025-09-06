<?php

class Class_user_signature {

    private $fn_general;
    private $constant;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg,'-');
            if ($pos !== false) {
                $msg = substr($msg, $pos+2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        } else {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
        }
    }

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value ) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property ) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property ) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getUserSignature ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));
            $constant = $this->constant;

            $result = array();
            $userSignature = Class_db::getInstance()->db_select('sys_user_signature', array('user_id'=>$userId));
            
            if (!empty($userSignature)) {
                $signature = $userSignature[0];
                $result['file_path'] = $constant::URL . $signature['signature_path'];
                $result['title'] = 'signature';
                $result['type'] = $signature['mime_type'];
                $result['size'] = ''; // Size not stored in this schema
                $result['width'] = $signature['width'];
                $result['height'] = $signature['height'];
                $result['sha256'] = $signature['signature_sha256'];
            }
            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $userSignature
     * @throws Exception
     */
    public function updateUserSignature ($userId, $userSignature) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $userSignature));

            // Get upload details to store the path and metadata
            $upload = Class_db::getInstance()->db_select_single2('sys_upload', array('upload_id'=>$userSignature));
            if (empty($upload)) {
                throw new Exception('Upload record not found');
            }
            
            $filePath = $upload['uploadFolder'].'/'.$upload['uploadFilename'].'.'.$upload['uploadExtension'];
            $fileContent = file_get_contents(__DIR__ . '/../../' . $filePath);
            $sha256 = hash('sha256', $fileContent);

            // Check if user signature already exists
            $existingSignature = Class_db::getInstance()->db_select('sys_user_signature', array('user_id'=>$userId));
            
            if (!empty($existingSignature)) {
                // Update existing signature record
                $oldPath = $existingSignature[0]['signature_path'];
                Class_db::getInstance()->db_update('sys_user_signature', 
                    array(
                        'signature_path' => $filePath,
                        'signature_sha256' => $sha256,
                        'mime_type' => $upload['uploadBlobType'],
                        'width' => $upload['uploadFileWidth'],
                        'height' => $upload['uploadFileHeight'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ), 
                    array('user_id'=>$userId));
                    
                // Delete old file if different path
                if (!empty($oldPath) && $oldPath !== $filePath) {
                    $oldFullPath = __DIR__ . '/../../' . $oldPath;
                    if (file_exists($oldFullPath)) {
                        unlink($oldFullPath);
                    }
                }
            } else {
                // Create new signature record
                Class_db::getInstance()->db_insert('sys_user_signature', array(
                    'user_id' => $userId,
                    'signature_path' => $filePath,
                    'signature_sha256' => $sha256,
                    'mime_type' => $upload['uploadBlobType'],
                    'width' => $upload['uploadFileWidth'],
                    'height' => $upload['uploadFileHeight'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ));
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}