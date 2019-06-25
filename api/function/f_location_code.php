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
    public function get_locationCode_list ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_location_code', array('contract_id'=>$contractId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['locationCodeId'] = $dataLocal['location_code_id'];
                $row_result['locationCodeName'] = $dataLocal['location_code_name'];
                $row_result['contractId'] = $dataLocal['contract_id'];
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
            $result['contractId'] = $dataLocal['contract_id'];
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

            if (Class_db::getInstance()->db_count('cli_location_code', array('location_code_name'=>$locationCodeName, 'contract_id'=>$contractId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_LOCATION_CODE_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('cli_location_code', array('location_code_name'=>$locationCodeName, 'contract_id'=>$contractId, 'location_code_status'=>$locationCodeStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}