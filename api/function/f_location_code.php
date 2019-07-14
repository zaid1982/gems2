<?php

class Class_locationCode {

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
     * @param $contractId
     * @return array
     * @throws Exception
     */
    public function get_locationCode_list ($contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($contractId)) {
                $arr_dataLocal = Class_db::getInstance()->db_select('cli_location_code', array());
            } else {
                $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);
                $arr_dataLocal = Class_db::getInstance()->db_select('cli_location_code', array('site_id'=>$siteId));
            }

            $result = array();
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['locationCodeId'] = $dataLocal['location_code_id'];
                $row_result['locationCodeName'] = $dataLocal['location_code_name'];
                $row_result['locationCodeDesc'] = $dataLocal['location_code_desc'];
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['locationCodeStatus'] = $dataLocal['location_code_status'];
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
     * @param $locationCodeId
     * @return array
     * @throws Exception
     */
    public function get_locationCode ($locationCodeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($locationCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('cli_location_code', array('location_code_id'=>$locationCodeId));
            $result['locationCodeId'] = $dataLocal['location_code_id'];
            $result['locationCodeName'] = $dataLocal['location_code_name'];
            $result['locationCodeDesc'] = $dataLocal['location_code_desc'];
            $result['siteId'] = $dataLocal['site_id'];
            $result['locationCodeStatus'] = $dataLocal['location_code_status'];

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
    public function add_locationCode ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('locationCodeName', $params) || empty($params['locationCodeName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeName empty');
            }
            if (!array_key_exists('contractId', $params) || empty($params['contractId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId not exist');
            }
            if (!array_key_exists('locationCodeStatus', $params) || empty($params['locationCodeStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeStatus empty');
            }

            $locationCodeName = $params['locationCodeName'];
            $contractId = $params['contractId'];
            $locationCodeStatus = $params['locationCodeStatus'];

            $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);
            if (Class_db::getInstance()->db_count('cli_location_code', array('location_code_name'=>$locationCodeName, 'site_id'=>$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('cli_location_code', array('location_code_name'=>$locationCodeName, 'site_id'=>$siteId, 'location_code_status'=>$locationCodeStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $locationCodeId
     * @param $put_vars
     * @throws Exception
     */
    public function update_locationCode ($locationCodeId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($locationCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['locationCodeName']) || empty($put_vars['locationCodeName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeName empty');
            }
            if (!isset($put_vars['contractId']) || empty($put_vars['contractId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId not exist');
            }
            if (!isset($put_vars['locationCodeStatus']) || empty($put_vars['locationCodeStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeStatus empty');
            }

            $locationCodeName = $put_vars['locationCodeName'];
            $contractId = $put_vars['contractId'];
            $locationCodeStatus = $put_vars['locationCodeStatus'];

            $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);
            if (Class_db::getInstance()->db_count('cli_location_code', array('location_code_name'=>$locationCodeName, 'site_id'=>$siteId, 'location_code_id'=>'<>'.$locationCodeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('cli_location_code', array('location_code_name'=>$locationCodeName, 'site_id'=>$siteId, 'location_code_status'=>$locationCodeStatus), array('location_code_id'=>$locationCodeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $locationCodeId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_locationCode ($locationCodeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($locationCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeId empty');
            }
            if (Class_db::getInstance()->db_count('cli_location_code', array('location_code_id'=>$locationCodeId, 'location_code_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('cli_location_code', array('location_code_status'=>'2'), array('location_code_id'=>$locationCodeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $locationCodeId
     * @throws Exception
     */
    public function activate_locationCode ($locationCodeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($locationCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeId empty');
            }
            if (Class_db::getInstance()->db_count('cli_location_code', array('location_code_id'=>$locationCodeId, 'location_code_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('cli_location_code', array('location_code_status'=>'1'), array('location_code_id'=>$locationCodeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $locationCodeId
     * @throws Exception
     */
    public function delete_locationCode ($locationCodeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($locationCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeId empty');
            }
            if (Class_db::getInstance()->db_count('cli_location_code', array('location_code_id'=>$locationCodeId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Category data not exist');
            }
            if (Class_db::getInstance()->db_count('cli_contract_user', array('location_code_id'=>$locationCodeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_DELETE_USER, 31);
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('location_code_id'=>$locationCodeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_DELETE_ASSET, 31);
            }

            Class_db::getInstance()->db_delete('cli_location_code', array('location_code_id'=>$locationCodeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}