<?php

class AttGroup extends General {

    public $attGroupId = 0;
    public $attGroup = array();
    public $siteName = '';
    private $sqlAttGroup = /** @lang text */
        "SELECT
            a.att_group_id,
            site_id,
            att_group_name,
            att_group_category,
            att_group_supervisor,
            ST_AsGeoJSON(att_group_polygon) AS att_group_polygon, 
            ST_X(att_group_map_center) AS att_group_map_center_lat,
            ST_Y(att_group_map_center) AS att_group_map_center_lng,
            att_group_map_zoom,
            TIME_FORMAT(att_group_day_shift_start,'%h:%i %p') AS att_group_day_shift_start,
            TIME_FORMAT(att_group_day_shift_end,'%h:%i %p') AS att_group_day_shift_end,
            TIME_FORMAT(att_group_night_shift_start,'%h:%i %p') AS att_group_night_shift_start,
            TIME_FORMAT(att_group_night_shift_end,'%h:%i %p') AS att_group_night_shift_end,
            TIME_FORMAT(att_group_day_shift_start,'%H:%i') AS att_group_day_shift_start_2,
            TIME_FORMAT(att_group_day_shift_end,'%H:%i') AS att_group_day_shift_end_2,
            TIME_FORMAT(att_group_night_shift_start,'%H:%i') AS att_group_night_shift_start_2,
            TIME_FORMAT(att_group_night_shift_end,'%H:%i') AS att_group_night_shift_end_2,
            att_group_holiday,
            att_group_req_week_hours,
            att_group_shift_mode, 
            att_group_ot_approver,
            att_group_remark,
            att_group_status,
            p.total_active AS total_participant_active
        FROM att_group a
        LEFT JOIN (
            SELECT att_group_id, COUNT(*) AS total_active
            FROM att_participant GROUP BY att_group_id
        ) p ON p.att_group_id = a.att_group_id";
    private $sqlAttSite = /** @lang text */
        "SELECT 
            s.*,
            IFNULL(ag.total, 0) AS total_group,
            IFNULL(ap.total, 0) AS total_participant	
        FROM cli_site s
        LEFT JOIN (SELECT site_id, COUNT(*) AS total FROM att_group WHERE att_group_status = 1 GROUP BY site_id) ag ON ag.site_id = s.site_id
        LEFT JOIN (SELECT site_id, COUNT(*) AS total FROM att_participant 
        LEFT JOIN att_group ON att_group.att_group_id = att_participant.att_group_id
        WHERE att_participant_status = 1 
        GROUP BY site_id) ap ON ap.site_id = s.site_id";

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
            $this->attGroup = DbMysql::selectSql($this->sqlAttGroup, array('attGroupId'=>$this->attGroupId),true);
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
            return DbMysql::selectSqlAll($this->sqlAttGroup, array('siteId'=>$siteId));
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
            return DbMysql::selectSqlAll($this->sqlAttGroup, array(), 1);
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
            return DbMysql::selectSql($this->sqlAttSite, array('siteId', $siteId), true);
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
            return DbMysql::selectSqlAll($this->sqlAttSite);
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