<?php

class Class_do {

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
     * @param $userId
     * @param $storeId
     * @return void
     * @throws Exception
     */
    public function checkCheckIn ($userId, $storeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $storeId));

            $siteId = Class_db::getInstance()->db_select_col('cli_store', array('store_id'=>$storeId), 'site_id', '', 1);
            if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'site_id'=>$siteId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Invalid user site');
            }
            if (Class_db::getInstance()->db_count('sys_user_role', array('user_id'=>$userId, 'role_id'=>'16')) == 0) {
                throw new Exception('[' . __LINE__ . '] - User does not have storekeeper role');
            }
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function addDoMobile ($userId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $params));
            $this->fn_general->checkEmptyParamsArray($params, array('doNo', 'doDate', 'supplierName'));

            $insertParams = array_merge(
                $this->fn_general->convertToMysqlArr($params, array('doNo', 'doDate', 'supplierName')),
                array('doType'=>'Normal', 'doCreatedBy'=>$userId, 'doStatus'=>'45')
            );
            return Class_db::getInstance()->db_insert('do', $this->fn_general->convertToMysqlArrAll($insertParams));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}