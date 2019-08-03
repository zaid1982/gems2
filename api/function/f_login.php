<?php
require_once 'src/BeforeValidException.php';
require_once 'src/ExpiredException.php';
require_once 'src/SignatureInvalidException.php';
require_once 'src/JWT.php';

use \Firebase\JWT\JWT;

class Class_login {
     
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
     * @param string $userId
     * @param string $username
     * @return string
     * @throws Exception
     */
    public function create_jwt ($userId='', $username='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($userId === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if ($username === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter username empty');
            }
            
            $key = "gems2";
            $token = array('iss'=>'inventory_sample1/jwt', 'userId'=>$userId, 'username'=>$username, 'iat'=>time(), 'exp'=>time()+10);
            $jwt = JWT::encode($token, $key);              
            return $jwt;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $jwt
     * @return object
     * @throws Exception
     */
    public function check_jwt ($jwt='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($jwt === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter jwt empty');
            }
            
            $key = "gems2";
            JWT::$leeway = 86400; // $leeway in seconds
            $data = JWT::decode(substr($jwt, 7), $key, array('HS256'));
            
            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$data->userId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Token not valid');
            }
            return $data;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $deviceId
     * @throws Exception
     */
    public function check_device_id ($userId, $deviceId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($deviceId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter deviceId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'user_device_id'=>$deviceId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Device ID invalid with this login');
            }
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $arr_roles
     * @return array
     * @throws Exception
     */
    public function get_menu_list ($arr_roles=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($arr_roles)) {
                throw new Exception('[' . __LINE__ . '] - Array arr_roles empty');
            }
            
            $role_list = array();
            foreach ($arr_roles as $roles) {
                array_push($role_list, $roles['roleId']);
            }
            //$this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Roles = '.$role_list[0]);
            $role_str = implode(',', $role_list);            
            //$this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $role_str);
            
            $menu_return = [];
            $nav_index = 0;
            $menu_list = Class_db::getInstance()->db_select('vw_menu', null, null, null, 1, array('roles'=>$role_str));
            foreach ($menu_list as $menu) {                
                //$this->fn_general->log_debug(__FUNCTION__, __LINE__, '$nav_page = '.$menu['nav_page']);
                //$this->fn_general->log_debug(__FUNCTION__, __LINE__, '$nav_index = '.$nav_index);
                if (is_null($menu['nav_second_id'])) {
                    array_push($menu_return, array('navId'=>$menu['nav_id'], 'navDesc'=>$menu['nav_desc'], 'navIcon'=>$menu['nav_icon'], 'navPage'=> $this->fn_general->clear_null($menu['nav_page']), 'navSecond'=>array()));
                    $nav_index++;
                } else {
                    array_push($menu_return[$nav_index-1]['navSecond'], array('navSecondId'=>$menu['nav_second_id'], 'navSecondDesc'=>$menu['nav_second_desc'], 'navSecondPage'=>$menu['nav_second_page']));
                }
            }
            return $menu_return;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $username
     * @param $password
     * @param string $deviceId
     * @return array
     * @throws Exception
     */
    public function check_login ($username, $password, $deviceId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (is_null($username) || $username === '') { 
                throw new Exception('[' . __LINE__ . '] - Parameter username empty');
            } 
            if (is_null($password) || $password === '') { 
                throw new Exception('[' . __LINE__ . '] - Parameter password empty');
            }

            // ^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%]{8,12}$
            $profile = Class_db::getInstance()->db_select_single('vw_profile', array('user_name'=>$username));
            $userId = $profile['user_id'];
            if (empty($profile)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_NOT_EXIST, 31);
            }
            if ($profile['user_password'] !== md5($password)) {
                $timeBlock = $profile['user_fail_attempt'] >= '2' ? 'Now()' : '';
                Class_db::getInstance()->db_update('sys_user', array('user_fail_attempt'=>'|user_fail_attempt + 1', 'user_time_block'=>$timeBlock), array('user_id'=>$userId));
                Class_db::getInstance()->db_commit();
                if ($profile['user_fail_attempt'] >= '2') {
                    throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_BLOCK, 32);
                } else {
                    throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_WRONG_PASSWORD, 32);
                }
            } 
            if ($profile['user_status'] !== '1') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_NOT_ACTIVE, 31);
            }
            if ($profile['user_fail_attempt'] >= '3') {
                if (!empty($profile['minute_block']) && $profile['minute_block'] <= '10') {
                    throw new Exception('[' . __LINE__ . '] - ' . $constant::ERR_LOGIN_BLOCK, 31);
                }
            }

            $result = array();
            $arr_roles = Class_db::getInstance()->db_select('vw_roles', array(), null, null, null, array('user_id'=>$userId));
            $token = $this->create_jwt($userId, $username);

            $result['token'] = $token;
            $result['userId'] = $userId;
            $result['userName'] = $username;
            $result['userFirstName'] = $profile['user_first_name'];
            $result['userLastName'] = $profile['user_last_name'];
            $result['userType'] = $profile['user_type'];
            $result['userMykadNo'] = $this->fn_general->clear_null($profile['user_mykad_no']);
            $result['userEmail'] = $profile['user_email'];
            $result['userContactNo'] = $profile['user_contact_no'];
            $result['isFirstTime'] = is_null($profile['user_time_activate']) ? 'Yes' : 'No';
            $result['address']['addressDesc'] = $this->fn_general->clear_null($profile['address_desc']);
            $result['address']['addressPostcode'] = $this->fn_general->clear_null($profile['address_postcode']);            
            $result['address']['addressCity'] = $this->fn_general->clear_null($profile['address_city']);          
            $result['address']['addressState'] = $this->fn_general->clear_null($profile['state_desc']);
            $result['roles'] = $arr_roles;
            if (!empty($profile['upload_id'])) {
                $upload = Class_db::getInstance()->db_select_single('vw_sys_upload', array('upload_id'=>$profile['upload_id']), null, 1);
                $result['imgUrl'] = $constant::URL.$upload['upload_folder'].'/'.$upload['upload_filename'].'.'.$upload['upload_extension'];
            } else {
                $result['imgUrl'] = '';
            }
            //$result['menu'] = $fn_login->get_menu_list($arr_roles);

            $arrUpdate = array('user_time_login'=>'Now()', 'user_fail_attempt'=>'0', 'user_time_block'=>'');
            if ($deviceId !== '') {
                $arrUpdate['user_device_id'] = $deviceId;
            }
            Class_db::getInstance()->db_update('sys_user', $arrUpdate, array('user_id'=>$userId));
            return $result;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    public function check_login_web ($username, $password) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (is_null($username) || $username === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter username empty');
            }
            if (is_null($password) || $password === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter password empty');
            }

            $profile = Class_db::getInstance()->db_select_single('vw_profile', array('user_name'=>$username));
            if (empty($profile)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_NOT_EXIST, 31);
            }
            if ($profile['user_password'] !== md5($password)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_WRONG_PASSWORD, 31);
            }
            if ($profile['user_status'] !== '1') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_NOT_ACTIVE, 31);
            }

            $userId = $profile['user_id'];
            $result = array();

            $arr_roles = Class_db::getInstance()->db_select('vw_roles', array(), null, null, null, array('user_id'=>$userId));

            $token = $this->create_jwt($userId, $username);

            $result['token'] = $token;
            $result['userId'] = $userId;
            $result['userName'] = $username;
            $result['userFirstName'] = $profile['user_first_name'];
            $result['userLastName'] = $profile['user_last_name'];
            $result['userType'] = $profile['user_type'];
            $result['userMykadNo'] = $this->fn_general->clear_null($profile['user_mykad_no']);
            $result['userEmail'] = $profile['user_email'];
            $result['userContactNo'] = $profile['user_contact_no'];
            $result['isFirstTime'] = is_null($profile['user_time_activate']) ? 'Yes' : 'No';
            $result['address']['addressDesc'] = $this->fn_general->clear_null($profile['address_desc']);
            $result['address']['addressPostcode'] = $this->fn_general->clear_null($profile['address_postcode']);
            $result['address']['addressCity'] = $this->fn_general->clear_null($profile['address_city']);
            $result['address']['addressState'] = $this->fn_general->clear_null($profile['state_desc']);
            $result['roles'] = $arr_roles;

            $result['menu'] = $this->get_menu_list($arr_roles);

            return $result;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $username
     * @param $password
     * @return
     * @throws Exception
     */
    public function reset_password ($username, $password) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (is_null($username) || $username === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter username empty');
            }
            if (is_null($password) || $password === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter password empty');
            }

            $profile = Class_db::getInstance()->db_select_single('vw_profile', array('user_name'=>$username));
            if (empty($profile)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_NOT_EXIST, 31);
            }
            if ($profile['user_password'] === md5($password)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_WRONG_PASSWORD, 31);
            }
            if ($profile['user_status'] !== '1') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOGIN_NOT_ACTIVE, 31);
            }
            if (!empty($profile['user_time_activate'])) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_USER_ALREADY_ACTIVATED, 31);
            }

            $userId = $profile['user_id'];
            Class_db::getInstance()->db_update('sys_user', array('user_password'=>md5($password), 'user_time_activate'=>'Now()'), array('user_id'=>$userId));
            return $userId;
        }
        catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
    
}