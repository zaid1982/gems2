<?php
/**
 * Created by PhpStorm.
 * User: Zaid
 * Date: 2/26/2019
 * Time: 11:08 PM
 */
require_once 'library/constant.php';
require_once 'function/f_general.php';

/* Error code range - 0500 */
class Class_reference {

    private $fn_general;

    function __construct()
    {
        $this->fn_general = new Class_general();
    }

    private function get_exception($codes, $function, $line, $msg)
    {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else {
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
        }
    }

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property)
    {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property)
    {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_status () {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_status()');

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_status');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['statusId'] = $dataLocal['status_id'];
                $row_result['statusDesc'] = $dataLocal['status_desc'];
                $row_result['statusColor'] = $this->fn_general->clear_null($dataLocal['status_color']);
                $row_result['statusColorCode'] = $this->fn_general->clear_null($dataLocal['status_color_code']);
                $row_result['statusAction'] = $this->fn_general->clear_null($dataLocal['status_action']);
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $stateId
     * @return array
     * @throws Exception
     */
    public function get_state ($stateId=null) {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_state()');

            $result = array();
            if (is_null($stateId)) {
                $arr_dataLocal = Class_db::getInstance()->db_select('ref_state');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['stateId'] = $dataLocal['state_id'];
                    $row_result['stateDesc'] = $dataLocal['state_desc'];
                    $row_result['stateStatus'] = $dataLocal['state_status'];
                    array_push($result, $row_result);
                }
            } else {
                $dataLocal = Class_db::getInstance()->db_select_single('ref_state', array('state_id'=>$stateId), null, 1);
                $result['stateId'] = $dataLocal['state_id'];
                $result['stateDesc'] = $dataLocal['state_desc'];
                $result['stateStatus'] = $dataLocal['state_status'];
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $cityId
     * @return array
     * @throws Exception
     */
    public function get_city ($cityId=null) {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_city()');

            $result = array();
            if (is_null($cityId)) {
                $arr_dataLocal = Class_db::getInstance()->db_select('ref_city');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['cityId'] = $dataLocal['city_id'];
                    $row_result['cityDesc'] = $dataLocal['city_desc'];
                    $row_result['stateId'] = $dataLocal['state_id'];
                    $row_result['cityStatus'] = $dataLocal['city_status'];
                    array_push($result, $row_result);
                }
            } else {
                $dataLocal = Class_db::getInstance()->db_select_single('ref_city', array('city_id'=>$cityId), null, 1);
                $result['cityId'] = $dataLocal['city_id'];
                $result['cityDesc'] = $dataLocal['city_desc'];
                $result['stateId'] = $dataLocal['state_id'];
                $result['cityStatus'] = $dataLocal['city_status'];
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_role () {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_role()');

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_role');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['roleId'] = $dataLocal['role_id'];
                $row_result['roleDesc'] = $dataLocal['role_desc'];
                $row_result['roleType'] = $this->fn_general->clear_null($dataLocal['role_type']);
                $row_result['roleStatus'] = $dataLocal['role_status'];
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $leaveTypeId
     * @return array
     * @throws Exception
     */
    public function get_leave_type ($leaveTypeId=null) {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_leave_type()');

            $result = array();
            if (is_null($leaveTypeId)) {
                $arr_dataLocal = Class_db::getInstance()->db_select('jpl_leave_type');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['leaveTypeId'] = $dataLocal['leave_type_id'];
                    $row_result['leaveTypeDesc'] = $dataLocal['leave_type_desc'];
                    $row_result['leaveTypeAllowed'] = $dataLocal['leave_type_allowed'];
                    $row_result['leaveTypeStatus'] = $dataLocal['leave_type_status'];
                    array_push($result, $row_result);
                }
            } else {
                $dataLocal = Class_db::getInstance()->db_select_single('jpl_leave_type', array('leave_type_id'=>$leaveTypeId), null, 1);
                $result['leaveTypeId'] = $dataLocal['leave_type_id'];
                $result['leaveTypeDesc'] = $dataLocal['leave_type_desc'];
                $result['leaveTypeAllowed'] = $dataLocal['leave_type_allowed'];
                $result['leaveTypeStatus'] = $dataLocal['leave_type_status'];
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function add_leave_type ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering add_leave_type()');

            if (empty($params)) {
                throw new Exception('(ErrCode:0502) [' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('leaveTypeDesc', $params) || empty($params['leaveTypeDesc'])) {
                throw new Exception('(ErrCode:0503) [' . __LINE__ . '] - Parameter leaveTypeDesc empty');
            }
            if (!array_key_exists('leaveTypeAllowed', $params) || $params['leaveTypeAllowed'] == '') {
                throw new Exception('(ErrCode:0520) [' . __LINE__ . '] - Parameter leaveTypeAllowed empty');
            }
            if (!array_key_exists('leaveTypeStatus', $params) || empty($params['leaveTypeStatus'])) {
                throw new Exception('(ErrCode:0504) [' . __LINE__ . '] - Parameter leaveTypeStatus empty');
            }

            $leaveTypeDesc = $params['leaveTypeDesc'];
            $leaveTypeAllowed = $params['leaveTypeAllowed'];
            $leaveTypeStatus = $params['leaveTypeStatus'];

            if (Class_db::getInstance()->db_count('jpl_leave_type', array('leave_type_desc'=>$leaveTypeDesc)) > 0) {
                throw new Exception('(ErrCode:0505) [' . __LINE__ . '] - '.$constant::ERR_LEAVE_TYPE_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('jpl_leave_type', array('leave_type_desc'=>$leaveTypeDesc, 'leave_type_allowed'=>$leaveTypeAllowed, 'leave_type_status'=>$leaveTypeStatus));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $leaveTypeId
     * @param $put_vars
     * @throws Exception
     */
    public function update_leave_type ($leaveTypeId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering update_leave_type()');

            if (empty($leaveTypeId)) {
                throw new Exception('(ErrCode:0506) [' . __LINE__ . '] - Parameter leaveTypeId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('(ErrCode:0507) [' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['leaveTypeDesc']) || empty($put_vars['leaveTypeDesc'])) {
                throw new Exception('(ErrCode:0503) [' . __LINE__ . '] - Parameter leaveTypeDesc empty');
            }
            if (!isset($put_vars['leaveTypeAllowed']) || empty($put_vars['leaveTypeAllowed'])) {
                throw new Exception('(ErrCode:0520) [' . __LINE__ . '] - Parameter leaveTypeAllowed empty');
            }
            if (!isset($put_vars['leaveTypeStatus']) || empty($put_vars['leaveTypeStatus'])) {
                throw new Exception('(ErrCode:0504) [' . __LINE__ . '] - Parameter leaveTypeStatus empty');
            }

            $leaveTypeDesc = $put_vars['leaveTypeDesc'];
            $leaveTypeAllowed = $put_vars['leaveTypeAllowed'];
            $leaveTypeStatus = $put_vars['leaveTypeStatus'];

            if (Class_db::getInstance()->db_count('jpl_leave_type', array('leave_type_desc'=>$leaveTypeDesc, 'leave_type_id'=>'<>'.$leaveTypeId)) > 0) {
                throw new Exception('(ErrCode:0505) [' . __LINE__ . '] - '.$constant::ERR_LEAVE_TYPE_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('jpl_leave_type', array('leave_type_desc'=>$leaveTypeDesc, 'leave_type_allowed'=>$leaveTypeAllowed, 'leave_type_status'=>$leaveTypeStatus), array('leave_type_id'=>$leaveTypeId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $leaveTypeId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_leave_type ($leaveTypeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering deactivate_leave_type()');

            if (empty($leaveTypeId)) {
                throw new Exception('(ErrCode:0506) [' . __LINE__ . '] - Parameter leaveTypeId empty');
            }
            if (Class_db::getInstance()->db_count('jpl_leave_type', array('leave_type_id'=>$leaveTypeId, 'leave_type_status'=>'2')) > 0) {
                throw new Exception('(ErrCode:0508) [' . __LINE__ . '] - '.$constant::ERR_LEAVE_TYPE_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('jpl_leave_type', array('leave_type_status'=>'2'), array('leave_type_id'=>$leaveTypeId));
            return Class_db::getInstance()->db_select_col('jpl_leave_type', array('leave_type_id'=>$leaveTypeId), 'leave_type_desc', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $leaveTypeId
     * @return mixed
     * @throws Exception
     */
    public function activate_leave_type ($leaveTypeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering activate_leave_type()');

            if (empty($leaveTypeId)) {
                throw new Exception('(ErrCode:0506) [' . __LINE__ . '] - Parameter leaveTypeId empty');
            }
            if (Class_db::getInstance()->db_count('jpl_leave_type', array('leave_type_id'=>$leaveTypeId, 'leave_type_status'=>'1')) > 0) {
                throw new Exception('(ErrCode:0509) [' . __LINE__ . '] - '.$constant::ERR_LEAVE_TYPE_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('jpl_leave_type', array('leave_type_status'=>'1'), array('leave_type_id'=>$leaveTypeId));
            return Class_db::getInstance()->db_select_col('jpl_leave_type', array('leave_type_id'=>$leaveTypeId), 'leave_type_desc', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $leaveTypeId
     * @return mixed
     * @throws Exception
     */
    public function delete_leave_type ($leaveTypeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering delete_leave_type()');

            if (empty($leaveTypeId)) {
                throw new Exception('(ErrCode:0506) [' . __LINE__ . '] - Parameter leaveTypeId empty');
            }
            if (Class_db::getInstance()->db_count('jpl_leave_type', array('leave_type_id'=>$leaveTypeId)) == 0) {
                throw new Exception('(ErrCode:0510) [' . __LINE__ . '] - Jenis Cuti data not exist');
            }
            if (Class_db::getInstance()->db_count('jpl_leave', array('leave_type_id'=>$leaveTypeId)) > 0) {
                throw new Exception('(ErrCode:0511) [' . __LINE__ . '] - '.$constant::ERR_LEAVE_TYPE_DELETE, 31);
            }
            if (Class_db::getInstance()->db_count('jpl_leave_user', array('leave_type_id'=>$leaveTypeId)) > 0) {
                throw new Exception('(ErrCode:0526) [' . __LINE__ . '] - '.$constant::ERR_LEAVE_TYPE_DELETE, 31);
            }

            $leaveTypeDesc = Class_db::getInstance()->db_select_col('jpl_leave_type', array('leave_type_id'=>$leaveTypeId), 'leave_type_desc', null, 1);
            Class_db::getInstance()->db_delete('jpl_leave_type', array('leave_type_id'=>$leaveTypeId));

            return $leaveTypeDesc;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $jabatanId
     * @return array
     * @throws Exception
     */
    public function get_jabatan ($jabatanId=null) {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_jabatan()');

            $result = array();
            if (is_null($jabatanId)) {
                $arr_dataLocal = Class_db::getInstance()->db_select('jpl_jabatan');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['jabatanId'] = $dataLocal['jabatan_id'];
                    $row_result['jabatanDesc'] = $dataLocal['jabatan_desc'];
                    $row_result['jabatanStatus'] = $dataLocal['jabatan_status'];
                    array_push($result, $row_result);
                }
            } else {
                $dataLocal = Class_db::getInstance()->db_select_single('jpl_jabatan', array('jabatan_id'=>$jabatanId), null, 1);
                $result['jabatanId'] = $dataLocal['jabatan_id'];
                $result['jabatanDesc'] = $dataLocal['jabatan_desc'];
                $result['jabatanStatus'] = $dataLocal['jabatan_status'];
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function add_jabatan ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering add_jabatan()');

            if (empty($params)) {
                throw new Exception('(ErrCode:0502) [' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('jabatanDesc', $params) || empty($params['jabatanDesc'])) {
                throw new Exception('(ErrCode:0520) [' . __LINE__ . '] - Parameter jabatanDesc empty');
            }
            if (!array_key_exists('jabatanStatus', $params) || empty($params['jabatanStatus'])) {
                throw new Exception('(ErrCode:0521) [' . __LINE__ . '] - Parameter jabatanStatus empty');
            }

            $jabatanDesc = $params['jabatanDesc'];
            $jabatanStatus = $params['jabatanStatus'];

            if (Class_db::getInstance()->db_count('jpl_jabatan', array('jabatan_desc'=>$jabatanDesc)) > 0) {
                throw new Exception('(ErrCode:0522) [' . __LINE__ . '] - '.$constant::ERR_JABATAN_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('jpl_jabatan', array('jabatan_desc'=>$jabatanDesc, 'jabatan_status'=>$jabatanStatus));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $jabatanId
     * @param $put_vars
     * @throws Exception
     */
    public function update_jabatan ($jabatanId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering update_jabatan()');

            if (empty($jabatanId)) {
                throw new Exception('(ErrCode:0523) [' . __LINE__ . '] - Parameter jabatanId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('(ErrCode:0507) [' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['jabatanDesc']) || empty($put_vars['jabatanDesc'])) {
                throw new Exception('(ErrCode:0520) [' . __LINE__ . '] - Parameter jabatanDesc empty');
            }
            if (!isset($put_vars['jabatanStatus']) || empty($put_vars['jabatanStatus'])) {
                throw new Exception('(ErrCode:0521) [' . __LINE__ . '] - Parameter jabatanStatus empty');
            }

            $jabatanDesc = $put_vars['jabatanDesc'];
            $jabatanStatus = $put_vars['jabatanStatus'];

            if (Class_db::getInstance()->db_count('jpl_jabatan', array('jabatan_desc'=>$jabatanDesc, 'jabatan_id'=>'<>'.$jabatanId)) > 0) {
                throw new Exception('(ErrCode:0522) [' . __LINE__ . '] - '.$constant::ERR_JABATAN_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('jpl_jabatan', array('jabatan_desc'=>$jabatanDesc, 'jabatan_status'=>$jabatanStatus), array('jabatan_id'=>$jabatanId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $jabatanId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_jabatan ($jabatanId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering deactivate_jabatan()');

            if (empty($jabatanId)) {
                throw new Exception('(ErrCode:0523) [' . __LINE__ . '] - Parameter jabatanId empty');
            }
            if (Class_db::getInstance()->db_count('jpl_jabatan', array('jabatan_id'=>$jabatanId, 'jabatan_status'=>'2')) > 0) {
                throw new Exception('(ErrCode:0524) [' . __LINE__ . '] - '.$constant::ERR_JABATAN_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('jpl_jabatan', array('jabatan_status'=>'2'), array('jabatan_id'=>$jabatanId));
            return Class_db::getInstance()->db_select_col('jpl_jabatan', array('jabatan_id'=>$jabatanId), 'jabatan_desc', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $jabatanId
     * @return mixed
     * @throws Exception
     */
    public function activate_jabatan ($jabatanId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering activate_jabatan()');

            if (empty($jabatanId)) {
                throw new Exception('(ErrCode:0523) [' . __LINE__ . '] - Parameter jabatanId empty');
            }
            if (Class_db::getInstance()->db_count('jpl_jabatan', array('jabatan_id'=>$jabatanId, 'jabatan_status'=>'1')) > 0) {
                throw new Exception('(ErrCode:0525) [' . __LINE__ . '] - '.$constant::ERR_JABATAN_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('jpl_jabatan', array('jabatan_status'=>'1'), array('jabatan_id'=>$jabatanId));
            return Class_db::getInstance()->db_select_col('jpl_jabatan', array('jabatan_id'=>$jabatanId), 'jabatan_desc', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $jabatanId
     * @return mixed
     * @throws Exception
     */
    public function delete_jabatan ($jabatanId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering delete_jabatan()');

            if (empty($jabatanId)) {
                throw new Exception('(ErrCode:0506) [' . __LINE__ . '] - Parameter jabatanId empty');
            }
            if (Class_db::getInstance()->db_count('jpl_jabatan', array('jabatan_id'=>$jabatanId)) == 0) {
                throw new Exception('(ErrCode:0510) [' . __LINE__ . '] - jabatan data not exist');
            }
            if (Class_db::getInstance()->db_count('sys_user_profile', array('jabatan_id'=>$jabatanId)) > 0) {
                throw new Exception('(ErrCode:0511) [' . __LINE__ . '] - '.$constant::ERR_JABATAN_DELETE, 31);
            }

            $jabatanDesc = Class_db::getInstance()->db_select_col('jpl_jabatan', array('jabatan_id'=>$jabatanId), 'jabatan_desc', null, 1);
            Class_db::getInstance()->db_delete('jpl_jabatan', array('jabatan_id'=>$jabatanId));

            return $jabatanDesc;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $designationId
     * @return array
     * @throws Exception
     */
    public function get_designation ($designationId=null) {
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering get_designation()');

            $result = array();
            if (is_null($designationId)) {
                $arr_dataLocal = Class_db::getInstance()->db_select('ref_designation');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['designationId'] = $dataLocal['designation_id'];
                    $row_result['designationDesc'] = $dataLocal['designation_desc'];
                    $row_result['designationStatus'] = $dataLocal['designation_status'];
                    array_push($result, $row_result);
                }
            } else {
                $dataLocal = Class_db::getInstance()->db_select_single('ref_designation', array('designation_id'=>$designationId), null, 1);
                $result['designationId'] = $dataLocal['designation_id'];
                $result['designationDesc'] = $dataLocal['designation_desc'];
                $result['designationStatus'] = $dataLocal['designation_status'];
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function add_designation ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering add_designation()');

            if (empty($params)) {
                throw new Exception('(ErrCode:0502) [' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('designationDesc', $params) || empty($params['designationDesc'])) {
                throw new Exception('(ErrCode:0512) [' . __LINE__ . '] - Parameter designationDesc empty');
            }
            if (!array_key_exists('designationStatus', $params) || empty($params['designationStatus'])) {
                throw new Exception('(ErrCode:0513) [' . __LINE__ . '] - Parameter designationStatus empty');
            }

            $designationDesc = $params['designationDesc'];
            $designationStatus = $params['designationStatus'];

            if (Class_db::getInstance()->db_count('ref_designation', array('designation_desc'=>$designationDesc)) > 0) {
                throw new Exception('(ErrCode:0514) [' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ref_designation', array('designation_desc'=>$designationDesc, 'designation_status'=>$designationStatus));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $designationId
     * @param $put_vars
     * @throws Exception
     */
    public function update_designation ($designationId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering update_designation()');

            if (empty($designationId)) {
                throw new Exception('(ErrCode:0515) [' . __LINE__ . '] - Parameter designationId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('(ErrCode:0507) [' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['designationDesc']) || empty($put_vars['designationDesc'])) {
                throw new Exception('(ErrCode:0512) [' . __LINE__ . '] - Parameter designationDesc empty');
            }
            if (!isset($put_vars['designationStatus']) || empty($put_vars['designationStatus'])) {
                throw new Exception('(ErrCode:0513) [' . __LINE__ . '] - Parameter designationStatus empty');
            }

            $designationDesc = $put_vars['designationDesc'];
            $designationStatus = $put_vars['designationStatus'];

            if (Class_db::getInstance()->db_count('ref_designation', array('designation_desc'=>$designationDesc, 'designation_id'=>'<>'.$designationId)) > 0) {
                throw new Exception('(ErrCode:0514) [' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ref_designation', array('designation_desc'=>$designationDesc, 'designation_status'=>$designationStatus), array('designation_id'=>$designationId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $designationId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_designation ($designationId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering deactivate_designation()');

            if (empty($designationId)) {
                throw new Exception('(ErrCode:0515) [' . __LINE__ . '] - Parameter designationId empty');
            }
            if (Class_db::getInstance()->db_count('ref_designation', array('designation_id'=>$designationId, 'designation_status'=>'2')) > 0) {
                throw new Exception('(ErrCode:0516) [' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ref_designation', array('designation_status'=>'2'), array('designation_id'=>$designationId));
            return Class_db::getInstance()->db_select_col('ref_designation', array('designation_id'=>$designationId), 'designation_desc', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $designationId
     * @return mixed
     * @throws Exception
     */
    public function activate_designation ($designationId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering activate_designation()');

            if (empty($designationId)) {
                throw new Exception('(ErrCode:0515) [' . __LINE__ . '] - Parameter designationId empty');
            }
            if (Class_db::getInstance()->db_count('ref_designation', array('designation_id'=>$designationId, 'designation_status'=>'1')) > 0) {
                throw new Exception('(ErrCode:0517) [' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ref_designation', array('designation_status'=>'1'), array('designation_id'=>$designationId));
            return Class_db::getInstance()->db_select_col('ref_designation', array('designation_id'=>$designationId), 'designation_desc', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $designationId
     * @return mixed
     * @throws Exception
     */
    public function delete_designation ($designationId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__FUNCTION__, __LINE__, 'Entering delete_designation()');

            if (empty($designationId)) {
                throw new Exception('(ErrCode:0515) [' . __LINE__ . '] - Parameter designationId empty');
            }
            if (Class_db::getInstance()->db_count('ref_designation', array('designation_id'=>$designationId)) == 0) {
                throw new Exception('(ErrCode:0518) [' . __LINE__ . '] - Designation data not exist');
            }
            if (Class_db::getInstance()->db_count('sys_user_profile', array('designation_id'=>$designationId)) > 0) {
                throw new Exception('(ErrCode:0519) [' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_DELETE, 31);
            }

            $designationDesc = Class_db::getInstance()->db_select_col('ref_designation', array('designation_id'=>$designationId), 'designation_desc', null, 1);
            Class_db::getInstance()->db_delete('ref_designation', array('designation_id'=>$designationId));

            return $designationDesc;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0501', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}