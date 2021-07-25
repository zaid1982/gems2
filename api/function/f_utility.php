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
     * @param string $meterId
     * @return string
     * @throws Exception
     */
    public function getUtilityMobileList($userId, $type = '', $readingType='', $meterId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);
            return Class_db::getInstance()->db_select2('vw_utility_mobile_list', array('u.site_id'=>$siteId, 'u.utility_type'=>$type, 'u.utility_reading_type'=>$readingType, 'm.meter_id'=>$meterId));
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
            if ($type === 'Electricity' && $readingType === 'Daily' && Class_db::getInstance()->db_count('utl_utility', array('meter_id'=>$params['meterId'], 'utility_date'=>$params['utilityDate'], 'utility_type'=>$type, 'utility_reading_type'=>'Daily')) > 0) {
                throw new Exception('[' . __LINE__ . '] - Today\'s daily reading for Electricity already recorded.', 31);
            }

            $opening = '';
            $total = '';
            $shift = '';
            $unit = $type === 'Electricity' ? 'kWh' : 'm^3';
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', '', 1);
            if ($readingType === 'Daily') {
                if ($type === 'Water') {
                    $utilityShiftZone = Class_db::getInstance()->db_select_single2('vw_utility_shift', array(), '', 1);
                    $shift = $utilityShiftZone['readingShift'];
                    $params['utilityDate'] = $utilityShiftZone['readingDate'];
                    if (Class_db::getInstance()->db_count('utl_utility', array('meter_id'=>$params['meterId'], 'utility_reading_type'=>'Daily', 'utility_type'=>'Water', 'utility_date'=>$params['utilityDate'], 'utility_shift'=>$shift)) > 0) {
                        throw new Exception('[' . __LINE__ . '] - Today\'s ' . $shift . ' shift reading for Water already recorded.', 31);
                    }
                    $previousReading = Class_db::getInstance()->db_select_single2('utl_utility', array('meter_id'=>$params['meterId'], 'utility_type'=>$type, 'utility_reading_type'=>'Daily'), 'utility_timestamp DESC');
                    if (!empty($previousReading)) {
                        $totalTemp = strval(floatval($params['utilityReading']) - floatval($previousReading['utilityReading']));
                        Class_db::getInstance()->db_update('utl_utility', array('utility_total'=>$totalTemp, array('utility_id'=>$previousReading['utilityId'])));
                    }
                } else {
                    $opening = Class_db::getInstance()->db_select_col('utl_utility', array('meter_id'=>$params['meterId'], 'utility_type'=>$type, 'utility_reading_type'=>'Daily'), 'utility_reading', 'utility_timestamp DESC');
                    $total = strval(floatval($params['utilityReading']) - floatval($opening));
                }
            }
            $insertParams = array_merge(
                $this->fn_general->convertToMysqlArr($params, array('meterId', 'utilityDate', 'utilityReading', 'utilityMaxDemand', 'utilityTotalRm')),
                array('siteId'=>$siteId, 'utilityType'=>$type, 'utilityReadingType'=>$readingType, 'utilityUnit'=>$unit, 'utilityOpening'=>$opening, 'utilityTotal'=>$total, 'utilityShift'=>$shift,
                    'utilityImage'=>$uploadId, 'utilityRecordedBy'=>$userId)
            );
            return Class_db::getInstance()->db_insert('utl_utility', $this->fn_general->convertToMysqlArrAll($insertParams));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getUtilityMonthlyElectricityAnalyzed($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $results = array();
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);
            if ($siteId === '7') {
                $utilityUsageRate = 0.324;
                $utilityMaxDemand = 6078;
                $utilityMaxDemandRate = 23.2;
                $cajSambunganBebanRate = 8.500;
                $kwtbbPerc = 1.6;
            }

            $previousCharges = 0;
            $analyzedMonthlyUtilities = Class_db::getInstance()->db_select2('vw_utility_monthly_electricity_analyzed', array(), '', '', 0, array('siteId'=>$siteId, 'andQuery'=>''));
            foreach ($analyzedMonthlyUtilities AS $analyzedMonthlyUtility) {
                $row = $analyzedMonthlyUtility;
                if ($siteId === '7') {
                    $row['utilityUsageRate'] = strval($utilityUsageRate);
                    $row['utilityUsageRm'] = strval($utilityUsageRate * floatval($row['utilityTotalKwh']));
                    $row['utilityMaxDemand'] = strval($utilityMaxDemand);
                    $row['utilityMaxDemandRate'] = strval($utilityMaxDemandRate);
                    $row['utilityMaxDemandRm'] = strval($utilityMaxDemandRate * floatval($row['utilityActualMaxDemand']));
                    $row['cajSambunganBeban'] = strval($utilityMaxDemand - floatval($row['utilityActualMaxDemand']));
                    $row['cajSambunganBebanRate'] = strval($cajSambunganBebanRate);
                    $row['cajSambunganBebanRm'] = strval($cajSambunganBebanRate * floatval($row['cajSambunganBeban']));
                    $row['electricityAmountRm'] = strval(floatval($row['utilityUsageRm']) + floatval($row['utilityMaxDemandRm']) + floatval($row['cajSambunganBebanRm']));
                    $row['kwtbbPerc'] = strval($kwtbbPerc);
                    $row['kwtbbRm'] = strval($kwtbbPerc/100 * (floatval($row['utilityUsageRm']) + floatval($row['utilityMaxDemandRm'])));
                    $row['electricityBillRm'] = strval(round(floatval($row['electricityAmountRm']) + floatval($row['kwtbbRm']),2));
                    $row['changePerc'] = $previousCharges > 0 ? strval(($previousCharges - floatval($row['electricityBillRm']))/$previousCharges*100) : '0';
                    $previousCharges = floatval($row['electricityBillRm']);
                }
                array_push($results, $row);
            }
            return $results;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getUtilityMonthlyWaterAnalyzed($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));

            $results = array();
            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id', null, 1);

            $previousCharges = 0;
            $analyzedMonthlyUtilities = Class_db::getInstance()->db_select2('vw_utility_monthly_water_analyzed', array(), '', '', 0, array('siteId'=>$siteId));
            foreach ($analyzedMonthlyUtilities AS $analyzedMonthlyUtility) {
                $row = $analyzedMonthlyUtility;
                $row['utilityTotalUsageRm'] = strval(round(floatval($row['utilityTotalUsage']) * 1.2,2));
                $row['changePerc'] = $previousCharges > 0 ? strval(($previousCharges - floatval($row['utilityTotalUsageRm']))/$previousCharges*100) : '0';
                $previousCharges = floatval($row['utilityTotalUsageRm']);
                array_push($results, $row);
            }
            return $results;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $utilityType
     * @param $siteId
     * @param $year
     * @param $month
     * @return array
     * @throws Exception
     */
    public function getUtilityDailyAnalyzed($utilityType, $siteId, $year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($utilityType, $siteId));

            $results = array();
            $previousCharges = 0;
            $utilities = Class_db::getInstance()->db_select2('utl_utility', array('utility_type'=>$utilityType, 'site_id'=>$siteId, 'YEAR(utility_date)'=>$year, 'MONTH(utility_date)'=>$month));
            foreach ($utilities AS $utility) {
                $row = $utility;
                $row['changePerc'] = $previousCharges > 0 ? strval(($previousCharges - floatval($row['utilityTotal']))/$previousCharges*100) : '0';
                $previousCharges = floatval($row['utilityTotal']);
                array_push($results, $row);
            }
            return $results;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}