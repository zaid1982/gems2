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
}