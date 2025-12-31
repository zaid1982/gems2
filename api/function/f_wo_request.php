<?php

class Class_wo_request {

    private $fn_general;

    private $mrReviewerRoleDesc = 'MR Reviewer';
    private $mrReviewerCheckpointDesc = 'MR Reviewer';
    private $mrFlowId = '4';

    function __construct() {
    }

    private function getMrReviewerRoleIdOrNull() {
        $roleId = Class_db::getInstance()->db_select_col('ref_role', array('role_desc'=>$this->mrReviewerRoleDesc), 'role_id', '', 1);
        return empty($roleId) ? '' : $roleId;
    }

    private function getMrReviewerCheckpointIdOrNull($reviewerRoleId) {
        if (empty($reviewerRoleId)) {
            return '';
        }
        $checkpointId = Class_db::getInstance()->db_select_col(
            'wfl_checkpoint',
            array('flow_id'=>$this->mrFlowId, 'checkpoint_desc'=>$this->mrReviewerCheckpointDesc, 'role_id'=>$reviewerRoleId),
            'checkpoint_id',
            '',
            1
        );
        return empty($checkpointId) ? '' : $checkpointId;
    }

    private function requireMrReviewerCheckpoint() {
        $roleId = $this->getMrReviewerRoleIdOrNull();
        $checkpointId = $this->getMrReviewerCheckpointIdOrNull($roleId);
        if (empty($roleId) || empty($checkpointId)) {
            throw new Exception('[' . __LINE__ . '] - MR Reviewer workflow not configured. Please add role/checkpoint (role_desc="'.$this->mrReviewerRoleDesc.'", checkpoint_desc="'.$this->mrReviewerCheckpointDesc.'")', 31);
        }
        return array('roleId'=>$roleId, 'checkpointId'=>$checkpointId);
    }

    private function hasAnyRole($userId, array $roleIds) {
        if (empty($roleIds)) {
            return false;
        }
        $roleList = '(' . implode(',', array_map('intval', $roleIds)) . ')';
        return Class_db::getInstance()->db_count('sys_user_role', array('user_id'=>$userId, 'role_id'=>$roleList)) > 0;
    }

    private function getUserSiteId($userId) {
        return Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
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
     * @param string $comment
     * @return string
     * @throws Exception
     */
    public function checkRequestTask ($submitType, $userId, $woTaskRequestId, $transactionId, $taskId, $woTaskId='', $comment='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($submitType, $userId));

            if ($submitType === 'submit_request') {
                // ********** submit request checking ********** \\
                $this->fn_general->checkEmptyParams(array($woTaskId));
                $woTaskRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_order_by'=>$userId, 'wo_task_request_status'=>'32'), 'wo_task_request_id', 'wo_task_request_id DESC');
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
                    if ($submitType === 'reject_request' && empty($comment)) {
                        throw new Exception('[' . __LINE__ . '] - Reject Comment is empty', 31);
                    }
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
                } else if ($submitType === 'recommend_request' || $submitType === 'not_recommend_request') {
                    if ($submitType === 'not_recommend_request' && empty($comment)) {
                        throw new Exception('[' . __LINE__ . '] - Comment is empty', 31);
                    }

                    $reviewer = $this->requireMrReviewerCheckpoint();
                    $reviewerCheckpointId = $reviewer['checkpointId'];

                    if (Class_db::getInstance()->db_count('wfl_transaction', array('transaction_id'=>$transactionId, 'transaction_status'=>'33')) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid transaction status');
                    }
                    if (Class_db::getInstance()->db_count('wfl_task', array('task_id'=>$taskId, 'task_current'=>'1', 'checkpoint_id'=>$reviewerCheckpointId)) == 0) {
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

            $woRequestId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_status'=>'32'), 'wo_task_request_id', 'wo_task_request_id DESC');
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_no'=>$woTaskRequestNo, 'transaction_id'=>$transactionId, 'wo_task_request_time_ordered'=>'Now()', 'wo_task_request_status'=>'33',
                'wo_task_request_mrf_generate'=>'1'), array('wo_task_request_id'=>$woRequestId));
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
            $reviewerRoleId = $this->getMrReviewerRoleIdOrNull();
            $reviewerCheckpointId = $this->getMrReviewerCheckpointIdOrNull($reviewerRoleId);
            if (!empty($reviewerCheckpointId) && !empty($reviewerRoleId)) {
                // Do not hardcode group_id here; users may belong to a site-specific group.
                if (Class_db::getInstance()->db_count('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>$reviewerCheckpointId, 'role_id'=>$reviewerRoleId)) > 0) {
                    array_push($checkpoints, $reviewerCheckpointId);
                }
            }
            if (Class_db::getInstance()->db_count('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>'42', 'role_id'=>'17')) > 0) {
                array_push($checkpoints, '42');
            }
            if (Class_db::getInstance()->db_count('wfl_checkpoint_user', array('user_id'=>$userId, 'checkpoint_id'=>'43', 'role_id'=>'16')) > 0) {
                array_push($checkpoints, '43');
            }
            if (empty($checkpoints)) {
                return array();
            }

            // Be defensive: a missing sys_user row or empty site should not hard-fail the mobile list.
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 0);
            if (empty($siteId)) {
                return array();
            }

            try {
                $rows = Class_db::getInstance()->db_select2('vw_wo_request_task_m', array(), 'task_received_time DESC', '100', 0,
                    array('taskCurrent'=>'task_current = 1', 'siteId'=>$siteId, 'checkpoints'=>implode(',', $checkpoints), 'search_text'=>$searchText));
                return empty($rows) ? array() : $rows;
            } catch (Exception $inner) {
                // Legacy DB layer may throw on empty results in some environments.
                if (strpos($inner->getMessage(), 'Select query result empty') !== false || $inner->getCode() === 30) {
                    return array();
                }
                throw $inner;
            }
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

            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'34', 'wo_task_request_mrf_generate'=>'1'), array('wo_task_request_id'=>$woTaskRequestId));
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
     * @param $remark
     * @throws Exception
     */
    public function submitReject ($woTaskRequestId, $transactionId, $remark) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskRequestId, $transactionId, $remark));

            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'50', 'wo_task_request_remark'=>$remark, 'wo_task_request_time_rejected'=>'Now()', 'wo_task_request_mrf_generate'=>'1'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wo_task_parts', array('wo_task_parts_status'=>'50'), array('wo_task_request_id'=>$woTaskRequestId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'50'), array('transaction_id'=>$transactionId));
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
                    Class_db::getInstance()->db_update('ast_part_sub', array('wo_task_parts_id'=>$requestPart['woTaskPartsId'], 'wo_task_no'=>$woTaskNo, 'wo_task_request_no'=>$woTaskRequestNo,
                        'part_sub_time_reserved'=>'Now()', 'part_sub_status'=>'51'), array('part_sub_id'=>$partSub['partSubId']));
                }
            }
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'38', 'wo_task_request_mrf_generate'=>'1'), array('wo_task_request_id'=>$woTaskRequestId));
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
            Class_db::getInstance()->db_update('wo_task_request', array('wo_task_request_status'=>'36', 'wo_task_request_time_collected'=>'Now()', 'wo_task_request_mrf_generate'=>'1'), array('wo_task_request_id'=>$woTaskRequestId));
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

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getReturnMobileList ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            return Class_db::getInstance()->db_select2('vw_return_mobile_list', array(), 'check_out_time DESC', '', 0,
                array('userId'=>$userId, 'siteId'=>$siteId));
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
    public function getReturnMobileSummary ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            return Class_db::getInstance()->db_select2('vw_return_eligible_items', array(), 'workOrderNo DESC', '', 0,
                array('user_id'=>$userId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param string $siteId
     * @param bool $includeDetail
     * @return array
     * @throws Exception
     */
    public function listReturnVerification ($userId, $siteId='', $includeDetail=false) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $isAdmin = $this->hasAnyRole($userId, array(1, 10));
            $siteScope = '';

            if ($isAdmin) {
                if ($siteId !== '') {
                    if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId)) == 0) {
                        throw new Exception('[' . __LINE__ . '] - Invalid siteId provided', 31);
                    }
                    $siteScope = strval(intval($siteId));
                }
            } else {
                $siteScope = strval($this->getUserSiteId($userId));
                if ($siteId !== '' && $siteId !== $siteScope) {
                    throw new Exception('[' . __LINE__ . '] - You are not allowed to access the requested site', 31);
                }
            }

            $siteFilter = $siteScope !== '' ? 's.site_id = '.$siteScope : '1=1';
            $tickets = Class_db::getInstance()->db_select2('vw_storekeeper_pending_returns', array(), 'returnRequestDate DESC', '', 0,
                array('site_filter'=>$siteFilter));

            foreach ($tickets as &$ticket) {
                $pendingItems = Class_db::getInstance()->db_select2('ast_part_sub', array(
                    'part_sub_return_id'=>$ticket['returnId'],
                    'part_sub_status'=>'37'
                ), 'part_sub_id ASC');

                $ticket['itemCount'] = count($pendingItems);
                $ticket['pendingCount'] = $ticket['itemCount'];
                $ticket['partSubIds'] = array_map(function($row) {
                    return $row['partSubId'];
                }, $pendingItems);
                $ticket['itemDescription'] = $ticket['partName'];

                if ($includeDetail) {
                    $ticket['items'] = array_map(function($row) use ($ticket) {
                        $row['partName'] = $ticket['partName'];
                        $row['itemDescription'] = $ticket['partName'];
                        $row['technicianName'] = $ticket['technicianName'];
                        $row['workOrderNo'] = $ticket['workOrderNo'];
                        $row['woTaskRequestNo'] = $ticket['woTaskRequestNo'];
                        return $row;
                    }, $pendingItems);
                }
            }

            return $tickets;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $items
     * @return array
     * @throws Exception
     */
    public function returnCollectedParts ($userId, $items) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            if (empty($items) || !is_array($items)) {
                throw new Exception('[' . __LINE__ . '] - Return payload empty', 31);
            }

            $allowedReasons = array('unused_excess', 'wrong_part', 'damaged', 'other');
            $totalReturned = 0;
            $summary = array();
            $processedSubIds = array();

            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    throw new Exception('[' . __LINE__ . '] - Invalid item structure at index '. $index, 31);
                }

                $partSubIds = array();
                if (isset($item['partSubIds'])) {
                    if (!is_array($item['partSubIds'])) {
                        throw new Exception('[' . __LINE__ . '] - partSubIds must be an array at index '.$index, 31);
                    }
                    $partSubIds = array_map('intval', $item['partSubIds']);
                    $partSubIds = array_filter($partSubIds, function($val) { return $val > 0; });
                }

                $requestedQuantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
                $woTaskPartsId = isset($item['woTaskPartsId']) ? $item['woTaskPartsId'] : '';
                $returnReason = isset($item['returnReason']) ? strtolower(trim($item['returnReason'])) : 'unused_excess';
                $returnRemarks = isset($item['returnRemarks']) ? $item['returnRemarks'] : (isset($item['remark']) ? $item['remark'] : null);

                if (!in_array($returnReason, $allowedReasons)) {
                    throw new Exception('[' . __LINE__ . '] - Invalid return reason supplied (item '.($index+1).')', 31);
                }

                if (empty($partSubIds) && empty($woTaskPartsId)) {
                    throw new Exception('[' . __LINE__ . '] - woTaskPartsId required when partSubIds not provided (item '.($index+1).')', 31);
                }

                if (!empty($partSubIds) && $requestedQuantity > 0 && $requestedQuantity !== count($partSubIds)) {
                    throw new Exception('[' . __LINE__ . '] - Quantity mismatch for partSubIds at item '.($index+1), 31);
                }

                $targetQuantity = $requestedQuantity;
                if (empty($partSubIds)) {
                    if ($targetQuantity <= 0) {
                        throw new Exception('[' . __LINE__ . '] - Quantity must be greater than zero when partSubIds not provided (item '.($index+1).')', 31);
                    }
                } else {
                    $targetQuantity = count($partSubIds);
                }

                if ($targetQuantity <= 0) {
                    throw new Exception('[' . __LINE__ . '] - Invalid return quantity at item '.($index+1), 31);
                }

                $partSubs = array();
                if (!empty($partSubIds)) {
                    $duplicates = array_intersect(array_keys($processedSubIds), $partSubIds);
                    if (!empty($duplicates)) {
                        throw new Exception('[' . __LINE__ . '] - Duplicate partSubId detected: '.implode(',', $duplicates), 31);
                    }

                    $idStr = '('.implode(',', $partSubIds).')';
                    $partSubs = Class_db::getInstance()->db_select2('ast_part_sub', array(
                        'part_sub_id'=>$idStr,
                        'part_sub_collected_by'=>$userId,
                        'part_sub_status'=>'36'
                    ));

                    if (count($partSubs) !== count($partSubIds)) {
                        throw new Exception('[' . __LINE__ . '] - One or more selected items are no longer returnable', 31);
                    }
                } else {
                    $availableCount = Class_db::getInstance()->db_count('ast_part_sub', array(
                        'wo_task_parts_id'=>$woTaskPartsId,
                        'part_sub_collected_by'=>$userId,
                        'part_sub_status'=>'36'
                    ));

                    if ($availableCount < $targetQuantity) {
                        throw new Exception('[' . __LINE__ . '] - Not enough items available to return for woTaskPartsId '.$woTaskPartsId.' (available '.$availableCount.', requested '.$targetQuantity.')', 31);
                    }

                    $partSubs = Class_db::getInstance()->db_select2('ast_part_sub', array(
                        'wo_task_parts_id'=>$woTaskPartsId,
                        'part_sub_collected_by'=>$userId,
                        'part_sub_status'=>'36'
                    ), 'part_sub_id ASC', $targetQuantity);
                }

                if (empty($partSubs)) {
                    throw new Exception('[' . __LINE__ . '] - No items resolved for return (item '.($index+1).')', 31);
                }

                $woTaskPartsIdResolved = $partSubs[0]['woTaskPartsId'];
                $woTaskPart = Class_db::getInstance()->db_select_single2('wo_task_parts', array('wo_task_parts_id'=>$woTaskPartsIdResolved), '', 1);

                if (!empty($woTaskPartsId) && $woTaskPartsId !== $woTaskPartsIdResolved) {
                    throw new Exception('[' . __LINE__ . '] - Mismatched woTaskPartsId detected', 31);
                }

                $returnQty = count($partSubs);
                $partId = $woTaskPart['partId'];

                $returnId = Class_db::getInstance()->db_insert('material_returns', array(
                    'wo_task_parts_id'=>$woTaskPartsIdResolved,
                    'part_id'=>$partId,
                    'technician_user_id'=>$userId,
                    'quantity_returned'=>$returnQty,
                    'return_status'=>'pending',
                    'return_reason'=>$returnReason,
                    'return_remarks'=>$returnRemarks,
                    'return_request_date'=>'Now()'
                ));

                foreach ($partSubs as $partSub) {
                    if ($partSub['woTaskPartsId'] !== $woTaskPartsIdResolved) {
                        throw new Exception('[' . __LINE__ . '] - Mixed woTaskPartsId detected in return payload', 31);
                    }
                    $processedSubIds[$partSub['partSubId']] = true;

                    Class_db::getInstance()->db_update('ast_part_sub', array(
                        'part_sub_status'=>'37',
                        'part_sub_return_id'=>$returnId,
                        'part_sub_returned_by'=>'NULL',
                        'part_sub_returned_date'=>'NULL'
                    ), array('part_sub_id'=>$partSub['partSubId']));
                }

                $summary[] = array(
                    'returnTicketId'=>$returnId,
                    'returnStatus'=>'pending',
                    'returnReason'=>$returnReason,
                    'returnRemarks'=>$returnRemarks,
                    'woTaskRequestId'=>$woTaskPart['woTaskRequestId'],
                    'woTaskPartsId'=>$woTaskPartsIdResolved,
                    'partId'=>$partId,
                    'quantityReturned'=>$returnQty,
                    'partSubIds'=>array_map(function($row) { return $row['partSubId']; }, $partSubs),
                    'woTaskNo'=>$partSubs[0]['woTaskNo'],
                    'woTaskRequestNo'=>$partSubs[0]['woTaskRequestNo']
                );

                $totalReturned += $returnQty;
            }

            if (empty($summary)) {
                throw new Exception('[' . __LINE__ . '] - No items resolved for return', 31);
            }

            $ticketIds = array_values(array_unique(array_map(function($row) {
                return $row['returnTicketId'];
            }, $summary)));
            $resultPayload = array(
                'totalReturned'=>$totalReturned,
                'items'=>$summary,
                'returnTicketIds'=>$ticketIds
            );
            if (count($ticketIds) === 1) {
                $resultPayload['returnTicketId'] = $ticketIds[0];
            }

            return $resultPayload;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $returnId
     * @param $action
     * @param array $partSubIds
     * @param string $remark
     * @return array
     * @throws Exception
     */
    public function verifyReturnTicket ($userId, $returnId, $action, $partSubIds=array(), $remark='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $returnId, $action));

            $action = strtolower($action);
            if (!in_array($action, array('approve', 'reject'), true)) {
                throw new Exception('[' . __LINE__ . '] - Invalid action supplied', 31);
            }

            if ($action === 'reject' && empty($remark)) {
                throw new Exception('[' . __LINE__ . '] - Remark required when rejecting returned items', 31);
            }

            $return = Class_db::getInstance()->db_select_single2('material_returns', array('return_id'=>$returnId), '', 1);
            if ($return['returnStatus'] !== 'pending') {
                throw new Exception('[' . __LINE__ . '] - Return ticket already processed', 31);
            }

            $isAdmin = $this->hasAnyRole($userId, array(1, 10));
            $isStorekeeper = $this->hasAnyRole($userId, array(16));
            if (!$isAdmin && !$isStorekeeper) {
                throw new Exception('[' . __LINE__ . '] - You are not allowed to verify returns', 31);
            }

            $woTaskPartsId = $return['woTaskPartsId'];
            $woTaskRequestId = Class_db::getInstance()->db_select_col('wo_task_parts', array('wo_task_parts_id'=>$woTaskPartsId), 'wo_task_request_id', '', 1);
            $woTaskId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_request_id'=>$woTaskRequestId), 'wo_task_id', '', 1);
            $siteId = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$woTaskId), 'site_id', '', 1);

            if (!$isAdmin) {
                $userSiteId = $this->getUserSiteId($userId);
                if ($userSiteId !== $siteId) {
                    throw new Exception('[' . __LINE__ . '] - You are not allowed to verify returns for this site', 31);
                }
            }

            $pendingItems = Class_db::getInstance()->db_select2('ast_part_sub', array(
                'part_sub_return_id'=>$returnId,
                'part_sub_status'=>'37'
            ), 'part_sub_id ASC');

            if (empty($pendingItems)) {
                throw new Exception('[' . __LINE__ . '] - No items left to verify for this ticket', 31);
            }

            $pendingMap = array();
            foreach ($pendingItems as $pending) {
                $pendingMap[$pending['partSubId']] = $pending;
            }

            $targets = array();
            if (!empty($partSubIds)) {
                if (!is_array($partSubIds)) {
                    throw new Exception('[' . __LINE__ . '] - partSubIds must be an array', 31);
                }
                $partSubIds = array_unique(array_map('intval', $partSubIds));
                foreach ($partSubIds as $partSubId) {
                    if (!isset($pendingMap[$partSubId])) {
                        throw new Exception('[' . __LINE__ . '] - partSubId '.$partSubId.' is not pending for this ticket', 31);
                    }
                    $targets[] = $pendingMap[$partSubId];
                }
            } else {
                $targets = $pendingItems;
            }

            if (empty($targets)) {
                throw new Exception('[' . __LINE__ . '] - No valid items selected for verification', 31);
            }

            $part = Class_db::getInstance()->db_select_single2('ast_part', array('part_id'=>$return['partId']), '', 1);
            $currentCount = intval($part['partCount']);
            $approvedCount = 0;
            $rejectedCount = 0;
            $itemsResult = array();

            foreach ($targets as $target) {
                if ($action === 'approve') {
                    Class_db::getInstance()->db_update('ast_part_sub', array(
                        'wo_task_parts_id'=>'NULL',
                        'wo_task_no'=>'',
                        'wo_task_request_no'=>'',
                        'part_sub_collected_by'=>'NULL',
                        'part_sub_time_check_out'=>'',
                        'part_sub_status'=>'46',
                        'part_sub_returned_by'=>$userId,
                        'part_sub_returned_date'=>'Now()'
                    ), array('part_sub_id'=>$target['partSubId']));
                    $approvedCount++;
                    $itemsResult[] = array(
                        'partSubId'=>$target['partSubId'],
                        'status'=>'approved'
                    );
                } else {
                    Class_db::getInstance()->db_update('ast_part_sub', array(
                        'part_sub_status'=>'38',
                        'part_sub_returned_by'=>$userId,
                        'part_sub_returned_date'=>'Now()'
                    ), array('part_sub_id'=>$target['partSubId']));
                    $rejectedCount++;
                    $itemsResult[] = array(
                        'partSubId'=>$target['partSubId'],
                        'status'=>'rejected',
                        'remark'=>$remark
                    );
                }
            }

            if ($approvedCount > 0) {
                $newCount = $currentCount + $approvedCount;
                Class_db::getInstance()->db_update('ast_part', array('part_count'=>strval($newCount)), array('part_id'=>$return['partId']));

                try {
                    Class_db::getInstance()->db_insert('inventory_logs', array(
                        'part_id'=>$return['partId'],
                        'change_type'=>'return',
                        'quantity_change'=>$approvedCount,
                        'quantity_before'=>$currentCount,
                        'quantity_after'=>$newCount,
                        'user_id'=>$userId,
                        'reference_id'=>$returnId,
                        'reference_type'=>'material_return',
                        'change_reason'=>'Store verification approve'
                    ));
                } catch (Exception $logEx) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Inventory log skipped: '.$logEx->getMessage());
                }

                $currentCount = $newCount;
            }

            $pendingCount = intval(Class_db::getInstance()->db_count('ast_part_sub', array('part_sub_return_id'=>$returnId, 'part_sub_status'=>'37')));
            $materialUpdate = array(
                'storekeeper_user_id'=>$userId,
                'updated_at'=>'Now()'
            );

            if ($pendingCount == 0) {
                $materialUpdate['return_status'] = 'completed';
                $materialUpdate['return_confirmed_date'] = 'Now()';
            }

            if ($action === 'reject' && !empty($remark)) {
                $existingRemark = isset($return['returnRemarks']) ? $return['returnRemarks'] : '';
                $suffix = (empty($existingRemark) ? '' : "\n").'Storekeeper: '.$remark;
                $materialUpdate['return_remarks'] = $existingRemark.$suffix;
            }

            Class_db::getInstance()->db_update('material_returns', $materialUpdate, array('return_id'=>$returnId));

            $statusLabel = 'pending';
            if ($approvedCount > 0 && $rejectedCount === 0 && $pendingCount === 0) {
                $statusLabel = 'approved';
            } else if ($approvedCount > 0 && ($rejectedCount > 0 || $pendingCount > 0)) {
                $statusLabel = 'partially_approved';
            } else if ($approvedCount === 0 && $rejectedCount > 0 && $pendingCount === 0) {
                $statusLabel = 'rejected';
            } else if ($pendingCount > 0) {
                $statusLabel = 'pending';
            }

            return array(
                'returnTicketId'=>$returnId,
                'action'=>$statusLabel,
                'approvedCount'=>$approvedCount,
                'rejectedCount'=>$rejectedCount,
                'pendingCount'=>$pendingCount,
                'items'=>$itemsResult
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @param $userId
     * @throws Exception
     */
    public function resetRequest ($woTaskId, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskId));

            $woRequestId = Class_db::getInstance()->db_select_single('wo_task_request', array('wo_task_id'=>$woTaskId), 'wo_task_request_id DESC');
            if ($woRequestId['wo_task_request_status'] !== '50') {
                throw new Exception('[' . __LINE__ . '] - Invalid request status');
            }
            Class_db::getInstance()->db_insert('wo_task_request', array('wo_task_id'=>$woTaskId, 'wo_task_request_order_by'=>$userId, 'wo_task_request_status'=>'32'));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
