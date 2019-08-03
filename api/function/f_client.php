<?php

class Class_client {

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
     * @return array
     * @throws Exception
     */
    public function get_client_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_client');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['clientId'] = $dataLocal['client_id'];
                $row_result['clientName'] = $dataLocal['client_name'];
                $row_result['clientDesc'] = $this->fn_general->clear_null($dataLocal['client_desc']);
                $row_result['clientTimeCreated'] = str_replace('-', '/', $dataLocal['client_time_created']);
                $row_result['clientStatus'] = $dataLocal['client_status'];
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
     * @param $clientId
     * @return array
     * @throws Exception
     */
    public function get_client ($clientId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Array clientId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('cli_client', array('client_id'=>$clientId), null, 1);
            $result['clientId'] = $dataLocal['client_id'];
            $result['clientName'] = $dataLocal['client_name'];
            $result['clientDesc'] = $this->fn_general->clear_null($dataLocal['client_desc']);
            $result['clientTimeCreated'] = str_replace('-', '/', $dataLocal['client_time_created']);
            $result['clientStatus'] = $dataLocal['client_status'];

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
    public function add_client ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('clientName', $params) || empty($params['clientName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientName empty');
            }
            if (!array_key_exists('clientDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientDesc not exist');
            }
            if (!array_key_exists('clientStatus', $params) || empty($params['clientStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientStatus empty');
            }

            $clientName = $params['clientName'];
            $clientDesc = $params['clientDesc'];
            $clientStatus = $params['clientStatus'];

            if (Class_db::getInstance()->db_count('cli_client', array('client_name'=>$clientName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('cli_client', array('client_name'=>$clientName, 'client_desc'=>$clientDesc, 'client_status'=>$clientStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $clientId
     * @param $put_vars
     * @throws Exception
     */
    public function update_client ($clientId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['clientName']) || empty($put_vars['clientName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientName empty');
            }
            if (!isset($put_vars['clientDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientDesc not exist');
            }
            if (!isset($put_vars['clientStatus']) || empty($put_vars['clientStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientStatus empty');
            }

            $clientName = $put_vars['clientName'];
            $clientDesc = $put_vars['clientDesc'];
            $clientStatus = $put_vars['clientStatus'];

            if (Class_db::getInstance()->db_count('cli_client', array('client_name'=>$clientName, 'client_id'=>'<>'.$clientId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('cli_client', array('client_name'=>$clientName, 'client_desc'=>$clientDesc, 'client_status'=>$clientStatus), array('client_id'=>$clientId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $clientId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_client ($clientId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (Class_db::getInstance()->db_count('cli_client', array('client_id'=>$clientId, 'client_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('cli_client', array('client_status'=>'2'), array('client_id'=>$clientId));
            return Class_db::getInstance()->db_select_col('cli_client', array('client_id'=>$clientId), 'client_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $clientId
     * @return mixed
     * @throws Exception
     */
    public function activate_client ($clientId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (Class_db::getInstance()->db_count('cli_client', array('client_id'=>$clientId, 'client_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('cli_client', array('client_status'=>'1'), array('client_id'=>$clientId));
            return Class_db::getInstance()->db_select_col('cli_client', array('client_id'=>$clientId), 'client_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $clientId
     * @return mixed
     * @throws Exception
     */
    public function delete_client ($clientId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (Class_db::getInstance()->db_count('cli_client', array('client_id'=>$clientId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Client data not exist');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('client_id'=>$clientId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_DELETE_SITE, 31);
            }

            $clientName = Class_db::getInstance()->db_select_col('cli_client', array('client_id'=>$clientId), 'client_name', null, 1);
            Class_db::getInstance()->db_delete('cli_client', array('client_id'=>$clientId));

            return $clientName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
