<?php

class Class_wo {

    private $constant;
    private $fn_general;
    private $userId;
    private $woTaskId;

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
     * @return mixed
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

            return $woTaskId;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $searchText
     * @return array
     * @throws Exception
     */
    public function get_submitted_wo_m ($searchText='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $statusArr = $this->fn_general->getRefStatus ();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_submitted_m', array(), 'wo_task_time_created', '100', null, array('user_id'=>$this->userId, 'search_text'=>$searchText));
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
     * @param string $searchText
     * @return array
     * @throws Exception
     */
    public function get_pending_task_m ($searchText='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $statusArr = $this->fn_general->getRefStatus ();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_pending_m', array(), 'wfl_task.task_id', '100', null, array('user_id'=>$this->userId, 'search_text'=>$searchText));
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
     * @return array
     * @throws Exception
     */
    public function get_section_status_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $arr_status = $this->fn_general->getRefStatus();
            $result = array(
                array('sectionName'=>'A', 'sectionStatus'=>$arr_status[17]),
                array('sectionName'=>'B', 'sectionStatus'=>$arr_status[18]),
                array('sectionName'=>'C', 'sectionStatus'=>$arr_status[18])
            );

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if (!empty($woTask['wo_task_repair_desc'])) {
                $result[1]['sectionStatus'] = $arr_status[19];
            }

            $imgBefore = false;
            $imgDuring = false;
            $imfAfter = false;
            $woTaskUploads = Class_db::getInstance()->db_select('wo_task_upload', array('wo_task_id'=>$this->woTaskId));
            foreach ($woTaskUploads as $woTaskUpload) {
                $uploadType = $woTaskUpload['wo_task_upload_type'];
                if ($uploadType === '2') {
                    $imgBefore = true;
                } else if ($uploadType === '3') {
                    $imgDuring = true;
                } else if ($uploadType === '4') {
                    $imfAfter = true;
                }
            }
            if ($imgBefore && $imgDuring && $imfAfter) {
                $result[2]['sectionStatus'] = $arr_status[19];
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function save_respond_time_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            Class_db::getInstance()->db_update('wo_task', array('wo_task_time_responded'=>'Now()'), array('wo_task_id'=>$this->woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_complaint_details_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $result = array();
            $arrStatus = $this->fn_general->getRefStatus();
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrSiteName = $this->fn_general->getSiteName();
            $arrWoTaskType = array('', 'External Complaint', 'Internal Complaint');

            $dataLocal = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $createdBy = $dataLocal['wo_task_created_by'];
            $result['woTaskId'] = $dataLocal['wo_task_id'];
            $result['woTaskReportedBy'] = $arrUserFullName[intval($createdBy)];
            $result['woTaskTimeResponded'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['wo_task_time_responded']));
            $result['woTaskCategory'] = $arrWoTaskType[intval($dataLocal['wo_task_type'])];
            $result['woTaskClient'] = !empty($dataLocal['site_id']) ? $arrSiteName[intval($dataLocal['site_id'])] : '';
            $result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
            $result['woTaskComplaint'] = $this->fn_general->clear_null($dataLocal['wo_task_complaint']);
            $result['woTaskStatus'] = $arrStatus[intval($dataLocal['wo_task_status'])];

            $userProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$createdBy, 'user_profile_status'=>'1'), null, 1);
            $result['woTaskPhoneNo'] = $this->fn_general->clear_null($userProfile['user_contact_no']);
            $result['woTaskEmail'] = $this->fn_general->clear_null($userProfile['user_email']);

            $result['complaintImages'] = $this->get_wo_section_upload_m('1');

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $uploadType
     * @return array
     * @throws Exception
     */
    public function get_wo_section_upload_m ($uploadType) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($uploadType)) {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadType empty');
            }

            $imageType = ['', 'Complaint', 'Before', 'During', 'After'];
            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_upload_m', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>$uploadType, 'sys_upload.upload_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskUploadId'] = $dataLocal['wo_task_upload_id'];
                $row_result['woTaskUploadType'] = $imageType[intval($dataLocal['wo_task_upload_type'])];
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woTaskUploadLongitude'] = $this->fn_general->clear_null($dataLocal['wo_task_upload_longitude']);
                $row_result['woTaskUploadLatitude'] = $this->fn_general->clear_null($dataLocal['wo_task_upload_latitude']);
                $row_result['woTaskUploadTimestamp'] = str_replace('-', '/', $dataLocal['wo_task_upload_timestamp']);
                $row_result['woTaskUploadDesc'] = $this->fn_general->clear_null($dataLocal['wo_task_upload_desc']);
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
     * @return array
     * @throws Exception
     */
    public function get_wo_group_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $result = array();
            $siteId = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'site_id', null, 1);
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_group', array('site_id'=>$siteId, 'role_id'=>'8'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['groupId'] = $dataLocal['ppm_group_id'];
                $row_result['groupName'] = $dataLocal['ppm_group_name'];
                array_push($result, $row_result);
            }
            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmGroupId
     * @return array
     * @throws Exception
     */
    public function get_wo_technician_m ($ppmGroupId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_ppm_group_user', array('ppm_group_user.ppm_group_id'=>$ppmGroupId, 'user_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['userId'] = $dataLocal['user_id'];
                $row_result['userName'] = $dataLocal['user_first_name'];
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $userTechId
     * @param $ppmGroupId
     * @return array
     * @throws Exception
     */
    public function get_technician_details_m ($userTechId='', $ppmGroupId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            if (empty($userTechId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userTechId empty');
            }
            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            $result = array();
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrPpmGroupName = $this->fn_general->getPpmGroupName();

            $userProfile = Class_db::getInstance()->db_select_single('sys_user_profile', array('user_id'=>$userTechId, 'user_profile_status'=>'1'));
            $result['name'] = $arrUserFullName[$userTechId];
            $result['phoneNo'] = $this->fn_general->clear_null($userProfile['user_contact_no']);
            $result['email'] = $this->fn_general->clear_null($userProfile['user_email']);
            $result['group'] = $arrPpmGroupName[$ppmGroupId];

            $woTasks = Class_db::getInstance()->db_select('wo_task', array('wo_task_assigned_to'=>$userTechId, 'wo_task_status'=>'13'));
            $result['totalCurrentTask'] = sizeof($woTasks);
            $result['currentTask'] = array();
            foreach ($woTasks as $woTask) {
                $row_result['woTaskNo'] = $woTask['wo_task_no'];
                $row_result['dateReceived'] = str_replace('-', '/', $this->fn_general->clear_null($woTask['wo_task_time_assigned']));
                if (!empty($row_result['dateReceived'])) {
                    $row_result['dateReceived'] = substr($row_result['dateReceived'], 0, 10);
                }
                array_push($result['currentTask'], $row_result);
            }
            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}