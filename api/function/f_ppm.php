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
            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            }
            if (Class_db::getInstance()->db_count('ppm', array('asset_id'=>$assetId)) > 0) {
                //throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_SIMILAR_ASSET, 31);
            }

            $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $contractId = $asset['contract_id'];
            $contract = Class_db::getInstance()->db_select_single('cli_contract', array('contract_id'=>$contractId), null, 1);
            $contractDateStart = $contract['contract_date_start'];
            $contractDateEnd = $contract['contract_date_end'];
            $siteId = Class_db::getInstance()->db_select_col('cli_contract', array('contract_id'=>$contractId), 'site_id', null, 1);
            if ($asset['asset_type_id'] != $checklist['asset_type_id']) {
                //throw new Exception('[' . __LINE__ . '] - Checklist asset_type_id not sync with asset');
            }

            //$technicians = Class_db::getInstance()->db_select_colm('vw_technicians', array('ppm_group_user.ppm_group_id'=>$ppmGroupId, 'ppm_group.site_id'=>$siteId), 'user_id');
            //if (empty($technicians)) {
            //    throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_PPM_NO_TECHNICIAN, 31);
            //}
            //$technicianDays = Class_db::getInstance()->db_select('vw_technicians_ppm_monthly', array(), null, null, 0, array('technicians'=>implode(',',$technicians)));

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
            $ppmId = Class_db::getInstance()->db_insert('ppm', array('ppm_task_no'=>$checklist['checklist_document_no'], 'ppm_issue_no'=>$checklist['checklist_issue_no'], 'ppm_date_start'=>$ppmDateStart, 'asset_id'=>$assetId, 'checklist_id'=>$checklistId, 'asset_type_id'=>$asset['asset_type_id'],
                'contract_id'=>$contractId, 'ppm_created_by'=>$userId, 'ppm_group_id'=>$ppmGroupId));
            $currentMonth = array('year'=>'', 'month'=>'');
            //$technicianKpis = array();
            //foreach ($technicians as $technician) {
            //    array_push($technicianKpis, array('userId'=>$technician, 'total'=>0, 'totalPPM'=>0));
            //}
            //$lastTechnician = '';

            foreach($tempDays as $key => $dateStr){
                /*$curYear = substr($dateStr, 0, 4);
                $curMonth = strval(intval(substr($dateStr, 5, 2)));
                if ($currentMonth['year'] != $curYear || $currentMonth['month'] != $curMonth) {
                    $currentMonth = array('year'=>$curYear, 'month'=>$curMonth);
                    foreach ($technicianKpis as $key2 => $technicianKpi) {
                        $technicianKpis[$key2]['total'] = 0;
                    }
                    $kpiIntersects = array_intersect(array_keys(array_column($technicianDays, 'ppm_year'), $curYear), array_keys(array_column($technicianDays, 'ppm_month'), $curMonth));
                    foreach ($kpiIntersects as $kpiIntersect) {
                        $key = array_search($technicianDays[$kpiIntersect]['ppm_task_assigned_to'], array_column($technicianKpis, 'userId'));
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'kpiIntersect = ' . $kpiIntersect);
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'key = ' . $key);
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'total = ' . $technicianDays[$kpiIntersect]['total']);
                        $technicianKpis[$key]['total'] = intval($technicianDays[$kpiIntersect]['total']);
                    }
                    foreach ($technicianKpis as $key2 => $technicianKpi) {
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'TechnicianId = ' . $technicianKpi['userId'] . ', Total = ' . $technicianKpi['total'] . ', TotalPPM = ' . $technicianKpi['totalPPM']);
                    }
                }

                $columnKpi = array_column($technicianKpis, 'total');
                $lowestKpi = min($columnKpi);
                $lowestKpiIndex = array_search(min($columnKpi), $columnKpi, true);
                $technician = $technicianKpis[$lowestKpiIndex]['userId'];
                if ($technician == $lastTechnician) {
                    $lowestKpiPPM = 10000;
                    $lowestKpiPPMIndex = 1000;
                    foreach ($technicianKpis as $key2 => $technicianKpi) {
                        if ($technicianKpis[$key2]['total'] == $lowestKpi && $technicianKpis[$key2]['userId'] != $technician) {
                            if ($technicianKpis[$key2]['totalPPM'] < $lowestKpiPPM) {
                                $lowestKpiPPM = $technicianKpis[$key2]['totalPPM'];
                                $lowestKpiPPMIndex = $key2;
                            }
                        }
                    }
                    if ($lowestKpiPPMIndex != 1000) {
                        $lowestKpiIndex = $lowestKpiPPMIndex;
                        $technician = $technicianKpis[$lowestKpiIndex]['userId'];
                    }
                }

                $lastTechnician = $technician;
                $technicianKpis[$lowestKpiIndex]['total']++;
                $technicianKpis[$lowestKpiIndex]['totalPPM']++;
                //$technicianKey = $key%count($technicians);
                //$technician = $technicians[$technicianKey];
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Lowest TechnicianId = ' . $technician . ', Total = ' . $technicianKpis[$lowestKpiIndex]['total']);*/

                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $ppmTaskNo = 'P'.$siteCode.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).$runningNoStr;
                $runningNo++;

                $taskId = $this->fn_task->create_new_task('1', $userId, '5', '1', $ppmTaskNo, $dateStr);
                $transactionId = Class_db::getInstance()->db_select_col('wfl_task', array('task_id' => $taskId), 'transaction_id', null, 1);
                $checklistGuideline = !empty($checklist['checklist_guideline']) ? $checklist['checklist_guideline'] : '';
                $ppmTaskId = Class_db::getInstance()->db_insert('ppm_task', array('ppm_task_no'=>$ppmTaskNo, 'ppm_task_schedule_date'=>$dateStr, 'ppm_id'=>$ppmId, 'ppm_task_guideline'=>$checklistGuideline,
                    'ppm_task_status'=>'12', 'transaction_id'=>$transactionId));  // 'ppm_task_assigned_to'=>$technician

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

            return array('ppmId'=>$ppmId, 'ppmTaskNo'=>$checklist['checklist_document_no']);
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

            $ppmStartDate = '';
            $ppmDate = new DateTime($ppmDate);
            if ($frequency === '1') {   // yearly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                //$ppmDate->modify('-1 year');
                //$ppmDate->modify('+1 day');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === '2') {    // quarterly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                //$ppmDate->modify('+1 day');
                //$ppmDate->modify('-3 month');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === '3') {    // monthly
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === '4') {    // weekly
                $ppmDate->modify('-6 day');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === '5') {    // daily
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else if ($frequency === '6') {    // half-annually
                $ppmDate->modify('+1 day');
                $ppmDate->modify('-1 month');
                //$ppmDate->modify('+1 day');
                //$ppmDate->modify('-6 month');
                $ppmStartDate = $ppmDate->format("Y-m-d");
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter frequency invalid');
            }

            return $ppmStartDate;
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
                //if (Class_db::getInstance()->db_count('ppm_task_qual', array('ppm_task_qual_id'=>$ppmTaskQual['id'], 'ppm_task_qual_result'=>'2')) > 0) {
                //    throw new Exception('[' . __LINE__ . '] - Item ppm_task_qual_id = '.$ppmTaskQual['id'].' currently set as N/A');
                //}
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
                //if (Class_db::getInstance()->db_count('ppm_task_quan', array('ppm_task_quan_id'=>$ppmTaskQuan['id'], 'ppm_task_quan_result'=>'2')) > 0) {
                //    throw new Exception('[' . __LINE__ . '] - Item ppm_task_quan_id = '.$ppmTaskQuan['id'].' currently set as N/A');
                //}
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
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

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
    public function save_ppm_scan_start_time_m ($ppmTaskId, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($ppmTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmTaskId empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            Class_db::getInstance()->db_update('ppm_task', array('ppm_task_assigned_to'=>$userId, 'ppm_task_status'=>'13', 'ppm_task_time_start'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
            $transactionId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'transaction_id', null, 1);
            Class_db::getInstance()->db_update('wfl_transaction', array('user_id'=>$userId, 'transaction_status' => '13'), array('transaction_id' => $transactionId));
            Class_db::getInstance()->db_update('wfl_task', array('task_claimed_user'=>$userId, 'task_time_claimed'=>'Now()'), array('transaction_id'=>$transactionId, 'checkpoint_id'=>'1'));

            $ppmId = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'ppm_id', null, 1);
            $checklistId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmId), 'checklist_id', null, 1);
            $checklist = Class_db::getInstance()->db_select_single2('ppm_checklist', array('checklist_id'=>$checklistId));
            if (!empty($checklist)) {
                $updateArr = array(
                    'ppmTaskMinExecTime'=>$checklist['checklistMinExecTime'],
                    'ppmTaskMaxExecTime'=>$checklist['checklistMaxExecTime'],
                    'ppmTaskMaxAssistant'=>$checklist['checklistMaxAssistant']
                );
                Class_db::getInstance()->db_update('ppm_task', $this->fn_general->convertToMysqlArrAll($updateArr), array('ppm_task_id'=>$ppmTaskId));
            }

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
     * @param string $remark
     * @param string $nextUser
     * @return mixed
     * @throws Exception
     */
    public function process_ppm ($ppmTaskId, $checkpoint, $result, $uploadId, $userId, $remark='', $nextUser='') {
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
                Class_db::getInstance()->db_update('ppm_task', array('ppm_task_serviced_by'=>$userId, 'ppm_task_time_serviced'=>'Now()'), array('ppm_task_id'=>$ppmTaskId));
            } else if ($checkpoint === '2' && $result === '1') {
                $statusUpdate = '15';
                $taskName = 'pending verification';
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

            $emailTo = '';
            $comment = '';
            if (($taskName === 'pending verification' || $taskName === 'pending check') && !empty($nextUser)) {
                $emailTo = $nextUser;
            }
            else if ($taskName === 're-open') {
                $comment = !empty($remark) ? $remark : $task['task_remark'];
                $receiver = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id'=>$transactionId, 'role_id'=>'5', 'checkpoint_id'=>'1'), 'user_id', null, 1);
                $emailTo = $receiver;
            }
            else if ($taskName === 'completed') {
                $emailTo = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'ppm_task_assigned_to', null, 1);
            }

            $ppmTaskNo = Class_db::getInstance()->db_select_col('ppm_task', array('ppm_task_id'=>$ppmTaskId), 'ppm_task_no', null, 1);
            return array('taskId'=>$task['task_id'], 'emailTo'=>$emailTo, 'taskStatus'=>$taskName, 'ppmTaskNo'=>$ppmTaskNo, 'comment'=>$comment);
        }
        catch(Exception $ex) {
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
            $ppmGroupId = $params['ppmGroupId'];
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
            $ppmId = Class_db::getInstance()->db_insert('ppm', array('ppm_task_no'=>$checklist['checklistDocumentNo'], 'ppm_issue_no'=>$checklist['checklistIssueNo'], 'ppm_date_start'=>$ppmDateStart, 'asset_id'=>$assetId, 'checklist_id'=>$checklistId,
                'contract_id'=>$contractId, 'ppm_created_by'=>$userId, 'ppm_group_id'=>$ppmGroupId));

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
                $assistantStatus = ($checklist['checklistMaxAssistant'] === '' || $checklist['checklistMaxAssistant'] === '0') ? '19' :'18';
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

            return array('ppmId'=>$ppmId, 'ppmTaskNo'=>$checklist['checklistDocumentNo']);
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
}