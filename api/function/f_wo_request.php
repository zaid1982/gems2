<?php

class Class_wo_request {

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
     * @param $woTaskRequestId
     * @return mixed
     * @throws Exception
     */
    public function getWoRequest ($woTaskRequestId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId));
            return $this->fn_general->convertDbIndex(Class_db::getInstance()->db_select_single('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId), null, 1));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @return string
     * @throws Exception
     */
    public function createRequestNo ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            $site = Class_db::getInstance()->db_select_single('cli_site', array('site_id'=>$siteId), null, 1);
            $siteCode = $site['site_code'];
            $runningNo = $site['site_running_no_req'];
            $runningNo = intval($runningNo);
            $runningNoTemp = 100000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            $runningNo++;
            $curDates = new DateTime();
            Class_db::getInstance()->db_update('cli_site', array('site_running_no_req'=>strval($runningNo)), array('site_id'=>$siteId));
            return 'RQ'.$siteCode.$curDates->format("ymd").$runningNoStr;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $submitType
     * @param $woTaskId
     * @param $userId
     * @throws Exception
     */
    public function checkRequestTask ($submitType, $woTaskId, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($submitType, $woTaskId));

            if ($submitType === 'submit_request') {
                $woRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_order_by'=>$userId, 'wo_task_request_status'=>'32'), 'wo_task_request_id');
                if (empty($woRequestId) || Class_db::getInstance()->db_count('wo_task_parts', array('wo_task_request_id'=>$woRequestId)) == 0) {
                    throw new Exception('[' . __LINE__ . '] - Requested Material list empty', 31);
                }
                if (Class_db::getInstance()->db_sum('wo_task_parts', 'wo_task_parts_quantity', array('wo_task_request_id'=>$woRequestId)) == 0) {
                    throw new Exception('[' . __LINE__ . '] - Total Requested Material empty', 31);
                }
            } else {
                throw new Exception('[' . __LINE__ . '] - Invalid submitType');
            }
       } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @param $transactionId
     * @param $woTaskRequestNo
     * @throws Exception
     */
    public function submitRequest ($woTaskId, $transactionId, $woTaskRequestNo) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskId, $woTaskRequestNo));

            $woRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_status'=>'32'), 'wo_task_request_id');
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_no'=>$woTaskRequestNo, 'transaction_id'=>$transactionId, 'wo_task_request_time_ordered'=>'Now()', 'wo_task_request_status'=>'33'),
                array('wo_task_request_id'=>$woRequestId));
            Class_db::getInstance()->db_update('wo_task_parts', array('wo_task_parts_status'=>'33'), array('wo_task_request_id'=>$woRequestId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_has_parts'=>'1'), array('wo_task'=>$woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}