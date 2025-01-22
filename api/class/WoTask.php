<?php

class WoTask extends General {

    public $woTaskId = 0;
    public $woTaskNo = '';
    public $woTaskIsWr = 0;
    private static $tableName = 'wo_task';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskId
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskId, 'woTaskId');
            return DbMysql::select($this::$tableName, array('woTaskId'=>$woTaskId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return void
     * @throws Exception
     */
    public function generateNo (int $siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            $site = DbMysql::select('cli_site', array('siteId'=>$siteId), 1);
            $curDates = new DateTime();
            $siteCode = $site['siteCode'];
            if ($site['siteIsWr'] !== 1) {
                $runningNo = $site['siteRunningNoWo'];
                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                DbMysql::update('cli_site', array('siteRunningNoWo'=>'++'), array('siteId'=>$siteId));
                $this->woTaskNo = 'WO'.$siteCode.$curDates->format("ymd").$runningNoStr;
            } else {
                $runningNoWr = $site['siteRunningNoWr'];
                $runningNoWrTemp = 100000 + $runningNoWr;
                $runningNoWrStr = substr(strval($runningNoWrTemp), 1);
                DbMysql::update('cli_site', array('siteRunningNoWr'=>'++'), array('siteId'=>$siteId));
                $this->woTaskNo = 'WR'.$siteCode.$curDates->format("ymd").$runningNoWrStr;
                $this->woTaskIsWr = 1;
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @param int $transactionId
     * @param int $uploadId
     * @return void
     * @throws Exception
     */
    public function submitPublic (array $columns, int $transactionId, int $uploadId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            parent::checkEmptyString($this->woTaskNo, 'woTaskNo');
            parent::checkMandatoryArray($columns, array('siteId', 'zoneId', 'name', 'email', 'complaint'));

            $params = array('transactionId'=>$transactionId, 'woTaskNo'=>$this->woTaskNo, 'woTaskType'=>6, 'woTaskTypeInit'=>6, 'siteId'=>$columns['siteId'], 'zoneId'=>$columns['zoneId'],
                'woTaskComplaint'=>$columns['complaint'], 'woTaskCreatedBy'=>$this->userId, 'woTaskTimeCreated'=>'NOW()', 'woTaskIsPublic'=>1, 'woTaskStatus'=>24);
            $zone = DbMysql::select('cli_zone', array('zoneId'=>$columns['zoneId']), 1);
            $params['woTaskLocation'] = $zone['zoneCode'].' - '.$zone['zoneName'];
            $params['woTaskTimeResponded'] = 'NOW()';
            if ($this->woTaskIsWr === 1) {
                $params['woTaskIsWr'] = 1;
                $params['woTaskRequestNo'] = $this->woTaskNo;
            }
            $this->woTaskId = DbMysql::insert($this::$tableName, $params);

            DbMysql::insert('wo_task_public', array('woTaskId'=>$this->woTaskId, 'transactionId'=>$transactionId, 'userId'=>$this->userId, 'woTaskPublicName'=>$columns['name'],
                'woTaskPublicIcNo'=>$columns['icNo'], 'woTaskPublicAgency'=>$columns['agency'], 'woTaskPublicPhoneNo'=>$columns['phoneNo'], 'woTaskPublicEmail'=>$columns['email'], 'woTaskPublicComplaint'=>$columns['complaint']));
            if (!empty($uploadId)) {
                DbMysql::insert('wo_task_upload', array('woTaskId'=>$this->woTaskId, 'uploadId'=>$uploadId));
            }
            DbMysql::update('wfl_transaction', array('transactionStatus'=>24), array('transactionId'=>$transactionId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}