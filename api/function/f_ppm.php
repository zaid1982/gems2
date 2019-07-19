<?php

class Class_ppm {

    private $fn_general;
    private $fn_task;
    private $fn_email;

    function __construct() {
        $this->fn_general = new Class_general();
        $this->fn_task = new Class_task();
        $this->fn_email = new Class_email();
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
     * @return array
     * @throws Exception
     */
    private function get_dates_day ($startDate, $endDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+1 day' );
            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                array_push($newDates, $date->format("Y-m-d"));
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
     * @return array
     * @throws Exception
     */
    private function get_dates_week ($startDate, $endDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+1 week' );
            $begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+1 day' );
            $interval = new DateInterval('P1W');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                array_push($newDates, $date->format("Y-m-d"));
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
     * @return array
     * @throws Exception
     */
    private function get_dates_month ($startDate, $endDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+1 month' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );
            $interval = new DateInterval('P1M');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                array_push($newDates, $xx->format("Y-m-d"));
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
     * @return array
     * @throws Exception
     */
    private function get_dates_quarter ($startDate, $endDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+3 month' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );
            $interval = new DateInterval('P3M');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                array_push($newDates, $xx->format("Y-m-d"));
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
     * @return array
     * @throws Exception
     */
    private function get_dates_halfAnnual ($startDate, $endDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+6 month' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );
            $interval = new DateInterval('P6M');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                array_push($newDates, $xx->format("Y-m-d"));
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
     * @return array
     * @throws Exception
     */
    private function get_dates_year ($startDate, $endDate) {
        try {
            $newDates = array();
            $begin = new DateTime( $startDate );
            $begin = $begin->modify( '+1 year' );
            //$begin = $begin->modify( '-1 day' );
            $end = new DateTime( $endDate );
            $end = $end->modify( '+2 day' );
            $interval = new DateInterval('P1Y');
            $dateRange = new DatePeriod($begin, $interval ,$end);
            foreach($dateRange as $date){
                $xx = $date->modify( '-1 day' );
                array_push($newDates, $xx->format("Y-m-d"));
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
                $row_result['ppmDateCycle'] = $this->fn_general->clear_null($dataLocal['ppm_date_cycle']);
                $row_result['checklistId'] = $this->fn_general->clear_null($dataLocal['checklist_id']);
                $row_result['ppmCreatedBy'] = $this->fn_general->clear_null($dataLocal['ppm_created_by']);
                $row_result['ppmTimeCreated'] = $this->fn_general->clear_null($dataLocal['ppm_time_created']);
                $row_result['ppmStatus'] = $this->fn_general->clear_null($dataLocal['ppm_status']);
                $row_result['assignedStatus'] = is_null($dataLocal['ppm_id']) ? '11' : '10';
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
                $row_result['ppmTaskScheduleDate'] = str_replace('-', '/', $dataLocal['ppm_task_schedule_date']);
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
     * @param $ppmDateCycle
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function assign_ppm_single ($assetId, $checklistId, $ppmDateCycle, $userId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (empty($ppmDateCycle)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmDateCycle empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (Class_db::getInstance()->db_count('ppm', array('asset_id'=>$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_SIMILAR_ASSET, 31);
            }

            $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $contractId = $asset['contract_id'];
            $contract = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id'=>$contractId), null, 1);
            $contractDateEnd = $contract['contract_date_end'];
            if ($asset['asset_type_id'] != $checklist['asset_type_id']) {
                throw new Exception('[' . __LINE__ . '] - Checklist asset_type_id not sync with asset');
            }

            $technicians = Class_db::getInstance()->db_select_colm('vw_technicians', array('cli_contract_user.contract_id'=>$contractId, 'cli_contract_user.location_code_id'=>$asset['location_code_id'],
                'cli_contract_user.asset_group_id'=>$asset['asset_group_id']), 'user_id');
            if (empty($technicians)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_TECHNICIAN, 31);
            }

            $isYearly = false;
            $isHalfAnnaully = false;
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
                        $isHalfAnnaully = true;
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
                        $isHalfAnnaully = true;
                        break;
                }
            }

            $dailyDates = $this->get_dates_day($ppmDateCycle, $contractDateEnd);
            $weeklyDates = $this->get_dates_week($ppmDateCycle, $contractDateEnd);
            $monthlyDates = $this->get_dates_month($ppmDateCycle, $contractDateEnd);
            $quarterlyDates = $this->get_dates_quarter($ppmDateCycle, $contractDateEnd);
            $halfAnnuallyDates = $this->get_dates_halfAnnual($ppmDateCycle, $contractDateEnd);
            $yearlyDates = $this->get_dates_year($ppmDateCycle, $contractDateEnd);

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
                if ($isHalfAnnaully && in_array($dateStr, $halfAnnuallyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
                if ($isYearly && in_array($dateStr, $yearlyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
            }

            if (count($tempDays) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_DATES, 31);
            }

            $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);
            $siteCode = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_code', null, 1);
            $runningNo = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_running_no', null, 1);
            $runningNo = intval($runningNo);
            $ppmId = Class_db::getInstance()->db_insert('ppm', array('ppm_task_no'=>$checklist['checklist_document_no'], 'ppm_issue_no'=>$checklist['checklist_issue_no'], 'ppm_date_cycle'=>$ppmDateCycle, 'asset_id'=>$assetId, 'checklist_id'=>$checklistId,
                'contract_id'=>$contractId, 'ppm_created_by'=>$userId));

            foreach($tempDays as $key => $dateStr){
                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $ppmTaskNo = 'P'.$siteCode.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).$runningNoStr;
                $runningNo++;
                $ppmTaskIssueNo = $key + 1;
                $technicianKey = $key%count($technicians);
                $technician = $technicians[$technicianKey];
                $taskId = $this->fn_task->create_new_task('1', $technician, '5', '1', $ppmTaskNo, $dateStr);
                $transactionId = Class_db::getInstance()->db_select_col('wfl_task', array('task_id' => $taskId), 'transaction_id', null, 1);
                $ppmTaskId = Class_db::getInstance()->db_insert('ppm_task', array('ppm_task_no'=>$ppmTaskNo, 'ppm_task_schedule_date'=>$dateStr, 'ppm_id'=>$ppmId, 'ppm_task_guideline'=>$checklist['checklist_guideline'],
                    'ppm_task_status'=>'12', 'transaction_id'=>$transactionId, 'ppm_task_assigned_to'=>$technician));

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
                if ($isYearly && in_array($dateStr, $yearlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'1'));
                }
                if ($isQuarterly && in_array($dateStr, $quarterlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'2'));
                }
                if ($isMonthly && in_array($dateStr, $monthlyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'3'));
                }
                if ($isWeekly && in_array($dateStr, $weeklyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'4'));
                }
                if ($isDaily && in_array($dateStr, $dailyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'5'));
                }
                if ($isHalfAnnaully && in_array($dateStr, $halfAnnuallyDates)) {
                    Class_db::getInstance()->db_insert('ppm_task_frequency', array('ppm_task_id'=>$ppmTaskId, 'frequency_id'=>'6'));
                }

                Class_db::getInstance()->db_update('wfl_task', array('task_status'=>'8'), array('transaction_id'=>$transactionId));
                Class_db::getInstance()->db_update('wfl_transaction', array('transaction_date_due'=>$dateStr, 'transaction_status'=>'12'), array('transaction_id'=>$transactionId));
                // notification
            }
            Class_db::getInstance()->db_update('cli_site', array('site_running_no'=>strval($runningNo)), array('site_id'=>$siteId));

            return array('ppmId'=>$ppmId, 'ppmTaskNo'=>$checklist['checklist_document_no']);
        } catch (Exception $ex) {
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
                $restFilter = 'AND (ast_asset.asset_no LIKE \'%'.$searchTxt.'%\' OR wfl_transaction.transaction_no LIKE \'%'.$searchTxt.'%\' OR ast_asset_type.asset_type_name LIKE \'%'.$searchTxt.'%\' OR cli_site.site_name LIKE \'%'.$searchTxt.'%\' '.
                    'OR sys_user.user_first_name LIKE \'%'.$searchTxt.'%\')';
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_task_ppm_pending', array(), 'task_date_due', null, null, array('user_id'=>$userId, 'rest_filter'=>$restFilter));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['taskId'] = $dataLocal['task_id'];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['transactionNo'] = $dataLocal['transaction_no'];
                $row_result['assetNo'] = $dataLocal['asset_no'];
                $row_result['siteName'] = $dataLocal['site_name'];
                $row_result['assetTypeName'] = $dataLocal['asset_type_name'];
                $row_result['statusDesc'] = $dataLocal['status_desc'];
                $row_result['frequency'] = explode(',', $dataLocal['frequency']);
                $row_result['technician'] = $dataLocal['user_first_name'];
                $row_result['taskDateDue'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_schedule_date']);
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $date
     * @param string $assetNo
     * @param string $searchTxt
     * @return array
     * @throws Exception
     */
    public function get_ppm_all_task_m ($date='', $assetNo='', $searchTxt='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            $restFilter = '';
            if (!empty($date)) {
                $restFilter = 'AND task_date_due = \''.$date.'\'';
            }
            if (!empty($assetNo)) {
                $restFilter = 'AND ast_asset.asset_no = \''.$assetNo.'\'';
            }
            if (!empty($searchTxt)) {
                $restFilter = 'AND (ast_asset.asset_no LIKE \'%'.$searchTxt.'%\' OR wfl_transaction.transaction_no LIKE \'%'.$searchTxt.'%\' OR ast_asset_type.asset_type_name LIKE \'%'.$searchTxt.'%\' OR cli_site.site_name LIKE \'%'.$searchTxt.'%\' '.
                    'OR sys_user.user_first_name LIKE \'%'.$searchTxt.'%\')';
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_task_ppm_all', array(), null, null, null, array('rest_filter'=>$restFilter));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['taskId'] = $dataLocal['task_id'];
                $row_result['ppmTaskId'] = $dataLocal['ppm_task_id'];
                $row_result['transactionNo'] = $dataLocal['transaction_no'];
                $row_result['assetNo'] = $dataLocal['asset_no'];
                $row_result['siteName'] = $dataLocal['site_name'];
                $row_result['assetTypeName'] = $dataLocal['asset_type_name'];
                $row_result['statusDesc'] = $dataLocal['status_desc'];
                $row_result['frequency'] = explode(',', $dataLocal['frequency']);
                $row_result['technician'] = $dataLocal['user_first_name'];
                $row_result['taskDateDue'] = $this->fn_general->convertDateToDisplay($dataLocal['ppm_task_schedule_date']);
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
            if (empty($month)) {
                throw new Exception('[' . __LINE__ . '] - Parameter month empty');
            }
            if (empty($year)) {
                throw new Exception('[' . __LINE__ . '] - Parameter year empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_task_calendar_count_all', array(), 'task_date_due', null, null, array('user_id'=>$userId, 'month'=>$month, 'year'=>$year));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['date'] = $dataLocal['task_date_due'];
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
            $result['ppmTaskTimeStart'] = str_replace('-', '/', $dataLocal['ppm_task_time_start']);
            $result['ppmTaskTimeServiced'] = str_replace('-', '/', $dataLocal['ppm_task_time_serviced']);

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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

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
    public function save_qualitative_tasks_m ($ppmTaskId, $ppmTaskQuals) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (!is_array($ppmTaskQuals)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals is not array');
            }
            if (empty($ppmTaskQuals)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals empty');
            }

            foreach ($ppmTaskQuals as $ppmTaskQual) {
                if (!array_key_exists('id', $ppmTaskQual) || empty($ppmTaskQual['id'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals[id] empty');
                }
                if (!array_key_exists('result', $ppmTaskQual)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals[result] not exist');
                }
                if (!array_key_exists('remark', $ppmTaskQual)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuals[remark] not exist');
                }
                if (Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_qual_id'=>$ppmTaskQual['id'], 'ppm_task_qual_result'=>'2')) > 0) {
                    throw new Exception('[' . __LINE__ . '] - Item ppm_task_qual_id = '.$ppmTaskQual['id'].' currently set as N/A');
                }
                Class_db::getInstance()->db_update('ppm_task_qual', array('ppm_task_qual_result'=>$ppmTaskQual['result'], 'ppm_task_qual_remark'=>$ppmTaskQual['remark']), array('ppm_task_qual_id'=>$ppmTaskQual['id']));
            }

            $totalNull = Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_qual_result'=>'is NULL'));
            $sectionStatus = $totalNull > '0' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'C'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $ppmTaskQuans
     * @throws Exception
     */
    public function save_quantitative_tasks_m ($ppmTaskId, $ppmTaskQuans) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (!is_array($ppmTaskQuans)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans is not array');
            }
            if (empty($ppmTaskQuans)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans empty');
            }

            foreach ($ppmTaskQuans as $ppmTaskQuan) {
                if (!array_key_exists('id', $ppmTaskQuan) || empty($ppmTaskQuan['id'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskQuans[id] empty');
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
                if (Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_quan_id'=>$ppmTaskQuan['id'], 'ppm_task_quan_result'=>'2')) > 0) {
                    throw new Exception('[' . __LINE__ . '] - Item ppm_task_quan_id = '.$ppmTaskQuan['id'].' currently set as N/A');
                }
                Class_db::getInstance()->db_update('ppm_task_quan', array('ppm_task_quan_measured_values'=>$ppmTaskQuan['measuredValues'], 'ppm_task_quan_limit'=>$ppmTaskQuan['limit'],
                    'ppm_task_quan_result'=>$ppmTaskQuan['result'], 'ppm_task_quan_remark'=>$ppmTaskQuan['remark']), array('ppm_task_quan_id'=>$ppmTaskQuan['id']));
            }

            $totalNull = Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_quan_result'=>'is NULL'));
            $sectionStatus = $totalNull > '0' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'D'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param string $checked
     * @throws Exception
     */
    public function save_ppm_check_parts_m ($ppmTaskId, $checked='0') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if ($checked == '') {
                throw new Exception('[' . __LINE__ . '] - Parameter checked empty');
            }

            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_is_parts'=>$checked), array('ppm_task_id'=>$ppmTaskId));

            $sectionStatus = '19';
            if ($checked === '1') {
                $totalFile = Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_id'=>$ppmTaskId));
                $sectionStatus = $totalFile == '0' ? '18' : '19';
            }
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'E'));
        }
        catch(Exception $ex) {
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
    public function add_ppm_parts_m ($ppmTaskId, $ppmTaskPartsDesc) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($ppmTaskPartsDesc)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskPartsDesc empty');
            }
            if (Class_db::getInstance()->db_count('ppm_task_parts', array('ppm_task_parts_desc'=>$ppmTaskPartsDesc, 'ppm_task_id'=>$ppmTaskId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_PARTS_EXIST, 31);
            }

            $ppmTaskPartsId = Class_db::getInstance()->db_insert('ppm_task_parts', array('ppm_task_parts_desc'=>$ppmTaskPartsDesc, 'ppm_task_id'=>$ppmTaskId));
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>'19'), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'E'));

            $dataLocal = Class_db::getInstance()->db_select_single('ppm_task_parts', array('ppm_task_parts_id'=>$ppmTaskPartsId), null, 1);
            $result['ppmTaskPartsId'] = $dataLocal['ppm_task_parts_id'];
            $result['ppmTaskId'] = $dataLocal['ppm_task_id'];
            $result['ppmTaskPartsDesc'] = $this->fn_general->clear_null($dataLocal['ppm_task_parts_desc']);

            return $result;
        }
        catch(Exception $ex) {
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($uploadId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_task', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_is_additional_report'=>'1')) == 0) {
                throw new Exception('[' . __LINE__ . '] - Additional Report check not saved');
            }

            Class_db::getInstance()->db_insert('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'3', 'upload_id'=>$uploadId));
            $totalFile = Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'3'));
            $sectionStatus = $totalFile == '0' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'F'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param string $checked
     * @throws Exception
     */
    public function save_ppm_check_additional_report_m ($ppmTaskId, $checked='0') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if ($checked == '') {
                throw new Exception('[' . __LINE__ . '] - Parameter checked empty');
            }

            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_is_additional_report'=>$checked), array('ppm_task_id'=>$ppmTaskId));

            $sectionStatus = '19';
            if ($checked === '1') {
                $totalFile = Class_db::getInstance()->db_count('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'3'));
                $sectionStatus = $totalFile == '0' ? '18' : '19';
            }
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'F'));
        }
        catch(Exception $ex) {
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
    public function save_ppm_remark_m ($ppmTaskId, $ppmTaskRemark='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            $sectionStatus = $ppmTaskRemark === '' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_remark'=>$ppmTaskRemark), array('ppm_task_id'=>$ppmTaskId));
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'G'));
        }
        catch(Exception $ex) {
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
     * @throws Exception
     */
    public function save_ppm_maintenance_image_m ($ppmTaskId, $uploadId, $uploadType, $longitude='', $latitude='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
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

            Class_db::getInstance()->db_insert('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>$uploadType, 'upload_id'=>$uploadId,
                'ppm_task_upload_longitude'=>$longitude, 'ppm_task_upload_latitude'=>$latitude));

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
        }
        catch(Exception $ex) {
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
     * @param $ppmTaskId
     * @throws Exception
     */
    public function save_ppm_scan_start_time_m ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }

            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status'=>'13', 'ppm_task_time_start'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
            $transactionId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'transaction_id', null, 1);
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status' => '13'), array('transaction_id' => $transactionId));

            $totalNull = Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_qual_result'=>'is NULL'));
            $sectionStatus = $totalNull > '0' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'C'));

            $totalNull = Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_quan_result'=>'is NULL'));
            $sectionStatus = $totalNull > '0' ? '18' : '19';
            Class_db::getInstance()->db_update('ppm_task_section', array('ppm_task_section_status'=>$sectionStatus), array('ppm_task_id'=>$ppmTaskId, 'ppm_task_section_name'=>'D'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmTaskId
     * @param $ppmTaskUploads
     * @throws Exception
     */
    public function save_image_desc_m ($ppmTaskId, $ppmTaskUploads) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (!is_array($ppmTaskUploads)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUploads is not array');
            }
            if (empty($ppmTaskUploads)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUploads empty');
            }

            foreach ($ppmTaskUploads as $ppmTaskUpload) {
                if (!array_key_exists('ppmTaskUploadId', $ppmTaskUpload) || empty($ppmTaskUpload['ppmTaskUploadId'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUpload[ppmTaskUploadId] empty');
                }
                if (!array_key_exists('ppmTaskUploadDesc', $ppmTaskUpload)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskUpload[ppmTaskUploadDesc] not exist');
                }
                Class_db::getInstance()->db_update('ppm_task_upload', array('ppm_task_upload_desc'=>$ppmTaskUpload['ppmTaskUploadDesc']), array('ppm_task_upload_id'=>$ppmTaskUpload['ppmTaskUploadId']));
            }
        }
        catch(Exception $ex) {
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
     * @return mixed
     * @throws Exception
     */
    public function process_ppm ($ppmTaskId, $checkpoint, $result, $uploadId, $userId, $remark='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($checkpoint)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpoint empty');
            }
            if (empty($result)) {
                throw new Exception('[' . __LINE__ . '] - Parameter result empty');
            }
            //if (empty($uploadId)) {
            //    throw new Exception('[' . __LINE__ . '] - Parameter uploadId empty');
            //}
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $transactionId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'transaction_id', null, 1);
            $task = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id'=>$transactionId, 'task_current'=>'1'), null, 1);

            if ($task['checkpoint_id'] !== $checkpoint) {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpoint invalid');
            }

            $statusUpdate = '';
            $taskName = '';
            $reportTo = '';
            if ($checkpoint === '1') {
                $statusUpdate = '14';
                $taskName = 'pending check';
                $reportTo = Class_db::getInstance()->db_select_col('wfl_user_report', array('user_id'=>$userId, 'role_id'=>'5', 'report_role'=>'3'), 'report_to');
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_serviced_by'=>$userId, 'ppm_task_time_serviced'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
            } else if ($checkpoint === '2' && $result === '1') {
                $statusUpdate = '15';
                $taskName = 'pending verification';
                $reportTo = Class_db::getInstance()->db_select_col('wfl_user_report', array('user_id'=>$userId, 'role_id'=>'3', 'report_role'=>'4'), 'report_to');
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_checked_by'=>$userId, 'ppm_task_time_checked'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
            } else if ($checkpoint === '2' && $result === '2') {
                $statusUpdate = '21';
                $taskName = 're-open';
            } else if ($checkpoint === '3' && $result === '1') {
                $statusUpdate = '16';
                $taskName = 'completed';
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_verified_by'=>$userId, 'ppm_task_time_verified'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
            } else if ($checkpoint === '3' && $result === '2') {
                $statusUpdate = '21';
                $taskName = 're-open';
            }

            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_status'=>$statusUpdate), array('ppm_task_id'=>$ppmTaskId));
            if (!empty($uploadId)) {
                Class_db::getInstance()->db_insert('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>intval($checkpoint)+3, 'upload_id'=>$uploadId));
            }
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>$statusUpdate), array('transaction_id'=>$transactionId));

            if ($statusUpdate === '21') {
                $ppmUploads = Class_db::getInstance()->db_select('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'(4,5,6)'));
                if (!empty($ppmUploads)) {
                    foreach ($ppmUploads as $ppmUpload) {
                        Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$ppmUpload['upload_id']));
                    }
                    Class_db::getInstance()->db_delete('ppm_task_upload', array('ppm_task_id'=>$ppmTaskId, 'ppm_task_upload_type'=>'(4,5,6)'));
                }
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_serviced_by'=>'', 'ppm_task_checked_by'=>'', 'ppm_task_verified_by'=>'', 'ppm_task_time_serviced'=>'', 'ppm_task_time_checked'=>'', 'ppm_task_time_verified'=>''), array('ppm_task_id'=>$ppmTaskId));
            }
            if (($taskName === 'pending verification' || $taskName === 'pending check') && !empty($reportTo)) {
                $sysUser = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$reportTo), null, 1);
                $sysUserProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$reportTo, 'user_profile_status'=>'1'), null, 1);
                $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
                $content = '<p>Dear '.$sysUser['user_first_name'].',</p>
                    <p>You have received 1 new PPM '.$taskName.' task with task no = '.$ppmTask['ppm_task_no'].'.</p>
                    <p>Please open the mobile apps and proceed with the task.</p>
                    <br /><br />
                    <p><i>Note: This is an automail from GEMS 2.0 System. Please do not reply to this email.</i></p>';
                $this->fn_email->send_email_express($sysUserProfile['user_email'], 'GEMS 2.0 - PPM Task Received', $content);
            }
            else if ($taskName === 're-open') {
                $comment = !empty($remark) ? $remark : $task['task_remark'];
                //$taskPrevious = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id'=>$transactionId, 'task_current'=>'2'), 'task_id DESC', 1);
                $receiver = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id'=>$transactionId, 'role_id'=>'5', 'checkpoint_id'=>'1'), 'user_id', null, 1);
                $sysUser = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$receiver), null, 1);
                $sysUserProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$receiver, 'user_profile_status'=>'1'), null, 1);
                $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
                $content = '<p>Dear '.$sysUser['user_first_name'].',</p>
                    <p>A PPM '.$taskName.' task with task no = '.$ppmTask['ppm_task_no'].' was returned to you for further action.</p>
                    <p>Comment : '.$comment.'</p>
                    <p>Please open the mobile apps and proceed with the task.</p>
                    <br /><br />
                    <p><i>Note: This is an automail from GEMS 2.0 System. Please do not reply to this email.</i></p>';
                $this->fn_email->send_email_express($sysUserProfile['user_email'], 'GEMS 2.0 - Re-open PPM Task', $content);
            }
            else if ($taskName === 'completed') {
                $ppmTask = Class_db::getInstance()->db_select_single('ppm_task', array('ppm_task_id'=>$ppmTaskId), null, 1);
                $sysUser = Class_db::getInstance()->db_select_single('sys_user', array('user_id'=>$ppmTask['ppm_task_assigned_to']), null, 1);
                $sysUserProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$ppmTask['ppm_task_assigned_to'], 'user_profile_status'=>'1'), null, 1);
                $content = '<p>Dear '.$sysUser['user_first_name'].',</p>
                    <p>A PPM '.$taskName.' task with task no = '.$ppmTask['ppm_task_no'].' has been verified and completed.</p>
                    <br /><br />
                    <p><i>Note: This is an automail from GEMS 2.0 System. Please do not reply to this email.</i></p>';
                $this->fn_email->send_email_express($sysUserProfile['user_email'], 'GEMS 2.0 - Closed PPM Task', $content);
            }

            return $task['task_id'];
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $month
     * @param string $year
     * @param string $clientId
     * @param string $contractId
     * @return mixed
     * @throws Exception
     */
    public function get_total_ppm_task ($month='', $year='', $clientId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $arrWhere = array();
            if (!empty($clientId) && empty($contractId)) {
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>'('.$siteIdStr.')', 'contract_status'=>'1'), 'contract_id');
                    if (!empty($contractIds)) {
                        $contractId = '('.implode(',',$contractIds).')';
                    }
                }
                $arrWhere['contract_id'] = $contractId;
            }
            if (!empty($contractId)) {
                $arrWhere['contract_id'] = $contractId;
            }
            if (intval($month) >= 0 && intval($month) <= 12 && intval($year) >= 2019) {
                $arrWhere['MONTH(ppm_task_schedule_date)'] = intval($month)+1;
                $arrWhere['YEAR(ppm_task_schedule_date)'] = $year;
            }
            return Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $month
     * @param string $year
     * @param string $clientId
     * @param string $contractId
     * @return mixed
     * @throws Exception
     */
    public function get_total_ppm_late ($month='', $year='', $clientId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $arrWhere = array('ppm_task_status'=>'(12,13)');
            if (!empty($clientId) && empty($contractId)) {
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>'('.$siteIdStr.')', 'contract_status'=>'1'), 'contract_id');
                    if (!empty($contractIds)) {
                        $contractId = '('.implode(',',$contractIds).')';
                    }
                }
                $arrWhere['contract_id'] = $contractId;
            }
            if (!empty($contractId)) {
                $arrWhere['contract_id'] = $contractId;
            }
            if (intval($month) >= 0 && intval($month) <= 12 && intval($year) >= 2019) {
                $arrWhere['MONTH(ppm_task_schedule_date)'] = intval($month)+1;
                $arrWhere['YEAR(ppm_task_schedule_date)'] = $year;
            }
            return Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    public function get_perc_ppm_done ($month='', $year='', $clientId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $arrWhere = array();
            if (!empty($clientId) && empty($contractId)) {
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>'('.$siteIdStr.')', 'contract_status'=>'1'), 'contract_id');
                    if (!empty($contractIds)) {
                        $contractId = '('.implode(',',$contractIds).')';
                    }
                }
                $arrWhere['contract_id'] = $contractId;
            }
            if (!empty($contractId)) {
                $arrWhere['contract_id'] = $contractId;
            }
            if (intval($month) >= 0 && intval($month) <= 12 && intval($year) >= 2019) {
                $arrWhere['MONTH(ppm_task_schedule_date)'] = intval($month)+1;
                $arrWhere['YEAR(ppm_task_schedule_date)'] = $year;
            }
            $total = Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
            if ($total == '0') {
                return 0;
            }
            $arrWhere = array('ppm_task_status'=>'16');
            $done = Class_db::getInstance()->db_select_col('vw_count_ppm_task', $arrWhere, 'total');
            return intval($done)/intval($total)*100;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}