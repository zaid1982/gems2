<?php

class Class_ppm {

    private $constant;
    private $fn_general;
    private $fn_task;
    private $fn_email;

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
     * @param $ppmTaskId
     * @param string $isErrorEmpty
     * @return array
     * @throws Exception
     */
    public function getPpmTask ($ppmTaskId, $isErrorEmpty=0) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmTaskId));
            return Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id'=>$ppmTaskId), '', $isErrorEmpty);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    private function get_q_task_result ($resultInput) {
        if ($resultInput == '0') {
            return 'Fail';
        } else if ($resultInput == '1') {
            return 'Pass';
        } else if ($resultInput == '2') {
            return 'N/A';
        }
        return '';
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @return array
     * @throws Exception
     */
    private function get_dates_day ($startDate, $endDate, $applyDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+1 day' );

            $apply = new DateTime( $applyDate );
            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                if ($date > $apply) {
                    array_push($newDates, $date->format("Y-m-d"));
                }
            }
            return $newDates;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @return array
     * @throws Exception
     */
    private function get_dates_week ($startDate, $endDate, $applyDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+1 week' );
            $begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+1 day' );

            $apply = new DateTime( $applyDate );
            $interval = new DateInterval('P1W');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                if ($date->format("D") == 'Mon') {
                    $date->modify( '+6 day' );
                } else if ($date->format("D") == 'Tue') {
                    $date->modify( '+5 day' );
                } else if ($date->format("D") == 'Wed') {
                    $date->modify( '+4 day' );
                } else if ($date->format("D") == 'Thu') {
                    $date->modify( '+3 day' );
                } else if ($date->format("D") == 'Fri') {
                    $date->modify( '+2 day' );
                } else if ($date->format("D") == 'Sat') {
                    $date->modify( '+1 day' );
                }
                if ($date > $apply && $date < $end) {
                    array_push($newDates, $date->format("Y-m-d"));
                }
            }
            return $newDates;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @return array
     * @throws Exception
     */
    private function get_dates_month ($startDate, $endDate, $applyDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+1 month' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );

            $apply = new DateTime( $applyDate );
            $interval = new DateInterval('P1M');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                if ($xx > $apply) {
                    array_push($newDates, $xx->format("Y-m-d"));
                }
            }
            return $newDates;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @return array
     * @throws Exception
     */
    private function get_dates_quarter ($startDate, $endDate, $applyDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+3 month' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );

            $apply = new DateTime( $applyDate );
            $interval = new DateInterval('P3M');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                if ($xx > $apply) {
                    array_push($newDates, $xx->format("Y-m-d"));
                }
            }
            return $newDates;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @return array
     * @throws Exception
     */
    private function get_dates_halfAnnual ($startDate, $endDate, $applyDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+6 month' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );

            $apply = new DateTime( $applyDate );
            $interval = new DateInterval('P6M');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                if ($xx > $apply) {
                    array_push($newDates, $xx->format("Y-m-d"));
                }
            }
            return $newDates;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $applyDate
     * @return array
     * @throws Exception
     */
    public function get_dates_year ($startDate, $endDate, $applyDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+1 year' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );

            $apply = new DateTime( $applyDate );
            $interval = new DateInterval('P1Y');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                if ($xx > $apply) {
                    array_push($newDates, $xx->format("Y-m-d"));
                }
            }
            return $newDates;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $contractId
     * @return array
     * @throws Exception
     */
    public function get_ppm_from_asset_list ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_ppm_asset', array('ast_asset.contract_id'=>$contractId, 'asset_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
                $row_result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
                $row_result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
                $row_result['locationCodeId'] = $this->fn_general->clear_null($dataLocal['location_code_id']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['locationCodeName'] = $this->fn_general->clear_null($dataLocal['location_code_name']);
                $row_result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
                $row_result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
                $row_result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
                $row_result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
                $row_result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
                $row_result['assetTimeCreated'] = str_replace('-', '/', $dataLocal['asset_time_created']);
                $row_result['assetStatus'] = $dataLocal['asset_status'];
                $row_result['ppmId'] = $this->fn_general->clear_null($dataLocal['ppm_id']);
                $row_result['ppmTaskNo'] = $this->fn_general->clear_null($dataLocal['ppm_task_no']);
                $row_result['ppmGroupIdPpm'] = $this->fn_general->clear_null($dataLocal['ppm_group_id_ppm']);
                $row_result['ppmDateStart'] = $this->fn_general->clear_null($dataLocal['ppm_date_start']);
                $row_result['checklistId'] = $this->fn_general->clear_null($dataLocal['checklist_id']);
                $row_result['ppmCreatedBy'] = $this->fn_general->clear_null($dataLocal['ppm_created_by']);
                $row_result['ppmTimeCreated'] = $this->fn_general->clear_null($dataLocal['ppm_time_created']);
                $row_result['ppmStatus'] = $this->fn_general->clear_null($dataLocal['ppm_status']);
                $row_result['assignedStatus'] = empty($dataLocal['ppm_id']) ? '11' : '10';
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmId
     * @return array
     * @throws Exception
     */
    public function get_ppm_scheduled_list ($ppmId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_ppm_scheduled', array('ppm_id'=>$ppmId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskNo'] = $dataLocal['ppm_task_no'];
                //$row_result['ppmTaskScheduleDate'] = str_replace('-', '/', $dataLocal['ppm_task_schedule_date']);
                $row_result['ppmTaskScheduleDate'] = str_replace('-', '/', $dataLocal['ppm_task_start_date']);
                $row_result['ppmTaskAssignedTo'] = $dataLocal['ppm_task_assigned_to'];
                $row_result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
                $row_result['frequency'] = $this->fn_general->clear_null($dataLocal['frequency']);
                $row_result['ppmTaskStatus'] = $this->fn_general->clear_null($dataLocal['ppm_task_status']);
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

        /**
     * @param $assetId
     * @param $checklistId
     * @param $ppmDateStart
     * @param $userId
     * @param string $ppmGroupId
     * @return array
     * @throws Exception
     */
    public function assign_ppm_single ($assetId, $checklistId, $ppmDateStart, $userId, $ppmGroupId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);
            $constant = $this->constant;
            date_default_timezone_set("Asia/Kuala_Lumpur");

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (empty($ppmDateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmDateStart empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            // Removed ppmGroupId empty check as it might not be relevant for new grouping.
            // if (empty($ppmGroupId)) {
            //     throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            // }
            if (Class_db::getInstance()->db_count('ppm', array('asset_id'=>$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_SIMILAR_ASSET, 31);
            }

            $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $contractId = $asset['contract_id'];
            $contract = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id'=>$contractId), null, 1);
            $contractDateStart = $contract['contract_date_start'];
            $contractDateEnd = $contract['contract_date_end'];
            $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);
            if ($asset['asset_type_id'] != $checklist['asset_type_id']) {
                throw new Exception('[' . __LINE__ . '] - Checklist asset_type_id not sync with asset');
            }

            // --- Determine ppm_set_id for the assigned asset ---
            $ppmSetId = null;
            $assetSet = Class_db::getInstance()->db_select_single('ppm_set_asset', array('asset_id' => $assetId));
            if (!empty($assetSet)) {
                $tempPpmSetId = $assetSet['ppm_set_id'];

                if (Class_db::getInstance()->db_count('ppm_set', array('ppm_set_id' => $tempPpmSetId)) > 0) {
                    $ppmSetId = $tempPpmSetId;
                } else {
                    $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Orphaned ppm_set_asset entry found for asset_id: ' . $assetId . '. ppm_set_id ' . $tempPpmSetId . ' does not exist in ppm_set. Setting ppm_set_id to NULL for this assignment.');
                    $ppmSetId = null; // Set to NULL if the referenced ppm_set does not exist
                }
            }

            $isYearly = false;
            $isHalfAnnually = false;
            $isQuarterly = false;
            $isMonthly = false;
            $isWeekly = false;
            $isDaily = false;

            $checklistQuals = Class_db::getInstance()->db_select('ppm_checklist_qual', array('checklist_id'=>$checklistId, 'checklist_qual_status'=>'1'), 'ABS(checklist_qual_numb)');
            foreach ($checklistQuals as $checklistQual) {
                switch ($checklistQual['frequency_id']) {
                    case '1';
                        $isYearly = true;
                        break;
                    case '2';
                        $isQuarterly = true;
                        break;
                    case '3';
                        $isMonthly = true;
                        break;
                    case '4';
                        $isWeekly = true;
                        break;
                    case '5';
                        $isDaily = true;
                        break;
                    case '6';
                        $isHalfAnnually = true;
                        break;
                }
            }

            $checklistQuans = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id'=>$checklistId, 'checklist_quan_status'=>'1'), 'ABS(checklist_quan_numb)');
            foreach ($checklistQuans as $checklistQuan) {
                switch ($checklistQuan['frequency_id']) {
                    case '1';
                        $isYearly = true;
                        break;
                    case '2';
                        $isQuarterly = true;
                        break;
                    case '3';
                        $isMonthly = true;
                        break;
                    case '4';
                        $isWeekly = true;
                        break;
                    case '5';
                        $isDaily = true;
                        break;
                    case '6';
                        $isHalfAnnually = true;
                        break;
                }
            }

            $dailyDates = $this->get_dates_day($contractDateStart, $contractDateEnd, $ppmDateStart);
            $weeklyDates = $this->get_dates_week($contractDateStart, $contractDateEnd, $ppmDateStart);
            $monthlyDates = $this->get_dates_month($contractDateStart, $contractDateEnd, $ppmDateStart);
            $quarterlyDates = $this->get_dates_quarter($contractDateStart, $contractDateEnd, $ppmDateStart);
            $halfAnnuallyDates = $this->get_dates_halfAnnual($contractDateStart, $contractDateEnd, $ppmDateStart);
            $yearlyDates = $this->get_dates_year($contractDateStart, $contractDateEnd, $ppmDateStart);

            $tempDays = array();
            foreach($dailyDates as $dateStr){
                if ($isDaily) {
                    array_push($tempDays, $dateStr);
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
                if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
                if ($isYearly && in_array($dateStr, $yearlyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
            }

            if (count($tempDays) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_DATES, 31);
            }

            $siteCode = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_code', null, 1);
            $runningNo = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_running_no', null, 1);
            $runningNo = intval($runningNo);
            
            // --- Include ppm_set_id in the ppm insert ---
            $ppmInsertData = array(
                'ppm_task_no' => $checklist['checklist_document_no'],
                'ppm_issue_no' => $checklist['checklist_issue_no'],
                'ppm_date_start' => $ppmDateStart,
                'asset_id' => $assetId,
                'checklist_id' => $checklistId,
                'asset_type_id' => $asset['asset_type_id'],
                'contract_id' => $contractId,
                'ppm_created_by' => $userId,
                'ppm_group_id' => $ppmGroupId, // Keep ppmGroupId for assignment purposes
                // only initialize ppm_set_id if it exists
                'ppm_set_id' => $ppmSetId, // Include ppm_set_id if it exists
                'ppm_status' => '0'
            );

            // if $ppmSetId is null remove it from the insert data
            if (is_null($ppmSetId)) {
                unset($ppmInsertData['ppm_set_id']);
            }

            $ppmId = Class_db::getInstance()->db_insert('ppm', $ppmInsertData);
            
            $currentMonth = array('year'=>'', 'month'=>'');

            foreach($tempDays as $key => $dateStr){
                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $ppmTaskNo = 'P'.$siteCode.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).$runningNoStr;
                $runningNo++;

                $taskId = $this->fn_task->create_new_task('1', $userId, '5', '1', $ppmTaskNo, $dateStr);
                $transactionId = Class_db::getInstance()->db_select_col('wfl_task', array('task_id' => $taskId), 'transaction_id', null, 1);
                $checklistGuideline = !empty($checklist['checklist_guideline']) ? $checklist['checklist_guideline'] : '';
                $ppmTaskId = Class_db::getInstance()->db_insert('ppm_task', array('ppm_task_no'=>$ppmTaskNo, 'ppm_task_schedule_date'=>$dateStr, 'ppm_id'=>$ppmId, 'ppm_task_guideline'=>$checklistGuideline,
                    'ppm_task_status'=>'12', 'transaction_id'=>$transactionId));

                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'A', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'17'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'B', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'17'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'C', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'D', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>empty($checklistQuans)?'19':'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'E', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'F', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'G', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'H', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));

                foreach ($checklistQuals as $checklistQual) {
                    $qualResult = '';
                    $qualFrequency = $checklistQual['frequency_id'];
                    if ($qualFrequency === '1' && !in_array($dateStr, $yearlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '2' && !in_array($dateStr, $quarterlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '3' && !in_array($dateStr, $monthlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '4' && !in_array($dateStr, $weeklyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '5' && !in_array($dateStr, $dailyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '6' && !in_array($dateStr, $halfAnnuallyDates)) {
                        $qualResult = '2';
                    }
                    Class_db::getInstance()->db_insert('ppm_task_qual', array('ppm_task_qual_numb'=>$checklistQual['checklist_qual_numb'], 'ppm_task_qual_desc'=>$checklistQual['checklist_qual_desc'], 'frequency_id'=>$qualFrequency,
                        'ppm_task_qual_result'=>$qualResult, 'ppm_task_id'=>$ppmTaskId, 'checklist_qual_id'=>$checklistQual['checklist_qual_id']));
                }

                foreach ($checklistQuans as $checklistQuan) {
                    $quanResult = '';
                    $quanFrequency = $this->fn_general->clear_null($checklistQuan['frequency_id']);
                    if ($quanFrequency === '1' && !in_array($dateStr, $yearlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '2' && !in_array($dateStr, $quarterlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '3' && !in_array($dateStr, $monthlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '4' && !in_array($dateStr, $weeklyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '5' && !in_array($dateStr, $dailyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '6' && !in_array($dateStr, $halfAnnuallyDates)) {
                        $quanResult = '2';
                    }
                    Class_db::getInstance()->db_insert('ppm_task_quan', array('ppm_task_quan_numb'=>$checklistQuan['checklist_quan_numb'], 'ppm_task_quan_desc'=>$checklistQuan['checklist_quan_desc'], 'frequency_id'=>$quanFrequency,
                        'ppm_task_quan_unit'=>$this->fn_general->clear_null($checklistQuan['checklist_quan_unit']), 'ppm_task_quan_set_values'=>$this->fn_general->clear_null($checklistQuan['checklist_quan_set_values']), 'ppm_task_quan_result'=>$quanResult, 'ppm_task_id'=>$ppmTaskId, 'checklist_quan_id'=>$checklistQuan['checklist_quan_id']));
                }

                $highestFrequency = '';
                if ($isDaily && in_array($dateStr, $dailyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'5'));
                    $highestFrequency = '5';
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'4'));
                    $highestFrequency = '4';
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'3'));
                    $highestFrequency = '3';
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'2'));
                    $highestFrequency = '2';
                }
                if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'6'));
                    $highestFrequency = '6';
                }
                if ($isYearly && in_array($dateStr, $yearlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'1'));
                    $highestFrequency = '1';
                }
                $ppmStartDate = $this->get_ppm_start_date($dateStr, $highestFrequency);
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_start_date'=>$ppmStartDate), array('ppm_task_id'=>$ppmTaskId));

                Class_db::getInstance()->db_update('wfl_task', array('task_status'=>'8', 'task_time_claimed'=>''), array('transaction_id'=>$transactionId));
                Class_db::getInstance()->db_update('wfl_transaction', array('transaction_date_due'=>$dateStr, 'transaction_status'=>'12', 'asset_no'=>$asset['asset_no']), array('transaction_id'=>$transactionId));
            }
            Class_db::getInstance()->db_update('cli_site', array('site_running_no'=>strval($runningNo)), array('site_id'=>$siteId));

            // Loop through ppmOld to update their status (from assign_ppm_single, this is for replacing old PPMs)
            $ppmOld = Class_db::getInstance()->db_select('ppm', array('asset_id'=>$assetId, 'contract_id'=>$contractId, 'ppm_status'=>'1')); // Fetch this here if not fetched above
            foreach ($ppmOld as $row) {
                Class_db::getInstance()->db_update('ppm', array('ppm_status'=>'6'), array('ppm_id'=>$row['ppm_id']));
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status'=>'53') , array('ppm_id'=>$row['ppm_id'], 'ppm_task_status'=>'12', 'ppm_task_schedule_date'=>'>='.$ppmDateStart));
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status'=>'3') , array('ppm_id'=>$row['ppm_id'], 'ppm_task_status'=>'12', 'ppm_task_schedule_date'=>'<'.$ppmDateStart));
            }
            Class_db::getInstance()->db_update('ppm', array('ppm_status' => '1'), array('ppm_id' => $ppmId));
            return array('ppmId'=>$ppmId, 'ppmTaskNo'=>$checklist['checklist_document_no'], 'assetNo'=>$asset['asset_no']);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmDate
     * @param $frequency
     * @return mixed
     * @throws Exception
     */
    private function get_ppm_start_date ($ppmDate, $frequency) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmDate)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmDate empty');
            }
            if (empty($frequency)) {
                throw new Exception('[' . __LINE__ . '] - Parameter frequency empty');
            }

            $ppmDate = new DateTime($ppmDate);
            if ($frequency === '1') {   // yearly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 year');
            } else if ($frequency === '2') {    // quarterly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-3 month');
            } else if ($frequency === '3') {    // monthly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
            } else if ($frequency === '4') {    // weekly
                $ppmDate->modify('-6 day');
            } else if ($frequency === '5') {    // daily
            } else if ($frequency === '6') {    // half-annually
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-6 month');
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter frequency invalid');
            }
            return $ppmDate->format("Y-m-d");
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param string $assetNo
     * @param string $searchTxt
     * @return array
     * @throws Exception
     */
    public function get_pending_task_m ($userId, $assetNo='', $searchTxt='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $restFilter = '';
            if (!empty($assetNo)) {
                $restFilter = 'AND ast_asset.asset_no = \''.$assetNo.'\'';
            }
            if (!empty($searchTxt)) {
                $restFilter .= 'AND (ast_asset.asset_no LIKE \'%'.$searchTxt.'%\' OR wfl_transaction.transaction_no LIKE \'%'.$searchTxt.'%\' OR ast_asset_type.asset_type_name LIKE \'%'.$searchTxt.'%\' OR cli_site.site_name LIKE \'%'.$searchTxt.'%\' '.
                    'OR sys_user.user_first_name LIKE \'%'.$searchTxt.'%\')';
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_task_ppm_pending', array(), 'ppm_task_start_date', '30', null, array('user_id'=>$userId, 'rest_filter'=>$restFilter));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['taskId'] = $dataLocal['task_id'];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['transactionNo'] = $dataLocal['transaction_no'];
                $row_result['assetNo'] = $dataLocal['asset_no'];
                $row_result['siteName'] = $dataLocal['site_name'];
                $row_result['assetTypeName'] = $dataLocal['asset_type_name'];
                $row_result['statusDesc'] = $dataLocal['status_desc'];
                $row_result['frequency'] = explode(',', $dataLocal['frequency']);
                $row_result['technician'] = $this->fn_general->clear_null($dataLocal['user_first_name'], 'Not yet claimed');
                $row_result['taskStartDue'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_start_date']);
                $row_result['taskDateDue'] = $row_result['taskStartDue'];
                //$row_result['taskDateDue'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_schedule_date']);
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param string $date
     * @param string $assetNo
     * @param string $searchTxt
     * @return array
     * @throws Exception
     */
    public function get_ppm_all_task_m ($userId, $date='', $assetNo='', $searchTxt='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $restFilter = '';
            $roles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$userId), 'role_id');
            if (in_array('1', $roles) || in_array('10', $roles)) {
                $restFilter = 'ppm_task.ppm_task_id > 0 ';
            }
            else if (in_array('2', $roles) || in_array('6', $roles) || in_array('3', $roles) || in_array('4', $roles) || in_array('5', $roles)) {
                $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id');
                if (empty($siteId)) {
                    return array();
                }
                $contractArr = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>$siteId), 'contract_id');
                if (empty($contractArr)) {
                    return array();
                }
                $contractArr = array_unique($contractArr, SORT_REGULAR);
                if (!empty($restFilter)) { $restFilter .= ' AND '; }
                $restFilter .= 'ppm.contract_id IN ('.implode(',', $contractArr).') ';
            } else {
                return array();
            }

            if (!empty($date)) {
                if (!empty($restFilter)) { $restFilter .= ' AND '; }
                $restFilter .= 'ppm_task_start_date = \''.$date.'\' ';
            }
            if (!empty($assetNo)) {
                if (!empty($restFilter)) { $restFilter .= ' AND '; }
                $restFilter .= 'ast_asset.asset_no = \''.$assetNo.'\' ';
            }
            if (!empty($searchTxt)) {
                if (!empty($restFilter)) { $restFilter .= ' AND '; }
                $restFilter .= '(ast_asset.asset_no LIKE \'%'.$searchTxt.'%\' OR ppm_task.ppm_task_no LIKE \'%'.$searchTxt.'%\' OR ast_asset_type.asset_type_name LIKE \'%'.$searchTxt.'%\' OR cli_site.site_name LIKE \'%'.$searchTxt.'%\' '.
                    'OR sys_user.user_first_name LIKE \'%'.$searchTxt.'%\') ';
            }
            if (!empty($restFilter)) { $restFilter .= ' AND '; }
            $restFilter .= 'ppm_task_status NOT IN (3, 53)';

            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrPpmFrequency = $this->fn_general->getPpmFrequency();
            $arrUserFullName[0] = 'Not yet claimed';
            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_task_ppm_all', array(), 'ppm_task_start_date', '30', null, array('rest_filter'=>$restFilter));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['taskId'] = '';
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['transactionNo'] = $dataLocal['ppm_task_no'];
                $row_result['assetNo'] = $dataLocal['asset_no'];
                $row_result['siteName'] = $dataLocal['site_name'];
                $row_result['assetTypeName'] = $dataLocal['asset_type_name'];
                $row_result['statusDesc'] = $dataLocal['status_desc'];
                $row_result['frequency'] = explode(',', $arrPpmFrequency[intval($dataLocal['frequency'])]);
                $row_result['technician'] = $arrUserFullName[intval($this->fn_general->clear_null($dataLocal['ppm_task_assigned_to'], '0'))];
                $row_result['taskStartDue'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_start_date']);
                $row_result['taskDateDue'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_start_date']);
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $month
     * @param $year
     * @return array
     * @throws Exception
     */
    public function get_calendar_task_dot_m ($userId, $month, $year) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if ($month === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter month empty');
            }
            if (empty($year)) {
                throw new Exception('[' . __LINE__ . '] - Parameter year empty');
            }

            $roles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$userId), 'role_id');
            if (in_array('1', $roles) || in_array('10', $roles)) {
                $siteId = '';
            }
            else if (in_array('2', $roles) || in_array('6', $roles) || in_array('3', $roles) || in_array('4', $roles) || in_array('5', $roles)) {
                $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$userId), 'site_id');
                if (empty($siteId)) {
                    return array();
                }
            } else {
                return array();
            }

            $contractArr = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>$siteId, 'contract_status'=>'1'), 'contract_id');
            if (empty($contractArr)) {
                return array();
            }
            $contractArr = array_unique($contractArr, SORT_REGULAR);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_task_ppm_calendar_count_all', array(), null, null, null, array('month'=>$month, 'year'=>$year, 'contract_id'=>implode(',', $contractArr)));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['date'] = $dataLocal['ppm_task_start_date'];
                $row_result['total'] = $dataLocal['total'];
                $row_result['status'] = explode(',', $dataLocal['status']);
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_status_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);

            $result = array();
            $arr_status = $this->fn_general->getRefStatus();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_task_section', array('ppm_task_id'=>$ppmTaskId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmTaskSectionId'] = $dataLocal['ppm_task_section_id'];
                $row_result['ppmTaskSectionName'] = $this->fn_general->clear_null($dataLocal['ppm_task_section_name']);
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskSectionStatus'] = $arr_status[intval($dataLocal['ppm_task_section_status'])];
                $row_result['checkParts'] = 'N/A';
                $row_result['checkAdditionalReport'] = 'N/A';
                if ($row_result['ppmTaskSectionName'] === 'E') {
                    $row_result['checkParts'] = $this->fn_general->clear_null($ppmTask['ppm_task_is_parts']);
                } else if ($row_result['ppmTaskSectionName'] === 'F') {
                    $row_result['checkAdditionalReport'] = $this->fn_general->clear_null($ppmTask['ppm_task_is_additional_report']);
                }
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_a_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('mw_ppm_section_a', array('ppm_task.ppm_task_id'=>$ppmTaskId), null, 1);
            $result['ppmTaskId'] = $dataLocal['ppm_task_id'];
            $result['ppmTaskScheduleDate'] = str_replace('-', '/', $dataLocal['ppm_task_schedule_date']);
            $result['assetId'] = $this->fn_general->clear_null($dataLocal['asset_id']);
            $result['assetGroupName'] = $this->fn_general->clear_null($dataLocal['asset_group_name']);
            $result['assetCategoryName'] = $this->fn_general->clear_null($dataLocal['asset_category_name']);
            $result['assetTypeName'] = $this->fn_general->clear_null($dataLocal['asset_type_name']);
            $result['assetBrandName'] = $this->fn_general->clear_null($dataLocal['asset_brand_name']);
            $result['assetModelName'] = $this->fn_general->clear_null($dataLocal['asset_model_name']);
            $result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
            $result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
            $result['locationCodeId'] = $this->fn_general->clear_null($dataLocal['location_code_id']);
            $result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
            $result['ppmIsGroup'] = $this->fn_general->clear_null($dataLocal['ppm_is_group']);
            $result['ppmTaskTimeStart'] = str_replace('-', '/', $dataLocal['ppm_task_time_start']);
            $result['ppmTaskTimeServiced'] = str_replace('-', '/', $dataLocal['ppm_task_time_serviced']);
            $result['assetList'] = array();
            if ($result['ppmIsGroup'] === '1') {
                $result['assetList'] = Class_db::getInstance()->db_select('mw_ppm_section_a_asset_group', array('pst.ppm_id'=>$dataLocal['ppm_id']), 'ast.asset_no');
            }
            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_b_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
            $result['ppmTaskId'] = $dataLocal['ppm_task_id'];
            $result['ppmTaskGuideline'] = $this->fn_general->clear_null($dataLocal['ppm_task_guideline']);

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_c_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $result = array();
            $frequencies = $this->fn_general->getPpmFrequency();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_task_qual', array('ppm_task_id'=>$ppmTaskId), 'ABS(ppm_task_qual_numb)');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmTaskQualId'] = $dataLocal['ppm_task_qual_id'];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskQualNumb'] = $this->fn_general->clear_null($dataLocal['ppm_task_qual_numb']);
                $row_result['ppmTaskQualDesc'] = $this->fn_general->clear_null($dataLocal['ppm_task_qual_desc']);
                $row_result['frequencyId'] = $this->fn_general->clear_null($dataLocal['frequency_id']);
                $row_result['frequencyName'] = $frequencies[intval($dataLocal['frequency_id'])];
                $row_result['ppmTaskQualResult'] = $this->get_q_task_result($dataLocal['ppm_task_qual_result']);
                $row_result['ppmTaskQualRemark'] = $this->fn_general->clear_null($dataLocal['ppm_task_qual_remark']);
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_d_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $result = array();
            $frequencies = $this->fn_general->getPpmFrequency();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_task_quan', array('ppm_task_id'=>$ppmTaskId), 'ABS(ppm_task_quan_numb)');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmTaskQuanId'] = $dataLocal['ppm_task_quan_id'];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskQuanNumb'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_numb']);
                $row_result['ppmTaskQuanDesc'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_desc']);
                $row_result['frequencyId'] = $this->fn_general->clear_null($dataLocal['frequency_id']);
                $row_result['frequencyName'] = $frequencies[intval($dataLocal['frequency_id'])];
                $row_result['ppmTaskQuanUnit'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_unit']);
                $row_result['ppmTaskQuanSetValues'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_set_values']);
                $row_result['ppmTaskQuanMeasuredValues'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_measured_values']);
                $row_result['ppmTaskQuanLimit'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_limit']);
                $row_result['ppmTaskQuanResult'] = $this->get_q_task_result($dataLocal['ppm_task_quan_result']);
                $row_result['ppmTaskQuanRemark'] = $this->fn_general->clear_null($dataLocal['ppm_task_quan_remark']);
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_e_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_task_parts', array('ppm_task_id'=>$ppmTaskId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmTaskPartsId'] = $dataLocal['ppm_task_parts_id'];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskPartsDesc'] = $this->fn_general->clear_null($dataLocal['ppm_task_parts_desc']);
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_g_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
            $result['ppmTaskId'] = $dataLocal['ppm_task_id'];
            $result['ppmTaskRemark'] = $this->fn_general->clear_null($dataLocal['ppm_task_remark']);

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $uploadType
     * @return array
     * @throws Exception
     */
    public function get_ppm_section_upload_m ($ppmTaskId, $uploadType) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($uploadType)) {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadType empty');
            }

            $imageType = ['Before', 'During', 'After', 'Additional Report'];
            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_ppm_section_h', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>$uploadType, 'sys_upload.upload_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmTaskUploadId'] = $dataLocal['ppm_task_upload_id'];
                $row_result['ppmTaskUploadType'] = $imageType[intval($dataLocal['ppm_task_upload_type'])];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskUploadLongitude'] = $this->fn_general->clear_null($dataLocal['ppm_task_upload_longitude']);
                $row_result['ppmTaskUploadLatitude'] = $this->fn_general->clear_null($dataLocal['ppm_task_upload_latitude']);
                $row_result['ppmTaskUploadTimestamp'] = str_replace('-', '/', $dataLocal['ppm_task_upload_timestamp']);
                $row_result['ppmTaskUploadDesc'] = $this->fn_general->clear_null($dataLocal['ppm_task_upload_desc']);
                $row_result['uploadId'] = $dataLocal['upload_id'];
                $row_result['uploadName'] = $this->fn_general->clear_null($dataLocal['upload_name']);
                $row_result['documentDesc'] = $this->fn_general->clear_null($dataLocal['document_desc']);
                $row_result['documentFilename'] = $this->fn_general->clear_null($dataLocal['upload_uplname']);
                $docUrl = $constant::URL.$dataLocal['upload_folder'].'/'.$dataLocal['upload_filename'].'.'.$dataLocal['upload_extension'];
                $row_result['documentSrc'] = $docUrl;
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $ppmTaskQuals
     * @throws Exception
     */
    public function save_qualitative_tasks_m ($ppmTaskId, $ppmTaskQuals, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (!is_array($ppmTaskQuals)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals is not array');
            }
            // Note: empty($ppmTaskQuals) is handled inside the helper now, to allow status update.

            // 1. Apply update to the initiating task
            // Check if current task is valid and at Checkpoint 1 (Service)
            $this->_apply_qualitative_task_update($ppmTaskId, $ppmTaskQuals, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }
                    // For Option A, we assume identical structure and propagate the same data.
                    // To do this, we need to map the original ppmTaskQuals by their checklistQualId or ppmTaskQualNumb
                    // instead of the direct ppm_task_qual_id, because the IDs will differ per task.
                    // The _apply_qualitative_task_update helper is adjusted to handle this.

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress if it started with group)
                    // For updates, we usually allow propagation if the task is In Progress (13) or Open (12).
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    // Note: We check for '13' because it implies it was part of the group start
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1' // Check its own group execution flag
                    ) {
                        try {
                            // To propagate, we need the checklistQualId from the original ppmTaskQuals.
                            // The input $ppmTaskQuals currently has 'id' (ppm_task_qual_id).
                            // We need to transform $ppmTaskQuals to include 'checklistQualId' if it's not already there.
                            // Assuming client sends 'checklistQualId' for group update scenarios,
                            // or we need to fetch it from the DB for the original ppmTaskId first.

                            // Let's modify the client's input ppmTaskQuals to add checklistQualId for propagation
                            $propagatedQuals = [];
                            foreach ($ppmTaskQuals as $qual) {
                                $originalQual = Class_db::getInstance()->db_select_single2('ppm_task_qual', ['ppm_task_qual_id' => $qual['id']], null, 1);
                                $propagatedQuals[] = [
                                    'checklistQualId' => $originalQual['checklistQualId'],
                                    'result' => $qual['result'],
                                    'remark' => $qual['remark']
                                ];
                            }
                            $this->_apply_qualitative_task_update($targetPpmTaskId, $propagatedQuals, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $ppmTaskQuans
     * @throws Exception
     */
    public function save_quantitative_tasks_m ($ppmTaskId, $ppmTaskQuans, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (!is_array($ppmTaskQuans)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans is not array');
            }
            // Note: empty($ppmTaskQuans) is handled inside the helper now, to allow status update.

            // 1. Apply update to the initiating task
            // Check if current task is valid and at Checkpoint 1 (Service)            
            $this->_apply_quantitative_task_update($ppmTaskId, $ppmTaskQuans, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            // Transform ppmTaskQuans to include checklistQuanId for propagation
                            $propagatedQuans = [];
                            foreach ($ppmTaskQuans as $quan) {
                                $originalQuan = Class_db::getInstance()->db_select_single2('ppm_task_quan', ['ppm_task_quan_id' => $quan['id']], null, 1);
                                $propagatedQuans[] = [
                                    'checklistQuanId' => $originalQuan['checklistQuanId'],
                                    'measuredValues' => $quan['measuredValues'],
                                    'limit' => $quan['limit'],
                                    'result' => $quan['result'],
                                    'remark' => $quan['remark']
                                ];
                            }
                            $this->_apply_quantitative_task_update($targetPpmTaskId, $propagatedQuans, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param string $checked
     * @throws Exception
     */
    public function save_ppm_check_parts_m ($ppmTaskId, $checked='0', $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if ($checked == '') {
                throw new Exception('[' . __LINE__ . '] - Parameter checked empty');
            }

            // 1. Apply update to the initiating task
            // Check if current task is valid and at Checkpoint 1 (Service)
            $this->_apply_ppm_check_parts_update($ppmTaskId, $checked, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            $this->_apply_ppm_check_parts_update($targetPpmTaskId, $checked, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $ppmTaskPartsDesc
     * @return mixed
     * @throws Exception
     */
    public function add_ppm_parts_m ($ppmTaskId, $ppmTaskPartsDesc, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($ppmTaskPartsDesc)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskPartsDesc empty');
            }
            // Original check for ERR_PPM_PARTS_EXIST should remain here for the initial task.
            // For propagation, the helper will handle the check per target task.
            if (Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_parts_desc'=>$ppmTaskPartsDesc, 'ppm_task_id'=>$ppmTaskId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_PARTS_EXIST, 31);
            }

            // 1. Apply update to the initiating task
            // Check if current task is valid and at Checkpoint 1 (Service)
            $result = $this->_apply_add_ppm_parts($ppmTaskId, $ppmTaskPartsDesc, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            // Propagate the same part description.
                            // The _apply_add_ppm_parts helper will handle duplicate checks per task.
                            $this->_apply_add_ppm_parts($targetPpmTaskId, $ppmTaskPartsDesc, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }
            return $result; // Return the result of the initial task addition

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskPartsId
     * @return
     * @throws Exception
     */
    public function delete_ppm_parts_m ($ppmTaskPartsId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskPartsId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskPartsId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_parts_id'=>$ppmTaskPartsId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Parts data not exist');
            }

            $ppmTaskId = Class_db::getInstance()->db_select_col('ppm_task_parts', array('ppm_task_parts_id'=>$ppmTaskPartsId), 'ppm_task_id', null, 1);
            Class_db::getInstance()->db_delete('ppm_task_parts', array('ppm_task_parts_id'=>$ppmTaskPartsId));
            $totalNull = Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_id'=>$ppmTaskId));
            $sectionStatus = $totalNull == '0' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'E'));

            return $ppmTaskId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $uploadId
     * @throws Exception
     */
    public function save_ppm_additional_report_m ($ppmTaskId, $uploadId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($uploadId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadId empty');
            }

            // Check if current task is valid and at Checkpoint 1 (Service)
            $this->check_current_task($ppmTaskId, '1', '');

            // Apply upload logic for the initiating task.
            // As per requirement, direct file uploads are UNIQUE per asset and DO NOT propagate to group tasks.
            $this->_apply_ppm_additional_report_upload($ppmTaskId, $uploadId);

            // No group propagation logic here as this is a direct file upload unique to the asset.

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param string $checked
     * @throws Exception
     */
    public function save_ppm_check_additional_report_m ($ppmTaskId, $checked='0', $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if ($checked == '') {
                throw new Exception('[' . __LINE__ . '] - Parameter checked empty');
            }

            // 1. Apply update to the initiating task
            // Check if current task is valid and at Checkpoint 1 (Service)
            $this->_apply_ppm_check_additional_report_update($ppmTaskId, $checked, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            $this->_apply_ppm_check_additional_report_update($targetPpmTaskId, $checked, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskUploadId
     * @return
     * @throws Exception
     */
    public function delete_ppm_additional_report_m ($ppmTaskUploadId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskUploadId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUploadId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_upload_id'=>$ppmTaskUploadId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Additional Report data not exist');
            }

            $ppmTaskUpload = Class_db::getInstance()->db_select_single('ppm_task_upload', array('ppm_task_upload_id'=>$ppmTaskUploadId), null, 1);
            $ppmTaskId = $ppmTaskUpload['ppm_task_id'];
            $uploadId = $ppmTaskUpload['upload_id'];
            $checked = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'ppm_task_is_additional_report', null, 1);
            Class_db::getInstance()->db_delete('ppm_task_upload', array('ppm_task_upload_id'=>$ppmTaskUploadId));
            Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$uploadId));

            $sectionStatus = '19';
            if ($checked === '1') {
                $totalFile = Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'3'));
                $sectionStatus = $totalFile == '0' ? '18' : '19';
            }
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'F'));
            return $ppmTaskId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param string $ppmTaskRemark
     * @throws Exception
     */
    public function save_ppm_remark_m ($ppmTaskId, $ppmTaskRemark='', $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            // 1. Apply update to the initiating task
            // Check if current task is valid and at Checkpoint 1 (Service)
            $this->_apply_ppm_remark_update($ppmTaskId, $ppmTaskRemark, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            $this->_apply_ppm_remark_update($targetPpmTaskId, $ppmTaskRemark, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $uploadId
     * @param $uploadType
     * @param string $longitude
     * @param string $latitude
     * @param int $userId // NEW: Added userId parameter
     * @throws Exception
     */
    public function save_ppm_maintenance_image_m ($ppmTaskId, $uploadId, $uploadType, $longitude='', $latitude='', $userId) { // User's provided updated signature
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            // Note: uploadId is generated by m_ppm.php and passed here.
            // No need to validate uploadId here again, as it comes from a trusted source (uploadDocument).
            if ($uploadType != '0' && $uploadType != '1' && $uploadType != '2') {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadType invalid');
            }
            if (empty($longitude)) {
                throw new Exception('[' . __LINE__ . '] - Parameter longitude empty');
            }
            if (empty($latitude)) {
                throw new Exception('[' . __LINE__ . '] - Parameter latitude empty');
            }

            // --- 1. Apply image upload logic for the initiating task ---
            // Check if current task is valid and at Checkpoint 1 (Service)
            $this->check_current_task($ppmTaskId, '1', $userId); // Pass userId here

            $this->_apply_ppm_maintenance_image_upload($ppmTaskId, $uploadId, $uploadType, $longitude, $latitude, $userId); // Pass userId here

            // --- 2. Implement Group Propagation Logic ---
            // "looks like user is ok to use one picture for all group execution."
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            // Propagate the SAME uploadId, uploadType, longitude, latitude, and userId.
                            // The helper will insert a new record for the target task.
                            $this->_apply_ppm_maintenance_image_upload($targetPpmTaskId, $uploadId, $uploadType, $longitude, $latitude, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskUploadId
     * @return mixed
     * @throws Exception
     */
    public function delete_ppm_maintenance_image_m ($ppmTaskUploadId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskUploadId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUploadId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_upload_id'=>$ppmTaskUploadId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Maintenance Image data not exist');
            }

            $ppmTaskUpload = Class_db::getInstance()->db_select_single('ppm_task_upload', array('ppm_task_upload_id'=>$ppmTaskUploadId), null, 1);
            $ppmTaskId = $ppmTaskUpload['ppm_task_id'];
            $uploadId = $ppmTaskUpload['upload_id'];
            Class_db::getInstance()->db_delete('ppm_task_upload', array('ppm_task_upload_id'=>$ppmTaskUploadId));
            Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$uploadId));

            $taskUploads = Class_db::getInstance()->db_select('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'(0,1,2)'));
            $totalFile0 = 0;
            $totalFile1 = 0;
            $totalFile2 = 0;
            foreach ($taskUploads as $taskUpload) {
                if ($taskUpload['ppm_task_upload_type'] == '0') {
                    $totalFile0++;
                } else if ($taskUpload['ppm_task_upload_type'] == '1') {
                    $totalFile1++;
                } else if ($taskUpload['ppm_task_upload_type'] == '2') {
                    $totalFile2++;
                }
            }
            $sectionStatus = ($totalFile0 > 0 && $totalFile1 > 0 && $totalFile2 > 0) ? '19' : '18';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'H'));
            return $ppmTaskId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $ppmTaskId
     * @param string $currentCheckpoint
     * @param string $userId
     * @return mixed
     * @throws Exception
     */
    public function check_current_task ($ppmTaskId='', $currentCheckpoint='', $userId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);
            $constant = $this->constant;

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($currentCheckpoint)) {
                throw new Exception('[' . __LINE__ . '] - Parameter currentCheckpoint empty');
            }

            $transactionId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'transaction_id', null, 1);
            $wfTask = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id'=>$transactionId, 'task_current'=>'1'), null, 1);
            if ($wfTask['checkpoint_id'] !== $currentCheckpoint) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_TASK_ALREADY_SUBMITTED, 31);
            }
            if (!empty($userId) && $wfTask['task_claimed_user'] !== $userId) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_TASK_CLAIMED, 31);
            }
            if (empty($userId) && $this->fn_general->clear_null($wfTask['task_claimed_user']) !== '') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_TASK_CLAIMED, 31);
            }

            return $wfTask;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $userId
     * @throws Exception
     */
    // public function save_ppm_scan_start_time_m ($ppmTaskId, $userId) {
    //     try {
    //         $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

    //         if (empty($ppmTaskId)) {
    //             throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
    //         }
    //         if (empty($userId)) {
    //             throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
    //         }

    //         Class_db::getInstance()->db_update('ppm_task', array('ppm_task_assigned_to'=>$userId, 'ppm_task_status'=>'13', 'ppm_task_time_start'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
    //         $transactionId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'transaction_id', null, 1);
    //         Class_db::getInstance()->db_update('wfl_transaction', array('user_id'=>$userId, 'transaction_status' => '13'), array('transaction_id' => $transactionId));
    //         Class_db::getInstance()->db_update('wfl_task', array('task_claimed_user'=>$userId, 'task_time_claimed'=>'Now()'), array('transaction_id'=>$transactionId, 'checkpoint_id'=>'1'));

    //         $ppmId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'ppm_id', null, 1);
    //         $checklistId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmId), 'checklist_id', null, 1);
    //         $checklist = Class_db::getInstance()->db_select_single2('ppm_checklist', array('checklist_id'=>$checklistId));
    //         if (!empty($checklist)) {
    //             $updateArr = array(
    //                 'ppmTaskMinExecTime'=>$checklist['checklistMinExecTime'],
    //                 'ppmTaskMaxExecTime'=>$checklist['checklistMaxExecTime'],
    //                 'ppmTaskMaxAssistant'=>$checklist['checklistMaxAssistant']
    //             );
    //             Class_db::getInstance()->db_update('ppm_task', $this->fn_general->convertToMysqlArrAll($updateArr), array('ppm_task_id'=>$ppmTaskId));
    //         }

    //         $totalNull = Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_qual_result'=>'is NULL'));
    //         $sectionStatus = $totalNull > '0' ? '18' : '19';
    //         Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'C'));

    //         $totalNull = Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_quan_result'=>'is NULL'));
    //         $sectionStatus = $totalNull > '0' ? '18' : '19';
    //         Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'D'));
    //     }
    //     catch(Exception $ex) {
    //         $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
    //         throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
    //     }
    // }

    /**
     * @param $ppmTaskId
     * @param $ppmTaskUploads
     * @throws Exception
     */
    public function save_image_desc_m ($ppmTaskId, $ppmTaskUploads, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (!is_array($ppmTaskUploads)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUploads is not array');
            }
            // Note: empty($ppmTaskUploads) is handled inside the helper.

            // To propagate by type, we need the upload type from the original input.
            // The input $ppmTaskUploads currently has 'ppmTaskUploadId'.
            // We need to transform $ppmTaskUploads to include 'ppmTaskUploadType' for propagation.
            $transformedPpmTaskUploads = [];
            foreach ($ppmTaskUploads as $upload) {
                // Fetch the uploadType for the original ppmTaskUploadId
                $originalUpload = Class_db::getInstance()->db_select_single2('ppm_task_upload', ['ppm_task_upload_id' => $upload['ppmTaskUploadId']], null, 1);
                $transformedPpmTaskUploads[] = [
                    'ppmTaskUploadId' => $upload['ppmTaskUploadId'], // Keep original ID for the initial update
                    'ppmTaskUploadDesc' => $upload['ppmTaskUploadDesc'],
                    'ppmTaskUploadType' => $originalUpload['ppmTaskUploadType'] // Add type for propagation
                ];
            }

            $this->_apply_image_desc_update($ppmTaskId, $transformedPpmTaskUploads, $userId);

            // 2. Check if this task is part of a group execution and propagate
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state (Open or In Progress)
                    $targetTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is still 'Open' (12) or 'In Progress' (13) and group executed
                    if (($targetTaskStatus === '12' || $targetTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            // Propagate the transformed data (including ppmTaskUploadType) to the helper
                            $this->_apply_image_desc_update($targetPpmTaskId, $transformedPpmTaskUploads, $userId);
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

/**
     * @param $ppmTaskId
     * @param $checkpoint
     * @param $result
     * @param $uploadId
     * @param $userId
     * @param string $remark
     * @param string $nextUser
     * @return mixed  // Will now return an array containing submitParam and groupNotificationData if applicable
     * @throws Exception
     */
    public function process_ppm ($ppmTaskId, $checkpoint, $result, $uploadId, $userId, $remark='', $nextUser='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($checkpoint)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpoint empty');
            }
            if (empty($result)) {
                throw new Exception('[' . __LINE__ . '] - Parameter result empty');
            }
            // uploadId can be empty if no file is uploaded.
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            // 1. Apply process to the initiating task
            // The check_current_task for the initiating task is already done in m_ppm.php for submit_ppm.
            // So we directly call the helper.
            $submitParam = $this->_apply_ppm_process($ppmTaskId, $checkpoint, $result, $uploadId, $userId, $remark, $nextUser);

            // 2. Perform workflow submission for the initiating task
            // This part handles the wfl_task updates and next checkpoint creation.
            // It uses taskId from $submitParam.
            $taskId = $submitParam['taskId'];
            if ($result == '1') { // Approved/Passed
                $toGroup = '';
                if ($checkpoint == '2' && !empty($nextUser)) { // If checker approves, and next user (verifier) is specified
                    $toGroup = $this->fn_task->get_group_id_from_user($nextUser, '4'); // Get group for Engineer role (role 4)
                }
                $this->fn_task->submit_task($taskId, $userId, '9', $remark, '', '', $toGroup, $nextUser); // Status 9: Submitted
                // If Section I (Assistant List) is completed as part of submit, mark it as done.
                if ($checkpoint == '1') { // If technician is submitting (checkpoint 1)
                     $this->savePpmTaskDoneAssistant($ppmTaskId); // Mark Section I as complete.
                }
            } else if ($result == '2') { // Re-open
                $this->fn_task->submit_task($taskId, $userId, '20', $remark, '1', '', '', '', 1); // Status 20: Rejected/Re-open, next=1 for re-open, skipTaskAssign=1
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter result invalid');
            }

            // --- Group Execution Consolidation ---
            $groupNotificationData = [];
            $initiatingTaskData = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $ppmTaskId), null, 1);

            if ($initiatingTaskData['ppmTaskIsGroupExecuted'] === '1') { // Check the new flag
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId);
                
                $groupPpmTaskNos = [];
                $groupEmailTo = $submitParam['emailTo']; // Use the primary task's intended recipient
                $groupTaskStatus = $submitParam['taskStatus'];
                $groupComment = $submitParam['comment'];

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    if ($targetPpmTaskId === $ppmTaskId) { // Skip the original task as its data is already in $submitParam
                        $groupPpmTaskNos[] = $submitParam['ppmTaskNo'];
                        continue;
                    }

                    // Before applying, ensure this target task is also in an eligible state
                    // For submission, tasks must be at the SAME CHECKPOINT and in Open(12) or In Progress(13) state
                    $targetTaskWorkflow = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id' => Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'transaction_id', null, 1), 'task_current' => '1'), null, 0);
                    $targetPpmTaskStatus = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_status', null, 1);

                    // Apply only if the target task is at the same checkpoint and in 'Open' (12) or 'In Progress' (13)
                    // and its own group execution flag is '1'.
                    if (!empty($targetTaskWorkflow) && $targetTaskWorkflow['checkpoint_id'] === $checkpoint &&
                        ($targetPpmTaskStatus === '12' || $targetPpmTaskStatus === '13') &&
                        Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_is_group_executed', null, 0) === '1'
                    ) {
                        try {
                            // Recursively call _apply_ppm_process for peer tasks
                            // Pass empty uploadId, as signature images are unique per asset.
                            $peerSubmitParam = $this->_apply_ppm_process($targetPpmTaskId, $checkpoint, $result, '', $userId, $remark, $nextUser);

                            // Also apply workflow submission for peer tasks
                            $peerTaskId = $peerSubmitParam['taskId'];
                            if ($result == '1') {
                                $peerToGroup = '';
                                if ($checkpoint == '2' && !empty($nextUser)) {
                                    $peerToGroup = $this->fn_task->get_group_id_from_user($nextUser, '4');
                                }
                                $this->fn_task->submit_task($peerTaskId, $userId, '9', $remark, '', '', $peerToGroup, $nextUser);
                                if ($checkpoint == '1') {
                                     $this->savePpmTaskDoneAssistant($targetPpmTaskId);
                                }
                            } else if ($result == '2') {
                                $this->fn_task->submit_task($peerTaskId, $userId, '20', $remark, '1', '', '', '', 1);
                            }
                            // Collect PPM Task Numbers for consolidated email
                            $groupPpmTaskNos[] = $peerSubmitParam['ppmTaskNo'];
                        } catch (Exception $e) {
                            // As per requirement, if any fails, rollback. So re-throw.
                            throw $e;
                        }
                    }
                }
                // Prepare consolidated notification data
                $groupNotificationData = [
                    'emailTo' => $groupEmailTo,
                    'taskStatus' => $groupTaskStatus,
                    'ppmTaskNos' => implode(', ', $groupPpmTaskNos), // Consolidated list of task numbers
                    'comment' => $groupComment
                ];
            }

            // Return original submitParam and the new consolidated data
            return array('submitParam' => $submitParam, 'groupNotificationData' => $groupNotificationData);

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param string $taskStatus
     * @param string $ppmTaskNo
     * @param string $comment
     */
    public function ppm_submit_notification ($userId, $taskStatus='', $ppmTaskNo='', $comment='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($taskStatus)) {
                throw new Exception('[' . __LINE__ . '] - Parameter taskStatus empty');
            }
            if (empty($ppmTaskNo)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskNo empty');
            }

            if (($taskStatus === 'pending verification' || $taskStatus === 'pending check') && !empty($userId)) {
                $this->fn_email->setup_email($userId, 1, array('task_name'=>$taskStatus, 'task_no'=>$ppmTaskNo));
                if ($taskStatus === 'pending verification') {
                    $this->fn_email->setup_mobile_notification($userId, 1, array('task_no'=>$ppmTaskNo));
                } else {
                    $this->fn_email->setup_mobile_notification($userId, 2, array('task_no'=>$ppmTaskNo));
                }
            } else if ($taskStatus === 're-open') {
                $this->fn_email->setup_email($userId, 2, array('task_no'=>$ppmTaskNo, 'comment'=>$comment));
                $this->fn_email->setup_mobile_notification($userId, 3, array('task_no'=>$ppmTaskNo));
            } else if ($taskStatus === 'completed') {
                $this->fn_email->setup_email($userId, 3, array('task_no'=>$ppmTaskNo));
                $this->fn_email->setup_mobile_notification($userId, 4, array('task_no'=>$ppmTaskNo));
            } else {
                throw new Exception('[' . __LINE__ . '] - Email condition false, taskStatus = '.$taskStatus);
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            //throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $dateStart
     * @param string $dateEnd
     * @param string $clientId
     * @param string $siteId
     * @param string $contractId
     * @return mixed
     * @throws Exception
     */
    public function get_total_ppm_task ($dateStart='', $dateEnd='', $clientId='', $siteId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $arrWhere = array();
            if (!empty($clientId) && empty($contractId)) {
                $contractIds = '';
                if (empty($siteId)) {
                    $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id' => $clientId, 'site_status' => '1'), 'site_id');
                    if (!empty($siteIds)) {
                        $siteIdStr = implode(',', $siteIds);
                        $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id' => '(' . $siteIdStr . ')', 'contract_status' => '1'), 'contract_id');
                    }
                } else {
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id' => $siteId, 'contract_status' => '1'), 'contract_id');
                }
                if (!empty($contractIds)) {
                    $contractId = '(' . implode(',', $contractIds) . ')';
                    $arrWhere['contract_id'] = $contractId;
                }
            }
            if (!empty($contractId)) {
                $arrWhere['contract_id'] = $contractId;
            }
            $arrWhere['DATE(ppm_task_start_date)'] = '>='.$dateStart;
            $arrWhere['DATE(ppm_task_start_date) '] = '<='.$dateEnd;
            return Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $dateStart
     * @param string $dateEnd
     * @param string $clientId
     * @param string $siteId
     * @param string $contractId
     * @return mixed
     * @throws Exception
     */
    public function get_total_ppm_late ($dateStart='', $dateEnd='', $clientId='', $siteId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $arrWhere = array('w1'=>'((ppm_task_time_serviced IS NULL AND CURDATE() > ppm_task_schedule_date) OR DATE(ppm_task_time_serviced) > ppm_task_schedule_date)');
            if (!empty($clientId) && empty($contractId)) {
                $contractIds = '';
                if (empty($siteId)) {
                    $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id' => $clientId, 'site_status' => '1'), 'site_id');
                    if (!empty($siteIds)) {
                        $siteIdStr = implode(',', $siteIds);
                        $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id' => '(' . $siteIdStr . ')', 'contract_status' => '1'), 'contract_id');
                    }
                } else {
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id' => $siteId, 'contract_status' => '1'), 'contract_id');
                }
                if (!empty($contractIds)) {
                    $contractId = '(' . implode(',', $contractIds) . ')';
                    $arrWhere['contract_id'] = $contractId;
                }
            }
            if (!empty($contractId)) {
                $arrWhere['contract_id'] = $contractId;
            }
            $arrWhere['DATE(ppm_task_start_date)'] = '>='.$dateStart;
            $arrWhere['DATE(ppm_task_start_date) '] = '<='.$dateEnd;
            return Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $dateStart
     * @param string $dateEnd
     * @param string $clientId
     * @param string $siteId
     * @param string $contractId
     * @return float|int
     * @throws Exception
     */
    public function get_perc_ppm_done ($dateStart='', $dateEnd='', $clientId='', $siteId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $arrWhere = array();
            if (!empty($clientId) && empty($contractId)) {
                $contractIds = '';
                if (empty($siteId)) {
                    $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id' => $clientId, 'site_status' => '1'), 'site_id');
                    if (!empty($siteIds)) {
                        $siteIdStr = implode(',', $siteIds);
                        $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id' => '(' . $siteIdStr . ')', 'contract_status' => '1'), 'contract_id');
                    }
                } else {
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id' => $siteId, 'contract_status' => '1'), 'contract_id');
                }
                if (!empty($contractIds)) {
                    $contractId = '(' . implode(',', $contractIds) . ')';
                    $arrWhere['contract_id'] = $contractId;
                }
            }
            if (!empty($contractId)) {
                $arrWhere['contract_id'] = $contractId;
            }
            $arrWhere['DATE(ppm_task_start_date)'] = '>='.$dateStart;
            $arrWhere['DATE(ppm_task_start_date) '] = '<='.$dateEnd;
            $total = Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
            if ($total == '0') {
                return 0;
            }
            $arrWhere['ppm_task_status'] = '16';
            $done = Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
            return intval($done)/intval($total)*100;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $checkpointId
     * @return mixed
     * @throws Exception
     */
    public function get_next_ppm_user ($ppmTaskId, $checkpointId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($checkpointId) || ($checkpointId != '1' && $checkpointId != '2')) {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpointId invalid');
            }

            if ($checkpointId == '1') {
                $roleId = '5';
                $checkpointTo = '2';
            } else {
                $roleId = '3';
                $checkpointTo = '3';
            }

            $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
            $transactionId = $ppmTask['transaction_id'];
            $ppmId = $ppmTask['ppm_id'];
            $ppmGroupId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmId), 'ppm_group_id', null, 1);
            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Variable ppmGroupId empty');
            }
            if (Class_db::getInstance()->db_count('wfl_task_assign', array('transaction_id'=>$transactionId, 'checkpoint_id'=>$checkpointTo, 'role_id'=>$roleId)) > 0) {
                return '';
            }

            $ppmGroupId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmId), 'ppm_group_id', null, 1);
            if ($checkpointId == '2') {
                $ppmGroupId = Class_db::getInstance()->db_select_col('ppm_group', array('ppm_group_id'=>$ppmGroupId, 'role_id'=>'5'), 'ppm_group_report_to', null, 1);
            }
            $ppmGroupTo = Class_db::getInstance()->db_select_col('ppm_group', array('ppm_group_id'=>$ppmGroupId, 'role_id'=>$roleId), 'ppm_group_report_to', null, 1);
            $ppmGroupUser = Class_db::getInstance()->db_select_colm('ppm_group_user', array('ppm_group_id'=>$ppmGroupTo), 'user_id');
            if (empty($ppmGroupUser)) {
                throw new Exception('[' . __LINE__ . '] - ' . $constant::ERR_PPM_GROUP_SUPERVISOR_EMPTY, 31);
            } else if (sizeof($ppmGroupUser) === 1) {
                $userId = $ppmGroupUser[0];
            } else {
                $userId = Class_db::getInstance()->db_select_col('vw_ppm_least_task', array(), 'user_id', 'total', 0, array('user_ids'=>implode(',',$ppmGroupUser)));
                if (empty($userId)) {
                    $userId = $ppmGroupUser[0];
                }
            }
            return $userId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $dateStart
     * @param string $dateEnd
     * @return mixed
     * @throws Exception
     */
    public function get_total_ppm_by_site_status ($clientId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
            $dataEmpty = array();
            foreach ($siteIds as $siteId) {
                array_push($dataEmpty, 0);
            }

            $series = array(
                array('name'=>'Open', 'ppmTaskStatus'=>'12', 'data'=>$dataEmpty),
                array('name'=>'In Progress', 'ppmTaskStatus'=>'13|21', 'data'=>$dataEmpty),
                array('name'=>'Check', 'ppmTaskStatus'=>'14', 'data'=>$dataEmpty),
                array('name'=>'Verify', 'ppmTaskStatus'=>'15', 'data'=>$dataEmpty),
                array('name'=>'Completed', 'ppmTaskStatus'=>'16', 'data'=>$dataEmpty)
            );
            if (!empty($siteIds)) {
                $siteIdStr = implode(',', $siteIds);
                $ppmBySites = Class_db::getInstance()->db_select('vg_count_ppm_by_site_status', array('site_id'=>'('.$siteIdStr.')'), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
                foreach ($ppmBySites as $ppmBySite) {
                    $status = $ppmBySite['ppm_task_status'];
                    $total = $ppmBySite['total'];
                    $siteIndex = array_search($ppmBySite['site_id'], $siteIds);
                    if ($status === '12') {
                        $series[0]['data'][$siteIndex] = intval($total);
                    } else if ($status === '13' || $status === '21') {
                        $series[1]['data'][$siteIndex] += intval($total);
                    } else if ($status === '14') {
                        $series[2]['data'][$siteIndex] += intval($total);
                    } else if ($status === '15') {
                        $series[3]['data'][$siteIndex] = intval($total);
                    } else if ($status === '16') {
                        $series[4]['data'][$siteIndex] = intval($total);
                    }
                }
            }

            return array('categories'=>$siteIds, 'series'=>$series);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $dateStart
     * @param string $dateEnd
     * @return mixed
     * @throws Exception
     */
    public function get_total_ppm_by_site_trade ($clientId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
            $dataEmpty = array();
            foreach ($siteIds as $siteId) {
                array_push($dataEmpty, 0);
            }

            $series = array();
            $assetGroups = Class_db::getInstance()->db_select('ast_asset_group', array('asset_group_status'=>'1'), 'asset_group_id');
            foreach ($assetGroups as $assetGroup) {
                array_push($series, array('name'=>$assetGroup['asset_group_name'], 'assetGroupId'=>$assetGroup['asset_group_id'], 'data'=>$dataEmpty));
            }

            if (!empty($siteIds)) {
                $siteIdStr = implode(',', $siteIds);
                $ppmByTrades = Class_db::getInstance()->db_select('vg_count_ppm_by_site_trade', array('site_id'=>'('.$siteIdStr.')'), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
                foreach ($ppmByTrades as $ppmByTrade) {
                    $assetGroupId = $ppmByTrade['asset_group_id'];
                    $total = $ppmByTrade['total'];
                    $siteIndex = array_search($ppmByTrade['site_id'], $siteIds);
                    for ($i=0; $i<count($series); $i++) {
                        if ($series[$i]['assetGroupId'] === $assetGroupId) {
                            $series[$i]['data'][$siteIndex] = intval($total);
                            break;
                        }
                    }
                }
            }

            return array('categories'=>$siteIds, 'series'=>$series);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $dateStart
     * @param string $dateEnd
     * @return array
     * @throws Exception
     */
    public function get_total_ppm_by_trade ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            if (empty($siteId)) {
                if (empty($clientId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
                }
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $siteId = '('.$siteIdStr.')';
                }
            }

            $series = array(
                array('name'=>'Civil', 'assetGroupId'=>'1', 'y'=>0, 'sliced'=>true, 'selected'=>true),
                array('name'=>'Electrical', 'assetGroupId'=>'2', 'y'=>0),
                array('name'=>'Mechanical', 'assetGroupId'=>'3', 'y'=>0),
                array('name'=>'ICT', 'assetGroupId'=>'4', 'y'=>0)
            );
            $ppmByTrades = Class_db::getInstance()->db_select('vg_count_ppm_by_site_trade', array('site_id'=>$siteId), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($ppmByTrades as $ppmByTrade) {
                $assetGroupId = $ppmByTrade['asset_group_id'];
                $total = $ppmByTrade['total'];
                if ($assetGroupId === '1') {
                    $series[0]['y'] += intval($total);
                } else if ($assetGroupId === '2') {
                    $series[1]['y'] += intval($total);
                } else if ($assetGroupId === '3') {
                    $series[2]['y'] += intval($total);
                } else if ($assetGroupId === '4') {
                    $series[3]['y'] += intval($total);
                }
            }

            return $series;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $dateStart
     * @param string $dateEnd
     * @return array
     * @throws Exception
     */
    public function get_total_ppm_by_status ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            if (empty($siteId)) {
                if (empty($clientId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
                }
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $siteId = '('.$siteIdStr.')';
                }
            }

            $categories = array('Open', 'In Progress', 'Check', 'Verify', 'Completed');
            $data = array(
                array('y'=>0, 'ppmTaskStatus'=>'12'),
                array('y'=>0, 'ppmTaskStatus'=>'13|21'),
                array('y'=>0, 'ppmTaskStatus'=>'14'),
                array('y'=>0, 'ppmTaskStatus'=>'15'),
                array('y'=>0, 'ppmTaskStatus'=>'16')
            );
            $ppmByStatus = Class_db::getInstance()->db_select('vg_count_ppm_by_site_status', array('site_id'=>$siteId), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($ppmByStatus as $ppmStatus) {
                $status = $ppmStatus['ppm_task_status'];
                $total = $ppmStatus['total'];
                if ($status === '12') {
                    $data[0]['y'] += intval($total);
                } else if ($status === '13' || $status === '21') {
                    $data[1]['y'] += intval($total);
                } else if ($status === '14') {
                    $data[2]['y'] += intval($total);
                } else if ($status === '15') {
                    $data[3]['y'] += intval($total);
                } else if ($status === '16') {
                    $data[4]['y'] += intval($total);
                }
            }

            return array('categories'=>$categories, 'data'=>$data);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $dateStart
     * @param string $dateEnd
     * @return array
     * @throws Exception
     */
    public function get_ppm_top5_execute ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            if (empty($siteId)) {
                if (empty($clientId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
                }
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $siteId = 'IN ('.$siteIdStr.')';
                }
            } else {
                $siteId = '= '.$siteId;
            }

            $categories = array();
            $data = array();
            $arrColor = array('#1b5e20', '#388e3c', '#4caf50', '#81c784', '#c8e6c9');
            $arrUserFullName = $this->fn_general->getUserFullName();

            $ppmByTop5Executes = Class_db::getInstance()->db_select('vg_ppm_top5_execute', array(), null, null, null, array('site_id'=>$siteId, 'date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($ppmByTop5Executes as $key => $ppmByTop5Execute) {
                array_push($categories, $arrUserFullName[intval($ppmByTop5Execute['ppm_task_serviced_by'])]);
                array_push($data,
                    array(
                        'y'=>intval($ppmByTop5Execute['total']),
                        'ppmTaskServicedBy'=>$ppmByTop5Execute['ppm_task_serviced_by'],
                        'color'=>$arrColor[$key]
                    )
                );
            }

            return array('categories'=>$categories, 'data'=>$data);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $dateStart
     * @param string $dateEnd
     * @return array
     * @throws Exception
     */
    public function get_ppm_bottom5_execute ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            if (empty($siteId)) {
                if (empty($clientId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
                }
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $siteId = 'IN ('.$siteIdStr.')';
                }
            } else {
                $siteId = '= '.$siteId;
            }

            $categories = array();
            $data = array();
            $arrColor = array('#ffccbc', '#ff8a65', '#ff5722', '#e64a19', '#bf360c');
            $arrUserFullName = $this->fn_general->getUserFullName();

            $ppmByBottom5Executes = Class_db::getInstance()->db_select('vg_ppm_bottom5_execute', array(), 'total DESC', null, null, array('site_id'=>$siteId, 'date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($ppmByBottom5Executes as $key => $ppmByBottom5Execute) {
                array_push($categories, $arrUserFullName[intval($ppmByBottom5Execute['ppm_task_serviced_by'])]);
                array_push($data,
                    array(
                        'y'=>intval($ppmByBottom5Execute['total']),
                        'ppmTaskServicedBy'=>$ppmByBottom5Execute['ppm_task_serviced_by'],
                        'color'=>$arrColor[$key]
                    )
                );
            }

            return array('categories'=>$categories, 'data'=>$data);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $dateStart
     * @param string $dateEnd
     * @return array
     * @throws Exception
     */
    public function get_ppm_average_execute_by_trade ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($dateStart)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateStart empty');
            }
            if (empty($dateEnd)) {
                throw new Exception('[' . __LINE__ . '] - Parameter dateEnd empty');
            }

            if (empty($siteId)) {
                if (empty($clientId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
                }
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $siteId = 'IN ('.$siteIdStr.')';
                }
            } else {
                $siteId = '= '.$siteId;
            }

            $categories = array();
            $data = array();

            $ppmByAverageExecutes = Class_db::getInstance()->db_select('vg_ppm_average_execute_by_trade', array(), null, null, null, array('site_id'=>$siteId, 'date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($ppmByAverageExecutes as $ppmByAverageExecute) {
                array_push($categories, $ppmByAverageExecute['asset_group_name']);
                array_push($data,
                    array(
                        'y'=>doubleval($ppmByAverageExecute['total']),
                        'display'=>substr($ppmByAverageExecute['display'], 0, 8),
                        'assetGroupId'=>$ppmByAverageExecute['asset_group_id']
                    )
                );
            }

            return array('categories'=>$categories, 'data'=>$data);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $siteId
     * @param string $year
     * @param string $month
     * @return array
     * @throws Exception
     */
    public function get_report_ppm_summary ($siteId='', $year='', $month='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (empty($year)) {
                throw new Exception('[' . __LINE__ . '] - Parameter year empty');
            }
            if ($month === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter month empty');
            }

            $result = array();
            $reportDatas = Class_db::getInstance()->db_select('vg_report_ppm_summary', array(), null, null, null, array('site_id'=>$siteId, 'selected_year'=>$year, 'selected_month'=>$month));
            foreach ($reportDatas as $reportData) {
                $row_result['assetTypeName'] = $reportData['asset_type_name'];
                $row_result['noAsset'] = $reportData['no_asset'];
                $row_result['frequency'] = $reportData['frequency'];
                $row_result['totalPpm'] = $reportData['total_ppm'];
                $row_result['ppmDone'] = $reportData['total_ppm_done'];
                $row_result['totalPercDone'] = intval($reportData['total_ppm_done']) > 0 ? intval($reportData['total_ppm_done'])/intval($reportData['total_ppm'])*100 : 0;
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $year
     * @param string $month
     * @param string $isRoutine
     * @return array
     * @throws Exception
     */
    public function get_ppm_list ($clientId='', $siteId='', $year='', $month='', $isRoutine='0') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($siteId)) {
                if (empty($clientId)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
                }
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $siteId = '('.$siteIdStr.')';
                }
            }

            $result = array();
            $dataLocals = Class_db::getInstance()->db_select('vw_ppm_list', array('site_id'=>$siteId, 'YEAR(ppm_task_start_date)'=>$year, 'MONTH(ppm_task_start_date) - 1'=>$month, 'ppm_is_routine'=>$isRoutine));
            foreach ($dataLocals as $dataLocal) {
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['ppmTaskNo'] = $dataLocal['ppm_task_no'];
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['ppmTaskStartDate'] = str_replace('-', '/', $dataLocal['ppm_task_start_date']);
                $row_result['ppmTaskScheduleDate'] = str_replace('-', '/', $dataLocal['ppm_task_schedule_date']);
                $row_result['frequency'] = $dataLocal['frequency'];
                $row_result['frequencyIds'] = $dataLocal['frequency_ids'];
                $row_result['uploadIds'] = explode('||', $dataLocal['upload_ids']);
                $row_result['documentNo'] = $dataLocal['document_no'];
                $row_result['assetNo'] = $dataLocal['asset_no'];
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetGroupId'] = $dataLocal['asset_group_id'];
                $row_result['assetCategoryId'] = $dataLocal['asset_category_id'];
                $row_result['assetTypeId'] = $dataLocal['asset_type_id'];
                $row_result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
                $row_result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
                $row_result['assetBlock'] = $this->fn_general->clear_null($dataLocal['asset_block']);
                $row_result['assetLevel'] = $this->fn_general->clear_null($dataLocal['asset_level']);
                $row_result['ppmTaskRemark'] = $this->fn_general->clear_null($dataLocal['ppm_task_remark']);
                $row_result['ppmGroupId'] = $dataLocal['ppm_group_id'];
                $row_result['ppmTaskIsScheduled'] = $dataLocal['ppm_task_is_scheduled'];
                $row_result['executor'] = $this->fn_general->clear_null($dataLocal['ppm_task_assigned_to']);
                $row_result['ppmTaskServicedBy'] = $this->fn_general->clear_null($dataLocal['ppm_task_serviced_by']);
                $row_result['reviewer'] = $this->fn_general->clear_null($dataLocal['ppm_task_checked_by']);
                $row_result['verifier'] = $this->fn_general->clear_null($dataLocal['ppm_task_verified_by']);
                $row_result['ppmTaskTimeStart'] = str_replace('-', '/', $dataLocal['ppm_task_time_start']);
                $row_result['ppmTaskTimeServiced'] = str_replace('-', '/', $dataLocal['ppm_task_time_serviced']);
                $row_result['ppmTaskTimeChecked'] = str_replace('-', '/', $dataLocal['ppm_task_time_checked']);
                $row_result['ppmTaskTimeVerified'] = str_replace('-', '/', $dataLocal['ppm_task_time_verified']);
                $row_result['lateness'] = $dataLocal['lateness'];
                $row_result['lateness2'] = $dataLocal['lateness2'];
                $row_result['ppmMinExecTime'] = $dataLocal['ppm_task_min_exec_time'];
                $row_result['ppmMaxExecTime'] = $dataLocal['ppm_task_max_exec_time'];
                $row_result['withinStatus'] = $dataLocal['within_status'];
                $row_result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
                $row_result['ppmTaskStatus'] = $dataLocal['ppm_task_status'];
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $put_vars
     * @throws Exception
     */
    public function reschedule_date ($ppmTaskId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['frequency']) || $put_vars['frequency'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter frequency empty');
            }
            if (!isset($put_vars['newDate']) || $put_vars['newDate'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter newDate empty');
            }

            $frequency = $put_vars['frequency'];
            $newDate = $put_vars['newDate'];

            $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
            if (Class_db::getInstance()->db_count('ppm_task', array('ppm_id'=>$ppmTask['ppm_id'], 'ppm_task_start_date'=>$newDate, 'ppm_task_id'=>'<>'.$ppmTaskId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_RESCHEDULE_EXIST, 31);
            }

            $ppmTaskFrequency = Class_db::getInstance()->db_select('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId), null, null, 1);
            if (sizeof($ppmTaskFrequency) > 1) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_RESCHEDULE_UNALLOWED, 31);
            } else if ($ppmTaskFrequency[0]['frequency_id'] !== $frequency) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_RESCHEDULE_UNALLOWED, 31);
            }

            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_start_date'=>$newDate, 'ppm_task_is_scheduled'=>'1'), array('ppm_task_id'=>$ppmTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_date_due'=>$newDate), array('transaction_id'=>$ppmTask['transaction_id']));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function getExecutionInfo ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($ppmTaskId));
            $minExecutionTime = '-';
            $maxExecutionTime = '-';
            $isTimeExceeded = false;

            $ppmTask = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id'=>$ppmTaskId));
            $ppmMinExecutionTime = $ppmTask['ppmTaskMinExecTime'];
            $ppmMaxExecutionTime = $ppmTask['ppmTaskMaxExecTime'];

            if ($ppmMinExecutionTime !== '') {
                $minTimeArr = explode(':', $ppmMinExecutionTime);
                if (count($minTimeArr) === 3) {
                    $minHours = intval($minTimeArr[0]);
                    $minMinutes = intval($minTimeArr[1]);
                    $minHoursText = '';
                    if ($minHours > 0) {
                        $minHoursText = $minHours === 1 ? '1 hour ' : $minHours . ' hours ';
                    }
                    $minMinutesText = '';
                    if ($minMinutes > 0) {
                        $minMinutesText = $minMinutes === 1 ? '1 minute' : $minMinutes . ' minutes';
                    }
                    $minExecutionTime = $minHoursText . $minMinutesText;
                }
            }

            if ($ppmMaxExecutionTime !== '') {
                $maxTimeArr = explode(':', $ppmMaxExecutionTime);
                if (count($maxTimeArr) === 3) {
                    $maxHours = intval($maxTimeArr[0]);
                    $maxMinutes = intval($maxTimeArr[1]);
                    $maxHoursText = '';
                    if ($maxHours > 0) {
                        $maxHoursText = $maxHours === 1 ? '1 hour ' : $maxHours.' hours ';
                    }
                    $maxMinutesText = '';
                    if ($maxMinutes > 0) {
                        $maxMinutesText = $maxMinutes === 1 ? '1 minute' : $maxMinutes.' minutes';
                    }
                    $maxExecutionTime = $maxHoursText.$maxMinutesText;

                    if ($ppmTask['ppmTaskTimeStart'] !== '' && $maxExecutionTime !== '') {
                        $now = new DateTime();
                        $assignTime = new DateTime($ppmTask['ppmTaskTimeStart']);
                        $assignTime->modify($maxExecutionTime);
                        $isTimeExceeded = $now > $assignTime;
                    }
                }
            }

            return array('minExecutionTime'=>$minExecutionTime, 'maxExecutionTime'=>$maxExecutionTime, 'isTimeExceeded'=>$isTimeExceeded);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @param string $userId
     * @return array
     * @throws Exception
     */
    public function assignPpmSingleV2 ($params, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);
            $constant = $this->constant;

            $this->fn_general->checkEmptyParams(array($userId));
            $this->fn_general->checkEmptyParamsArray($params, array('assetId', 'checklistId', 'ppmDateStart', 'ppmGroupId'));
            date_default_timezone_set("Asia/Kuala_Lumpur");

            $assetId = $params['assetId'];
            $checklistId = $params['checklistId'];
            $ppmDateStart = $params['ppmDateStart'];
            $ppmGroupId = $params['ppmGroupId']; // Keep ppmGroupId for assignment purposes
            $isYearly = false;
            $isHalfAnnually = false;
            $isQuarterly = false;
            $isMonthly = false;
            $isWeekly = false;
            $isDaily = false;

            $asset = Class_db::getInstance()->db_select_single2('ast_asset', array('asset_id'=>$assetId), null, 1);
            $checklist = Class_db::getInstance()->db_select_single2('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $contractId = $asset['contractId'];
            $contract = Class_db::getInstance()->db_select_single2('cli_contract', array('contract_id'=>$contractId), null, 1);
            $contractDateStart = $contract['contractDateStart'];
            $contractDateEnd = $contract['contractDateEnd'];
            $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);

            // --- Determine ppm_set_id for the assigned asset ---
            $ppmSetId = null;
            // Query ppm_set_asset to see if this assetId belongs to any ppm_set
            $assetSet = Class_db::getInstance()->db_select_single('ppm_set_asset', array('asset_id' => $assetId));
            if (!empty($assetSet)) {
                $tempPpmSetId = $assetSet['ppm_set_id'];

                // *********** PROPOSED FIX IMPLEMENTATION ***********
                // Defensively check if the retrieved ppmSetId actually exists in the ppm_set table
                if (Class_db::getInstance()->db_count('ppm_set', array('ppm_set_id' => $tempPpmSetId)) > 0) {
                    $ppmSetId = $tempPpmSetId;
                } else {
                    // Log a warning or error if an orphaned ppm_set_asset entry is found
                    $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Orphaned ppm_set_asset entry found for asset_id: ' . $assetId . '. ppm_set_id ' . $tempPpmSetId . ' does not exist in ppm_set. Setting ppm_set_id to NULL for this assignment.');
                    $ppmSetId = null; // Set to NULL if the referenced ppm_set does not exist
                }
                // ****************************************************
            }

            $checklistQuals = Class_db::getInstance()->db_select2('ppm_checklist_qual', array('checklist_id'=>$checklistId, 'checklist_qual_status'=>'1'), 'ABS(checklist_qual_numb)');
            foreach ($checklistQuals as $checklistQual) {
                switch ($checklistQual['frequencyId']) {
                    case '1';
                        $isYearly = true;
                        break;
                    case '2';
                        $isQuarterly = true;
                        break;
                    case '3';
                        $isMonthly = true;
                        break;
                    case '4';
                        $isWeekly = true;
                        break;
                    case '5';
                        $isDaily = true;
                        break;
                    case '6';
                        $isHalfAnnually = true;
                        break;
                }
            }

            $checklistQuans = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id'=>$checklistId, 'checklist_quan_status'=>'1'), 'ABS(checklist_quan_numb)');
            foreach ($checklistQuans as $checklistQuan) {
                switch ($checklistQuan['frequencyId']) {
                    case '1';
                        $isYearly = true;
                        break;
                    case '2';
                        $isQuarterly = true;
                        break;
                    case '3';
                        $isMonthly = true;
                        break;
                    case '4';
                        $isWeekly = true;
                        break;
                    case '5';
                        $isDaily = true;
                        break;
                    case '6';
                        $isHalfAnnually = true;
                        break;
                }
            }

            $dailyDates = $this->get_dates_day($contractDateStart, $contractDateEnd, $ppmDateStart);
            $weeklyDates = $this->get_dates_week($contractDateStart, $contractDateEnd, $ppmDateStart);
            $monthlyDates = $this->get_dates_month($contractDateStart, $contractDateEnd, $ppmDateStart);
            $quarterlyDates = $this->get_dates_quarter($contractDateStart, $contractDateEnd, $ppmDateStart);
            $halfAnnuallyDates = $this->get_dates_halfAnnual($contractDateStart, $contractDateEnd, $ppmDateStart);
            $yearlyDates = $this->get_dates_year($contractDateStart, $contractDateEnd, $ppmDateStart);

            $tempDays = array();
            foreach($dailyDates as $dateStr){
                if ($isDaily) {
                    $tempDays[] = $dateStr;
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
                if ($isYearly && in_array($dateStr, $yearlyDates) && !in_array($dateStr, $tempDays)) {
                    $tempDays[] = $dateStr;
                }
            }
            if (count($tempDays) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_DATES, 31);
            }

            $siteCode = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_code', null, 1);
            $runningNo = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_running_no', null, 1);
            $runningNo = intval($runningNo);
            
            // --- Include ppm_set_id in the ppm insert ---
            $ppmInsertData = array(
                'ppm_task_no' => $checklist['checklistDocumentNo'],
                'ppm_issue_no' => $checklist['checklistIssueNo'],
                'ppm_date_start' => $ppmDateStart,
                'asset_id' => $assetId,
                'checklist_id' => $checklistId,
                'asset_type_id' => $asset['assetTypeId'],
                'contract_id' => $contractId,
                'ppm_created_by' => $userId,
                'ppm_group_id' => $ppmGroupId, // Keep ppmGroupId for assignment purposes
                'ppm_set_id' => $ppmSetId,// <-- NEW: Include ppm_set_id
                'ppm_status' => '0'
            );

            // if $ppmSetId is null remove it from the insert data
            if (is_null($ppmSetId)) {
                unset($ppmInsertData['ppm_set_id']);
            }

            $ppmId = Class_db::getInstance()->db_insert('ppm', $ppmInsertData);

            foreach($tempDays as $dateStr){
                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $ppmTaskNo = 'P'.$siteCode.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).$runningNoStr;
                $runningNo++;

                $taskId = $this->fn_task->create_new_task('1', $userId, '5', '1', $ppmTaskNo, $dateStr);
                $transactionId = Class_db::getInstance()->db_select_col('wfl_task', array('task_id' => $taskId), 'transaction_id', null, 1);
                $checklistGuideline = !empty($checklist['checklistGuideline']) ? $checklist['checklistGuideline'] : '';
                $ppmTaskId = Class_db::getInstance()->db_insert('ppm_task', array('ppm_task_no'=>$ppmTaskNo, 'ppm_task_schedule_date'=>$dateStr, 'ppm_id'=>$ppmId, 'ppm_task_guideline'=>$checklistGuideline,
                    'ppm_task_status'=>'12', 'transaction_id'=>$transactionId));

                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'A', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'17'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'B', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'17'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'C', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'D', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>empty($checklistQuans)?'19':'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'E', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'F', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'G', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'H', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18'));
                $assistantStatus = (empty($checklist['checklistMaxAssistant']) || $checklist['checklistMaxAssistant'] === '0') ? '19' :'18';
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'I', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>$assistantStatus));

                foreach ($checklistQuals as $checklistQual) {
                    $qualResult = '';
                    $qualFrequency = $checklistQual['frequencyId'];
                    if ($qualFrequency === '1' && !in_array($dateStr, $yearlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '2' && !in_array($dateStr, $quarterlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '3' && !in_array($dateStr, $monthlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '4' && !in_array($dateStr, $weeklyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '5' && !in_array($dateStr, $dailyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '6' && !in_array($dateStr, $halfAnnuallyDates)) {
                        $qualResult = '2';
                    }
                    Class_db::getInstance()->db_insert('ppm_task_qual', array('ppm_task_qual_numb'=>$checklistQual['checklistQualNumb'], 'ppm_task_qual_desc'=>$checklistQual['checklistQualDesc'], 'frequency_id'=>$qualFrequency,
                        'ppm_task_qual_result'=>$qualResult, 'ppm_task_id'=>$ppmTaskId, 'checklist_qual_id'=>$checklistQual['checklistQualId']));
                }

                foreach ($checklistQuans as $checklistQuan) {
                    $quanResult = '';
                    $quanFrequency = $this->fn_general->clear_null($checklistQuan['frequencyId']);
                    if ($quanFrequency === '1' && !in_array($dateStr, $yearlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '2' && !in_array($dateStr, $quarterlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '3' && !in_array($dateStr, $monthlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '4' && !in_array($dateStr, $weeklyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '5' && !in_array($dateStr, $dailyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '6' && !in_array($dateStr, $halfAnnuallyDates)) {
                        $quanResult = '2';
                    }
                    Class_db::getInstance()->db_insert('ppm_task_quan', array('ppm_task_quan_numb'=>$checklistQuan['checklistQuanNumb'], 'ppm_task_quan_desc'=>$checklistQuan['checklistQuanDesc'], 'frequency_id'=>$quanFrequency,
                        'ppm_task_quan_unit'=>$this->fn_general->clear_null($checklistQuan['checklistQuanUnit']), 'ppm_task_quan_set_values'=>$this->fn_general->clear_null($checklistQuan['checklistQuanSetValues']), 'ppm_task_quan_result'=>$quanResult, 'ppm_task_id'=>$ppmTaskId, 'checklist_quan_id'=>$checklistQuan['checklistQuanId']));
                }

                $highestFrequency = '';
                if ($isDaily && in_array($dateStr, $dailyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'5'));
                    $highestFrequency = '5';
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'4'));
                    $highestFrequency = '4';
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'3'));
                    $highestFrequency = '3';
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'2'));
                    $highestFrequency = '2';
                }
                if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'6'));
                    $highestFrequency = '6';
                }
                if ($isYearly && in_array($dateStr, $yearlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'1'));
                    $highestFrequency = '1';
                }
                $ppmStartDate = $this->get_ppm_start_date($dateStr, $highestFrequency);
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_start_date'=>$ppmStartDate), array('ppm_task_id'=>$ppmTaskId));

                Class_db::getInstance()->db_update('wfl_task', array('task_status'=>'8', 'task_time_claimed'=>''), array('transaction_id'=>$transactionId));
                Class_db::getInstance()->db_update('wfl_transaction', array('transaction_date_due'=>$dateStr, 'transaction_status'=>'12', 'asset_no'=>$asset['assetNo']), array('transaction_id'=>$transactionId));
            }
            Class_db::getInstance()->db_update('cli_site', array('site_running_no'=>strval($runningNo)), array('site_id'=>$siteId));

            // Loop through ppmOld to update their status (from assign_ppm_single, this is for replacing old PPMs)
            $ppmOld = Class_db::getInstance()->db_select('ppm', array('asset_id'=>$assetId, 'contract_id'=>$contractId, 'ppm_status'=>'1')); // Fetch this here if not fetched above
            foreach ($ppmOld as $row) {
                Class_db::getInstance()->db_update('ppm', array('ppm_status'=>'6'), array('ppm_id'=>$row['ppm_id']));
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status'=>'53') , array('ppm_id'=>$row['ppm_id'], 'ppm_task_status'=>'12', 'ppm_task_schedule_date'=>'>='.$ppmDateStart));
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status'=>'3') , array('ppm_id'=>$row['ppm_id'], 'ppm_task_status'=>'12', 'ppm_task_schedule_date'=>'<'.$ppmDateStart));
            }
            Class_db::getInstance()->db_update('ppm', array('ppm_status' => '1'), array('ppm_id' => $ppmId));
            return array('ppmId'=>$ppmId, 'ppmTaskNo'=>$checklist['checklistDocumentNo'], 'assetNo'=>$asset['assetNo']);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function getPpmSectionStatusV2M ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $this->fn_general->checkEmptyParams(array($ppmTaskId));

            $ppmTask = $this->getPpmTask($ppmTaskId, 1);

            /**
             * Check if existing running task should have new section Assistant.
             * Only add new section if task status complete
             */
            if ($ppmTask['ppmTaskStatus'] !== '16' && Class_db::getInstance()->db_count('ppm_task_section', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'I')) == 0) {
                $checklistId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmTask['ppmId']), 'checklist_id', '', 1);
                $checklist = Class_db::getInstance()->db_select_single2('ppm_checklist', array('checklist_id' => $checklistId), '', 1);
                $assistantStatus = ($checklist['checklistMaxAssistant'] === '' || $checklist['checklistMaxAssistant'] === '0') ? '19' : '18';
                Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name' => 'I', 'ppm_task_id' => $ppmTaskId, 'ppm_task_section_status' => $assistantStatus));
            }

            $result = array();
            $statusArr = $this->fn_general->getRefStatus();
            $ppmTaskSectionArr = Class_db::getInstance()->db_select2('ppm_task_section', array('ppm_task_id'=>$ppmTaskId));
            foreach ($ppmTaskSectionArr as $ppmTaskSection) {
                $row_result['ppmTaskSectionId'] = $ppmTaskSection['ppmTaskSectionId'];
                $row_result['ppmTaskSectionName'] = $this->fn_general->clear_null($ppmTaskSection['ppmTaskSectionName']);
                $row_result['ppmTaskId'] = $ppmTaskSection['ppmTaskId'];
                $row_result['ppmTaskSectionStatus'] = $statusArr[intval($ppmTaskSection['ppmTaskSectionStatus'])];
                $row_result['checkParts'] = 'N/A';
                $row_result['checkAdditionalReport'] = 'N/A';
                if ($row_result['ppmTaskSectionName'] === 'E') {
                    $row_result['checkParts'] = $this->fn_general->clear_null($ppmTask['ppmTaskIsParts']);
                } else if ($row_result['ppmTaskSectionName'] === 'F') {
                    $row_result['checkAdditionalReport'] = $this->fn_general->clear_null($ppmTask['ppmTaskIsAdditionalReport']);
                }
                $result[] = $row_result;
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $ppmTaskId
     * @return void
     * @throws Exception
     */
    public function savePpmTaskDoneAssistant ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmTaskId));
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>'19'), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'I'));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function getAllPpmTaskGroupByPpmId ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmTaskId));

            //get groupId from ppm_task
            $ppmTask = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'transaction_id', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $initiatingPpmTaskId
     * @return array
     * @throws Exception
     */
    private function _get_group_tasks_for_execution ($initiatingPpmTaskId, $isOpen = 0) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($initiatingPpmTaskId));

            // Get the ppm_id and ppm_task_start_date of the initiating task
            $initiatingTask = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $initiatingPpmTaskId), null, 1);
            $ppmId = $initiatingTask['ppmId'];
            $ppmTaskStartDate = $initiatingTask['ppmTaskStartDate'];

            // Get the ppm_set_id from the master PPM schedule (ppm table)
            $masterPpm = Class_db::getInstance()->db_select_single2('ppm', array('ppm_id' => $ppmId), null, 1);
            $ppmSetId = $masterPpm['ppmSetId']; // This is the new column we added

            // --- ADD THIS DEBUG LINE ---
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'DEBUG: ppmSetId for initiating task ' . $initiatingPpmTaskId . ' (ppm_id ' . $ppmId . ') is: ' . (is_null($ppmSetId) ? 'NULL' : $ppmSetId));

            // If the initiating task's PPM is not part of any ppm_set, return only itself.
            // This handles the "optional set" scenario, where ppmGroupExecution=1 acts like single execution.
            if (empty($ppmSetId)) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Initiating PPM Task ID ' . $initiatingPpmTaskId . ' does not belong to a ppm_set. Returning only initiating task.');
                return [$initiatingPpmTaskId];
            }

            // --- Construct the SQL query to find all tasks in the same ppm_set for the same date ---
            $sql = "SELECT pt.ppm_task_id
                    FROM ppm_task pt
                    INNER JOIN ppm p ON p.ppm_id = pt.ppm_id
                    WHERE p.ppm_set_id = :ppmSetId
                      AND pt.ppm_task_start_date = :ppmTaskStartDate
                      AND pt.ppm_task_status = :ppmTaskStatus"; // Only 'Open' tasks

            $params = array(
                ':ppmSetId' => $ppmSetId,
                ':ppmTaskStartDate' => $ppmTaskStartDate,
                ':ppmTaskStatus' => $isOpen == 1 ? '12' : '13' // Status 12 is 'Open'
            );

            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Direct SQL for group tasks by ppm_set: ' . $sql . ' with params: ' . json_encode($params));

            $groupTasks = Class_db::getInstance()->db_raw_select_colm_prepared(
                $sql,          // The full SQL query string
                $params,       // The parameters for the query
                'ppm_task_id', // The column name to extract from the results
                null           // throwEmpty (null means do not throw on empty, return empty array)
            );

            // --- ADD THIS DEBUG LINE ---
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Tasks identified for group execution: ' . json_encode($groupTasks));

            return $groupTasks;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $userId
     * @param string $ppmGroupExecution  // Added new parameter for group execution flag
     * @throws Exception
     */
    public function save_ppm_scan_start_time_m ($ppmTaskId, $userId, $ppmGroupExecution = '0') { // Default to '0' for single execution
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if ($ppmGroupExecution !== '0' && $ppmGroupExecution !== '1') { // Validate the new parameter
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupExecution invalid');
            }

            // Centralized logic to apply scan start for a single task
            $applyScanStartToTask = function($targetPpmTaskId, $targetUserId, $isGroupExecutedFlag) {
                // Check if the current task is eligible for this operation (checkpoint 1, not claimed, etc.)
                // Pass '' for userId for check_current_task, as it's checking the task's state, not who is claiming it *now*
                // The actual claiming happens by updating task_claimed_user.
                $this->check_current_task($targetPpmTaskId, '1', ''); // Check if it's at Checkpoint 1 and not claimed

                Class_db::getInstance()->db_update('ppm_task', array(
                    'ppm_task_assigned_to' => $targetUserId,
                    'ppm_task_status' => '13', // In Progress
                    'ppm_task_time_start' => 'Now()',
                    'ppm_task_is_group_executed' => $isGroupExecutedFlag // Set the new flag
                ), array('ppm_task_id' => $targetPpmTaskId));

                $transactionId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'transaction_id', null, 1);
                Class_db::getInstance()->db_update('wfl_transaction', array(
                    'user_id' => $targetUserId,
                    'transaction_status' => '13' // In Progress
                ), array('transaction_id' => $transactionId));
                Class_db::getInstance()->db_update('wfl_task', array(
                    'task_claimed_user' => $targetUserId,
                    'task_time_claimed' => 'Now()'
                ), array('transaction_id' => $transactionId, 'checkpoint_id' => '1'));

                $ppmId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_id', null, 1);
                $checklistId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id' => $ppmId), 'checklist_id', null, 1);
                $checklist = Class_db::getInstance()->db_select_single2('ppm_checklist', array('checklist_id' => $checklistId));
                if (!empty($checklist)) {
                    $updateArr = array(
                        'ppmTaskMinExecTime' => $checklist['checklistMinExecTime'],
                        'ppmTaskMaxExecTime' => $checklist['checklistMaxExecTime'],
                        'ppmTaskMaxAssistant' => $checklist['checklistMaxAssistant']
                    );
                    Class_db::getInstance()->db_update('ppm_task', $this->fn_general->convertToMysqlArrAll($updateArr), array('ppm_task_id' => $targetPpmTaskId));
                }

                // Update section C and D status based on their initial state
                $totalNullQual = Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_qual_result' => 'is NULL'));
                $sectionStatusC = $totalNullQual > '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
                Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatusC), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'C'));

                $totalNullQuan = Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_quan_result' => 'is NULL'));
                $sectionStatusD = $totalNullQuan > '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
                Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatusD), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'D'));

                // Ensure Section I is added/updated if applicable (from getPpmSectionStatusV2M logic)
                $targetPpmTask = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id' => $targetPpmTaskId), null, 1);
                // Only add new section if task status is NOT complete (16) AND section I does not exist
                if ($targetPpmTask['ppmTaskStatus'] !== '16' && Class_db::getInstance()->db_count('ppm_task_section', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'I')) == 0) {
                    $checklistIdForSectionI = Class_db::getInstance()->db_select_col('ppm', array('ppm_id' => $targetPpmTask['ppmId']), 'checklist_id', '', 1);
                    $checklistForSectionI = Class_db::getInstance()->db_select_single2('ppm_checklist', array('checklist_id' => $checklistIdForSectionI), '', 1);
                    $assistantStatus = (empty($checklistForSectionI['checklistMaxAssistant']) || $checklistForSectionI['checklistMaxAssistant'] === '0') ? '19' : '18';
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name' => 'I', 'ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_status' => $assistantStatus));
                }
            }; // End of applyScanStartToTask anonymous function

            // Main logic for group vs. single execution
            if ($ppmGroupExecution === '1') {
                $groupTaskIds = $this->_get_group_tasks_for_execution($ppmTaskId, 1); // Get all eligible tasks in the group

                if (empty($groupTaskIds)) {
                    // This scenario means the initiating task itself wasn't "Open" or no other tasks qualified.
                    // This can happen if the initiating task is already in progress/completed etc.
                    // Or if no other assets are in the same group for that date.
                    throw new Exception('[' . __LINE__ . '] - No eligible tasks found for group execution with PPM Task ID: ' . $ppmTaskId);
                }

                foreach ($groupTaskIds as $targetPpmTaskId) {
                    // Use a try-catch for individual task processing if you want to skip faulty ones
                    // BUT as per requirement, we want to roll back the entire group if ANY fails.
                    // So, let exceptions bubble up.
                    $applyScanStartToTask($targetPpmTaskId, $userId, '1'); // Apply logic for each task, setting group_executed flag to 1
                }
            } else {
                // Single task execution
                $applyScanStartToTask($ppmTaskId, $userId, '0'); // Apply logic for single task, setting group_executed flag to 0
            }

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Helper method to apply qualitative task updates for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param array $ppmTaskQuals // Expects an array of arrays with 'id', 'result', 'remark'
     * @throws Exception
     */
    private function _apply_qualitative_task_update($targetPpmTaskId, $ppmTaskQuals, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (!is_array($ppmTaskQuals)) {
            throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals is not array');
        }

        $this->check_current_task($targetPpmTaskId, '1', $userId); // Check if it's at Checkpoint 1 and not claimed

        if (empty($ppmTaskQuals)) {
            // No qualitative tasks to update, but might still need to set section status if previously incomplete
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No ppmTaskQuals provided for PPM Task ID: ' . $targetPpmTaskId);
            // We can still try to determine section status if there are existing N/A tasks
            $totalNull = Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_qual_result' => 'is NULL'));
            $sectionStatus = $totalNull > '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'C'));
            return;
        }

        foreach ($ppmTaskQuals as $ppmTaskQual) {
            if (!array_key_exists('id', $ppmTaskQual) || empty($ppmTaskQual['id'])) {
                // For group execution (Option A), we might not have a direct 'id' mapping.
                // We need to find the corresponding ppm_task_qual_id for this targetPpmTaskId
                // based on checklist_qual_id or ppm_task_qual_numb.
                // Let's use checklist_qual_id if available, as it's a direct reference from checklist to task_qual.
                if (!array_key_exists('checklistQualId', $ppmTaskQual) || empty($ppmTaskQual['checklistQualId'])) {
                    throw new Exception('[' . __LINE__ . '] - Neither ppmTaskQuals[id] nor ppmTaskQuals[checklistQualId] exist for targetPpmTaskId: ' . $targetPpmTaskId);
                }
                $qualToUpdate = Class_db::getInstance()->db_select_single2(
                    'ppm_task_qual',
                    array(
                        'ppm_task_id' => $targetPpmTaskId,
                        'checklist_qual_id' => $ppmTaskQual['checklistQualId']
                    ),
                    null,
                    1 // Throw exception if not found, to ensure data consistency for Option A
                );
                $qualId = $qualToUpdate['ppmTaskQualId'];
            } else {
                // Original behavior: use the provided ID directly
                $qualId = $ppmTaskQual['id'];
            }

            if (!array_key_exists('result', $ppmTaskQual)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals[result] not exist');
            }
            if (!array_key_exists('remark', $ppmTaskQual)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals[remark] not exist');
            }

            Class_db::getInstance()->db_update(
                'ppm_task_qual',
                array(
                    'ppm_task_qual_result' => $ppmTaskQual['result'],
                    'ppm_task_qual_remark' => $ppmTaskQual['remark']
                ),
                array('ppm_task_qual_id' => $qualId)
            );
        }

        $totalNull = Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_qual_result' => 'is NULL'));
        $sectionStatus = $totalNull > '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'C'));
    }

    /**
     * Helper method to apply quantitative task updates for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param array $ppmTaskQuans // Expects an array of arrays with 'id', 'measuredValues', 'limit', 'result', 'remark'
     * @throws Exception
     */
    private function _apply_quantitative_task_update($targetPpmTaskId, $ppmTaskQuans, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (!is_array($ppmTaskQuans)) {
            throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans is not array');
        }

        $this->check_current_task($targetPpmTaskId, '1', $userId);

        if (empty($ppmTaskQuans)) {
            // No quantitative tasks to update, but might still need to set section status if previously incomplete
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No ppmTaskQuans provided for PPM Task ID: ' . $targetPpmTaskId);
            $totalNull = Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_quan_result' => 'is NULL'));
            $sectionStatus = $totalNull > '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'D'));
            return;
        }

        foreach ($ppmTaskQuans as $ppmTaskQuan) {
            if (!array_key_exists('id', $ppmTaskQuan) || empty($ppmTaskQuan['id'])) {
                // Find the corresponding ppm_task_quan_id for this targetPpmTaskId
                // based on checklist_quan_id.
                if (!array_key_exists('checklistQuanId', $ppmTaskQuan) || empty($ppmTaskQuan['checklistQuanId'])) {
                    throw new Exception('[' . __LINE__ . '] - Neither ppmTaskQuans[id] nor ppmTaskQuans[checklistQuanId] exist for targetPpmTaskId: ' . $targetPpmTaskId);
                }
                $quanToUpdate = Class_db::getInstance()->db_select_single2(
                    'ppm_task_quan',
                    array(
                        'ppm_task_id' => $targetPpmTaskId,
                        'checklist_quan_id' => $ppmTaskQuan['checklistQuanId']
                    ),
                    null,
                    1 // Throw exception if not found, to ensure data consistency for Option A
                );
                $quanId = $quanToUpdate['ppmTaskQuanId'];
            } else {
                // Original behavior: use the provided ID directly
                $quanId = $ppmTaskQuan['id'];
            }

            if (!array_key_exists('measuredValues', $ppmTaskQuan)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans[measuredValues] not exist');
            }
            if (!array_key_exists('limit', $ppmTaskQuan)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans[limit] not exist');
            }
            if (!array_key_exists('result', $ppmTaskQuan)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans[result] not exist');
            }
            if (!array_key_exists('remark', $ppmTaskQuan)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans[remark] not exist');
            }

            Class_db::getInstance()->db_update(
                'ppm_task_quan',
                array(
                    'ppm_task_quan_measured_values' => $ppmTaskQuan['measuredValues'],
                    'ppm_task_quan_limit' => $ppmTaskQuan['limit'],
                    'ppm_task_quan_result' => $ppmTaskQuan['result'],
                    'ppm_task_quan_remark' => $ppmTaskQuan['remark']
                ),
                array('ppm_task_quan_id' => $quanId)
            );
        }

        $totalNull = Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_quan_result' => 'is NULL'));
        $sectionStatus = $totalNull > '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'D'));
    }

    /**
     * Helper method to apply PPM check parts status update for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param string $checked '0' or '1'
     * @throws Exception
     */
    private function _apply_ppm_check_parts_update($targetPpmTaskId, $checked, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if ($checked == '') {
            throw new Exception('[' . __LINE__ . '] - Parameter checked empty');
        }

        $this->check_current_task($targetPpmTaskId, '1', $userId);

        Class_db::getInstance()->db_update('ppm_task', array('ppm_task_is_parts' => $checked), array('ppm_task_id' => $targetPpmTaskId));

        $sectionStatus = '19'; // Default to complete
        if ($checked === '1') {
            $totalFile = Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_id' => $targetPpmTaskId));
            $sectionStatus = $totalFile == '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
        }
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'E'));
    }

    /**
     * Helper method to add PPM parts for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param string $ppmTaskPartsDesc
     * @return array The added part's details.
     * @throws Exception
     */
    private function _apply_add_ppm_parts($targetPpmTaskId, $ppmTaskPartsDesc, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
        $constant = $this->constant;

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (empty($ppmTaskPartsDesc)) {
            throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskPartsDesc empty');
        }
        // Check for similar part description only if adding to the original task
        // For propagation, we might allow duplicates if the checklist has it.
        // However, for identical propagation, if it errors on one it should rollback all.
        if (Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_parts_desc' => $ppmTaskPartsDesc, 'ppm_task_id' => $targetPpmTaskId)) > 0) {
            throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_PARTS_EXIST, 31);
        }

        $this->check_current_task($targetPpmTaskId, '1', $userId);

        $ppmTaskPartsId = Class_db::getInstance()->db_insert('ppm_task_parts', array('ppm_task_parts_desc' => $ppmTaskPartsDesc, 'ppm_task_id' => $targetPpmTaskId));
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => '19'), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'E')); // 19: Complete

        $dataLocal = Class_db::getInstance()->db_select_single2('ppm_task_parts', array('ppm_task_parts_id' => $ppmTaskPartsId), null, 1);
        $result['ppmTaskPartsId'] = $dataLocal['ppmTaskPartsId'];
        $result['ppmTaskId'] = $dataLocal['ppmTaskId'];
        $result['ppmTaskPartsDesc'] = $this->fn_general->clear_null($dataLocal['ppmTaskPartsDesc']);

        return $result;
    }

    /**
     * Helper method to apply PPM check additional report status update for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param string $checked '0' or '1'
     * @throws Exception
     */
    private function _apply_ppm_check_additional_report_update($targetPpmTaskId, $checked, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if ($checked == '') {
            throw new Exception('[' . __LINE__ . '] - Parameter checked empty');
        }

        $this->check_current_task($targetPpmTaskId, '1', $userId);

        Class_db::getInstance()->db_update('ppm_task', array('ppm_task_is_additional_report' => $checked), array('ppm_task_id' => $targetPpmTaskId));

        $sectionStatus = '19'; // Default to complete
        if ($checked === '1') {
            $totalFile = Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => '3')); // Type '3' for additional report
            $sectionStatus = $totalFile == '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
        }
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'F'));
    }

    /**
     * Helper method to save an additional report upload for a single PPM task.
     * Note: Direct file uploads are unique per asset and do NOT propagate to group tasks.
     * @param bigint $targetPpmTaskId
     * @param bigint $uploadId
     * @throws Exception
     */
    private function _apply_ppm_additional_report_upload($targetPpmTaskId, $uploadId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (empty($uploadId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter uploadId empty');
        }
        // Ensure the 'additional report' checkbox was indeed checked for this task
        if (Class_db::getInstance()->db_count('ppm_task', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_is_additional_report' => '1')) == 0) {
            throw new Exception('[' . __LINE__ . '] - Additional Report check not saved for PPM Task ID: ' . $targetPpmTaskId);
        }

        Class_db::getInstance()->db_insert('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => '3', 'upload_id' => $uploadId)); // Type '3' for additional report
        $totalFile = Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => '3'));
        $sectionStatus = $totalFile == '0' ? '18' : '19'; // 18: Incomplete, 19: Complete
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'F'));
    }

    /**
     * Helper method to save PPM remark for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param string $ppmTaskRemark
     * @throws Exception
     */
    private function _apply_ppm_remark_update($targetPpmTaskId, $ppmTaskRemark, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }

        $this->check_current_task($targetPpmTaskId, '1', $userId);

        $sectionStatus = $ppmTaskRemark === '' ? '18' : '19'; // 18: Incomplete, 19: Complete
        Class_db::getInstance()->db_update('ppm_task', array('ppm_task_remark' => $ppmTaskRemark), array('ppm_task_id' => $targetPpmTaskId));
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'G'));
    }

    /**
     * Helper method to save a maintenance image upload for a single PPM task.
     * Note: This helper will now be called for each task in a group, with the same uploadId.
     * @param bigint $targetPpmTaskId
     * @param bigint $uploadId
     * @param string $uploadType ('0','1','2')
     * @param string $longitude
     * @param string $latitude
     * @param int $userId // NEW: Added userId parameter
     * @throws Exception
     */
    private function _apply_ppm_maintenance_image_upload($targetPpmTaskId, $uploadId, $uploadType, $longitude, $latitude, $userId) { // Added userId here
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (empty($uploadId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter uploadId empty');
        }
        if ($uploadType != '0' && $uploadType != '1' && $uploadType != '2') {
            throw new Exception('[' . __LINE__ . '] - Parameter uploadType invalid');
        }
        if (empty($longitude)) {
            throw new Exception('[' . __LINE__ . '] - Parameter longitude empty');
        }
        if (empty($latitude)) {
            throw new Exception('[' . __LINE__ . '] - Parameter latitude empty');
        }
        // This helper does not call check_current_task, as it's assumed to be done by the calling public method.
        // It simply applies the insert/update for the provided task.

        Class_db::getInstance()->db_insert('ppm_task_upload', array(
            'ppm_task_id' => $targetPpmTaskId,
            'ppm_task_upload_type' => $uploadType,
            'upload_id' => $uploadId,
            'ppm_task_upload_longitude' => $longitude,
            'ppm_task_upload_latitude' => $latitude
        ));

        $taskUploads = Class_db::getInstance()->db_select('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => '(0,1,2)'));
        $totalFile0 = 0; $totalFile1 = 0; $totalFile2 = 0;
        foreach ($taskUploads as $taskUpload) {
            if ($taskUpload['ppm_task_upload_type'] == '0') { $totalFile0++; }
            else if ($taskUpload['ppm_task_upload_type'] == '1') { $totalFile1++; }
            else if ($taskUpload['ppm_task_upload_type'] == '2') { $totalFile2++; }
        }
        $sectionStatus = ($totalFile0 > 0 && $totalFile1 > 0 && $totalFile2 > 0) ? '19' : '18'; // 18: Incomplete, 19: Complete
        Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status' => $sectionStatus), array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_section_name' => 'H'));
    }

    /**
     * Helper method to save image descriptions for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param array $ppmTaskUploads // Expects an array of arrays with 'ppmTaskUploadId', 'ppmTaskUploadDesc'
     * // For propagation, will also handle 'ppmTaskUploadType' to find matching images.
     * @throws Exception
     */
    private function _apply_image_desc_update($targetPpmTaskId, $ppmTaskUploads, $userId) {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (!is_array($ppmTaskUploads)) {
            throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUploads is not array');
        }
        if (empty($ppmTaskUploads)) {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No ppmTaskUploads provided for PPM Task ID: ' . $targetPpmTaskId);
            return;
        }

        // 1. Apply update to the initiating task
        // Check if current task is valid and at Checkpoint 1 (Service)
        $this->check_current_task($targetPpmTaskId, '1', $userId);

        foreach ($ppmTaskUploads as $ppmTaskUpload) {
            $uploadIdToUpdate = null;
            if (!array_key_exists('ppmTaskUploadId', $ppmTaskUpload) || empty($ppmTaskUpload['ppmTaskUploadId'])) {
                // For propagation, we need to find the target ppm_task_upload_id based on uploadType.
                // Assuming ppmTaskUploadType is provided or can be derived.
                if (!array_key_exists('ppmTaskUploadType', $ppmTaskUpload) || $ppmTaskUpload['ppmTaskUploadType'] === '') {
                     throw new Exception('[' . __LINE__ . '] - Neither ppmTaskUpload[ppmTaskUploadId] nor ppmTaskUpload[ppmTaskUploadType] exist for targetPpmTaskId: ' . $targetPpmTaskId);
                }
                // Find the existing image of this type for the target task. If multiple, pick the first or newest.
                $existingUploads = Class_db::getInstance()->db_select(
                    'ppm_task_upload',
                    array(
                        'ppm_task_id' => $targetPpmTaskId,
                        'ppm_task_upload_type' => $ppmTaskUpload['ppmTaskUploadType']
                    ),
                    'ppm_task_upload_timestamp DESC', // Order by newest if multiple
                    '1' // Limit to one
                );
                if (!empty($existingUploads)) {
                    $uploadIdToUpdate = $existingUploads[0]['ppm_task_upload_id'];
                } else {
                    // No matching image found on target task, cannot propagate description.
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No matching image of type ' . $ppmTaskUpload['ppmTaskUploadType'] . ' found for PPM Task ID: ' . $targetPpmTaskId . '. Skipping description update.');
                    continue; // Skip to next upload in the list
                }

            } else {
                // Original behavior: use the provided ID directly
                $uploadIdToUpdate = $ppmTaskUpload['ppmTaskUploadId'];
            }

            if (!array_key_exists('ppmTaskUploadDesc', $ppmTaskUpload)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUpload[ppmTaskUploadDesc] not exist');
            }

            Class_db::getInstance()->db_update(
                'ppm_task_upload',
                array('ppm_task_upload_desc' => $ppmTaskUpload['ppmTaskUploadDesc']),
                array('ppm_task_upload_id' => $uploadIdToUpdate)
            );
        }
    }

    /**
     * Helper method to apply the core PPM task processing (submission) for a single PPM task.
     * @param bigint $targetPpmTaskId
     * @param string $checkpoint ('1', '2', '3')
     * @param string $result ('1'=pass, '2'=re-open)
     * @param bigint $uploadId (Can be null/empty if no upload, or empty for propagated tasks)
     * @param int $userId (User performing the action)
     * @param string $remark
     * @param string $nextUser (Next user for the workflow, if applicable)
     * @return array Parameters for notification (taskId, emailTo, taskStatus, ppmTaskNo, comment)
     * @throws Exception
     */
    private function _apply_ppm_process($targetPpmTaskId, $checkpoint, $result, $uploadId, $userId, $remark = '', $nextUser = '') {
        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
        $constant = $this->constant;

        if (empty($targetPpmTaskId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter targetPpmTaskId empty');
        }
        if (empty($checkpoint)) {
            throw new Exception('[' . __LINE__ . '] - Parameter checkpoint empty');
        }
        if (empty($result)) {
            throw new Exception('[' . __LINE__ . '] - Parameter result empty');
        }
        if (empty($userId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
        }

        // Check if the current task is valid and at the expected checkpoint
        $task = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id' => Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'transaction_id', null, 1), 'task_current' => '1'), null, 1);
        if ($task['checkpoint_id'] !== $checkpoint) {
            throw new Exception('[' . __LINE__ . '] - Task for PPM Task ID ' . $targetPpmTaskId . ' is not at expected checkpoint ' . $checkpoint);
        }
        // Additional check: Ensure it's not claimed by someone else if not the initiating task
        // For propagation, if task_claimed_user is set, it implies it was claimed as part of the group start
        if ($task['task_claimed_user'] !== $userId && !empty($task['task_claimed_user'])) {
            throw new Exception('[' . __LINE__ . '] - Task for PPM Task ID ' . $targetPpmTaskId . ' already claimed by another user');
        }


        $statusUpdate = '';
        $taskName = '';
        $emailTo = '';
        $comment = '';

        if ($checkpoint === '1') {
            $statusUpdate = '14'; // Pending Check
            $taskName = 'pending check';
            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_serviced_by' => $userId, 'ppm_task_time_serviced' => 'Now()'), array('ppm_task_id' => $targetPpmTaskId));
            $emailTo = $nextUser; // Next user is the checker/supervisor
        } else if ($checkpoint === '2' && $result === '1') {
            $statusUpdate = '15'; // Pending Verification
            $taskName = 'pending verification';
            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_checked_by' => $userId, 'ppm_task_time_checked' => 'Now()'), array('ppm_task_id' => $targetPpmTaskId));
            $emailTo = $nextUser; // Next user is the verifier/engineer
        } else if ($checkpoint === '2' && $result === '2') {
            $statusUpdate = '21'; // Re-open
            $taskName = 're-open';
            $comment = !empty($remark) ? $remark : $task['task_remark']; // Use provided remark or existing task remark
            $receiver = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id' => $task['transaction_id'], 'role_id' => '5', 'checkpoint_id' => '1'), 'user_id', null, 1);
            $emailTo = $receiver; // Send to original assigned technician (role 5)
        } else if ($checkpoint === '3' && $result === '1') {
            $statusUpdate = '16'; // Completed
            $taskName = 'completed';
            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_verified_by' => $userId, 'ppm_task_time_verified' => 'Now()'), array('ppm_task_id' => $targetPpmTaskId));
            $emailTo = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_assigned_to', null, 1); // Notify the original assigned technician
        } else if ($checkpoint === '3' && $result === '2') {
            $statusUpdate = '21'; // Re-open
            $taskName = 're-open';
            $comment = !empty($remark) ? $remark : $task['task_remark'];
            $receiver = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id' => $task['transaction_id'], 'role_id' => '5', 'checkpoint_id' => '1'), 'user_id', null, 1);
            $emailTo = $receiver; // Send to original assigned technician (role 5)
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter result invalid for checkpoint ' . $checkpoint);
        }

        Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status' => $statusUpdate), array('ppm_task_id' => $targetPpmTaskId));
        if (!empty($uploadId)) {
            // This is for signature uploads (type 4,5,6). As per requirement, these are unique per asset.
            Class_db::getInstance()->db_insert('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => intval($checkpoint) + 3, 'upload_id' => $uploadId));
        }
        Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status' => $statusUpdate), array('transaction_id' => $task['transaction_id']));

        // Handle re-open: clear previous related uploads and reset task status fields
        if ($statusUpdate === '21') {
            $ppmUploads = Class_db::getInstance()->db_select('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => '(4,5,6)'));
            if (!empty($ppmUploads)) {
                foreach ($ppmUploads as $ppmUpload) {
                    Class_db::getInstance()->db_update('sys_upload', array('upload_status' => '6'), array('upload_id' => $ppmUpload['upload_id'])); // Soft delete
                }
                Class_db::getInstance()->db_delete('ppm_task_upload', array('ppm_task_id' => $targetPpmTaskId, 'ppm_task_upload_type' => '(4,5,6)'));
            }
            Class_db::getInstance()->db_update('ppm_task', array(
                'ppm_task_serviced_by' => '',
                'ppm_task_checked_by' => '',
                'ppm_task_verified_by' => '',
                'ppm_task_time_serviced' => '',
                'ppm_task_time_checked' => '',
                'ppm_task_time_verified' => ''
            ), array('ppm_task_id' => $targetPpmTaskId));

            // When re-opened, also reset group execution flag for the task if applicable
            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_is_group_executed' => '0'), array('ppm_task_id' => $targetPpmTaskId));
        }

        $ppmTaskNo = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id' => $targetPpmTaskId), 'ppm_task_no', null, 1);

        return array('taskId' => $task['task_id'], 'emailTo' => $emailTo, 'taskStatus' => $taskName, 'ppmTaskNo' => $ppmTaskNo, 'comment' => $comment);
    }

    /**
     * Creates a new PPM Set (asset group).
     * @param string $ppmSetName
     * @param string $ppmSetDesc
     * @param smallint $assetGroupId // NEW PARAMETER
     * @param smallint $assetCategoryId // NEW PARAMETER
     * @param smallint $assetTypeId
     * @param smallint $ppmGroupId
     * @param int $userId
     * @return array {ppmSetId: int}
     * @throws Exception
     */
    public function create_ppm_set_basic ($ppmSetName, $ppmSetDesc, $assetGroupId, $assetCategoryId, $assetTypeId, $ppmGroupId, $userId) { // Updated signature
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($ppmSetName)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmSetName empty');
            }
            // ADD THESE TWO VALIDATIONS:
            if (empty($assetGroupId)) { // Validate assetGroupId
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (empty($assetCategoryId)) { // Validate assetCategoryId
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }
            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            // Optional: Check for duplicate ppm_set_name if needed
            if (Class_db::getInstance()->db_count('ppm_set', array('ppm_set_name' => $ppmSetName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Set Name already exists', 31); // Custom error code 31 for user-friendly message
            }

            $insertData = array(
                'ppm_set_name' => $ppmSetName,
                'ppm_set_desc' => $ppmSetDesc,
                'asset_group_id' => $assetGroupId,      // ADD THIS
                'asset_category_id' => $assetCategoryId, // ADD THIS
                'asset_type_id' => $assetTypeId,
                'ppm_group_id' => $ppmGroupId,
                'ppm_set_created_by' => $userId,
                'ppm_set_status' => 1 // Active by default
            );

            $ppmSetId = Class_db::getInstance()->db_insert('ppm_set', $insertData);

            return array('ppmSetId' => $ppmSetId);

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Retrieves details of a single PPM Set (asset group).
     * @param smallint $ppmSetId The ID of the PPM Set to retrieve.
     * @return array
     * @throws Exception
     */
    public function get_ppm_set_details ($ppmSetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmSetId));

            $ppmSet = Class_db::getInstance()->db_select_single2('ppm_set', array('ppm_set_id' => $ppmSetId), null, 1); // Get basic ppm_set data

            // Ensure assetGroupId and assetCategoryId are included in the returned data for frontend update
            $ppmSet['assetGroupId'] = $this->fn_general->clear_null($ppmSet['assetGroupId']); // Ensure it's explicitly included
            $ppmSet['assetCategoryId'] = $this->fn_general->clear_null($ppmSet['assetCategoryId']); // Ensure it's explicitly included
            $ppmSet['assetTypeId'] = $this->fn_general->clear_null($ppmSet['assetTypeId']);
            $ppmSet['ppmGroupId'] = $this->fn_general->clear_null($ppmSet['ppmGroupId']);
            $ppmSet['ppmSetDesc'] = $this->fn_general->clear_null($ppmSet['ppmSetDesc']);
            $ppmSet['ppmSetName'] = $this->fn_general->clear_null($ppmSet['ppmSetName']);


            // Fetch names for display if not already available in the `ppm_set` direct select
            $ppmSet['assetGroupName'] = Class_db::getInstance()->db_select_col('ast_asset_group', array('asset_group_id' => $ppmSet['assetGroupId']), 'asset_group_name', null, 0);
            $ppmSet['assetCategoryName'] = Class_db::getInstance()->db_select_col('ast_asset_category', array('asset_category_id' => $ppmSet['assetCategoryId']), 'asset_category_name', null, 0);
            $ppmSet['assetTypeName'] = Class_db::getInstance()->db_select_col('ast_asset_type', array('asset_type_id' => $ppmSet['assetTypeId']), 'asset_type_name', null, 0);
            $ppmSet['ppmGroupName'] = Class_db::getInstance()->db_select_col('ppm_group', array('ppm_group_id' => $ppmSet['ppmGroupId']), 'ppm_group_name', null, 0);


            return $ppmSet; // Returns the data with camelCase keys
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Updates an existing PPM Set (asset group).
     * @param smallint $ppmSetId The ID of the PPM Set to update.
     * @param array $params Array of update parameters (ppmSetName, ppmSetDesc, assetGroupId, assetCategoryId, assetTypeId, ppmGroupId).
     * @param int $userId The ID of the user performing the action.
     * @return bool
     * @throws Exception
     */
    public function update_ppm_set ($ppmSetId, $params, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($ppmSetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmSetId empty');
            }
            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            // Verify ppmSetId exists
            if (Class_db::getInstance()->db_count('ppm_set', array('ppm_set_id' => $ppmSetId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Set ID not found: ' . $ppmSetId, 31);
            }

            // Basic validation for parameters that should be updated
            // Update this to include the new fields
            $this->fn_general->checkEmptyParamsArray($params, array('ppmSetName', 'assetGroupId', 'assetCategoryId', 'assetTypeId', 'ppmGroupId'));


            // Optional: Check for duplicate ppm_set_name if name is being changed
            $existingPpmSet = Class_db::getInstance()->db_select_single2('ppm_set', array('ppm_set_id' => $ppmSetId));
            if ($existingPpmSet['ppmSetName'] !== $params['ppmSetName'] &&
                Class_db::getInstance()->db_count('ppm_set', array('ppm_set_name' => $params['ppmSetName'])) > 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Set Name already exists', 31);
            }

            $updateData = array(
                'ppm_set_name' => $params['ppmSetName'],
                'ppm_set_desc' => $this->fn_general->clear_null($params['ppmSetDesc']),
                'asset_group_id' => $params['assetGroupId'],      // ADD THIS
                'asset_category_id' => $params['assetCategoryId'], // ADD THIS
                'asset_type_id' => $params['assetTypeId'],
                'ppm_group_id' => $params['ppmGroupId']
            );

            Class_db::getInstance()->db_update('ppm_set', $updateData, array('ppm_set_id' => $ppmSetId));

            return true; // Or return updated data
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

   /**
     * Retrieves a list of assets for selection in the PPM Set modal.
     * Can filter by asset group, category, and type of the ppm_set.
     * Excludes assets already linked to the current ppmSetId (if ppmSetId provided).
     *
     * @param smallint $ppmSetId (Optional) The ID of the PPM Set to exclude already added assets.
     * @param smallint $assetGroupId The Asset Group ID to filter by.
     * @param smallint $assetCategoryId The Asset Category ID to filter by.
     * @param smallint $assetTypeId The Asset Type ID to filter by.
     * @param bool $returnIdsOnly (NEW) If true, returns only an array of asset_ids, without display formatting.
     * @return array
     * @throws Exception
     */
    public function get_assets_for_ppm_set_modal ($ppmSetId = null, $assetGroupId, $assetCategoryId, $assetTypeId, $returnIdsOnly = false) { // MODIFIED SIGNATURE
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            // Ensure the required filter parameters are provided.
            $this->fn_general->checkEmptyParams(array($assetGroupId, $assetCategoryId, $assetTypeId));

            $result = array();
            $arrWhereTemplate = array(
                'ast_asset.asset_status' => '1', // Only active assets
                // Add filters for Asset Group, Category, and Type
                'ast_asset.asset_group_id' => $assetGroupId,
                'ast_asset.asset_category_id' => $assetCategoryId,
                'ast_asset.asset_type_id' => $assetTypeId
            );

            $arrWhere = $arrWhereTemplate; // Start with the template
            $assetsToExclude = []; // Initialize

            // Get ppm_set_id from ppm_set_asset table where asset group, asset category, and asset type match
            // Exclude assets that are already part of any PPM Set with the same filters
            $assetsInPpmSets = Class_db::getInstance()->db_select_colm(
                'ppm_set',
                array(
                    'asset_group_id' => $assetGroupId,
                    'asset_category_id' => $assetCategoryId,
                    'asset_type_id' => $assetTypeId
                ),
                'ppm_set_id'
            );

            if(!empty($assetsInPpmSets)) {
                $assetsInPpmSets = array_map('strval', $assetsInPpmSets);
                $assetsToExclude = Class_db::getInstance()->db_select_colm(
                    'ppm_set_asset',
                    array('ppm_set_id' => 'IN('. implode(',', $assetsInPpmSets) . ')'),
                    'asset_id'
                );
            }

            // Exclude assets that are already part of this specific ppm_set (if ppmSetId is provided)
            if (!empty($ppmSetId)) {
                $existingAssetIdsInSet = Class_db::getInstance()->db_select_colm(
                    'ppm_set_asset',
                    array('ppm_set_id' => $ppmSetId),
                    'asset_id'
                );

                if (!empty($assetsToExclude)) {
                    $existingAssetIdsInSet = array_merge($assetsToExclude, $existingAssetIdsInSet);
                    $existingAssetIdsInSet = array_unique($existingAssetIdsInSet);
                }
                $assetsToExclude = $existingAssetIdsInSet; // Update the main exclusion list
            }

            // Apply the combined exclusion list
            if (!empty($assetsToExclude)) {
                $uniqueAssetsToExclude = array_unique($assetsToExclude);
                $intAssetsToExclude = array_map('intval', $uniqueAssetsToExclude);
                $arrWhere['asset_id'] = 'N('. implode(',', $intAssetsToExclude) . ')'; // N(...) means NOT IN
            }

            // --- NEW CONDITIONAL RETURN LOGIC ---
            if ($returnIdsOnly) {
                // If only IDs are needed, use db_select_colm for efficiency and to get all matching IDs
                return Class_db::getInstance()->db_select_colm(
                    'ast_asset', // Directly query ast_asset
                    $arrWhere,
                    'asset_id' // Only select the asset_id column
                );
            } else {
                // Original behavior: return full asset details for display
                $arr_dataLocal = Class_db::getInstance()->db_select(
                    'ast_asset', // Directly query ast_asset
                    $arrWhere,
                    'asset_no ASC' // Order by asset number for display
                );

                foreach ($arr_dataLocal as $dataLocal) {
                    $row_result['assetId'] = $dataLocal['asset_id'];
                    $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                    $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                    $row_result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
                    // Add more relevant fields if needed for the selection modal display
                    array_push($result, $row_result);
                }
                return $result;
            }
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Retrieves a list of all PPM Sets (asset groups) with their associated asset counts.
     * @return array
     * @throws Exception
     */
    public function get_ppm_set_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            // --- REVISED: Use a new view in Class_sql for complex query ---
            // Assuming vw_ppm_set_list has been added to library/sql.php as per previous instruction.
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_ppm_set_list'); // Using the new view

            $result = array();
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmSetId'] = $dataLocal['ppm_set_id'];
                $row_result['ppmSetName'] = $this->fn_general->clear_null($dataLocal['ppm_set_name']);
                $row_result['ppmSetDesc'] = $this->fn_general->clear_null($dataLocal['ppm_set_desc']); // Ensure description is included
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
                $row_result['assetTypeName'] = $this->fn_general->clear_null($dataLocal['asset_type_name']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['ppmGroupName'] = $this->fn_general->clear_null($dataLocal['ppm_group_name']);
                $row_result['totalAssets'] = intval($dataLocal['total_assets']); // Ensure this is int
                $row_result['ppmSetStatus'] = $dataLocal['ppm_set_status'];
                array_push($result, $row_result);
            }

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Retrieves a list of assets that are associated with a given PPM Set.
     *
     * @param smallint $ppmSetId The ID of the PPM Set.
     * @return array A list of asset details.
     * @throws Exception
     */
    public function get_assets_in_ppm_set ($ppmSetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmSetId));

            $result = array();
            // Use a view or join to get asset details along with their link to ppm_set_asset
            // Assuming ast_asset has asset_no, asset_name, asset_location_desc
            $arr_dataLocal = Class_db::getInstance()->db_select(
                'vw_ppm_set_asset_details', // We will need to create this view or use a direct join
                array('ppm_set_id' => $ppmSetId),
                'asset_no ASC' // Order by asset number for consistency
            );

            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmSetAssetId'] = $dataLocal['ppm_set_asset_id']; // The ID of the linking table entry
                $row_result['ppmSetId'] = $dataLocal['ppm_set_id'];
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
                // Add any other relevant asset details for display in the "Assets in Set" list
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
    
    /**
     * Adds multiple assets to a specified PPM Set.
     *
     * @param smallint $ppmSetId The ID of the PPM Set.
     * @param array $assetIds An array of asset IDs to link to the PPM Set.
     * @param int $userId The ID of the user performing the action.
     * @param bool $allAssetSelected (NEW) Flag to indicate if 'select all' was used.
     * @return array An associative array with 'totalAdded' indicating the number of assets successfully added.
     * @throws Exception If parameters are empty, ppmSetId does not exist, or a database error occurs.
     */
    public function add_assets_to_ppm_set ($ppmSetId, $assetIds, $userId, $allAssetSelected = false) { // MODIFIED SIGNATURE: Removed assetGroupId, assetCategoryId, assetTypeId
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            
            // --- MODIFIED VALIDATION LOGIC ---
            // If 'select all' is true, assetIds from frontend is just a placeholder, so don't check its emptiness.
            // The filters will be fetched from ppm_set itself.
            $this->fn_general->checkEmptyParams(array($ppmSetId, $userId)); 
            
            // If not 'select all', then assetIds must contain actual selections and not be empty.
            if (!$allAssetSelected) {
                if (!is_array($assetIds) || empty($assetIds)) { 
                    throw new Exception('[' . __LINE__ . '] - Parameter assetIds must be a non-empty array for individual selection.');
                }
            }
            // --- END MODIFIED VALIDATION LOGIC ---
    
            // Verify that the ppmSetId exists AND fetch its associated filter data
            $ppmSetData = Class_db::getInstance()->db_select_single('ppm_set', array('ppm_set_id' => $ppmSetId), null, 1); 
            // db_select_single with throwEmpty=1 will throw an exception if ppmSetId is not found.

            // if $allAssetSelected is true, the $assetIds parameter is populated by fetching from the modal function.
            // If $allAssetSelected is false, $assetIds already contains the array from the frontend.

            if ($allAssetSelected == true) {
                // This is the "select all" path

                // Get the filter parameters directly from the fetched ppmSetData
                $assetGroupId = $ppmSetData['asset_group_id'];
                $assetCategoryId = $ppmSetData['asset_category_id'];
                $assetTypeId = $ppmSetData['asset_type_id'];

                // Validate these fetched filters - they should not be empty in the ppm_set itself
                // (This acts as a defensive check if ppm_set records somehow get created without these critical filters)
                if (empty($assetGroupId) || empty($assetCategoryId) || empty($assetTypeId)) {
                    throw new Exception('[' . __LINE__ . '] - PPM Set ID ' . $ppmSetId . ' has incomplete filter data (Asset Group, Category, or Type). Cannot perform "Select All".');
                }

                // Call get_assets_for_ppm_set_modal with the correct filter parameters fetched from ppm_set
                $assetsToAdd = $this->get_assets_for_ppm_set_modal($ppmSetId, $assetGroupId, $assetCategoryId, $assetTypeId, true); // Get all asset IDs

                if (empty($assetsToAdd)) {
                    // It's a valid scenario if no assets are found matching filters/exclusions.
                    // Simply return 0 added, no error.
                    return array('totalAdded' => 0); 
                }

                $assetIds = $assetsToAdd; // Use the fetched asset IDs for the loop
            }
            
            $totalAdded = 0;
            foreach ($assetIds as $assetId) {
                // Ensure assetId is a string before passing to db_count for consistency
                $assetIdStr = (string)$assetId; 
    
                // Check if this asset is already linked to this ppmSetId to prevent duplicates
                if (Class_db::getInstance()->db_count('ppm_set_asset', array('ppm_set_id' => $ppmSetId, 'asset_id' => $assetIdStr)) == '0') {
                    $insertData = array(
                        'ppm_set_id' => $ppmSetId,
                        'asset_id' => $assetIdStr, 
                        'ppm_set_asset_created_by' => $userId
                    );
                    Class_db::getInstance()->db_insert('ppm_set_asset', $insertData);
                    $totalAdded++;
                } else {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Asset ' . $assetIdStr . ' already exists in PPM Set ' . $ppmSetId . '. Skipping.');
                }
            }
    
            return array('totalAdded' => $totalAdded);
    
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Removes multiple assets from a specified PPM Set.
     *
     * @param smallint $ppmSetId The ID of the PPM Set.
     * @param array $assetIds An array of asset IDs to unlink from the PPM Set.
     * @param int $userId The ID of the user performing the action.
     * @return array An associative array with 'totalRemoved' indicating the number of assets successfully removed.
     * @throws Exception If parameters are empty, ppmSetId does not exist, or a database error occurs.
     */
    public function remove_assets_from_ppm_set ($ppmSetId, $assetIds, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmSetId, $assetIds, $userId));

            if (!is_array($assetIds) || empty($assetIds)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetIds must be a non-empty array');
            }

            // Verify that the ppmSetId actually exists
            if (Class_db::getInstance()->db_count('ppm_set', array('ppm_set_id' => $ppmSetId)) === '0') {
                throw new Exception('[' . __LINE__ . '] - PPM Set ID not found: ' . $ppmSetId);
            }

            $totalRemoved = 0;
            // Use db_delete with an IN clause for multiple asset IDs
            // This is more efficient than looping and deleting one by one
            $assetIdsString = '(' . implode(',', $assetIds) . ')';

            $where = array(
                'ppm_set_id' => $ppmSetId,
                'asset_id' => $assetIdsString // This will be used in an 'IN' clause by get_whereAnd_str
            );
            $totalRemoved = Class_db::getInstance()->db_delete('ppm_set_asset', $where);

            // Optionally, you might want to log each removal for detailed auditing,
            // but the save_audit at the API level will cover the overall action.

            return array('totalRemoved' => $totalRemoved);

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Deletes a PPM Set and its associated assets.
     *
     * @param smallint $ppmSetId The ID of the PPM Set to delete.
     * @param int $userId The ID of the user performing the action.
     * @return bool True on success.
     * @throws Exception
     */
    public function delete_ppm_set ($ppmSetId, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmSetId, $userId));

            // 1. Check if PPM Set exists
            if (Class_db::getInstance()->db_count('ppm_set', array('ppm_set_id' => $ppmSetId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - PPM Set ID not found: ' . $ppmSetId, 31);
            }

            // 2. IMPORTANT: Handle existing PPM records that reference this ppm_set_id.
            // If your `ppm.ppm_set_id` FK is `ON DELETE SET NULL`, this might happen automatically.
            // But if it's `RESTRICT` or `NO ACTION`, you must set them to NULL first or you'll get an FK error.
            // Even if `SET NULL`, it's good practice to log or be aware of affected records.
            $affectedPpmCount = Class_db::getInstance()->db_update(
                'ppm',
                array('ppm_set_id' => NULL), // Set to NULL when the PPM Set is deleted
                array('ppm_set_id' => $ppmSetId)
            );
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'DEBUG: ' . $affectedPpmCount . ' PPM records had their ppm_set_id set to NULL for ppmSetId: ' . $ppmSetId);


            // 3. Delete associated entries in ppm_set_asset (child table)
            $deletedAssetsCount = Class_db::getInstance()->db_delete('ppm_set_asset', array('ppm_set_id' => $ppmSetId));
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'DEBUG: Deleted ' . $deletedAssetsCount . ' assets from ppm_set_asset for ppmSetId: ' . $ppmSetId);

            // 4. Delete the PPM Set record itself
            $deletedSetCount = Class_db::getInstance()->db_delete('ppm_set', array('ppm_set_id' => $ppmSetId));
            if ($deletedSetCount === '0') {
                 throw new Exception('[' . __LINE__ . '] - Failed to delete PPM Set: ' . $ppmSetId);
            }

            $this->fn_general->save_audit('X_AUDIT_ID_FOR_DELETE_SET', $userId, 'Deleted PPM Set ID = ' . $ppmSetId);
            return true;

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Generates new PPM tasks for assets under a contract due to an extension period.
     *
     * @param int $contractId The ID of the extended contract.
     * @param string $oldContractEndDate The original contract end date (YYYY-MM-DD).
     * @param string $newContractEndDate The new extended contract end date (YYYY-MM-DD).
     * @throws Exception
     */
    public function generate_ppm_tasks_for_contract_extension($contractId, $oldContractEndDate, $newContractEndDate) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->fn_general->checkEmptyParams(array($contractId, $oldContractEndDate, $newContractEndDate));

            // 1. Get all assets under this contract
            $assets = Class_db::getInstance()->db_select(
                'ast_asset', // Querying ast_asset directly
                array('contract_id' => $contractId, 'asset_status' => '1'),
                null, null, 0 // Do not throw error if no assets, just return empty array
            );

            if (empty($assets)) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "No active assets found for contract {$contractId}. No PPM tasks to generate.");
                return; // Nothing to do
            }

            // --- CRUCIAL FIX: Get site_id from cli_contract using contractId ---
            // Assuming all assets under the same contract share the same siteId
            $contractDetails = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id' => $contractId), null, 1);
            $contractSiteId = $contractDetails['site_id']; // Get site_id from the contract

            if (empty($contractSiteId)) {
                $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, "ERROR: Contract {$contractId} has no associated site_id. Cannot generate PPM tasks."); //
                throw new Exception("Contract cannot be processed: Site ID not found for contract."); //
            }

            $generatedTaskCount = 0;
            // Use the site_id obtained from the contract
            $siteRunningNo = Class_db::getInstance()->db_select_col('cli_site', array('site_id' => $contractSiteId), 'site_running_no', null, 1);
            $siteRunningNo = intval($siteRunningNo);
            $siteCode = Class_db::getInstance()->db_select_col('cli_site', array('site_id' => $contractSiteId), 'site_code', null, 1);

            foreach ($assets as $asset) {
                $assetId = $asset['asset_id'];
                $assetTypeId = $asset['asset_type_id']; // For checklist filtering later

                // 2. Get the active master PPM schedule for this asset
                // Assuming an asset has one primary active PPM schedule (ppm_status = 1)
                $masterPpm = Class_db::getInstance()->db_select_single(
                    'ppm',
                    array('asset_id' => $assetId, 'ppm_status' => '1'),
                    null, 0 // Do not throw error if no master PPM
                );

                if (empty($masterPpm)) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "No active master PPM found for asset {$assetId}. Skipping.");
                    continue;
                }

                $ppmId = $masterPpm['ppm_id'];
                $checklistId = $masterPpm['checklist_id']; //
                $ppmDateStart = $masterPpm['ppm_date_start']; // Original PPM cycle start date
                $ppmGroupId = $masterPpm['ppm_group_id']; // Original PPM executor group

                // Retrieve checklist frequencies to calculate dates
                $checklistQuals = Class_db::getInstance()->db_select('ppm_checklist_qual', array('checklist_id'=>$checklistId, 'checklist_qual_status'=>'1')); //
                $checklistQuans = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id'=>$checklistId, 'checklist_quan_status'=>'1')); //

                $isYearly = false; $isHalfAnnually = false; $isQuarterly = false;
                $isMonthly = false; $isWeekly = false; $isDaily = false;

                foreach ($checklistQuals as $checklistQual) {
                    switch ($checklistQual['frequency_id']) {
                        case '1': $isYearly = true; break;
                        case '2': $isQuarterly = true; break;
                        case '3': $isMonthly = true; break;
                        case '4': $isWeekly = true; break;
                        case '5': $isDaily = true; break;
                        case '6': $isHalfAnnually = true; break;
                    }
                }
                foreach ($checklistQuans as $checklistQuan) {
                    switch ($checklistQuan['frequency_id']) {
                        case '1': $isYearly = true; break;
                        case '2': $isQuarterly = true; break;
                        case '3': $isMonthly = true; break;
                        case '4': $isWeekly = true; break;
                        case '5': $isDaily = true; break;
                        case '6': $isHalfAnnually = true; break;
                    }
                }

                // 3. Calculate new scheduled dates specifically within the extended period
                // Generate all theoretical dates based on original PPM start date and NEW contract end date
                $allPossibleDates = [];
                // We need to use the original ppmDateStart (cycle start) with the NEW contract end date.
                // However, we only want tasks whose *schedule date* falls within the extension period.
                // So, calculate all future dates starting from ppmDateStart up to newContractEndDate
                // and then filter those dates to be > oldContractEndDate.
                
                // Dates based on original PPM start and new contract end
                $dailyDates = $this->get_dates_day($ppmDateStart, $newContractEndDate, $oldContractEndDate); //
                $weeklyDates = $this->get_dates_week($ppmDateStart, $newContractEndDate, $oldContractEndDate); //
                $monthlyDates = $this->get_dates_month($ppmDateStart, $newContractEndDate, $oldContractEndDate); //
                $quarterlyDates = $this->get_dates_quarter($ppmDateStart, $newContractEndDate, $oldContractEndDate); //
                $halfAnnuallyDates = $this->get_dates_halfAnnual($ppmDateStart, $newContractEndDate, $oldContractEndDate); //
                $yearlyDates = $this->get_dates_year($ppmDateStart, $newContractEndDate, $oldContractEndDate); //


                $newScheduledDatesForExtension = [];
                // Collect dates that fall within the extended period AND match frequency criteria
                // Dates should be strictly after oldContractEndDate
                $startExtensionPeriod = new DateTime($oldContractEndDate);
                $startExtensionPeriod->modify('+1 day'); // Start from the day *after* the old end date

                $endExtendedPeriod = new DateTime($newContractEndDate);

                $tempDates = []; // Collect all dates first to avoid duplicates
                if ($isDaily) $tempDates = array_merge($tempDates, $dailyDates);
                if ($isWeekly) $tempDates = array_merge($tempDates, $weeklyDates);
                if ($isMonthly) $tempDates = array_merge($tempDates, $monthlyDates);
                if ($isQuarterly) $tempDates = array_merge($tempDates, $quarterlyDates);
                if ($isHalfAnnually) $tempDates = array_merge($tempDates, $halfAnnuallyDates);
                if ($isYearly) $tempDates = array_merge($tempDates, $yearlyDates);
                
                $tempDates = array_unique($tempDates); // Remove duplicates
                sort($tempDates); // Sort dates chronologically

                foreach ($tempDates as $dateStr) {
                    $currentDate = new DateTime($dateStr);
                    // Only include dates that are within the extended period
                    if ($currentDate >= $startExtensionPeriod && $currentDate <= $endExtendedPeriod) {
                        $newScheduledDatesForExtension[] = $dateStr;
                    }
                }

                if (empty($newScheduledDatesForExtension)) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "No new PPM tasks scheduled for asset {$assetId} in extended period.");
                    continue;
                }

                // 4. Generate new ppm_task entries for these dates
                foreach ($newScheduledDatesForExtension as $dateStr) {
                    $runningNoTemp = 100000 + $siteRunningNo;
                    $runningNoStr = substr(strval($runningNoTemp), 1);
                    $ppmTaskNo = 'P' . $siteCode . substr($dateStr, 2, 2) . substr($dateStr, 5, 2) . substr($dateStr, 8, 2) . $runningNoStr;
                    $siteRunningNo++; // Increment for next task

                    // Create new task in workflow
                    $taskId = $this->fn_task->create_new_task('1', $masterPpm['ppm_created_by'], '5', '1', $ppmTaskNo, $dateStr); // Use creator of original PPM
                    $transactionId = Class_db::getInstance()->db_select_col('wfl_task', array('task_id' => $taskId), 'transaction_id', null, 1);

                    $checklistGuideline = !empty($masterPpm['checklist_guideline']) ? $masterPpm['checklist_guideline'] : ''; // Assuming guideline is stored in ppm or checklist
                    $ppmTaskId = Class_db::getInstance()->db_insert('ppm_task', array(
                        'ppm_task_no' => $ppmTaskNo,
                        'ppm_task_schedule_date' => $dateStr,
                        'ppm_id' => $ppmId, // Link to existing master PPM
                        'ppm_task_guideline' => $checklistGuideline,
                        'ppm_task_status' => '12', // Open
                        'transaction_id' => $transactionId
                        // REMOVED: 'checklist_id' => $checklistId // This line was the problem.
                    ));

                    // Add task sections (similar to assign_ppm_single)
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'A', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'17')); 
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'B', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'17')); 
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'C', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18')); 
                    // You'll need to get checklistQuansCount from the fetched checklist, which is available
                    $checklistQuansCount = Class_db::getInstance()->db_count('ppm_checklist_quan', array('checklist_id'=>$checklistId, 'checklist_quan_status'=>'1'));
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'D', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>($checklistQuansCount == 0)?'19':'18')); 
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'E', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18')); 
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'F', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18')); 
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'G', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18')); 
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'H', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>'18')); 
                    // Need to get checklistMaxAssistant from the fetched checklist
                    $checklistMaxAssistant = Class_db::getInstance()->db_select_col('ppm_checklist', array('checklist_id'=>$checklistId), 'checklist_max_assistant', null, 0);
                    $assistantStatus = (empty($checklistMaxAssistant) || $checklistMaxAssistant === '0') ? '19' :'18';
                    Class_db::getInstance()->db_insert('ppm_task_section', array('ppm_task_section_name'=>'I', 'ppm_task_id'=>$ppmTaskId, 'ppm_task_section_status'=>$assistantStatus)); 


                    // Populate qualitative tasks for the new ppm_task
                    foreach ($checklistQuals as $checklistQual) {
                        $qualResult = ''; // Default result
                        $qualFrequency = $checklistQual['frequency_id'];
                        // Determine if this task is N/A for this specific date due to frequency mismatch
                        if (($qualFrequency === '1' && !in_array($dateStr, $yearlyDates)) ||
                            ($qualFrequency === '2' && !in_array($dateStr, $quarterlyDates)) ||
                            ($qualFrequency === '3' && !in_array($dateStr, $monthlyDates)) ||
                            ($qualFrequency === '4' && !in_array($dateStr, $weeklyDates)) ||
                            ($qualFrequency === '5' && !in_array($dateStr, $dailyDates)) ||
                            ($qualFrequency === '6' && !in_array($dateStr, $halfAnnuallyDates))) {
                            $qualResult = '2'; // Set to N/A
                        }
                        Class_db::getInstance()->db_insert('ppm_task_qual', array(
                            'ppm_task_qual_numb'=>$checklistQual['checklist_qual_numb'],
                            'ppm_task_qual_desc'=>$checklistQual['checklist_qual_desc'],
                            'frequency_id'=>$qualFrequency,
                            'ppm_task_qual_result'=>$qualResult,
                            'ppm_task_id'=>$ppmTaskId,
                            'checklist_qual_id'=>$checklistQual['checklist_qual_id']
                        ));
                    }

                    // Populate quantitative tasks for the new ppm_task
                    foreach ($checklistQuans as $checklistQuan) {
                        $quanResult = ''; // Default result
                        $quanFrequency = $this->fn_general->clear_null($checklistQuan['frequency_id']);
                         // Determine if this task is N/A for this specific date due to frequency mismatch
                        if (($quanFrequency === '1' && !in_array($dateStr, $yearlyDates)) ||
                            ($quanFrequency === '2' && !in_array($dateStr, $quarterlyDates)) ||
                            ($quanFrequency === '3' && !in_array($dateStr, $monthlyDates)) ||
                            ($quanFrequency === '4' && !in_array($dateStr, $weeklyDates)) ||
                            ($quanFrequency === '5' && !in_array($dateStr, $dailyDates)) ||
                            ($quanFrequency === '6' && !in_array($dateStr, $halfAnnuallyDates))) {
                            $quanResult = '2'; // Set to N/A
                        }
                        Class_db::getInstance()->db_insert('ppm_task_quan', array(
                            'ppm_task_quan_numb'=>$checklistQuan['checklist_quan_numb'],
                            'ppm_task_quan_desc'=>$checklistQuan['checklist_quan_desc'],
                            'frequency_id'=>$quanFrequency,
                            'ppm_task_quan_unit'=>$this->fn_general->clear_null($checklistQuan['checklist_quan_unit']),
                            'ppm_task_quan_set_values'=>$this->fn_general->clear_null($checklistQuan['checklist_quan_set_values']),
                            'ppm_task_quan_result'=>$quanResult,
                            'ppm_task_id'=>$ppmTaskId,
                            'checklist_quan_id'=>$checklistQuan['checklist_quan_id']
                        ));
                    }
                    
                    // --- ADDED THIS SECTION: ppm_task_frequency insertion for new tasks ---
                    $highestFrequency = ''; // Initialize for this task
                    if ($isDaily && in_array($dateStr, $dailyDates)) {
                        Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'5'));
                        $highestFrequency = '5';
                    }
                    if ($isWeekly && in_array($dateStr, $weeklyDates)) {
                        Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'4'));
                        $highestFrequency = '4';
                    }
                    if ($isMonthly && in_array($dateStr, $monthlyDates)) {
                        Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'3'));
                        $highestFrequency = '3';
                    }
                    if ($isQuarterly && in_array($dateStr, $quarterlyDates)) {
                        Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'2'));
                        $highestFrequency = '2';
                    }
                    if ($isHalfAnnually && in_array($dateStr, $halfAnnuallyDates)) {
                        Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'6'));
                        $highestFrequency = '6';
                    }
                    if ($isYearly && in_array($dateStr, $yearlyDates)) {
                        Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'1'));
                        $highestFrequency = '1';
                    }
                    $ppmStartDate = $this->get_ppm_start_date($dateStr, $highestFrequency);
                    Class_db::getInstance()->db_update('ppm_task', array('ppm_task_start_date'=>$ppmStartDate), array('ppm_task_id'=>$ppmTaskId));
                    // --- END ADDED SECTION ---

                    // Update workflow task and transaction status
                    Class_db::getInstance()->db_update('wfl_task', array('task_status'=>'8', 'task_time_claimed'=>''), array('transaction_id'=>$transactionId)); // Status 8: Open
                    Class_db::getInstance()->db_update('wfl_transaction', array('transaction_date_due'=>$dateStr, 'transaction_status'=>'12', 'asset_no'=>$asset['asset_no']), array('transaction_id'=>$transactionId)); // Status 12: Open

                    $generatedTaskCount++;
                } // End foreach newScheduledDatesForExtension

            } // End foreach assets

            // Update site running number only once after all tasks are generated for this contract
            Class_db::getInstance()->db_update('cli_site', array('site_running_no'=>strval($siteRunningNo)), array('site_id'=>$contractSiteId));

            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, "Generated {$generatedTaskCount} new PPM tasks for contract {$contractId} extension.");

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * Helper to synchronize open PPM task snapshots when master checklist is updated.
     * This method fetches the latest master checklist content and re-populates
     * the ppm_task_qual and ppm_task_quan for all 'OPEN' tasks using that checklist.
     * It also deletes the existing PDF file for the synchronized tasks to force regeneration on next view.
     *
     * @param smallint $masterChecklistId The ID of the master checklist that was just updated.
     * @throws Exception
     */
    public function _sync_open_task_snapshots($masterChecklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__ . ' for checklist: ' . $masterChecklistId);
            $this->fn_general->checkEmptyParams(array($masterChecklistId));

            // 1. Fetch the latest master checklist content (Qualitative and Quantitative items)
            $latestChecklistQuals = Class_db::getInstance()->db_select('ppm_checklist_qual', array('checklist_id' => $masterChecklistId, 'checklist_qual_status' => '1'), 'ABS(checklist_qual_numb)');
            $latestChecklistQuans = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id' => $masterChecklistId, 'checklist_quan_status' => '1'), 'ABS(checklist_quan_numb)');

            // 2. Find all active PPM schedules that currently use this master checklist
            $activePpmSchedules = Class_db::getInstance()->db_select('ppm', array('checklist_id' => $masterChecklistId, 'ppm_status' => '1'));

            if (empty($activePpmSchedules)) {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No active PPM schedules found for checklist: ' . $masterChecklistId);
                return; // No active PPMs using this checklist, nothing to sync down.
            }

            foreach ($activePpmSchedules as $ppmSchedule) {
                $ppmId = $ppmSchedule['ppm_id'];
                $ppmDateStart = $ppmSchedule['ppm_date_start']; // Master PPM's start date (used for date array calculation)
                $contractId = $ppmSchedule['contract_id'];

                // Get contract dates for calculating date arrays (needed for N/A logic for each task)
                $contract = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id' => $contractId), null, 1);
                $contractDateStart = $contract['contract_date_start'];
                $contractDateEnd = $contract['contract_date_end'];

                // Calculate date arrays based on the PPM's schedule (not the current date, to match task generation logic)
                $dailyDates = $this->get_dates_day($contractDateStart, $contractDateEnd, $ppmDateStart);
                $weeklyDates = $this->get_dates_week($contractDateStart, $contractDateEnd, $ppmDateStart);
                $monthlyDates = $this->get_dates_month($contractDateStart, $contractDateEnd, $ppmDateStart);
                $quarterlyDates = $this->get_dates_quarter($contractDateStart, $contractDateEnd, $ppmDateStart);
                $halfAnnuallyDates = $this->get_dates_halfAnnual($contractDateStart, $contractDateEnd, $ppmDateStart);
                $yearlyDates = $this->get_dates_year($contractDateStart, $contractDateEnd, $ppmDateStart);
                // Note: The above date arrays are calculated using the PPM's start date and contract dates.
                // We then use the *specific ppm_task_schedule_date* for N/A logic below.

                // 3. Find all 'OPEN' tasks for this specific PPM schedule
                $openPpmTasks = Class_db::getInstance()->db_select('ppm_task', array('ppm_id' => $ppmId, 'ppm_task_status' => '12'));

                if (empty($openPpmTasks)) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No open tasks found for PPM ID: ' . $ppmId . ' to sync.');
                    continue; // No open tasks for this PPM schedule, move to next master PPM.
                }

                foreach ($openPpmTasks as $openTask) {
                    $openPpmTaskId = $openTask['ppm_task_id'];
                    $openTaskScheduleDate = $openTask['ppm_task_schedule_date'];
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Syncing snapshot for open PPM Task ID: ' . $openPpmTaskId);

                    // --- ADDED BLOCK: Delete existing PDF file and update its status ---
                    $existingPdfId = $openTask['pdf_id']; // Get pdf_id directly from the ppm_task record

                    if (!empty($existingPdfId)) {
                        // Fetch PDF file details from sys_pdf table
                        $pdfDetails = Class_db::getInstance()->db_select_single(
                            'sys_pdf',
                            array('pdf_id' => $existingPdfId, 'pdf_status' => '1'), // Only active PDFs
                            null, 0 // Don't throw error if not found, just return empty
                        );

                        if (!empty($pdfDetails)) {
                            $pdfFolderPath = $pdfDetails['pdf_folder']; // e.g., 'pdf/ppm/2124'
                            $pdfFilename = $pdfDetails['pdf_filename'];   // e.g., 'ppm_2124016.pdf'
                            $pdfExtension = $pdfDetails['pdf_extension']; // e.g., 'pdf'

                            // Construct the absolute path to the PDF file
                            // Use the same logic as Class_pdf_ppm::create_pdf for path construction
                            $config = parse_ini_file('library/config.ini');
                            $environment = $config['environment'];

                            $filename_src_relative = '';
                            // This part ensures correct slash based on environment
                            if ($environment == 'windows') {
                                // Assuming $pdfFolderPath is like 'pdf/ppm/2124', basename gives '2124'
                                $filename_src_relative = '\ppm\\' . basename($pdfFolderPath) . '\\' . $pdfFilename;
                            } else {
                                $filename_src_relative = '/ppm/' . basename($pdfFolderPath) . '/' . $pdfFilename;
                            }
                            // dirname(dirname(__FILE__)) navigates from 'api/function/f_ppm.php' up to 'api/'
                            $absolutePdfPath = dirname(dirname(__FILE__)) . $filename_src_relative;

                            // Try to delete the physical file
                            if (file_exists($absolutePdfPath)) {
                                if (unlink($absolutePdfPath)) {
                                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Successfully deleted old PDF file: ' . $absolutePdfPath);
                                    // Update sys_pdf status to '6' (deleted/inactive)
                                    Class_db::getInstance()->db_update('sys_pdf', array('pdf_status' => '6'), array('pdf_id' => $existingPdfId));
                                    // Clear pdf_id in ppm_task so it's regenerated next time on view
                                    Class_db::getInstance()->db_update('ppm_task', array('pdf_id' => 'NULL'), array('ppm_task_id' => $openPpmTaskId));
                                } else {
                                    $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Failed to delete old PDF file (permission issue?): ' . $absolutePdfPath);
                                    // Optionally, throw an exception here if failure to delete is critical for your system
                                }
                            } else {
                                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Old PDF file not found at path (already deleted or path mismatch?): ' . $absolutePdfPath);
                                // If file not found on disk but sys_pdf entry exists and is active, mark sys_pdf as deleted and clear ppm_task.pdf_id
                                Class_db::getInstance()->db_update('sys_pdf', array('pdf_status' => '6'), array('pdf_id' => $existingPdfId));
                                Class_db::getInstance()->db_update('ppm_task', array('pdf_id' => 'NULL'), array('ppm_task_id' => $openPpmTaskId));
                            }
                        } else {
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'sys_pdf entry not found for pdf_id: ' . $existingPdfId . ' or not active. Skipping physical file deletion.');
                            // If sys_pdf entry doesn't exist or is not active, just clear ppm_task.pdf_id for good measure
                            Class_db::getInstance()->db_update('ppm_task', array('pdf_id' => 'NULL'), array('ppm_task_id' => $openPpmTaskId));
                        }
                    } else {
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'No existing PDF associated with PPM Task ID: ' . $openPpmTaskId);
                    }
                    // --- END ADDED BLOCK ---

                    // --- Delete and Re-insert Qualitative Task Snapshots ---
                    Class_db::getInstance()->db_delete('ppm_task_qual', array('ppm_task_id' => $openPpmTaskId));
                    foreach ($latestChecklistQuals as $checklistQual) {
                        $qualResult = '';
                        $qualFrequency = $checklistQual['frequency_id'];
                        // Re-apply N/A logic based on the task's schedule date and calculated date arrays
                        if (($qualFrequency === '1' && !in_array($openTaskScheduleDate, $yearlyDates)) ||
                            ($qualFrequency === '2' && !in_array($openTaskScheduleDate, $quarterlyDates)) ||
                            ($qualFrequency === '3' && !in_array($openTaskScheduleDate, $monthlyDates)) ||
                            ($qualFrequency === '4' && !in_array($openTaskScheduleDate, $weeklyDates)) ||
                            ($qualFrequency === '5' && !in_array($openTaskScheduleDate, $dailyDates)) ||
                            ($qualFrequency === '6' && !in_array($openTaskScheduleDate, $halfAnnuallyDates))) {
                            $qualResult = '2'; // Set to N/A
                        }
                        Class_db::getInstance()->db_insert('ppm_task_qual', array(
                            'ppm_task_qual_numb' => $checklistQual['checklist_qual_numb'],
                            'ppm_task_qual_desc' => $checklistQual['checklist_qual_desc'],
                            'frequency_id' => $qualFrequency,
                            'ppm_task_qual_result' => $qualResult,
                            'ppm_task_id' => $openPpmTaskId,
                            'checklist_qual_id' => $checklistQual['checklist_qual_id']
                        ));
                    }

                    // --- Delete and Re-insert Quantitative Task Snapshots ---
                    Class_db::getInstance()->db_delete('ppm_task_quan', array('ppm_task_id' => $openPpmTaskId));
                    foreach ($latestChecklistQuans as $checklistQuan) {
                        $quanResult = '';
                        $quanFrequency = $this->fn_general->clear_null($checklistQuan['frequency_id']);
                        // Re-apply N/A logic based on the task's schedule date and calculated date arrays
                        if (($quanFrequency === '1' && !in_array($openTaskScheduleDate, $yearlyDates)) ||
                            ($quanFrequency === '2' && !in_array($openTaskScheduleDate, $quarterlyDates)) ||
                            ($quanFrequency === '3' && !in_array($openTaskScheduleDate, $monthlyDates)) ||
                            ($quanFrequency === '4' && !in_array($openTaskScheduleDate, $weeklyDates)) ||
                            ($quanFrequency === '5' && !in_array($openTaskScheduleDate, $dailyDates)) ||
                            ($quanFrequency === '6' && !in_array($openTaskScheduleDate, $halfAnnuallyDates))) {
                            $quanResult = '2'; // Set to N/A
                        }
                        Class_db::getInstance()->db_insert('ppm_task_quan', array(
                            'ppm_task_quan_numb' => $checklistQuan['checklist_quan_numb'],
                            'ppm_task_quan_desc' => $checklistQuan['checklist_quan_desc'],
                            'frequency_id' => $quanFrequency,
                            'ppm_task_quan_unit' => $this->fn_general->clear_null($checklistQuan['checklist_quan_unit']),
                            'ppm_task_quan_set_values' => $this->fn_general->clear_null($checklistQuan['checklist_quan_set_values']),
                            'ppm_task_quan_result' => $quanResult,
                            'ppm_task_id' => $openPpmTaskId,
                            'checklist_quan_id' => $checklistQuan['checklist_quan_id']
                        ));
                    }
                } // End foreach openPpmTasks
            } // End foreach activePpmSchedules

        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}