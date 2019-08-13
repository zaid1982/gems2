<?php

class Class_wo {

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
     * @param $userId
     * @param $groupId
     * @return string
     * @throws Exception
     */
    public function create_wo_no ($userId, $groupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            $curDates = new DateTime();
            $siteCode = Class_db::getInstance()->db_select_col('cli_site', array('group_id'=>$groupId), 'site_code', null, 1);
            $runningNo = Class_db::getInstance()->db_select_col('cli_site', array('group_id'=>$groupId), 'site_running_no_wo', null, 1);
            $runningNo = intval($runningNo);
            $runningNoTemp = 100000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            $runningNo++;
            Class_db::getInstance()->db_update('cli_site', array('site_running_no_wo'=>strval($runningNo)), array('group_id'=>$groupId));

            return 'W'.$siteCode.$curDates->format("ymd").$runningNoStr;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $taskId
     * @param string $woTaskNo
     * @param string $woTaskLocation
     * @param string $woTaskComplaint
     * @param array $complaintImageUploads
     * @param string $signatureId
     * @param string $woTaskLongitude
     * @param string $woTaskLatitude
     * @throws Exception
     */
    public function process_new_complaint ($taskId, $woTaskNo='', $woTaskLocation='', $woTaskComplaint='', $complaintImageUploads=array(), $signatureId='', $woTaskLongitude='', $woTaskLatitude='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);
            $constant = $this->constant;

            if (empty($taskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter taskId empty');
            }
            if (empty($woTaskNo)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskNo empty');
            }
            if (empty($woTaskLocation)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_WO_LOCATION_EMPTY, 31);
            }
            if (empty($woTaskComplaint)) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_WO_DESCRIPTION_EMPTY, 31);
            }
            if (empty($complaintImageUploads)) {
                throw new Exception('[' . __LINE__ . '] - Array complaintImageUploads empty');
            }
            if (empty($signatureId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter signatureId empty');
            }

            $task = Class_db::getInstance()->db_select_single('wfl_task', array('task_id'=>$taskId), null, 1);
            $siteId = Class_db::getInstance()->db_select_col('cli_site', array('group_id'=>$task['group_id']), 'site_id', null, 1);
            $woTaskId = Class_db::getInstance()->db_insert('wo_task', array('transaction_id'=>$task['transaction_id'], 'wo_task_no'=>$woTaskNo, 'wo_task_type'=>'1', 'wo_task_location'=>$woTaskLocation, 'wo_task_complaint'=>$woTaskComplaint,
                'wo_task_longitude'=>$woTaskLongitude, 'wo_task_latitude'=>$woTaskLatitude, 'site_id'=>$siteId, 'wo_task_created_by'=>$task['task_created_user'], 'wo_task_status'=>'24'));
            Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$woTaskId, 'wo_task_upload_type'=>'5', 'upload_id'=>$signatureId));
            foreach ($complaintImageUploads as $complaintImageUpload) {
                if (!array_key_exists('uploadId', $complaintImageUpload)) {
                    throw new Exception('[' . __LINE__ . '] - Index uploadId not exist in complaintImageUpload');
                }
                if (!array_key_exists('description', $complaintImageUpload)) {
                    throw new Exception('[' . __LINE__ . '] - Index description not exist in complaintImageUpload');
                }
                if (!array_key_exists('longitude', $complaintImageUpload)) {
                    throw new Exception('[' . __LINE__ . '] - Index longitude not exist in complaintImageUpload');
                }
                if (!array_key_exists('latitude', $complaintImageUpload)) {
                    throw new Exception('[' . __LINE__ . '] - Index latitude not exist in complaintImageUpload');
                }
                Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$woTaskId, 'wo_task_upload_type'=>'1', 'upload_id'=>$complaintImageUpload['uploadId'], 'wo_task_upload_desc'=>$complaintImageUpload['description'],
                    'wo_task_upload_longitude'=>$complaintImageUpload['longitude'], 'wo_task_upload_latitude'=>$complaintImageUpload['latitude']));
            }
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'24'), array('transaction_id'=>$task['transaction_id']));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @param $searchText
     * @return array
     * @throws Exception
     */
    public function get_submitted_wo_m ($userId, $searchText='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $statusArr = $this->fn_general->getRefStatus ();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_submitted_m', array(), 'wo_task_time_created', '100', null, array('user_id'=>$userId, 'search_text'=>$searchText));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_no'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['reportedBy'] = $dataLocal['user_first_name'];
                $row_result['woTaskTimeCreated'] = $this->fn_general->convertDateToDisplay($dataLocal['wo_task_time_created']);
                $row_result['woTaskStatus'] = $statusArr[$dataLocal['wo_task_status']];
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
     * @param string $searchText
     * @return array
     * @throws Exception
     */
    public function get_pending_task_m ($userId, $searchText='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $statusArr = $this->fn_general->getRefStatus ();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_pending_m', array(), 'wfl_task.task_id', '100', null, array('user_id'=>$userId, 'search_text'=>$searchText));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_no'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['reportedBy'] = $dataLocal['user_first_name'];
                $row_result['woTaskTimeCreated'] = $this->fn_general->convertDateToDisplay($dataLocal['wo_task_time_created']);
                $row_result['woTaskStatus'] = $statusArr[$dataLocal['wo_task_status']];
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}