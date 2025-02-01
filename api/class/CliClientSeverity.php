<?php

class CliClientSeverity extends General {
    private static $tableName = 'cli_client_severity';

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $clientSeverityId
     * @return array
     * @throws Exception
     */
    public function get (int $clientSeverityId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($clientSeverityId, 'clientSeverityId');
            return DbMysql::select($this::$tableName, array('clientSeverityId'=>$clientSeverityId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @param int $severityId
     * @return array
     * @throws Exception
     */
    public function getBySiteSeverity (int $siteId, int $severityId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            parent::checkEmptyInteger($severityId, 'severityId');
            $clientId = DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'clientId', 1);
            return DbMysql::select($this::$tableName, array('clientId'=>$clientId, 'severityId'=>$severityId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }
}