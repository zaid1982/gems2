<?php

class Class_part {

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
     * @param string $woTaskId
     * @param string $itemTypeId
     * @return mixed
     * @throws Exception
     */
    public function getPartListMobile ($woTaskId='', $itemTypeId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            
            $this->fn_general->checkEmptyParams(array($woTaskId, $itemTypeId));
            $siteId = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$woTaskId), 'site_id', null, 1);
            return Class_db::getInstance()->db_select2('vw_part_mobile', array(), null, null, 0, array('siteId'=>$siteId, 'itemTypeId'=>$itemTypeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $storeId
     * @return mixed
     * @throws Exception
     */
    public function getPartList ($storeId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return Class_db::getInstance()->db_select2('ast_part', array('store_id'=>$storeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $storeId
     * @return mixed
     * @throws Exception
     */
    public function getPartListWithImage ($storeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId));
            return Class_db::getInstance()->db_select2('vw_part_with_image', array('store_id'=>$storeId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $partId
     * @return mixed
     * @throws Exception
     */
    public function getPart ($partId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($partId));
            return Class_db::getInstance()->db_select_single2('ast_part', array('part_id'=>$partId), null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @return mixed
     * @throws Exception
     */
    public function getPartAssetGroupOption ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $assetGroups = array();
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            $storeIds = Class_db::getInstance()->db_select_colm('cli_store', array('site_id'=>$siteId), 'store_id');
            if (!empty($storeIds)) {
                $assetGroups =  Class_db::getInstance()->db_select('vw_part_asset_group', array(), '', '', 0, array('storeIds'=>implode(',', $storeIds)));
            }
            return $assetGroups;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function getPartItemTypeOption ($userId, $assetGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $assetGroupId));

            $assetGroups = array();
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            $storeIds = Class_db::getInstance()->db_select_colm('cli_store', array('site_id'=>$siteId), 'store_id');
            if (!empty($storeIds)) {
                $assetGroups =  Class_db::getInstance()->db_select('vw_part_item_type', array(), '', '', 0, array('storeIds'=>implode(',', $storeIds), 'assetGroupId'=>$assetGroupId));
            }
            return $assetGroups;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $itemTypeId
     * @return mixed
     * @throws Exception
     */
    public function getPartItemOption ($userId, $itemTypeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $itemTypeId));

            $assetGroups = array();
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            $storeIds = Class_db::getInstance()->db_select_colm('cli_store', array('site_id'=>$siteId), 'store_id');
            if (!empty($storeIds)) {
                $assetGroups =  Class_db::getInstance()->db_select('vw_part_item', array(), '', '', 0, array('storeIds'=>implode(',', $storeIds), 'itemTypeId'=>$itemTypeId));
            }
            return $assetGroups;
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
    public function getPartAddAssetGroupOption ($storeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId));

            $dataOption = Class_db::getInstance()->db_select_colm('vw_part_left_asset_group', array(), 'asset_group_list', '', null, array('storeId'=>$storeId));
            if (empty($dataOption)) {
                throw new Exception('[' . __LINE__ . '] - You have no item available left to add to the store inventory.', 31);
            }
            return $dataOption;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $storeId
     * @param $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function getPartAddItemTypeOption ($storeId, $assetGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId, $assetGroupId));

            $dataOption = Class_db::getInstance()->db_select_colm('vw_part_left_item_type', array(), 'item_type_list', '', null, array('storeId'=>$storeId, 'assetGroupId'=>$assetGroupId));
            if (empty($dataOption)) {
                throw new Exception('[' . __LINE__ . '] - You have no item available left to add to the store inventory.', 31);
            }
            return $dataOption;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $storeId
     * @param $itemTypeId
     * @return mixed
     * @throws Exception
     */
    public function getPartAddItemOption ($storeId, $itemTypeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId, $itemTypeId));

            $dataOption = Class_db::getInstance()->db_select_colm('vw_part_left_item', array(), 'item_list', '', null, array('storeId'=>$storeId, 'itemTypeId'=>$itemTypeId));
            if (empty($dataOption)) {
                throw new Exception('[' . __LINE__ . '] - You have no item available left to add to the store inventory.', 31);
            }
            return $dataOption;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function addPart ($params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($params));
            $this->fn_general->checkEmptyParamsArray($params, array('storeId', 'itemId'));
            if (Class_db::getInstance()->db_count('ast_part', array('store_id'=>$params['storeId'], 'item_id'=>$params['itemId'])) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item description already exist in this store inventory.', 31);
            }

            $item = Class_db::getInstance()->db_select_single2('ref_item', array('item_id'=>$params['itemId']), null, 1);
            $assetGroupId = Class_db::getInstance()->db_select_col('ref_item_type', array('item_type_id'=>$item['itemTypeId']), 'asset_group_id', null, 1);
            $siteId = Class_db::getInstance()->db_select_col('cli_store', array('store_id'=>$params['storeId']), 'site_id', null, 1);
            return Class_db::getInstance()->db_insert('ast_part',
                array(
                    'site_id'=>$siteId,
                    'store_id'=>$params['storeId'],
                    'asset_group_id'=>$assetGroupId,
                    'item_type_id'=>$item['itemTypeId'],
                    'item_id'=>$params['itemId'],
                    'part_count'=>'0',
                    'part_locked'=>'0',
                    'part_threshold'=>$item['itemTypeId'],
                    'part_min_order'=>$item['itemMinOrder'],
                    'part_max_order'=>$item['itemMaxOrder'],
                    'part_remark'=>$item['itemRemark'],
                    'part_status'=>'2'
                )
            );
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $partId
     * @param array $params
     * @throws Exception
     */
    public function updatePart ($partId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($partId, $params));
            Class_db::getInstance()->db_update('ast_part', $this->fn_general->convertToMysqlArrAll($params), array('part_id'=>$partId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $partId
     * @throws Exception
     */
    public function deactivatePart ($partId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($partId));
            Class_db::getInstance()->db_update('ast_part', array('part_status'=>'2'), array('part_id'=>$partId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $partId
     * @throws Exception
     */
    public function activatePart ($partId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($partId));
            Class_db::getInstance()->db_update('ast_part', array('part_status'=>'1'), array('part_id'=>$partId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
