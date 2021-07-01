<?php

class Class_item_type {

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
     * @param string $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function getItemTypeList ($assetGroupId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return Class_db::getInstance()->db_select2('ref_item_type', array('asset_group_id'=>$assetGroupId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemTypeId
     * @return mixed
     * @throws Exception
     */
    public function getItemType ($itemTypeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemTypeId));
            return Class_db::getInstance()->db_select_single2('ref_item_type', array('item_type_id'=>$itemTypeId), null, 1);
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
    public function addItemType ($params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($params));
            $this->fn_general->checkEmptyParamsArray($params, array('assetGroupId', 'itemTypeDesc'));
            if (Class_db::getInstance()->db_count('ref_item_type', array('item_type_desc'=>$params['itemTypeDesc'])) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item type name already exist. Please used another name.', 31);
            }
            return Class_db::getInstance()->db_insert('ref_item_type', $this->fn_general->convertToMysqlArrAll($params));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemTypeId
     * @param array $params
     * @throws Exception
     */
    public function updateItemType ($itemTypeId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemTypeId, $params));
            if (Class_db::getInstance()->db_count('ref_item_type', array('item_type_desc'=>$params['itemTypeDesc'], 'item_type_id'=>'<>'.$itemTypeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item type name already exist. Please used another name.', 31);
            }
            Class_db::getInstance()->db_update('ref_item_type', $this->fn_general->convertToMysqlArrAll($params), array('item_type_id'=>$itemTypeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemTypeId
     * @throws Exception
     */
    public function deleteItemType ($itemTypeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemTypeId));
            if (Class_db::getInstance()->db_count('ref_item', array('item_type_id'=>$itemTypeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item type cannot be deleted because it\'s already being used in item list', 31);
            }
            Class_db::getInstance()->db_delete('ref_item_type', array('item_type_id'=>$itemTypeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
