<?php

class Class_user {

    private $constant;
    private $fn_general;
    private $fn_email;
    
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
     * @param array $userDetails
     * @param int $type
     * @return array
     * @throws Exception
     */
    public function register_user ($userDetails=array(), $type=0) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($userDetails)) {
                throw new Exception('['.__LINE__.'] - Array userDetails empty');
            }     
            if (empty($type)) {
                throw new Exception('['.__LINE__.'] - Parameter type empty');
            }     
            if (!array_key_exists('userFirstName', $userDetails)) {
                throw new Exception('['.__LINE__.'] - Index userFirstName in array userDetails empty');
            }  
            if (!array_key_exists('userLastName', $userDetails)) {
                throw new Exception('['.__LINE__.'] - Index userLastName in array userDetails empty');
            } 
            if (!array_key_exists('userEmail', $userDetails)) {
                throw new Exception('['.__LINE__.'] - Index userEmail in array userDetails empty');
            } 
            if (!array_key_exists('userMykadNo', $userDetails)) {
                throw new Exception('['.__LINE__.'] - Index userMykadNo in array userDetails empty');
            } 
            if (!array_key_exists('userProfileContactNo', $userDetails)) {
                throw new Exception('['.__LINE__.'] - Index userProfileContactNo in array userDetails empty');
            } 
            if (!array_key_exists('userPassword', $userDetails)) {
                throw new Exception('['.__LINE__.'] - Index userPassword in array userDetails empty');
            }            
            
            $userFirstName = $userDetails['userFirstName'];
            $userLastName = $userDetails['userLastName'];
            $userEmail = $userDetails['userEmail'];
            $userMykadNo = $userDetails['userMykadNo'];
            $userProfileContactNo = $userDetails['userProfileContactNo'];
            $userPassword = $userDetails['userPassword'];
            
            if (Class_db::getInstance()->db_count('sys_user', array('user_email'=>$userEmail)) > 0) {
                throw new Exception('['.__LINE__.'] - Email already exist. Please use different email.', 31);
            }
            
            if ($type === 2) {
                $userId = Class_db::getInstance()->db_insert('sys_user', array('user_email'=>$userEmail, 'user_type'=>strval($type), 'user_password'=>md5($userPassword), 'user_first_name'=>$userFirstName, 
                    'user_last_name'=>$userLastName, 'user_mykad_no'=>$userMykadNo, 'group_id'=>'1', 'user_status'=>'3'));
                $userActivationKey = $this->fn_general->generateRandomString().$userId;
                Class_db::getInstance()->db_update('sys_user', array('user_activation_key'=>$userActivationKey), array('user_id'=>$userId));
                Class_db::getInstance()->db_insert('sys_user_profile', array('user_id'=>$userId, 'user_profile_contact_no'=>$userProfileContactNo));
                Class_db::getInstance()->db_insert('sys_user_role', array('user_id'=>$userId, 'role_id'=>'2'));
                $arr_checkpoint = Class_db::getInstance()->db_select('wfl_checkpoint', array('role_id'=>'2', 'checkpoint_type'=>'<>5'));
                foreach ($arr_checkpoint as $checkpoint) {
                    $checkpointId = $checkpoint['checkpoint_id'];
                    $groupId = $checkpoint['group_id'];
                    //if ($groupId === '1' || is_null($groupId)) {
                    //    Class_db::getInstance()->db_insert('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>$checkpointId));
                    //}
                }
            } else {
                throw new Exception('['.__LINE__.'] - Parameter type invalid ('.$type.')');
            }
            
            return array('userId'=>$userId, 'activationKey'=>$userActivationKey);
        }
        catch(Exception $ex) {   
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $activationInput
     * @return bool|string
     * @throws Exception
     */
    public function activate_user ($activationInput='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($activationInput)) {
                throw new Exception('['.__LINE__.'] - Parameter activationInput empty');
            }    
            if (strlen($activationInput) < 21) { 
                throw new Exception('['.__LINE__.'] - Wrong activation key. Please click the activation link given from your email.', 31);
            }
            
            $userId = substr($activationInput, 20);
            
            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'user_activation_key'=>$activationInput)) == 0) {
                throw new Exception('['.__LINE__.'] - Wrong activation key. Please click the activation link given from your email.', 31);
            }
            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'user_activation_key'=>$activationInput, 'user_status'=>'1')) == 1) {
                throw new Exception('['.__LINE__.'] - Your account already activated. Please login with email as user ID and your registered password.', 31);
            }
                        
            Class_db::getInstance()->db_update('sys_user', array('user_status'=>'1', 'user_time_activate'=>'Now()'), array('user_id'=>$userId));
            return $userId;
        }
        catch(Exception $ex) {   
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $email
     * @return mixed
     * @throws Exception
     */
    public function forgot_password ($email='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($email)) {
                throw new Exception('['.__LINE__.'] - Parameter email empty');
            }

            $sys_user_profile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_email'=>$email, 'user_profile_status'=>'1'));
            if (empty($sys_user_profile)) {
                throw new Exception('['.__LINE__.'] - '.$constant::ERR_FORGOT_PASSWORD_NOT_EXIST, 31);
            }
            $userId = $sys_user_profile['user_id'];

            $temporaryPassword = $this->fn_general->generateRandomString(15);
            Class_db::getInstance()->db_update('sys_user', array('user_password'=>md5($temporaryPassword), 'user_password_temp'=>$temporaryPassword, 'user_time_activate'=>'', 'user_fail_attempt'=>'0', 'user_time_block'=>''), array('user_id'=>$userId));
            
            //$emailParam = array('userName'=>$userName, 'tempPassword'=>$temporaryPassword);
            //$this->fn_email->setup_email($userId, 1, $emailParam);
            $sys_user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$userId), null, 1);
            $content = '<p>Dear '.$sys_user['user_first_name'].',</p>
            <p>Your temporary password is '.$temporaryPassword.'.</p>
            <p>To change your password, please open the mobile apps and key in the given password to login.</p>
            <br /><br />
            <p><i>Note: This is an automail from GEMS 2.0 System. Please do not reply to this email.</i></p>';
            $this->fn_email->send_email_express($email, 'GEMS 2.0 - Temporary Password', $content);
            
            return array('userId'=>$userId, 'tempPassword'=>$temporaryPassword);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $put_vars
     * @throws Exception
     */
    public function update_profile ($userId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;
            
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (!isset($put_vars['userEmail']) || empty($put_vars['userEmail'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter userEmail empty');
            }
            if (!isset($put_vars['userFirstName']) || empty($put_vars['userFirstName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter userFirstName empty');
            }
            if (!isset($put_vars['userContactNo']) || empty($put_vars['userContactNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter userContactNo empty');
            }
            if (!isset($put_vars['designationId']) || empty($put_vars['designationId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationId empty');
            }
            if (!isset($put_vars['roles']) || empty($put_vars['roles'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter roles empty');
            }
            if (!isset($put_vars['userType']) || empty($put_vars['userType'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter userType empty');
            }
            if (!isset($put_vars['siteId']) || empty($put_vars['siteId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }

            $userEmail = $put_vars['userEmail'];
            $userFirstName = $put_vars['userFirstName'];
            $userContactNo = $put_vars['userContactNo'];
            $designationId = $put_vars['designationId'];
            $rolesStr = $put_vars['roles'];
            $userType = $put_vars['userType'];
            $siteId = $put_vars['siteId'];

            $curSite = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);
            if ($curSite !== $siteId) {
                if (Class_db::getInstance()->db_count('mw_ppm_group_user', array('ppm_group_user.user_id'=>$userId, 'sys_user.site_id'=>$curSite)) > 0) {
                    throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_EXIST_IN_GROUP, 31);
                }
            }

            if ($userType === '1') {
                $groupId = '1';
            } else if ($userType === '2') {
                $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
                //Class_db::getInstance()->db_update('sys_user_role', array('group_id'=>$groupId), array('user_id'=>$userId, 'role_id'=>'6'));
                //Class_db::getInstance()->db_update('wfl_checkpoint_user', array('group_id'=>$groupId), array('user_id'=>$userId, 'role_id'=>'6'));
            } else {
                throw new Exception('['.__LINE__.'] - Parameter userType invalid ('.$userType.')');
            }

            if ($userType === '1' || $userType === '2') {
                $roles = explode(',', $rolesStr);
                $dbRoles = Class_db::getInstance()->db_select('sys_user_role', array('user_id'=>$userId));
                foreach ($dbRoles as $dbRole) {
                    $curRole = $dbRole['role_id'];
                    $key = array_search($curRole, $roles);
                    if ($key !== false) {
                        if ($dbRole['group_id'] !== $groupId) {
                            Class_db::getInstance()->db_update('sys_user_role', array('group_id'=>$groupId), array('user_id'=>$userId, 'role_id'=>$curRole));
                            Class_db::getInstance()->db_update('wfl_checkpoint_user', array('group_id'=>$groupId), array('user_id'=>$userId, 'role_id'=>$curRole));
                            if ($curRole === '3' || $curRole === '4' || $curRole === '5' || $curRole === '8') {
                                $ppmGroupUsers = Class_db::getInstance()->db_select('ppm_group_user', array('user_id'=>$userId));
                                foreach ($ppmGroupUsers as $ppmGroupUser) {
                                    if (Class_db::getInstance()->db_select_col('ppm_group', array('ppm_group_id'=>$ppmGroupUser['ppm_group_id']), 'role_id', null, 1) == $curRole) {
                                        Class_db::getInstance()->db_delete('ppm_group_user', array('ppm_group_user_id' => $ppmGroupUser['ppm_group_user_id']));
                                    }
                                }
                            }
                        }
                        array_splice($roles, $key, 1);
                    } else {
                        Class_db::getInstance()->db_delete('sys_user_role', array('user_id'=>$userId, 'role_id'=>$curRole));
                        Class_db::getInstance()->db_delete('wfl_checkpoint_user', array('user_id'=>$userId, 'role_id'=>$curRole));
                        if ($curRole === '3' || $curRole === '4' || $curRole === '5' || $curRole === '8') {
                            $ppmGroupUsers = Class_db::getInstance()->db_select('ppm_group_user', array('user_id'=>$userId));
                            foreach ($ppmGroupUsers as $ppmGroupUser) {
                                if (Class_db::getInstance()->db_select_col('ppm_group', array('ppm_group_id'=>$ppmGroupUser['ppm_group_id']), 'role_id', null, 1) == $curRole) {
                                    Class_db::getInstance()->db_delete('ppm_group_user', array('ppm_group_user_id' => $ppmGroupUser['ppm_group_user_id']));
                                }
                            }
                        }
                    }
                }
                foreach ($roles as $role) {
                    Class_db::getInstance()->db_insert('sys_user_role', array('user_id'=>$userId, 'role_id'=>$role, 'group_id'=>$groupId));
                    $checkpoints = Class_db::getInstance()->db_select('wfl_checkpoint', array('checkpoint_type'=>'<>3', 'role_id'=>$role));
                    foreach ($checkpoints as $checkpoint) {
                        $checkpointId = $checkpoint['checkpoint_id'];
                        if ($checkpointId == '3' && $role == '4') {
                            $groupId_ = $groupId;
                        } else {
                            $groupId_ = $checkpoint['group_id'];
                        }
                        if ($groupId_ === $groupId || is_null($groupId_)) {
                            Class_db::getInstance()->db_insert('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>$checkpointId, 'role_id'=>$role, 'group_id'=>$groupId_));
                        }
                    }
                }
            }

            Class_db::getInstance()->db_update('sys_user', array('user_first_name'=>$userFirstName, 'site_id'=>$siteId), array('user_id'=>$userId));
            Class_db::getInstance()->db_update('sys_user_profile', array('user_email'=>$userEmail, 'user_contact_no'=>$userContactNo, 'designation_id'=>$designationId), array('user_id'=>$userId, 'user_profile_status'=>'1'));

        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $name
     * @param $phoneNo
     * @param $uploadId
     * @return string
     * @throws Exception
     */
    public function update_profile_m ($userId, $name, $phoneNo, $uploadId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $sys_user = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$userId), null, 1);
            if (!empty($name)) {
                Class_db::getInstance()->db_update('sys_user', array('user_first_name'=>$name), array('user_id'=>$userId));
            }
            if (!empty($phoneNo)) {
                Class_db::getInstance()->db_update('sys_user_profile', array('user_contact_no'=>$phoneNo), array('user_id'=>$userId, 'user_profile_status'=>'1'));
            }
            if (!empty($uploadId)) {
                if (!empty($sys_user['upload_id'])) {
                    Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$sys_user['upload_id']));
                }

                Class_db::getInstance()->db_update('sys_user', array('upload_id'=>$uploadId), array('user_id'=>$userId));
                $upload = Class_db::getInstance()->db_select_single('vw_sys_upload', array('upload_id'=>$uploadId), null, 1);
                $docUrl = $constant::URL.$upload['upload_folder'].'/'.$upload['upload_filename'].'.'.$upload['upload_extension'];
                return $docUrl;
            }
            return '';
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $put_vars
     * @throws Exception
     */
    public function change_password ($userId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;
            
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            } 
            if (!isset($put_vars['oldPassword']) || empty($put_vars['oldPassword'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter oldPassword empty');
            }
            if (!isset($put_vars['newPassword']) || empty($put_vars['newPassword'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter newPassword empty');
            }
            
            $oldPassword = $put_vars['oldPassword'];
            $newPassword = $put_vars['newPassword'];

            if ($oldPassword === $newPassword){
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHANGE_PASSWORD_OLD_NEW_SAME, 31);
            }
            if (Class_db::getInstance()->db_count('sys_user', array('user_password'=>md5($oldPassword), 'user_id'=>$userId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHANGE_PASSWORD_WRONG_CURRENT, 31);
            }
                        
            Class_db::getInstance()->db_update('sys_user', array('user_password'=>md5($newPassword)), array('user_id'=>$userId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $put_vars
     * @throws Exception
     */
    public function edit_password ($userId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (!isset($put_vars['newPassword']) || empty($put_vars['newPassword'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter newPassword empty');
            }

            $newPassword = $put_vars['newPassword'];
            Class_db::getInstance()->db_update('sys_user', array('user_password'=>md5($newPassword)), array('user_id'=>$userId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $userDetails
     * @return mixed
     * @throws Exception
     */
    public function add_user ($userDetails=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($userDetails)) {
                throw new Exception('['.__LINE__.'] - Array userDetails empty');
            }
            if (!array_key_exists('userName', $userDetails) && empty($userDetails['userName'])) {
                throw new Exception('['.__LINE__.'] - Parameter userName empty');
            }
            if (!array_key_exists('userFirstName', $userDetails) && empty($userDetails['userFirstName'])) {
                throw new Exception('['.__LINE__.'] - Parameter userFirstName empty');
            }
            if (!array_key_exists('userEmail', $userDetails) && empty($userDetails['userEmail'])) {
                throw new Exception('['.__LINE__.'] - Parameter userEmail empty');
            }
            if (!array_key_exists('userContactNo', $userDetails) && empty($userDetails['userContactNo'])) {
                throw new Exception('['.__LINE__.'] - Parameter userProfileContactNo empty');
            }
            if (!array_key_exists('userPassword', $userDetails) && empty($userDetails['userPassword'])) {
                throw new Exception('['.__LINE__.'] - Parameter userPassword empty');
            }
            if (!array_key_exists('userType', $userDetails) && empty($userDetails['userType'])) {
                throw new Exception('['.__LINE__.'] - Parameter userType empty');
            }
            if (!array_key_exists('roles', $userDetails) && empty($userDetails['roles'])) {
                throw new Exception('['.__LINE__.'] - Parameter roles empty');
            }
            if (!array_key_exists('designationId', $userDetails) && empty($userDetails['designationId'])) {
                throw new Exception('['.__LINE__.'] - Parameter designationId empty');
            }
            if (!array_key_exists('siteId', $userDetails) && empty($userDetails['siteId'])) {
                throw new Exception('['.__LINE__.'] - Parameter siteId empty');
            }

            $userName = $userDetails['userName'];
            $userFirstName = $userDetails['userFirstName'];
            $userEmail = $userDetails['userEmail'];
            $userContactNo = $userDetails['userContactNo'];
            $userPassword = $userDetails['userPassword'];
            $designationId = $userDetails['designationId'];
            $userType = $userDetails['userType'];
            $rolesStr = $userDetails['roles'];
            $siteId = $userDetails['siteId'];

            if ($userType == '1') {
                $groupId = '1';
            } else if ($userType == '2') {
                $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            } else {
                throw new Exception('['.__LINE__.'] - Parameter userType invalid ('.$userType.')');
            }

            if (Class_db::getInstance()->db_count('sys_user', array('user_name'=>$userName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_ADD_SIMILAR_USERNAME, 31);
            }
            if (Class_db::getInstance()->db_count('sys_user_profile', array('user_email'=>$userEmail)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_ADD_SIMILAR_EMAIL, 31);
            }

            $userId = Class_db::getInstance()->db_insert('sys_user', array('user_name'=>$userName, 'user_type'=>$userType, 'user_password'=>md5($userPassword), 'user_first_name'=>$userFirstName, 'site_id'=>$siteId, 'user_status'=>'1'));
            Class_db::getInstance()->db_insert('sys_user_profile', array('user_id'=>$userId, 'user_email'=>$userEmail, 'user_contact_no'=>$userContactNo, 'designation_id'=>$designationId));
            Class_db::getInstance()->db_insert('sys_user_group', array('user_id'=>$userId, 'group_id'=>$groupId));
            $roles = explode(',', $rolesStr);
            foreach ($roles as $role) {
                Class_db::getInstance()->db_insert('sys_user_role', array('user_id'=>$userId, 'role_id'=>$role, 'group_id'=>$groupId));
                $checkpoints = Class_db::getInstance()->db_select('wfl_checkpoint', array('checkpoint_type'=>'<>3', 'role_id'=>$role));
                foreach ($checkpoints as $checkpoint) {
                    $checkpointId = $checkpoint['checkpoint_id'];
                    if ($checkpointId == '3' && $role == '4') {
                        $groupId_ = $groupId;
                    } else {
                        $groupId_ = $checkpoint['group_id'];
                    }
                    if ($groupId_ === $groupId || is_null($groupId_)) {
                        Class_db::getInstance()->db_insert('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>$checkpointId, 'role_id'=>$role, 'group_id'=>$groupId));
                    }
                }
            }

            return $userId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_users() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $users = Class_db::getInstance()->db_select('vw_user_list');
            foreach ($users as $user) {
                $row_result['userId'] = $user['user_id'];
                $row_result['userName'] = $user['user_name'];
                $row_result['userType'] = $user['user_type'];
                $row_result['userFirstName'] = $user['user_first_name'];
                $row_result['userLastName'] = $user['user_last_name'];
                $row_result['userFullName'] = $user['user_first_name'].' '.$user['user_last_name'];
                $row_result['userMykadNo'] = $this->fn_general->clear_null($user['user_mykad_no']);
                $row_result['userContactNo'] = $this->fn_general->clear_null($user['user_contact_no']);
                $row_result['userEmail'] = $this->fn_general->clear_null($user['user_email']);
                $row_result['designationId'] = $this->fn_general->clear_null($user['designation_id']);
                $row_result['roles'] = $this->fn_general->clear_null($user['roles']);
                $row_result['groupId'] = $this->fn_general->clear_null($user['group_id']);
                $row_result['userStatus'] = $user['user_status'];
                $row_result['siteId'] = $this->fn_general->clear_null($user['site_id']);
                array_push($result, $row_result);
            }
            return $result;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }


    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function get_user ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $result = array();
            $user = Class_db::getInstance()->db_select_single('vw_user_list', array('sys_user.user_id'=>$userId), null, 1);
            $result['userId'] = $user['user_id'];
            $result['userName'] = $user['user_name'];
            $result['userType'] = $user['user_type'];
            $result['userFirstName'] = $user['user_first_name'];
            $result['userLastName'] = $user['user_last_name'];
            $result['userFullName'] = $user['user_first_name'].' '.$user['user_last_name'];
            $result['userMykadNo'] = $this->fn_general->clear_null($user['user_mykad_no']);
            $result['userContactNo'] = $this->fn_general->clear_null($user['user_contact_no']);
            $result['userEmail'] = $this->fn_general->clear_null($user['user_email']);
            $result['designationId'] = $this->fn_general->clear_null($user['designation_id']);
            $result['roles'] = $this->fn_general->clear_null($user['roles']);
            $result['groupId'] = $user['group_id'];
            $result['userStatus'] = $user['user_status'];
            $result['siteId'] = $this->fn_general->clear_null($user['site_id']);
            $result['clientId'] = '';

            if (!empty($result['siteId'])) {
                $result['clientId'] = Class_db::getInstance()->db_select_col('cli_site', array('site_id' => $result['siteId']), 'client_id');
            }

            return $result;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_user_by_role() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $userData = Class_db::getInstance()->db_select('vw_user_by_role');
            foreach ($userData as $data) {
                $row_result['roleId'] = $data['role_id'];
                $row_result['total'] = $data['total'];
                array_push($result, $row_result);
            }

            return $result;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @throws Exception
     */
    public function deactivate_profile ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'user_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('sys_user', array('user_status'=>'2'), array('user_id'=>$userId));
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @throws Exception
     */
    public function activate_profile ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'user_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('sys_user', array('user_status'=>'1'), array('user_id'=>$userId));
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $token
     * @throws Exception
     */
    public function save_token ($userId, $token) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($token)) {
                throw new Exception('[' . __LINE__ . '] - Parameter token empty');
            }

            Class_db::getInstance()->db_update('sys_user', array('user_token'=>$token), array('user_id'=>$userId));
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}