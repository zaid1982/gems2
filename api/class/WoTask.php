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
            $this->woTaskId = $woTaskId;
            return DbMysql::select($this::$tableName, array('woTaskId'=>$woTaskId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $assetId
     * @return array
     * @throws Exception
     */
    public function getByAssetId (int $assetId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($assetId, 'assetId');
            return DbMysql::selectAll($this::$tableName, array('assetId'=>$assetId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
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
     * @param $uploadId
     * @return void
     * @throws Exception
     */
    public function submitPublic (array $columns, int $transactionId, $uploadId): void {
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
                $params['woTaskIsPdfWr'] = 1;
            } else {
                $params['woTaskIsPdf'] = 1;
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

    /**
     * @return array
     * @throws Exception
     */
    public function pendingAssign (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $siteId = $this->userSite;
            return DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                     wo.*,
                     tsk.task_id,
                     tsk.checkpoint_id,
                     tsk.task_time_created,
                     tsk.task_status
                FROM wfl_task tsk 
                INNER JOIN wo_task wo ON wo.transaction_id = tsk.transaction_id
                WHERE tsk.checkpoint_id IN (12, 17) AND tsk.task_current = 1 AND wo.site_id = $siteId"
            );
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function pendingVerify (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $userId = $this->userId;
            $siteId = $this->userSite;
            return DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                     wo.*,
                     tsk.task_id,
                     tsk.checkpoint_id,
                     tsk.task_time_created,
                     tsk.task_status,
                     sev.client_severity_respond_time,
                     sev.client_severity_hour
                FROM wfl_task tsk 
                INNER JOIN wo_task wo ON wo.transaction_id = tsk.transaction_id
                LEFT JOIN cli_site ste ON ste.site_id = wo.site_id
                LEFT JOIN cli_client_severity sev ON sev.severity_id = wo.wo_task_severity AND sev.client_id = ste.client_id
                WHERE tsk.checkpoint_id IN (16, 19) AND tsk.task_current = 1 AND wo.site_id = $siteId AND task_claimed_user = $userId"
            );
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function submittedAssign (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $userId = $this->userId;
            $siteId = $this->userSite;
            return DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                     wo.*,
                     tsk.task_id,
                     tsk.checkpoint_id,
                     tsk.task_time_created,
                     tsk.task_time_submit,
                     tsk.task_status,
                     sev.client_severity_respond_time
                FROM wfl_task tsk 
                INNER JOIN wo_task wo ON wo.transaction_id = tsk.transaction_id
                LEFT JOIN cli_site ste ON ste.site_id = wo.site_id
                LEFT JOIN cli_client_severity sev ON sev.severity_id = wo.wo_task_severity AND sev.client_id = ste.client_id
                WHERE tsk.checkpoint_id IN (12, 17) AND tsk.task_current = 2 AND task_claimed_user = $userId AND wo.site_id = $siteId"
            );
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function submittedVerify (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $userId = $this->userId;
            $siteId = $this->userSite;
            return DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                     wo.*,
                     tsk.task_id,
                     tsk.checkpoint_id,
                     tsk.task_time_created,
                     tsk.task_time_submit,
                     tsk.task_status,
                     sev.client_severity_respond_time,
                     sev.client_severity_hour
                FROM wfl_task tsk 
                INNER JOIN wo_task wo ON wo.transaction_id = tsk.transaction_id
                LEFT JOIN cli_site ste ON ste.site_id = wo.site_id
                LEFT JOIN cli_client_severity sev ON sev.severity_id = wo.wo_task_severity AND sev.client_id = ste.client_id
                WHERE tsk.checkpoint_id IN (16, 19) AND tsk.task_current = 2 AND task_claimed_user = $userId AND wo.site_id = $siteId"
            );
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    public function submittedAssignTotal (): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $userId = $this->userId;
            $siteId = $this->userSite;
            return DbMysql::selectSql(
            /** @lang text */
                "SELECT
                     COUNT(*) AS total
                FROM wfl_task tsk 
                INNER JOIN wo_task wo ON wo.transaction_id = tsk.transaction_id
                WHERE tsk.checkpoint_id IN (12, 17) AND tsk.task_current = 2 AND task_claimed_user = $userId AND wo.site_id = $siteId",
            )['total'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    public function submittedVerifyTotal (): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $userId = $this->userId;
            $siteId = $this->userSite;
            return DbMysql::selectSql(
            /** @lang text */
                "SELECT
                     COUNT(*) AS total
                FROM wfl_task tsk 
                INNER JOIN wo_task wo ON wo.transaction_id = tsk.transaction_id
                WHERE tsk.checkpoint_id = 16 AND tsk.task_current = 2 AND task_claimed_user = $userId AND wo.site_id = $siteId",
            )['total'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskId
     * @return array
     * @throws Exception
     */
    public function materialList (int $woTaskId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskId, 'woTaskId');
            return DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                    r.wo_task_id,
                    r.wo_task_request_no,
                    c.item_description,
                    d.item_type_desc,
                    e.asset_group_name,
                    a.wo_task_parts_remark,
                    a.wo_task_parts_quantity,
                    r.wo_task_request_time_ordered,
                    a.wo_task_parts_status
                FROM wo_task_parts a
                LEFT JOIN wo_task_request r ON r.wo_task_request_id = a.wo_task_request_id
                LEFT JOIN ast_part b ON b.part_id = a.part_id
                LEFT JOIN ref_item c ON c.item_id = b.item_id
                LEFT JOIN ref_item_type d ON d.item_type_id = b.item_type_id
                LEFT JOIN ast_asset_group e ON e.asset_group_id = b.asset_group_id",
            array('r.wo_task_id'=>$woTaskId));
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
    public function submitAssign (array $columns, int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->woTaskId, 'woTaskId');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            parent::checkMandatoryArray($columns, array('woTaskType', 'woTaskSeverity', 'ppmGroupId', 'woTaskAssignedTo', 'woTaskMaxAssistant'));

            $columns['woTaskAssignedBy'] = $this->userId;
            $columns['woTaskTimeAssigned'] = 'NOW()';
            if ($this->woTaskIsWr === 1) {
                $woStatus = 27;
                $columns['woTaskIsPdfWr'] = 1;
            } else {
                $woStatus = 13;
                $columns['woTaskIsPdf'] = 1;
            }
            $columns['woTaskStatus'] = $woStatus;
            DbMysql::update($this::$tableName, $columns, array('woTaskId'=>$this->woTaskId));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>$woStatus), array('transactionId'=>$transactionId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $transactionId
     * @return void
     * @throws Exception
     */
    public function rejectAssign (int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->woTaskId, 'woTaskId');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            if ($this->woTaskIsWr === 1) {
                $woStatus = 27;
                $columns['woTaskIsPdfWr'] = 1;
            } else {
                $woStatus = 13;
                $columns['woTaskIsPdf'] = 1;
            }
            $columns['woTaskStatus'] = 25;
            DbMysql::update($this::$tableName, $columns, array('woTaskId'=>$this->woTaskId));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>$woStatus), array('transactionId'=>$transactionId));
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
    public function reassign (array $columns, int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->woTaskId, 'woTaskId');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            parent::checkMandatoryArray($columns, array('ppmGroupId', 'woTaskAssignedTo'));

            $columns['woTaskAssignedBy'] = $this->userId;
            $columns['woTaskTimeAssigned'] = 'NOW()';
            $columns['woTaskIsPdf'] = 1;
            $columns['woTaskStatus'] = 13;
            DbMysql::update($this::$tableName, $columns, array('woTaskId'=>$this->woTaskId));
            DbMysql::delete('wo_task_assist', array('woTaskId'=>$this->woTaskId));
            DbMysql::update('wfl_task_assign', array('userId'=>$columns['woTaskAssignedTo']), array('transactionId'=>$transactionId, 'roleId'=>8));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>13), array('transactionId'=>$transactionId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $transactionId
     * @return void
     * @throws Exception
     */
    public function returnVerify (int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->woTaskId, 'woTaskId');
            parent::checkEmptyInteger($transactionId, 'transactionId');

            $columns['woTaskTimeExecuted'] = null;
            $columns['woTaskIsPdf'] = 1;
            $columns['woTaskStatus'] = 21;
            DbMysql::update($this::$tableName, $columns, array('woTaskId'=>$this->woTaskId));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>21), array('transactionId'=>$transactionId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $transactionId
     * @param int $rating
     * @return void
     * @throws Exception
     */
    public function submitVerify (int $transactionId, int $rating): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($this->woTaskId, 'woTaskId');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            parent::checkEmptyInteger($rating, 'rating');

            $signatureId = DbMysql::selectColumn('sys_user', array('userId'=>$this->userId), 'userSignature', true);
            $columns['woTaskRate'] = $rating;
            $columns['woTaskVerifiedBy'] = $this->userId;
            $columns['woTaskTimeVerified'] = null;
            $columns['woTaskIsPdf'] = 1;
            $columns['woTaskStatus'] = 16;
            DbMysql::update($this::$tableName, $columns, array('woTaskId'=>$this->woTaskId));
            DbMysql::insert('wo_task_upload', array('woTaskId'=>$this->woTaskId, 'woTaskUploadType'=>8, 'uploadId'=>$signatureId));
            DbMysql::update('wfl_transaction', array('transactionStatus'=>16), array('transactionId'=>$transactionId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskId
     * @param array $columns
     * @return bool
     * @throws Exception
     */
    public function updateByAdmin (int $woTaskId, array $columns): bool {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($woTaskId, 'woTaskId');
            $woTask = $this->get($woTaskId);
            $params = parent::arraySpliceAssoc($columns, array('woTaskComplaint'));
            if (!empty($columns['assetNo'])) {
                $contractId = DbMysql::selectColumn('cli_contract', array('siteId'=>$woTask['siteId'], 'contractStatus'=>1), 'contractId', true);
                $assetId = DbMysql::selectColumn('ast_asset', array('assetNo'=>$columns['assetNo'], 'contractId'=>$contractId), 'assetId');
                if (empty($assetId)) {
                    return false;
                }
                $params['assetId'] = $assetId;
            }
            if ($woTask['woTaskType'] !== 2 && $woTask['woTaskType'] !== 6) {
                $params['woTaskType'] = $columns['woTaskType'];
            }
            DbMysql::update($this::$tableName, $params, array('woTaskId'=>$woTaskId));
            $this->auditRemark = 'Work Order no. = ' . $woTask['woTaskNo'];
            return true;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function prepareImageZip (array $params): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($params, array('clientId', 'dateFrom', 'dateTo'));
            $folder = 'upload/wo/zip_image/';
            if (!parent::folderExist($folder)) {
                mkdir ($folder,0777, true);
            }
            $currentTime = new DateTime();
            $timeStr = $currentTime->format('YmdHis');
            $zip = new ZipArchive();
            $zipFile = $folder.'woImages_'.$timeStr.'.zip';
            $flag = (file_exists($zipFile))? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE;
            $totalFile = 1;
            if ($zip->open($zipFile, $flag) === true){
                $uploadList = DbMysql::selectSqlAll( /** @lang text */
                    "SELECT
                            wo.wo_task_no, upl.document_id, upl.upload_folder, upl.upload_filename, upl.upload_extension
                        FROM sys_upload upl 
                        LEFT JOIN wo_task_upload wup ON wup.upload_id = upl.upload_id
                        LEFT JOIN wo_task wo ON wo.wo_task_id = wup.wo_task_id
                        LEFT JOIN cli_site site ON site.site_id = wo.site_id",
                    array('site.clientId'=>$params['clientId'], 'upl.documentId'=>'IN|9,10,11,12', 'date(wo.woTaskTimeCreated)'=>'>=|'.$params['dateFrom'], 'date(wo.woTaskTimeCreated) '=>'<=|'.$params['dateTo']), 0, 0, 'wo.wo_task_no');
                foreach ($uploadList as $row) {
                    $filenameSrc = $row['uploadFolder'].'/'.$row['uploadFilename'].'.'.$row['uploadExtension'];
                    if (file_exists($filenameSrc)) {
                        //parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, '$row = '.json_encode($row));
                        if ($row['documentId'] === 9) {
                            $name = $row['woTaskNo'].'_'.$totalFile.'_complaint';
                        } else if ($row['documentId'] === 10) {
                            $name = $row['woTaskNo'].'_'.$totalFile.'_before';
                        } else if ($row['documentId'] === 11) {
                            $name = $row['woTaskNo'].'_'.$totalFile.'_during';
                        } else if ($row['documentId'] === 12) {
                            $name = $row['woTaskNo'].'_'.$totalFile.'_after';
                        } else {
                            continue;
                        }
                        $zip->addFile($filenameSrc, '/'.$row['woTaskNo'].'/'.$name.'.'.$row['uploadExtension']);
                        $totalFile++;
                    }
                }
                $zip->close();
            } else{
                throw new Exception('Issue to create zip file');
            }
            if ($totalFile === 0) {
                return 'No WO image available!';
            } else {
                return 'api/'.$zipFile;
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}