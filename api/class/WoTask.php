<?php

class WoTask extends General {

    public $woTaskId = 0;
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
}