<?php

class AttGroup extends General {

    public $attGroupId = 0;
    public $attGroupName = '';
    public $attGroup = array();
    public $siteName = '';
    private $sqlAttGroup = /** @lang text */
        "SELECT
            a.att_group_id,
            site_id,
            att_group_name,
            asset_group_id,
            att_group_supervisor,
            ST_AsGeoJSON(att_group_polygon) AS att_group_polygon, 
            ST_X(att_group_map_center) AS att_group_map_center_lat,
            ST_Y(att_group_map_center) AS att_group_map_center_lng,
            att_group_map_zoom,
            CONCAT(TIME_FORMAT(att_group_normal_start,'%h:%i %p'), ' to ', TIME_FORMAT(att_group_normal_end,'%h:%i %p')) AS att_group_normal_hours,
            CONCAT(TIME_FORMAT(att_group_am_start,'%h:%i %p'), ' to ', TIME_FORMAT(att_group_am_end,'%h:%i %p')) AS att_group_am_hours,
            CONCAT(TIME_FORMAT(att_group_pm_start,'%h:%i %p'), ' to ', TIME_FORMAT(att_group_pm_end,'%h:%i %p')) AS att_group_pm_hours,
            CONCAT(TIME_FORMAT(att_group_morning_start,'%h:%i %p'), ' to ', TIME_FORMAT(att_group_morning_end,'%h:%i %p')) AS att_group_morning_hours,
            CONCAT(TIME_FORMAT(att_group_evening_start,'%h:%i %p'), ' to ', TIME_FORMAT(att_group_evening_end,'%h:%i %p')) AS att_group_evening_hours,
            CONCAT(TIME_FORMAT(att_group_night_start,'%h:%i %p'), ' to ', TIME_FORMAT(att_group_night_end,'%h:%i %p')) AS att_group_night_hours,
            att_group_normal_start, att_group_normal_end,
            att_group_am_start, att_group_am_end,
            att_group_pm_start, att_group_pm_end,
            att_group_morning_start, att_group_morning_end,
            att_group_evening_start, att_group_evening_end,
            att_group_night_start, att_group_night_end,
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

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $siteId
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getChartsSite (int $siteId, int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');

            $absentArr = array();
            $punctualArr = array();
            $insideArr = array();
            $dataArr = DbMysql::selectSqlAll(
                /** @lang text */
                "SELECT 
                    g.att_group_name, 
                    COUNT(*) AS total,
                    ROUND(SUM(IF(att_transaction_result = 'Present' OR (att_transaction_date < CURDATE() AND att_transaction_status <> 'Ready'), 1, 0)) / COUNT(*) * 100, 2) AS absent,
                    ROUND(SUM(IF(att_transaction_time_in IS NOT NULL AND att_transaction_time_in <= att_transaction_shift_start, 1, 0)) / COUNT(*) * 100, 2) AS punctual,
                    ROUND(SUM(ST_CONTAINS(g.att_group_polygon, att_transaction_location_in) + ST_CONTAINS(g.att_group_polygon, att_transaction_location_out)) / COUNT(*) * 50, 2) AS inside
                FROM att_transaction t
                LEFT JOIN att_group g ON g.att_group_id = t.att_group_id 
                WHERE g.site_id = $siteId AND att_type_id IN (1,2,3,9,10,11) AND YEAR(att_transaction_date) = $year AND MONTH(att_transaction_date) = $month AND DATE(att_transaction_date) <= CURDATE()
                GROUP BY t.att_group_id", array());
            foreach ($dataArr AS $data) {
                $attGroupName = $data['attGroupName'];
                $absentArr[] = array($attGroupName, floatval($data['absent']));
                $punctualArr[] = array($attGroupName, floatval($data['punctual']));
                $insideArr[] = array($attGroupName, floatval($data['inside']));
            }
            return array($absentArr, $punctualArr, $insideArr);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
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
            $this->attGroup = DbMysql::selectSql($this->sqlAttGroup, array('a.attGroupId'=>$this->attGroupId),true);
            $this->attGroupName = $this->attGroup['attGroupName'];
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
            return DbMysql::selectSql($this->sqlAttSite, array('s.siteId'=>$siteId), true);
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
     * @return array
     * @throws Exception
     */
    public function getSupervisorList (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($this->userId, 'userId');
            $supervisorArr = array();
            $attGroupArr = DbMysql::selectAll('att_group', array('attGroupSupervisor'=>$this->userId), 0, false, 'attGroupName');
            foreach ($attGroupArr as $attGroup) {
                $supervisorArr[] = $attGroup['attGroupId'];
            }
            return $supervisorArr;
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

    /**
     * @param array $params
     * @param array $maps
     * @return array
     * @throws Exception
     */
    private function getAttGroupParams (array $params, array $maps): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkMandatoryArray($params,  array('siteId', 'attGroupName', 'attGroupSupervisor', 'assetGroupId', 'attGroupHoliday', 'attGroupReqWeekHours', 'attGroupShiftMode', 'attGroupNormalStart', 'attGroupNormalEnd',
                'attGroupAmStart', 'attGroupAmEnd', 'attGroupPmStart', 'attGroupPmEnd', 'attGroupMorningStart', 'attGroupMorningEnd', 'attGroupEveningStart', 'attGroupEveningEnd', 'attGroupNightStart', 'attGroupNightEnd', 'attGroupOtApprover',
                'attGroupStatus'));
            parent::checkMandatoryArray($maps, array('mapCenter', 'zoomLevel'));

            if (array_key_exists('coordinates',$maps) && !empty($maps['coordinates'])) {
                $params['attGroupMapCenter'] = "|ST_GEOMFROMTEXT('POINT".str_replace(',', ' ', $maps['mapCenter'])."')";
                $params['attGroupMapZoom'] = $maps['zoomLevel'];
                $coordinateStr = '';
                foreach ($maps['coordinates'] as $coordinates) {
                    $coordinateStr .= $coordinates['lat'].' '.$coordinates['lng'].',';
                }
                $coordinateStr .= $maps['coordinates'][0]['lat'].' '.$maps['coordinates'][0]['lng'];
                $params['attGroupPolygon'] = "|ST_GEOMFROMTEXT('POLYGON((".$coordinateStr."))')";
            }
            return $params;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @param array $maps
     * @return void
     * @throws Exception
     */
    public function insert (array $params, array $maps): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (!array_key_exists('coordinates', $maps) || empty($maps['coordinates'])) {
                throw new Exception('Please draw the work area on the map before submitting.', 31);
            }
            if (DbMysql::count('att_group', parent::arraySpliceAssoc($params, array('siteId', 'attGroupName'))) > 0) {
                throw new Exception(str_replace('__', $params['attGroupName'], Constant::$attGroup['errAlreadyExist']), 31);
            }
            $this->set(DbMysql::insert('att_group', $this->getAttGroupParams($params, $maps)));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attGroupId
     * @param array $params
     * @param array $maps
     * @return void
     * @throws Exception
     */
    public function update (int $attGroupId, array $params, array $maps): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attGroupId, 'attGroupId');
            DbMysql::update('att_group', $this->getAttGroupParams($params, $maps), array('attGroupId'=>$attGroupId));
            $this->set($attGroupId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}