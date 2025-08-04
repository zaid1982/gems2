<?php

class Class_gamification {

    private $constant;
    private $fn_general;
    private $config;

    function __construct() {
        $this->fn_general = new Class_general();
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
            
            if (empty($configData)) {
                // Log warning if no active config found
                if (isset($this->fn_general)) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No active configuration found in gmi_config table, using defaults');
                }
                $this->setDefaultConfig();
                return;
            }
            
            foreach ($configData as $config) {
                // Handle different possible column name formats
                $configKey = isset($config['configKey']) ? $config['configKey'] : 
                           (isset($config['config_key']) ? $config['config_key'] : null);
                $configValue = isset($config['configValue']) ? $config['configValue'] : 
                             (isset($config['config_value']) ? $config['config_value'] : null);
                $dataType = isset($config['dataType']) ? $config['dataType'] : 
                          (isset($config['data_type']) ? $config['data_type'] : 'string');
                
                if ($configKey === null || $configValue === null) {
                    continue; // Skip invalid config entries
                }
                
                // Convert based on data type
                switch ($dataType) {
                    case 'int':
                        $value = intval($configValue);
                        break;
                    case 'float':
                        $value = floatval($configValue);
                        break;
                    case 'string':
                    default:
                        $value = $configValue;
                        break;
                }
                $this->config[$configKey] = $value;
                
                // Log each loaded config for debugging
                if (isset($this->fn_general)) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "Loaded config: $configKey = $value (type: $dataType)");
                }
            }
            
            // Log total number of configs loaded
            if (isset($this->fn_general)) {
                $configCount = count($this->config);
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "Successfully loaded $configCount configuration values from database");
            }
            
        } catch (Exception $ex) {
            // If config table doesn't exist or is empty, use default values
            if (isset($this->fn_general)) {
                $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 
                    'Failed to load config from database: ' . $ex->getMessage() . ', using defaults');
            }
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
     * Get Work Order details for gamification calculation for a specific month
     * @param $year
     * @param $month
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getWoDetailsForGamification($year, $month, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($year, $month, $userId));
            
            // Calculate date range for the month
            $monthStart = "$year-" . sprintf('%02d', $month) . "-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Processing WO details for User: $userId, Year: $year, Month: $month");
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Date range: $monthStart to $monthEnd");
            
            $woDetails = array();
            
            // Debug: Check what WO data runMonthly would use for comparison
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "=== COMPARISON: What runMonthly uses for WO data ===");
            
            // Query same view that runMonthly uses for WO data
            $runMonthlyWoData = Class_db::getInstance()->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
                array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "runMonthly would find " . count($runMonthlyWoData) . " WO records from vw_gamification_wo_daily");
            
            $runMonthlyUserWos = array_filter($runMonthlyWoData, function($wo) use ($userId) {
                return intval($wo['woTaskAssignedTo']) == intval($userId);
            });
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "runMonthly would find " . count($runMonthlyUserWos) . " WO records for user $userId");
            
            if (!empty($runMonthlyUserWos)) {
                foreach ($runMonthlyUserWos as $wo) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "runMonthly WO: AssignedTo={$wo['woTaskAssignedTo']}, Total={$wo['woTotal']}, " .
                        "Completed={$wo['woCompleted']}, OnTime={$wo['woOnTime']}, Late={$wo['woLate']}, " .
                        "SelfFinding={$wo['woSelfFinding']}");
                }
            }
            
            // Query same view for WO Assist data
            $runMonthlyWoAssistData = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_daily', array(), '', '', 0, 
                array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "runMonthly would find " . count($runMonthlyWoAssistData) . " WO Assist records from vw_gamification_wo_assist_daily");
            
            $runMonthlyUserWoAssists = array_filter($runMonthlyWoAssistData, function($wo) use ($userId) {
                return intval($wo['userId']) == intval($userId);
            });
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "runMonthly would find " . count($runMonthlyUserWoAssists) . " WO Assist records for user $userId");
            
            if (!empty($runMonthlyUserWoAssists)) {
                foreach ($runMonthlyUserWoAssists as $wo) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "runMonthly WO Assist: UserId={$wo['userId']}, Total={$wo['woTotal']}, " .
                        "Completed={$wo['woCompleted']}, OnTime={$wo['woOnTime']}, Late={$wo['woLate']}");
                }
            }
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "=== NOW: What getWoDetailsForGamification finds ===");
            
            // Get WO tasks assigned to user with date and status filtering (by reported date)
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Querying wo_task table with asset join, filters: wo_task_assigned_to=$userId, wo_task_status=16, " .
                "wo_task_time_created between $monthStart and $monthEnd");
            
            // Create a custom view-like query to get complete WO task data with asset information
            $woTaskView = "(SELECT 
                wt.wo_task_id, wt.wo_task_no, wt.wo_task_complaint, wt.wo_task_status,
                wt.wo_task_assigned_to, wt.wo_task_time_created, wt.wo_task_time_verified,
                wt.wo_task_time_assigned, wt.wo_task_type,
                ast.asset_no, ast.asset_desc,
                CASE 
                    WHEN wt.wo_task_time_verified <= DATE_ADD(wt.wo_task_time_assigned, INTERVAL 24 HOUR) THEN 'On-Time'
                    WHEN wt.wo_task_time_verified > DATE_ADD(wt.wo_task_time_assigned, INTERVAL 24 HOUR) THEN 'Late'
                    ELSE 'Pending'
                END as wo_on_time_status
            FROM wo_task wt
            LEFT JOIN ast_asset ast ON ast.asset_id = wt.asset_id) as vw_wo_task_complete";
            
            // Use db_select2 with the custom view and parameters
            $woTasks = Class_db::getInstance()->db_select2($woTaskView, 
                array(
                    'wo_task_assigned_to' => $userId,
                    'wo_task_status' => '16', // Only completed tasks
                    'w1' => "DATE(wo_task_time_created) >= '$monthStart'",
                    'w2' => "DATE(wo_task_time_created) <= '$monthEnd'"
                ),
                'wo_task_time_created DESC', '', 0
            );
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Found " . count($woTasks) . " direct WO task assignments");
            
            foreach ($woTasks as $index => $wt) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "WO Task #" . ($index + 1) . ": ID=" . (isset($wt['woTaskId']) ? $wt['woTaskId'] : 'NULL') . 
                    ", No=" . (isset($wt['woTaskNo']) ? $wt['woTaskNo'] : 'NULL') . 
                    ", Status=" . (isset($wt['woTaskStatus']) ? $wt['woTaskStatus'] : 'NULL') . 
                    ", AssignedTo=" . (isset($wt['woTaskAssignedTo']) ? $wt['woTaskAssignedTo'] : 'NULL') . 
                    ", Created=" . (isset($wt['woTaskTimeCreated']) ? $wt['woTaskTimeCreated'] : 'NULL') . 
                    ", Verified=" . (isset($wt['woTaskTimeVerified']) ? $wt['woTaskTimeVerified'] : 'NULL'));
                
                $woDetails[] = array(
                    'woNo' => isset($wt['woTaskNo']) ? ($wt['woTaskNo'] ?: 'N/A') : 'N/A',
                    'woDesc' => isset($wt['woTaskComplaint']) ? ($wt['woTaskComplaint'] ?: '') : '',
                    'assetDesc' => isset($wt['assetDesc']) ? ($wt['assetDesc'] ?: (isset($wt['assetNo']) ? $wt['assetNo'] : 'N/A')) : 'N/A',
                    'woStatus' => 'Completed',
                    'woPriority' => '', // Priority field removed from query temporarily
                    'woCreateDate' => isset($wt['woTaskTimeCreated']) ? $wt['woTaskTimeCreated'] : '',
                    'woTargetDate' => isset($wt['woTaskTimeAssigned']) ? $wt['woTaskTimeAssigned'] : '',
                    'woCompletedDate' => isset($wt['woTaskTimeVerified']) ? $wt['woTaskTimeVerified'] : '',
                    'woOnTimeStatus' => isset($wt['woOnTimeStatus']) ? $wt['woOnTimeStatus'] : 'Pending',
                    'woType' => isset($wt['woTaskType']) ? $wt['woTaskType'] : '',
                    'woRole' => 'Assigned' // This user is the primary assignee
                );
            }
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Processed " . count($woTasks) . " direct WO assignments");
            
            // Get WO assist tasks
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Querying wo_task_assist table for user_id=$userId");
            
            $woAssistRecords = Class_db::getInstance()->db_select2('wo_task_assist', 
                array('user_id' => $userId), '', '', 0);
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Found " . count($woAssistRecords) . " WO assist records");
                
            $assistTasksProcessed = 0;
            foreach ($woAssistRecords as $index => $wta) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "WO Assist #" . ($index + 1) . ": UserId=" . (isset($wta['userId']) ? $wta['userId'] : 'NULL') . 
                    ", woTaskId=" . (isset($wta['woTaskId']) ? $wta['woTaskId'] : 'NULL'));
                
                if (empty($wta['woTaskId'])) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "Skipping assist record - woTaskId is empty");
                    continue;
                }
                
                // Get the corresponding wo_task record with date and status filtering (by reported date)
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "Looking up wo_task for assist: wo_task_id={$wta['woTaskId']}, status=16, " .
                    "created between $monthStart and $monthEnd");
                
                // Use the same enhanced query to get complete WO task data
                $wt = Class_db::getInstance()->db_select_single2($woTaskView, 
                    array(
                        'wo_task_id' => $wta['woTaskId'],
                        'wo_task_status' => '16', // Only completed tasks
                        'w1' => "DATE(wo_task_time_created) >= '$monthStart'",
                        'w2' => "DATE(wo_task_time_created) <= '$monthEnd'"
                    ));
                    
                if (!$wt) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "No matching wo_task found for assist record (either not completed or outside date range)");
                    continue;
                }
                
                $assistTasksProcessed++;
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "Found matching WO Task for assist: ID=" . (isset($wt['woTaskId']) ? $wt['woTaskId'] : 'NULL') . 
                    ", No=" . (isset($wt['woTaskNo']) ? $wt['woTaskNo'] : 'NULL') . 
                    ", Status=" . (isset($wt['woTaskStatus']) ? $wt['woTaskStatus'] : 'NULL') . 
                    ", AssignedTo=" . (isset($wt['woTaskAssignedTo']) ? $wt['woTaskAssignedTo'] : 'NULL') . 
                    ", Created=" . (isset($wt['woTaskTimeCreated']) ? $wt['woTaskTimeCreated'] : 'NULL') . 
                    ", Verified=" . (isset($wt['woTaskTimeVerified']) ? $wt['woTaskTimeVerified'] : 'NULL'));
                
                $woDetails[] = array(
                    'woNo' => isset($wt['woTaskNo']) ? ($wt['woTaskNo'] ?: 'N/A') : 'N/A',
                    'woDesc' => isset($wt['woTaskComplaint']) ? ($wt['woTaskComplaint'] ?: '') : '',
                    'assetDesc' => isset($wt['assetDesc']) ? ($wt['assetDesc'] ?: (isset($wt['assetNo']) ? $wt['assetNo'] : 'N/A')) : 'N/A',
                    'woStatus' => 'Completed',
                    'woPriority' => '', // Priority field removed from query temporarily
                    'woCreateDate' => isset($wt['woTaskTimeCreated']) ? $wt['woTaskTimeCreated'] : '',
                    'woTargetDate' => isset($wt['woTaskTimeAssigned']) ? $wt['woTaskTimeAssigned'] : '',
                    'woCompletedDate' => isset($wt['woTaskTimeVerified']) ? $wt['woTaskTimeVerified'] : '',
                    'woOnTimeStatus' => isset($wt['woOnTimeStatus']) ? $wt['woOnTimeStatus'] : 'Pending',
                    'woType' => isset($wt['woTaskType']) ? $wt['woTaskType'] : '',
                    'woRole' => 'Assist' // This user provided assistance
                );
            }
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Processed $assistTasksProcessed out of " . count($woAssistRecords) . " assist records");
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Total WO details before deduplication: " . count($woDetails));
            
            // Remove duplicates based on WO number
            $uniqueWoDetails = array();
            $seenWoNos = array();
            $duplicatesRemoved = 0;
            
            foreach ($woDetails as $wo) {
                if (!in_array($wo['woNo'], $seenWoNos)) {
                    $seenWoNos[] = $wo['woNo'];
                    $uniqueWoDetails[] = $wo;
                } else {
                    $duplicatesRemoved++;
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "Removed duplicate WO: {$wo['woNo']}");
                }
            }
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Removed $duplicatesRemoved duplicates, final count: " . count($uniqueWoDetails));
            
            // Sort by completion date descending
            usort($uniqueWoDetails, function($a, $b) {
                return strcmp($b['woCompletedDate'], $a['woCompletedDate']);
            });
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Final result summary:");
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "- Direct assignments: " . count($woTasks));
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "- Assist tasks processed: $assistTasksProcessed");
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "- Total before dedup: " . count($woDetails));
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "- Duplicates removed: $duplicatesRemoved");
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "- Final unique count: " . count($uniqueWoDetails));
            
            if (!empty($uniqueWoDetails)) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "Sample of final results:");
                foreach (array_slice($uniqueWoDetails, 0, 3) as $index => $wo) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "Result #" . ($index + 1) . ": woNo={$wo['woNo']}, status={$wo['woStatus']}, " .
                        "completed={$wo['woCompletedDate']}");
                }
            }
            
            return $uniqueWoDetails;
            
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
            $returnArr['gmiPpmTierPoint'] = 1;
            $returnArr['gmiPpmTierName'] = 'Under Rated';
            $returnArr['gmiWoTotal'] = 0;
            $returnArr['gmiWoCompleted'] = 0;
            $returnArr['gmiWoOnTime'] = 0;
            $returnArr['gmiWoLate'] = 0;
            $returnArr['gmiWoSelfFinding'] = 0;
            $returnArr['gmiWoAssist'] = 0;
            $returnArr['gmiWoTierPoint'] = 1;
            $returnArr['gmiWoTierName'] = 'Under Rated';
            $returnArr['gmiPointCompleted'] = 0;
            $returnArr['gmiPointOnTime'] = 0;
            $returnArr['gmiPointLate'] = 0;
            $returnArr['gmiPointSelfFinding'] = 0;
            $returnArr['gmiPointTotal'] = 0;
            $returnArr['gmiPointLessProductive'] = 0;
            $returnArr['gmiPointBeforeMinus'] = 0;
            $returnArr['gmiPointAfterMinus'] = 0;
            $returnArr['gmiMbv'] = 0;
            $returnArr['gmiTierPoint'] = 1;
            $returnArr['gmiProductivityLevel'] = 0;
            $returnArr['gmiProductivityDeduction'] = 0;
            return $returnArr;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param int $year
     * @param int $month
     * @throws Exception
     */
    public function runMonthly($year, $month)
    {
        try {
            // --- Setup & validation ---
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams([$year, $month]);
            
            // Refresh configuration from database to ensure latest values are used
            $this->refreshConfig();

            // Choose data collection method based on configuration
            $useWeeklyProcessing = $this->getConfig('use_weekly_processing', true);
            
            if ($useWeeklyProcessing) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Using weekly processing method');
                $this->runMonthlyWithWeeklyProcessing($year, $month);
            } else {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Using direct monthly processing method');
                $this->runMonthlyWithDirectProcessing($year, $month);
            }

        } catch (Exception $ex) {
            // Log and rethrow
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception(
                $this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()),
                $ex->getCode()
            );
        }
    }

    /**
     * Run monthly gamification using weekly processing (existing method)
     * @param int $year
     * @param int $month
     * @throws Exception
     */
    private function runMonthlyWithWeeklyProcessing($year, $month)
    {
        try {

            // Determine how many weeks in the given month
            $weeksInMonth = $this->getWeeksInMonth($year, $month);
            $gmiMonthlyAggregated = [];

            // --- Weekly processing & aggregation ---
            for ($week = 1; $week <= $weeksInMonth; $week++) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Processing week ' . $week . ' of month ' . $month);

                // Calculate week date range
                $range     = $this->getWeekDateRange($year, $month, $week);
                $weekStart = $range['start'];
                $weekEnd   = $range['end'];
                
                // Log week information for debugging
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                    "Week $week date range: $weekStart to $weekEnd " . 
                    "(intersects_month: " . ($range['intersects_month'] ? 'Yes' : 'No') . ")");
                
                // Skip weeks that don't intersect with the month to avoid processing irrelevant data
                if (!$range['intersects_month']) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                        "Skipping week $week as it doesn't intersect with month $month");
                    continue;
                }

                // Compute weekly scores
                $gmiWeekly = $this->calculateWeeklyScores($year, $month, $week, $weekStart, $weekEnd);

                // Persist weekly data
                $this->storeWeeklyData($gmiWeekly, $year, $month, $week);

                // Accumulate into monthly totals
                foreach ($gmiWeekly as $userId => $weeklyData) {
                    if (!isset($gmiMonthlyAggregated[$userId])) {
                        // Initialize monthly record (preserve existing row if present)
                        $existing   = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                            'user_id'   => (string)$userId,
                            'gmi_year'  => (string)$year,
                            'gmi_month' => (string)$month
                        ]);

                        $existingId = !empty($existing) ? $existing['gmi_id'] : 0;
                        $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr(
                            $userId,
                            $year,
                            $month,
                            $weeklyData['siteId'],
                            $existingId
                        );
                    }

                    // Sum each weekly metric into the monthly totals
                    $sumFields = [
                        'gmiPpmTotal', 'gmiPpmCompleted', 'gmiPpmOnTime', 'gmiPpmLate', 'gmiPpmWithin', 'gmiPpmAssist',
                        'gmiWoTotal', 'gmiWoCompleted', 'gmiWoOnTime', 'gmiWoLate', 'gmiWoSelfFinding', 'gmiWoAssist',
                        'gmiPointCompleted', 'gmiPointOnTime', 'gmiPointLate', 'gmiPointSelfFinding',
                        'gmiPointLessProductive', 'gmiPointBeforeMinus', 'gmiPointAfterMinus', 'gmiPointTotal',
                        'gmiMbv'  // Add MBV to aggregation
                    ];

                    foreach ($sumFields as $field) {
                        $gmiMonthlyAggregated[$userId][$field] += $weeklyData[str_replace('gmi', 'gmw', $field)];
                    }
                    
                    // Store tier points from the latest weekly data (trade ratios)
                    if (isset($weeklyData['gmwPpmTierPoint'])) {
                        $gmiMonthlyAggregated[$userId]['gmiPpmTierPoint'] = $weeklyData['gmwPpmTierPoint'];
                    }
                    if (isset($weeklyData['gmwWoTierPoint'])) {
                        $gmiMonthlyAggregated[$userId]['gmiWoTierPoint'] = $weeklyData['gmwWoTierPoint'];
                    }
                }
            }

            // --- Apply monthly tiers and performance based on aggregated data ---
            foreach ($gmiMonthlyAggregated as &$gmi) {
                // Monthly PPM tier assignment
                if ($gmi['gmiPpmCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmi['gmiPpmTierName']  = 'Medalist';
                    $gmi['gmiPpmTierPoint'] = 1;
                } elseif ($gmi['gmiPpmCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmi['gmiPpmTierName']  = 'Finisher';
                    $gmi['gmiPpmTierPoint'] = 1;
                }

                // Monthly WO tier assignment
                if ($gmi['gmiWoCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmi['gmiWoTierName']  = 'Medalist';
                    $gmi['gmiWoTierPoint'] = 1;
                } elseif ($gmi['gmiWoCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmi['gmiWoTierName']  = 'Finisher';
                    $gmi['gmiWoTierPoint'] = 1;
                }

                // Monthly productivity calculation
                $allWithin = $gmi['gmiPpmWithin'] + $gmi['gmiWoOnTime'];
                $allTotal  = $gmi['gmiPpmTotal']  + $gmi['gmiWoTotal'];
                if ($allTotal > 0) {
                    $gmi['gmiProductivityLevel']     = ($allWithin / $allTotal) * $this->getConfig('productivity_base', 90);
                    $gmi['gmiProductivityDeduction'] = $this->getConfig('productivity_base', 90) - $gmi['gmiProductivityLevel'];
                } else {
                    $gmi['gmiProductivityLevel']     = 0;
                    $gmi['gmiProductivityDeduction'] = $this->getConfig('productivity_base', 90);
                }
            }
            unset($gmi);

            // --- Persist monthly aggregated totals only ---
            foreach ($gmiMonthlyAggregated as $gmi) {
                $gmiId = $gmi['gmiId'];
                unset($gmi['gmiId']);

                if (empty($gmiId)) {
                    // Use INSERT IGNORE or ON DUPLICATE KEY UPDATE to prevent duplicates
                    // First check if record already exists (double-check for race conditions)
                    $existingCheck = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                        'user_id'   => (string)$gmi['userId'],
                        'gmi_year'  => (string)$gmi['gmiYear'], 
                        'gmi_month' => (string)$gmi['gmiMonth']
                    ]);
                    
                    if (empty($existingCheck)) {
                        Class_db::getInstance()
                            ->db_insert('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi));
                    } else {
                        // Update the existing record instead of inserting
                        Class_db::getInstance()
                            ->db_update(
                                'gmi_monthly',
                                $this->fn_general->convertToMysqlArrAll($gmi),
                                [
                                    'user_id'   => (string)$gmi['userId'],
                                    'gmi_year'  => (string)$gmi['gmiYear'],
                                    'gmi_month' => (string)$gmi['gmiMonth']
                                ]
                            );
                    }
                } else {
                    Class_db::getInstance()
                        ->db_update(
                            'gmi_monthly',
                            $this->fn_general->convertToMysqlArrAll($gmi),
                            ['gmi_id' => $gmiId]
                        );
                }
            }

        } catch (Exception $ex) {
            // Log and rethrow
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception(
                $this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()),
                $ex->getCode()
            );
        }
    }

    /**
     * Run monthly gamification using direct monthly processing (alternative method)
     * This method collects all data for the month at once, avoiding week boundary issues
     * @param int $year
     * @param int $month
     * @throws Exception
     */
    private function runMonthlyWithDirectProcessing($year, $month)
    {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Using direct monthly processing');
            
            // Simple monthly date range
            $monthStart = "$year-" . sprintf('%02d', $month) . "-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Processing data for month range: $monthStart to $monthEnd");
            
            $gmiMonthlyAggregated = [];
            
            // --- Collect all PPM data for the month ---
            $gmiPpm = Class_db::getInstance()->db_select2('vw_gamification_ppm_daily', array(), '', '', 0, 
                array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                'Found ' . count($gmiPpm) . ' PPM records for the month');
            
            foreach ($gmiPpm as $ppm) {
                $userId = intval($ppm['ppmTaskAssignedTo']);
                $ppmGroupId = intval($ppm['ppmGroupId'] ?? 0);
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
                    // Initialize monthly record (preserve existing row if present)
                    $existing = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                        'user_id'   => (string)$userId,
                        'gmi_year'  => (string)$year,
                        'gmi_month' => (string)$month
                    ]);
                    
                    $existingId = !empty($existing) ? $existing['gmi_id'] : 0;
                    $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr(
                        $userId, $year, $month, $ppm['siteId'], $existingId
                    );
                }
                
                // Apply trade ratio to task completion counts
                $gmiMonthlyAggregated[$userId]['gmiPpmTotal'] += round(intval($ppm['ppmTotal']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmCompleted'] += round(intval($ppm['ppmCompleted']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmOnTime'] += round(intval($ppm['ppmOnTime']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmLate'] += round(intval($ppm['ppmLate']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmWithin'] += round(intval($ppm['ppmWithin']) * $tradeRatio);
            }
            
            // --- Collect all PPM Assist data for the month ---
            $gmiPpmAssist = Class_db::getInstance()->db_select2('vw_gamification_ppm_assist_daily', array(), '', '', 0, 
                array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                'Found ' . count($gmiPpmAssist) . ' PPM Assist records for the month');
            
            foreach ($gmiPpmAssist as $ppmAssist) {
                $userId = intval($ppmAssist['userId']);
                $ppmGroupId = intval($ppmAssist['ppmGroupId'] ?? 0);
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
                    $existing = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                        'user_id'   => (string)$userId,
                        'gmi_year'  => (string)$year,
                        'gmi_month' => (string)$month
                    ]);
                    
                    $existingId = !empty($existing) ? $existing['gmi_id'] : 0;
                    $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr(
                        $userId, $year, $month, $ppmAssist['siteId'], $existingId
                    );
                }
                
                $gmiMonthlyAggregated[$userId]['gmiPpmAssist'] += round(intval($ppmAssist['ppmTotal']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmTotal'] += round(intval($ppmAssist['ppmTotal']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmCompleted'] += round(intval($ppmAssist['ppmCompleted']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmOnTime'] += round(intval($ppmAssist['ppmOnTime']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmLate'] += round(intval($ppmAssist['ppmLate']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiPpmWithin'] += round(intval($ppmAssist['ppmWithin']) * $tradeRatio);
            }
            
            // --- Collect all WO data for the month ---
            $gmiWo = Class_db::getInstance()->db_select2('vw_gamification_wo_daily', array(), '', '', 0, 
                array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                'Found ' . count($gmiWo) . ' WO records for the month');
            
            foreach ($gmiWo as $wo) {
                $userId = intval($wo['woTaskAssignedTo']);
                $ppmGroupId = intval($wo['ppmGroupId'] ?? 0);
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
                    $existing = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                        'user_id'   => (string)$userId,
                        'gmi_year'  => (string)$year,
                        'gmi_month' => (string)$month
                    ]);
                    
                    $existingId = !empty($existing) ? $existing['gmi_id'] : 0;
                    $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr(
                        $userId, $year, $month, $wo['siteId'], $existingId
                    );
                }
                
                $gmiMonthlyAggregated[$userId]['gmiWoTotal'] += round(intval($wo['woTotal']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoCompleted'] += round(intval($wo['woCompleted']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoOnTime'] += round(intval($wo['woOnTime']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoLate'] += round(intval($wo['woLate']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoSelfFinding'] += round(intval($wo['woSelfFinding']) * $tradeRatio);
            }
            
            // --- Collect all WO Assist data for the month ---
            $gmiWoAssist = Class_db::getInstance()->db_select2('vw_gamification_wo_assist_daily', array(), '', '', 0, 
                array('dateStart' => $monthStart, 'dateEnd' => $monthEnd));
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                'Found ' . count($gmiWoAssist) . ' WO Assist records for the month');
            
            foreach ($gmiWoAssist as $woAssist) {
                $userId = intval($woAssist['userId']);
                $ppmGroupId = intval($woAssist['ppmGroupId'] ?? 0);
                $tradeRatio = $this->getTradeRatio($ppmGroupId);
                
                if (!array_key_exists($userId, $gmiMonthlyAggregated)) {
                    $existing = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                        'user_id'   => (string)$userId,
                        'gmi_year'  => (string)$year,
                        'gmi_month' => (string)$month
                    ]);
                    
                    $existingId = !empty($existing) ? $existing['gmi_id'] : 0;
                    $gmiMonthlyAggregated[$userId] = $this->setInitialGmiMonthArr(
                        $userId, $year, $month, $woAssist['siteId'], $existingId
                    );
                }
                
                $gmiMonthlyAggregated[$userId]['gmiWoAssist'] += round(intval($woAssist['woTotal']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoTotal'] += round(intval($woAssist['woTotal']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoCompleted'] += round(intval($woAssist['woCompleted']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoOnTime'] += round(intval($woAssist['woOnTime']) * $tradeRatio);
                $gmiMonthlyAggregated[$userId]['gmiWoLate'] += round(intval($woAssist['woLate']) * $tradeRatio);
            }
            
            // --- Calculate points and apply monthly tiers ---
            foreach ($gmiMonthlyAggregated as &$gmi) {
                // Calculate monthly totals
                $allTotal = $gmi['gmiPpmTotal'] + $gmi['gmiWoTotal'];
                $allCompleted = $gmi['gmiPpmCompleted'] + $gmi['gmiWoCompleted'];
                $allOnTime = $gmi['gmiPpmOnTime'] + ($this->getConfig('wo_ontime_multiplier', 2) * $gmi['gmiWoOnTime']) + $gmi['gmiPpmWithin'];
                $allWithin = $gmi['gmiWoOnTime'] + $gmi['gmiPpmWithin'];
                $allLate = $gmi['gmiPpmLate'] + $gmi['gmiWoLate'];
                $mbv = $allOnTime - $allLate;
                
                // Determine tier multiplier based on monthly MBV
                if ($mbv <= $this->getConfig('mbv_tier1_threshold', 50)) {
                    $tierDivider = $this->getConfig('mbv_tier1_multiplier', 1);
                } else if ($mbv <= $this->getConfig('mbv_tier2_threshold', 100)) {
                    $tierDivider = $this->getConfig('mbv_tier2_multiplier', 3);
                } else {
                    $tierDivider = $this->getConfig('mbv_tier3_multiplier', 5);
                }
                
                // Calculate monthly points
                if ($allTotal > 0) {
                    $gmi['gmiPointCompleted'] = ($allCompleted / $allTotal) * $this->getConfig('weight_completed', 0.3) * $this->getConfig('point_scale_factor', 10000);
                    $gmi['gmiPointOnTime'] = (($allWithin / $allTotal) * $tierDivider) * $this->getConfig('weight_ontime', 0.7) * $this->getConfig('point_scale_factor', 10000);
                    
                    // Fix division by zero: Only calculate late penalty if there are completed tasks
                    if ($allCompleted > 0) {
                        $gmi['gmiPointLate'] = -(($allLate / $allCompleted) * $tierDivider) * $this->getConfig('weight_late_penalty', 0.15) * $this->getConfig('point_scale_factor', 10000);
                    } else {
                        $gmi['gmiPointLate'] = 0;
                    }
                    
                    // Productivity calculations
                    $gmi['gmiProductivityLevel'] = ($allWithin / $allTotal) * $this->getConfig('productivity_base', 90);
                    $gmi['gmiProductivityDeduction'] = $this->getConfig('productivity_base', 90) - $gmi['gmiProductivityLevel'];
                    // Step 3: Less Productive Point = (Within Time/PPM&WO Total) x Tier Point x Prod. Deduction perc. x 10,000
                    // Productivity deduction should be treated as percentage (divide by 100)
                    $gmi['gmiPointLessProductive'] = ($allWithin / $allTotal) * $tierDivider * ($gmi['gmiProductivityDeduction'] / 100) * $this->getConfig('point_scale_factor', 10000);
                } else {
                    $gmi['gmiPointCompleted'] = 0;
                    $gmi['gmiPointOnTime'] = 0;
                    $gmi['gmiPointLate'] = 0;
                    $gmi['gmiProductivityLevel'] = 0;
                    $gmi['gmiProductivityDeduction'] = $this->getConfig('productivity_base', 90);
                    $gmi['gmiPointLessProductive'] = 0;
                }
                
                $gmi['gmiPointSelfFinding'] = intval($gmi['gmiWoSelfFinding']) * $this->getConfig('self_finding_points', 5);
                $gmi['gmiPointBeforeMinus'] = $gmi['gmiPointCompleted'] + $gmi['gmiPointLate'] + $gmi['gmiPointSelfFinding'] + $gmi['gmiPointOnTime'];
                $gmi['gmiPointAfterMinus'] = $gmi['gmiPointBeforeMinus'] - $gmi['gmiPointLessProductive'];
                $gmi['gmiPointTotal'] = $gmi['gmiPointBeforeMinus'];
                $gmi['gmiMbv'] = $mbv;
                $gmi['gmiTierPoint'] = $tierDivider;
                
                // Monthly PPM tier assignment
                if ($gmi['gmiPpmCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmi['gmiPpmTierName'] = 'Medalist';
                    $gmi['gmiPpmTierPoint'] = 1;
                } elseif ($gmi['gmiPpmCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmi['gmiPpmTierName'] = 'Finisher';
                    $gmi['gmiPpmTierPoint'] = 1;
                }
                
                // Monthly WO tier assignment
                if ($gmi['gmiWoCompleted'] > $this->getConfig('tier_medalist_threshold', 150)) {
                    $gmi['gmiWoTierName'] = 'Medalist';
                    $gmi['gmiWoTierPoint'] = 1;
                } elseif ($gmi['gmiWoCompleted'] > $this->getConfig('tier_finisher_threshold', 80)) {
                    $gmi['gmiWoTierName'] = 'Finisher';
                    $gmi['gmiWoTierPoint'] = 1;
                }
            }
            unset($gmi);
            
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                'Processed ' . count($gmiMonthlyAggregated) . ' users for monthly gamification');
            
            // --- Persist monthly aggregated totals ---
            foreach ($gmiMonthlyAggregated as $gmi) {
                $gmiId = $gmi['gmiId'];
                unset($gmi['gmiId']);
                
                if (empty($gmiId)) {
                    // First check if record already exists (double-check for race conditions)
                    $existingCheck = Class_db::getInstance()->db_select_single2('gmi_monthly', [
                        'user_id'   => (string)$gmi['userId'],
                        'gmi_year'  => (string)$gmi['gmiYear'],
                        'gmi_month' => (string)$gmi['gmiMonth']
                    ]);
                    
                    if (empty($existingCheck)) {
                        Class_db::getInstance()->db_insert('gmi_monthly', $this->fn_general->convertToMysqlArrAll($gmi));
                    } else {
                        // Update the existing record instead of inserting
                        Class_db::getInstance()->db_update(
                            'gmi_monthly',
                            $this->fn_general->convertToMysqlArrAll($gmi),
                            [
                                'user_id'   => (string)$gmi['userId'],
                                'gmi_year'  => (string)$gmi['gmiYear'],
                                'gmi_month' => (string)$gmi['gmiMonth']
                            ]
                        );
                    }
                } else {
                    Class_db::getInstance()->db_update(
                        'gmi_monthly',
                        $this->fn_general->convertToMysqlArrAll($gmi),
                        ['gmi_id' => $gmiId]
                    );
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
        
        // ❌ REMOVED: Don't artificially constrain weeks to month boundaries
        // This was causing data loss for tasks that span across month boundaries
        // if ($weekStart < $firstDay) {
        //     $weekStart = $firstDay;
        // }
        // if ($weekEnd > $lastDay) {
        //     $weekEnd = $lastDay;
        // }
        
        // ✅ IMPROVED: Only adjust if the week is completely outside the month
        // If week overlaps with month, include the full week to capture all related data
        $weekStartsBeforeMonth = $weekStart < $firstDay;
        $weekEndsAfterMonth = $weekEnd > $lastDay;
        $weekIntersectsMonth = !($weekEnd < $firstDay || $weekStart > $lastDay);
        
        // Log week boundary information for debugging
        if (isset($this->fn_general)) {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                "Week $week: {$weekStart->format('Y-m-d')} to {$weekEnd->format('Y-m-d')} " .
                "(Month: {$firstDay->format('Y-m-d')} to {$lastDay->format('Y-m-d')}) " .
                "StartsBefore: " . ($weekStartsBeforeMonth ? 'Yes' : 'No') . ", " .
                "EndsAfter: " . ($weekEndsAfterMonth ? 'Yes' : 'No') . ", " .
                "Intersects: " . ($weekIntersectsMonth ? 'Yes' : 'No'));
        }
        
        return array(
            'start' => $weekStart->format('Y-m-d'),
            'end' => $weekEnd->format('Y-m-d'),
            'intersects_month' => $weekIntersectsMonth,
            'starts_before_month' => $weekStartsBeforeMonth,
            'ends_after_month' => $weekEndsAfterMonth
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
                
                // Store original counts (for display/reporting)
                $gmiWeekly[$userId]['gmwPpmTotal'] += intval($ppm['ppmTotal']);
                $gmiWeekly[$userId]['gmwPpmCompleted'] += intval($ppm['ppmCompleted']);
                $gmiWeekly[$userId]['gmwPpmOnTime'] += intval($ppm['ppmOnTime']);
                $gmiWeekly[$userId]['gmwPpmLate'] += intval($ppm['ppmLate']);
                $gmiWeekly[$userId]['gmwPpmWithin'] += intval($ppm['ppmWithin']);
                
                // Store trade ratio adjusted counts for scoring calculations
                $gmiWeekly[$userId]['gmwPpmTotalScore'] += round(intval($ppm['ppmTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmCompletedScore'] += round(intval($ppm['ppmCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmOnTimeScore'] += round(intval($ppm['ppmOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmLateScore'] += round(intval($ppm['ppmLate']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmWithinScore'] += round(intval($ppm['ppmWithin']) * $tradeRatio);
                
                // Set standard tier point (reverted from trade ratio)
                $gmiWeekly[$userId]['gmwPpmTierPoint'] = 1;
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
                
                // Store original counts (for display/reporting)
                $gmiWeekly[$userId]['gmwPpmAssist'] += intval($ppmAssist['ppmTotal']);
                $gmiWeekly[$userId]['gmwPpmTotal'] += intval($ppmAssist['ppmTotal']);
                $gmiWeekly[$userId]['gmwPpmCompleted'] += intval($ppmAssist['ppmCompleted']);
                $gmiWeekly[$userId]['gmwPpmOnTime'] += intval($ppmAssist['ppmOnTime']);
                $gmiWeekly[$userId]['gmwPpmLate'] += intval($ppmAssist['ppmLate']);
                $gmiWeekly[$userId]['gmwPpmWithin'] += intval($ppmAssist['ppmWithin']);
                
                // Store trade ratio adjusted counts for scoring calculations
                $gmiWeekly[$userId]['gmwPpmAssistScore'] += round(intval($ppmAssist['ppmTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmTotalScore'] += round(intval($ppmAssist['ppmTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmCompletedScore'] += round(intval($ppmAssist['ppmCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmOnTimeScore'] += round(intval($ppmAssist['ppmOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmLateScore'] += round(intval($ppmAssist['ppmLate']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwPpmWithinScore'] += round(intval($ppmAssist['ppmWithin']) * $tradeRatio);
                
                // Set standard tier point (reverted from trade ratio)
                $gmiWeekly[$userId]['gmwPpmTierPoint'] = 1;
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
                
                // Store original counts (for display/reporting)
                $gmiWeekly[$userId]['gmwWoTotal'] += intval($wo['woTotal']);
                $gmiWeekly[$userId]['gmwWoCompleted'] += intval($wo['woCompleted']);
                $gmiWeekly[$userId]['gmwWoOnTime'] += intval($wo['woOnTime']);
                $gmiWeekly[$userId]['gmwWoLate'] += intval($wo['woLate']);
                $gmiWeekly[$userId]['gmwWoSelfFinding'] += intval($wo['woSelfFinding']);
                
                // Store trade ratio adjusted counts for scoring calculations
                $gmiWeekly[$userId]['gmwWoTotalScore'] += round(intval($wo['woTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoCompletedScore'] += round(intval($wo['woCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoOnTimeScore'] += round(intval($wo['woOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoLateScore'] += round(intval($wo['woLate']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoSelfFindingScore'] += round(intval($wo['woSelfFinding']) * $tradeRatio);
                
                // Set standard tier point (reverted from trade ratio)
                $gmiWeekly[$userId]['gmwWoTierPoint'] = 1;
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
                
                // Store original counts (for display/reporting)
                $gmiWeekly[$userId]['gmwWoAssist'] += intval($woAssist['woTotal']);
                $gmiWeekly[$userId]['gmwWoTotal'] += intval($woAssist['woTotal']);
                $gmiWeekly[$userId]['gmwWoCompleted'] += intval($woAssist['woCompleted']);
                $gmiWeekly[$userId]['gmwWoOnTime'] += intval($woAssist['woOnTime']);
                $gmiWeekly[$userId]['gmwWoLate'] += intval($woAssist['woLate']);
                
                // Store trade ratio adjusted counts for scoring calculations
                $gmiWeekly[$userId]['gmwWoAssistScore'] += round(intval($woAssist['woTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoTotalScore'] += round(intval($woAssist['woTotal']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoCompletedScore'] += round(intval($woAssist['woCompleted']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoOnTimeScore'] += round(intval($woAssist['woOnTime']) * $tradeRatio);
                $gmiWeekly[$userId]['gmwWoLateScore'] += round(intval($woAssist['woLate']) * $tradeRatio);
                
                // Set standard tier point (reverted from trade ratio)
                $gmiWeekly[$userId]['gmwWoTierPoint'] = 1;
            }

            // Calculate points for each user for this week
            foreach ($gmiWeekly as $userId => $gmi) {
                // Calculate weekly totals for display (original counts)
                $allTotal = $gmi['gmwPpmTotal'] + $gmi['gmwWoTotal'];
                $allCompleted = $gmi['gmwPpmCompleted'] + $gmi['gmwWoCompleted'];
                $allWithin = $gmi['gmwWoOnTime'] + $gmi['gmwPpmWithin'];
                $allLate = $gmi['gmwPpmLate'] + $gmi['gmwWoLate'];
                
                // Calculate scoring totals (trade ratio adjusted counts)
                $allTotalScore = ($gmi['gmwPpmTotalScore'] ?? 0) + ($gmi['gmwWoTotalScore'] ?? 0);
                $allCompletedScore = ($gmi['gmwPpmCompletedScore'] ?? 0) + ($gmi['gmwWoCompletedScore'] ?? 0);
                $allOnTimeScore = ($gmi['gmwPpmOnTimeScore'] ?? 0) + ($this->getConfig('wo_ontime_multiplier', 2) * ($gmi['gmwWoOnTimeScore'] ?? 0)) + ($gmi['gmwPpmWithinScore'] ?? 0);
                $allWithinScore = ($gmi['gmwWoOnTimeScore'] ?? 0) + ($gmi['gmwPpmWithinScore'] ?? 0);
                $allLateScore = ($gmi['gmwPpmLateScore'] ?? 0) + ($gmi['gmwWoLateScore'] ?? 0);
                $mbvScore = $allOnTimeScore - $allLateScore;
                
                // Store original MBV for display purposes (using original counts)
                $mbv = ($gmi['gmwPpmOnTime'] + ($this->getConfig('wo_ontime_multiplier', 2)*$gmi['gmwWoOnTime']) + $gmi['gmwPpmWithin']) - $allLate;
                
                // Determine tier multiplier based on weekly MBV (using scored values for consistency)
                if ($mbvScore <= $this->getConfig('mbv_tier1_threshold', 50)) {
                    $tierDivider = $this->getConfig('mbv_tier1_multiplier', 1);
                } else if ($mbvScore <= $this->getConfig('mbv_tier2_threshold', 100)) {
                    $tierDivider = $this->getConfig('mbv_tier2_multiplier', 3);
                } else {
                    $tierDivider = $this->getConfig('mbv_tier3_multiplier', 5);
                }

                // Calculate weekly points using scored values
                if ($allTotalScore > 0) {
                    $gmiWeekly[$userId]['gmwPointCompleted'] = ($allCompletedScore/$allTotalScore) * $this->getConfig('weight_completed', 0.3) * $this->getConfig('point_scale_factor', 10000);
                    $gmiWeekly[$userId]['gmwPointOnTime'] = (($allWithinScore/$allTotalScore) * $tierDivider) * $this->getConfig('weight_ontime', 0.7) * $this->getConfig('point_scale_factor', 10000);
                    
                    // Fix division by zero: Only calculate late penalty if there are completed tasks
                    if ($allCompletedScore > 0) {
                        $gmiWeekly[$userId]['gmwPointLate'] = -(($allLateScore/$allCompletedScore) * $tierDivider) * $this->getConfig('weight_late_penalty', 0.15) * $this->getConfig('point_scale_factor', 10000);
                    } else {
                        // Log when this edge case occurs for debugging
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 
                            "User $userId has tasks assigned but none completed (allTotal=$allTotal, allCompleted=$allCompleted). Setting late penalty to 0.");
                        $gmiWeekly[$userId]['gmwPointLate'] = 0;
                    }
                    
                    // Productivity calculations (use original counts for display, scored values for calculations)
                    $gmiWeekly[$userId]['gmwProductivityLevel'] = ($allWithinScore / $allTotalScore) * $this->getConfig('productivity_base', 90);
                    $gmiWeekly[$userId]['gmwProductivityDeduction'] = $this->getConfig('productivity_base', 90) - $gmiWeekly[$userId]['gmwProductivityLevel'];
                    // Step 3: Less Productive Point = (Within Time/PPM&WO Total) x Tier Point x Prod. Deduction perc. x 10,000
                    // Productivity deduction should be treated as percentage (divide by 100)
                    $gmiWeekly[$userId]['gmwPointLessProductive'] = ($allWithinScore/$allTotalScore) * $tierDivider * ($gmiWeekly[$userId]['gmwProductivityDeduction'] / 100) * $this->getConfig('point_scale_factor', 10000);
                } else {
                    $gmiWeekly[$userId]['gmwPointCompleted'] = 0;
                    $gmiWeekly[$userId]['gmwPointOnTime'] = 0;
                    $gmiWeekly[$userId]['gmwPointLate'] = 0;
                    $gmiWeekly[$userId]['gmwProductivityLevel'] = 0;
                    $gmiWeekly[$userId]['gmwProductivityDeduction'] = $this->getConfig('productivity_base', 90);
                    $gmiWeekly[$userId]['gmwPointLessProductive'] = 0;
                }
                
                // Use scored values for self-finding calculation as well
                $gmiWeekly[$userId]['gmwPointSelfFinding'] = intval(($gmi['gmwWoSelfFindingScore'] ?? $gmi['gmwWoSelfFinding'])) * $this->getConfig('self_finding_points', 5);
                $gmiWeekly[$userId]['gmwPointRework'] = 0; // Default for rework points (you can implement logic if needed)
                $gmiWeekly[$userId]['gmwPointBeforeMinus'] = $gmiWeekly[$userId]['gmwPointCompleted'] + $gmiWeekly[$userId]['gmwPointSelfFinding'] + $gmiWeekly[$userId]['gmwPointOnTime'] + $gmiWeekly[$userId]['gmwPointRework'];
                $gmiWeekly[$userId]['gmwPointAfterMinus'] = $gmiWeekly[$userId]['gmwPointBeforeMinus'] - ($gmiWeekly[$userId]['gmwPointLessProductive'] - $gmiWeekly[$userId]['gmwPointLate']);
                $gmiWeekly[$userId]['gmwPointTotal'] = $gmiWeekly[$userId]['gmwPointBeforeMinus'];
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
        $returnArr['gmwPpmTierPoint'] = 1;
        $returnArr['gmwPpmTotal'] = 0;
        $returnArr['gmwPpmCompleted'] = 0;
        $returnArr['gmwPpmOnTime'] = 0;
        $returnArr['gmwPpmLate'] = 0;
        $returnArr['gmwPpmWithin'] = 0;
        $returnArr['gmwPpmAssist'] = 0;
        // Score fields for PPM (trade ratio adjusted)
        $returnArr['gmwPpmTotalScore'] = 0;
        $returnArr['gmwPpmCompletedScore'] = 0;
        $returnArr['gmwPpmOnTimeScore'] = 0;
        $returnArr['gmwPpmLateScore'] = 0;
        $returnArr['gmwPpmWithinScore'] = 0;
        $returnArr['gmwPpmAssistScore'] = 0;
        $returnArr['gmwWoTierName'] = 'Under Rated';
        $returnArr['gmwWoTierPoint'] = 1;
        $returnArr['gmwWoTotal'] = 0;
        $returnArr['gmwWoCompleted'] = 0;
        $returnArr['gmwWoOnTime'] = 0;
        $returnArr['gmwWoLate'] = 0;
        $returnArr['gmwWoRework'] = 0; // Existing field in your table
        $returnArr['gmwWoSelfFinding'] = 0;
        $returnArr['gmwWoAssist'] = 0;
        // Score fields for WO (trade ratio adjusted)
        $returnArr['gmwWoTotalScore'] = 0;
        $returnArr['gmwWoCompletedScore'] = 0;
        $returnArr['gmwWoOnTimeScore'] = 0;
        $returnArr['gmwWoLateScore'] = 0;
        $returnArr['gmwWoSelfFindingScore'] = 0;
        $returnArr['gmwWoAssistScore'] = 0;
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