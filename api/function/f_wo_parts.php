<?php

class Class_wo_parts {

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
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getWoPartsMobileList ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;
            $this->fn_general->checkEmptyParams(array($woTaskId));

            $result = array();
            $woTaskParts = $this->fn_general->convertDbIndexs(Class_db::getInstance()->db_select('vw_wo_task_parts_mobile', array('wo_task_id'=>$woTaskId)));
            foreach ($woTaskParts as $woTaskPart) {
                $woTaskPartSliced = array_slice($woTaskPart, 4);
                $imageUploads = explode('||', $woTaskPart['uploadList']);
                $imageTitles = explode('||', $woTaskPart['titleList']);
                $imageWidths = explode('||', $woTaskPart['widthList']);
                $imageHeights = explode('||', $woTaskPart['heightList']);
                $images = array();
                foreach ($imageUploads as $n => $imageUpload) {
                    array_push($images, array('file'=>$constant::URL_FULL.$imageUpload, 'title'=>$imageTitles[$n], 'width'=>$imageWidths[$n], 'height'=>$imageHeights[$n]));
                }
                $woTaskPartSliced['images'] = $images;
                array_push($result, $woTaskPartSliced);
            }
            return $result;
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
    public function getWoPartsList ($partId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($partId));
            return $this->fn_general->convertDbIndexs(Class_db::getInstance()->db_select('vw_wo_task_parts', array('part_id'=>$partId)));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $partId
     * @param $statusId
     * @return mixed
     * @throws Exception
     */
    public function getWoPartsByStatusList ($partId, $statusId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($partId));
            return $this->fn_general->convertDbIndexs(Class_db::getInstance()->db_select('vw_wo_task_parts', array('part_id'=>$partId, 'wo_task_parts_status'=>$statusId)));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @param $userId
     * @return string
     * @throws Exception
     */
    public function addWoPartsMobile ($params, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($params, $userId));
            $this->fn_general->checkEmptyParamsArray($params, array('woTaskId', 'itemId', 'quantity', 'remark'));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            $partId = Class_db::getInstance()->db_select_col('ast_part', array('item_id'=>$params['itemId'], 'site_id'=>$siteId), 'part_id', 'part_count DESC', 1);
            if (Class_db::getInstance()->db_count('wo_task_parts', array('wo_task_id'=>$params['woTaskId'], 'part_id'=>$partId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - This item description already exist in this Request List.', 31);
            }
            return Class_db::getInstance()->db_insert('wo_task_parts', array('wo_task_id'=>$params['woTaskId'], 'part_id'=>$partId, 'wo_task_parts_quantity'=>$params['quantity'], 'wo_task_parts_remark'=>$params['remark'], 'wo_task_parts_order_by'=>$userId,
                'wo_task_parts_time_ordered'=>'Now()'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
