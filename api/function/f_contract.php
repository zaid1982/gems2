<?php

class Class_contract {

    private $fn_general;

    function __construct() {
        $this->fn_general = new Class_general();
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
    public function get_contract_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_contract');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['contractId'] = $dataLocal['contract_id'];
                $row_result['contractName'] = $dataLocal['contract_name'];
                $row_result['contractDesc'] = $this->fn_general->clear_null($dataLocal['contract_desc']);
                $row_result['contractDateStart'] = str_replace('-', '/', $dataLocal['contract_date_start']);
                $row_result['contractDateEnd'] = str_replace('-', '/', $dataLocal['contract_date_end']);
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['clientId'] = $dataLocal['client_id'];
                $row_result['contractTimeCreated'] = str_replace('-', '/', $dataLocal['contract_time_created']);
                $row_result['contractStatus'] = $dataLocal['contract_status'];
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
     * @return array
     * @throws Exception
     */
    public function get_contract ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Array contractId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id'=>$contractId), null, 1);
            $result['contractId'] = $dataLocal['contract_id'];
            $result['contractName'] = $dataLocal['contract_name'];
            $result['contractDesc'] = $this->fn_general->clear_null($dataLocal['contract_desc']);
            $result['contractDateStart'] = str_replace('-', '/', $dataLocal['contract_date_start']);
            $result['contractDateEnd'] = str_replace('-', '/', $dataLocal['contract_date_end']);
            $result['siteId'] = $dataLocal['site_id'];
            $result['contractTimeCreated'] = str_replace('-', '/', $dataLocal['contract_time_created']);
            $result['contractStatus'] = $dataLocal['contract_status'];

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
    public function add_contract ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('contractName', $params) || empty($params['contractName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractName empty');
            }
            if (!array_key_exists('contractDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractDesc not exist');
            }
            if (!array_key_exists('contractDateStart', $params) || empty($params['contractDateStart'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractDateStart empty');
            }
            if (!array_key_exists('contractDateEnd', $params) || empty($params['contractDateEnd'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractDateEnd empty');
            }
            if (!array_key_exists('siteId', $params) || empty($params['siteId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (!array_key_exists('contractStatus', $params) || empty($params['contractStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractStatus empty');
            }

            $contractName = $params['contractName'];
            $contractDesc = $params['contractDesc'];
            $contractDateStart = $params['contractDateStart'];
            $contractDateEnd = $params['contractDateEnd'];
            $siteId = $params['siteId'];
            $contractStatus = $params['contractStatus'];

            if (Class_db::getInstance()->db_count('cli_contract', array('contract_name'=>$contractName, 'site_id'=>$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CONTRACT_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('cli_contract', array('contract_name'=>$contractName, 'contract_desc'=>$contractDesc, 'contract_date_start'=>$contractDateStart, 'contract_date_end'=>$contractDateEnd,
                'site_id'=>$siteId, 'contract_status'=>$contractStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $contractId
     * @param $put_vars
     * @throws Exception
     */
    public function update_contract ($contractId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['contractName']) || empty($put_vars['contractName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractName empty');
            }
            if (!isset($put_vars['contractDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractDesc not exist');
            }
            if (!isset($put_vars['contractDateStart']) || empty($put_vars['contractDateStart'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractDateStart empty');
            }
            if (!isset($put_vars['contractDateEnd']) || empty($put_vars['contractDateEnd'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractDateEnd empty');
            }
            if (!isset($put_vars['siteId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId not exist');
            }
            if (!isset($put_vars['contractStatus']) || empty($put_vars['contractStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractStatus empty');
            }

            $contractName = $put_vars['contractName'];
            $contractDesc = $put_vars['contractDesc'];
            $contractDateStart = $put_vars['contractDateStart'];
            $contractDateEnd = $put_vars['contractDateEnd'];
            $siteId = $put_vars['siteId'];
            $contractStatus = $put_vars['contractStatus'];

            if (Class_db::getInstance()->db_count('cli_contract', array('contract_name'=>$contractName, 'site_id'=>$siteId, 'contract_id'=>'<>'.$contractId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('cli_contract', array('contract_name'=>$contractName, 'contract_desc'=>$contractDesc, 'contract_date_start'=>$contractDateStart, 'contract_date_end'=>$contractDateEnd, 'contract_status'=>$contractStatus), array('contract_id'=>$contractId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $contractId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_contract ($contractId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }
            if (Class_db::getInstance()->db_count('cli_contract', array('contract_id'=>$contractId, 'contract_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('cli_contract', array('contract_status'=>'2'), array('contract_id'=>$contractId));
            return Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'contract_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $contractId
     * @return mixed
     * @throws Exception
     */
    public function activate_contract ($contractId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }
            if (Class_db::getInstance()->db_count('cli_contract', array('contract_id'=>$contractId, 'contract_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('cli_contract', array('contract_status'=>'1'), array('contract_id'=>$contractId));
            return Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'contract_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $contractId
     * @return mixed
     * @throws Exception
     */
    public function delete_contract ($contractId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }
            if (Class_db::getInstance()->db_count('cli_contract', array('contract_id'=>$contractId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Contract data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('contract_id'=>$contractId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CONTRACT_DELETE_ASSET, 31);
            }

            $contractName = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'contract_name', null, 1);
            Class_db::getInstance()->db_delete('cli_contract', array('contract_id'=>$contractId));

            return $contractName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
