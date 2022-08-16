<?php

class AttParticipant extends General {

    public $attParticipantId = 0;
    public $attParticipantName = '';
    public $attGroupName = '';

    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @param int $attParticipantId
     * @return array
     * @throws Exception
     */
    public function get(int $attParticipantId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($attParticipantId, 'attParticipantId');
            return DbMysql::select('att_participant', array('attParticipantId'=>$attParticipantId), 1);
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $siteId
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getListSite (int $siteId, int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            return DbMysql::selectSqlAll(/** @lang text */
                "SELECT
                        u.user_first_name,
                        up.user_contact_no,
                        up.user_email,
                        d.designation_desc,
                        IFNULL(p.att_participant_status, 52) AS participant_status,   
                        g.att_group_supervisor,
                        u.user_id AS user_ids,
                        SUM(IF(att_transaction_result = 'Present' OR (att_transaction_date < CURDATE() AND y.att_type_mode IN ('Normal', '2 Shifts', '3 Shifts') AND att_transaction_status <> 'Ready'), 1, 0)) AS total_present,
                        SUM(IF(att_transaction_result = 'Absent' OR (att_transaction_date < CURDATE() AND y.att_type_mode IN ('Normal', '2 Shifts', '3 Shifts') AND att_transaction_status = 'Ready'), 1, 0)) AS total_absent,
                        SUM(IF(y.att_type_id = 7, 1, 0)) AS total_mc,
                        SUM(IF(y.att_type_id = 6, 1, 0)) AS total_al,
                        SUM(IF(y.att_type_id = 5, 1, 0)) AS total_od,
                        SUM(IF(y.att_type_id = 4, 1, 0)) AS total_rd,
                        SUM(IF(y.att_type_id = 14, 1, 0)) AS total_ph,
                        SUM(IF(y.att_type_id = 8, 1, 0)) AS total_training,
                        FLOOR(SUM(TO_SECONDS(att_transaction_time_out) - TO_SECONDS(att_transaction_time_in)) / 3600) AS total_hours,
                        SUM(IF(att_transaction_time_in IS NOT NULL AND att_transaction_time_in > att_transaction_shift_start, 1, 0)) AS total_in_late,
                        SUM(IF(att_transaction_time_out IS NOT NULL AND att_transaction_time_out < att_transaction_shift_end, 1, 0)) AS total_out_early,
                        SUM(IF(att_transaction_location_in IS NOT NULL AND ST_CONTAINS(g.att_group_polygon, att_transaction_location_in) = FALSE, 1, 0) + IF(att_transaction_location_out IS NOT NULL AND ST_CONTAINS(g.att_group_polygon, att_transaction_location_out) = FALSE, 1, 0)) AS total_outside,
                        p.*
                    FROM sys_user u
                    LEFT JOIN att_participant p ON p.user_id = u.user_id
                    LEFT JOIN sys_user_profile up ON up.user_id = u.user_id
                    LEFT JOIN ref_designation d ON d.designation_id = up.designation_id
                    LEFT JOIN att_transaction t ON t.att_participant_id = p.att_participant_id AND YEAR(t.att_transaction_date) = $year AND MONTH(t.att_transaction_date) = $month
                    LEFT JOIN att_type y ON y.att_type_id = t.att_type_id
                    LEFT JOIN att_group g ON g.att_group_id = p.att_group_id
                    WHERE u.site_id = $siteId
                    GROUP BY u.user_id");
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attGroupId
     * @param int $year
     * @param int $month
     * @return array
     * @throws Exception
     */
    public function getListGroup (int $attGroupId, int $year, int $month): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($attGroupId, 'attGroupId');
            parent::checkEmptyInteger($year, 'year');
            parent::checkEmptyInteger($month, 'month');
            return DbMysql::selectSqlAll(/** @lang text */
                "SELECT
                        u.user_first_name,
                        up.user_contact_no,
                        up.user_email,
                        d.designation_desc,
                        IFNULL(p.att_participant_status, 52) AS participant_status,   
                        g.att_group_supervisor,
                        u.user_id AS user_ids,
                        SUM(IF(att_transaction_result = 'Present' OR (att_transaction_date < CURDATE() AND y.att_type_mode IN ('Normal', '2 Shifts', '3 Shifts') AND att_transaction_status <> 'Ready'), 1, 0)) AS total_present,
                        SUM(IF(att_transaction_result = 'Absent' OR (att_transaction_date < CURDATE() AND y.att_type_mode IN ('Normal', '2 Shifts', '3 Shifts') AND att_transaction_status = 'Ready'), 1, 0)) AS total_absent,
                        SUM(IF(y.att_type_id = 7, 1, 0)) AS total_mc,
                        SUM(IF(y.att_type_id = 6, 1, 0)) AS total_al,
                        SUM(IF(y.att_type_id = 5, 1, 0)) AS total_od,
                        SUM(IF(y.att_type_id = 4, 1, 0)) AS total_rd,
                        SUM(IF(y.att_type_id = 14, 1, 0)) AS total_ph,
                        SUM(IF(y.att_type_id = 8, 1, 0)) AS total_training,
                        FLOOR(SUM(TO_SECONDS(att_transaction_time_out) - TO_SECONDS(att_transaction_time_in)) / 3600) AS total_hours,
                        SUM(IF(att_transaction_time_in IS NOT NULL AND att_transaction_time_in > att_transaction_shift_start, 1, 0)) AS total_in_late,
                        SUM(IF(att_transaction_time_out IS NOT NULL AND att_transaction_time_out < att_transaction_shift_end, 1, 0)) AS total_out_early,
                        SUM(IF(att_transaction_location_in IS NOT NULL AND ST_CONTAINS(g.att_group_polygon, att_transaction_location_in) = FALSE, 1, 0) + IF(att_transaction_location_out IS NOT NULL AND ST_CONTAINS(g.att_group_polygon, att_transaction_location_out) = FALSE, 1, 0)) AS total_outside,
                        p.*
                    FROM att_participant p
                    LEFT JOIN sys_user u ON u.user_id = p.user_id
                    LEFT JOIN sys_user_profile up ON up.user_id = u.user_id
                    LEFT JOIN ref_designation d ON d.designation_id = up.designation_id
                    LEFT JOIN att_transaction t ON t.att_participant_id = p.att_participant_id AND YEAR(t.att_transaction_date) = $year AND MONTH(t.att_transaction_date) = $month
                    LEFT JOIN att_type y ON y.att_type_id = t.att_type_id
                    LEFT JOIN att_group g ON g.att_group_id = p.att_group_id
                    WHERE p.att_group_id = $attGroupId
                    GROUP BY p.att_participant_id");
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $attParticipantId
     * @throws Exception
     */
    public function set (int $attParticipantId): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyInteger($attParticipantId, 'attParticipantId');
            $attParticipant = $this->get($attParticipantId);
            $this->attParticipantId = $attParticipantId;
            $this->attParticipantName = DbMysql::selectColumn('sys_user', array('userId'=>$attParticipant['userId']),'userFirstName', true);
            $this->attGroupName = DbMysql::selectColumn('att_group', array('attGroupId'=>$attParticipant['attGroupId']),'attGroupName', true);
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
            parent::checkMandatoryArray($columns, array('userId', 'attGroupId', 'assetGroupId', 'attParticipantReqWeekHours', 'attParticipantShiftMode', 'attTypeId', 'attParticipantHoliday', 'attParticipantStatus',
                'userContactNo', 'userEmail', 'designationId'), true);
            if (DbMysql::count('att_participant', array('userId'=>$columns['userId'], 'attGroupId'=>$columns['attGroupId'])) > 0
                || DbMysql::count('att_participant', array('userId'=>$columns['userId'], 'attParticipantStatus'=>1)) > 0) {
                $userFirstName = DbMysql::selectColumn('sys_user', array('userId'=>$columns['userId']), 'userFirstName', true);
                $attGroupId = DbMysql::selectColumn('att_participant', array('userId'=>$columns['userId'], 'attParticipantStatus'=>1), 'attGroupId', true);
                $attGroupName = DbMysql::selectColumn('att_group', array('attGroupId'=>$attGroupId), 'attGroupName', true);
                $errorMsg = str_replace('_1', $userFirstName, Constant::$attParticipant['errAlreadyAssigned']);
                throw new Exception(str_replace('_2', $attGroupName, $errorMsg), 31);
            }
            DbMysql::update('sys_user_profile', parent::arraySpliceAssoc($columns, array('userContactNo', 'userEmail', 'designationId')), array('userId'=>$columns['userId']));
            $this->set(DbMysql::insert('att_participant', parent::arraySpliceAssoc($columns, array('userId', 'attParticipantGfId', 'attParticipantYearService', 'attParticipantCidbCardExpiry', 'attParticipantCompetency', 'attGroupId', 'assetGroupId', 'attParticipantReqWeekHours',
                'attParticipantShiftMode', 'attTypeId', 'attParticipantHoliday', 'attParticipantStatus'))));
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $attParticipant
     * @param array $columns
     * @return void
     * @throws Exception
     */
    public function update (array $attParticipant, array $columns): void {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            parent::checkEmptyArray($attParticipant, 'attParticipant');
            parent::checkMandatoryArray($columns, array('userId', 'attGroupId', 'assetGroupId', 'attParticipantReqWeekHours', 'attParticipantShiftMode', 'attTypeId', 'attParticipantHoliday', 'attParticipantStatus',
                'userContactNo', 'userEmail', 'designationId'), true);
            $attParticipantId = $attParticipant['attParticipantId'];
            if (intval($columns['attParticipantStatus']) === 1 && $attParticipant['attGroupId'] !== intval($columns['attGroupId'])
                && DbMysql::count('att_participant', array('userId'=>$columns['userId'], 'attParticipantId'=>'<>|'.$attParticipantId, 'attParticipantStatus'=>1)) > 0) {
                $userFirstName = DbMysql::selectColumn('sys_user', array('userId'=>$columns['userId']), 'userFirstName', true);
                $attGroupId = DbMysql::selectColumn('att_participant', array('userId'=>$columns['userId'], 'attParticipantId'=>'<>|'.$attParticipantId, 'attParticipantStatus'=>1), 'attGroupId', true);
                $attGroupName = DbMysql::selectColumn('att_group', array('attGroupId'=>$attGroupId), 'attGroupName', true);
                $errorMsg = str_replace('_1', $userFirstName, Constant::$attParticipant['errAlreadyAssigned']);
                throw new Exception(str_replace('_2', $attGroupName, $errorMsg), 31);
            }
            DbMysql::update('sys_user_profile', parent::arraySpliceAssoc($columns, array('userContactNo', 'userEmail', 'designationId')), array('userId'=>$columns['userId']));
            DbMysql::update('att_participant', parent::arraySpliceAssoc($columns, array('attParticipantGfId', 'attParticipantYearService', 'attParticipantCidbCardExpiry', 'attParticipantCompetency', 'attGroupId', 'assetGroupId', 'attParticipantReqWeekHours',
                'attParticipantShiftMode', 'attTypeId', 'attParticipantHoliday', 'attParticipantStatus')), array('attParticipantId'=>$attParticipantId));
            $this->set($attParticipantId);
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}