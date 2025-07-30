<?php

class Class_gamification {

    private $constant;
    private $fn_general;
    private $config;

    function __construct() {
        $this->loadGamificationConfig();
    }

    /**
     * Load gamification configuration from database
     * @throws Exception
     */
    private function loadGamificationConfig() {
        try {
            $configData = Class_db::getInstance()->db_select2('gmi_config', array('status' => '1'));
            $this->config = array();
            foreach ($configData as $config) {
                // Use camelCase column names for db_select2 method
                $value = $config['configValue'];
                // Convert based on data type
                switch ($config['dataType']) {
                    case 'int':
                        $value = intval($config['configValue']);
                        break;
                    case 'float':
                        $value = floatval($config['configValue']);
                        break;
                    case 'string':
                    default:
                        $value = $config['configValue'];
                        break;
                }
                $this->config[$config['configKey']] = $value;
            }
        } catch (Exception $ex) {
            // If config table doesn't exist or is empty, use default values
            $this->setDefaultConfig();
        }
    }

    /**
     * Set default configuration values as fallback
     */
    private function setDefaultConfig() {
        $this->config = array(
            'tier_medalist_threshold' => 150,
            'tier_finisher_threshold' => 80,
            'mbv_tier1_threshold' => 50,
            'mbv_tier2_threshold' => 100,
            'mbv_tier1_multiplier' => 1,
            'mbv_tier2_multiplier' => 3,
            'mbv_tier3_multiplier' => 5,
            'weight_completed' => 0.3,
            'weight_ontime' => 0.7,
            'weight_late_penalty' => 0.15,
            'self_finding_points' => 5,
            'point_scale_factor' => 10000,
            'productivity_base' => 90,
            'wo_ontime_multiplier' => 2,
            // Trade Ratio defaults
            'trade_ratio_mechanical' => 0.9,
            'trade_ratio_electrical' => 1.0,
            'trade_ratio_civil' => 1.0,
            'trade_ratio_verifier' => 1.0,
            'trade_ratio_reviewer' => 1.0,
            'trade_ratio_executor' => 1.0,
            'trade_ratio_default' => 1.0
        );
    }

    /**
     * Get configuration value by key
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getConfig($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Get Trade Ratio multiplier for PPM group
     * @param int $ppmGroupId
     * @return float
     */
    private function getTradeRatio($ppmGroupId) {
        // Check for specific PPM group configuration first
        if (!empty($ppmGroupId)) {
            $specificKey = "trade_ratio_group_$ppmGroupId";
            $specificRatio = $this->getConfig($specificKey);
            if ($specificRatio !== null) {
                return (float)$specificRatio;
            }
        }
        
        // Fall back to default trade ratio
        return $this->getConfig('trade_ratio_default', 1.0);
    }

    /**
     * Refresh configuration from database
     * @throws Exception
     */
    public function refreshConfig() {
        $this->loadGamificationConfig();
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
            return Class_db::getInstance()->db_select_single2('gmi_monthly', array('gmi_id'=>strval($gmiId)));
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
            return Class_db::getInstance()->db_select2('gmi_monthly', array('gmi_year'=>strval($year), 'gmi_month'=>strval($month)));
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
            return Class_db::getInstance()->db_select2('gmi_monthly', array('gmi_year'=>strval($year), 'gmi_month'=>strval($month)), 'gmi_point_total DESC', '5');
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
            return Class_db::getInstance()->db_select2('gmi_monthly', array('user_id'=>strval($userId), 'gmi_year'=>'<='.$year, 'w1'=>'IF(gmi_year = '.$year.', gmi_month <= '.$month.' , 1) = 1'), 'gmi_year DESC, gmi_month DESC');
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

            // Get the number of weeks in the month
            $weeksInMonth = $this->getWeeksInMonth($year, $month);
            $gmiMonthlyAggregated = array();

            // Process each week in the month
            for ($week = 1; $week <= $weeksInMonth; $week++) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Processing week ' . $week . ' of month ' . $month);
                
                // Get week date range
                $weekDateRange = $this->getWeekDateRange($year, $month, $week);
                $weekStartDate = $weekDateRange['start'];
                $weekEndDate = $weekDateRange['end'];
                
                // Calculate weekly scores for this week
                $gmiWeekly = $this->calculateWeeklyScores($year, $month, $week, $weekStartDate, $weekEndDate);
                
                // Store weekly data in gmi_weekly table
                $this->storeWeeklyData($gmiWeekly, $year, $month, $week);
                
                // Aggregate weekly scores into monthly totals
                foreach ($gmiWeekly as $userId => $weeklyData) {
                    if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
                        // Check if monthly record already exists
                        $existingMonthlyRecord = Class_db::getInstance()->db_select_single2('gmi_monthly', array(
                            'user_id' => strval($userId),
                            'gmi_year' => strval($year),
                            'gmi_month' => strval($month)
                        ));
                        
                        $existingGmiId = !empty($existingMonthlyRecord) ? $existingMonthlyRecord['gmiId'] : 0;
                        $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr($userId, $year, $month, $weeklyData['siteId'], $existingGmiId);
                    }
                    
                    // Aggregate counts (convert from gmw_ to gmi_ for monthly aggregation)
                    $gmiMonthlyAggregated[$userId]['gmiPpmTotal'] += $weeklyData['gmwPpmTotal'];
                    $gmiMonthlyAggregated[$userId]['gmiPpmCompleted'] += $weeklyData['gmwPpmCompleted'];
                    $gmiMonthlyAggregated[$userId]['gmiPpmOnTime'] += $weeklyData['gmwPpmOnTime'];
                    $gmiMonthlyAggregated[$userId]['gmiPpmLate'] += $weeklyData['gmwPpmLate'];
                    $gmiMonthlyAggregated[$userId]['gmiPpmWithin'] += $weeklyData['gmwPpmWithin'];
                    $gmiMonthlyAggregated[$userId]['gmiPpmAssist'] += $weeklyData['gmwPpmAssist'];
                    $gmiMonthlyAggregated[$userId]['gmiWoTotal'] += $weeklyData['gmwWoTotal'];
                    $gmiMonthlyAggregated[$userId]['gmiWoCompleted'] += $weeklyData['gmwWoCompleted'];
                    $gmiMonthlyAggregated[$userId]['gmiWoOnTime'] += $weeklyData['gmwWoOnTime'];
                    $gmiMonthlyAggregated[$userId]['gmiWoLate'] += $weeklyData['gmwWoLate'];
                    $gmiMonthlyAggregated[$userId]['gmiWoSelfFinding'] += $weeklyData['gmwWoSelfFinding'];
                    $gmiMonthlyAggregated[$userId]['gmiWoAssist'] += $weeklyData['gmwWoAssist'];
                    
                    // Accumulate weekly points (this is the key change - cumulative weekly scores)
                    $gmiMonthlyAggregated[$userId]['gmiPointCompleted'] += $weeklyData['gmwPointCompleted'];
                    $gmiMonthlyAggregated[$userId]['gmiPointOnTime'] += $weeklyData['gmwPointOnTime'];
                    $gmiMonthlyAggregated[$userId]['gmiPointLate'] += $weeklyData['gmwPointLate'];
                    $gmiMonthlyAggregated[$userId]['gmiPointSelfFinding'] += $weeklyData['gmwPointSelfFinding'];
                    $gmiMonthlyAggregated[$userId]['gmiPointTotal'] += $weeklyData['gmwPointTotal'];
                    $gmiMonthlyAggregated[$userId]['gmiPointLessProductive'] += $weeklyData['gmwPointLessProductive'];
                    $gmiMonthlyAggregated[$userId]['gmiPointBeforeMinus'] += $weeklyData['gmwPointBeforeMinus'];
                    $gmiMonthlyAggregated[$userId]['gmiPointAfterMinus'] += $weeklyData['gmwPointAfterMinus'];
                }
            }

            // Update tier information based on monthly aggregated data
            foreach ($gmiMonthlyAggregated as $userId => $gmi) {
                // Update tier information based on total monthly completion
                if ($gmi['gmiPpmCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmiMonthlyAggregated[$userId]['gmiPpmTierPoint'] = 1;
                    $gmiMonthlyAggregated[$userId]['gmiPpmTierName'] = 'Medalist';
                } else if ($gmi['gmiPpmCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmiMonthlyAggregated[$userId]['gmiPpmTierPoint'] = 1;
                    $gmiMonthlyAggregated[$userId]['gmiPpmTierName'] = 'Finisher';
                }

                if ($gmi['gmiWoCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmiMonthlyAggregated[$userId]['gmiWoTierPoint'] = 1;
                    $gmiMonthlyAggregated[$userId]['gmiWoTierName'] = 'Medalist';
                } else if ($gmi['gmiWoCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmiMonthlyAggregated[$userId]['gmiWoTierPoint'] = 1;
                    $gmiMonthlyAggregated[$userId]['gmiWoTierName'] = 'Finisher';
                }

                // Calculate monthly MBV and tier point for reference
                $allTotal = $gmi['gmiPpmTotal'] + $gmi['gmiWoTotal'];
                $allCompleted = $gmi['gmiPpmCompleted'] + $gmi['gmiWoCompleted'];
                $allOnTime = $gmi['gmiPpmOnTime'] + ($this->getConfig('wo_ontime_multiplier', 2)*$gmi['gmiWoOnTime']) + $gmi['gmiPpmWithin'];
                $allWithin = $gmi['gmiWoOnTime'] + $gmi['gmiPpmWithin'];
                $allLate = $gmi['gmiPpmLate'] + $gmi['gmiWoLate'];
                $mbv = $allOnTime - $allLate;
                
                if ($mbv <= $this->getConfig('mbv_tier1_threshold', 50)) {
                    $tierDivider = $this->getConfig('mbv_tier1_multiplier', 1);
                } else if ($mbv <= $this->getConfig('mbv_tier2_threshold', 100)) {
                    $tierDivider = $this->getConfig('mbv_tier2_multiplier', 3);
                } else {
                    $tierDivider = $this->getConfig('mbv_tier3_multiplier', 5);
                }

                $gmiMonthlyAggregated[$userId]['gmiMbv'] = $mbv;
                $gmiMonthlyAggregated[$userId]['gmiTierPoint'] = $tierDivider;
                
                // Calculate productivity based on monthly totals
                if ($allTotal > 0) {
                    $gmiMonthlyAggregated[$userId]['gmiProductivityLevel'] = $allWithin / $allTotal * $this->getConfig('productivity_base', 90);
                    $gmiMonthlyAggregated[$userId]['gmiProductivityDeduction'] = $this->getConfig('productivity_base', 90) - $gmiMonthlyAggregated[$userId]['gmiProductivityLevel'];
                } else {
                    $gmiMonthlyAggregated[$userId]['gmiProductivityLevel'] = 0;
                    $gmiMonthlyAggregated[$userId]['gmiProductivityDeduction'] = $this->getConfig('productivity_base', 90);
                }
            }

            // Save monthly aggregated data to gmi_monthly table
            foreach ($gmiMonthlyAggregated as $gmi) {
                $gmiId = $gmi['gmiId'];
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
            return Class_db::getInstance()->db_select_single2('gmi_monthly', array('user_id'=>strval($userId), 'gmi_year'=>strval($year), 'gmi_month'=>strval($month)));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Get the number of weeks in a month
     * @param int $year
     * @param int $month
     * @return int
     */
    private function getWeeksInMonth($year, $month) {
        $firstDay = new DateTime("$year-$month-01");
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        
        // Get the week number of first and last day
        $firstWeek = intval($firstDay->format('W'));
        $lastWeek = intval($lastDay->format('W'));
        
        // Handle year boundary cases
        if ($month == 1 && $firstWeek > 50) {
            // January with first week belonging to previous year
            $firstWeek = 1;
        }
        if ($month == 12 && $lastWeek == 1) {
            // December with last week belonging to next year
            $lastWeek = 53;
        }
        
        return max(1, $lastWeek - $firstWeek + 1);
    }

    /**
     * Convert month-based week to year-based week number
     * @param int $year
     * @param int $month
     * @param int $week Week within the month (1-5)
     * @return int Week number within the year (1-53)
     */
    private function calculateWeekOfYear($year, $month, $week) {
        $firstDay = new DateTime("$year-$month-01");
        
        // Calculate the target date for this week within the month
        $targetDate = clone $firstDay;
        $targetDate->modify('+' . (($week - 1) * 7) . ' days');
        
        // Adjust to start of week (Monday)
        $dayOfWeek = $targetDate->format('N'); // 1 = Monday, 7 = Sunday
        if ($dayOfWeek != 1) {
            $targetDate->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        
        // Get the ISO week number
        return intval($targetDate->format('W'));
    }

    /**
     * Get the date range for a specific week in a month
     * @param int $year
     * @param int $month
     * @param int $week
     * @return array
     */
    private function getWeekDateRange($year, $month, $week) {
        $firstDay = new DateTime("$year-$month-01");
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        
        // Calculate week start (Monday) and end (Sunday)
        $weekStart = clone $firstDay;
        $weekStart->modify('+' . (($week - 1) * 7) . ' days');
        
        // Adjust to start of week (Monday)
        $dayOfWeek = $weekStart->format('N'); // 1 = Monday, 7 = Sunday
        if ($dayOfWeek != 1) {
            $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days'); // Sunday
        
        // Ensure we don't go beyond the month boundaries
        if ($weekStart < $firstDay) {
            $weekStart = $firstDay;
        }
        if ($weekEnd > $lastDay) {
            $weekEnd = $lastDay;
        }
        
        return array(
            'start' => $weekStart->format('Y-m-d'),
            'end' => $weekEnd->format('Y-m-d')
        );
    }

    /**
     * Calculate weekly scores for a specific week
     * @param int $year
     * @param int $month
     * @param int $week
     * @param string $weekStartDate
     * @param string $weekEndDate
     * @return array
     * @throws Exception
     */
    private function calculateWeeklyScores($year, $month, $week, $weekStartDate, $weekEndDate) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Calculating weekly scores for week ' . $week);
            
            $gmiWeekly = array();
            
            // Get PPM data for the week - using date range instead of monthly view
            $gmiPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, 
                array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
            
            foreach ($gmiPpm as $ppm) {
                $userId = intval($ppm['ppmTaskAssignedTo']);
                $ppmGroupId = intval($ppm['ppmGroupId'] ?? 0); // Default to 0 if not specified
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiWeekly)) {
                    $gmiWeekly[$userId] = $this->setInitialGmiWeekArr($userId, $year, $month, $week, $ppm['siteId'], 0);
                }
                
                // Apply trade ratio to task completion counts
                $gmiWeekly[$userId]['gmwPpmTotal'] += round(intval($ppm['ppmTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmCompleted'] += round(intval($ppm['ppmCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmOnTime'] += round(intval($ppm['ppmOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmLate'] += round(intval($ppm['ppmLate']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmWithin'] += round(intval($ppm['ppmWithin']) * $tradeRatio);
            }

            // Get PPM Assist data for the week
            $gmiPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_daily', array(), '', '', 0, 
                array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
            
            foreach ($gmiPpmAssist as $ppmAssist) {
                $userId = intval($ppmAssist['userId']);
                $ppmGroupId = intval($ppmAssist['ppmGroupId'] ?? 0); // Default to 0 if not specified
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiWeekly)) {
                    $gmiWeekly[$userId] = $this->setInitialGmiWeekArr($userId, $year, $month, $week, $ppmAssist['siteId'], 0);
                }
                
                // Apply trade ratio to task completion counts
                $gmiWeekly[$userId]['gmwPpmAssist'] += round(intval($ppmAssist['ppmTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmTotal'] += round(intval($ppmAssist['ppmTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmCompleted'] += round(intval($ppmAssist['ppmCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmOnTime'] += round(intval($ppmAssist['ppmOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmLate'] += round(intval($ppmAssist['ppmLate']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmWithin'] += round(intval($ppmAssist['ppmWithin']) * $tradeRatio);
            }

            // Get WO data for the week
            $gmiWo = Class_db::getInstance()->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
                array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
            
            foreach ($gmiWo as $wo) {
                $userId = intval($wo['woTaskAssignedTo']);
                $ppmGroupId = intval($wo['ppmGroupId'] ?? 0); // Default to 0 if not specified
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiWeekly)) {
                    $gmiWeekly[$userId] = $this->setInitialGmiWeekArr($userId, $year, $month, $week, $wo['siteId'], 0);
                }
                
                // Apply trade ratio to task completion counts
                $gmiWeekly[$userId]['gmwWoTotal'] += round(intval($wo['woTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoCompleted'] += round(intval($wo['woCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoOnTime'] += round(intval($wo['woOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoLate'] += round(intval($wo['woLate']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoSelfFinding'] += round(intval($wo['woSelfFinding']) * $tradeRatio);
            }

            // Get WO Assist data for the week
            $gmiWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_daily', array(), '', '', 0, 
                array('dateStart'=>$weekStartDate, 'dateEnd'=>$weekEndDate));
            
            foreach ($gmiWoAssist as $woAssist) {
                $userId = intval($woAssist['userId']);
                $ppmGroupId = intval($woAssist['ppmGroupId'] ?? 0); // Default to 0 if not specified
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiWeekly)) {
                    $gmiWeekly[$userId] = $this->setInitialGmiWeekArr($userId, $year, $month, $week, $woAssist['siteId'], 0);
                }
                
                // Apply trade ratio to task completion counts
                $gmiWeekly[$userId]['gmwWoAssist'] += round(intval($woAssist['woTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoTotal'] += round(intval($woAssist['woTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoCompleted'] += round(intval($woAssist['woCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoOnTime'] += round(intval($woAssist['woOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoLate'] += round(intval($woAssist['woLate']) * $tradeRatio);
            }

            // Calculate points for each user for this week
            foreach ($gmiWeekly as $userId => $gmi) {
                // Calculate weekly totals
                $allTotal = $gmi['gmwPpmTotal'] + $gmi['gmwWoTotal'];
                $allCompleted = $gmi['gmwPpmCompleted'] + $gmi['gmwWoCompleted'];
                $allOnTime = $gmi['gmwPpmOnTime'] + ($this->getConfig('wo_ontime_multiplier', 2)*$gmi['gmwWoOnTime']) + $gmi['gmwPpmWithin'];
                $allWithin = $gmi['gmwWoOnTime'] + $gmi['gmwPpmWithin'];
                $allLate = $gmi['gmwPpmLate'] + $gmi['gmwWoLate'];
                $mbv = $allOnTime - $allLate;
                
                // Determine tier multiplier based on weekly MBV
                if ($mbv <= $this->getConfig('mbv_tier1_threshold', 50)) {
                    $tierDivider = $this->getConfig('mbv_tier1_multiplier', 1);
                } else if ($mbv <= $this->getConfig('mbv_tier2_threshold', 100)) {
                    $tierDivider = $this->getConfig('mbv_tier2_multiplier', 3);
                } else {
                    $tierDivider = $this->getConfig('mbv_tier3_multiplier', 5);
                }

                // Calculate weekly points
                if ($allTotal > 0) {
                    $gmiWeekly[$userId]['gmwPointCompleted'] = ($allCompleted/$allTotal) * $this->getConfig('weight_completed', 0.3) * $this->getConfig('point_scale_factor', 10000);
                    $gmiWeekly[$userId]['gmwPointOnTime'] = (($allWithin/$allTotal) * $tierDivider) * $this->getConfig('weight_ontime', 0.7) * $this->getConfig('point_scale_factor', 10000);
                    $gmiWeekly[$userId]['gmwPointLate'] = $allCompleted === 0 ? 0 : -(($allLate/$allCompleted) * $tierDivider) * $this->getConfig('weight_late_penalty', 0.15) * $this->getConfig('point_scale_factor', 10000);
                    
                    // Productivity calculations
                    $gmiWeekly[$userId]['gmwProductivityLevel'] = $allWithin / $allTotal * $this->getConfig('productivity_base', 90);
                    $gmiWeekly[$userId]['gmwProductivityDeduction'] = $this->getConfig('productivity_base', 90) - $gmiWeekly[$userId]['gmwProductivityLevel'];
                    $gmiWeekly[$userId]['gmwPointLessProductive'] = ($allWithin/$allTotal) * $tierDivider * ($gmiWeekly[$userId]['gmwProductivityDeduction']/100) * $this->getConfig('point_scale_factor', 10000);
                } else {
                    $gmiWeekly[$userId]['gmwPointCompleted'] = 0;
                    $gmiWeekly[$userId]['gmwPointOnTime'] = 0;
                    $gmiWeekly[$userId]['gmwPointLate'] = 0;
                    $gmiWeekly[$userId]['gmwProductivityLevel'] = 0;
                    $gmiWeekly[$userId]['gmwProductivityDeduction'] = $this->getConfig('productivity_base', 90);
                    $gmiWeekly[$userId]['gmwPointLessProductive'] = 0;
                }
                
                $gmiWeekly[$userId]['gmwPointSelfFinding'] = intval($gmi['gmwWoSelfFinding']) * $this->getConfig('self_finding_points', 5);
                $gmiWeekly[$userId]['gmwPointRework'] = 0; // Default for rework points (you can implement logic if needed)
                $gmiWeekly[$userId]['gmwPointBeforeMinus'] = $gmiWeekly[$userId]['gmwPointCompleted'] + $gmiWeekly[$userId]['gmwPointLate'] + $gmiWeekly[$userId]['gmwPointSelfFinding'] + $gmiWeekly[$userId]['gmwPointOnTime'] + $gmiWeekly[$userId]['gmwPointRework'];
                $gmiWeekly[$userId]['gmwPointAfterMinus'] = $gmiWeekly[$userId]['gmwPointBeforeMinus'] - $gmiWeekly[$userId]['gmwPointLessProductive'];
                $gmiWeekly[$userId]['gmwPointTotal'] = $gmiWeekly[$userId]['gmwPointAfterMinus'];
                $gmiWeekly[$userId]['gmwMbv'] = $mbv;
                $gmiWeekly[$userId]['gmwTierPoint'] = $tierDivider;
                
                // Set tier names based on weekly completion
                if ($gmi['gmwPpmCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmiWeekly[$userId]['gmwPpmTierPoint'] = 1;
                    $gmiWeekly[$userId]['gmwPpmTierName'] = 'Medalist';
                } else if ($gmi['gmwPpmCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmiWeekly[$userId]['gmwPpmTierPoint'] = 1;
                    $gmiWeekly[$userId]['gmwPpmTierName'] = 'Finisher';
                }

                if ($gmi['gmwWoCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmiWeekly[$userId]['gmwWoTierPoint'] = 1;
                    $gmiWeekly[$userId]['gmwWoTierName'] = 'Medalist';
                } else if ($gmi['gmwWoCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmiWeekly[$userId]['gmwWoTierPoint'] = 1;
                    $gmiWeekly[$userId]['gmwWoTierName'] = 'Finisher';
                }
            }

            return $gmiWeekly;
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Initialize weekly gamification array for a user (adapted for existing gmi_weekly table structure)
     * @param int $userId
     * @param int $year
     * @param int $month
     * @param int $week
     * @param int $siteId
     * @param int $gmiId
     * @return array
     */
    private function setInitialGmiWeekArr($userId, $year, $month, $week, $siteId, $gmiId) {
        $returnArr = array();
        // Map to existing table structure with gmw_ prefix
        $returnArr['userId'] = $userId;
        $returnArr['siteId'] = $siteId;
        $returnArr['gmwYear'] = $year;
        $returnArr['gmwWeek'] = $this->calculateWeekOfYear($year, $month, $week); // Convert month-week to year-week
        $returnArr['gmwPpmTierName'] = 'Under Rated';
        $returnArr['gmwPpmTierPoint'] = 0.5;
        $returnArr['gmwPpmTotal'] = 0;
        $returnArr['gmwPpmCompleted'] = 0;
        $returnArr['gmwPpmOnTime'] = 0;
        $returnArr['gmwPpmLate'] = 0;
        $returnArr['gmwPpmWithin'] = 0;
        $returnArr['gmwPpmAssist'] = 0;
        $returnArr['gmwWoTierName'] = 'Under Rated';
        $returnArr['gmwWoTierPoint'] = 0.5;
        $returnArr['gmwWoTotal'] = 0;
        $returnArr['gmwWoCompleted'] = 0;
        $returnArr['gmwWoOnTime'] = 0;
        $returnArr['gmwWoLate'] = 0;
        $returnArr['gmwWoRework'] = 0; // Existing field in your table
        $returnArr['gmwWoSelfFinding'] = 0;
        $returnArr['gmwWoAssist'] = 0;
        $returnArr['gmwMbv'] = 0;
        $returnArr['gmwTierPoint'] = 1; // tinyint(1) in your table
        $returnArr['gmwPointCompleted'] = 0;
        $returnArr['gmwPointOnTime'] = 0;
        $returnArr['gmwPointLate'] = 0;
        $returnArr['gmwPointRework'] = 0; // Existing field in your table
        $returnArr['gmwPointSelfFinding'] = 0;
        $returnArr['gmwPointTotal'] = 0;
        $returnArr['gmwProductivityLevel'] = 0;
        $returnArr['gmwProductivityDeduction'] = 0;
        $returnArr['gmwPointLessProductive'] = 0;
        $returnArr['gmwPointBeforeMinus'] = 0;
        $returnArr['gmwPointAfterMinus'] = 0;
        return $returnArr;
    }

    /**
     * Store weekly gamification data (adapted for existing gmi_weekly table structure)
     * @param array $gmiWeekly
     * @param int $year
     * @param int $month
     * @param int $week
     * @throws Exception
     */
    private function storeWeeklyData($gmiWeekly, $year, $month, $week) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Storing weekly data for week ' . $week);
            
            foreach ($gmiWeekly as $gmi) {
                $yearWeek = $this->calculateWeekOfYear($year, $month, $week);
                
                // Check if weekly record already exists using available columns
                $existingRecord = Class_db::getInstance()->db_select_single2('gmi_weekly', array(
                    'user_id' => strval($gmi['userId']),
                    'gmw_year' => strval($year),
                    'gmw_week' => strval($yearWeek)
                ));
                
                // Prepare data array for your existing table structure
                $weeklyData = array(
                    'userId' => $gmi['userId'],
                    'siteId' => $gmi['siteId'],
                    'gmwYear' => $gmi['gmwYear'],
                    'gmwWeek' => $gmi['gmwWeek'],
                    'gmwPpmTierName' => $gmi['gmwPpmTierName'],
                    'gmwPpmTierPoint' => $gmi['gmwPpmTierPoint'],
                    'gmwPpmTotal' => $gmi['gmwPpmTotal'],
                    'gmwPpmCompleted' => $gmi['gmwPpmCompleted'],
                    'gmwPpmOnTime' => $gmi['gmwPpmOnTime'],
                    'gmwPpmLate' => $gmi['gmwPpmLate'],
                    'gmwPpmWithin' => $gmi['gmwPpmWithin'],
                    'gmwPpmAssist' => $gmi['gmwPpmAssist'],
                    'gmwWoTierName' => $gmi['gmwWoTierName'],
                    'gmwWoTierPoint' => $gmi['gmwWoTierPoint'],
                    'gmwWoTotal' => $gmi['gmwWoTotal'],
                    'gmwWoCompleted' => $gmi['gmwWoCompleted'],
                    'gmwWoOnTime' => $gmi['gmwWoOnTime'],
                    'gmwWoLate' => $gmi['gmwWoLate'],
                    'gmwWoRework' => $gmi['gmwWoRework'],
                    'gmwWoSelfFinding' => $gmi['gmwWoSelfFinding'],
                    'gmwWoAssist' => $gmi['gmwWoAssist'],
                    'gmwMbv' => $gmi['gmwMbv'],
                    'gmwTierPoint' => $gmi['gmwTierPoint'],
                    'gmwPointCompleted' => $gmi['gmwPointCompleted'],
                    'gmwPointOnTime' => $gmi['gmwPointOnTime'],
                    'gmwPointLate' => $gmi['gmwPointLate'],
                    'gmwPointRework' => $gmi['gmwPointRework'],
                    'gmwPointSelfFinding' => $gmi['gmwPointSelfFinding'],
                    'gmwPointTotal' => $gmi['gmwPointTotal'],
                    'gmwProductivityLevel' => $gmi['gmwProductivityLevel'],
                    'gmwProductivityDeduction' => $gmi['gmwProductivityDeduction'],
                    'gmwPointLessProductive' => $gmi['gmwPointLessProductive'],
                    'gmwPointBeforeMinus' => $gmi['gmwPointBeforeMinus'],
                    'gmwPointAfterMinus' => $gmi['gmwPointAfterMinus']
                );
                
                if (empty($existingRecord)) {
                    // Insert new weekly record
                    Class_db::getInstance()->db_insert('gmi_weekly', $this->fn_general->convertToMysqlArrAll($weeklyData));
                } else {
                    // Update existing weekly record using available columns
                    Class_db::getInstance()->db_update('gmi_weekly', $this->fn_general->convertToMysqlArrAll($weeklyData), array(
                        'user_id' => strval($gmi['userId']),
                        'gmw_year' => strval($year),
                        'gmw_week' => strval($yearWeek)
                    ));
                }
            }
            
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}