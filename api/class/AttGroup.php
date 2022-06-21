<?php

class AttGroup extends General {

    public $attGroupId = 0;
    public $attGroup = array();
    public $siteName = '';

    function __construct () {
    }

    /**
     * @param int $attGroupId
     * @return void
     * @throws Exception
     */
    public function set (int $attGroupId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attGroupId, 'attGroupId');
            $this->attGroupId = $attGroupId;
            $this->attGroup = DbMysql::select('v_att_group', array('attGroupId'=>$this->attGroupId),true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return array
     * @throws Exception
     */
    public function getList (int $siteId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            return DbMysql::selectAll('v_att_group', array('siteId'=>$siteId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRef (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return DbMysql::selectAll('v_att_group', array(), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return array
     * @throws Exception
     */
    public function getAttSite (int $siteId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            return DbMysql::select('v_att_site', array('siteId'=>$siteId), true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAttSiteList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            return DbMysql::selectAll('v_att_site');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return void
     * @throws Exception
     */
    public function setSiteName (int $siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            $this->siteName = DbMysql::selectColumn('cli_site', array('siteId'=>$siteId), 'siteName', true);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return void
     * @throws Exception
     */
    public function activate (int $siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            if (DbMysql::count('cli_site', array('siteId'=>$siteId, 'siteIsAttendance'=>1)) > 0) {
                throw new Exception(str_replace('__', $this->siteName, Constant::$attGroup['errSiteAlreadyEnabled']), 31);
            }
            DbMysql::update('cli_site', array('siteIsAttendance'=>1), array('siteId'=>$siteId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @return void
     * @throws Exception
     */
    public function deactivate (int $siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            if (DbMysql::count('cli_site', array('siteId'=>$siteId, 'siteIsAttendance'=>0)) > 0) {
                throw new Exception(str_replace('__', $this->siteName, Constant::$attGroup['errSiteAlreadyDisabled']), 31);
            }
            DbMysql::update('cli_site', array('siteIsAttendance'=>0), array('siteId'=>$siteId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}