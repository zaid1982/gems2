<?php
/**
 * Created by PhpStorm.
 * User: Zaid
 * Date: 2/26/2019
 * Time: 11:08 PM
 */
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_reference {

    private $fn_general;

    function __construct() {
        $this->fn_general = new Class_general();
    }

    private function get_exception($codes, $function, $line, $msg) {
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
    public function __get($property) {
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
    public function __set($property, $value) {
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
    public function __isset($property) {
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
    public function __unset($property) {
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $stateId
     * @return array
     * @throws Exception
     */
    public function get_state ($stateId=null) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $cityId
     * @return array
     * @throws Exception
     */
    public function get_city ($cityId=null) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
    public function get_role () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param null $designationId
     * @return array
     * @throws Exception
     */
    public function get_designation ($designationId=null) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('designationDesc', $params) || empty($params['designationDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationDesc empty');
            }
            if (!array_key_exists('designationStatus', $params) || empty($params['designationStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationStatus empty');
            }

            $designationDesc = $params['designationDesc'];
            $designationStatus = $params['designationStatus'];

            if (Class_db::getInstance()->db_count('ref_designation', array('designation_desc'=>$designationDesc)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ref_designation', array('designation_desc'=>$designationDesc, 'designation_status'=>$designationStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($designationId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['designationDesc']) || empty($put_vars['designationDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationDesc empty');
            }
            if (!isset($put_vars['designationStatus']) || empty($put_vars['designationStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationStatus empty');
            }

            $designationDesc = $put_vars['designationDesc'];
            $designationStatus = $put_vars['designationStatus'];

            if (Class_db::getInstance()->db_count('ref_designation', array('designation_desc'=>$designationDesc, 'designation_id'=>'<>'.$designationId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ref_designation', array('designation_desc'=>$designationDesc, 'designation_status'=>$designationStatus), array('designation_id'=>$designationId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($designationId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationId empty');
            }
            if (Class_db::getInstance()->db_count('ref_designation', array('designation_id'=>$designationId, 'designation_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ref_designation', array('designation_status'=>'2'), array('designation_id'=>$designationId));
            return Class_db::getInstance()->db_select_col('ref_designation', array('designation_id'=>$designationId), 'designation_desc', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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
            $this->fn_general->log_debug(__CLASS__,__FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($designationId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationId empty');
            }
            if (Class_db::getInstance()->db_count('ref_designation', array('designation_id'=>$designationId, 'designation_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ref_designation', array('designation_status'=>'1'), array('designation_id'=>$designationId));
            return Class_db::getInstance()->db_select_col('ref_designation', array('designation_id'=>$designationId), 'designation_desc', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($designationId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter designationId empty');
            }
            if (Class_db::getInstance()->db_count('ref_designation', array('designation_id'=>$designationId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Designation data not exist');
            }
            if (Class_db::getInstance()->db_count('sys_user_profile', array('designation_id'=>$designationId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_DESIGNATION_DELETE, 31);
            }

            $designationDesc = Class_db::getInstance()->db_select_col('ref_designation', array('designation_id'=>$designationId), 'designation_desc', null, 1);
            Class_db::getInstance()->db_delete('ref_designation', array('designation_id'=>$designationId));

            return $designationDesc;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    public function get_group_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('sys_group');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['groupId'] = $dataLocal['group_id'];
                $row_result['groupName'] = $dataLocal['group_name'];
                $row_result['groupType'] = $dataLocal['group_type'];
                $row_result['groupRegNo'] = $this->fn_general->clear_null($dataLocal['group_reg_no']);
                $row_result['groupStatus'] = $dataLocal['group_status'];
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}