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
            return Class_db::getInstance()->db_select_single2('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId), null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskRequestId
     * @return array
     * @throws Exception
     */
    public function getCurrentTask ($woTaskRequestId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId));

            $transactionId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId), 'transaction_id', '', 1);
            $taskId = Class_db::getInstance()->db_select_col('wfl_task', array('transaction_id'=>$transactionId, 'task_current'=>'1'), 'task_id', '', 1);
            return array('transactionId'=>$transactionId, 'taskId'=>$taskId);
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
     * @param $userId
     * @param string $woTaskRequestId
     * @param $transactionId
     * @param string $taskId
     * @param string $woTaskId
     * @return string
     * @throws Exception
     */
    public function checkRequestTask ($submitType, $userId, $woTaskRequestId, $transactionId, $taskId, $woTaskId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($submitType, $userId));

            if ($submitType === 'submit_request') {
                // ********** submit request checking ********** \\
                $this->fn_general->checkEmptyParams(array($woTaskId));
                $woTaskRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_order_by'=>$userId, 'wo_task_request_status'=>'32'), 'wo_task_request_id');
                if (empty($woTaskRequestId)) {
                    Class_db::getInstance()->db_update('wo_task', array('wo_task_has_parts'=>'0'), array('wo_task_id'=>$woTaskId));
                    return 'noPart';
                }
                if (Class_db::getInstance()->db_count('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId)) == 0) {
                    Class_db::getInstance()->db_delete('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId));
                    Class_db::getInstance()->db_update('wo_task', array('wo_task_has_parts'=>'0'), array('wo_task_id'=>$woTaskId));
                    return 'noPart';
                } else if (Class_db::getInstance()->db_sum('wo_task_parts', 'wo_task_parts_quantity', array('wo_task_request_id'=>$woTaskRequestId)) == 0) {
                    throw new Exception('[' . __LINE__ . '] - Total Requested Material empty', 31);
                }
            } else {
                // ********** standard checking ********** \\
                $this->fn_general->checkEmptyParams(array($woTaskRequestId, $transactionId, $taskId));
                $woTaskId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId), 'wo_task_id', '', 1);
                $siteId = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$woTaskId), 'site_id', '', 1);
                if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'site_id'=>$siteId)) == 0) {
                    throw new Exception('[' . __LINE__ . '] - Invalid site');
                }
                if (Class_db::getInstance()->db_count('sys_user', array('user_id'=>$userId, 'user_signature'=>'is NULL')) == 1) {
                    throw new Exception('[' . __LINE__ . '] - Please make sure you already save your personal signature from User Profile page to proceed', 31);
                }

                // ********** checking for every submit type ********** \\
                if ($submitType === 'approve_request' || $submitType === 'reject_request') {
                    if (Class_db::getInstance()->db_count('wfl_transaction', array('transaction_id'=>$transactionId, 'transaction_status'=>'33')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid transaction status');
                    }
                    if (Class_db::getInstance()->db_count('wfl_task', array('task_id'=>$taskId, 'task_current'=>'1', 'checkpoint_id'=>'42')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid task request');
                    }
                    if (Class_db::getInstance()->db_count('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId, 'wo_task_request_status'=>'33')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid request status');
                    }
                    if (Class_db::getInstance()->db_count('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId, 'wo_task_parts_status'=>'33')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Request part empty');
                    }
                    if (Class_db::getInstance()->db_count('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId, 'wo_task_parts_status'=>'<>33')) > 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid request part status');
                    }
                    if (Class_db::getInstance()->db_sum('wo_task_parts', 'wo_task_parts_quantity', array('wo_task_request_id'=>$woTaskRequestId)) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Total Requested Material empty', 31);
                    }
                } else if ($submitType === 'reserve_request') {
                    if (Class_db::getInstance()->db_count('wfl_transaction', array('transaction_id'=>$transactionId, 'transaction_status'=>'34')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid transaction status');
                    }
                    if (Class_db::getInstance()->db_count('wfl_task', array('task_id'=>$taskId, 'task_current'=>'1', 'checkpoint_id'=>'43')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid task request');
                    }
                    if (Class_db::getInstance()->db_count('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId, 'wo_task_request_status'=>'34')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid request status');
                    }
                    $requestParts = Class_db::getInstance()->db_select2('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId));
                    if (empty($requestParts)) {
                        throw new Exception('[' . __LINE__ . '] - Request part empty');
                    }
                    foreach ($requestParts as $requestPart) {
                        if ($requestPart['woTaskPartsStatus'] !== '34') {
                            throw new Exception('[' . __LINE__ . '] - Invalid request part status');
                        }
                        $part = Class_db::getInstance()->db_select_single2('ast_part', array('part_id'=>$requestPart['partId']), '', 1);
                        $partAvailable = intval($part['partCount']) - intval($part['partLocked']);
                        if ($partAvailable < intval($requestPart['woTaskPartsQuantity'])) {
                            throw new Exception('[' . __LINE__ . '] - Please make sure all the requested parts available. If not, please perform the stock order for the insufficient part.', 31);
                        }
                        if (Class_db::getInstance()->db_count('ast_part_sub', array('part_id'=>$requestPart['partId'], 'part_sub_status'=>'46')) != $partAvailable) {
                            throw new Exception('[' . __LINE__ . '] - Invalid total parts available in inventory');
                        }
                    }
                } else if ($submitType === 'check_out_request') {
                    if (Class_db::getInstance()->db_count('wfl_transaction', array('transaction_id'=>$transactionId, 'transaction_status'=>'38')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid transaction status');
                    }
                    if (Class_db::getInstance()->db_count('wfl_task', array('task_id'=>$taskId, 'task_current'=>'1', 'checkpoint_id'=>'43')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid task request');
                    }
                    if (Class_db::getInstance()->db_count('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId, 'wo_task_request_status'=>'38')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid request status');
                    }
                    $requestParts = Class_db::getInstance()->db_select2('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId));
                    if (empty($requestParts)) {
                        throw new Exception('[' . __LINE__ . '] - Request part empty');
                    }
                    foreach ($requestParts as $requestPart) {
                        if ($requestPart['woTaskPartsStatus'] !== '38') {
                            throw new Exception('[' . __LINE__ . '] - Invalid request part status');
                        }
                        if (Class_db::getInstance()->db_count('ast_part_sub', array('wo_task_parts_id'=>$requestPart['woTaskPartsId'], 'part_id'=>$requestPart['partId'], 'part_sub_status'=>'51')) != $requestPart['woTaskPartsQuantity']) {
                            throw new Exception('[' . __LINE__ . '] - Invalid total parts locked in inventory');
                        }
                    }
                } else {
                    throw new Exception('[' . __LINE__ . '] - Invalid submitType');
                }
            }
            return '1';
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
            $this->fn_general->checkEmptyParams(array($woTaskId, $transactionId, $woTaskRequestNo));

            $woRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_status'=>'32'), 'wo_task_request_id');
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_no'=>$woTaskRequestNo, 'transaction_id'=>$transactionId, 'wo_task_request_time_ordered'=>'Now()', 'wo_task_request_status'=>'33'),
                array('wo_task_request_id'=>$woRequestId));
            Class_db::getInstance()->db_update('wo_task_parts', array('wo_task_parts_status'=>'33'), array('wo_task_request_id'=>$woRequestId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'33'), array('transaction_id'=>$transactionId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_has_parts'=>'1'), array('wo_task_id'=>$woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param string $searchText
     * @return array
     * @throws Exception
     */
    public function getPendingTask ($userId, $searchText='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $checkpoints = array();
            if (Class_db::getInstance()->db_count('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>'42', 'role_id'=>'17', 'group_id'=>'1')) > 0) {
                array_push($checkpoints, '42');
            }
            if (Class_db::getInstance()->db_count('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>'43', 'role_id'=>'16', 'group_id'=>'1')) > 0) {
                array_push($checkpoints, '43');
            }
            if (empty($checkpoints)) {
                return array();
            }

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            return Class_db::getInstance()->db_select2('vw_wo_request_task_m', array(), 'task_received_time DESC', '100', 0,
                array('taskCurrent'=>'task_current = 1', 'siteId'=>$siteId, 'checkpoints'=>implode(',', $checkpoints), 'search_text'=>$searchText));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskRequestId
     * @return array
     * @throws Exception
     */
    public function getWoRequestDetails ($woTaskRequestId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId));
            return Class_db::getInstance()->db_select_single2('vw_wo_request_task_detail_m', array('wo_task_request_id'=>$woTaskRequestId), '', 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskRequestId
     * @param $transactionId
     * @throws Exception
     */
    public function submitApprove ($woTaskRequestId, $transactionId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId, $transactionId));

            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'34'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wo_task_parts', array('wo_task_parts_status'=>'34'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'34'), array('transaction_id'=>$transactionId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskRequestId
     * @param $transactionId
     * @param $woTaskNo
     * @param $woTaskRequestNo
     * @throws Exception
     */
    public function submitReserve ($woTaskRequestId, $transactionId, $woTaskNo, $woTaskRequestNo) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId, $transactionId, $woTaskNo, $woTaskRequestNo));

            $requestParts = Class_db::getInstance()->db_select2('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId));
            foreach ($requestParts as $requestPart) {
                $part = Class_db::getInstance()->db_select_single2('ast_part', array('part_id'=>$requestPart['partId']), '', 1);
                $partLocked = intval($part['partLocked'] + intval($requestPart['woTaskPartsQuantity']));
                Class_db::getInstance()->db_update('ast_part', array('part_locked'=>strval($partLocked)), array('part_id'=>$requestPart['partId']));
                $partSubs = Class_db::getInstance()->db_select2('ast_part_sub', array('part_id'=>$requestPart['partId'], 'part_sub_status'=>'46'), 'part_sub_validity, part_sub_id', $requestPart['woTaskPartsQuantity']);
                foreach ($partSubs as $partSub) {
                    Class_db::getInstance()->db_update('ast_part_sub', array('wo_task_parts_id'=>$requestPart['wo_task_parts_id'], 'wo_task_no'=>$woTaskNo, 'wo_task_request_no'=>$woTaskRequestNo,
                        'part_sub_time_reserved'=>'Now()', 'part_sub_status'=>'51'), array('part_sub_id'=>$partSub['partSubId']));
                }
            }
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'38'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wo_task_parts', array('wo_task_parts_status'=>'38'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'38'), array('transaction_id'=>$transactionId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskRequestId
     * @param $transactionId
     * @param $userId
     * @throws Exception
     */
    public function submitCheckOutRequest ($woTaskRequestId, $transactionId, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId, $transactionId, $userId));

            $requestParts = Class_db::getInstance()->db_select2('wo_task_parts', array('wo_task_request_id'=>$woTaskRequestId));
            foreach ($requestParts as $requestPart) {
                $part = Class_db::getInstance()->db_select_single2('ast_part', array('part_id'=>$requestPart['partId']), '', 1);
                $partCount = intval($part['partCount'] - intval($requestPart['woTaskPartsQuantity']));
                $partLocked = intval($part['partLocked'] - intval($requestPart['woTaskPartsQuantity']));
                Class_db::getInstance()->db_update('ast_part', array('part_count'=>strval($partCount), 'part_locked'=>strval($partLocked)), array('part_id'=>$requestPart['partId']));
                Class_db::getInstance()->db_update('ast_part_sub', array('part_sub_collected_by'=>$userId, 'part_sub_time_check_out'=>'Now()', 'part_sub_status'=>'36'), array('wo_task_parts_id'=>$requestPart['woTaskPartsId']));
            }
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'36', 'wo_task_request_time_collected'=>'Now()'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wo_task_parts', array('wo_task_parts_status'=>'36'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'36'), array('transaction_id'=>$transactionId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getCheckOutMobileList ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            return Class_db::getInstance()->db_select2('vw_check_out_mobile_list', array('r.wo_task_request_status'=>'36', 'w.site_id'=>$siteId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}