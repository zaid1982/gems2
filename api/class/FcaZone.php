<?php

class FcaZone extends General {

    public $fcaZoneId = 0;
    public $fcaZoneName = '';

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
            return DbMysql::selectAll('fca_zone', array(), 1);
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
            return DbMysql::selectAll('fca_zone', array());
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaZoneId
     * @return array
     * @throws Exception
     */
    public function get(int $fcaZoneId=0): array
    {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fcaZoneId = !empty($fcaZoneId) ? $fcaZoneId : $this->fcaZoneId;
            return DbMysql::select('fca_zone', array('fcaZoneId'=>$this->fcaZoneId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaZoneId
     * @throws Exception
     */
    public function set (int $fcaZoneId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaZoneId, 'fcaZoneId');
            $this->fcaZoneId = $fcaZoneId;
            $this->fcaZoneName = DbMysql::selectColumn('fca_zone', array('fcaZoneId'=>$fcaZoneId),'fcaZoneName', true);
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
            parent::checkMandatoryArray($columns, array('siteId', 'fcaZoneName', 'fcaZoneStatus'), true);
            $this->set(DbMysql::insert('fca_zone', $columns));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $fcaZoneId
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (int $fcaZoneId, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($fcaZoneId, 'fcaZoneId');
            parent::checkMandatoryArray($columns, array('siteId', 'fcaZoneName', 'fcaZoneStatus'), true);
            DbMysql::update('fca_zone', $columns, array('fcaZoneId'=>$fcaZoneId));
            $this->set($fcaZoneId);
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
            parent::checkEmptyInteger($this->fcaZoneId, 'fcaZoneId');
            if (DbMysql::count('fca_task', array('fcaZoneId'=>$this->fcaZoneId)) > 0) {
                throw new Exception(str_replace('__', $this->fcaZoneName, Constant::$fcaZone['errStillExist']), 31);
            }
            DbMysql::delete('fca_zone', array('fcaZoneId'=>$this->fcaZoneId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}