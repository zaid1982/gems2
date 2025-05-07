<?php

class Zone extends General {

    public $zoneId = 0;
    public $zoneName = '';

    private static $tableName = 'cli_zone';
    private static $idName = 'zoneId';

    function __construct(int $userId = 0, bool $isLogged = false)
    {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRef(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll($this::$tableName, array(), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList(): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll($this::$tableName);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getList2(): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return DbMysql::selectAll($this::$tableName, array('siteId'=>$this->userSite));
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $zoneId
     * @return array
     * @throws Exception
     */
    public function get(int $zoneId=0): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->zoneId = !empty($zoneId) ? $zoneId : $this->zoneId;
            return DbMysql::select($this::$tableName, array($this::$idName=>$this->zoneId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $zoneId
     * @throws Exception
     */
    public function set (int $zoneId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($zoneId, $this::$idName);
            $this->zoneId = $zoneId;
            $this->zoneName = DbMysql::selectColumn($this::$tableName, array($this::$idName=>$zoneId),'zoneName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function insert (array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($columns, array('siteId', 'zoneName', 'zoneStatus'), true);
            if (DbMysql::count($this::$tableName, parent::arraySpliceAssoc($columns, array('siteId', 'zoneName'))) > 0) {
                throw new Exception(str_replace('__', $columns['zoneName'], Constant::$zone['errAlreadyExist']), 31);
            }
            $this->set(DbMysql::insert($this::$tableName, $columns));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $zoneId
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (int $zoneId, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($zoneId, $this::$idName);
            parent::checkMandatoryArray($columns, array('siteId', 'zoneName', 'zoneStatus'), true);
            if (DbMysql::count($this::$tableName, array_merge(parent::arraySpliceAssoc($columns, array('siteId', 'zoneName')), array($this::$idName=>'<>|'.$zoneId))) > 0) {
                throw new Exception(str_replace('__', $columns['zoneName'], Constant::$zone['errAlreadyExist']), 31);
            }
            DbMysql::update($this::$tableName, $columns, array($this::$idName=>$zoneId));
            $this->set($zoneId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete (): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->zoneId, $this::$idName);
            if (DbMysql::count($this::$tableName, array($this::$idName=>$this->zoneId)) > 0) {
                throw new Exception(str_replace('__', $this->zoneName, Constant::$zone['errStillExist']), 31);
            }
            DbMysql::delete($this::$tableName, array($this::$idName=>$this->zoneId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}