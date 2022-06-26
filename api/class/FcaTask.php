<?php

class FcaTask extends General {

    public $fcaTaskId = 0;
    public $fcaTaskNo = '';
    public $fcaTask = array();

    function __construct () {
    }

    /**
     * @param int $fcaTaskId
     * @throws Exception
     */
    public function set (int $fcaTaskId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaTaskId, 'fcaTaskId');
            $this->fcaTask = DbMysql::select('fca_task', array('fcaTaskId'=>$fcaTaskId),true);
            $this->fcaTaskId = $this->fcaTask['fcaTaskId'];
            $this->fcaTaskNo = $this->fcaTask['fcaTaskNo'];
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
            $this->fcaTask = DbMysql::select('fca_task', array('transactionId'=>$transactionId),true);
            $this->fcaTaskId = $this->fcaTask['fcaTaskId'];
            $this->fcaTaskNo = $this->fcaTask['fcaTaskNo'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return DbMysql::selectAll('fca_task');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaTaskId
     * @return array
     * @throws Exception
     */
    public function get(int $fcaTaskId=0): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fcaTaskId = !empty($fcaTaskId) ? $fcaTaskId : $this->fcaTaskId;
            return DbMysql::select('fca_task', array('fcaTaskId'=>$this->fcaTaskId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getObserveList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectAll('v_fca_task_wf', array('checkpointId'=>51, 'taskCurrent'=>2, 'taskClaimedUser'=>$this->userId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRecommendList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectAll('v_fca_task_wf', array('checkpointId'=>52, 'taskCurrent'=>1, 'taskClaimedUser'=>$this->userId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getValidateList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectAll('v_fca_task_wf', array('checkpointId'=>53, 'taskCurrent'=>1, 'taskClaimedUser'=>'s1|(task_claimed_user IS NULL OR task_claimed_user = '.$this->userId.')'));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCorrectionList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectAll('v_fca_task_wf', array('checkpointId'=>54, 'taskCurrent'=>1, 'taskClaimedUser'=>$this->userId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return void
     * @throws Exception
     */
    public function generateNo ($siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            $cliSite = DbMysql::select('cli_site', array('siteId'=>$siteId), true);
            $curDates = new DateTime();
            $runningNo = $cliSite['siteRunningNoFca'];
            $runningNoStr = $runningNo < 100000 ? substr(strval($runningNo + 100000), 1) : '00001';
            DbMysql::update('cli_site', array('siteRunningNoFca'=>'++'), array('siteId'=>$siteId));
            $this->fcaTaskNo = 'FCA'.$cliSite['siteCode'].$curDates->format("ymd").$runningNoStr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @param int $transactionId
     * @return void
     * @throws Exception
     */
    public function insert (array $columns, int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            parent::checkEmptyString($this->fcaTaskNo, 'fcaTaskNo');
            parent::checkMandatoryArray($columns, array('siteId', 'assetGroupId', 'fcaTaskArea', 'fcaTaskDefectItem', 'fcaTaskObservation', 'fcaTaskImage1'), true);
            $columns['transactionId'] = $transactionId;
            $columns['fcaTaskNo'] = $this->fcaTaskNo;
            $columns['fcaTaskCreatedBy'] = $this->userId;
            $columns['fcaTaskTimeCreated'] = 'NOW()';
            $columns['fcaTaskStatus'] = '55';
            $this->set(DbMysql::insert('fca_task', $columns));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function submitRecommend (array $params): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->fcaTaskId, 'fcaTaskId');
            $columns = parent::arraySpliceAssoc($params, array('fcaTaskAssetNo', 'fcaTaskConditionScale', 'fcaTaskEvaluationType', 'fcaTaskRecommendation'));
            parent::checkMandatoryArray($columns, array('fcaTaskConditionScale', 'fcaTaskEvaluationType', 'fcaTaskRecommendation'), true);
            $columns['fcaTaskRecommendBy'] = $this->userId;
            $columns['fcaTaskTimeRecommended'] = 'NOW()';
            $columns['fcaTaskStatus'] = '56';
            DbMysql::update('fca_task', $columns, array('fcaTaskId'=>$this->fcaTaskId));
            $this->set($this->fcaTaskId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $fcaTaskValidation
     * @return void
     * @throws Exception
     */
    public function submitCorrection (string $fcaTaskValidation): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->fcaTaskId, 'fcaTaskId');
            parent::checkEmptyString($fcaTaskValidation);
            $columns['fcaTaskValidation'] = $fcaTaskValidation;
            $columns['fcaTaskValidateBy'] = $this->userId;
            $columns['fcaTaskTimeValidated'] = 'NOW()';
            $columns['fcaTaskStatus'] = '57';
            DbMysql::update('fca_task', $columns, array('fcaTaskId'=>$this->fcaTaskId));
            $this->set($this->fcaTaskId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function submitValidate (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->fcaTaskId, 'fcaTaskId');
            if ($this->fcaTask['fcaTaskEvaluationType'] !== 1) {
                parent::checkMandatoryArray($columns, array('fcaTaskValidation', 'fcaTaskImageRectify1'), true);
            }
            $columns['fcaTaskValidateBy'] = $this->userId;
            $columns['fcaTaskTimeValidated'] = 'NOW()';
            $columns['fcaTaskStatus'] = '19';
            DbMysql::update('fca_task', $columns, array('fcaTaskId'=>$this->fcaTaskId));
            $this->set($this->fcaTaskId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function resubmit (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->fcaTaskId, 'fcaTaskId');
            parent::checkMandatoryArray($columns, array('siteId', 'assetGroupId', 'fcaTaskArea', 'fcaTaskDefectItem', 'fcaTaskObservation', 'fcaTaskImage1'), true);
            $columns['fcaTaskStatus'] = '55';
            DbMysql::update('fca_task', $columns, array('fcaTaskId'=>$this->fcaTaskId));
            $this->set($this->fcaTaskId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}