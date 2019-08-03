<?php

class Class_email {

    private $fn_general;

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
     * @param string $userId
     * @param int $emailTemplateId
     * @param array $emailParam
     * @return bool
     * @throws Exception
     */
    public function setup_email ($userId='', $emailTemplateId=0, $emailParam=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

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
            $emailHtml = str_replace ("[fullName]", $sys_user['user_first_name'], $emailHtml);

            Class_db::getInstance()->db_insert('email_send', array('email_template_id'=>$emailTemplateId, 'email_address'=>$sys_profile['user_email'], 'email_title'=>$emailTitle,
                'email_html'=>$emailHtml, 'user_id'=>$userId));
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $userId
     * @param int $notiTextId
     * @param array $notiParam
     * @return bool
     */
    public function setup_mobile_notification ($userId='', $notiTextId=0, $notiParam=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($notiTextId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter notiTextId empty');
            }
            if (empty($notiParam)) {
                throw new Exception('[' . __LINE__ . '] - Array notiParam empty');
            }

            $userToken = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'user_token');
            if (empty($userToken)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userToken empty');
            }

            $notiText = Class_db::getInstance()->db_select_single('noti_text', array('noti_text_id'=>$notiTextId), NULL, 1);
            $notiTextTitle = $notiText['noti_text_title'];
            $notiTextHtml = $notiText['noti_text_html'];

            $notiParameters = Class_db::getInstance()->db_select('noti_parameter', array('noti_text_id'=>$notiTextId), NULL, NULL, 1);
            foreach ($notiParameters as $parameter) {
                $paramCode = $parameter['noti_param_code'];
                if (!array_key_exists($paramCode, $notiParam)) {
                    throw new Exception('[' . __LINE__ . '] - Index '.$paramCode.' in array notiParam empty');
                }
                if (strpos($notiTextTitle,"[".$paramCode."]") !== false) {
                    $notiTextTitle = str_replace ("[".$paramCode."]", $notiParam[$paramCode], $notiTextTitle);
                }
                if (strpos($notiTextHtml,"[".$paramCode."]") !== false) {
                    $notiTextHtml = str_replace ("[".$paramCode."]", $notiParam[$paramCode], $notiTextHtml);
                }
            }

            Class_db::getInstance()->db_insert('noti_send', array('noti_text_id'=>$notiTextId, 'noti_to'=>$userToken, 'noti_title'=>$notiTextTitle,
                'noti_html'=>$notiTextHtml, 'user_id'=>$userId));
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $arr_emailSend = Class_db::getInstance()->db_select('email_send', array(), 'email_id', '20');
            foreach ($arr_emailSend as $emailSend) {
                $status = '23'; // fail
                try {
                    $uid = md5(uniqid(time()));
                    $header = "From: ict-support@globalfm.com.my\r\n";
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
                        $status = '22'; // success
                    }

                } catch(Exception $ey) {
                }

                try {
                    Class_db::getInstance()->db_beginTransaction();
                    Class_db::getInstance()->db_insert('email_log', array('email_template_id'=>$emailSend['email_template_id'], 'email_address'=>$emailSend['email_address'],
                        'email_title'=>$emailSend['email_title'], 'email_html'=>$emailSend['email_html'], 'user_id'=> (is_null($emailSend['user_id'])?'':$emailSend['user_id']), 'email_retry_no'=>$emailSend['email_retry_no'],
                        'email_attachment'=>$this->fn_general->clear_null($emailSend['email_attachment']), 'email_filename'=>$this->fn_general->clear_null($emailSend['email_filename']), 'email_id'=>$emailSend['email_id'], 'email_log_status'=>$status));
                    Class_db::getInstance()->db_delete('email_send', array('email_id'=>$emailSend['email_id']));
                    Class_db::getInstance()->db_commit();
                } catch(Exception $ez) {
                    Class_db::getInstance()->db_rollback();
                }
            }

            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $receiver
     * @param $title
     * @param $content
     * @throws Exception
     */
    public function send_email_express ($receiver, $title, $content) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $uid = md5(uniqid(time()));
            $header = "From: ict-support@globalfm.com.my\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n\r\n";

            $nmessage = "--" . $uid . "\r\n";
            $nmessage .= "Content-type:text/html; charset=utf-8\n";
            $nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $nmessage .= $content . "\r\n\r\n";
            $nmessage .= "--" . $uid . "\r\n";

            mail($receiver, $title, $nmessage, $header, '-fict-support@globalfm.com.my');
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $title
     * @param $message
     * @param $token
     */
    public function send_mobile_notification ($title, $message, $token) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($title)) {
                throw new Exception('[' . __LINE__ . '] - Parameter title empty');
            }
            if (empty($message)) {
                throw new Exception('[' . __LINE__ . '] - Parameter message empty');
            }
            if (empty($token)) {
                throw new Exception('[' . __LINE__ . '] - Parameter token empty');
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\n \"to\" : \"".$token."\",\n \"collapse_key\" : \"type_a\",\n \"notification\" : {\n     \"body\" : \"".$message."\",\n     \"title\": \"".$title."\"\n }\n}",
                CURLOPT_HTTPHEADER => array(
                    "Accept: */*",
                    "Authorization: key=AAAA0VbV4yY:APA91bEkhqjl72wrey1qcbBlaaGNZTVtRcDQMwBkIOTkzWzytnTHbEVypleaWjHA3SeO0klvh9M2M_MaX-1yf2jupOZnDyn2Zx9lx2CLDgZGOwPfBpr1HvFO14lnZSKlpqi1rKM5BX-i",
                    "Cache-Control: no-cache",
                    "Connection: keep-alive",
                    "Content-Type: application/json",
                    "Host: fcm.googleapis.com",
                    "accept-encoding: gzip, deflate",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'response = ' . $response);
            $err = curl_error($curl);
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'err = ' . $err);

            curl_close($curl);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function send_push_notification () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $notiSends = Class_db::getInstance()->db_select('noti_send', array(), 'noti_id', '20');
            foreach ($notiSends as $notiSend) {
                $status = '23'; // fail
                try {
                    $this->send_mobile_notification($notiSend['noti_title'], $notiSend['noti_html'], $notiSend['noti_to']);
                    $status = '22';
                } catch(Exception $ey) {
                }

                try {
                    Class_db::getInstance()->db_beginTransaction();
                    Class_db::getInstance()->db_insert('noti_log', array('noti_text_id'=>$notiSend['noti_text_id'], 'noti_to'=>$notiSend['noti_to'], 'noti_title'=>$notiSend['noti_title'],
                        'noti_html'=>$notiSend['noti_html'], 'user_id'=> $this->fn_general->clear_null($notiSend['user_id']), 'noti_id'=>$notiSend['noti_id'], 'noti_log_status'=>$status));
                    Class_db::getInstance()->db_delete('noti_send', array('noti_id'=>$notiSend['noti_id']));
                    Class_db::getInstance()->db_commit();
                } catch(Exception $ez) {
                    Class_db::getInstance()->db_rollback();
                }
            }

            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}