<?php

class Class_part_sub {

    private $fn_general;
    private $site;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else {
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
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
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param string $statusId
     * @param string $partId
     * @return mixed
     * @throws Exception
     */
    public function getPartSubList ($statusId='', $partId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return Class_db::getInstance()->db_select2('ast_part_sub', array('part_sub_status'=>$statusId, 'part_id'=>$partId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return string
     * @throws Exception
     */
    public function createPartSubNo ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($siteId));

            $site = Class_db::getInstance()->db_select_single2('cli_site', array('site_id'=>$siteId), null, 1);
            $siteCode = $site['siteCode'];
            $runningNo = intval($site['siteRunningNoPartSub']);
            $runningNoTemp = 1000000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            $runningNo++;
            $curDates = new DateTime();
            Class_db::getInstance()->db_update('cli_site', array('site_running_no_part_sub'=>strval($runningNo)), array('site_id'=>$siteId));
            return 'I'.$siteCode.$curDates->format("y").$runningNoStr;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $storeId
     * @param $userId
     * @param $doNo
     * @param array $doItem
     * @return void
     * @throws Exception
     */
    public function addPartSubMobile ($storeId, $userId, $doNo, $doItem=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($storeId, $userId, $doNo, $doItem));
            $this->fn_general->checkEmptyParamsArray($doItem, array('partId', 'doItemTotal', 'doItemCost'));

            $totalItem = intval($doItem['doItemTotal']);
            $part = Class_db::getInstance()->db_select_single2('ast_part', array('part_id'=>$doItem['partId'], 'part_status'=>'1'), null, 1);
            $itemDescription = Class_db::getInstance()->db_select_col('ref_item', array('item_id'=>$part['itemId']), 'item_description', '', 1);
            if (intval($part['partMinOrder']) > $totalItem) {
                throw new Exception('[' . __LINE__ . '] - Invalid order. Minimum order for '.$itemDescription.' is '.$part['partMinOrder'], 31);
            }
            if (intval($part['partMaxOrder']) < $totalItem) {
                throw new Exception('[' . __LINE__ . '] - Invalid order. Maximum order for '.$itemDescription.' is '.$part['partMinOrder'], 31);
            }
            $newCount = intval($part['partCount']) + $totalItem;
            Class_db::getInstance()->db_update('ast_part', array('part_count'=>strval($newCount)), array('part_id'=>$doItem['partId']));

            $siteId = Class_db::getInstance()->db_select_col('cli_store', array('store_id'=>$storeId), 'site_id', '', 1);
            $itemId = Class_db::getInstance()->db_select_col('ast_part', array('part_id'=>$doItem['partId']), 'item_id', '', 1);
            $site = Class_db::getInstance()->db_select_single2('cli_site', array('site_id'=>$siteId), null, 1);
            $siteCode = $site['siteCode'];
            $runningNo = intval($site['siteRunningNoPartSub']);
            $curDates = new DateTime();
            for ($i=0; $i<$totalItem; $i++) {
                $runningNoTemp = 1000000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $partSubNo = 'I'.$siteCode.$curDates->format("y").$runningNoStr;
                Class_db::getInstance()->db_insert('ast_part_sub', array('part_id'=>$doItem['partId'], 'item_id'=>$itemId, 'part_sub_no'=>$partSubNo, 'do_no'=>$doNo, 'do_item_id'=>$doItem['doItemId'],
                    'part_sub_location'=>$doItem['partSubLocation'], 'part_sub_warranty'=>$doItem['doItemWarranty'], 'part_sub_validity'=>$doItem['doItemValidity'], 'part_sub_cost'=>$doItem['doItemCost'],
                    'part_sub_registered_by'=>$userId, 'part_sub_status'=>'46'));
            }
            Class_db::getInstance()->db_update('cli_site', array('site_running_no_part_sub'=>strval($runningNo)), array('site_id'=>$siteId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}