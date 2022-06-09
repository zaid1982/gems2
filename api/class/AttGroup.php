<?php

class AttGroup extends General {

    public $attGroupId = '';
    public $attGroup = array();
    public $siteName = '';

    function __construct () {
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAttGroupRef (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            return DbMysql::selectAll('v_att_group', array(), 1);
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
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
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($siteId);
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
    public function activateAttSite (int $siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($siteId);
            if (DbMysql::count('cli_site', array('siteId'=>$siteId, 'siteIsAttendance'=>1)) > 0) {
                throw new Exception(str_replace('__', $this->siteName, Constant::$attGroupErr['siteAlreadyEnabled']), 31);
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
    public function deactivateAttSite (int $siteId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            parent::checkEmptyInteger($siteId);
            if (DbMysql::count('cli_site', array('siteId'=>$siteId, 'siteIsAttendance'=>0)) > 0) {
                throw new Exception(str_replace('__', $this->siteName, Constant::$attGroupErr['siteAlreadyDisabled']), 31);
            }
            DbMysql::update('cli_site', array('siteIsAttendance'=>0), array('siteId'=>$siteId));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}