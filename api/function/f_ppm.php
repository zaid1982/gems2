<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';
require_once 'function/f_task.php';

class Class_ppm {

    private $fn_general;
    private $fn_task;

    function __construct() {
        $this->fn_general = new Class_general();
        $this->fn_task = new Class_task();
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
     * @return array
     * @throws Exception
     */
    public function get_ppm_from_asset_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_ppm_asset', array('asset_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
                $row_result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
                $row_result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
                $row_result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
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

            $technicians = Class_db::getInstance()->db_select_colm('vw_technicians', array('cli_contract_user.contract_id'=>$contractId, 'cli_contract_user.role_id'=>'5'), 'user_id');
            if (empty($technicians)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_TECHNICIAN, 31);
            }

            $isYearly = false;
            $isQuarterly = false;
            $isMonthly = false;
            $isWeekly = false;
            $isDaily = false;

            $checklistQuals = Class_db::getInstance()->db_select('ppm_checklist_qual', array('checklist_id'=>$checklistId, 'checklist_qual_status'=>'1'), 'checklist_qual_numb');
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
                }
            }

            $checklistQuans = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id'=>$checklistId, 'checklist_quan_status'=>'1'), 'checklist_quan_numb');
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
                }
            }

            $dailyDates = $this->get_dates_day($ppmDateCycle, $contractDateEnd);
            $weeklyDates = $this->get_dates_week($ppmDateCycle, $contractDateEnd);
            $monthlyDates = $this->get_dates_month($ppmDateCycle, $contractDateEnd);
            $quarterlyDates = $this->get_dates_quarter($ppmDateCycle, $contractDateEnd);
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
                if ($isYearly && in_array($dateStr, $yearlyDates) && !in_array($dateStr, $tempDays)) {
                    array_push($tempDays, $dateStr);
                }
            }

            if (count($tempDays) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_DATES, 31);
            }

            $runningNo = Class_db::getInstance()->db_select_col('ppm', array(), 'ppm_running_no', 'ppm_running_no DESC');
            if (empty($runningNo)) {
                $runningNo = 1;
            } else {
                $runningNo = intval($runningNo);
            }
            $runningNoTemp = 10000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            $ppmTaskNo = 'P'.date('ymd').$runningNoStr;
            $ppmId = Class_db::getInstance()->db_insert('ppm', array('ppm_task_no'=>$ppmTaskNo, 'ppm_date_cycle'=>$ppmDateCycle, 'asset_id'=>$assetId, 'checklist_id'=>$checklistId,
                'contract_id'=>$contractId, 'ppm_running_no'=>strval($runningNo), 'ppm_created_by'=>$userId));

            foreach($tempDays as $key => $dateStr){
                $ppmTaskIssueNo = $key + 1;
                $technicianKey = $key%count($technicians);
                $technician = $technicians[$technicianKey];
                $taskId = $this->fn_task->create_new_task('1', $technician, '5', '1', $ppmTaskNo.'/'.strval($ppmTaskIssueNo), $dateStr);
                $transactionId = Class_db::getInstance()->db_select_col('wfl_task', array('task_id' => $taskId), 'transaction_id', null, 1);
                $ppmTaskId = Class_db::getInstance()->db_insert('ppm_task', array('ppm_task_no'=>$ppmTaskNo, 'ppm_task_issue_no'=>strval($ppmTaskIssueNo), 'ppm_task_schedule_date'=>$dateStr, 'ppm_id'=>$ppmId, 'ppm_task_guideline'=>$checklist['checklist_guideline'],
                    'ppm_task_status'=>'12', 'transaction_id'=>$transactionId, 'ppm_task_assigned_to'=>$technician));

                foreach ($checklistQuals as $checklistQual) {
                    $qualResult = '';
                    $qualFrequency = $checklistQual['frequency_id'];
                    if ($qualFrequency === '1' && in_array($dateStr, $yearlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '2' && in_array($dateStr, $quarterlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '3' && in_array($dateStr, $monthlyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '4' && in_array($dateStr, $weeklyDates)) {
                        $qualResult = '2';
                    } else if ($qualFrequency === '5' && in_array($dateStr, $dailyDates)) {
                        $qualResult = '2';
                    }
                    Class_db::getInstance()->db_insert('ppm_task_qual', array('ppm_task_qual_numb'=>$checklistQual['checklist_qual_numb'], 'ppm_task_qual_desc'=>$checklistQual['checklist_qual_desc'], 'frequency_id'=>$qualFrequency,
                        'ppm_task_qual_result'=>$qualResult, 'ppm_task_id'=>$ppmTaskId, 'checklist_qual_id'=>$checklistQual['checklist_qual_id']));
                }

                foreach ($checklistQuans as $checklistQuan) {
                    $quanResult = '';
                    $quanFrequency = $this->fn_general->clear_null($checklistQuan['frequency_id']);
                    if ($quanFrequency === '1' && in_array($dateStr, $yearlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '2' && in_array($dateStr, $quarterlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '3' && in_array($dateStr, $monthlyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '4' && in_array($dateStr, $weeklyDates)) {
                        $quanResult = '2';
                    } else if ($quanFrequency === '5' && in_array($dateStr, $dailyDates)) {
                        $quanResult = '2';
                    }
                    Class_db::getInstance()->db_insert('ppm_task_quan', array('ppm_task_quan_numb'=>$checklistQuan['checklist_quan_numb'], 'ppm_task_quan_desc'=>$checklistQuan['checklist_quan_desc'], 'frequency_id'=>$quanFrequency,
                        'ppm_task_quan_unit'=>$this->fn_general->clear_null($checklistQuan['checklist_quan_unit']), 'ppm_task_quan_result'=>$quanResult, 'ppm_task_id'=>$ppmTaskId, 'checklist_quan_id'=>$checklistQuan['checklist_quan_id']));
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

                Class_db::getInstance()->db_update('wfl_transaction', array('transaction_date_due'=>$dateStr), array('transaction_id'=>$transactionId));
                // notification
            }

            return array('ppmId'=>$ppmId, 'ppmTaskNo'=>$ppmTaskNo);
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
}