
<?php

class WflTask extends General {

    public $taskId = 0;
    public $transactionId = 0;
    public $transactionNo = '';
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
     * @param int $transactionId
     * @throws Exception
     */
    public function setByTransaction (int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($transactionId, 'transactionId');
            $this->transactionId = $transactionId;
            $this->wflTask = DbMysql::select('wfl_task', array('transactionId'=>$this->transactionId, 'taskCurrent'=>1),true);
            $this->taskId = $this->wflTask['taskId'];
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
            if ($this->wflTask['taskCurrent'] !== 1 || $this->wflTask['checkpointId'] !== $checkpointId) {
                throw new Exception(Constant::$task['errAlreadySubmitted'], 31);
            }
            if ($this->wflTask['taskClaimedUser'] !== null && $this->wflTask['taskClaimedUser'] !== $this->userId) {
                throw new Exception(Constant::$task['errClaimed'], 31);
            }
            if (DbMysql::count('sys_user_role', array('userId'=>$this->userId, 'roleId'=>$this->wflTask['roleId'], 'groupId'=>$this->wflTask['groupId'])) === 0) {
                $roleName = DbMysql::selectColumn('ref_role', array('roleId'=>$this->wflTask['roleId']), 'roleDesc', true);
                throw new Exception(str_replace('__', $roleName, Constant::$task['errInvalidRole']), 31);
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
                throw new Exception(str_replace('__', $roleName, Constant::$task['errInvalidRole']), 31);
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
     * @param bool $isSite
     * @return array
     * @throws Exception
     */
    public function getRecipients (bool $isSite=false): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyArray($this->wflTaskNew, 'wflTaskNew');
            if (!empty($this->wflTaskNew['taskClaimedUser'])) {
                return array($this->userId);
            }
            if ($isSite) {
                $siteId = DbMysql::selectColumn('sys_user', array('userId'=>$this->userId), 'siteId', true);
                $checkpointUsers = DbMysql::selectSqlAll(/** @lang text */ "SELECT wfl_checkpoint_user.*, sys_user.site_id
                    FROM wfl_checkpoint_user 
                    LEFT JOIN sys_user ON sys_user.user_id = wfl_checkpoint_user.user_id", array('siteId'=>$siteId, 'checkpointId'=>$this->wflTaskNew['checkpointId'], 'roleId'=>$this->wflTaskNew['roleId']));
            } else {
                $checkpointUsers = DbMysql::selectAll('wfl_checkpoint_user', array('checkpointId'=>$this->wflTaskNew['checkpointId'], 'roleId'=>$this->wflTaskNew['roleId']));
            }
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
                throw new Exception(Constant::$task['errNotClaimed'], 31);
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
                $groupId = !empty($wflCheckpointNext['groupId']) ? $wflCheckpointNext['groupId'] : $wflTransaction['groupId'];
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
            $groupId = !empty($wflCheckpointNext['groupId']) ? $wflCheckpointNext['groupId'] : 0;
            $claimedUser = null;
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
                'taskCreatedGroup'=>$this->wflTask['groupId'], 'taskClaimedUser'=>$claimedUser, 'taskDateDue'=>$whereTaskDueDay, 'taskStatusPrevious'=>$status, 'taskStatus'=>$statusNew));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>$statusNew), array('transactionId'=>$this->transactionId));
            $this->wflTaskNew = DbMysql::select('wfl_task', array('taskId'=>$taskIdNew), true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $transactionId
     * @return array
     * @throws Exception
     */
    public function getProgressTimeList (int $transactionId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($transactionId, 'transactionId');
            $checkpointTurn = 0;
            $returnArr = array();
            $taskArr = DbMysql::selectSqlAll(/** @lang text */ "SELECT 
                    TIMEDIFF(task_time_submit, task_time_created) AS performance,
                    TIMEDIFF(NOW(), task_time_created) AS balance,
                    task_time_submit,
                    checkpoint_desc,
                    checkpoint_type,
                    checkpoint_color,
                    checkpoint_order
                FROM wfl_task t
                LEFT JOIN wfl_checkpoint c ON c.checkpoint_id = t.checkpoint_id", array('transactionId'=>$transactionId, 'checkpointType'=>'<>|3'));
            foreach ($taskArr as $i=>$task) {
                $return = parent::arraySpliceAssoc($task, array('checkpointDesc', 'checkpointColor', 'taskTimeSubmit'));
                $durationStr = '';
                $barPercent = 100;
                if ($task['checkpointType'] <> 1) {
                    $barPercent = $task['performance'] !== null ? 100 : 0;
                    $duration = $task['performance'] !== null ? $task['performance'] : $task['balance'];
                    $durationStr = parent::timeDisplay($duration);
                }
                $return['duration'] = $durationStr;
                $return['barPercent'] = $barPercent;
                $returnArr[] = $return;
                if ($i === count($taskArr) - 1 && $task['checkpointOrder'] !== null) {
                    $checkpointTurn = $task['checkpointOrder'];
                }
            }
            $flowId = DbMysql::selectColumn('wfl_transaction', array('transactionId'=>$transactionId), 'flowId');
            $checkpointArr = DbMysql::selectAll('wfl_checkpoint', array('flowId'=>$flowId, 'checkpointType'=>'<>|3', 'checkpointSkip'=>0, 'checkpointOrder'=>'>|'.$checkpointTurn), 0, false, 'checkpointOrder');
            foreach ($checkpointArr as $checkpoint) {
                $returnArr[] = array('checkpointDesc'=>$checkpoint['checkpointDesc'], 'checkpointColor'=>$checkpoint['checkpointColor'], 'taskTimeSubmit'=>null, 'duration', 'barPercent'=>0, 'duration'=>'');
            }
            return $returnArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $transactionId
     * @throws Exception
     */
    public function deleteDraft (int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($transactionId, 'transactionId');
            $this->setByTransaction($transactionId);
            if ($this->wflTask['taskStatus'] !== 5) {
                throw new Exception(str_replace('__', $this->transactionNo, Constant::$task['errAlreadySubmitted2']), 31);
            } else if ($this->wflTask['taskCreatedUser'] !== $this->userId) {
                throw new Exception(Constant::$task['errNotAllowed'], 31);
            }
            DbMysql::delete('wfl_task_assign', array('transactionId'=>$transactionId));
            DbMysql::delete('wfl_task', array('transactionId'=>$transactionId));
            DbMysql::delete('wfl_transaction', array('transactionId'=>$transactionId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}