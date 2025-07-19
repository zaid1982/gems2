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
            
            // --- NEW MBV CLARIFICATION LOGIC START ---
            // 1. Get all weeks within the current month
            $startOfMonth = new DateTime("$year-$month-01");
            $endOfMonth = (new DateTime("$year-$month-01"))->modify('last day of this month');

            $weeklyDataByUserId = []; // To store aggregated weekly data for each user
            $currentDate = clone $startOfMonth;

            while ($currentDate <= $endOfMonth) {
                $weekNumber = (int)$currentDate->format('W'); // Get ISO week number (Monday as first day of week)
                $weekYear = (int)$currentDate->format('Y'); // Get year for the week number (can spill over year boundaries)

                // Fetch PPM data for the current week
                $weeklyPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyPpm as $ppm) {
                    $userId = intval($ppm['ppmTaskAssignedTo']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_mbvs' => [], // To store MBV for each week
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $ppm['siteId'], $ppm['gmiId']) // Initialize monthly data struct
                        ];
                    }
                    // Aggregate weekly PPM totals into monthly data structure for later use
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmTotal'] += intval($ppm['ppmTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmCompleted'] += intval($ppm['ppmCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmOnTime'] += intval($ppm['ppmOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmLate'] += intval($ppm['ppmLate']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmWithin'] += intval($ppm['ppmWithin']);
                }

                // Fetch PPM Assist data for the current week
                $weeklyPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyPpmAssist as $ppmAssist) {
                    $userId = intval($ppmAssist['userId']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_mbvs' => [],
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $ppmAssist['siteId'], $ppmAssist['gmiId'])
                        ];
                    }
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmAssist'] += intval($ppmAssist['ppmTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmTotal'] += intval($ppmAssist['ppmTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmCompleted'] += intval($ppmAssist['ppmCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmOnTime'] += intval($ppmAssist['ppmOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmLate'] += intval($ppmAssist['ppmLate']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmWithin'] += intval($ppmAssist['ppmWithin']);
                }

                // Fetch WO data for the current week
                $weeklyWo = Class_db::getInstance()->db_select2('vw_gamification_wo_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyWo as $wo) {
                    $userId = intval($wo['woTaskAssignedTo']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_mbvs' => [],
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $wo['siteId'], $wo['gmiId'])
                        ];
                    }
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoTotal'] += intval($wo['woTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoCompleted'] += intval($wo['woCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoOnTime'] += intval($wo['woOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoLate'] += intval($wo['woLate']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoSelfFinding'] += intval($wo['woSelfFinding']);
                }

                // Fetch WO Assist data for the current week
                $weeklyWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyWoAssist as $woAssist) {
                    $userId = intval($woAssist['userId']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_mbvs' => [],
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $woAssist['siteId'], $woAssist['gmiId'])
                        ];
                    }
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoAssist'] += intval($woAssist['woTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoTotal'] += intval($woAssist['woTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoCompleted'] += intval($woAssist['woCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoOnTime'] += intval($woAssist['woOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoLate'] += intval($woAssist['woLate']);
                }
                
                // Move to the next week's first day or end of month, whichever is earlier
                $currentDate->modify('+1 week'); // Move to the same day of next week
                // Ensure we don't jump past the end of the month
                if ($currentDate->format('Y-m') > $endOfMonth->format('Y-m')) {
                     // If we crossed month boundary, move to endOfMonth+1 day to ensure loop terminates
                     $currentDate = (clone $endOfMonth)->modify('+1 day');
                }
            }

            // Now, process the aggregated weekly data for each user to get their final monthly score
            foreach ($weeklyDataByUserId as $userId => $userData) {
                $gmi = $userData['monthly_data'];
                $gmiId = $gmi['gmiId'];

                // ---- total (using aggregated weekly values for the month) ----
                $allTotal = $gmi['gmiPpmTotal'] + $gmi['gmiWoTotal'];
                $allCompleted = $gmi['gmiPpmCompleted'] + $gmi['gmiWoCompleted'];
                // Use the sum of weekly OnTime/Within for the monthly calculation
                $allOnTime = $gmi['gmiPpmOnTime'] + (2 * $gmi['gmiWoOnTime']) + $gmi['gmiPpmWithin']; // Using the combined monthly accumulated on-time
                $allWithin = $gmi['gmiWoOnTime'] + $gmi['gmiPpmWithin']; // Combined monthly accumulated within
                $allLate = $gmi['gmiPpmLate'] + $gmi['gmiWoLate'];

                // Deriving monthly MBV from aggregated weekly data (e.g., sum of weekly OnTime - sum of weekly Late)
                // This is where the "tier will become a bit harder to decide" logic comes in.
                // For a starting point, let's use the sum of combined weekly OnTime/Late for MBV.
                // The tiers (50, 100) will be based on these combined weekly numbers.
                $mbv = $allOnTime - $allLate; 

                if ($mbv <= 50) {
                    $tierDivider = 1;
                } else if ($mbv <= 100) {
                    $tierDivider = 3;
                } else {
                    $tierDivider = 5;
                }
                
                // Tier names (Medalist, Finisher) will still be based on gmiPpmCompleted / gmiWoCompleted
                // which are now accumulated from weekly totals.
                if ($gmi['gmiPpmCompleted'] > 150) {
                    $gmi['gmiPpmTierPoint'] = 1;
                    $gmi['gmiPpmTierName'] = 'Medalist';
                } else if ($gmi['gmiPpmCompleted'] > 80) {
                    $gmi['gmiPpmTierPoint'] = 1;
                    $gmi['gmiPpmTierName'] = 'Finisher';
                }

                if ($gmi['gmiWoCompleted'] > 150) {
                    $gmi['gmiWoTierPoint'] = 1;
                    $gmi['gmiWoTierName'] = 'Medalist';
                } else if ($gmi['gmiWoCompleted'] > 80) {
                    $gmi['gmiWoTierPoint'] = 1;
                    $gmi['gmiWoTierName'] = 'Finisher';
                }

                // --- Original Point Calculation (using the newly derived mbv and tierDivider) ---
                $gmi['gmiPointCompleted'] = ($allTotal > 0) ? ($allCompleted/$allTotal) * 0.3 * 10000 : 0;
                $gmi['gmiPointOnTime'] = ($allTotal > 0) ? (($allWithin/$allTotal) * $tierDivider) * 0.7 * 10000 : 0;
                $gmi['gmiPointLate'] = ($allCompleted === 0) ? 0 : -(($allLate/$allCompleted) * $tierDivider) * 0.15 * 10000;
                $gmi['gmiPointSelfFinding'] = intval($gmi['gmiWoSelfFinding']) * 5;
                $gmi['gmiPointTotal'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointOnTime'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding'];
				$gmi['gmiMbv'] = $mbv;
				$gmi['gmiTierPoint'] = $tierDivider;

                // --- Original Productivity Calculation (using new aggregated totals) ---
                $gmi['gmiProductivityLevel'] = ($allTotal > 0) ? $allWithin / $allTotal * 90 : 0;
                $gmi['gmiProductivityDeduction'] = 90 - $gmi['gmiProductivityLevel'];
                $gmi['gmiPointLessProductive'] = ($allTotal > 0) ? ($allWithin/$allTotal) * $gmi['gmiTierPoint'] * ($gmi['gmiProductivityDeduction']/100) * 10000 : 0;
                $gmi['gmiPointBeforeMinus'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding'] + $gmi['gmiPointOnTime'];
                $gmi['gmiPointAfterMinus'] = $gmi['gmiPointBeforeMinus'] -  $gmi['gmiPointLessProductive'];
                
                // ---- Save to database ----
                unset($gmi['gmiId']); // Ensure gmiId is unset for insert, but used for update where applicable
                $existingRecord = Class_db::getInstance()->db_select_single2('gmi_monthly', array('user_id' => $gmi['userId'], 'gmi_year' => $gmi['gmiYear'], 'gmi_month' => $gmi['gmiMonth']));

                if (empty($existingRecord)) {
                    Class_db::getInstance()->db_insert('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi));
                } else {
                    Class_db::getInstance()->db_update('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi), array('gmi_id' => $existingRecord['gmiId']));
                }
            }
            // --- NEW MBV CLARIFICATION LOGIC END ---

            // The original direct fetching of monthly data (if any) would be removed as it's now done through weekly aggregation.
            // Original code that would be removed/replaced:
            /*
            $gmiPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiPpm as $ppm) { ... }
            $gmiPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiPpmAssist as $ppmAssist) { ... }
            $gmiWo = Class_db::getInstance()->db_select2('vw_gamification_wo_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiWo as $wo) { ... }
            $gmiWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_monthly', array(), '', '', 0, array('yearNo'=>$year, 'monthNo'=>$month));
            foreach ($gmiWoAssist as $woAssist) { ... }
            // Original foreach ($gmiMonthly as $gmi) { ... calculation ... } block is now wrapped/modified by the new logic.
            */

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