<?php

class Class_do {

    private $fn_general;
    private $constant;

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
     * @return mixed
     * @throws Exception
     */
    public function getCheckInMobileList ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            return Class_db::getInstance()->db_select2('vw_check_in_mobile_list', array('d.site_id'=>$siteId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $doId
     * @return mixed
     * @throws Exception
     */
    public function getCheckInMobileDetails ($doId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($doId));
            $constant = $this->constant;

            // ********** do items ********** \\
            $doItemsResult = array();
            $doItems = Class_db::getInstance()->db_select2('vw_do_item_mobile', array('pdi.do_id'=>$doId));
            foreach ($doItems as $doItem) {
                $doItemsSliced = array_slice($doItem, 4);
                $imageUploads = explode('||', $doItem['uploadList']);
                $imageTitles = explode('||', $doItem['titleList']);
                $imageWidths = explode('||', $doItem['widthList']);
                $imageHeights = explode('||', $doItem['heightList']);
                $images = array();
                foreach ($imageUploads as $n => $imageUpload) {
                    array_push($images, array('file'=>$constant::URL_FULL.$imageUpload, 'title'=>$imageTitles[$n], 'width'=>$imageWidths[$n], 'height'=>$imageHeights[$n]));
                }
                $doItemsSliced['images'] = $images;
                array_push($doItemsResult, $doItemsSliced);
            }

            // ********** do ********** \\
            $checkInDetails = Class_db::getInstance()->db_select_single2('vw_check_in_mobile_list', array('d.do_id'=>$doId), '', 1);
            $checkInDetails['doItems'] = $doItemsResult;

            // ********** do uploads ********** \\
            $images = array();
            $items = Class_db::getInstance()->db_select2('vw_do_upload', array('d.do_id'=>$doId));
            foreach ($items as $item) {
                array_push($images, array('file'=>$constant::URL_FULL.$item['uploadFolder'].'/'.$item['uploadFilename'].'.'.$item['uploadExtension'], 'title'=>$item['uploadName'], 'width'=>$item['uploadFileWidth'], 'height'=>$item['uploadFileHeight']));
            }
            $checkInDetails['doUploads'] = $images;

            return $checkInDetails;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
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

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            $insertParams = array_merge(
                $this->fn_general->convertToMysqlArr($params, array('doNo', 'doDate', 'supplierName')),
                array('siteId'=>$siteId, 'doType'=>'Normal', 'doCreatedBy'=>$userId, 'doReceivedBy'=>$userId, 'doStatus'=>'45')
            );
            return Class_db::getInstance()->db_insert('do', $this->fn_general->convertToMysqlArrAll($insertParams));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}