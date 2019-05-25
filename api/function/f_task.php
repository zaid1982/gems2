<?php
/**
 * Created by PhpStorm.
 * User: Zaid
 * Date: 2/23/2019
 * Time: 1:35 PM
 */
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_task {

    private $fn_general;

    function __construct() {
        $this->fn_general = new Class_general();
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
     * @throws Exception
     */
    private function check_assign ($checkpoint, $transactionId, $assignedGroup = '', $assignedUser = '') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $checkpointId = $checkpoint['checkpoint_id'];

            $checkpointAssigns = Class_db::getInstance()->db_select('wfl_checkpoint_assign', array('checkpoint_id' => $checkpointId));
            foreach ($checkpointAssigns as $checkpointAssign) {
                $assignType = $checkpointAssign['checkpoint_assign_type'];
                $checkpointTo = $checkpointAssign['checkpoint_to'];
                $checkpointData = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('checkpoint_id' => $checkpointTo), null, 1);
                $roleId = $checkpointData['role_id'];
                $groupId = $checkpointData['group_id'];
                if ($assignType == '1') {   // Assign to himself
                    Class_db::getInstance()->db_insert('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId, 'group_id' => $groupId));
                } else if ($assignType == '2') {    // Assign to User
                    if (empty($assignedGroup)) {
                        throw new Exception('[' . __LINE__ . '] - Parameter assignedGroup empty');
                    }
                    if (empty($assignedUser)) {
                        throw new Exception('[' . __LINE__ . '] - Parameter assignedUser empty');
                    }
                    Class_db::getInstance()->db_insert('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId, 'group_id' => $assignedGroup, 'user_id' => $assignedUser));
                } else if ($assignType == '3') {    // Assign to Group
                    if (empty($assignedGroup)) {
                        throw new Exception('[' . __LINE__ . '] - Parameter assignedGroup empty');
                    }
                    Class_db::getInstance()->db_insert('wfl_task_assign', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointTo, 'role_id' => $roleId, 'group_id' => $assignedGroup));
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
     * @return string
     * @throws Exception
     */
    public function create_new_task ($flowId, $userId, $roleId, $groupId, $transactionNo) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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

            $checkpoint = Class_db::getInstance()->db_select_single('wfl_checkpoint', array('flow_id' => $flowId, 'checkpoint_type' => '1'), null, 1);
            $checkpointId = $checkpoint['checkpoint_id'];
            $checkDueDay = $checkpoint['checkpoint_due_day'];

            $this->check_next_task($checkpoint, $userId, $roleId, $groupId);

            $flowDueDay = Class_db::getInstance()->db_select_col('wfl_flow', array('flow_id' => $flowId), 'flow_due_day', null, 1);
            $transactionId = Class_db::getInstance()->db_insert('wfl_transaction', array('transaction_no' => $transactionNo, 'flow_id' => $flowId, 'user_id' => $userId, 'group_id' => $groupId,
                'transaction_date_due' => '|Curdate() + INTERVAL ' . $flowDueDay . ' DAY', 'transaction_status' => '5'));

            $checkDueDay = !empty($checkDueDay) ? '|Curdate() + INTERVAL ' . $checkDueDay . ' DAY' : '';
            $taskId = Class_db::getInstance()->db_insert('wfl_task', array('transaction_id' => $transactionId, 'checkpoint_id' => $checkpointId, 'role_id' => $roleId, 'group_id' => $groupId,
                'task_created_user' => $userId, 'task_created_group' => $groupId, 'task_date_due' => $checkDueDay, 'task_status' => '5'));

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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
                throw new Exception('[' . __LINE__ . '] - Parameter task_current not equal to 1');
            }
            if (empty($roleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter roleId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
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
            if (empty($nextRoleId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter nextRoleId empty');
            }

            if ($nextpointType == '3') {    // Last checkpoint
                Class_db::getInstance()->update('wfl_transaction', array('transaction_time_complete' => 'Now()', 'transaction_status' => '7'), array('transaction_id' => $transactionId));
                return '';
            }

            $this->check_assign($checkpoint, $transactionId, $toGroup, $toUser);

            $nextpointDueDay = !empty($nextpointDueDay) ? '|Curdate() + INTERVAL ' . $nextpointDueDay . ' DAY' : '';
            $arrInsertTask = array('transaction_id' => $transactionId, 'checkpoint_id' => $nextPointId, 'role_id' => $nextRoleId, 'task_created_user' => $userId, 'task_created_group' => $groupId,
                'task_date_due' => $nextpointDueDay, 'task_status_previous' => $status, 'task_status' => '8');
            if ($nextpointClaimType == '3') {
                $taskAssign = Class_db::getInstance()->db_select_single('wfl_task_assign', array('transaction_id' => $transactionId));
                if (empty($taskAssign) || empty($taskAssign['group_id']) || empty($taskAssign['user_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter group_id/user_id empty when assigned');
                }
                $arrInsertTask['group_id'] = $taskAssign['group_id'];
                $arrInsertTask['task_claimed_user'] = $taskAssign['user_id'];
            } else if ($nextpointClaimType == '4') {
                $taskAssign = Class_db::getInstance()->db_select_single('wfl_task_assign', array('transaction_id' => $transactionId));
                if (empty($taskAssign) || empty($taskAssign['group_id'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter group_id empty when assigned');
                }
                $arrInsertTask['group_id'] = $taskAssign['group_id'];
            } else {
                $arrInsertTask['group_id'] = $nextGroupId;
            }

            $newTaskId = Class_db::getInstance()->db_insert('wfl_task', $arrInsertTask);
            if ($checkpointType == '1') {
                Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status' => '4'), array('transaction_id' => $transactionId));
            }

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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
     * @param $checkpointId
     * @return
     * @throws Exception
     */
    public function get_checkpoint_groups ($userId, $roleId, $checkpointId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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

}