<?php

class WoTaskAssist extends General {
    private static $tableName = 'wo_task_assist';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskAssistId
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskAssistId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskAssistId, 'woTaskAssistId');
            return DbMysql::select($this::$tableName, array('woTaskAssistId'=>$woTaskAssistId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $woTaskId
     * @return array
     * @throws Exception
     */
    public function getByWoTaskId (int $woTaskId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskId, 'woTaskId');
            return DbMysql::selectAll($this::$tableName, array('woTaskId'=>$woTaskId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}