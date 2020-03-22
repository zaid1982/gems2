<?php

class Class_failureCode {

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
     * @return array
     * @throws Exception
     */
    public function get_failureCode_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_failure_code');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['failureCodeId'] = $dataLocal['failure_code_id'];
                $row_result['failureCodeName'] = $dataLocal['failure_code_name'];
                $row_result['failureCodeStatus'] = $dataLocal['failure_code_status'];
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
     * @param $failureCodeId
     * @return array
     * @throws Exception
     */
    public function get_failureCode ($failureCodeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($failureCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Array failureCodeId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ref_failure_code', array('failure_code_id'=>$failureCodeId), null, 1);
            $result['failureCodeId'] = $dataLocal['failure_code_id'];
            $result['failureCodeName'] = $dataLocal['failure_code_name'];
            $result['failureCodeStatus'] = $dataLocal['failure_code_status'];

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
    public function add_failureCode ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('failureCodeName', $params) || empty($params['failureCodeName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeName empty');
            }
            if (!array_key_exists('failureCodeStatus', $params) || empty($params['failureCodeStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeStatus empty');
            }

            $failureCodeName = $params['failureCodeName'];
            $failureCodeStatus = $params['failureCodeStatus'];

            if (Class_db::getInstance()->db_count('ref_failure_code', array('failure_code_name'=>$failureCodeName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - Failure Code name already exist. Please insert another name.', 31);
            }

            return Class_db::getInstance()->db_insert('ref_failure_code', array('failure_code_name'=>$failureCodeName, 'failure_code_status'=>$failureCodeStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $failureCodeId
     * @param $put_vars
     * @throws Exception
     */
    public function update_failureCode ($failureCodeId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($failureCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['failureCodeName']) || empty($put_vars['failureCodeName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeName empty');
            }
            if (!isset($put_vars['failureCodeStatus']) || empty($put_vars['failureCodeStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeStatus empty');
            }

            $failureCodeName = $put_vars['failureCodeName'];
            $failureCodeStatus = $put_vars['failureCodeStatus'];

            if (Class_db::getInstance()->db_count('ref_failure_code', array('failure_code_name'=>$failureCodeName, 'failure_code_id'=>'<>'.$failureCodeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - Failure Code name already exist. Please insert another name.', 31);
            }

            Class_db::getInstance()->db_update('ref_failure_code', array('failure_code_name'=>$failureCodeName, 'failure_code_status'=>$failureCodeStatus), array('failure_code_id'=>$failureCodeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $failureCodeId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_failureCode ($failureCodeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($failureCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeId empty');
            }
            if (Class_db::getInstance()->db_count('ref_failure_code', array('failure_code_id'=>$failureCodeId, 'failure_code_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - Failure Code already deactivated', 31);
            }

            Class_db::getInstance()->db_update('ref_failure_code', array('failure_code_status'=>'2'), array('failure_code_id'=>$failureCodeId));
            return Class_db::getInstance()->db_select_col('ref_failure_code', array('failure_code_id'=>$failureCodeId), 'failure_code_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $failureCodeId
     * @return mixed
     * @throws Exception
     */
    public function activate_failureCode ($failureCodeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($failureCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeId empty');
            }
            if (Class_db::getInstance()->db_count('ref_failure_code', array('failure_code_id'=>$failureCodeId, 'failure_code_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - Failure Code already activated', 31);
            }

            Class_db::getInstance()->db_update('ref_failure_code', array('failure_code_status'=>'1'), array('failure_code_id'=>$failureCodeId));
            return Class_db::getInstance()->db_select_col('ref_failure_code', array('failure_code_id'=>$failureCodeId), 'failure_code_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $failureCodeId
     * @return mixed
     * @throws Exception
     */
    public function delete_failureCode ($failureCodeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($failureCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter failureCodeId empty');
            }
            if (Class_db::getInstance()->db_count('ref_failure_code', array('failure_code_id'=>$failureCodeId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Failure Code data not exist');
            }
            if (Class_db::getInstance()->db_count('cli_client_failure_code', array('failure_code_id'=>$failureCodeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - Failure Code still used in client. Please make sure Failure Code removed from client first.', 31);
            }

            $failureCodeName = Class_db::getInstance()->db_select_col('ref_failure_code', array('failure_code_id'=>$failureCodeId), 'failure_code_name', null, 1);
            Class_db::getInstance()->db_delete('ref_failure_code', array('failure_code_id'=>$failureCodeId));

            return $failureCodeName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
