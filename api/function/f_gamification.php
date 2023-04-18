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
     * @return array
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
     * @return array
     * @throws Exception
     */
    public function getGmiMonthlyTop5M ($year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $result = array();
            $top5Arr = $this->getGmiMonthlyTop5($year, $month);
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrSite = $this->fn_general->getSiteName();
            foreach ($top5Arr as $top5) {
                $row['individualName'] = $arrUserFullName[intval($top5['userId'])];
                $row['projectName'] = $arrSite[intval($top5['siteId'])];
                $row['individualCategory'] = '';//Class_db::getInstance()->db_select_col('att_participant', array('user_id'=>$top5['userId']), 'att_participant_category');
                $row['totalScore'] = $top5['gmiPointTotal'];
                $result[] = $row;
            }
            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $year
     * @param $month
     * @return array
     * @throws Exception
     */
    public function getGmiMonthlyTop5ProjectM ($year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($year, $month));
            return Class_db::getInstance()->db_select2('vw_gmi_monthly_project_m', array(), 'total_score DESC', '5', 0, array('yearNo'=>$year, 'monthNo'=>$month));
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
     * @param $userId
     * @param $year
     * @param $month
     * @param $siteId
     * @param $gmiId
     * @return array
     * @throws Exception
     */
    private function setInitialGmiMonthArr ($userId, $year, $month, $siteId, $gmiId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId, $year, $month, $siteId));
            $returnArr['userId'] = $userId;
            $returnArr['gmiYear'] = $year;
            $returnArr['gmiMonth'] = $month;
            $returnArr['siteId'] = $siteId;
            $returnArr['gmiId'] = $gmiId;
            $returnArr['gmiPpmTotal'] = 0;
            $returnArr['gmiPpmCompleted'] = 0;
            $returnArr['gmiPpmOnTime'] = 0;
            $returnArr['gmiPpmLate'] = 0;
            $returnArr['gmiPpmWithin'] = 0;
            $returnArr['gmiPpmAssist'] = 0;
            $returnArr['gmiPpmTierPoint'] = 0.5;
            $returnArr['gmiPpmTierName'] = 'Under Rated';
            $returnArr['gmiWoTotal'] = 0;
            $returnArr['gmiWoCompleted'] = 0;
            $returnArr['gmiWoOnTime'] = 0;
            $returnArr['gmiWoLate'] = 0;
            $returnArr['gmiWoSelfFinding'] = 0;
            $returnArr['gmiWoAssist'] = 0;
            $returnArr['gmiWoTierPoint'] = 0.5;
            $returnArr['gmiWoTierName'] = 'Under Rated';
            $returnArr['gmiPointCompleted'] = 0;
            $returnArr['gmiPointOnTime'] = 0;
            $returnArr['gmiPointLate'] = 0;
            $returnArr['gmiPointSelfFinding'] = 0;
            $returnArr['gmiPointTotal'] = 0;
            return $returnArr;
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
                $gmiMonthly[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $ppm['siteId'], $ppm['gmiId']);
                $gmiMonthly[$userId]['gmiPpmTotal'] = intval($ppm['ppmTotal']);
                $gmiMonthly[$userId]['gmiPpmCompleted'] = intval($ppm['ppmCompleted']);
                $gmiMonthly[$userId]['gmiPpmOnTime'] = intval($ppm['ppmOnTime']);
                $gmiMonthly[$userId]['gmiPpmLate'] = intval($ppm['ppmLate']);
                $gmiMonthly[$userId]['gmiPpmWithin'] = intval($ppm['ppmWithin']);
                if ($gmiMonthly[$userId]['gmiPpmCompleted'] > 150) {
                    $gmiMonthly[$userId]['gmiPpmTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiPpmTierName'] = 'Medalist';
                } else if ($gmiMonthly[$userId]['gmiPpmCompleted'] > 80) {
                    $gmiMonthly[$userId]['gmiPpmTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiPpmTierName'] = 'Finisher';
                }
            }

            $gmiPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiPpmAssist as $ppmAssist) {
                $userId = intval($ppmAssist['userId']);
                if (!array_key_exists($userId, $gmiMonthly)) {
                    $gmiMonthly[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $ppmAssist['siteId'], $ppmAssist['gmiId']);
                }
                $gmiMonthly[$userId]['gmiPpmAssist'] = intval($ppmAssist['ppmTotal']);
                $gmiMonthly[$userId]['gmiPpmTotal'] += intval($ppmAssist['ppmTotal']);
                $gmiMonthly[$userId]['gmiPpmCompleted'] += intval($ppmAssist['ppmCompleted']);
                $gmiMonthly[$userId]['gmiPpmOnTime'] += intval($ppmAssist['ppmOnTime']);
                $gmiMonthly[$userId]['gmiPpmLate'] += intval($ppmAssist['ppmLate']);
                $gmiMonthly[$userId]['gmiPpmWithin'] += intval($ppmAssist['ppmWithin']);
                if ($gmiMonthly[$userId]['gmiPpmCompleted'] > 150) {
                    $gmiMonthly[$userId]['gmiPpmTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiPpmTierName'] = 'Medalist';
                } else if ($gmiMonthly[$userId]['gmiPpmCompleted'] > 80) {
                    $gmiMonthly[$userId]['gmiPpmTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiPpmTierName'] = 'Finisher';
                }
            }

            $gmiWo = Class_db::getInstance()->db_select2('vw_gamification_wo_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiWo as $wo) {
                $userId = intval($wo['woTaskAssignedTo']);
                if (!array_key_exists($userId, $gmiMonthly)) {
                    $gmiMonthly[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $wo['siteId'], $wo['gmiId']);
                }
                $gmiMonthly[$userId]['gmiWoTotal'] = intval($wo['woTotal']);
                $gmiMonthly[$userId]['gmiWoCompleted'] = intval($wo['woCompleted']);
                $gmiMonthly[$userId]['gmiWoOnTime'] = intval($wo['woOnTime']);
                $gmiMonthly[$userId]['gmiWoLate'] = intval($wo['woLate']);
                $gmiMonthly[$userId]['gmiWoSelfFinding'] = intval($wo['woSelfFinding']);
                if ($gmiMonthly[$userId]['gmiWoCompleted'] > 150) {
                    $gmiMonthly[$userId]['gmiWoTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiWoTierName'] = 'Medalist';
                } else if ($gmiMonthly[$userId]['gmiWoCompleted'] > 80) {
                    $gmiMonthly[$userId]['gmiWoTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiWoTierName'] = 'Finisher';
                }
            }

            $gmiWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiWoAssist as $woAssist) {
                $userId = intval($woAssist['userId']);
                if (!array_key_exists($userId, $gmiMonthly)) {
                    $gmiMonthly[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $woAssist['siteId'], $woAssist['gmiId']);
                }
                $gmiMonthly[$userId]['gmiWoAssist'] = intval($woAssist['woTotal']);
                $gmiMonthly[$userId]['gmiWoTotal'] += intval($woAssist['woTotal']);
                $gmiMonthly[$userId]['gmiWoCompleted'] += intval($woAssist['woCompleted']);
                $gmiMonthly[$userId]['gmiWoOnTime'] += intval($woAssist['woOnTime']);
                $gmiMonthly[$userId]['gmiWoLate'] += intval($woAssist['woLate']);
                if ($gmiMonthly[$userId]['gmiWoCompleted'] > 150) {
                    $gmiMonthly[$userId]['gmiWoTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiWoTierName'] = 'Medalist';
                } else if ($gmiMonthly[$userId]['gmiWoCompleted'] > 80) {
                    $gmiMonthly[$userId]['gmiWoTierPoint'] = 1;
                    $gmiMonthly[$userId]['gmiWoTierName'] = 'Finisher';
                }
            }

            foreach ($gmiMonthly as $gmi) {
                $gmiId = $gmi['gmiId'];
                // ---- total ---- \\
                $allTotal = $gmi['gmiPpmTotal'] + $gmi['gmiWoTotal'];
                $allCompleted = $gmi['gmiPpmCompleted'] + $gmi['gmiWoCompleted'];
                $allOnTime = $gmi['gmiPpmOnTime'] + (2*$gmi['gmiWoOnTime']) + $gmi['gmiPpmWithin'];
				$allWithin = $gmi['gmiWoOnTime'] + $gmi['gmiPpmWithin'];
                $allLate = $gmi['gmiPpmLate'] + $gmi['gmiWoLate'];
				$mbv = $allOnTime - $allLate;
				if ($mbv <= 50) {
					$tierDivider = 1;
				} else if ($mbv <= 100) {
					$tierDivider = 3;
				} else {
					$tierDivider = 5;
				}
                // ---- point ---- \\
                //$tierDivider = max($gmi['gmiWoTierPoint'], $gmi['gmiPpmTierPoint']);
                $gmi['gmiPointCompleted'] = ($allCompleted/$allTotal) * 0.3 * 10000;
                $gmi['gmiPointOnTime'] = (($allWithin/$allTotal) * $tierDivider) * 0.7 * 10000;
                $gmi['gmiPointLate'] = $allCompleted === 0 ? 0 : -(($allLate/$allCompleted) * $tierDivider) * 0.15 * 10000;
                $gmi['gmiPointSelfFinding'] = intval($gmi['gmiWoSelfFinding']) * 5;
                $gmi['gmiPointTotal'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointOnTime'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding'];
				$gmi['gmiMbv'] = $mbv;
				$gmi['gmiTierPoint'] = $tierDivider;
                // ---- productivity ---- \\
                $gmi['gmiProductivityLevel'] = $allWithin / $allTotal * 90;
                $gmi['gmiProductivityDeduction'] = 90 - $gmi['gmiProductivityLevel'];
                $gmi['gmiPointLessProductive'] = ($allWithin/$allTotal) * $gmi['gmiTierPoint'] * ($gmi['gmiProductivityDeduction']/100) * 10000;
                $gmi['gmiPointBeforeMinus'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding'] + $gmi['gmiPointOnTime'];
                $gmi['gmiPointAfterMinus'] = $gmi['gmiPointBeforeMinus'] -  $gmi['gmiPointLessProductive'];
                // ---- done ---- \\
                unset($gmi['gmiId']);
                if (empty($gmiId)) {
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

    /**
     * @param $userId
     * @param $year
     * @param $month
     * @return string
     * @throws Exception
     */
    public function getCurrentScore ($userId, $year, $month) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($userId));
            return Class_db::getInstance()->db_select_single2('gmi_monthly', array('user_id'=>$userId, 'gmi_year'=>$year, 'gmi_month'=>$month));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}