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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($fcaTaskId);
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($transactionId);
            $this->fcaTask = DbMysql::select('fca_task', array('transactionId'=>$transactionId),true);
            $this->fcaTaskId = $this->fcaTask['fcaTaskId'];
            $this->fcaTaskNo = $this->fcaTask['fcaTaskNo'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function generateNo (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($this->userId);
            $siteId = DbMysql::selectColumn('sys_user', array('userId'=>$this->userId), 'siteId', true);
            $cliSite = DbMysql::select('cli_site', array('siteId'=>$siteId), true);
            $curDates = new DateTime();
            $runningNo = $cliSite['siteRunningNoFca'];
            $runningNoStr = substr(strval($runningNo + 100000), 1);
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($this->userId);
            parent::checkEmptyString($this->fcaTaskNo);
            parent::checkEmptyArray($columns);
            parent::checkMandatoryArray($columns, array('assetGroupId', 'fcaTaskAssetEvaluated', 'fcaTaskDefectItem', 'fcaDefectCategoryId', 'fcaTaskObservation', 'fcaTaskImage1'), true);
            $columns['transactionId'] = $transactionId;
            $columns['siteId'] = DbMysql::selectColumn('sys_user', array('userId'=>$this->userId), 'siteId', true);
            $columns['fcaTaskCreatedBy'] = $this->userId;
            $columns['fcaTaskTimeCreated'] = 'NOW()';
            $fcaTaskId = DbMysql::insert('fca_task', $columns);
            $this->set($fcaTaskId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}