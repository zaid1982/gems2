<?php

class WoTaskPublic extends General {
    private static $tableName = 'wo_task_public';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $woTaskPublicId
     * @return array
     * @throws Exception
     */
    public function get (int $woTaskPublicId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($woTaskPublicId, 'woTaskPublicId');
            return DbMysql::select($this::$tableName, array('woTaskPublicId'=>$woTaskPublicId), 1);
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
            return DbMysql::select($this::$tableName, array('woTaskId'=>$woTaskId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}