<?php
/**
 * Created by PhpStorm.
 * User: Zaid
 * Date: 2/23/2019
 * Time: 1:35 PM
 */

class Class_task {

    private $constant;
    private $fn_general;

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
     * @param $checkpoint
     * @param $userId
     * @param $roleId
     * @param $groupId
     * @throws Exception
     */
    private function check_next_task($checkpoint, $userId, $roleId, $groupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $checkpointId = $checkpoint['checkpoint_id'];
            $checkpointRole = $checkpoint['role_id'];
            $checkpointGroup = $checkpoint['group_id'];

            if (!empty($checkpointRole) && $roleId !== '' && $checkpointRole != $roleId) {
                throw new Exception('[' . __LINE__ . '] - Role ID (' . $roleId . ') is not allowed to perform this checkpoint (' . $checkpointId . ')');
            }
            if (!empty($checkpointGroup) && $groupId !== '' && $checkpointGroup != $groupId) {
                throw new Exception('[' . __LINE__ . '] - Group ID (' . $groupId . ') is not allowed to perform this checkpoint (' . $checkpointId . ')');
            }
            if (Class_db::getInstance()->db_count('wfl_checkpoint_user', array('checkpoint_id' => $checkpointId, 'user_id' => $userId, 'group_id' => $groupId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - User ID (' . $userId . ') is not allowed to perform this checkpoint (' . $checkpointId . ')');
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checkpoint
     * @param $transactionId
     * @param string $assignedGroup
     * @param string $assignedUser
     * @param string $userId
     * @throws Exception
     */
    private function check_assign ($checkpoint, $transactionId, $assignedGroup = '', $assignedUser = '', $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $checkpointId = $checkpoint['checkpoint_id'];

            $checkpointAssigns = Class_db::getInstance()->db_select('wfl_checkpoint_assign', array('checkpoint_id' => $checkpointId));
            foreach ($checkpointAssigns as $checkpointAssign) {
                $assignType = $checkpointAssign['checkpoint_assign_type'];
                $checkpointTo = $checkpointAssign['checkpoint_to'];
                $checkpointData = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('checkpoint_id' => $checkpointTo), null, 1);
                $roleId = $checkpointData['role_id'];
                $groupId = $checkpointData['group_id'];
                if ($assignType == '1') {   // Assign to himself
                    if (Class_db::getInstance()->db_count('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId)) == 0) {
                        if (empty($userId)) {
                            throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
                        }
                        if (empty($groupId)) {
                            $groupId = $assignedGroup;
                        }
                        Class_db::getInstance()->db_insert('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId, 'group_id' => $groupId, 'user_id' => $userId));
                    }
                } else if ($assignType == '2') {    // Assign to User
                    if (Class_db::getInstance()->db_count('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId)) == 0) {
                        if (empty($groupId)) {
                            if (empty($assignedGroup)) {
                                throw new Exception('[' . __LINE__ . '] - Parameter assignedGroup empty');
                            }
                            $groupId = $assignedGroup;
                        }
                        if (empty($assignedUser)) {
                            throw new Exception('[' . __LINE__ . '] - Parameter assignedUser empty');
                        }
                        Class_db::getInstance()->db_insert('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId, 'group_id' => $groupId, 'user_id' => $assignedUser));
                    }
                } else if ($assignType == '3') {    // Assign to Group
                    if (Class_db::getInstance()->db_count('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId)) == 0) {
                        if (empty($assignedGroup)) {
                            throw new Exception('[' . __LINE__ . '] - Parameter assignedGroup empty');
                        }
                        Class_db::getInstance()->db_insert('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId, 'group_id' => $assignedGroup));
                    }
                }
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $flowId
     * @param $userId
     * @param $roleId
     * @param $groupId
     * @param $transactionNo
     * @param string $dueDate
     * @param string $checkpointId
     * @return string
     * @throws Exception
     */
    public function create_new_task ($flowId, $userId, $roleId, $groupId, $transactionNo, $dueDate='', $checkpointId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($flowId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter flowId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }
            if (empty($transactionNo)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionNo empty');
            }

            if (empty($checkpointId)) {
                $checkpoint = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('flow_id'=>$flowId, 'checkpoint_type'=>'1'), null, 1);
                $checkpointId = $checkpoint['checkpoint_id'];
            } else {
                $checkpoint = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('checkpoint_id'=>$checkpointId,'flow_id'=>$flowId, 'checkpoint_type'=>'1'), null, 1);
            }

            if (empty($dueDate)) {
                $checkDueDay = $checkpoint['checkpoint_due_day'];
                $checkDueDay = !empty($checkDueDay)?'|Curdate() + INTERVAL '.$checkDueDay.' DAY':'';
            } else {
                $checkDueDay = $dueDate;
            }

            $this->check_next_task($checkpoint, $userId, $roleId, $groupId);

            $flowDueDay = Class_db::getInstance()->db_select_col('wfl_flow', array('flow_id'=>$flowId), 'flow_due_day', null, 1);
            $transactionId = Class_db::getInstance()->db_insert('wfl_transaction', array('transaction_no'=>$transactionNo, 'flow_id'=>$flowId, 'user_id'=>$userId, 'group_id'=>$groupId,
                'transaction_date_due'=>'|Curdate() + INTERVAL '.$flowDueDay.' DAY', 'transaction_status'=>'5'));

            $taskId = Class_db::getInstance()->db_insert('wfl_task', array('transaction_id'=>$transactionId, 'checkpoint_id'=>$checkpointId, 'role_id'=>$roleId, 'group_id'=>$groupId,
                'task_created_user'=>$userId, 'task_created_group'=>$groupId,'task_claimed_user'=>$userId, 'task_time_claimed'=>'Now()', 'task_date_due'=>$checkDueDay, 'task_status'=>'5'));

            //$this->check_assign($checkpoint, $transactionId, $groupId, '', $userId);

            return $taskId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $taskId
     * @param $userId
     * @param string $status
     * @param string $remark
     * @param string $next
     * @param string $groupId
     * @param string $toGroup
     * @param string $toUser
     * @return mixed
     * @throws Exception
     */
    public function submit_task ($taskId, $userId, $status = '9', $remark = '', $next = '', $groupId = '', $toGroup = '', $toUser = '') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($taskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter taskId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $task = Class_db::getInstance()->db_select_single('wfl_task', array('task_id' => $taskId), null, 1);
            $taskId = $task['task_id'];
            $transactionId = $task['transaction_id'];
            $checkpointId = $task['checkpoint_id'];
            $roleId = $task['role_id'];
            $groupId = empty($task['group_id']) ? $groupId : $task['group_id'];
            $taskClaimedUser = $task['task_claimed_user'];

            if ($task['task_current'] != '1') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_TASK_ALREADY_SUBMITTED, 31);
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }
            if (!empty($taskClaimedUser) && $taskClaimedUser != $userId) {
                throw new Exception('[' . __LINE__ . '] - User claimed not same');
            }

            $checkpoint = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('checkpoint_id' => $checkpointId), null, 1);
            $checkpointType = $checkpoint['checkpoint_type'];
            $checkpointClaimType = $checkpoint['checkpoint_claim_type'];
            $checkFlowId = $checkpoint['flow_id'];
            $this->check_next_task($checkpoint, $userId, $roleId, $groupId);

            $arrUpdTask = array('task_current' => '2', 'role_id' => $roleId, 'group_id' => $groupId, 'task_remark' => $remark, 'task_time_submit' => 'Now()', 'task_status' => $status);
            if ($checkpointClaimType == '2') {
                if (empty($taskClaimedUser)) {
                    throw new Exception('[' . __LINE__ . '] - Task supposed to be claimed first');
                }
            } else {
                $arrUpdTask['task_time_claimed'] = 'Now()';
                $arrUpdTask['task_claimed_user'] = $userId;
            }
            Class_db::getInstance()->db_update('wfl_task', $arrUpdTask, array('task_id' => $taskId));

            if (empty($next)) {
                $nextPointId = Class_db::getInstance()->db_select_col('wfl_checkpoint', array('checkpoint_id' => $checkpointId), 'checkpoint_next', null, 1);
            } else if ($next === '1' || $next === '2' || $next === '3') {
                $nextPointId = Class_db::getInstance()->db_select_col('wfl_checkpoint', array('checkpoint_id' => $checkpointId), 'checkpoint_case_' . $next, null, 1);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter next invalid (' . $next . ')');
            }

            $nextpoint = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('checkpoint_id' => $nextPointId), null, 1);
            $nextFlowId = $nextpoint['flow_id'];
            $nextpointType = $nextpoint['checkpoint_type'];
            $nextpointClaimType = $nextpoint['checkpoint_claim_type'];
            $nextpointDueDay = $nextpoint['checkpoint_due_day'];
            $nextRoleId = $nextpoint['role_id'];
            $nextGroupId = $nextpoint['group_id'];

            if ($nextFlowId !== $checkFlowId) {
                throw new Exception('[' . __LINE__ . '] - Parameter nextFlowId invalid (' . $nextFlowId . ')');
            }

            if ($nextpointType == '3') {    // Last checkpoint
                $transaction = Class_db::getInstance()->db_select_single('wfl_transaction', array('transaction_id' => $transactionId), null, 1);
                Class_db::getInstance()->db_update('wfl_transaction', array('transaction_time_complete' => 'Now()'), array('transaction_id' => $transactionId)); // , 'transaction_status' => '7'
                $arrInsertTask = array('transaction_id' => $transactionId, 'checkpoint_id' => $nextPointId, 'task_created_user' => $userId, 'task_created_group' => $groupId, 'task_status_previous' => $status, 'task_status' => '7',
                    'role_id'=>$nextRoleId, 'group_id'=>$transaction['group_id'], 'task_claimed_user'=>$transaction['user_id']);
                $newTaskId = Class_db::getInstance()->db_insert('wfl_task', $arrInsertTask);
                return $newTaskId;
            }
            if (empty($nextRoleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter nextRoleId empty');
            }

            $this->check_assign($checkpoint, $transactionId, $toGroup, $toUser, $userId);
            $nextpointDueDay = !empty($nextpointDueDay) ? '|Curdate() + INTERVAL ' . $nextpointDueDay . ' DAY' : '';
            $arrInsertTask = array('transaction_id' => $transactionId, 'checkpoint_id' => $nextPointId, 'role_id' => $nextRoleId, 'task_created_user' => $userId, 'task_created_group' => $groupId,
                'task_date_due' => $nextpointDueDay, 'task_status_previous' => $status, 'task_status' => '8');
            if ($nextpointClaimType == '3') {
                $taskAssign = Class_db::getInstance()->db_select_single('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $nextPointId, 'role_id' => $nextRoleId));
                if (empty($taskAssign)) {
                    throw new Exception('[' . __LINE__ . '] - Data taskAssign empty when assigned to user');
                } else if (empty($taskAssign['group_id']) || empty($taskAssign['user_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter group_id or user_id empty when assigned to user');
                } else {
                    $arrInsertTask['group_id'] = $taskAssign['group_id'];
                    $arrInsertTask['task_claimed_user'] = $taskAssign['user_id'];
                }
            } else if ($nextpointClaimType == '4') {
                $taskAssign = Class_db::getInstance()->db_select_single('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id'=>$nextPointId, 'role_id'=>$nextRoleId));
                if (empty($taskAssign) || empty($taskAssign['group_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter group_id empty when assigned');
                }
                $arrInsertTask['group_id'] = $taskAssign['group_id'];
            } else {
                $arrInsertTask['group_id'] = $nextGroupId;
            }

            $newTaskId = Class_db::getInstance()->db_insert('wfl_task', $arrInsertTask);
            //if ($checkpointType == '1') {
                //Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status' => '4'), array('transaction_id' => $transactionId));
            //}

            return $newTaskId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $roleId
     * @param $groupId
     * @throws Exception
     */
    public function delete_user_role ($userId, $roleId, $groupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            if (Class_db::getInstance()->db_count('sys_user_role', array('role_id' => $roleId, 'group_id' => $groupId, 'user_id' => '<>' . $userId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - ' . $constant::ERR_ROLE_DELETE_ALONE, 31);
            }
            if (Class_db::getInstance()->db_count('vw_check_assigned', array('wfl_task_assign.role_id' => $roleId, 'wfl_task_assign.group_id' => $groupId, 'wfl_task_assign.user_id' => $userId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - ' . $constant::ERR_ROLE_DELETE_HAVE_TASK, 31);
            }

            Class_db::getInstance()->db_delete('sys_user_role', array('group_id' => $groupId, 'user_id' => $userId, 'role_id' => $roleId));
            Class_db::getInstance()->db_delete('wfl_checkpoint_user', array('group_id' => $groupId, 'user_id' => $userId, 'role_id' => $roleId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $roleId
     * @param $groupId
     * @throws Exception
     */
    public function add_user_role ($userId, $roleId, $groupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            if (Class_db::getInstance()->db_count('sys_user_group', array('user_id' => $userId, 'group_id' => $groupId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - User not exist in group', 31);
            }

            Class_db::getInstance()->db_insert('sys_user_role', array('group_id' => $groupId, 'user_id' => $userId, 'role_id' => $roleId));
            $checkpointIds = Class_db::getInstance()->db_select_colm('wfl_checkpoint', array('role_id' => $roleId, 'w1' => ('(group_id = ' . $groupId . ' OR group_id IS NULL)')), 'checkpoint_id');
            foreach ($checkpointIds as $checkpointId) {
                Class_db::getInstance()->db_insert('wfl_checkpoint_user', array('checkpoint_id' => $checkpointId, 'group_id' => $groupId, 'user_id' => $userId, 'role_id' => $roleId));
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $roleId
     * @return array
     * @throws Exception
     */
    public function get_group_id_from_user ($userId, $roleId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }

            return Class_db::getInstance()->db_select_col('sys_user_role', array('role_id'=>$roleId, 'user_id'=>$userId), 'group_id', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $roleId
     * @param $checkpointId
     * @return array
     * @throws Exception
     */
    public function get_checkpoint_groups ($userId, $roleId, $checkpointId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($checkpointId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpointId empty');
            }

            $groupIds = Class_db::getInstance()->db_select_colm('wfl_checkpoint_user', array('user_id'=>$userId, 'role_id'=>$roleId, 'checkpoint_id'=>$checkpointId), 'group_id');
            return array_unique($groupIds);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $roleId
     * @param $checkpointId
     * @param $woTaskId
     * @return
     * @throws Exception
     */
    public function get_checkpoints_users ($roleId, $checkpointId, $woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($checkpointId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpointId empty');
            }

            if ($checkpointId === '12') {
                if (empty($woTaskId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
                }
                $siteId = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$woTaskId), 'site_id', null, 1);
                return Class_db::getInstance()->db_select_colm('mw_checkpoint_user_with_site', array('role_id'=>$roleId, 'checkpoint_id'=>$checkpointId, 'site_id'=>$siteId), 'user_id');
            }
            return Class_db::getInstance()->db_select_colm('wfl_checkpoint_user', array('role_id'=>$roleId, 'checkpoint_id'=>$checkpointId), 'user_id');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $siteCode
     * @param string $flowId
     * @param string $year
     * @return array
     * @throws Exception
     */
    public function get_track_monitoring_list ($siteCode='', $flowId='', $year='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $result = array();
            $arrWhere = array('task_current'=>'1');
            if (!empty($siteCode)) {
                $arrWhere['transaction_no'] = '%'.$siteCode.'%';
            }
            if (!empty($flowId)) {
                $arrWhere['flow_id'] = $flowId;
            }
            if (!empty($year)) {
                $arrWhere['YEAR(task_time_created)'] = $year;
            }

            $arr_dataLocal = Class_db::getInstance()->db_select('vw_track_monitoring', $arrWhere);
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['transactionId'] = $dataLocal['transaction_id'];
                $row_result['transactionNo'] = $dataLocal['transaction_no'];
                $row_result['transGroup'] = $dataLocal['trans_group'];
                $row_result['transUser'] = $dataLocal['trans_user'];
                $row_result['transactionTimeCreated'] = str_replace('-', '/', $dataLocal['transaction_time_created']);
                $row_result['transactionTimeComplete'] = str_replace('-', '/', $dataLocal['transaction_time_complete']);
                $row_result['transactionDateDue'] = str_replace('-', '/', $dataLocal['transaction_date_due']);
                $row_result['taskId'] = $dataLocal['task_id'];
                $row_result['flowId'] = $dataLocal['flow_id'];
                $row_result['roleId'] = $this->fn_general->clear_null($dataLocal['role_id']);
                $row_result['checkpointId'] = $dataLocal['checkpoint_id'];
                $row_result['userId'] = $this->fn_general->clear_null($dataLocal['task_claimed_user']);
                $row_result['taskTimeCreated'] = str_replace('-', '/', $dataLocal['task_time_created']);
                $row_result['taskTimeSubmit'] = str_replace('-', '/', $dataLocal['task_time_submit']);
                $row_result['taskDateDue'] = str_replace('-', '/', $dataLocal['task_date_due']);
                $row_result['taskStatus'] = $dataLocal['task_status'];
                $row_result['transactionStatus'] = $dataLocal['transaction_status'];
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $taskId
     * @param $transactionId
     * @return array
     * @throws Exception
     */
    public function get_task_history ($taskId, $transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($taskId) && empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter taskId and transactionId empty');
            }

            $arrWhere = array();
            if (!empty($taskId)) {
                $arrWhere['task_id'] = $taskId;
            }
            if (!empty($transactionId)) {
                $arrWhere['transaction_id'] = $transactionId;
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('wfl_task', $arrWhere);
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['transactionId'] = $dataLocal['transaction_id'];
                $row_result['taskId'] = $dataLocal['task_id'];
                $row_result['checkpointId'] = $dataLocal['checkpoint_id'];
                $row_result['roleId'] = $this->fn_general->clear_null($dataLocal['role_id']);
                $row_result['groupId'] = $this->fn_general->clear_null($dataLocal['group_id']);
                $row_result['taskCurrent'] = $dataLocal['task_current'];
                $row_result['taskCreatedUser'] = $this->fn_general->clear_null($dataLocal['task_created_user']);
                $row_result['taskCreatedGroup'] = $this->fn_general->clear_null($dataLocal['task_created_group']);
                $row_result['taskClaimedUser'] = $this->fn_general->clear_null($dataLocal['task_claimed_user']);
                $row_result['taskRemark'] = $this->fn_general->clear_null($dataLocal['task_remark']);
                $row_result['taskDateDue'] = str_replace('-', '/', $dataLocal['task_date_due']);
                $row_result['taskTimeCreated'] = str_replace('-', '/', $dataLocal['task_time_created']);
                $row_result['taskTimeSubmit'] = str_replace('-', '/', $dataLocal['task_time_submit']);
                $row_result['taskStatusSave'] = $this->fn_general->clear_null($dataLocal['task_status_save']);
                $row_result['taskStatusPrevious'] = $this->fn_general->clear_null($dataLocal['task_status_previous']);
                $row_result['taskStatus'] = $dataLocal['task_status'];
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $flowId
     * @param string $assetNo
     * @param string $searchTxt
     * @return array
     * @throws Exception
     */
    public function get_track_monitoring_list_m ($userId, $flowId, $assetNo='', $searchTxt='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($flowId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter flowId empty');
            }

            $result = array();
            $arrWhere = array();
            $roles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$userId), 'role_id');

            if (empty($searchTxt)) {
                $arrStatus = $this->fn_general->getRefStatus();
                $arrUserFullName = $this->fn_general->getUserFullName();
                $arrFlowName = $this->fn_general->getFlowName();
                $arrCheckPointName = $this->fn_general->getCheckPointName();
                $arrWoTaskType = array('', 'Client Complaint', 'Self Finding');
                $arrWoTaskSeverity = array('', 'Non-Critical', 'Critical');
            }

            if ($flowId == '1') {
                if (in_array('1', $roles) || in_array('10', $roles)) {
                    //
                }
                else if (in_array('2', $roles) || in_array('6', $roles)) {
                    $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id' => $userId), 'site_id');
                    if (empty($siteId)) {
                        return array();
                    }
                    $contractId = Class_db::getInstance()->db_select_col('cli_contract', array('site_id' => $siteId, 'contract_status'=>'1'), 'contract_id');
                    $arrWhere['contract_id'] = $contractId;
                } else if (in_array('3', $roles) || in_array('4', $roles) || in_array('5', $roles)) {
                    $ppmGroupFinalArr = array();
                    $ppmGroups = Class_db::getInstance()->db_select('mw_ppm_group_user', array('ppm_group_user.user_id' => $userId));
                    foreach ($ppmGroups as $ppmGroup) {
                        $ppmGroupId = $ppmGroup['ppm_group_id'];
                        if ($ppmGroup['role_id'] == '4') {
                            $ppmGroupFsArr = Class_db::getInstance()->db_select_colm('ppm_group', array('ppm_group_report_to'=>$ppmGroupId, 'role_id'=>'3'), 'ppm_group_id');
                            if (!empty($ppmGroupFsArr)) {
                                $ppmGroupTechArr = Class_db::getInstance()->db_select_colm('ppm_group', array('ppm_group_report_to'=>'('.implode(',', $ppmGroupFsArr).')', 'role_id' => '5'), 'ppm_group_id');
                                $ppmGroupFinalArr = array_unique(array_merge($ppmGroupTechArr, $ppmGroupFinalArr));
                            }
                        } else if ($ppmGroup['role_id'] == '3') {
                            $ppmGroupTechArr = Class_db::getInstance()->db_select_colm('ppm_group', array('ppm_group_report_to' => $ppmGroupId, 'role_id' => '5'), 'ppm_group_id');
                            $ppmGroupFinalArr = array_unique(array_merge($ppmGroupTechArr, $ppmGroupFinalArr));
                        } else if ($ppmGroup['role_id'] == '5') {
                            array_push($ppmGroupFinalArr, $ppmGroupId);
                            $ppmGroupFinalArr = array_unique($ppmGroupFinalArr);
                        }
                    }
                    if (empty($ppmGroupFinalArr)) {
                        return array();
                    }

                    $ppmUserArr = Class_db::getInstance()->db_select_colm('ppm_group_user', array('ppm_group_id'=>'('.implode(',', $ppmGroupFinalArr).')'), 'user_id');
                    if (empty($ppmUserArr)) {
                        return array();
                    }
                    $ppmUserArr = array_unique($ppmUserArr);
                    $arrWhere['wfl_transaction.user_id'] = '('.implode(',', $ppmUserArr).')';
                } else {
                    return array();
                }

                if (!empty($assetNo)) {
                    $arrWhere['asset_no'] = $assetNo;
                }

                if (!empty($searchTxt)) {
                    $arrWhere['w1'] = "(transaction_no LIKE '%".$searchTxt."%' OR asset_no LIKE '%".$searchTxt."%'  OR status_desc LIKE '%".$searchTxt."%' OR sys_user.user_first_name LIKE '%".$searchTxt."%' OR checked_by.user_first_name LIKE '%".$searchTxt."%' OR verified_by.user_first_name LIKE '%".$searchTxt."%'  
                    OR flow_desc LIKE '%".$searchTxt."%' OR checkpoint_desc LIKE '%".$searchTxt."%')";
                }

                $arrWhere['task_current'] = '1';
                $arrWhere['wfl_transaction.flow_id'] = $flowId;
                $arrWhere['wfl_task.checkpoint_id'] = '<> 1';
                $sqlText = $searchTxt === '' ? 'vw_track_monitoring_ppm_m' : 'vw_track_monitoring_ppm_search_m';
                $arr_dataLocal = Class_db::getInstance()->db_select($sqlText, $arrWhere, 'task_id DESC', '200');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['transactionId'] = $dataLocal['transaction_id'];
                    $row_result['transactionNo'] = $dataLocal['transaction_no'];
                    $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                    //if ($dataLocal['checkpoint_id'] == '1') {
                        $row_result['transactionTimeCreated'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_start_date']);
                    //} else {
                    //    $row_result['transactionTimeCreated'] = $this->fn_general->convertDateToDisplay($dataLocal['task_time_created']);
                    //}
                    $row_result['flowId'] = $dataLocal['flow_id'];
                    $row_result['flowName'] = isset($arrFlowName) ? $arrFlowName[intval($dataLocal['flow_id'])] : $dataLocal['flow_desc'];
                    $row_result['checkpointName'] = isset($arrCheckPointName) ? $arrCheckPointName[intval($dataLocal['checkpoint_id'])] : $dataLocal['checkpoint_desc'];
                    $row_result['userFullName'] = isset($arrUserFullName) ? $arrUserFullName[intval($this->fn_general->clear_null($dataLocal['task_claimed_user']))] : $dataLocal['user_first_name'];
                    $row_result['transactionStatus'] = isset($arrStatus) ? $arrStatus[intval($dataLocal['transaction_status'])] : $dataLocal['status_desc'];
                    array_push($result, $row_result);
                    //if ($searchTxt === '' || strpos($row_result['transactionNo'], $searchTxt) !== false || strpos($row_result['assetNo'], $searchTxt) !== false || strpos($row_result['transactionStatus'], $searchTxt) !== false
                    //    || strpos($row_result['userFullName'], $searchTxt) !== false || strpos($row_result['flowName'], $searchTxt) !== false || strpos($row_result['checkpointName'], $searchTxt) !== false) {
                    //    array_push($result, $row_result);
                    //}
                }
            } else if ($flowId == '2') {
                if (in_array('1', $roles) || in_array('10', $roles)) {
                    //
                } else {
                    $arrWhere['site_id'] = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);
                }

                if (!empty($assetNo)) {
                    return array();
                }

                if (!empty($searchTxt)) {
                    $arrWhere['w1'] = "(transaction_no LIKE '%".$searchTxt."%' OR status_desc LIKE '%".$searchTxt."%' OR user_first_name LIKE '%".$searchTxt."%' OR flow_desc LIKE '%".$searchTxt."%' OR checkpoint_desc LIKE '%".$searchTxt."%' OR assigned_name LIKE '%".$searchTxt."%' OR wo_task_type LIKE '%".$searchTxt."%' OR wo_task_severity LIKE '%".$searchTxt."%')";
                }

                $arrWhere['task_current'] = '1';
                $arrWhere['flow_id'] = $flowId;
                $sqlText = $searchTxt === '' ? 'vw_track_monitoring_wo_m' : 'vg_track_monitoring_wo_search_m';
                $arr_dataLocal = Class_db::getInstance()->db_select($sqlText, $arrWhere, 'task_id DESC', '200');
                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['transactionId'] = $dataLocal['transaction_id'];
                    $row_result['transactionNo'] = $dataLocal['transaction_no'];
                    $row_result['transactionTimeCreated'] = $this->fn_general->convertDateToDisplay($dataLocal['task_time_created']);
                    $row_result['flowId'] = $dataLocal['flow_id'];
                    $row_result['flowName'] = isset($arrFlowName) ? $arrFlowName[intval($dataLocal['flow_id'])] : $dataLocal['flow_desc'];
                    $row_result['checkpointName'] = isset($arrCheckPointName) ? $arrCheckPointName[intval($dataLocal['checkpoint_id'])] : $dataLocal['checkpoint_desc'];
                    $row_result['currentTaskOwner'] = isset($arrUserFullName) ? $arrUserFullName[intval($this->fn_general->clear_null($dataLocal['wo_task_created_by']))] : $this->fn_general->clear_null($dataLocal['user_first_name']);
                    $row_result['woTaskType'] = isset($arrWoTaskType) ? $arrWoTaskType[intval($this->fn_general->clear_null($dataLocal['wo_task_type']))] : $dataLocal['wo_task_type'];
                    $row_result['siteId'] = $dataLocal['site_id'];
                    $row_result['woTaskSeverity'] = isset($arrWoTaskSeverity) ? $arrWoTaskSeverity[intval($this->fn_general->clear_null($dataLocal['wo_task_severity']))] : $dataLocal['wo_task_severity'];
                    $row_result['assignedTo'] = isset($arrUserFullName) ? $arrUserFullName[intval($this->fn_general->clear_null($dataLocal['wo_task_assigned_to']))] : $this->fn_general->clear_null($dataLocal['assigned_name']);
                    $row_result['transactionStatus'] = isset($arrStatus) ? $arrStatus[intval($dataLocal['transaction_status'])] : $dataLocal['status_desc'];
                    array_push($result, $row_result);
                    //if ($searchTxt === '' || strpos($row_result['transactionNo'], $searchTxt) !== false || strpos($row_result['transactionStatus'], $searchTxt) !== false
                    //    || strpos($row_result['currentTaskOwner'], $searchTxt) !== false || strpos($row_result['flowName'], $searchTxt) !== false || strpos($row_result['checkpointName'], $searchTxt) !== false
                    //    || strpos($row_result['woTaskType'], $searchTxt) !== false || strpos($row_result['assignedTo'], $searchTxt) !== false || strpos($row_result['woTaskSeverity'], $searchTxt) !== false) {
                    //    array_push($result, $row_result);
                    //}
                }
            } else {
                return array();
            }



            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $transactionId
     * @return mixed
     * @throws Exception
     */
    public function get_track_monitoring_details_m ($transactionId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $transaction = Class_db::getInstance()->db_select_single('wfl_transaction', array('transaction_id'=>$transactionId), null, 1);
            $currentTask = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id'=>$transactionId, 'task_current'=>'1'), null, 1);

            $arrStatus = $this->fn_general->getRefStatus();
            $arrRoles = $this->fn_general->getRefRole();
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrGroupName = $this->fn_general->getGroupName();
            $arrFlowName = $this->fn_general->getFlowName();
            $arrCheckPointName = $this->fn_general->getCheckPointName();

            $result['flowName'] = $arrFlowName[intval($transaction['flow_id'])];
            $result['transactionNo'] = $transaction['transaction_no'];
            $result['initiateBy'] = $arrUserFullName[intval($transaction['user_id'])];
            $result['initiateByGroup'] = $arrGroupName[intval($transaction['group_id'])];
            $result['initiateTimeCreated'] = $this->fn_general->convertDateToDisplay($transaction['transaction_time_created']);
            $result['taskStatus'] = $arrStatus[intval($currentTask['task_status'])];
            $result['currentUser'] = !empty($currentTask['task_claimed_user'])?$arrUserFullName[intval($currentTask['task_claimed_user'])]:'';
            $result['receivedTime'] = $this->fn_general->convertDateToDisplay($currentTask['task_time_created']);
            $result['flowStatus'] = $arrStatus[intval($transaction['transaction_status'])];
            $result['flowDueDate'] = $this->fn_general->convertDateToDisplay($transaction['transaction_date_due']);
            $result['checkpointId'] = $currentTask['checkpoint_id'];

            if ($transaction['flow_id'] == '1') {
                $ppmTaskId = Class_db::getInstance()->db_select_col('ppm_task', array('transaction_id'=>$transactionId), 'ppm_task_id', null, 1);
                $ppmId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'ppm_id', null, 1);
                $contractId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmId), 'contract_id', null, 1);
                $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId, 'contract_status'=>'1'), 'site_id', null, 1);
                $siteName = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_name', null, 1);
                $result['ppmTaskId'] = $ppmTaskId;
                $result['siteName'] = $siteName;
            } else if ($transaction['flow_id'] == '2') {
                $woTask = Class_db::getInstance()->db_select_single('wo_task', array('transaction_id'=>$transactionId), null, 1);
                $siteName = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$woTask['site_id']), 'site_name', null, 1);
                $result['woTaskId'] = $woTask['wo_task_id'];
                $result['siteName'] = $siteName;
            }

            $resultHistory = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('wfl_task', array('transaction_id'=>$transactionId), 'task_id');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['checkpointId'] = $arrCheckPointName[intval($dataLocal['checkpoint_id'])];
                $row_result['roleId'] = $arrRoles[intval($dataLocal['role_id'])];
                //$row_result['groupId'] = $arrGroupName[intval($dataLocal['group_id'])];
                $row_result['taskClaimedUser'] = $arrUserFullName[intval($dataLocal['task_claimed_user'])];
                $row_result['taskRemark'] = $this->fn_general->clear_null($dataLocal['task_remark']);
                $row_result['taskDateDue'] = $this->fn_general->convertDateToDisplay($dataLocal['task_date_due']);
                $row_result['taskTimeCreated'] = $this->fn_general->convertDateToDisplay($dataLocal['task_time_created']);
                $row_result['taskTimeSubmit'] = $this->fn_general->convertDateToDisplay($dataLocal['task_time_submit']);
                $row_result['taskStatus'] = $arrStatus[intval($dataLocal['task_status'])];
                array_push($resultHistory, $row_result);
            }
            $result['taskHistory'] = $resultHistory;

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

}