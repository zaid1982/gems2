<?php

class Class_item {

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
     * @param string $itemTypeId
     * @return mixed
     * @throws Exception
     */
    public function getItemList ($itemTypeId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return $this->fn_general->convertDbIndexs(Class_db::getInstance()->db_select('ref_item', array('item_type_id'=>$itemTypeId)));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getItemListWithImage () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return $this->fn_general->convertDbIndexs(Class_db::getInstance()->db_select('vw_item_with_image'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemId
     * @return mixed
     * @throws Exception
     */
    public function getItem ($itemId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemId));
            return $this->fn_general->convertDbIndex(Class_db::getInstance()->db_select_single('ref_item', array('item_id'=>$itemId), null, 1));
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
    public function addItem ($params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($params));
            $this->fn_general->checkEmptyParamsArray($params, array('itemTypeId', 'itemDescription', 'itemThreshold'));
            if (Class_db::getInstance()->db_count('ref_item', array('item_description'=>$params['itemDescription'])) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item description already exist. Please used another name.', 31);
            }
            return Class_db::getInstance()->db_insert('ref_item', $this->fn_general->convertToMysqlArrAll($params));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemId
     * @param array $params
     * @throws Exception
     */
    public function updateItem ($itemId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemId, $params));
            if (Class_db::getInstance()->db_count('ref_item', array('item_description'=>$params['itemDescription'], 'item_id'=>'<>'.$itemId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item description already exist. Please used another name.', 31);
            }
            Class_db::getInstance()->db_update('ref_item', $this->fn_general->convertToMysqlArrAll($params), array('item_id'=>$itemId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemId
     * @throws Exception
     */
    public function deactivateItem ($itemId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemId));
            Class_db::getInstance()->db_update('ref_item', array('item_status'=>'2'), array('item_id'=>$itemId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemId
     * @throws Exception
     */
    public function activateItem ($itemId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemId));
            Class_db::getInstance()->db_update('ref_item', array('item_status'=>'1'), array('item_id'=>$itemId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $itemId
     * @throws Exception
     */
    public function deleteItem ($itemId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($itemId));
            if (Class_db::getInstance()->db_count('ast_part', array('item_id'=>$itemId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item description cannot be deleted because it\'s already being used in inventory list', 31);
            }
            Class_db::getInstance()->db_delete('ref_item_image', array('item_id'=>$itemId));
            Class_db::getInstance()->db_delete('ref_item', array('item_id'=>$itemId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
