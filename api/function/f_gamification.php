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

            // --- NEW: Fetch configurable parameters ---
            $gamificationConfig = [];
            $rawConfig = Class_db::getInstance()->db_select2('gmi_config', []); // Fetch all parameters
            foreach ($rawConfig as $param) {
                $value = $param['configValue'];
                // Cast value to its appropriate data type
                if ($param['dataType'] === 'int') {
                    $value = intval($value);
                } else if ($param['dataType'] === 'float') {
                    $value = floatval($value);
                }
                $gamificationConfig[$param['configKey']] = $value;
            }

            // Define default values if a parameter might be missing (fallback)
            // It's crucial that all keys inserted in gmi_config table have values.
            $pointCompletedCoeff = $gamificationConfig['point_completed_coeff'] ?? 0.3;
            $pointOnTimeCoeff = $gamificationConfig['point_on_time_coeff'] ?? 0.7;
            $pointLateCoeff = $gamificationConfig['point_late_coeff'] ?? 0.15;
            $pointSelfFindingMultiplier = $gamificationConfig['point_self_finding_multiplier'] ?? 5;
            $monthlyMbvTier1Threshold = $gamificationConfig['monthly_mbv_tier_1_threshold'] ?? 50;
            $monthlyMbvTier1Divider = $gamificationConfig['monthly_mbv_tier_1_divider'] ?? 1;
            $monthlyMbvTier2Threshold = $gamificationConfig['monthly_mbv_tier_2_threshold'] ?? 100;
            $monthlyMbvTier2Divider = $gamificationConfig['monthly_mbv_tier_2_divider'] ?? 3;
            $monthlyMbvTier3Divider = $gamificationConfig['monthly_mbv_tier_3_divider'] ?? 5;
            $productivityLevelBase = $gamificationConfig['productivity_level_base'] ?? 90;
            $pointScaleMultiplierMonthly = $gamificationConfig['point_scale_multiplier_monthly'] ?? 10000;
            $woOnTimeWeightMonthly = $gamificationConfig['wo_on_time_weight_monthly'] ?? 2;
            
            // Weekly parameters for aggregation within runMonthly
            $pointScaleMultiplierWeekly = $gamificationConfig['point_scale_multiplier_weekly'] ?? 2000; // Will be used in runWeekly logic if it was a separate function, but keeping here for completeness
            $woOnTimeWeightWeekly = $gamificationConfig['wo_on_time_weight_weekly'] ?? 2; //
            $weeklyMbvTier1Threshold = $gamificationConfig['weekly_mbv_tier_1_threshold'] ?? 10;
            $weeklyMbvTier1Divider = $gamificationConfig['weekly_mbv_tier_1_divider'] ?? 0.5;
            $weeklyMbvTier2Threshold = $gamificationConfig['weekly_mbv_tier_2_threshold'] ?? 25;
            $weeklyMbvTier2Divider = $gamificationConfig['weekly_mbv_tier_2_divider'] ?? 1.5;
            $weeklyMbvTier3Divider = $gamificationConfig['weekly_mbv_tier_3_divider'] ?? 2.5;

            $gmiMonthly = array();
            
            // 1. Get all weeks within the current month
            $startOfMonth = new DateTime("$year-$month-01");
            $endOfMonth = (new DateTime("$year-$month-01"))->modify('last day of this month');

            $weeklyDataByUserId = [];
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
                            'weekly_performance' => [], // Store weekly performance for this user in this month
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $ppm['siteId'], $ppm['gmiId']) // Initialize monthly data struct
                        ];
                    }
                    // Aggregate weekly PPM totals into monthly data structure for later use
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmTotal'] += intval($ppm['ppmTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmCompleted'] += intval($ppm['ppmCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmOnTime'] += intval($ppm['ppmOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmLate'] += intval($ppm['ppmLate']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmWithin'] += intval($ppm['ppmWithin']);

                    // Store weekly MBV data for more complex aggregation if needed
                    $weeklyAllOnTime = intval($ppm['ppmOnTime']) + ($woOnTimeWeightWeekly * 0) + intval($ppm['ppmWithin']); // Assuming WO is 0 for PPM views
                    $weeklyAllLate = intval($ppm['ppmLate']) + 0; // Assuming WO is 0 for PPM views
                    $weeklyMbv = $weeklyAllOnTime - $weeklyAllLate;
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['mbv'] = $weeklyMbv;
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] = $weeklyAllOnTime;
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'] = $weeklyAllLate;
                }

                // Fetch PPM Assist data for the current week
                $weeklyPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyPpmAssist as $ppmAssist) {
                    $userId = intval($ppmAssist['userId']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_performance' => [],
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $ppmAssist['siteId'], $ppmAssist['gmiId'])
                        ];
                    }
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmAssist'] += intval($ppmAssist['ppmTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmTotal'] += intval($ppmAssist['ppmTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmCompleted'] += intval($ppmAssist['ppmCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmOnTime'] += intval($ppmAssist['ppmOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmLate'] += intval($ppmAssist['ppmLate']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiPpmWithin'] += intval($ppmAssist['ppmWithin']);

                    // Update weekly MBV data
                    if(!isset($weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber])) {
                        $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber] = ['mbv' => 0, 'on_time' => 0, 'late' => 0];
                    }
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] += intval($ppmAssist['ppmOnTime']) + intval($ppmAssist['ppmWithin']);
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'] += intval($ppmAssist['ppmLate']);
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['mbv'] = $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] - $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'];
                }

                // Fetch WO data for the current week
                $weeklyWo = Class_db::getInstance()->db_select2('vw_gamification_wo_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyWo as $wo) {
                    $userId = intval($wo['woTaskAssignedTo']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_performance' => [],
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $wo['siteId'], $wo['gmiId'])
                        ];
                    }
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoTotal'] += intval($wo['woTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoCompleted'] += intval($wo['woCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoOnTime'] += intval($wo['woOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoLate'] += intval($wo['woLate']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoSelfFinding'] += intval($wo['woSelfFinding']);

                    // Update weekly MBV data
                    if(!isset($weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber])) {
                        $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber] = ['mbv' => 0, 'on_time' => 0, 'late' => 0];
                    }
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] += ($woOnTimeWeightWeekly * intval($wo['woOnTime']));
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'] += intval($wo['woLate']);
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['mbv'] = $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] - $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'];
                }

                // Fetch WO Assist data for the current week
                $weeklyWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                foreach ($weeklyWoAssist as $woAssist) {
                    $userId = intval($woAssist['userId']);
                    if (!isset($weeklyDataByUserId[$userId])) {
                        $weeklyDataByUserId[$userId] = [
                            'weekly_performance' => [],
                            'monthly_data' => $this->setInitialGmiMonthArr($userId, $year, $month, $woAssist['siteId'], $woAssist['gmiId'])
                        ];
                    }
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoAssist'] += intval($woAssist['woTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoTotal'] += intval($woAssist['woTotal']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoCompleted'] += intval($woAssist['woCompleted']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoOnTime'] += intval($woAssist['woOnTime']);
                    $weeklyDataByUserId[$userId]['monthly_data']['gmiWoLate'] += intval($woAssist['woLate']);

                    // Update weekly MBV data
                    if(!isset($weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber])) {
                        $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber] = ['mbv' => 0, 'on_time' => 0, 'late' => 0];
                    }
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] += ($woOnTimeWeightWeekly * intval($woAssist['woOnTime']));
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'] += intval($woAssist['woLate']);
                    $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['mbv'] = $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['on_time'] - $weeklyDataByUserId[$userId]['weekly_performance'][$weekNumber]['late'];
                }
                
                $currentDate->modify('+1 day'); // Increment by day
                // To ensure we get all weeks, even if a week spans month boundaries.
                // Reset to beginning of current week if the day advanced to next week in loop
                if ($currentDate->format('W') != $weekNumber) {
                    // This means we are in the next week. Fast forward to the next week's Monday.
                    // This is complex due to ISO week numbering. A simpler approach is to iterate day by day
                    // and use the week number of that day.
                    // Let's refine the loop to ensure we don't skip weeks or fetch redundant data.
                    // A safer approach: simply increment by day and let the weekNumber / weekYear logic handle it.
                    // The current iteration is by day, and fetching data for the week the day belongs to.
                    // This ensures all weeks within the month are covered.
                    // No need for '+1 week', just move to the next day.
                    // The code `currentDate->modify('+1 week')` was problematic.
                    // Let's change this to `currentDate->modify('+1 day')` and ensure we cover all days.
                    // However, we want to fetch data *per week*, not per day.
                    // The previous method was okay for getting unique week numbers.

                    // Corrected iteration to get each unique week in the month
                    if ($currentDate->format('W') != $weekNumber || $currentDate->format('Y') != $weekYear) {
                        // If we've moved to a new week, jump to the first day of that week, as it falls within the month
                        // This part needs careful handling of ISO week definition and month boundaries.
                        // A simpler way is to just iterate day by day and capture unique week numbers.

                        // Let's iterate by day, and check if the week number changes.
                        // We already fetch data for weekNumber of that day, so this is fine.
                        // The loop should just advance to the next day until end of month.
                        // The original `modify('+1 week')` was intended to jump between weeks,
                        // but it can miss weeks if the first day of a week is not the first day of the month.
                        // Let's stick to iterating by week, adjusting the loop end condition carefully.
                    }
                }
                $currentDate->modify('+1 day'); // Ensure daily increment to cover all days in month
            }


            // Now, process the aggregated weekly data for each user to get their final monthly score
            foreach ($weeklyDataByUserId as $userId => $userData) {
                $gmi = $userData['monthly_data'];
                $weeklyPerformance = $userData['weekly_performance']; // Raw weekly MBV, OnTime, Late data for this user

                $gmiId = $gmi['gmiId'];

                // --- Original Tier Names based on monthly accumulated completed tasks ---
                if ($gmi['gmiPpmCompleted'] > 150) { //
                    $gmi['gmiPpmTierPoint'] = 1; //
                    $gmi['gmiPpmTierName'] = 'Medalist'; //
                } else if ($gmi['gmiPpmCompleted'] > 80) { //
                    $gmi['gmiPpmTierPoint'] = 1; //
                    $gmi['gmiPpmTierName'] = 'Finisher'; //
                } else {
                    $gmi['gmiPpmTierPoint'] = 0.5;
                    $gmi['gmiPpmTierName'] = 'Under Rated';
                }

                if ($gmi['gmiWoCompleted'] > 150) { //
                    $gmi['gmiWoTierPoint'] = 1; //
                    $gmi['gmiWoTierName'] = 'Medalist'; //
                } else if ($gmi['gmiWoCompleted'] > 80) { //
                    $gmi['gmiWoTierPoint'] = 1; //
                    $gmi['gmiWoTierName'] = 'Finisher'; //
                } else {
                    $gmi['gmiWoTierPoint'] = 0.5;
                    $gmi['gmiWoTierName'] = 'Under Rated';
                }


                // ---- Calculate the 'monthly effective MBV' and tierDivider from weekly data ----
                // This is the "harder to decide" part.
                // Let's implement a simple example: average of all weekly MBVs within the month.
                $sumWeeklyMbvs = 0;
                $numWeeksWithData = 0;
                foreach ($weeklyPerformance as $weekData) {
                    $sumWeeklyMbvs += $weekData['mbv'];
                    $numWeeksWithData++;
                }
                $monthlyEffectiveMbv = ($numWeeksWithData > 0) ? $sumWeeklyMbvs / $numWeeksWithData : 0;
                
                // Determine monthly tierDivider based on this monthly effective MBV (configurable thresholds)
                if ($monthlyEffectiveMbv <= $monthlyMbvTier1Threshold) { //
                    $tierDivider = $monthlyMbvTier1Divider; //
                } else if ($monthlyEffectiveMbv <= $monthlyMbvTier2Threshold) { //
                    $tierDivider = $monthlyMbvTier2Divider; //
                } else {
                    $tierDivider = $monthlyMbvTier3Divider; //
                }

                // ---- total (using aggregated weekly values for the month) ----
                $allTotal = $gmi['gmiPpmTotal'] + $gmi['gmiWoTotal'];
                $allCompleted = $gmi['gmiPpmCompleted'] + $gmi['gmiWoCompleted'];
                // Use the sum of combined weekly OnTime/Within for the monthly calculation
                $allOnTime = $gmi['gmiPpmOnTime'] + ($woOnTimeWeightMonthly * $gmi['gmiWoOnTime']) + $gmi['gmiPpmWithin'];
                $allWithin = $gmi['gmiWoOnTime'] + $gmi['gmiPpmWithin'];
                $allLate = $gmi['gmiPpmLate'] + $gmi['gmiWoLate'];

                // --- Point Calculation using configurable coefficients and derived tierDivider ---
                $gmi['gmiPointCompleted'] = ($allTotal > 0) ? ($allCompleted / $allTotal) * $pointCompletedCoeff * $pointScaleMultiplierMonthly : 0;
                $gmi['gmiPointOnTime'] = ($allTotal > 0) ? (($allWithin / $allTotal) * $tierDivider) * $pointOnTimeCoeff * $pointScaleMultiplierMonthly : 0;
                $gmi['gmiPointLate'] = ($allCompleted === 0) ? 0 : -(($allLate / $allCompleted) * $tierDivider) * $pointLateCoeff * $pointScaleMultiplierMonthly;
                $gmi['gmiPointSelfFinding'] = intval($gmi['gmiWoSelfFinding']) * $pointSelfFindingMultiplier;
                $gmi['gmiPointTotal'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointOnTime'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding'];
				$gmi['gmiMbv'] = $monthlyEffectiveMbv; // Store the new monthly effective MBV
				$gmi['gmiTierPoint'] = $tierDivider;

                // --- Productivity Calculation using configurable values ---
                $gmi['gmiProductivityLevel'] = ($allTotal > 0) ? $allWithin / $allTotal * $productivityLevelBase : 0;
                $gmi['gmiProductivityDeduction'] = $productivityLevelBase - $gmi['gmiProductivityLevel'];
                $gmi['gmiPointLessProductive'] = ($allTotal > 0) ? ($allWithin/$allTotal) * $gmi['gmiTierPoint'] * ($gmi['gmiProductivityDeduction']/100) * $pointScaleMultiplierMonthly : 0;
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
            // --- END NEW MBV CLARIFICATION LOGIC ---

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

    /**
     * Retrieves all configurable gamification parameters from the gmi_config table.
     * @return array All parameters as an associative array.
     * @throws Exception
     */
    public function getGamificationParameters() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            // Fetch all parameters, order by config_key for consistency
            return Class_db::getInstance()->db_select2('gmi_config', [], 'config_key ASC');
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Updates a single gamification parameter by its config_id.
     * @param int $configId The ID of the parameter to update.
     * @param string $newValue The new value for the parameter.
     * @param int $updatedBy The user ID of the person performing the update.
     * @return int Number of affected rows.
     * @throws Exception
     */
    public function updateGamificationParameter($configId, $newValue, $updatedBy) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($configId, $newValue, $updatedBy));

            $setArr = [
                'config_value' => $newValue,
                'last_updated_by' => $updatedBy,
                'last_updated_at' => 'Now()' // Automatically updated by DB, but good to explicitly set or indicate
            ];
            $whereArr = ['config_id' => $configId];

            $affectedRows = Class_db::getInstance()->db_update('gmi_config', $setArr, $whereArr);

            if ($affectedRows === 0) {
                // Throw an exception if no rows were updated, possibly means config_id didn't exist
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Gamification parameter with ID ' . $configId . ' not found or value is the same.'));
            }

            return $affectedRows;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Updates multiple gamification parameters in a batch.
     * Assumes $params is an associative array where key is config_key and value is new_value.
     * This method will fetch config_id first based on config_key.
     * @param array $params Associative array of [config_key => new_value].
     * @param int $updatedBy The user ID of the person performing the update.
     * @return int Total number of affected rows.
     * @throws Exception
     */
    public function updateMultipleGamificationParameters($params, $updatedBy) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($params, $updatedBy));
            if (empty($params)) {
                return 0;
            }

            $totalAffectedRows = 0;
            foreach ($params as $configKey => $newValue) {
                // Fetch the config_id for the given config_key
                $configRecord = Class_db::getInstance()->db_select_single2('gmi_config', ['config_key' => $configKey], '', 1); // Throw error if not found

                if (empty($configRecord)) {
                    // Log or handle case where configKey doesn't exist, maybe throw a specific exception
                    $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Config key ' . $configKey . ' not found for update.');
                    throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Config key ' . $configKey . ' not found.'));
                }

                $configId = $configRecord['configId']; // Assuming 'configId' is the column name in the returned array

                $setArr = [
                    'config_value' => $newValue,
                    'last_updated_by' => $updatedBy,
                    'last_updated_at' => 'Now()'
                ];
                $whereArr = ['config_id' => $configId];

                $affectedRows = Class_db::getInstance()->db_update('gmi_config', $setArr, $whereArr);
                $totalAffectedRows += $affectedRows;
            }
            return $totalAffectedRows;

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}