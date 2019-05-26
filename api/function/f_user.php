<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';
require_once 'function/f_email.php';

class Class_user {
     
    private $fn_general;
    private $fn_email;
    
    function __construct() {
        $this->fn_general = new Class_general();
        $this->fn_email = new Class_email();
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
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
                    'user_last_name'=>$userLastName, 'user_mykad_no'=>$userMykadNo, 'group_id'=>'2', 'user_status'=>'3'));
                $userActivationKey = $this->fn_general->generateRandomString().$userId;
                Class_db::getInstance()->db_update('sys_user', array('user_activation_key'=>$userActivationKey), array('user_id'=>$userId));
                Class_db::getInstance()->db_insert('sys_user_profile', array('user_id'=>$userId, 'user_profile_contact_no'=>$userProfileContactNo));
                Class_db::getInstance()->db_insert('sys_user_role', array('user_id'=>$userId, 'role_id'=>'2'));
                $arr_checkpoint = Class_db::getInstance()->db_select('wfl_checkpoint', array('role_id'=>'2', 'checkpoint_type'=>'<>5'));
                foreach ($arr_checkpoint as $checkpoint) {
                    $checkpointId = $checkpoint['checkpoint_id'];
                    $groupId = $checkpoint['group_id'];
                    if ($groupId === '2' || is_null($groupId)) {
                        Class_db::getInstance()->db_insert('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>$checkpointId));
                    }
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
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
     * @param string $userName
     * @return mixed
     * @throws Exception
     */
    public function forgot_password ($userName='') {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            if (empty($userName)) {
                throw new Exception('['.__LINE__.'] - Parameter userName empty');
            } 
            
            $sys_user = Class_db::getInstance()->db_select_single('sys_user', array('user_name'=>$userName));
            if (empty($sys_user)) {
                throw new Exception('['.__LINE__.'] - '.$constant::ERR_FORGOT_PASSWORD_NOT_EXIST, 31);
            }
            
            $userId = $sys_user['user_id'];
            $temporaryPassword = $this->fn_general->generateRandomString(15);
            Class_db::getInstance()->db_update('sys_user', array('user_password'=>md5($temporaryPassword)), array('user_id'=>$userId));
            
            $emailParam = array('userName'=>$userName, 'tempPassword'=>$temporaryPassword); 
            $this->fn_email->setup_email($userId, 1, $emailParam);
            
            return $userId;
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            
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
            
            $userEmail = $put_vars['userEmail'];
            $userFirstName = $put_vars['userFirstName'];
            $userContactNo = $put_vars['userContactNo'];
            $designationId = $put_vars['designationId'];
            $rolesStr = $put_vars['roles'];

            $roles = explode(',', $rolesStr);
            $dbRoles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$userId), 'role_id');
            foreach ($dbRoles as $dbRole) {
                $key = array_search($dbRole, $roles);
                if ($key !== false) {
                    array_splice($roles, $key, 1);
                } else {
                    Class_db::getInstance()->db_delete('sys_user_role', array('user_id'=>$userId, 'role_id'=>$dbRole, 'group_id'=>'2'));
                    Class_db::getInstance()->db_delete('wfl_checkpoint_user', array('user_id'=>$userId, 'role_id'=>$dbRole, 'group_id'=>'2'));
                }
            }
            foreach ($roles as $role) {
                Class_db::getInstance()->db_insert('sys_user_role', array('user_id'=>$userId, 'role_id'=>$role, 'group_id'=>'2'));
                $checkpoints = Class_db::getInstance()->db_select('wfl_checkpoint', array('checkpoint_type'=>'<>3', 'role_id'=>$role));
                foreach ($checkpoints as $checkpoint) {
                    $checkpointId = $checkpoint['checkpoint_id'];
                    $groupId = $checkpoint['group_id'];
                    if ($groupId === '2' || is_null($groupId)) {
                        Class_db::getInstance()->db_insert('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>$checkpointId, 'role_id'=>$role, 'group_id'=>'2'));
                    }
                }
            }

            Class_db::getInstance()->db_update('sys_user', array('user_first_name'=>$userFirstName), array('user_id'=>$userId));
            Class_db::getInstance()->db_update('sys_user_profile', array('user_email'=>$userEmail, 'user_contact_no'=>$userContactNo, 'designation_id'=>$designationId), array('user_id'=>$userId, 'user_profile_status'=>'1'));

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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            
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
     * @param array $userDetails
     * @return mixed
     * @throws Exception
     */
    public function add_user ($userDetails=array()) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
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

            $userName = $userDetails['userName'];
            $userFirstName = $userDetails['userFirstName'];
            $userEmail = $userDetails['userEmail'];
            $userContactNo = $userDetails['userContactNo'];
            $userPassword = $userDetails['userPassword'];
            $designationId = $userDetails['designationId'];
            $userType = $userDetails['userType'];
            $rolesStr = $userDetails['roles'];
            $groupId = '';

            if ($userType == '1') {
                $groupId = '1';
            }
            else if  ($userType == '2') {
                if (!array_key_exists('siteId', $userDetails) && empty($userDetails['siteId'])) {
                    throw new Exception('['.__LINE__.'] - Parameter siteId empty');
                }
                $siteId = $userDetails['siteId'];
                $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            } else {
                throw new Exception('['.__LINE__.'] - Parameter userType invalid ('.$userType.')');
            }

            if (Class_db::getInstance()->db_count('sys_user', array('user_name'=>$userName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_ADD_SIMILAR_USERNAME, 31);
            }

            $userId = Class_db::getInstance()->db_insert('sys_user', array('user_name'=>$userName, 'user_type'=>'1', 'user_password'=>md5($userPassword), 'user_first_name'=>$userFirstName, 'user_time_activate'=>'Now()', 'user_status'=>'1'));
            Class_db::getInstance()->db_insert('sys_user_profile', array('user_id'=>$userId, 'user_email'=>$userEmail, 'user_contact_no'=>$userContactNo, 'designation_id'=>$designationId));
            Class_db::getInstance()->db_insert('sys_user_group', array('user_id'=>$userId, 'group_id'=>$groupId));
            $roles = explode(',', $rolesStr);
            foreach ($roles as $role) {
                Class_db::getInstance()->db_insert('sys_user_role', array('user_id'=>$userId, 'role_id'=>$role, 'group_id'=>$groupId));
                $checkpoints = Class_db::getInstance()->db_select('wfl_checkpoint', array('checkpoint_type'=>'<>3', 'role_id'=>$role));
                foreach ($checkpoints as $checkpoint) {
                    $checkpointId = $checkpoint['checkpoint_id'];
                    $groupId_ = $checkpoint['group_id'];
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $users = Class_db::getInstance()->db_select('vw_user_list');
            foreach ($users as $user) {
                $row_result['userId'] = $user['user_id'];
                $row_result['userName'] = $user['user_name'];
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
    public function get_user($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $result = array();
            $user = Class_db::getInstance()->db_select_single('vw_user_list', array('sys_user.user_id'=>$userId), null, 1);
            $result['userId'] = $user['user_id'];
            $result['userName'] = $user['user_name'];
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

            $site = Class_db::getInstance()->db_select_single('cli_site', array('group_id'=>$result['groupId']));
            $result['clientId'] = !empty($site) ? $site['client_id'] : '';
            $result['siteId'] = !empty($site) ? $site['site_id'] : '';

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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
}