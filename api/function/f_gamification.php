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
            $this->fn_general->checkEmptyParams(array($userId, $year, $month));
            
            // Ensure siteId has a fallback value if it's null or empty
            if (empty($siteId)) {
                $siteId = 1; // Default fallback siteId
            }
            
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

            // Fetch configurable parameters (as implemented in Step 15)
            $gamificationConfig = [];
            $rawConfig = Class_db::getInstance()->db_select2('gmi_config', [], 'config_key ASC');
            foreach ($rawConfig as $param) {
                $value = $param['configValue'];
                if ($param['dataType'] === 'int') {
                    $value = intval($value);
                } else if ($param['dataType'] === 'float') {
                    $value = floatval($value);
                }
                $gamificationConfig[$param['configKey']] = $value;
            }

            // Define default values if a parameter might be missing (fallback)
            $pointCompletedCoeff = $gamificationConfig['point_completed_coeff'] ?? 0.3;
            $pointOnTimeCoeff = $gamificationConfig['point_on_time_coeff'] ?? 0.7;
            $pointLateCoeff = $gamificationConfig['point_late_coeff'] ?? 0.15;
            $pointSelfFindingMultiplier = $gamificationConfig['point_self_finding_multiplier'] ?? 5;
            // Monthly MBV Tiers are now unused for tier determination, but keeping for reference if needed elsewhere.
            // $monthlyMbvTier1Threshold = $gamificationConfig['monthly_mbv_tier_1_threshold'] ?? 50;
            // $monthlyMbvTier1Divider = $gamificationConfig['monthly_mbv_tier_1_divider'] ?? 1;
            // $monthlyMbvTier2Threshold = $gamificationConfig['monthly_mbv_tier_2_threshold'] ?? 100;
            // $monthlyMbvTier2Divider = $gamificationConfig['monthly_mbv_tier_2_divider'] ?? 3;
            // $monthlyMbvTier3Divider = $gamificationConfig['monthly_mbv_tier_3_divider'] ?? 5;
            
            $productivityLevelBase = $gamificationConfig['productivity_level_base'] ?? 90;
            $pointScaleMultiplierMonthly = $gamificationConfig['point_scale_multiplier_monthly'] ?? 10000;
            $woOnTimeWeightMonthly = $gamificationConfig['wo_on_time_weight_monthly'] ?? 2; // Used in productivity and overall monthly totals

            // Weekly MBV Tiers - NOW ACTIVELY USED FOR TIER DETERMINATION
            $pointScaleMultiplierWeekly = $gamificationConfig['point_scale_multiplier_weekly'] ?? 2000; // Not directly used in this logic, but available
            $woOnTimeWeightWeekly = $gamificationConfig['wo_on_time_weight_weekly'] ?? 2; // Used in weekly MBV calculation
            $weeklyMbvTier1Threshold = $gamificationConfig['weekly_mbv_tier_1_threshold'] ?? 10;
            $weeklyMbvTier1Divider = $gamificationConfig['weekly_mbv_tier_1_divider'] ?? 0.5;
            $weeklyMbvTier2Threshold = $gamificationConfig['weekly_mbv_tier_2_threshold'] ?? 25;
            $weeklyMbvTier2Divider = $gamificationConfig['weekly_mbv_tier_2_divider'] ?? 1.5;
            $weeklyMbvTier3Divider = $gamificationConfig['weekly_mbv_tier_3_divider'] ?? 2.5;

            $gmiMonthly = array(); // This will hold the final monthly aggregated data

            // Phase 1: Aggregate ALL weekly performance data for the month
            $startOfMonth = new DateTime("$year-$month-01");
            $endOfMonth = (new DateTime("$year-$month-01"))->modify('last day of this month');
            
            $rawMonthlyAccumulatedData = []; // Accumulate all weekly raw counts here for each user
            // These accumulators are now per-user, to sum up weekly point components
            $weeklyOnTimePointComponentsSum = [];
            $weeklyLatePointComponentsSum = [];
            // These accumulate for averages to be stored in gmi_monthly
            $accumulatedWeeklyMbvs = []; 
            $accumulatedWeeklyTierPoints = []; 

            // Create a set of unique week-year pairs within the month to avoid redundant fetches
            $uniqueWeekYearPairs = [];
            $tempDate = clone $startOfMonth;
            while ($tempDate <= $endOfMonth) {
                $uniqueWeekYearPairs[(int)$tempDate->format('W') . '-' . (int)$tempDate->format('Y')] = [
                    'weekNo' => (int)$tempDate->format('W'),
                    'yearNo' => (int)$tempDate->format('Y')
                ];
                $tempDate->modify('+1 day');
            }


            foreach ($uniqueWeekYearPairs as $pair) {
                $weekNumber = $pair['weekNo'];
                $weekYear = $pair['yearNo'];

                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Processing week ' . $weekNumber . ' of year ' . $weekYear);

                // Fetch data for ALL USERS for this specific week from all weekly views
                $weeklyPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                $weeklyPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                $weeklyWo = Class_db::getInstance()->db_select2('vw_gamification_wo_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);
                $weeklyWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_weekly', [], '', '', 0, ['yearNo' => $weekYear, 'weekNo' => $weekNumber]);

                // Combine all weekly data for this week for easy iteration over users
                $allWeeklyDataThisWeekForAllUsers = [];
                foreach ($weeklyPpm as $item) {
                    if (isset($item['ppmTaskAssignedTo']) && !empty($item['ppmTaskAssignedTo'])) {
                        $allWeeklyDataThisWeekForAllUsers[$item['ppmTaskAssignedTo']]['ppm'] = $item;
                    }
                }
                foreach ($weeklyPpmAssist as $item) {
                    if (isset($item['userId']) && !empty($item['userId'])) {
                        if(!isset($allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist'])) {
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist'] = $item;
                        } else { 
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist']['ppmTotal'] += $item['ppmTotal'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist']['ppmCompleted'] += $item['ppmCompleted'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist']['ppmOnTime'] += $item['ppmOnTime'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist']['ppmLate'] += $item['ppmLate'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['ppm_assist']['ppmWithin'] += $item['ppmWithin'];
                        }
                    }
                }
                foreach ($weeklyWo as $item) {
                    if (isset($item['woTaskAssignedTo']) && !empty($item['woTaskAssignedTo'])) {
                        $allWeeklyDataThisWeekForAllUsers[$item['woTaskAssignedTo']]['wo'] = $item;
                    }
                }
                foreach ($weeklyWoAssist as $item) {
                    if (isset($item['userId']) && !empty($item['userId'])) {
                        if(!isset($allWeeklyDataThisWeekForAllUsers[$item['userId']]['wo_assist'])) {
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['wo_assist'] = $item;
                        } else { 
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['wo_assist']['woTotal'] += $item['woTotal'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['wo_assist']['woCompleted'] += $item['woCompleted'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['wo_assist']['woOnTime'] += $item['woOnTime'];
                            $allWeeklyDataThisWeekForAllUsers[$item['userId']]['wo_assist']['woLate'] += $item['woLate'];
                        }
                    }
                }

                // --- Process each user's data for this week ---
                foreach ($allWeeklyDataThisWeekForAllUsers as $userId => $data) {
                    // Initialize rawMonthlyAccumulatedData and weekly component sums for this user if not already set
                    if (!isset($rawMonthlyAccumulatedData[$userId])) {
                        // Get siteId from any available source, fallback to 1 if none found
                        $siteId = $data['ppm']['siteId'] ?? $data['wo']['siteId'] ?? $data['ppm_assist']['siteId'] ?? $data['wo_assist']['siteId'] ?? 1;
                        $rawMonthlyAccumulatedData[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $siteId, null);
                        $weeklyOnTimePointComponentsSum[$userId] = 0;
                        $weeklyLatePointComponentsSum[$userId] = 0;
                        $accumulatedWeeklyMbvs[$userId] = [];
                        $accumulatedWeeklyTierPoints[$userId] = [];
                    }

                    // Accumulate raw monthly totals from this week's data (Phase 1 logic)
                    $rawMonthlyAccumulatedData[$userId]['gmiPpmTotal'] += intval($data['ppm']['ppmTotal'] ?? 0) + intval($data['ppm_assist']['ppmTotal'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiPpmCompleted'] += intval($data['ppm']['ppmCompleted'] ?? 0) + intval($data['ppm_assist']['ppmCompleted'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiPpmOnTime'] += intval($data['ppm']['ppmOnTime'] ?? 0) + intval($data['ppm_assist']['ppmOnTime'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiPpmLate'] += intval($data['ppm']['ppmLate'] ?? 0) + intval($data['ppm_assist']['ppmLate'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiPpmWithin'] += intval($data['ppm']['ppmWithin'] ?? 0) + intval($data['ppm_assist']['ppmWithin'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiWoTotal'] += intval($data['wo']['woTotal'] ?? 0) + intval($data['wo_assist']['woTotal'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiWoCompleted'] += intval($data['wo']['woCompleted'] ?? 0) + intval($data['wo_assist']['woCompleted'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiWoOnTime'] += intval($data['wo']['woOnTime'] ?? 0) + intval($data['wo_assist']['woOnTime'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiWoLate'] += intval($data['wo']['woLate'] ?? 0) + intval($data['wo_assist']['woLate'] ?? 0);
                    $rawMonthlyAccumulatedData[$userId]['gmiWoSelfFinding'] += intval($data['wo']['woSelfFinding'] ?? 0);


                    // --- Phase 2: Calculate weekly MBV and determine weekly tierMultiplier for this user for THIS week ---
                    $currentWeekPpmOnTime = intval($data['ppm']['ppmOnTime'] ?? 0) + intval($data['ppm_assist']['ppmOnTime'] ?? 0);
                    $currentWeekPpmWithin = intval($data['ppm']['ppmWithin'] ?? 0) + intval($data['ppm_assist']['ppmWithin'] ?? 0);
                    $currentWeekPpmLate = intval($data['ppm']['ppmLate'] ?? 0) + intval($data['ppm_assist']['ppmLate'] ?? 0);
                    $currentWeekWoOnTime = intval($data['wo']['woOnTime'] ?? 0) + intval($data['wo_assist']['woOnTime'] ?? 0);
                    $currentWeekWoLate = intval($data['wo']['woLate'] ?? 0) + intval($data['wo_assist']['woLate'] ?? 0);
                    $currentWeekPpmTotal = intval($data['ppm']['ppmTotal'] ?? 0) + intval($data['ppm_assist']['ppmTotal'] ?? 0);
                    $currentWeekWoTotal = intval($data['wo']['woTotal'] ?? 0) + intval($data['wo_assist']['woTotal'] ?? 0);
                    $currentWeekPpmCompleted = intval($data['ppm']['ppmCompleted'] ?? 0) + intval($data['ppm_assist']['ppmCompleted'] ?? 0);
                    $currentWeekWoCompleted = intval($data['wo']['woCompleted'] ?? 0) + intval($data['wo_assist']['woCompleted'] ?? 0);
                    
                    $currentWeekAllTotal = $currentWeekPpmTotal + $currentWeekWoTotal;
                    $currentWeekAllCompleted = $currentWeekPpmCompleted + $currentWeekWoCompleted;
                    
                    // Only process if there's actual activity in this week
                    if ($currentWeekAllTotal > 0 || $currentWeekAllCompleted > 0) {
                        // Calculate weekly MBV (OnTime - Late) for this week
                        $currentWeekAllOnTimeForMbv = $currentWeekPpmOnTime + ($woOnTimeWeightWeekly * $currentWeekWoOnTime) + $currentWeekPpmWithin; // Use weekly WO on-time weight
                        $currentWeekAllLateForMbv = $currentWeekPpmLate + $currentWeekWoLate;
                        $weeklyMbv = $currentWeekAllOnTimeForMbv - $currentWeekAllLateForMbv;

                        // Determine weeklyTierMultiplier for this week (using configurable Weekly MBV Tiers)
                        $weeklyTierMultiplier = $weeklyMbvTier1Divider; // Default
                        if ($weeklyMbv > $weeklyMbvTier2Threshold) { 
                            $weeklyTierMultiplier = $weeklyMbvTier3Divider; 
                        } else if ($weeklyMbv > $weeklyMbvTier1Threshold) { 
                            $weeklyTierMultiplier = $weeklyMbvTier2Divider; 
                        }
                        
                        // Accumulate weekly MBVs and Tier Multipliers for averages (for gmiMbv and gmiTierPoint)
                        $accumulatedWeeklyMbvs[$userId][] = $weeklyMbv;
                        $accumulatedWeeklyTierPoints[$userId][] = $weeklyTierMultiplier;

                        // --- Phase 3: Calculate weekly point components for summation ---
                        // WeeklyOnTimePointComponent = (weeklyWithin / weeklyTotal) * weeklyTierMultiplier
                        $weeklyAllWithinForOnTimeComp = $currentWeekPpmOnTime + $currentWeekPpmWithin + ($woOnTimeWeightWeekly * $currentWeekWoOnTime);
                        if ($currentWeekAllTotal > 0) {
                            $weeklyOnTimePointComponentsSum[$userId] += ($weeklyAllWithinForOnTimeComp / $currentWeekAllTotal) * $weeklyTierMultiplier;
                        }

                        // WeeklyLatePointComponent = (weeklyLate / weeklyCompleted) * weeklyTierMultiplier
                        if ($currentWeekAllCompleted > 0) {
                            $weeklyLatePointComponentsSum[$userId] += ($currentWeekAllLateForMbv / $currentWeekAllCompleted) * $weeklyTierMultiplier;
                        }
                    }
                }
            }
            
            // --- Phase 4: Final Point Calculation and Database Save ---
            // Process each user's accumulated data for final calculation and saving
            foreach ($rawMonthlyAccumulatedData as $userId => $gmi) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Processing user ' . $userId . ' for final calculations');
                $gmiId = $gmi['gmiId'];

                // Assign Tier Names based on monthly accumulated completed tasks (as per existing logic)
                if ($gmi['gmiPpmCompleted'] > 150) { 
                    $gmi['gmiPpmTierPoint'] = 1; 
                    $gmi['gmiPpmTierName'] = 'Medalist'; 
                } else if ($gmi['gmiPpmCompleted'] > 80) { 
                    $gmi['gmiPpmTierPoint'] = 1; 
                    $gmi['gmiPpmTierName'] = 'Finisher'; 
                } else {
                    $gmi['gmiPpmTierPoint'] = 0.5;
                    $gmi['gmiPpmTierName'] = 'Under Rated';
                }

                if ($gmi['gmiWoCompleted'] > 150) { 
                    $gmi['gmiWoTierPoint'] = 1; 
                    $gmi['gmiWoTierName'] = 'Medalist'; 
                } else if ($gmi['gmiWoCompleted'] > 80) { 
                    $gmi['gmiWoTierPoint'] = 1; 
                    $gmi['gmiWoTierName'] = 'Finisher'; 
                } else {
                    $gmi['gmiWoTierPoint'] = 0.5;
                    $gmi['gmiWoTierName'] = 'Under Rated';
                }

                $allTotal = $gmi['gmiPpmTotal'] + $gmi['gmiWoTotal'];
                $allCompleted = $gmi['gmiPpmCompleted'] + $gmi['gmiWoCompleted'];
                
                // Final gmiPointOnTime / gmiPointLate calculation using SUM of weekly components
                $gmi['gmiPointOnTime'] = ($weeklyOnTimePointComponentsSum[$userId] ?? 0) * $pointOnTimeCoeff * $pointScaleMultiplierMonthly;
                $gmi['gmiPointLate'] = - (($weeklyLatePointComponentsSum[$userId] ?? 0) * $pointLateCoeff * $pointScaleMultiplierMonthly);
                
                // Other point components 
                $gmi['gmiPointCompleted'] = ($allTotal > 0) ? ($allCompleted / $allTotal) * $pointCompletedCoeff * $pointScaleMultiplierMonthly : 0; 
                $gmi['gmiPointSelfFinding'] = intval($gmi['gmiWoSelfFinding']) * $pointSelfFindingMultiplier; 
                
                $gmi['gmiPointTotal'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointOnTime'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding']; 
                
                // Calculate Average MBV and TierPoint for storage/display in gmi_monthly
                $avgMbv = count($accumulatedWeeklyMbvs[$userId] ?? []) > 0 ? array_sum($accumulatedWeeklyMbvs[$userId]) / count($accumulatedWeeklyMbvs[$userId]) : 0;
                $avgTierPoint = count($accumulatedWeeklyTierPoints[$userId] ?? []) > 0 ? array_sum($accumulatedWeeklyTierPoints[$userId]) / count($accumulatedWeeklyTierPoints[$userId]) : $weeklyMbvTier1Divider;

                $gmi['gmiMbv'] = $avgMbv; 
                $gmi['gmiTierPoint'] = $avgTierPoint; 

                // Productivity Calculation
                $allWithinForProductivity = $gmi['gmiPpmOnTime'] + $gmi['gmiPpmWithin'] + ($woOnTimeWeightMonthly * $gmi['gmiWoOnTime']);
                
                $gmi['gmiProductivityLevel'] = ($allTotal > 0) ? $allWithinForProductivity / $allTotal * $productivityLevelBase : 0; 
                $gmi['gmiProductivityDeduction'] = $productivityLevelBase - $gmi['gmiProductivityLevel']; 
                
                $gmi['gmiPointLessProductive'] = ($allTotal > 0) ? ($allWithinForProductivity/$allTotal) * $gmi['gmiTierPoint'] * ($gmi['gmiProductivityDeduction']/100) * $pointScaleMultiplierMonthly : 0; 
                $gmi['gmiPointBeforeMinus'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointOnTime'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding']; 
                $gmi['gmiPointAfterMinus'] = $gmi['gmiPointBeforeMinus'] -  $gmi['gmiPointLessProductive']; 
                
                // ---- Save to database ----
                unset($gmi['gmiId']); 
                
                // Add validation to ensure required fields are not null
                if (empty($gmi['userId']) || empty($gmi['gmiYear']) || empty($gmi['gmiMonth'])) {
                    $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Missing required fields: userId=' . $gmi['userId'] . ', year=' . $gmi['gmiYear'] . ', month=' . $gmi['gmiMonth']);
                    continue; // Skip this record
                }
                
                // Ensure all numeric fields are properly converted
                $gmi['userId'] = intval($gmi['userId']);
                $gmi['gmiYear'] = intval($gmi['gmiYear']);
                $gmi['gmiMonth'] = intval($gmi['gmiMonth']);
                $gmi['siteId'] = intval($gmi['siteId']);
                
                // Ensure values are properly formatted as strings for database operations
                $whereCondition = array(
                    'user_id' => strval($gmi['userId']), 
                    'gmi_year' => strval($gmi['gmiYear']), 
                    'gmi_month' => strval($gmi['gmiMonth'])
                );
                
                $existingRecord = Class_db::getInstance()->db_select_single2('gmi_monthly', $whereCondition); 

                if (empty($existingRecord)) { 
                    Class_db::getInstance()->db_insert('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi)); 
                } else { 
                    Class_db::getInstance()->db_update('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi), array('gmi_id' => strval($existingRecord['gmiId']))); 
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