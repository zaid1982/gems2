<?php

class Class_att_participant {

    private $constant;
    private $fn_general;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else {
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
        }
    }

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $attParticipantId
     * @return string
     * @throws Exception
     */
    public function getAttParticipant ($attParticipantId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($attParticipantId));
            return Class_db::getInstance()->db_select_single2('att_participant', array('att_participant_id'=>$attParticipantId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @return string
     * @throws Exception
     */
    public function getAttParticipantByUserId ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));
            return Class_db::getInstance()->db_select_single2('att_participant', array('user_id'=>$userId, 'att_participant_status'=>'1'));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return string
     * @throws Exception
     */
    public function getAttParticipantSite($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($siteId));
            return Class_db::getInstance()->db_select2('vw_att_participant_site', array('site_id'=>$siteId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function addAttParticipant ($params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParamsArray($params, array('userId', 'attGroupId', 'attParticipantCategory', 'attParticipantReqWeekHours', 'attParticipantShiftMode', 'attParticipantShift', 'attParticipantHoliday', 'attParticipantStatus',
                'userContactNo', 'userEmail', 'designationId'));

            $attParticipantArr = $this->fn_general->convertToMysqlArr($params, array('userId', 'attParticipantGfId', 'attParticipantYearService', 'attParticipantCidbCardExpiry', 'attParticipantCompetency', 'attGroupId', 'attParticipantCategory', 'attParticipantReqWeekHours',
                'attParticipantShiftMode', 'attParticipantShift', 'attParticipantHoliday', 'attParticipantStatus'));
            $userProfileArr = $this->fn_general->convertToMysqlArr($params, array('userContactNo', 'userEmail', 'designationId'));
            Class_db::getInstance()->db_update('sys_user_profile', $userProfileArr, array('user_id'=>$params['userId']));
            return Class_db::getInstance()->db_insert('att_participant', $attParticipantArr);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $attParticipantId
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function updateAttParticipant ($attParticipantId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($attParticipantId));
            $this->fn_general->checkEmptyParamsArray($params, array('userId', 'attGroupId', 'attParticipantCategory', 'attParticipantReqWeekHours', 'attParticipantShiftMode', 'attParticipantShift', 'attParticipantHoliday', 'attParticipantStatus',
                'userContactNo', 'userEmail', 'designationId'));

            $attParticipantArr = $this->fn_general->convertToMysqlArr($params, array('attParticipantGfId', 'attParticipantYearService', 'attParticipantCidbCardExpiry', 'attParticipantCompetency', 'attGroupId', 'attParticipantCategory', 'attParticipantReqWeekHours',
                'attParticipantShiftMode', 'attParticipantShift', 'attParticipantHoliday', 'attParticipantStatus'));
            $userProfileArr = $this->fn_general->convertToMysqlArr($params, array('userContactNo', 'userEmail', 'designationId'));
            Class_db::getInstance()->db_update('att_participant', $attParticipantArr, array('att_participant_id'=>$attParticipantId));
            Class_db::getInstance()->db_update('sys_user_profile', $userProfileArr, array('user_id'=>$params['userId']));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}