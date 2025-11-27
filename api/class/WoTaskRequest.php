<?php

class WoTaskRequest extends General {

    public $woTaskRequestId = 0;
    public $woTaskRequestNo;
    public $woTaskRequest;
    private static $tableName = 'wo_task_request';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskRequestId
     * @param bool $throwEmpty
     * @throws Exception
     */
    public function set (int $woTaskRequestId, bool $throwEmpty = true): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            $this->woTaskRequestId = $woTaskRequestId;
            $this->woTaskRequest = $this->get($woTaskRequestId, $throwEmpty);
            $this->woTaskRequestNo = $this->woTaskRequest['woTaskRequestNo'];
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @param bool $throwEmpty
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskRequestId, bool $throwEmpty = true): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            $this->woTaskRequestId = $woTaskRequestId;
            $woTaskRequest = DbMysql::select($this::$tableName, array('woTaskRequestId'=>$woTaskRequestId), $throwEmpty);
            if (!$throwEmpty && empty($woTaskRequest)) {
                throw new Exception(Constant::$woTaskRequest['errAlreadyRemoved'], 31);
            }
            return $woTaskRequest;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getListPendingMobile (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectSqlAll(
                    /** @lang text */
                    "SELECT
                        wr.wo_task_request_id,
                        wr.transaction_id,
                        wr.wo_task_request_no,
                        wr.wo_task_no,
                        sv.severity_name,
                        IF(COUNT(wp.wo_task_parts_id) > 1, CONCAT(COUNT(wp.wo_task_parts_id), ' materials'), 
                            it.item_description) AS item_name,
                        IFNULL(SUM(wp.wo_task_parts_quantity), 0) AS total_unit, 
                        DATE_FORMAT(IF(wr.wo_task_request_status = 32, wr.wo_task_request_time_created, wr.wo_task_request_time_ordered), '%e/%c/%Y %l:%i:%s %p') AS time_ordered, 
                        st.status_desc,
                        st.status_color_code
                    FROM wo_task_request wr
                    LEFT JOIN wo_task_parts wp ON wp.wo_task_request_id = wr.wo_task_request_id
                    LEFT JOIN ast_part ap ON ap.part_id = wp.part_id
                    LEFT JOIN ref_item it ON it.item_id = ap.item_id
                    LEFT JOIN ref_status st ON st.status_id = wr.wo_task_request_status
                    LEFT JOIN ref_severity sv ON sv.severity_id = wr.wo_task_request_severity",
                array('wr.woTaskRequestIsStandalone'=>1, 'wr.woTaskRequestOrderBy'=>$this->userId, 'wr.woTaskRequestStatus'=>'IN|32,33,34,38,51'), 0, false, 'timeOrdered', 'DESC', '', 'wr.woTaskRequestId');
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getListHistoryMobile (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectSqlAll(
                    /** @lang text */
                    "SELECT
                        wr.wo_task_request_id,
                        wr.transaction_id,
                        wr.wo_task_request_no,
                        wr.wo_task_no,
                        sv.severity_name,
                        IF(COUNT(wp.wo_task_parts_id) > 1, CONCAT(COUNT(wp.wo_task_parts_id), ' materials'), 
                            it.item_description) AS item_name,
                        IFNULL(SUM(wp.wo_task_parts_quantity), 0) AS total_unit, 
                        DATE_FORMAT(wr.wo_task_request_time_ordered, '%e/%c/%Y %l:%i:%s %p') AS time_ordered, 
                        st.status_desc,
                        st.status_color_code
                    FROM wo_task_request wr
                    LEFT JOIN wo_task_parts wp ON wp.wo_task_request_id = wr.wo_task_request_id
                    LEFT JOIN ast_part ap ON ap.part_id = wp.part_id
                    LEFT JOIN ref_item it ON it.item_id = ap.item_id
                    LEFT JOIN ref_status st ON st.status_id = wr.wo_task_request_status
                    LEFT JOIN ref_severity sv ON sv.severity_id = wr.wo_task_request_severity",
                array('wr.woTaskRequestOrderBy'=>$this->userId, 'wr.woTaskRequestStatus'=>'IN|36,50'), 0, false, 'timeOrdered', 'DESC', '', 'wr.woTaskRequestId');
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getListMrf (int $siteId, int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            return DbMysql::selectSqlAll(/** @lang text */
                "SELECT
                        r.*,
                        w.wo_task_no
                    FROM wo_task_request r
                    LEFT JOIN wo_task w ON w.wo_task_id = r.wo_task_id",
                array('w.siteId'=>$siteId, 'year(r.woTaskRequestTimeOrdered)'=>$year, 'month(r.woTaskRequestTimeOrdered)'=>$month));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskId
     * @return array
     * @throws Exception
     */
    public function getLatestByWoTaskId (int $woTaskId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskId, 'woTaskId');
            $records = DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT wr.* FROM wo_task_request wr",
                array('wr.woTaskId'=>$woTaskId),
                0,
                false,
                'wr.woTaskRequestId',
                'DESC',
                '1'
            );
            if (empty($records)) {
                throw new Exception(Constant::$woTaskRequest['errNotFound'], 31);
            }
            $latest = $records[0];
            if (empty($latest['woTaskNo'])) {
                $latest['woTaskNo'] = DbMysql::selectColumn('wo_task', array('woTaskId'=>$woTaskId), 'woTaskNo');
            }
            return $latest;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getRequestNoDraft (): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $runningNo = intval(DbMysql::selectColumn('sys_version', array('versionId'=>37), 'versionNo', true));
            $runningNoTemp = 1000000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            DbMysql::update('sys_version', array('versionNo'=>++$runningNo), array('versionId'=>37));
            return 'RQDRAFT'.$runningNoStr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getRequestNo (): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $site = DbMysql::select('cli_site', array('siteId'=>$this->userSite), 1);
            $runningNo = $site['siteRunningNoReq'];
            $runningNoTemp = 100000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            $runningNo++;
            $curDates = new DateTime();
            DbMysql::update('cli_site', array('siteRunningNoReq'=>++$runningNo), array('siteId'=>$this->userSite));
            return 'RQ'.$site['siteCode'].$curDates->format("ymd").$runningNoStr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param bool $isMobile
     * @return array
     * @throws Exception
     */
    public function getRefSeverity (bool $isMobile = false): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userSite, 'userSite');
            $clientId = DbMysql::selectColumn('cli_site', array('siteId'=>$this->userSite), 'clientId', true);
            $sql = /** @lang text */ "SELECT
                    sv.severity_id, sv.severity_name, cs.client_severity_hour, cs.client_severity_respond_time
                FROM cli_client_severity cs 
                LEFT JOIN ref_severity sv ON sv.severity_id = cs.severity_id";
            if ($isMobile) {
                return $this->arraySpliceAssocMultiple(DbMysql::selectSqlAll($sql, array('cs.clientId'=>$clientId, 'sv.severityStatus'=>1), 0, false, 'sv.severityName'), array('severityId', 'severityName'));
            } else {
                return DbMysql::selectSqlAll($sql, array('clientId'=>$clientId), 1);
            }
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @return array
     * @throws Exception
     */
    public function getDetailsMobile (int $woTaskRequestId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            $arrDetails = array();
            $woTaskRequest = DbMysql::select($this::$tableName, array('woTaskRequestId'=>$woTaskRequestId), true);
            if ($woTaskRequest['woTaskRequestIsStandalone'] === 1) {
                $siteId = DbMysql::selectColumn('sys_user', array('userId'=>$woTaskRequest['woTaskRequestOrderBy']), 'siteId', true);
            } else {
                $siteId = DbMysql::selectColumn('wo_task', array('woTaskId'=>$woTaskRequest['woTaskId']), 'siteId', true);
            }
            $arrDetails['woTaskRequestId'] = $woTaskRequest['woTaskRequestId'];
            $arrDetails['woTaskRequestNo'] = $woTaskRequest['woTaskRequestNo'];
            $arrDetails['woTaskNo'] = $woTaskRequest['woTaskNo'];
            $arrDetails['location'] = DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'siteName', true);
            $arrDetails['store'] = !empty($woTaskRequest['storeId']) ? DbMysql::selectColumn('cli_store', array('storeId'=>$woTaskRequest['storeId']), 'storeName') : null;
            $arrDetails['severity'] = DbMysql::selectColumn('ref_severity', array('severityId'=>$woTaskRequest['woTaskRequestSeverity']), 'severityName', true);
            $arrDetails['remark'] = $woTaskRequest['woTaskRequestRemark'];
            $arrDetails['requestTime'] = parent::timeDisplayPretty($woTaskRequest['woTaskRequestTimeOrdered'], true);
            $arrDetails['collectTime'] = parent::timeDisplayPretty($woTaskRequest['woTaskRequestTimeCollected'], true);
            $arrDetails['requestBy'] = DbMysql::selectColumn('sys_user', array('userId'=>$woTaskRequest['woTaskRequestOrderBy']), 'userFirstName', true);
            $arrDetails['status'] = DbMysql::selectColumn('ref_status', array('statusId'=>$woTaskRequest['woTaskRequestStatus']), 'statusDesc', true);
            return $arrDetails;
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $draftNo
     * @param int $transactionId
     * @param array $inputParams
     * @return void
     * @throws Exception
     */
    public function insertDraft (string $draftNo, int $transactionId, array $inputParams): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyString($draftNo, 'draftNo');
            parent::checkEmptyInteger($transactionId, 'transactionId');
            $params = $this->arraySpliceAssoc($inputParams, array('storeId', 'woTaskRequestSeverity', 'woTaskNo', 'woTaskRequestRemark'));
            parent::checkMandatoryArray($params, array('storeId'), true);
            parent::checkMandatoryOption($params['woTaskRequestSeverity'], array_keys($this->getRefSeverity()), 'Request Severity', true);
            $sqlInsert = array('woTaskRequestNo'=>$draftNo, 'transactionId'=>$transactionId, 'woTaskRequestOrderBy'=>$this->userId, 'woTaskRequestIsStandalone'=>1, 'woTaskRequestStatus'=>32);
            $this->woTaskRequestId = DbMysql::insert($this::$tableName, array_merge($sqlInsert, $params));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @throws Exception
     */
    public function delete (int $woTaskRequestId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            parent::checkEmptyArray($this->woTaskRequest, 'woTaskRequest');
            if ($this->woTaskRequest['woTaskRequestStatus'] !== 32) {
                throw new Exception(str_replace('__', $this->woTaskRequestNo, Constant::$woTaskRequest['errAlreadySubmitted']), 31);
            } else if ($this->woTaskRequest['woTaskRequestOrderBy'] !== $this->userId) {
                throw new Exception(Constant::$woTaskRequest['errNotAllowed'], 31);
            }
            DbMysql::delete('wo_task_parts', array('woTaskRequestId'=>$woTaskRequestId));
            DbMysql::delete($this::$tableName, array('woTaskRequestId'=>$woTaskRequestId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskRequestId
     * @param string $woTaskRequestNo
     * @param int $transactionId
     * @throws Exception
     */
    public function submit (int $woTaskRequestId, string $woTaskRequestNo): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            parent::checkEmptyString($woTaskRequestNo, 'woTaskRequestNo');
            parent::checkEmptyArray($this->woTaskRequest, 'woTaskRequest');
            if ($this->woTaskRequest['woTaskRequestStatus'] !== 32) {
                throw new Exception(str_replace('__', $this->woTaskRequestNo, Constant::$woTaskRequest['errAlreadySubmitted']), 31);
            } else if ($this->woTaskRequest['woTaskRequestOrderBy'] !== $this->userId) {
                throw new Exception(Constant::$woTaskRequest['errNotAllowed'], 31);
            }
            DbMysql::update($this::$tableName, array('woTaskRequestNo'=>$woTaskRequestNo, 'woTaskRequestTimeOrdered'=>'NOW()', 'woTaskRequestStatus'=>33, 'woTaskRequestMrfGenerate'=>1), array('woTaskRequestId'=>$woTaskRequestId));
            DbMysql::update('wo_task_parts', array('woTaskPartsStatus'=>33), array('woTaskRequestId'=>$woTaskRequestId));
            DbMysql::update('wfl_transaction', array('transactionNo'=>$woTaskRequestNo), array('transactionId'=>$this->woTaskRequest['transactionId']));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}