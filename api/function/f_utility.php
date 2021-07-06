<?php

class Class_utility {

    private $fn_general;
    private $constant;

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
     * @param $userId
     * @param string $type
     * @param string $readingType
     * @return string
     * @throws Exception
     */
    public function getUtilityMobileList($userId, $type = '', $readingType='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);
            return Class_db::getInstance()->db_select2('vw_utility_mobile_list', array('u.site_id'=>$siteId, 'u.utility_type'=>$type, 'u.utility_reading_type'=>$readingType));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $type
     * @param $readingType
     * @param $userId
     * @param array $params
     * @param string $uploadId
     * @return string
     * @throws Exception
     */
    public function addUtility ($type, $readingType, $userId, $params=array(), $uploadId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($type, $readingType, $userId, $params));
            $this->fn_general->checkEmptyParamsArray($params, array('meterId', 'utilityDate', 'utilityReading'));
            if (Class_db::getInstance()->db_count('sys_user_role', array('user_id'=>$userId, 'role_id'=>'18')) == 0) {
                throw new Exception('[' . __LINE__ . '] - User not allowed to take utility reading.', 31);
            }
            if ($type !== 'Electricity' && $type !== 'Water') {
                throw new Exception('[' . __LINE__ . '] - Invalid reading type.');
            }
            if ($readingType !== 'Daily' && $readingType !== 'Monthly') {
                throw new Exception('[' . __LINE__ . '] - Invalid reading type.');
            }
            if ($readingType === 'Daily' && Class_db::getInstance()->db_count('utl_utility', array('meter_id'=>$params['meterId'], 'utility_date'=>$params['utilityDate'], 'utility_type'=>$type, 'utility_reading_type'=>'Daily')) > 0) {
                throw new Exception('[' . __LINE__ . '] - The type of reading for the reading date already recorded.', 31);
            }

            $unit = '';
            $totalRm = 0.0;
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);
            if ($type === 'Electricity') {
                $unit = 'kWh';
                $totalRm = floatval($params['utilityReading']) * 0.365;
            }
            if ($type === 'Water') {
                $unit = 'm^3';
                $totalRm = floatval($params['utilityReading']) * 2.07;
            }
            $insertParams = array_merge(
                $this->fn_general->convertToMysqlArr($params, array('meterId', 'utilityDate', 'utilityReading', 'utilityMaxDemand')),
                array('siteId'=>$siteId, 'utilityType'=>$type, 'utilityReadingType'=>$readingType, 'utilityUnit'=>$unit, 'utilityTotalRm'=>strval($totalRm), 'utilityImage'=>$uploadId, 'utilityRecordedBy'=>$userId)
            );
            return Class_db::getInstance()->db_insert('utl_utility', $this->fn_general->convertToMysqlArrAll($insertParams));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}