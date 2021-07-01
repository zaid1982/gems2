<?php

class Class_store {

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
     * @return mixed
     * @throws Exception
     */
    public function getStoreList () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return Class_db::getInstance()->db_select2('vw_store');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws Exception
     */
    public function getStore ($storeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId));
            return Class_db::getInstance()->db_select_single2('cli_store', array('store_id'=>$storeId), null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @throws Exception
     */
    public function addStore ($params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($params));
            $this->fn_general->checkEmptyParamsArray($params, array('siteId', 'storeName'));
            if (Class_db::getInstance()->db_count('cli_store', array('site_id'=>$params['siteId'], 'store_name'=>$params['storeName'])) > 0) {
                throw new Exception('[' . __LINE__ . '] - This inventory store name already exist. Please used another name.', 31);
            }
            Class_db::getInstance()->db_update('cli_site', array('site_is_material'=>'1'), array('site_id'=>$params['siteId']));
            return Class_db::getInstance()->db_insert('cli_store', $this->fn_general->convertToMysqlArrAll($params));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $storeId
     * @param array $params
     * @throws Exception
     */
    public function updateStore ($storeId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId, $params));
            if (Class_db::getInstance()->db_count('cli_store', array('site_id'=>$params['siteId'], 'store_name'=>$params['storeName'], 'store_id'=>'<>'.$storeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This inventory store name already exist. Please used another name.', 31);
            }
            Class_db::getInstance()->db_update('cli_store', $this->fn_general->convertToMysqlArrAll($params), array('store_id'=>$storeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $storeId
     * @throws Exception
     */
    public function deleteStore ($storeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId));
            if (Class_db::getInstance()->db_count('ast_part', array('store_id'=>$storeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This inventory store cannot be deleted because it\'s already being used in inventory', 31);
            }
            $siteId = Class_db::getInstance()->db_select_col('cli_store', array('store_id'=>$storeId), 'site_id', null, 1);
            Class_db::getInstance()->db_delete('cli_store', array('store_id'=>$storeId));
            if (Class_db::getInstance()->db_count('cli_store', array('site_id'=>$siteId)) == 0) {
                Class_db::getInstance()->db_update('cli_site', array('site_is_material'=>'0'), array('site_id'=>$siteId));
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
