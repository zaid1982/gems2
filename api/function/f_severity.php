<?php

class Class_severity {

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
    public function get_severity_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_severity');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['severityId'] = $dataLocal['severity_id'];
                $row_result['severityName'] = $dataLocal['severity_name'];
                $row_result['severityStatus'] = $dataLocal['severity_status'];
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
     * @param $severityId
     * @return array
     * @throws Exception
     */
    public function get_severity ($severityId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Array severityId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ref_severity', array('severity_id'=>$severityId), null, 1);
            $result['severityId'] = $dataLocal['severity_id'];
            $result['severityName'] = $dataLocal['severity_name'];
            $result['severityStatus'] = $dataLocal['severity_status'];

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
    public function add_severity ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('severityName', $params) || empty($params['severityName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityName empty');
            }
            if (!array_key_exists('severityStatus', $params) || empty($params['severityStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityStatus empty');
            }

            $severityName = $params['severityName'];
            $severityStatus = $params['severityStatus'];

            if (Class_db::getInstance()->db_count('ref_severity', array('severity_name'=>$severityName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - Severity name already exist. Please insert another name.', 31);
            }

            return Class_db::getInstance()->db_insert('ref_severity', array('severity_name'=>$severityName, 'severity_status'=>$severityStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $severityId
     * @param $put_vars
     * @throws Exception
     */
    public function update_severity ($severityId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['severityName']) || empty($put_vars['severityName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityName empty');
            }
            if (!isset($put_vars['severityStatus']) || empty($put_vars['severityStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityStatus empty');
            }

            $severityName = $put_vars['severityName'];
            $severityStatus = $put_vars['severityStatus'];

            if (Class_db::getInstance()->db_count('ref_severity', array('severity_name'=>$severityName, 'severity_id'=>'<>'.$severityId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - Severity name already exist. Please insert another name.', 31);
            }

            Class_db::getInstance()->db_update('ref_severity', array('severity_name'=>$severityName, 'severity_status'=>$severityStatus), array('severity_id'=>$severityId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $severityId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_severity ($severityId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityId empty');
            }
            if (Class_db::getInstance()->db_count('ref_severity', array('severity_id'=>$severityId, 'severity_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - Severity already deactivated', 31);
            }

            Class_db::getInstance()->db_update('ref_severity', array('severity_status'=>'2'), array('severity_id'=>$severityId));
            return Class_db::getInstance()->db_select_col('ref_severity', array('severity_id'=>$severityId), 'severity_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $severityId
     * @return mixed
     * @throws Exception
     */
    public function activate_severity ($severityId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityId empty');
            }
            if (Class_db::getInstance()->db_count('ref_severity', array('severity_id'=>$severityId, 'severity_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - Severity already activated', 31);
            }

            Class_db::getInstance()->db_update('ref_severity', array('severity_status'=>'1'), array('severity_id'=>$severityId));
            return Class_db::getInstance()->db_select_col('ref_severity', array('severity_id'=>$severityId), 'severity_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $severityId
     * @return mixed
     * @throws Exception
     */
    public function delete_severity ($severityId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityId empty');
            }
            if (Class_db::getInstance()->db_count('ref_severity', array('severity_id'=>$severityId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Severity data not exist');
            }
            if (Class_db::getInstance()->db_count('cli_client_severity', array('severity_id'=>$severityId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - Severity still used in client. Please make sure severity removed from client first.', 31);
            }

            $severityName = Class_db::getInstance()->db_select_col('ref_severity', array('severity_id'=>$severityId), 'severity_name', null, 1);
            Class_db::getInstance()->db_delete('ref_severity', array('severity_id'=>$severityId));

            return $severityName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
