<?php

class Class_email {

    private $fn_general;

    function __construct() {
        // Ensure logger/general helper is available to avoid null dereference
        if (!isset($this->fn_general) || $this->fn_general === null) {
            if (class_exists('Class_general')) {
                try { $this->fn_general = new Class_general(); } catch (Exception $e) { /* fallback silent */ }
            }
        }
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
     * @param bool $isExpress
     * @return bool
     */
    public function setup_email ($userId='', $emailTemplateId=0, $emailParam=array(), $isExpress=false, $fullName='', $emailAddress='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $userWhereClause = '';
            if (is_array($userId) && !empty($userId)) {
                $userWhereClause = '(' . implode(',', array_map('intval', $userId)) . ')';
            } else if (!empty($userId)) {
                $userWhereClause = (string)intval($userId); // Ensure single user ID is a string
            } else {
                // If userId is empty, no user-specific query will be made
            }

            // Parameter userId cannot be empty if we intend to get user details
            if (empty($userWhereClause) && empty($fullName) && empty($emailAddress)) {
                 throw new Exception('[' . __LINE__ . '] - Recipient (userId, fullName, or emailAddress) empty');
            }

            $sys_user = null; // Initialize to null
            $sys_profile = null; // Initialize to null
            if (!empty($userWhereClause)) { // Only query if there's a valid user ID(s)
                // Use the formatted userWhereClause here
                $sys_user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$userWhereClause), NULL, 1);
                
                // Check if user account is active before sending email
                if ($sys_user && isset($sys_user['user_status']) && $sys_user['user_status'] !== '1') {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Skipping email for disabled user: '.$userWhereClause);
                    return false; // Skip sending email to disabled accounts
                }
                
                $sys_profile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$userWhereClause, 'user_profile_status'=>'1'), NULL, 1);
            }

            // MODIFIED: Ensure emailTemplateId is cast to string for the query condition
            $email_template = Class_db::getInstance()->db_select_single('email_template', array('email_template_id'=>(string)$emailTemplateId), NULL, 1);
            $emailTitle = $email_template['email_template_title'];
            $emailHtml = $email_template['email_template_html'];

            // MODIFIED: Ensure emailTemplateId is cast to string for the query condition
            $arr_parameter = Class_db::getInstance()->db_select('email_parameter', array('email_template_id'=>(string)$emailTemplateId), NULL, NULL, 1);
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
            
            // Check if $sys_user exists before accessing its properties.
            $recipientName = !empty($fullName) ? $fullName : ($sys_user ? $sys_user['user_first_name'] : '');
            $emailHtml = str_replace ("[fullName]", $recipientName, $emailHtml);

            // Check if $sys_profile exists before accessing its properties.
            $recipientEMail = !empty($emailAddress) ? $emailAddress : ($sys_profile ? $sys_profile['user_email'] : '');
            if ($isExpress) {
                $this->send_email_express($recipientEMail, $emailTitle, $emailHtml);
            } else {
                // If userId is an array, take the first element for email_send, or reconsider if multiple emails should be sent.
                // Assuming email_send is for a single email for simplicity now.
                $insertUserId = is_array($userId) ? (empty($userId) ? null : $userId[0]) : $userId; // Use a single user ID for logging
                Class_db::getInstance()->db_insert('email_send', array('email_template_id'=>(string)$emailTemplateId, 'email_address'=>$recipientEMail, 'email_title'=>$emailTitle,
                    'email_html'=>$emailHtml, 'user_id'=>$insertUserId));
            }
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode()); // Keep this commented out or re-enable if you want exceptions to stop execution
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

            $userWhereClause = '';
            if (is_array($userId) && !empty($userId)) {
                $userWhereClause = '(' . implode(',', array_map('intval', $userId)) . ')';
            } else if (!empty($userId)) {
                $userWhereClause = (string)intval($userId); // Ensure single user ID is a string
            } else {
                // If userId is empty, throw an exception as mobile notifications typically require a user.
                 throw new Exception('[' . __LINE__ . '] - Parameter userId empty for mobile notification');
            }

            // Use the formatted userWhereClause here
            $userToken = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userWhereClause), 'user_token');
            if (empty($userToken)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userToken empty');
            }

            // MODIFIED: Ensure notiTextId is cast to string for the query condition
            $notiText = Class_db::getInstance()->db_select_single('noti_text', array('noti_text_id'=>(string)$notiTextId), NULL, 1);
            $notiTextTitle = $notiText['noti_text_title'];
            $notiTextHtml = $notiText['noti_text_html'];

            // MODIFIED: Ensure notiTextId is cast to string for the query condition
            $notiParameters = Class_db::getInstance()->db_select('noti_parameter', array('noti_text_id'=>(string)$notiTextId));
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

            // If userId is an array, take the first element for noti_send
            $insertUserId = is_array($userId) ? (empty($userId) ? null : $userId[0]) : $userId; // Use a single user ID for logging
            Class_db::getInstance()->db_insert('noti_send', array('noti_text_id'=>(string)$notiTextId, 'noti_to'=>$userToken, 'noti_title'=>$notiTextTitle,
                'noti_html'=>$notiTextHtml, 'user_id'=>$insertUserId));
            return true;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode()); // Keep this commented out or re-enable if you want exceptions to stop execution
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
            // MODIFIED: Ensure emailTemplateId is cast to string for the query condition
            $email_template = Class_db::getInstance()->db_select_single('email_template', array('email_template_id'=>(string)$emailTemplateId), NULL, 1);
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

            // MODIFIED: Ensure emailTemplateId is cast to string for the query condition
            $arr_parameter = Class_db::getInstance()->db_select('email_parameter', array('email_template_id'=>(string)$emailTemplateId), NULL, NULL, 1);
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

            Class_db::getInstance()->db_insert('email_send', array('email_template_id'=>(string)$emailTemplateId, 'email_address'=>$emailAddress, 'email_title'=>$emailTitle,
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
            // Delegate to the SMTP-based queue processor to keep one code path.
            /* ------------------------------------------------------------------
             * LEGACY IMPLEMENTATION (using PHP mail())
             * Retained here (commented) for easy rollback if direct SMTP path has issues.
             * To revert temporarily, comment out the return line below and uncomment this block.
             *
             *  // Load mail configuration
             *  $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
             *  $mailFrom = $config['mail']['mail_from'] ?? 'ict-support@globalfm.com.my';
             *  $mailEnvelopeFrom = $config['mail']['mail_envelope_from'] ?? $mailFrom;
             *  $arr_emailSend = Class_db::getInstance()->db_select('email_send', array(), 'email_id', '20');
             *  foreach ($arr_emailSend as $emailSend) {
             *      $status = '23'; // fail
             *      try {
             *          $uid = md5(uniqid(time()));
             *          $header = "From: " . $mailFrom . "\r\n";
             *          $header .= "MIME-Version: 1.0\r\n";
             *          $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
             *          $nmessage = "--".$uid."\r\n";
             *          $nmessage .= "Content-type:text/html; charset=utf-8\n";
             *          $nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
             *          $nmessage .= $emailSend['email_html']."\r\n\r\n";
             *          $nmessage .= "--".$uid."\r\n";
             *          if (!empty($emailSend['email_attachment']) && !empty($emailSend['email_filename'])) {
             *              $file = $emailSend['email_attachment'];
             *              $content = file_get_contents($file);
             *              $content = chunk_split(base64_encode($content));
             *              $filename = $emailSend['email_filename'];
             *              $nmessage .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n";
             *              $nmessage .= "Content-Transfer-Encoding: base64\r\n";
             *              $nmessage .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
             *              $nmessage .= $content."\r\n\r\n";
             *              $nmessage .= "--".$uid."--";
             *          }
             *          $fifthParam = '-f' . $mailEnvelopeFrom;
             *          if (mail($emailSend['email_address'], $emailSend['email_title'], $nmessage, $header, $fifthParam)) {
             *              $status = '22';
             *          }
             *      } catch (Exception $legacyEx) {}
             *      try {
             *          Class_db::getInstance()->db_beginTransaction();
             *          Class_db::getInstance()->db_insert('email_log', array(
             *              'email_template_id'=>$emailSend['email_template_id'],
             *              'email_address'=>$emailSend['email_address'],
             *              'email_title'=>$emailSend['email_title'],
             *              'email_html'=>$emailSend['email_html'],
             *              'user_id'=> (is_null($emailSend['user_id'])?'':$emailSend['user_id']),
             *              'email_retry_no'=>$emailSend['email_retry_no'],
             *              'email_attachment'=>$this->fn_general->clear_null($emailSend['email_attachment']),
             *              'email_filename'=>$this->fn_general->clear_null($emailSend['email_filename']),
             *              'email_id'=>$emailSend['email_id'],
             *              'email_log_status'=>$status));
             *          Class_db::getInstance()->db_delete('email_send', array('email_id'=>$emailSend['email_id']));
             *          Class_db::getInstance()->db_commit();
             *      } catch (Exception $legacyLogEx) {
             *          Class_db::getInstance()->db_rollback();
             *      }
             *  }
             */
            return $this->send_email_365();
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Send emails directly via Exchange Online (SMTP AUTH, STARTTLS) without using mail().
     * - Reads SMTP config from api/library/config.ini under [smtp]
     *   host (default smtp.office365.com), port (default 587), username, password, security (STARTTLS|TLS|PLAIN)
     * - Uses [mail] mail_from and mail_envelope_from for header/envelope addresses
     * - Mirrors send_email() queue draining and logging behavior
     * @return bool
     * @throws Exception
     */
    public function send_email_365 () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            // Load mail and smtp configuration
            $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
            if ($config === false) {
                throw new Exception('[' . __LINE__ . '] - Unable to read config.ini');
            }

            $mailFrom = $config['mail']['mail_from'] ?? '';
            $mailEnvelopeFrom = $config['mail']['mail_envelope_from'] ?? $mailFrom;

            $smtpConf = [
                'host' => $config['smtp']['host'] ?? 'smtp.office365.com',
                'port' => isset($config['smtp']['port']) ? intval($config['smtp']['port']) : 587,
                'username' => $config['smtp']['m_username'] ?? ($config['smtp']['username'] ?? ''),
                'password' => $config['smtp']['m_password'] ?? ($config['smtp']['password'] ?? ''),
                'security' => strtoupper($config['smtp']['security'] ?? 'STARTTLS'), // STARTTLS|TLS|PLAIN
                'timeout' => isset($config['smtp']['timeout']) ? intval($config['smtp']['timeout']) : 30,
            ];

            if (empty($smtpConf['username']) || empty($smtpConf['password'])) {
                throw new Exception('[' . __LINE__ . '] - SMTP credentials missing (username/password)');
            }

            $arr_emailSend = Class_db::getInstance()->db_select('email_send', array(), 'email_id', '20');
            foreach ($arr_emailSend as $emailSend) {
                $status = '23'; // fail by default
                try {
                    // Build MIME message (reuse logic similar to send_email())
                    $uid = md5(uniqid(time()));

                    // Build headers for SMTP DATA (Subject/To must be included here)
                    $headers = '';
                    if (!empty($mailFrom)) {
                        $headers .= "From: " . $mailFrom . "\r\n";
                    }
                    $headers .= "To: " . $emailSend['email_address'] . "\r\n";
                    $headers .= "Subject: " . $emailSend['email_title'] . "\r\n";
                    $headers .= "Date: " . date('r') . "\r\n";
                    $headers .= "Message-ID: <" . uniqid('', true) . "@gems2>\r\n";
                    $headers .= "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n";

                    // Body with parts
                    $body = "--" . $uid . "\r\n";
                    $body .= "Content-type:text/html; charset=utf-8\r\n";
                    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                    $body .= $emailSend['email_html'] . "\r\n\r\n";

                    if (!empty($emailSend['email_attachment']) && !empty($emailSend['email_filename'])) {
                        $file = $emailSend['email_attachment'];
                        $content = @file_get_contents($file);
                        if ($content === false) {
                            throw new Exception('[' . __LINE__ . '] - Failed to read attachment');
                        }
                        $content = chunk_split(base64_encode($content));
                        $filename = $emailSend['email_filename'];

                        $body .= "--" . $uid . "\r\n";
                        $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\r\n";
                        $body .= "Content-Transfer-Encoding: base64\r\n";
                        $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
                        $body .= $content . "\r\n\r\n";
                    }
                    $body .= "--" . $uid . "--\r\n";

                    // Perform SMTP send
                    $this->smtp_send_exchange($smtpConf, $mailEnvelopeFrom ?: $mailFrom, $emailSend['email_address'], $headers, $body);
                    $status = '22'; // success
                } catch (Exception $ey) {
                    $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'SMTP send failed: ' . $ey->getMessage());
                }

                // Move to log regardless of success/failure
                try {
                    Class_db::getInstance()->db_beginTransaction();
                    Class_db::getInstance()->db_insert('email_log', array(
                        'email_template_id' => $emailSend['email_template_id'],
                        'email_address' => $emailSend['email_address'],
                        'email_title' => $emailSend['email_title'],
                        'email_html' => $emailSend['email_html'],
                        'user_id' => (is_null($emailSend['user_id']) ? '' : $emailSend['user_id']),
                        'email_retry_no' => $emailSend['email_retry_no'],
                        'email_attachment' => $this->fn_general->clear_null($emailSend['email_attachment']),
                        'email_filename' => $this->fn_general->clear_null($emailSend['email_filename']),
                        'email_id' => $emailSend['email_id'],
                        'email_log_status' => $status
                    ));
                    Class_db::getInstance()->db_delete('email_send', array('email_id' => $emailSend['email_id']));
                    Class_db::getInstance()->db_commit();
                } catch (Exception $ez) {
                    Class_db::getInstance()->db_rollback();
                }
            }

            return true;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Low-level SMTP sender for Exchange/Office 365 using STARTTLS and AUTH LOGIN.
     * @param array $smtpConf {host,port,username,password,security,timeout}
     * @param string $envelopeFrom
     * @param string $rcptTo
     * @param string $headers Full RFC-822 headers (must include From/To/Subject/MIME-Version/Content-Type)
     * @param string $body MIME body
     * @throws Exception on any SMTP failure
     */
    private function smtp_send_exchange(array $smtpConf, $envelopeFrom, $rcptTo, $headers, $body) {
        $host = $smtpConf['host'];
        $port = $smtpConf['port'];
        $security = strtoupper($smtpConf['security']);
        $username = $smtpConf['username'];
        $password = $smtpConf['password'];
        $timeout = $smtpConf['timeout'];

        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Connecting SMTP {$host}:{$port} ({$security})");

        $errno = 0; $errstr = '';
        $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new Exception('[' . __LINE__ . "] - Unable to connect SMTP: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, $timeout);

        list($code,) = $this->smtp_read_response($fp);
        if ($code !== 220) {
            fclose($fp);
            throw new Exception('[' . __LINE__ . "] - SMTP greeting failed ({$code})");
        }

        $this->smtp_cmd($fp, 'EHLO gems2.local');

        if ($security === 'STARTTLS') {
            $this->smtp_cmd_expect($fp, 'STARTTLS', 220);
            $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (!@stream_socket_enable_crypto($fp, true, $cryptoMethod)) {
                fclose($fp);
                throw new Exception('[' . __LINE__ . '] - STARTTLS negotiation failed');
            }
            // EHLO again after STARTTLS
            $this->smtp_cmd($fp, 'EHLO gems2.local');
        }

        // AUTH LOGIN
        $this->smtp_cmd_expect($fp, 'AUTH LOGIN', 334);
        $this->smtp_cmd_expect($fp, base64_encode($username), 334);
        $this->smtp_cmd_expect($fp, base64_encode($password), 235);

        // MAIL FROM / RCPT TO
        $this->smtp_cmd_expect($fp, 'MAIL FROM:<' . $envelopeFrom . '>', 250);
        $this->smtp_cmd_expect($fp, 'RCPT TO:<' . $rcptTo . '>', 250);

        // DATA
        $this->smtp_cmd_expect($fp, 'DATA', 354);

        // Build final DATA block: headers + CRLF + body
        $data = rtrim($headers, "\r\n") . "\r\n\r\n" . $this->smtp_dot_stuff($body) . "\r\n.\r\n";
        $this->smtp_write($fp, $data);
        list($code2,) = $this->smtp_read_response($fp);
        if ($code2 !== 250) {
            $this->smtp_cmd($fp, 'QUIT');
            fclose($fp);
            throw new Exception('[' . __LINE__ . "] - SMTP DATA not accepted ({$code2})");
        }

        $this->smtp_cmd($fp, 'QUIT');
        fclose($fp);
    }

    // Helper: write a command with CRLF
    private function smtp_write($fp, $data) {
        $written = @fwrite($fp, $data);
        if ($written === false) {
            throw new Exception('[' . __LINE__ . '] - SMTP write failed');
        }
    }

    // Helper: send a command and ignore specific response content
    private function smtp_cmd($fp, $cmd) {
        $this->smtp_write($fp, $cmd . "\r\n");
        $this->smtp_read_response($fp); // consume
    }

    // Helper: send a command and expect a specific status code
    private function smtp_cmd_expect($fp, $cmd, $expectCode) {
        $this->smtp_write($fp, $cmd . "\r\n");
        list($code,) = $this->smtp_read_response($fp);
        if ($code !== $expectCode) {
            throw new Exception('[' . __LINE__ . "] - Unexpected SMTP response code {$code} for '{$cmd}' (expected {$expectCode})");
        }
    }

    // Helper: read SMTP response, returns [code, lines]
    private function smtp_read_response($fp) {
        $lines = [];
        while (($line = @fgets($fp, 515)) !== false) { // 512 chars + CRLF
            $lines[] = rtrim($line, "\r\n");
            if (preg_match('/^(\d{3})[\s]/', $line, $m)) {
                $code = intval($m[1]);
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'SMTP <= ' . implode(' | ', $lines));
                return [$code, $lines];
            }
        }
        throw new Exception('[' . __LINE__ . '] - SMTP read timeout or connection closed');
    }

    // Helper: dot-stuff the body per RFC 5321 Section 4.5.2
    private function smtp_dot_stuff($body) {
        $body = preg_replace('/\r?\n/', "\r\n", $body); // normalize to CRLF
        $body = preg_replace('/\n\./', "\n..", $body); // dot-stuff lines beginning with '.'
        if (strpos($body, "\r\n.") === 0) {
            $body = ".." . substr($body, 1);
        }
        return $body;
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
            // Reuse direct SMTP sender (no queue)
            /* ------------------------------------------------------------------
             * LEGACY EXPRESS IMPLEMENTATION (mail()) retained for rollback:
             *  $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
             *  $mailFrom = $config['mail']['mail_from'] ?? 'ict-support@globalfm.com.my';
             *  $mailEnvelopeFrom = $config['mail']['mail_envelope_from'] ?? $mailFrom;
             *  $uid = md5(uniqid(time()));
             *  $header = "From: " . $mailFrom . "\r\n";
             *  $header .= "MIME-Version: 1.0\r\n";
             *  $header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n\r\n";
             *  $nmessage = "--" . $uid . "\r\n";
             *  $nmessage .= "Content-type:text/html; charset=utf-8\n";
             *  $nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
             *  $nmessage .= $content . "\r\n\r\n";
             *  $nmessage .= "--" . $uid . "\r\n"; // (no attachment in legacy express path)
             *  mail($receiver, $title, $nmessage, $header, '-f' . $mailEnvelopeFrom);
             */
            $this->send_email_365_direct($receiver, $title, $content);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Direct SMTP (Exchange 365) send without touching the email queue.
     * Builds a MIME message (optionally with one attachment) and sends immediately.
     * @param string $to Recipient email address
     * @param string $subject Subject line
     * @param string $htmlBody HTML body content
     * @param string $attachmentPath (optional) full path to file to attach
     * @param string $attachmentName (optional) override filename shown to user
     * @return bool
     * @throws Exception
     */
    public function send_email_365_direct($to, $subject, $htmlBody, $attachmentPath = '', $attachmentName = '') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($to)) {
                throw new Exception('[' . __LINE__ . '] - Parameter to empty');
            }
            if (empty($subject)) {
                throw new Exception('[' . __LINE__ . '] - Parameter subject empty');
            }
            if (empty($htmlBody)) {
                $htmlBody = '<html><body><p>(Empty Body)</p></body></html>';
            }

            // Load config
            $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
            if ($config === false) {
                throw new Exception('[' . __LINE__ . '] - Unable to read config.ini');
            }
            $mailFrom = $config['mail']['mail_from'] ?? '';
            $mailEnvelopeFrom = $config['mail']['mail_envelope_from'] ?? $mailFrom;
            $smtpConf = [
                'host' => $config['smtp']['host'] ?? 'smtp.office365.com',
                'port' => isset($config['smtp']['port']) ? intval($config['smtp']['port']) : 587,
                'username' => $config['smtp']['m_username'] ?? ($config['smtp']['username'] ?? ''),
                'password' => $config['smtp']['m_password'] ?? ($config['smtp']['password'] ?? ''),
                'security' => strtoupper($config['smtp']['security'] ?? 'STARTTLS'),
                'timeout' => isset($config['smtp']['timeout']) ? intval($config['smtp']['timeout']) : 30,
            ];
            if (empty($smtpConf['username']) || empty($smtpConf['password'])) {
                throw new Exception('[' . __LINE__ . '] - SMTP credentials missing');
            }

            $boundary = md5(uniqid(time()));
            $headers = '';
            if (!empty($mailFrom)) {
                $headers .= 'From: ' . $mailFrom . "\r\n";
            }
            $headers .= 'To: ' . $to . "\r\n";
            $headers .= 'Subject: ' . $subject . "\r\n";
            $headers .= 'Date: ' . date('r') . "\r\n";
            $headers .= 'Message-ID: <' . uniqid('', true) . '@gems2>' . "\r\n";
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n";

            $body = '--' . $boundary . "\r\n";
            $body .= "Content-type:text/html; charset=utf-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $htmlBody . "\r\n\r\n";

            if (!empty($attachmentPath) && is_file($attachmentPath)) {
                $raw = @file_get_contents($attachmentPath);
                if ($raw === false) {
                    throw new Exception('[' . __LINE__ . '] - Unable to read attachment');
                }
                $encoded = chunk_split(base64_encode($raw));
                $fname = !empty($attachmentName) ? $attachmentName : basename($attachmentPath);
                $body .= '--' . $boundary . "\r\n";
                $body .= 'Content-Type: application/octet-stream; name="' . $fname . '"' . "\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= 'Content-Disposition: attachment; filename="' . $fname . '"' . "\r\n\r\n";
                $body .= $encoded . "\r\n\r\n";
            }
            $body .= '--' . $boundary . "--\r\n";

            $this->smtp_send_exchange($smtpConf, $mailEnvelopeFrom ?: $mailFrom, $to, $headers, $body);
            return true;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Direct SMTP (Exchange 365) send with an inline image referenced by CID.
     * Builds a multipart/related MIME so the image renders inside the HTML body.
     * @param string $to Recipient email address
     * @param string $subject Subject line
     * @param string $htmlBody HTML body content which should include <img src="cid:...">
     * @param string $imagePath Absolute file path of the image (jpg/png)
     * @param string $imageName Optional filename to present to the client
     * @param string $imageCid Content-ID used in the HTML (default 'inline_image')
     * @return bool
     * @throws Exception
     */
    public function send_email_365_inline_image($to, $subject, $htmlBody, $imagePath, $imageName = '', $imageCid = 'inline_image') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            if (empty($to)) throw new Exception('[' . __LINE__ . '] - Parameter to empty');
            if (empty($subject)) throw new Exception('[' . __LINE__ . '] - Parameter subject empty');
            if (empty($htmlBody)) $htmlBody = '<html><body><p>(Empty Body)</p></body></html>';
            if (empty($imagePath) || !is_file($imagePath)) throw new Exception('[' . __LINE__ . '] - Inline image not found');

            // Load config
            $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
            if ($config === false) {
                throw new Exception('[' . __LINE__ . '] - Unable to read config.ini');
            }
            $mailFrom = $config['mail']['mail_from'] ?? '';
            $mailEnvelopeFrom = $config['mail']['mail_envelope_from'] ?? $mailFrom;
            $smtpConf = [
                'host' => $config['smtp']['host'] ?? 'smtp.office365.com',
                'port' => isset($config['smtp']['port']) ? intval($config['smtp']['port']) : 587,
                'username' => $config['smtp']['m_username'] ?? ($config['smtp']['username'] ?? ''),
                'password' => $config['smtp']['m_password'] ?? ($config['smtp']['password'] ?? ''),
                'security' => strtoupper($config['smtp']['security'] ?? 'STARTTLS'),
                'timeout' => isset($config['smtp']['timeout']) ? intval($config['smtp']['timeout']) : 30,
            ];
            if (empty($smtpConf['username']) || empty($smtpConf['password'])) {
                throw new Exception('[' . __LINE__ . '] - SMTP credentials missing');
            }

            $imageRaw = @file_get_contents($imagePath);
            if ($imageRaw === false) throw new Exception('[' . __LINE__ . '] - Unable to read image');
            $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $mime = 'image/jpeg';
            if ($ext === 'png') $mime = 'image/png';
            elseif ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
            elseif ($ext === 'heic' || $ext === 'heif') $mime = 'image/heic';
            else {
                // try mime_content_type
                $det = function_exists('mime_content_type') ? mime_content_type($imagePath) : '';
                if ($det && stripos($det, 'png') !== false) $mime = 'image/png';
                elseif ($det && stripos($det, 'heic') !== false) $mime = 'image/heic';
            }
            $encoded = chunk_split(base64_encode($imageRaw));
            $fname = !empty($imageName) ? $imageName : basename($imagePath);

            // Build multipart/related
            $boundary = md5(uniqid('rel', true));
            $headers = '';
            if (!empty($mailFrom)) { $headers .= 'From: ' . $mailFrom . "\r\n"; }
            $headers .= 'To: ' . $to . "\r\n";
            $headers .= 'Subject: ' . $subject . "\r\n";
            $headers .= 'Date: ' . date('r') . "\r\n";
            $headers .= 'Message-ID: <' . uniqid('', true) . '@gems2>' . "\r\n";
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-Type: multipart/related; boundary="' . $boundary . '"; type="text/html"' . "\r\n";

            $body = '';
            // HTML part
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/html; charset=utf-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $htmlBody . "\r\n\r\n";
            // Image part (inline)
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Type: ' . $mime . '; name="' . $fname . '"' . "\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= 'Content-ID: <' . $imageCid . '>' . "\r\n";
            $body .= 'Content-Disposition: inline; filename="' . $fname . '"' . "\r\n\r\n";
            $body .= $encoded . "\r\n\r\n";
            $body .= '--' . $boundary . "--\r\n";

            $this->smtp_send_exchange($smtpConf, $mailEnvelopeFrom ?: $mailFrom, $to, $headers, $body);
            return true;
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
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode()); // Keep this commented out or re-enable if you want exceptions to stop execution
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function send_push_notification () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $notiSends = Class_db::getInstance()->db_select('noti_send', array(), 'noti_id', '100');
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
                        'noti_html'=>$notiSend['noti_html'], 'user_id'=> (is_null($notiSend['user_id'])?'':$notiSend['user_id']), 'noti_id'=>$notiSend['noti_id'], 'noti_log_status'=>$status));
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