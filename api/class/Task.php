
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($taskId);
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($checkpointId);
            parent::checkEmptyInteger($this->userId);
            parent::checkEmptyInteger($this->taskId);
            parent::checkEmptyArray($this->wflTask);
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($flowId);
            parent::checkEmptyString($transactionNo);

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
            $taskId = DbMysql::insert('wfl_task', array('transactionId'=>$transactionId, 'checkpointIId'=>$checkpoint['checkpointId'], 'roleId'=>$checkpoint['roleId'], 'groupId'=>$groupId,
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
    public function getRecipients (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyArray($this->wflTaskNew);
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
}