<?php

class WoTaskRequest extends General {

    public $woTaskRequestId = 0;
    private static $tableName = 'wo_task_request';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskRequestId
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskRequestId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskRequestId, 'woTaskRequestId');
            $this->woTaskRequestId = $woTaskRequestId;
            return DbMysql::select($this::$tableName, array('woTaskRequestId'=>$woTaskRequestId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPendingTaskMobile (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT
                    wr.wo_task_request_id,
                    wr.wo_task_request_no,
                    IF(COUNT(wp.wo_task_parts_id) > 1, CONCAT(COUNT(wp.wo_task_parts_id), ' materials'), 
                        it.item_description) AS item_name,
                    IFNULL(SUM(wp.wo_task_parts_quantity), 0) AS total_unit, 
                    IF(wr.wo_task_request_status = 32, wr.wo_task_request_time_created, wr.wo_task_request_time_ordered) AS time_ordered, 
                    st.status_desc,
                    st.status_color_code
                FROM wo_task_request wr
                LEFT JOIN wo_task_parts wp ON wp.wo_task_request_id = wr.wo_task_request_id
                LEFT JOIN ast_part ap ON ap.part_id = wp.part_id
                LEFT JOIN ref_item it ON it.item_id = ap.item_id
                LEFT JOIN ref_status st ON st.status_id = wr.wo_task_request_status
                WHERE wr.wo_task_request_is_standalone = 1 AND wr.wo_task_request_order_by = $this->userId AND wr.wo_task_request_status IN (32, 33, 34, 38, 51) 
                GROUP BY wr.wo_task_request_id",  array(), 0, false, 'timeOrdered', 'DESC');
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getHistoryTaskMobile (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            return DbMysql::selectSqlAll(
            /** @lang text */
                "SELECT
                    wr.wo_task_request_id,
                    wr.wo_task_request_no,
                    IF(COUNT(wp.wo_task_parts_id) > 1, CONCAT(COUNT(wp.wo_task_parts_id), ' materials'), 
                        it.item_description) AS item_name,
                    IFNULL(SUM(wp.wo_task_parts_quantity), 0) AS total_unit, 
                    IF(wr.wo_task_request_status = 32, wr.wo_task_request_time_created, wr.wo_task_request_time_ordered) AS time_ordered, 
                    st.status_desc,
                    st.status_color_code
                FROM wo_task_request wr
                LEFT JOIN wo_task_parts wp ON wp.wo_task_request_id = wr.wo_task_request_id
                LEFT JOIN ast_part ap ON ap.part_id = wp.part_id
                LEFT JOIN ref_item it ON it.item_id = ap.item_id
                LEFT JOIN ref_status st ON st.status_id = wr.wo_task_request_status
                WHERE wr.wo_task_request_order_by = $this->userId AND wr.wo_task_request_status IN (36, 50) 
                GROUP BY wr.wo_task_request_id",  array(), 0, false, 'timeOrdered', 'DESC');
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
     * @param string $draftNo
     * @param int $transactionId
     * @return void
     * @throws Exception
     */
    public function createDraft (string $draftNo, int $transactionId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            parent::checkEmptyString($draftNo, 'draftNo');
            parent::checkEmptyInteger($transactionId, 'siteId');
            $this->woTaskRequestId = DbMysql::insert($this::$tableName, array('woTaskRequestNo'=>$draftNo, 'transactionId'=>$transactionId, 'woTaskRequestOrderBy'=>$this->userId, 'woTaskRequestIsStandalone'=>1,
                'woTaskRequestStatus'=>32));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}