<?php

class Class_kpi
{

    private $fn_general;

    function __construct()
    {
    }

    private function get_exception($codes, $function, $line, $msg)
    {
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
    public function __get($property)
    {
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
    public function __set($property, $value)
    {
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
    public function __isset($property)
    {
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
    public function __unset($property)
    {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $kpiId
     * @return mixed
     * @throws Exception
     */
    public function getKpi($kpiId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($kpiId));
            return Class_db::getInstance()->db_select_single2('kpi', array('kpi_id'=>$kpiId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $kpiPpnsId
     * @return mixed
     * @throws Exception
     */
    public function getKpiPpns($kpiPpnsId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($kpiPpnsId));
            return Class_db::getInstance()->db_select_single2('kpi_ppns', array('kpi_ppns_id'=>$kpiPpnsId), '', 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $kpiId
     * @param $category
     * @return mixed
     * @throws Exception
     */
    public function getKpiPpns2($kpiId, $category) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($kpiId, $category));
            return Class_db::getInstance()->db_select_single2('kpi_ppns', array('kpi_id'=>$kpiId, 'kpi_ppns_category'=>$category), '', 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getKpiPpnsList() {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return Class_db::getInstance()->db_select2('vw_kpi_ppns');
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getKpiInfo($siteId, $category, $version) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($siteId, $category, $version));
            return Class_db::getInstance()->db_select_single2('kpi_info', array('site_id'=>$siteId, 'kpi_info_version'=>$version, 'kpi_info_category'=>$category));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $kpiPpnsId
     * @return void
     * @throws Exception
     */
    public function calculateKpiPpnsCate6($kpiPpnsId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($kpiPpnsId));

            $kpiPpns = Class_db::getInstance()->db_select_single2('kpi_ppns', array('kpi_ppns_id'=>$kpiPpnsId, 'kpi_ppns_category'=>'6'), '', 1);
            $kpi = Class_db::getInstance()->db_select_single2('kpi', array('kpi_id'=>$kpiPpns['kpiId']), '', 1);
            $woTasks =  Class_db::getInstance()->db_select2('wo_task', array('site_id'=>$kpi['siteId'], 'YEAR(wo_task_time_created)'=>$kpi['kpiYear'], 'MONTH(wo_task_time_created)'=>$kpi['kpiMonth']));

            $totalComplaintEmergency = 0;
            $totalNonComplyEmergency = 0;
            $totalComplaintUrgent = 0;
            $totalNonComplyUrgent = 0;
            $totalComplaintNormal = 0;
            $totalNonComplyNormal = 0;
            $totalComplaint = 0;
            $totalNonComply = 0;
            $ncp = 0;
            foreach ($woTasks as $woTask) {
                $durationResponded = $this->fn_general->timeDiffMinute($woTask['woTaskTimeCreated'], ($woTask['woTaskIsWr'] === '1' ? $woTask['woTaskTimeWrChecked'] : $woTask['woTaskTimeAssigned']));
                if ($woTask['woTaskSeverity'] === '5') {
                    $totalComplaintEmergency++;
                    $totalComplaint++;
                } else if ($woTask['woTaskSeverity'] === '4') {
                    $totalComplaintUrgent++;
                    $totalComplaint++;
                } else if ($woTask['woTaskSeverity'] === '3') {
                    $totalComplaintNormal++;
                    $totalComplaint++;
                }
                if ($durationResponded !== '') {
                    if ($woTask['woTaskSeverity'] === '5' && $durationResponded > 15) {
                        $totalNonComplyEmergency++;
                        $totalNonComply++;
                    } else if ($woTask['woTaskSeverity'] === '4' && $durationResponded > 15) {
                        $totalNonComplyUrgent++;
                    } else if ($woTask['woTaskSeverity'] === '3' && $durationResponded > 30) {
                        $totalNonComplyNormal++;
                    }
                }
            }
            if ($totalComplaint > 0) {
                if ($totalComplaintUrgent > 0 && $totalNonComplyUrgent / $totalComplaintUrgent > 0.25) {
                    $totalNonComply += $totalNonComplyUrgent;
                }
                if ($totalComplaintNormal > 0 && $totalNonComplyNormal / $totalComplaintNormal > 0.3) {
                    $totalNonComply += $totalNonComplyNormal;
                }
                $ncp = $totalNonComply / $totalComplaint;
            }

            Class_db::getInstance()->db_update('kpi_ppns', array('kpi_ppns_param_1'=>$totalComplaintEmergency, 'kpi_ppns_param_2'=>$totalNonComplyEmergency, 'kpi_ppns_param_3'=>$totalComplaintUrgent,
                'kpi_ppns_param_4'=>$totalNonComplyUrgent, 'kpi_ppns_param_5'=>$totalComplaintNormal, 'kpi_ppns_param_6'=>$totalNonComplyNormal, 'kpi_ppns_param_7'=>$totalComplaint,
                'kpi_ppns_param_8'=>$totalNonComply, 'kpi_ppns_ncp'=>$ncp), array('kpi_ppns_id'=>$kpiPpnsId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $kpiPpnsId
     * @return void
     * @throws Exception
     */
    public function calculateKpiPpnsCate10($kpiPpnsId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($kpiPpnsId));

            $kpiPpns = Class_db::getInstance()->db_select_single2('kpi_ppns', array('kpi_ppns_id'=>$kpiPpnsId, 'kpi_ppns_category'=>'10'), '', 1);
            $kpi = Class_db::getInstance()->db_select_single2('kpi', array('kpi_id'=>$kpiPpns['kpiId']), '', 1);
            $woTasks =  Class_db::getInstance()->db_select2('wo_task', array('site_id'=>$kpi['siteId'], 'YEAR(wo_task_time_created)'=>$kpi['kpiYear'], 'MONTH(wo_task_time_created)'=>$kpi['kpiMonth']));

            $totalComplaintEmergency = 0;
            $totalNonComplyEmergency = 0;
            $totalComplaintUrgent = 0;
            $totalNonComplyUrgent = 0;
            $totalComplaintNormal = 0;
            $totalNonComplyNormal = 0;
            $totalComplaint = 0;
            $totalNonComply = 0;
            $ncp = 0;
            foreach ($woTasks as $woTask) {
                $durationMitigated = $this->fn_general->timeDiffHour($woTask['woTaskTimeCreated'], $woTask['woTaskTimeExecuted']);
                if ($woTask['woTaskSeverity'] === '5') {
                    $totalComplaintEmergency++;
                    $totalComplaint++;
                } else if ($woTask['woTaskSeverity'] === '4') {
                    $totalComplaintUrgent++;
                    $totalComplaint++;
                } else if ($woTask['woTaskSeverity'] === '3') {
                    $totalComplaintNormal++;
                    $totalComplaint++;
                }
                if ($durationMitigated !== '') {
                    if ($woTask['woTaskSeverity'] === '5' && $durationMitigated > 3) {
                        $totalNonComplyEmergency++;
                    } else if ($woTask['woTaskSeverity'] === '4' && $durationMitigated > 24) {
                        $totalNonComplyUrgent++;
                    } else if ($woTask['woTaskSeverity'] === '3' && $durationMitigated > 168) {
                        $totalNonComplyNormal++;
                    }
                }
            }
            if ($totalComplaint > 0) {
                $totalNonComply = $totalNonComplyEmergency + $totalNonComplyUrgent + $totalNonComplyNormal;
                if ($totalNonComply / $totalComplaint > 0.25) {
                    $ncp = $totalNonComply / $totalComplaint;
                }
            }

            Class_db::getInstance()->db_update('kpi_ppns', array('kpi_ppns_param_1'=>$totalComplaintEmergency, 'kpi_ppns_param_2'=>$totalNonComplyEmergency, 'kpi_ppns_param_3'=>$totalComplaintUrgent,
                'kpi_ppns_param_4'=>$totalNonComplyUrgent, 'kpi_ppns_param_5'=>$totalComplaintNormal, 'kpi_ppns_param_6'=>$totalNonComplyNormal, 'kpi_ppns_param_7'=>$totalComplaint,
                'kpi_ppns_param_8'=>$totalNonComply, 'kpi_ppns_ncp'=>$ncp), array('kpi_ppns_id'=>$kpiPpnsId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}