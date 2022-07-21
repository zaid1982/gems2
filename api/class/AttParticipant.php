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
     * @return array
     * @throws Exception
     */
    public function getListSite(int $siteId): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($siteId, 'siteId');
            return DbMysql::selectSqlAll(/** @lang text */ "SELECT
                    u.user_first_name,
                    up.user_contact_no,
                    up.user_email,
                    d.designation_desc,
                    IFNULL(p.att_participant_status, 52) AS participant_status,   
                    u.user_id AS user_ids,
                    p.*
                FROM sys_user u
                LEFT JOIN att_participant p ON p.user_id = u.user_id
                LEFT JOIN sys_user_profile up ON up.user_id = u.user_id
                LEFT JOIN ref_designation d ON d.designation_id = up.designation_id", array('siteId'=>$siteId));
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