
<?php

class Task extends General {

    public $taskId = 0;
    public $transactionId = 0;
    public $transactionNo = 0;
    public $wflTask = array();
    public $wflTaskNew = array();

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $taskId
     * @throws Exception
     */
    public function set (int $taskId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($taskId, 'taskId');
            $this->taskId = $taskId;
            $this->wflTask = DbMysql::select('wfl_task', array('taskId'=>$this->taskId),true);
            $this->transactionId = $this->wflTask['transactionId'];
            $this->transactionNo = DbMysql::selectColumn('wfl_transaction', array('transactionId'=>$this->transactionId), 'transactionNo', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $checkpointId
     * @throws Exception
     */
    public function checkValidity (int $checkpointId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($checkpointId, 'checkpointId');
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->taskId, 'taskId');
            parent::checkEmptyArray($this->wflTask, 'wflTask');
            if ($this->wflTask['taskCurrent'] !== 1) {
                throw new Exception(Constant::$taskErr['alreadySubmitted'], 31);
            }
            if ($this->wflTask['checkpointId'] !== $checkpointId) {
                throw new Exception(Constant::$err['default'], 31);
            }
            if ($this->wflTask['taskClaimedUser'] !== null && $this->wflTask['taskClaimedUser'] !== $this->userId) {
                throw new Exception(Constant::$taskErr['claimed'], 31);
            }
            if (DbMysql::count('sys_user_role', array('userId'=>$this->userId, 'roleId'=>$this->wflTask['roleId'], 'groupId'=>$this->wflTask['groupId'])) === 0) {
                $roleName = DbMysql::selectColumn('ref_role', array('roleId'=>$this->wflTask['roleId']), 'roleDesc', true);
                throw new Exception(str_replace('__', $roleName, Constant::$taskErr['invalidRole']), 31);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $flowId
     * @param string $transactionNo
     * @param int $checkpointStart
     * @return void
     * @throws Exception
     */
    public function createNew (int $flowId, string $transactionNo, int $checkpointStart=0): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($flowId, 'flowId');
            parent::checkEmptyString($transactionNo, 'transactionNo');

            $whereArr = array('flowId'=>$flowId, 'checkpointType'=>'1');
            if (!empty($checkpointStart)) {
                $whereArr['checkpointId'] = $checkpointStart;
            }
            $checkpoint = DbMysql::select('wfl_checkpoint', $whereArr, true);
            $groupId = empty($checkpoint['groupId']) ? DbMysql::selectColumn('sys_user_group', array('userId'=>$this->userId), 'groupId', true, 'userGroupId DESC') : $checkpoint['groupId'];
            if (DbMysql::count('sys_user_role', array('userId'=>$this->userId, 'roleId'=>$checkpoint['roleId'], 'groupId'=>$groupId)) === 0) {
                $roleName = DbMysql::selectColumn('ref_role', array('roleId'=>$checkpoint['roleId']), 'roleDesc', true);
                throw new Exception(str_replace('__', $roleName, Constant::$taskErr['invalidRole']), 31);
            }
            $whereTransactionDueDay = '|CURDATE() + INTERVAL '.DbMysql::selectColumn('wfl_flow', array('flowId'=>$flowId), 'flowDueDay', true).' DAY';
            $whereTaskDueDay = !empty($checkpoint['checkpointDueDay']) ? '|CURDATE() + INTERVAL '.$checkpoint['checkpointDueDay'].' DAY' : '';

            $transactionId = DbMysql::insert('wfl_transaction', array('transactionNo'=>$transactionNo, 'flowId'=>$flowId, 'userId'=>$this->userId, 'groupId'=>$groupId,
                'transactionDateDue'=>$whereTransactionDueDay, 'transactionStatus'=>'5'));
            $taskId = DbMysql::insert('wfl_task', array('transactionId'=>$transactionId, 'checkpointId'=>$checkpoint['checkpointId'], 'roleId'=>$checkpoint['roleId'], 'groupId'=>$groupId,
                'taskCreatedUser'=>$this->userId, 'taskCreatedGroup'=>$groupId,'taskClaimedUser'=>$this->userId, 'taskTimeClaimed'=>'NOW()', 'taskDateDue'=>$whereTaskDueDay, 'taskStatus'=>5));
            $this->wflTaskNew = DbMysql::select('wfl_task', array('taskId'=>$taskId), true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSiteRecipients (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyArray($this->wflTaskNew, 'wflTaskNew');
            if (!empty($this->wflTaskNew['taskClaimedUser'])) {
                throw new Exception('TaskClaimedUser should be empty');
            }
            $siteId = DbMysql::selectColumn('sys_user', array('userId'=>$this->userId), 'siteId', true);
            $checkpointUsers = DbMysql::select('v_checkpoint_user_site', array('siteId'=>$siteId, 'checkpointId'=>$this->wflTaskNew['checkpointId'], 'roleId'=>$this->wflTaskNew['roleId']));
            return array_column($checkpointUsers, 'userId');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $remark
     * @param int $status
     * @param int $statusNew
     * @param int $next
     * @param int $toGroup
     * @param int $toUser
     * @return void
     * @throws Exception
     */
    public function submit (string $remark, int $status=9, int $statusNew=8, int $next=0, int $toGroup=0, int $toUser=0): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->taskId, 'taskId');
            parent::checkEmptyInteger($this->transactionId, 'transactionId');
            parent::checkEmptyArray($this->wflTask, 'wflTask');
            if ($next > 3) {
                throw new Exception('Invalid checkpoint $next value = '.$next);
            }

            // ********* update current task ********* \\
            $wflCheckpoint = DbMysql::select('wfl_checkpoint', array('checkpoint_id'=>$this->wflTask['checkpointId']));
            $updateArr = array('taskCurrent'=>2, 'taskRemark'=>$remark, 'taskTimeSubmit'=>'NOW()', 'taskStatus'=>$status);
            if ($wflCheckpoint['checkpointClaimType'] === 2 && empty($this->wflTask['taskClaimedUser'])) {
                throw new Exception(Constant::$taskErr['notClaimed'], 31);
            } else {
                $updateArr['taskTimeClaimed'] = 'NOW()';
                $updateArr['taskClaimedUser'] = $this->userId;
            }
            DbMysql::update('wfl_task', $updateArr, array('task_id'=>$this->taskId));

            $checkpointIdNext = empty($next) ? $wflCheckpoint['checkpointNext'] : $wflCheckpoint['checkpointCase'.$next];
            $wflCheckpointNext = DbMysql::select('wfl_checkpoint', array('checkpointId'=>$checkpointIdNext), true);

            // ********* process last checkpoint ********* \\
            if ($wflCheckpointNext['checkpointType'] === 3) {
                $wflTransaction = DbMysql::select('wfl_transaction', array('transactionId'=>$this->transactionId));
                $statusFinish = $statusNew === 8 ? 7 : $statusNew;
                $groupId = empty($wflCheckpointNext['groupId']) ? $wflCheckpointNext['groupId'] : $wflTransaction['groupId'];
                DbMysql::update('wfl_transaction', array('transactionTimeComplete'=>'NOW()', 'transactionStatus'=>$statusFinish), array('transactionId'=>$this->transactionId));
                DbMysql::insert('wfl_task', array('transactionId'=>$this->transactionId, 'checkpointId'=>$checkpointIdNext, 'taskCreatedUser'=>$this->userId, 'taskCreatedGroup'=>$this->wflTask['groupId'],
                    'taskStatusPrevious'=>$this->wflTask['taskStatus'], 'taskStatus'=>$statusFinish, 'roleId'=>$wflCheckpointNext['roleId'], 'groupId'=>$groupId, 'taskClaimedUser'=>$wflTransaction['userId']));
                return;
            }

            // ********* process task assign to others ********* \\
            $wflCheckpointAssignList = DbMysql::selectAll('wfl_checkpoint_assign', array('checkpointId'=>$this->wflTask['checkpointId']));
            foreach ($wflCheckpointAssignList as $wflCheckpointAssign) {
                $wflCheckpointTo = DbMysql::select('wfl_checkpoint', array('checkpointId'=>$wflCheckpointAssign['checkpointTo']), true);
                if ($wflCheckpointAssign['checkpointAssignType'] === 1) {
                    $groupTo = empty($wflCheckpointTo['groupId']) ? $toGroup : $wflCheckpointTo['groupId'];
                    parent::checkEmptyInteger($groupTo, 'groupTo');
                    DbMysql::insert('wfl_task_assign', array('transactionId'=>$this->transactionId, 'checkpointId'=>$wflCheckpointAssign['checkpointTo'], 'roleId'=>$wflCheckpointTo['roleId'], 'groupId'=>$groupTo, 'userId'=>$this->userId));
                } else if ($wflCheckpointAssign['checkpointAssignType'] === 2) {
                    $groupTo = empty($wflCheckpointTo['groupId']) ? $toGroup : $wflCheckpointTo['groupId'];
                    parent::checkEmptyInteger($groupTo, 'groupTo');
                    parent::checkEmptyInteger($toUser, 'toUser');
                    DbMysql::insert('wfl_task_assign', array('transactionId'=>$this->transactionId, 'checkpointId'=>$wflCheckpointAssign['checkpointTo'], 'roleId'=>$wflCheckpointTo['roleId'], 'groupId'=>$groupTo, 'userId'=>$toUser));
                } else if ($wflCheckpointAssign['checkpointAssignType'] === 3) {
                    parent::checkEmptyInteger($toGroup, 'toGroup');
                    DbMysql::insert('wfl_task_assign', array('transactionId'=>$this->transactionId, 'checkpointId'=>$wflCheckpointAssign['checkpointTo'], 'roleId'=>$wflCheckpointTo['roleId'], 'groupId'=>$toGroup));
                }
            }

            // ********* process task assigned ********* \\
            $groupId = empty($wflCheckpointNext['groupId']) ? $wflCheckpointNext['groupId'] : 0;
            $claimedUser = 0;
            $wflTaskAssign = DbMysql::select('wfl_task_assign', array('transactionId'=>$this->transactionId, 'checkpointId'=>$checkpointIdNext, 'roleId'=>$wflCheckpointNext['roleId']), false, 'taskAssignId', 'DESC');
            if ($wflCheckpointNext['checkpointClaimType'] === 3) {
                parent::checkEmptyArray($wflTaskAssign, 'wflTaskAssign when set to assigned user');
                parent::checkEmptyInteger($wflTaskAssign['userId'], 'userId when set to assigned user');
                $groupId = $wflTaskAssign['groupId'];
                $claimedUser = $wflTaskAssign['userId'];
            } else if ($wflCheckpointNext['checkpointClaimType'] === 4) {
                parent::checkEmptyArray($wflTaskAssign, 'wflTaskAssign when set to assigned group');
                parent::checkEmptyInteger($wflTaskAssign['groupId'], 'userId when set to assigned group');
                $groupId = $wflTaskAssign['groupId'];
            } else if (!empty($wflTaskAssign) && !empty($wflTaskAssign['userId'])) {
                $groupId = $wflTaskAssign['groupId'];
                $claimedUser = $wflTaskAssign['userId'];
            } else if (!empty($wflTaskAssign) && !empty($wflTaskAssign['groupId'])) {
                $groupId = $wflTaskAssign['groupId'];
            }
            parent::checkEmptyInteger($groupId, 'groupId');

            // ********* insert new task ********* \\
            $whereTaskDueDay = !empty($wflCheckpointNext['checkpointDueDay']) ? '|CURDATE() + INTERVAL '.$wflCheckpointNext['checkpointDueDay'].' DAY' : '';
            $taskIdNew = DbMysql::insert('wfl_task', array('transactionId'=>$this->transactionId, 'checkpointId'=>$checkpointIdNext, 'roleId'=>$wflCheckpointNext['roleId'], 'groupId'=>$groupId, 'taskCreatedUser'=>$this->userId,
                'taskCreatedGroup'=>$this->wflTask['groupId'], 'taskClaimedUser'=>$claimedUser, 'taskDateDue'=>$whereTaskDueDay, 'taskStatusPrevious'=>$this->wflTask['taskStatus'], 'taskStatus'=>$statusNew));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>$statusNew), array('transactionId'=>$this->transactionId));
            $this->wflTaskNew = DbMysql::select('wfl_task', array('taskId'=>$taskIdNew), true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}