<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_email {
     
    private $fn_general;
    
    function __construct() {
        $this->fn_general = new Class_general();
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
     * @param string $userId
     * @param int $emailTemplateId
     * @param array $emailParam
     * @return bool
     * @throws Exception
     */
    public function setup_email ($userId='', $emailTemplateId=0, $emailParam=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }   
            if (empty($emailTemplateId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter emailTemplateId empty');
            }   
            if (empty($emailParam)) {
                throw new Exception('[' . __LINE__ . '] - Array emailParam empty');
            } 
            
            $sys_user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$userId), NULL, 1);
            $sys_profile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$userId, 'user_profile_status'=>'1'), NULL, 1);
            $email_template = Class_db::getInstance()->db_select_single('email_template', array('email_template_id'=>$emailTemplateId), NULL, 1); 
            $emailTitle = $email_template['email_template_title'];
            $emailHtml = $email_template['email_template_html'];
            
            $arr_parameter = Class_db::getInstance()->db_select('email_parameter', array('email_template_id'=>$emailTemplateId), NULL, NULL, 1);
            foreach ($arr_parameter as $parameter) {
                $paramCode = $parameter['email_param_code'];
                if (!array_key_exists($paramCode, $emailParam)) {
                    throw new Exception('[' . __LINE__ . '] - Index '.$parameter['email_param_code'].' in array emailParam empty');
                } 
                if (strpos($emailTitle,"[".$paramCode."]") !== false) {
                    $emailTitle = str_replace ("[".$paramCode."]", $emailParam[$paramCode], $emailTitle);
                }
                if (strpos($emailHtml,"[".$paramCode."]") !== false) {
                    $emailHtml = str_replace ("[".$paramCode."]", $emailParam[$paramCode], $emailHtml);
                }
            }
            $emailHtml = str_replace ("[fullName]", $sys_user['user_first_name'].' '.$sys_user['user_last_name'], $emailHtml);
            
            Class_db::getInstance()->db_insert('email_send', array('email_template_id'=>$emailTemplateId, 'email_address'=>$sys_profile['user_email'], 'email_title'=>$emailTitle,
                'email_html'=>$emailHtml, 'user_id'=>$userId));
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param int $emailTemplateId
     * @param array $emailParam
     * @return bool
     * @throws Exception
     */
    public function setup_email_public ($emailTemplateId=0, $emailParam=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            
            if (empty($emailTemplateId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter emailTemplateId empty');
            }   
            if (empty($emailParam)) {
                throw new Exception('[' . __LINE__ . '] - Array emailParam empty');
            } 
            if (!array_key_exists('emailAddress', $emailParam) || empty($emailParam['emailAddress'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter emailAddress empty');
            } 
            
            $emailAddress = $emailParam['emailAddress'];
            $email_template = Class_db::getInstance()->db_select_single('email_template', array('email_template_id'=>$emailTemplateId), NULL, 1); 
            $emailTitle = $email_template['email_template_title'];
            $emailHtml = $email_template['email_template_html'];
            $emailAttachment = '';
            $emailFilename = '';
            
            if (array_key_exists('emailAttachment', $emailParam) && !empty($emailParam['emailAttachment'])) {
                $emailAttachment = $emailParam['emailAttachment'];
            } 
            if (array_key_exists('emailFilename', $emailParam) && !empty($emailParam['emailFilename'])) {
                $emailFilename = $emailParam['emailFilename'];
            } 
            
            $arr_parameter = Class_db::getInstance()->db_select('email_parameter', array('email_template_id'=>$emailTemplateId), NULL, NULL, 1);
            foreach ($arr_parameter as $parameter) {
                $paramCode = $parameter['email_param_code'];
                if (!array_key_exists($paramCode, $emailParam)) {
                    throw new Exception('[' . __LINE__ . '] - Index '.$parameter['email_param_code'].' in array emailParam empty');
                } 
                if (strpos($emailTitle,"[".$paramCode."]") !== false) {
                    $emailTitle = str_replace ("[".$paramCode."]", $emailParam[$paramCode], $emailTitle);
                }
                if (strpos($emailHtml,"[".$paramCode."]") !== false) {
                    $emailHtml = str_replace ("[".$paramCode."]", $emailParam[$paramCode], $emailHtml);
                }
            }
            
            Class_db::getInstance()->db_insert('email_send', array('email_template_id'=>$emailTemplateId, 'email_address'=>$emailAddress, 'email_title'=>$emailTitle,
                'email_html'=>$emailHtml, 'email_attachment'=>$emailAttachment, 'email_filename'=>$emailFilename));
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function send_email () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            
            $arr_emailSend = Class_db::getInstance()->db_select('email_send', array(), 'email_id', '20');
            foreach ($arr_emailSend as $emailSend) { 
                $status = '14';
                try {
                    $uid = md5(uniqid(time()));
                    $header = "From: seminar@pdp.gov.my\r\n";
                    $header .= "MIME-Version: 1.0\r\n";
                    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";

                    $nmessage = "--".$uid."\r\n";
                    $nmessage .= "Content-type:text/html; charset=utf-8\n";
                    $nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                    $nmessage .= $emailSend['email_html']."\r\n\r\n";
                    $nmessage .= "--".$uid."\r\n";

                    if (!empty($emailSend['email_attachment']) && !empty($emailSend['email_filename'])) {
                        $file = $emailSend['email_attachment'];
                        $content = file_get_contents($file);
                        $content = chunk_split(base64_encode($content));                
                        $name = basename($file);
                        $filename = $emailSend['email_filename'];             

                        $nmessage .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n";
                        $nmessage .= "Content-Transfer-Encoding: base64\r\n";
                        $nmessage .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
                        $nmessage .= $content."\r\n\r\n";
                        $nmessage .= "--".$uid."--";
                    }

                    if(mail($emailSend['email_address'], $emailSend['email_title'], $nmessage, $header)) {
                        $status = '13';
                    }
                } catch(Exception $ey) {  
                    
                }
                Class_db::getInstance()->db_insert('email_log', array('email_template_id'=>$emailSend['email_template_id'], 'email_address'=>$emailSend['email_address'],
                    'email_title'=>$emailSend['email_title'], 'email_html'=>$emailSend['email_html'], 'user_id'=> (is_null($emailSend['user_id'])?'':$emailSend['user_id']), 'email_retry_no'=>$emailSend['email_retry_no'],
                    'email_attachment'=>$emailSend['email_attachment'], 'email_filename'=>$emailSend['email_filename'], 'email_id'=>$emailSend['email_id'], 'email_log_status'=>$status));
                Class_db::getInstance()->db_delete('email_send', array('email_id'=>$emailSend['email_id']));
            }
            
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
    
}