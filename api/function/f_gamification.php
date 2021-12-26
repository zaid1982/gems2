<?php

class Class_gamification {

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
     * @param $gmiId
     * @return string
     * @throws Exception
     */
    public function getGmiMonthly ($gmiId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($gmiId));
            return Class_db::getInstance()->db_select_single2('gmi_monthly', array('gmi_id'=>$gmiId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $year
     * @param $month
     * @return string
     * @throws Exception
     */
    public function getGmiMonthlyList ($year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($year, $month));
            return Class_db::getInstance()->db_select2('gmi_monthly', array('gmi_year'=>$year, 'gmi_month'=>$month));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $year
     * @param $month
     * @return string
     * @throws Exception
     */
    public function getGmiMonthlyTop5 ($year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($year, $month));
            return Class_db::getInstance()->db_select2('gmi_monthly', array('gmi_year'=>$year, 'gmi_month'=>$month), 'gmi_point_total DESC', '5');
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $year
     * @param $month
     * @param $userId
     * @return string
     * @throws Exception
     */
    public function getGmiMonthlyHistory ($year, $month, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($year, $month, $userId));
            return Class_db::getInstance()->db_select2('gmi_monthly', array('user_id'=>$userId, 'gmi_year'=>'<='.$year, 'w1'=>'IF(gmi_year = '.$year.', gmi_month <= '.$month.' , 1) = 1'), 'gmi_year DESC, gmi_month DESC');
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $year
     * @param $month
     * @throws Exception
     */
    public function runMonthly ($year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($year, $month));

            $gmiMonthly = array();

            $gmiPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiPpm as $ppm) {
                $userId = intval($ppm['ppmTaskAssignedTo']);
                $gmiMonthly[$userId]['userId'] = $ppm['ppmTaskAssignedTo'];
                $gmiMonthly[$userId]['gmiYear'] = $year;
                $gmiMonthly[$userId]['gmiMonth'] = $month;
                $gmiMonthly[$userId]['siteId'] = $ppm['siteId'];
                $gmiMonthly[$userId]['gmiId'] = $ppm['gmiId'];
                $gmiMonthly[$userId]['gmiPpmTotal'] = $ppm['ppmTotal'];
                $gmiMonthly[$userId]['gmiPpmCompleted'] = $ppm['ppmCompleted'];
                $gmiMonthly[$userId]['gmiPpmOnTime'] = $ppm['ppmOnTime'];
                $gmiMonthly[$userId]['gmiPpmLate'] = $ppm['ppmLate'];

                $ppmTierPoint = '0.5';
                $ppmTierName = 'Under Rated';
                if (intval($ppm['ppmCompleted']) > 150) {
                    $ppmTierPoint = '2';
                    $ppmTierName = 'Finisher';
                } else if (intval($ppm['ppmCompleted']) > 80) {
                    $ppmTierPoint = '1';
                    $ppmTierName = 'Medalist';
                }
                $gmiMonthly[$userId]['gmiPpmTierPoint'] = $ppmTierPoint;
                $gmiMonthly[$userId]['gmiPpmTierName'] = $ppmTierName;

                $gmiMonthly[$userId]['gmiWoTotal'] = '0';
                $gmiMonthly[$userId]['gmiWoCompleted'] = '0';
                $gmiMonthly[$userId]['gmiWoOnTime'] = '0';
                $gmiMonthly[$userId]['gmiWoLate'] = '0';
                $gmiMonthly[$userId]['gmiWoSelfFinding'] = '0';
                $gmiMonthly[$userId]['gmiWoTierPoint'] = '0.5';
                $gmiMonthly[$userId]['gmiWoTierName'] = 'Under Rated';
            }

            $gmiWo = Class_db::getInstance()->db_select2('vw_gamification_wo_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiWo as $wo) {
                $userId = intval($wo['woTaskAssignedTo']);
                if (!array_key_exists($userId, $gmiMonthly)) {
                    $gmiMonthly[$userId]['gmiYear'] = $year;
                    $gmiMonthly[$userId]['gmiMonth'] = $month;
                    $gmiMonthly[$userId]['gmiPpmTotal'] = '0';
                    $gmiMonthly[$userId]['gmiPpmCompleted'] = '0';
                    $gmiMonthly[$userId]['gmiPpmOnTime'] = '0';
                    $gmiMonthly[$userId]['gmiPpmLate'] = '0';
                    $gmiMonthly[$userId]['gmiPpmTierPoint'] = '0.5';
                    $gmiMonthly[$userId]['gmiPpmTierName'] = 'Under Rated';
                }

                $gmiMonthly[$userId]['userId'] = $wo['woTaskAssignedTo'];
                $gmiMonthly[$userId]['siteId'] = $wo['siteId'];
                $gmiMonthly[$userId]['gmiId'] = $wo['gmiId'];
                $gmiMonthly[$userId]['gmiWoTotal'] = $wo['woTotal'];
                $gmiMonthly[$userId]['gmiWoCompleted'] = $wo['woCompleted'];
                $gmiMonthly[$userId]['gmiWoOnTime'] = $wo['woOnTime'];
                $gmiMonthly[$userId]['gmiWoLate'] = $wo['woLate'];
                $gmiMonthly[$userId]['gmiWoSelfFinding'] = $wo['woSelfFinding'];

                $woTierPoint = '0.5';
                $woTierName = 'Under Rated';
                if (intval($wo['woCompleted']) > 150) {
                    $woTierPoint = '2';
                    $woTierName = 'Finisher';
                } else if (intval($wo['woCompleted']) > 80) {
                    $woTierPoint = '1';
                    $woTierName = 'Medalist';
                }
                $gmiMonthly[$userId]['gmiWoTierPoint'] = $woTierPoint;
                $gmiMonthly[$userId]['gmiWoTierName'] = $woTierName;
            }

            foreach ($gmiMonthly as $gmi) {
                $gmiId = $gmi['gmiId'];
                $allTotal = intval($gmi['gmiPpmTotal']) + intval($gmi['gmiWoTotal']);
                $allCompleted = intval($gmi['gmiPpmCompleted']) + intval($gmi['gmiWoCompleted']);
                $allOnTime = intval($gmi['gmiPpmOnTime']) + intval($gmi['gmiWoOnTime']);
                $allLate = intval($gmi['gmiPpmLate']) + intval($gmi['gmiWoLate']);
                $tierDivider = floatval($gmi['gmiWoTierPoint']) > floatval($gmi['gmiPpmTierPoint']) ? floatval($gmi['gmiWoTierPoint']) : floatval($gmi['gmiPpmTierPoint']);
                $gmi['gmiPointCompleted'] = strval((($allCompleted/$allTotal)*$tierDivider)*0.3*10000);
                $gmi['gmiPointOnTime'] = strval((($allOnTime/$allTotal)*$tierDivider)*0.7*10000);
                $gmi['gmiPointLate'] = strval(-(($allLate/$allTotal)*$tierDivider)*0.15*10000);
                $gmi['gmiPointSelfFinding'] = strval(intval($gmi['gmiWoSelfFinding']) * 5);
                $gmi['gmiPointTotal'] = strval(intval($gmi['gmiPointCompleted']) + intval($gmi['gmiPointOnTime']) + intval($gmi['gmiPointLate']) + intval($gmi['gmiPointSelfFinding']));
                unset($gmi['gmiId']);
                if ($gmiId === '') {
                    Class_db::getInstance()->db_insert('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi));
                } else {
                    Class_db::getInstance()->db_update('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi), array('gmi_id'=>$gmiId));
                }
            }
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}