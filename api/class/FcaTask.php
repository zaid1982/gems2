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
     * @param int $checkpointId
     * @return array
     * @throws Exception
     */
    public function getSubmittedList (int $checkpointId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($checkpointId, 'checkpointId');
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectAll('v_fca_task_wf', array('checkpointId'=>$checkpointId, 'taskCurrent'=>2, 'taskClaimedUser'=>$this->userId));
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
            parent::checkEmptyString($this->fcaTaskNo, 'fcaTaskNo');
            parent::checkEmptyArray($columns, 'columns');
            parent::checkMandatoryArray($columns, array('siteId', 'assetGroupId', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation', 'fcaTaskImage1'), true);
            $columns['transactionId'] = $transactionId;
            $columns['fcaTaskNo'] = $this->fcaTaskNo;
            $columns['fcaTaskCreatedBy'] = $this->userId;
            $columns['fcaTaskTimeCreated'] = 'NOW()';
            $columns['fcaTaskStatus'] = '55';
            $fcaTaskId = DbMysql::insert('fca_task', $columns);
            $this->set($fcaTaskId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

}