<?php

class Class_contractUser {

    private $constant;
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
    public function get_contractUser_list ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }

            $result = array();
            $locationCodes = $this->fn_general->getLocationCode();
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_contract_user', array('contract_id'=>$contractId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['contractUserId'] = $dataLocal['contract_user_id'];
                $row_result['userId'] = $dataLocal['user_id'];
                $row_result['locationCodeId'] = $dataLocal['location_code_id'];
                $row_result['locationCodeName'] = $locationCodes[intval($dataLocal['location_code_id'])];
                $row_result['contractId'] = $dataLocal['contract_id'];
                $row_result['assetGroupId'] = $dataLocal['asset_group_id'];
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
     * @param $contractId
     * @param $locationCodeId
     * @param $userId
     * @param $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function add_contractUser ($contractId, $locationCodeId, $userId, $assetGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }
            if (empty($locationCodeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter locationCodeId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($assetGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (Class_db::getInstance()->db_count('cli_contract_user', array('contract_id'=>$contractId, 'location_code_id'=>$locationCodeId, 'user_id'=>$userId, 'asset_group_id'=>$assetGroupId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CONTRACT_USER_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('cli_contract_user', array('contract_id'=>$contractId, 'location_code_id'=>$locationCodeId, 'user_id'=>$userId, 'asset_group_id'=>$assetGroupId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $contractUserId
     * @throws Exception
     */
    public function delete_contractUser ($contractUserId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractUserId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractUserId empty');
            }
            if (Class_db::getInstance()->db_count('cli_contract_user', array('contract_user_id'=>$contractUserId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Contract User data not exist');
            }

            Class_db::getInstance()->db_delete('cli_contract_user', array('contract_user_id'=>$contractUserId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}