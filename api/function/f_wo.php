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
     * @return array
     */
    public function get_severity () {
        return array('', 'Non-Critical', 'Critical');
    }

    /**
     * @return array
     */
    public function get_upload_type () {
        return array('', '', 'Before', 'During', 'After');
    }

    /**
     * @return array
     */
    public function get_wo_type () {
        return array('', 'Client Complaint', 'Self Finding', 'Request', 'Breakdown', 'Defect', 'Public Complaint');
    }

    /**
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getWoTask ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskId));
            return Class_db::getInstance()->db_select_single2('wo_task', array('wo_task_id'=>$woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getWoTaskPublic ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskId));
            return Class_db::getInstance()->db_select_single2('wo_task_public', array('wo_task_id'=>$woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_role_id_from_user () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            return Class_db::getInstance()->db_select_col('sys_user_role', array('user_id'=>$this->userId, 'role_id'=>'(6,9)'), 'role_id', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wo_task_type () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_type_init', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $groupId
     * @param bool $isWo
     * @return string
     * @throws Exception
     */
    public function create_wo_no ($groupId, $isWo=true) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($groupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            $curDates = new DateTime();
            if ($groupId === '1') {
                $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$this->userId), 'site_id', null, 1);
                $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            }
            $site = Class_db::getInstance()->db_select_single('cli_site', array('group_id'=>$groupId), null, 1);
            $siteId = $site['site_id'];
            $siteCode = $site['site_code'];
            if ($isWo === true || $site['site_is_wr'] !== '1') {
                $runningNo = $site['site_running_no_wo'];
                $runningNo = intval($runningNo);
                $runningNoTemp = 100000 + $runningNo;
                $runningNoStr = substr(strval($runningNoTemp), 1);
                $runningNo++;
                Class_db::getInstance()->db_update('cli_site', array('site_running_no_wo'=>strval($runningNo)), array('site_id'=>$siteId));
                return 'WO'.$siteCode.$curDates->format("ymd").$runningNoStr;
            } else {
                $runningNoWr = $site['site_running_no_wr'];
                $runningNoWr = intval($runningNoWr);
                $runningNoWrTemp = 100000 + $runningNoWr;
                $runningNoWrStr = substr(strval($runningNoWrTemp), 1);
                $runningNoWr++;
                Class_db::getInstance()->db_update('cli_site', array('site_running_no_wr'=>strval($runningNoWr)), array('site_id'=>$siteId));
                return 'WR'.$siteCode.$curDates->format("ymd").$runningNoWrStr;
            }
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
     * @param string $woTaskLongitude
     * @param string $woTaskLatitude
     * @param string $isHelpdesk
     * @return mixed
     * @throws Exception
     */
    public function submit_new_complaint ($taskId, $woTaskNo='', $woTaskLocation='', $woTaskComplaint='', $complaintImageUploads=array(), $woTaskLongitude='', $woTaskLatitude='', $isHelpdesk='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
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

            $task = Class_db::getInstance()->db_select_single('wfl_task', array('task_id'=>$taskId), null, 1);
            $groupId = $task['group_id'];
            $woTaskType = $task['checkpoint_id']==='10'?'2':'1';
            if ($groupId === '1') {
                $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$this->userId), 'site_id', null, 1);
            } else {
                $siteId = Class_db::getInstance()->db_select_col('cli_site', array('group_id'=>$groupId), 'site_id', null, 1);
            }

            $arrWhere = array('transaction_id'=>$task['transaction_id'], 'wo_task_no'=>$woTaskNo, 'wo_task_type'=>$woTaskType, 'wo_task_type_init'=>$woTaskType, 'wo_task_location'=>$woTaskLocation, 'wo_task_complaint'=>$woTaskComplaint,
                'wo_task_longitude'=>$woTaskLongitude, 'wo_task_latitude'=>$woTaskLatitude, 'site_id'=>$siteId, 'wo_task_created_by'=>$task['task_created_user'], 'wo_task_is_helpdesk'=>$isHelpdesk, 'wo_task_status'=>'24');
            if ($woTaskType === '1' && $this->get_wo_is_wr() === '1') {
                $arrWhere['wo_task_is_wr'] = '1';
                $arrWhere['wo_task_request_no'] = $woTaskNo;
                $arrWhere['wo_task_is_pdf_wr'] = '1';
            } else {
                $arrWhere['wo_task_is_pdf'] = '1';
            }

            $woTaskId = Class_db::getInstance()->db_insert('wo_task', $arrWhere);
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
     * @param string $woType
     * @return array
     * @throws Exception
     */
    public function get_submitted_wo_m ($searchText='', $woType='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $statusArr = $this->fn_general->getRefStatus ();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_submitted_m', array(), 'wo_task_time_created DESC', '100', null, array('user_id'=>$this->userId, 'search_text'=>$searchText, 'wo_type'=>$woType));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woType'] = $dataLocal['wo_type'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_no'];
                $row_result['woTaskType'] = $dataLocal['wo_task_type_desc'];
                $row_result['woTaskSeverity'] = $dataLocal['wo_task_severity_desc'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['reportedBy'] = $dataLocal['user_first_name'];
                $row_result['assignedTo'] = $this->fn_general->clear_null($dataLocal['assigned_to']);
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
     * @param string $woType
     * @return array
     * @throws Exception
     */
    public function get_pending_task_m ($searchText='', $woType='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $statusArr = $this->fn_general->getRefStatus ();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_pending_m', array(), 'wfl_task.task_id DESC', '300', null, array('user_id'=>$this->userId, 'search_text'=>$searchText, 'wo_type'=>$woType));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woType'] = $dataLocal['wo_type'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_no'];
                $row_result['woTaskType'] = $dataLocal['wo_task_type_desc'];
                $row_result['woTaskSeverity'] = $dataLocal['wo_task_severity_desc'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['reportedBy'] = $dataLocal['user_first_name'];
                $row_result['assignedTo'] = $this->fn_general->clear_null($dataLocal['assigned_to']);
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $arr_status = $this->fn_general->getRefStatus();
            $result = array(
                array('sectionName'=>'A', 'sectionDesc'=>'Complaint Details', 'sectionStatus'=>$arr_status[17]),
                array('sectionName'=>'B', 'sectionDesc'=>'Description of Repair Works', 'sectionStatus'=>$arr_status[18]),
                array('sectionName'=>'C', 'sectionDesc'=>'Images', 'sectionStatus'=>$arr_status[18]),
                array('sectionName'=>'D', 'sectionDesc'=>'Asset No', 'sectionStatus'=>$arr_status[18])
            );

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if (!empty($woTask['wo_task_repair_desc'])) {
                $result[1]['sectionStatus'] = $arr_status[19];
            }
            if ($woTask['wo_task_done_asset'] === '1') {
                $result[3]['sectionStatus'] = $arr_status[19];
            }

            $imgBefore = false;
            $imgDuring = false;
            $imgAfter = false;
            $woTaskUploads = Class_db::getInstance()->db_select('wo_task_upload', array('wo_task_id'=>$this->woTaskId));
            foreach ($woTaskUploads as $woTaskUpload) {
                $uploadType = $woTaskUpload['wo_task_upload_type'];
                if ($uploadType === '2') {
                    $imgBefore = true;
                } else if ($uploadType === '3') {
                    $imgDuring = true;
                } else if ($uploadType === '4') {
                    $imgAfter = true;
                }
            }
            if ($imgBefore && $imgDuring && $imgAfter) {
                $result[2]['sectionStatus'] = $arr_status[19];
            }

            $isMaterial = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$woTask['site_id']), 'site_is_material');
            if ($isMaterial === '1') {
                $sectionStatus = $arr_status[18];
                $materialStatusId = '';
                $materialStatus = '';
                $sectionComment = '';
                if ($woTask['wo_task_has_parts'] === '0') {
                    $sectionStatus = $arr_status[19];
                } else if ($woTask['wo_task_has_parts'] === '1') {
                    $statusPartId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$this->woTaskId), 'wo_task_request_status', 'wo_task_request_id DESC');
                    if (!empty($statusPartId)) {
                        if ($statusPartId === '36') {
                            $sectionStatus = $arr_status[19];
                        } else if ($statusPartId === '50') {
                            $sectionComment = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$this->woTaskId), 'wo_task_request_remark', 'wo_task_request_id DESC');
                        }
                        $materialStatus = $arr_status[$statusPartId];
                        $materialStatusId = $statusPartId;
                    }
                }
                array_push($result, array('sectionName'=>'E',
                    'sectionDesc'=>'Material / Spare Parts',
                    'sectionStatus'=>$sectionStatus,
                    'sectionStatusMaterialId'=>$materialStatusId,
                    'sectionStatusMaterial'=>$materialStatus,
                    'sectionComment'=>$sectionComment));
            }

            $remark = Class_db::getInstance()->db_select_col('wfl_task', array('transaction_id'=>$woTask['transaction_id'], 'task_current'=>'2'), 'task_remark', 'task_id DESC');
            if (!empty($remark)) {
                array_push($result, array('sectionName'=>($isMaterial === '1'?'F':'E'), 'sectionDesc'=>'Comment', 'sectionStatus'=>$arr_status[17], 'comment'=>$remark));
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
    public function getSectionStatusV2M ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskId));

            $statusArr = $this->fn_general->getRefStatus();
            $result = array(
                array('sectionName'=>'A', 'sectionDesc'=>'Complaint Details', 'sectionStatus'=>$statusArr[17]),
                array('sectionName'=>'B', 'sectionDesc'=>'Assign Executor', 'sectionStatus'=>$statusArr[18]),
                array('sectionName'=>'C', 'sectionDesc'=>'Description of Repair Works', 'sectionStatus'=>$statusArr[18]),
                array('sectionName'=>'D', 'sectionDesc'=>'Images', 'sectionStatus'=>$statusArr[18]),
                array('sectionName'=>'E', 'sectionDesc'=>'Asset No', 'sectionStatus'=>$statusArr[18]),
                array('sectionName'=>'F', 'sectionDesc'=>'Assistants', 'sectionStatus'=>$statusArr[18])
            );

            $woTask = Class_db::getInstance()->db_select_single2('wo_task', array('wo_task_id'=>$woTaskId), null, 1);
            if (!empty($woTask['woTaskAssignedTo'])) {
                $result[1]['sectionStatus'] = $statusArr[19];
            }
            if (!empty($woTask['woTaskRepairDesc'])) {
                $result[2]['sectionStatus'] = $statusArr[19];
            }
            if ($woTask['woTaskDoneAsset'] === '1') {
                $result[4]['sectionStatus'] = $statusArr[19];
            }
            if ($woTask['woTaskDoneAssistant'] === '1') {
                $result[5]['sectionStatus'] = $statusArr[19];
            }

            $imgBefore = false;
            $imgDuring = false;
            $imgAfter = false;
            $woTaskUploads = Class_db::getInstance()->db_select2('wo_task_upload', array('wo_task_id'=>$woTaskId));
            foreach ($woTaskUploads as $woTaskUpload) {
                $uploadType = $woTaskUpload['woTaskUploadType'];
                if ($uploadType === '2') {
                    $imgBefore = true;
                } else if ($uploadType === '3') {
                    $imgDuring = true;
                } else if ($uploadType === '4') {
                    $imgAfter = true;
                }
            }
            if ($imgBefore && $imgDuring && $imgAfter) {
                $result[3]['sectionStatus'] = $statusArr[19];
            }

            $isMaterial = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$woTask['siteId']), 'site_is_material');
            if ($isMaterial === '1') {
                $sectionStatus = $statusArr[18];
                $materialStatusId = '';
                $materialStatus = '';
                $sectionComment = '';
                if ($woTask['woTaskHasParts'] === '0') {
                    $sectionStatus = $statusArr[19];
                } else if ($woTask['woTaskHasParts'] === '1') {
                    $statusPartId = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId), 'wo_task_request_status', 'wo_task_request_id DESC');
                    if (!empty($statusPartId)) {
                        if ($statusPartId === '36') {
                            $sectionStatus = $statusArr[19];
                        } else if ($statusPartId === '50') {
                            $sectionComment = Class_db::getInstance()->db_select_col('wo_task_request', array('wo_task_id'=>$woTaskId), 'wo_task_request_remark', 'wo_task_request_id DESC');
                        }
                        $materialStatus = $statusArr[$statusPartId];
                        $materialStatusId = $statusPartId;
                    }
                }
                $result[] = array('sectionName' => 'G',
                    'sectionDesc' => 'Material / Spare Parts',
                    'sectionStatus' => $sectionStatus,
                    'sectionStatusMaterialId' => $materialStatusId,
                    'sectionStatusMaterial' => $materialStatus,
                    'sectionComment' => $sectionComment);
            }

            $remark = Class_db::getInstance()->db_select_col('wfl_task', array('transaction_id'=>$woTask['transactionId'], 'task_current'=>'2'), 'task_remark', 'task_id DESC');
            if (!empty($remark)) {
                $result[] = array('sectionName' => ($isMaterial === '1' ? 'H' : 'G'), 'sectionDesc' => 'Comment', 'sectionStatus' => $statusArr[17], 'comment' => $remark);
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
    public function get_section_status_assign_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $arr_status = $this->fn_general->getRefStatus();
            $result = array(
                array('sectionName'=>'A', 'sectionDesc'=>'Complaint Details', 'sectionStatus'=>$arr_status[17]),
                array('sectionName'=>'B', 'sectionDesc'=>'Assign Executor', 'sectionStatus'=>$arr_status[18])
            );

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if (!empty($woTask['wo_task_assigned_to']) && !empty($woTask['wo_task_severity'])) {
                $result[1]['sectionStatus'] = $arr_status[19];
            }

            $remark = Class_db::getInstance()->db_select_col('wfl_task', array('transaction_id'=>$woTask['transaction_id'], 'task_current'=>'2'), 'task_remark', 'task_id DESC');
            if (!empty($remark)) {
                array_push($result, array('sectionName'=>'C', 'sectionDesc'=>'Comment', 'sectionStatus'=>$arr_status[17], 'comment'=>$remark));
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
    public function get_section_status_wr_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $arr_status = $this->fn_general->getRefStatus();
            $result = array(
                array('sectionName'=>'A', 'sectionDesc'=>'Complaint Details', 'sectionStatus'=>$arr_status[17])
            );

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $remark = Class_db::getInstance()->db_select_col('wfl_task', array('transaction_id'=>$woTask['transaction_id'], 'task_current'=>'2'), 'task_remark', 'task_id DESC');
            if ($woTask['wo_task_status'] === '28' || $woTask['wo_task_status'] === '30' || $woTask['wo_task_status'] === '31') {
                $validStatus = $woTask['wo_task_is_invalid'] === '1' ? 'Invalid' : 'Valid';
                array_push($result, array('sectionName'=>'B', 'sectionDesc'=>'Comment', 'sectionStatus'=>$validStatus, 'comment'=>$remark));
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

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
     * @param $transactionId
     * @return array
     * @throws Exception
     */
    public function get_wo_task ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                if (!empty($transactionId)) {
                    $dataLocal = Class_db::getInstance()->db_select_single('wo_task', array('transaction_id'=>$transactionId), null, 1);
                } else {
                    throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
                }
            } else {
                $dataLocal = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            }

            $result = array();
            $result['woTaskId'] = $dataLocal['wo_task_id'];
            $result['woTaskNo'] = $dataLocal['wo_task_no'];
            $result['woTaskRequestNo'] = $dataLocal['wo_task_request_no'];
            $result['siteId'] = $dataLocal['site_id'];
            $result['woTaskReportedBy'] = $dataLocal['wo_task_created_by'];
            $result['woTaskTimeResponded'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['wo_task_time_responded']));
            $result['woTaskType'] = $dataLocal['wo_task_type'];
            $result['woTaskTypeInit'] = $dataLocal['wo_task_type_init'];
            $result['woTaskIsWr'] = $dataLocal['wo_task_is_wr'];
            $result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
            $result['woTaskComplaint'] = $this->fn_general->clear_null($dataLocal['wo_task_complaint']);
            $result['woTaskStatus'] = $dataLocal['wo_task_status'];

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
    public function get_complaint_details_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $result = array();
            $arrStatus = $this->fn_general->getRefStatus();
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrSiteName = $this->fn_general->getSiteName();
            $arrWoType = $this->get_wo_type();

            $dataLocal = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $createdBy = $dataLocal['wo_task_created_by'];
            $result['woTaskId'] = $dataLocal['wo_task_id'];
			$result['woTaskNo'] = $dataLocal['wo_task_is_wr'] === '1' && $dataLocal['wo_task_wr_confirm'] !== '1' ? '-' : $dataLocal['wo_task_no'];
            $result['woTaskRequestNo'] = $this->fn_general->clear_null($dataLocal['wo_task_request_no'], '-');
            $result['woTaskReportedBy'] = $arrUserFullName[intval($createdBy)];
            $result['woTaskTimeResponded'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['wo_task_time_responded']));
            $result['woTaskCategory'] = $arrWoType[intval($dataLocal['wo_task_type'])];
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
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>$uploadType, 'sys_upload.upload_status'=>'1'));
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter groupId empty');
            }

            $attTypeArr = Class_db::getInstance()->db_select_cols('att_type', array('att_type_id', 'att_type_name'), array('att_type_mode'=>"('Leave','Training')", 'att_type_status'=>'1'));

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_ppm_group_user', array('ppm_group_user.ppm_group_id'=>$ppmGroupId, 'user_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['userId'] = $dataLocal['user_id'];
                $row_result['userName'] = $dataLocal['user_first_name'];
                $attendance = Class_db::getInstance()->db_select_single('att_transaction', array('user_id'=>$dataLocal['user_id'], 'att_transaction_date'=>'Curdate()'));
                if (!empty($attendance) && isset($attTypeArr[$attendance['att_type_id']])) {
                    $row_result['userName'] .= ' ('.$attTypeArr[$attendance['att_type_id']].')';
                }
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

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

            $woTasks = Class_db::getInstance()->db_select('wo_task', array('wo_task_assigned_to'=>$userTechId, 'wo_task_status'=>'(13, 27)'));
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

    /**
     * @param string $ppmGroupId
     * @param string $userTechId
     * @param string $severityId
     * @param array $assistUserId
     * @param string $woTaskType
     * @return array
     * @throws Exception
     */
    public function save_assigned_technician_m ($ppmGroupId='', $userTechId='', $severityId='', $assistUserId=array(), $woTaskType='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            }
            if (empty($userTechId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userTechId empty');
            }
            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityId empty');
            }
            if (empty($woTaskType)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskCategory empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['wo_task_is_public'] === '1' && intval($woTaskType) !== 6) {
                throw new Exception('[' . __LINE__ . '] - This complaint category must set to Public Complaint only!', 31);
            }

            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrSeverity = $this->fn_general->getSeverityName();
            $arrTaskType = $this->get_wo_type();
            Class_db::getInstance()->db_update('wo_task', array('wo_task_assigned_to'=>$userTechId, 'ppm_group_id'=>$ppmGroupId, 'wo_task_severity'=>$severityId, 'wo_task_type'=>$woTaskType), array('wo_task_id'=>$this->woTaskId));

            Class_db::getInstance()->db_delete('wo_task_assist', array('wo_task_id'=>$this->woTaskId));
            if (!empty($assistUserId)) {
                foreach ($assistUserId as $userId) {
                    Class_db::getInstance()->db_insert('wo_task_assist', array('wo_task_id'=>$this->woTaskId, 'user_id'=>$userId));
                }
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'userFirstName'=>$arrUserFullName[intval($userTechId)],
                'severityName'=>$arrSeverity[intval($severityId)],
                'woTaskType'=>$arrTaskType[intval($woTaskType)]
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function saveAssignedTechnicianV2M ($woTaskId, $params=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $this->fn_general->checkEmptyParams(array($woTaskId));
            $this->fn_general->checkEmptyParamsArray($params, array('groupId', 'userId', 'severity', 'woTaskCategory', 'woTaskMaxAssistant'));
            $arrUserFullName = $this->fn_general->getUserFullName();
            $arrSeverity = $this->fn_general->getSeverityName();
            $arrTaskType = $this->get_wo_type();
            $woTaskAssignedTo = $params['userId'];
            $ppmGroupId = $params['groupId'];
            $woTaskSeverity = $params['severity'];
            $woTaskType = $params['woTaskCategory'];
            $woTaskMaxAssistant = $params['woTaskMaxAssistant'];
            Class_db::getInstance()->db_update('wo_task',
                array('wo_task_assigned_to'=>$woTaskAssignedTo, 'ppm_group_id'=>$ppmGroupId, 'wo_task_severity'=>$woTaskSeverity, 'wo_task_type'=>$woTaskType, 'wo_task_max_assistant'=>$woTaskMaxAssistant),
                array('wo_task_id'=>$woTaskId));

            $woTaskNo = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$woTaskId), 'wo_task_no', null, 1);
            return array(
                'woTaskNo'=>$woTaskNo,
                'userFirstName'=>!empty($woTaskAssignedTo)?$arrUserFullName[intval($woTaskAssignedTo)]:'',
                'severityName'=>!empty($woTaskSeverity)?$arrSeverity[intval($woTaskSeverity)]:'',
                'woTaskType'=>!empty($woTaskType)?$arrTaskType[intval($woTaskType)]:''
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $severityId
     * @return array
     * @throws Exception
     */
    public function save_wo_severity_m ($severityId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($severityId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter severityId empty');
            }

            $arrSeverity = $this->fn_general->getSeverityName();
            Class_db::getInstance()->db_update('wo_task', array('wo_task_severity'=>$severityId), array('wo_task_id'=>$this->woTaskId));

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'severityName'=>$arrSeverity[intval($severityId)]
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $timeRectified
     * @return array
     * @throws Exception
     */
    public function save_wr_rectification_time_m ($timeRectified='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            Class_db::getInstance()->db_update('wo_task', array('wo_task_time_rectified'=>$timeRectified), array('wo_task_id'=>$this->woTaskId));
            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'timeRectified'=>$timeRectified
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_assigned_technician () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_assigned_to', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_complainer () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_created_by', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wr_validity () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_is_invalid', null, 1);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $currentStatus
     * @param string $currentCheckpoint
     * @param string $currentStatus2
     * @param string $currentCheckpoint2
     * @param string $currentStatus3
     * @return array
     * @throws Exception
     */
    public function get_current_task ($currentStatus='', $currentCheckpoint='', $currentStatus2='', $currentCheckpoint2='', $currentStatus3='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $constant = $this->constant;

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($currentStatus)) {
                throw new Exception('[' . __LINE__ . '] - Parameter currentStatus empty');
            }
            if (empty($currentCheckpoint)) {
                throw new Exception('[' . __LINE__ . '] - Parameter currentCheckpoint empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $transactionId = $woTask['transaction_id'];
            if ($woTask['wo_task_status'] !== $currentStatus && $woTask['wo_task_status'] !== $currentStatus2 && $woTask['wo_task_status'] !== $currentStatus3) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_TASK_ALREADY_SUBMITTED, 31);
            }

            $wfTask = Class_db::getInstance()->db_select_single('wfl_task', array('transaction_id'=>$transactionId, 'task_current'=>'1'), null, 1);
            if ($wfTask['checkpoint_id'] !== $currentCheckpoint && $wfTask['checkpoint_id'] !== $currentCheckpoint2) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_TASK_ALREADY_SUBMITTED, 31);
            }

            return array(
                'transactionId'=>$transactionId,
                'taskId'=>$wfTask['task_id'],
                'checkpointId'=>$wfTask['checkpoint_id'],
                'taskStatus'=>$woTask['wo_task_status']
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @return mixed
     * @throws Exception
     */
    public function submit_assign ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            $woStatus = $this->get_wo_is_wr() === '1' ? '27' : '13';

            Class_db::getInstance()->db_update('wo_task', array('wo_task_assigned_by'=>$this->userId, 'wo_task_is_pdf'=>($woStatus==='13'?'1':'0'), 'wo_task_is_pdf_wr'=>($woStatus==='27'?'1':'0'), 'wo_task_time_assigned'=>'Now()',  'wo_task_status'=>$woStatus), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>$woStatus), array('transaction_id'=>$transactionId));

            return $woTask['wo_task_no'];
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @return mixed
     * @throws Exception
     */
    public function reject_complaint ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_update('wo_task', array('wo_task_status'=>'25', 'wo_task_is_pdf'=>'1'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'25'), array('transaction_id'=>$transactionId));

            return $woTask['wo_task_no'];
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wo_assign_severity_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $arrWoType = $this->get_wo_type();
            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            $assistUserId = Class_db::getInstance()->db_select_colm('wo_task_assist', array('wo_task_id'=>$this->woTaskId), 'user_id');

            return array(
                'groupId'=>$this->fn_general->clear_null($woTask['ppm_group_id']),
                'userId'=>$this->fn_general->clear_null($woTask['wo_task_assigned_to']),
                'severity'=>$this->fn_general->clear_null($woTask['wo_task_severity']),
                'userCategory'=>($woTask['wo_task_type_init']==='1'?'Client':'Internal'),
                'woTaskCategory'=>$this->fn_general->clear_null($woTask['wo_task_type']),
                'assistUserId'=>$assistUserId);
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getWoAssignSeverityV2M ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $this->fn_general->checkEmptyParams(array($woTaskId));
            $woTask = Class_db::getInstance()->db_select_single2('wo_task', array('wo_task_id'=>$woTaskId), null, 1);
            return array(
                'groupId'=>$woTask['ppmGroupId'],
                'userId'=>$woTask['woTaskAssignedTo'],
                'severity'=>$woTask['woTaskSeverity'],
                'userCategory'=>($woTask['woTaskTypeInit']==='1'?'Client':'Internal'),
                'woTaskCategory'=>$woTask['woTaskType'],
                'woTaskMaxAssistant'=>(empty($woTask['woTaskMaxAssistant'])?'0':$woTask['woTaskMaxAssistant'])
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $repairDesc
     * @return mixed
     * @throws Exception
     */
    public function save_wo_repair_desc_m ($repairDesc='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($repairDesc)) {
                throw new Exception('[' . __LINE__ . '] - Parameter repairDesc empty');
            }

            Class_db::getInstance()->db_update('wo_task', array('wo_task_repair_desc'=>$repairDesc), array('wo_task_id'=>$this->woTaskId));

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return $woTask['wo_task_no'];
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wo_repair_desc_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return $this->fn_general->clear_null(Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_repair_desc', null, 1));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $uploadId
     * @param $uploadType
     * @param string $longitude
     * @param string $latitude
     * @return mixed
     * @throws Exception
     */
    public function save_wo_image_m ($uploadId, $uploadType, $longitude='', $latitude='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($uploadId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadId empty');
            }
            if ($uploadType != '2' && $uploadType != '3' && $uploadType != '4') {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadType invalid');
            }
            if (empty($longitude)) {
                throw new Exception('[' . __LINE__ . '] - Parameter longitude empty');
            }
            if (empty($latitude)) {
                throw new Exception('[' . __LINE__ . '] - Parameter latitude empty');
            }

            Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>$uploadType, 'upload_id'=>$uploadId,
                'wo_task_upload_longitude'=>$longitude, 'wo_task_upload_latitude'=>$latitude));

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return $woTask['wo_task_no'];
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskUploads
     * @return mixed
     * @throws Exception
     */
    public function save_wo_image_desc_m ($woTaskUploads) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (!is_array($woTaskUploads)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskUploads is not array');
            }
            if (empty($woTaskUploads)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskUploads empty');
            }

            foreach ($woTaskUploads as $woTaskUpload) {
                if (!array_key_exists('woTaskUploadId', $woTaskUpload) || empty($woTaskUpload['woTaskUploadId'])) {
                    throw new Exception('[' . __LINE__ . '] - Parameter woTaskUpload[woTaskUploadId] empty');
                }
                if (!array_key_exists('woTaskUploadDesc', $woTaskUpload)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter woTaskUpload[woTaskUploadDesc] not exist');
                }
                Class_db::getInstance()->db_update('wo_task_upload', array('wo_task_upload_desc'=>$woTaskUpload['woTaskUploadDesc']), array('wo_task_upload_id'=>$woTaskUpload['woTaskUploadId']));
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return $woTask['wo_task_no'];
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskUploadId
     * @return mixed
     * @throws Exception
     */
    public function delete_wo_repair_image_m ($woTaskUploadId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($woTaskUploadId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskUploadId empty');
            }

            $uploadId = Class_db::getInstance()->db_select_col('wo_task_upload', array('wo_task_upload_id'=>$woTaskUploadId), 'upload_id', null, 1);
            Class_db::getInstance()->db_delete('wo_task_upload', array('wo_task_upload_id'=>$woTaskUploadId));
            Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$uploadId));

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return $woTask['wo_task_no'];
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
    public function get_wo_repair_images_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $result = array();
            $arrUploadType = $this->get_upload_type();
            $arr_dataLocal = Class_db::getInstance()->db_select('mw_wo_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>'(2,3,4)', 'sys_upload.upload_status'=>'1'));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskUploadId'] = $dataLocal['wo_task_upload_id'];
                $row_result['woTaskUploadType'] = $arrUploadType[intval($dataLocal['wo_task_upload_type'])];
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
     * @param string $transactionId
     * @return mixed
     * @throws Exception
     */
    public function return_by_technician ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_update('wo_task', array('wo_task_assigned_by'=>'', 'wo_task_is_pdf'=>'1', 'wo_task_time_assigned'=>'',  'wo_task_status'=>'26'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'26'), array('transaction_id'=>$transactionId));
            Class_db::getInstance()->db_delete('wfl_task_assign', array('transaction_id'=>$transactionId, 'checkpoint_id'=>'(13, 16)'));

            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskAssignedBy'=>$woTask['wo_task_assigned_by']
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @return mixed
     * @throws Exception
     */
    public function return_wr_by_technician ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_update('wo_task', array('wo_task_assigned_by'=>'', 'wo_task_time_assigned'=>'',  'wo_task_status'=>'29'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'29'), array('transaction_id'=>$transactionId));
            Class_db::getInstance()->db_delete('wfl_task_assign', array('transaction_id'=>$transactionId, 'checkpoint_id'=>'(13, 18)'));

            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskAssignedBy'=>$woTask['wo_task_assigned_by']
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @param string $signatureId
     * @param string $remark
     * @param string $isVerified
     * @param string $isRejected
     * @return array
     * @throws Exception
     */
    public function submit_wr_check ($transactionId='', $signatureId='', $remark='', $isVerified='0', $isRejected='0') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }
            if (empty($signatureId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter signatureId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            //$status = $isVerified === '2' ? '30' : '28';
            $isRejected = $isRejected === '1' ? '1' : '0';
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>'9', 'upload_id'=>$signatureId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_wr_checked_by'=>$this->userId, 'wo_task_is_pdf_wr'=>'1', 'wo_task_is_invalid'=>$isRejected, 'wo_task_wr_check'=>$remark, 'wo_task_is_wr_verified_together'=>$isVerified, 'wo_task_time_wr_checked'=>'Now()', 'wo_task_status'=>'28'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'28'), array('transaction_id'=>$transactionId));

            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskCreatedBy'=>$woTask['wo_task_created_by']
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @param string $signatureId
     * @param string $woTaskNo
     * @param string $verifier
     * @param string $isRejected
     * @return mixed
     * @throws Exception
     */
    public function submit_wr_verify ($transactionId='', $signatureId='', $woTaskNo='', $verifier='', $isRejected='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }
            if (empty($signatureId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter signatureId empty');
            }
            if (empty($woTaskNo)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskNo empty');
            }

            $isWr = $this->get_wo_is_wr();
            if ($isWr === '1') {
                if (empty($verifier)) {
                    throw new Exception('[' . __LINE__ . '] - Parameter verifier empty');
                }
            } else {
                $verifier = $this->userId;
            }
            $status = $isRejected === '1' ? '30' : '13';

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>'10', 'upload_id'=>$signatureId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_no'=>$woTaskNo, 'wo_task_is_pdf_wr'=>'1', 'wo_task_is_pdf'=>($status === '13' ? '1':'0'), 'wo_task_wr_verified_by'=>$verifier, 'wo_task_time_wr_verified'=>'Now()', 'wo_task_status'=>$status), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>$status), array('transaction_id'=>$transactionId));

            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskAssignedTo'=>$woTask['wo_task_assigned_to'],
                'woTaskCreatedBy'=>$woTask['wo_task_created_by']
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @return mixed
     * @throws Exception
     */
    public function return_wr_verify ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_update('wo_task', array('wo_task_status'=>'31', 'wo_task_is_pdf_wr'=>'1'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'31'), array('transaction_id'=>$transactionId));

            $technician = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id'=>$transactionId), 'user_id', null, 1);
            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'technician'=>$technician
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @param $signatureId
     * @return mixed
     * @throws Exception
     */
    public function submit_repair ($transactionId='', $signatureId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }
            if (empty($signatureId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter signatureId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>'7', 'upload_id'=>$signatureId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_fixed_by'=>$this->userId, 'wo_task_is_pdf'=>'1', 'wo_task_time_executed'=>'Now()', 'wo_task_status'=>'15'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'15'), array('transaction_id'=>$transactionId));

            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskCreatedBy'=>$woTask['wo_task_created_by'],
                'woTaskAssignedBy'=>$woTask['wo_task_assigned_by']
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @return array
     * @throws Exception
     */
    public function return_verify ($transactionId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_update('wo_task', array('wo_task_status'=>'21', 'wo_task_time_executed'=>'', 'wo_task_is_pdf'=>'1'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'21'), array('transaction_id'=>$transactionId));

            $woTaskUploads = Class_db::getInstance()->db_select('wo_task_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>'7'));
            foreach ($woTaskUploads as $woTaskUpload) {
                Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$woTaskUpload['upload_id']));
                Class_db::getInstance()->db_delete('wo_task_upload', array('wo_task_upload_id'=>$woTaskUpload['wo_task_upload_id']));
            }

            $woTaskReturnTo = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id'=>$transactionId, 'checkpoint_id'=>'13', 'role_id'=>'8'), 'user_id');
            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskReturnTo'=>$woTaskReturnTo
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $transactionId
     * @param string $signatureId
     * @param string $woTaskRate
     * @return array
     * @throws Exception
     */
    public function submit_verify ($transactionId='', $signatureId='', $woTaskRate='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }
            if (empty($transactionId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId empty');
            }
            if (empty($signatureId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter signatureId empty');
            }
            if (empty($woTaskRate)) {
                $woTaskRate = '';
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            if ($woTask['transaction_id'] !== $transactionId) {
                throw new Exception('[' . __LINE__ . '] - Parameter transactionId invalid');
            }
            Class_db::getInstance()->db_insert('wo_task_upload', array('wo_task_id'=>$this->woTaskId, 'wo_task_upload_type'=>'8', 'upload_id'=>$signatureId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_rate'=>$woTaskRate, 'wo_task_is_pdf'=>'1', 'wo_task_verified_by'=>$this->userId, 'wo_task_time_verified'=>'Now()', 'wo_task_status'=>'16'), array('wo_task_id'=>$this->woTaskId));
            Class_db::getInstance()->db_update('wfl_transaction', array('transaction_status'=>'16'), array('transaction_id'=>$transactionId));

            $woTaskTechnician = Class_db::getInstance()->db_select_col('wfl_task_assign', array('transaction_id'=>$transactionId, 'checkpoint_id'=>'13', 'role_id'=>'8'), 'user_id');
            return array(
                'woTaskNo'=>$woTask['wo_task_no'],
                'woTaskTechnician'=>$woTaskTechnician
            );
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $woTaskRate
     * @return mixed
     * @throws Exception
     */
    public function save_wo_rate_m ($woTaskRate='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($woTaskRate)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskRate empty');
            }

            Class_db::getInstance()->db_update('wo_task', array('wo_task_rate'=>$woTaskRate, 'wo_task_is_pdf'=>'1'), array('wo_task_id'=>$this->woTaskId));

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
            return $woTask['wo_task_no'];
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wo_rate_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return $this->fn_general->clear_null(Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_rate', null, 1));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wr_rectification_time_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            return $this->fn_general->clear_null(Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'wo_task_time_rectified'));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $siteId
     * @param string $year
     * @param string $month
     * @param bool $isPending
     * @param string $kpiType
     * @return array
     * @throws Exception
     */
    public function get_wo_task_dashboard_list ($clientId='', $siteId='', $year='', $month='', $isPending=false, $kpiType='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

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

            $arrWhere = array('site_id'=>$siteId, 'YEAR(wo_task_time_created)'=>$year, 'MONTH(wo_task_time_created)-1'=>$month);
            $arrSeverity = $this->fn_general->getSeverityName();
            $arrWoType = $this->get_wo_type();
            if ($isPending) {
                $arrWhere['wo_task_status'] = 'N(16, 25)';
            }
            if ($kpiType === 'responseTime') {
                $arrWhere['wo_task_status'] = 'N(25)';
                $arrWhere['wo_task_type'] = '<>2';
            } else if ($kpiType === 'mitigateTime') {
                $arrWhere['wo_task_time_executed'] = 'is not NULL';
                $arrWhere['wo_task_type'] = '<>2';
            } else if ($kpiType === 'serviceQuality') {
                $arrWhere['wo_task_status'] = '16';
                $arrWhere['wo_task_type'] = '<>2';
                $arrWhere['wo_task_rate'] = 'is not NULL';
            } else if ($kpiType === 'turnaroundTime') {
                $arrWhere['wo_task_status'] = 'N(25)';
                $arrWhere['wo_task_type'] = '<>2';
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vg_wo_dashboard', $arrWhere);
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woTaskNoOri'] = $dataLocal['wo_task_no'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_is_wr'] === '1' && $dataLocal['wo_task_wr_confirm'] !== '1' ? '-' : $dataLocal['wo_task_no'];
                $row_result['woTaskRequestNo'] = $this->fn_general->clear_null($dataLocal['wo_task_request_no'], '-');
                $row_result['woTaskType'] = $this->fn_general->clear_null($dataLocal['wo_task_type'], '0');
                $row_result['woTaskTypeDesc'] = $arrWoType[intval($this->fn_general->clear_null($dataLocal['wo_task_type'], '0'))];
                $row_result['woTaskIsWr'] = $dataLocal['wo_task_is_wr'];
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['woTaskComplaint'] = $this->fn_general->clear_null($dataLocal['wo_task_complaint']);
                $row_result['woTaskAssignedTo'] = $this->fn_general->clear_null($dataLocal['wo_task_assigned_to']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['woTaskSeverity'] = $arrSeverity[intval($this->fn_general->clear_null($dataLocal['wo_task_severity'], '0'))];
                $row_result['woTaskRepairDesc'] = $this->fn_general->clear_null($dataLocal['wo_task_repair_desc']);
                $row_result['woTaskRateOri'] = $this->fn_general->clear_null($dataLocal['wo_task_rate']);
                $row_result['woTaskRate'] = empty($dataLocal['wo_task_rate']) ? '' : $dataLocal['wo_task_rate'].' / 5';
                $row_result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
                $row_result['pdfIdWr'] = $this->fn_general->clear_null($dataLocal['pdf_id_wr']);
                $row_result['woTaskCreatedBy'] = $dataLocal['wo_task_created_by'];
                $row_result['woTaskFixedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_fixed_by']);
                $row_result['woTaskAssignedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_assigned_by']);
                $row_result['woTaskVerifiedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_verified_by']);
                $row_result['woTaskTimeCreated'] = str_replace('-', '/', $dataLocal['wo_task_time_created']);
                $row_result['woTaskTimeResponded'] = str_replace('-', '/', $dataLocal['wo_task_time_responded']);
                $row_result['woTaskTimeAssigned'] = str_replace('-', '/', $dataLocal['wo_task_time_assigned']);
                $row_result['woTaskTimeWrChecked'] = str_replace('-', '/', $dataLocal['wo_task_time_wr_checked']);
                $row_result['woTaskTimeWrVerified'] = str_replace('-', '/', $dataLocal['wo_task_time_wr_verified']);
                $row_result['woTaskTimeExecuted'] = str_replace('-', '/', $dataLocal['wo_task_time_executed']);
                $row_result['woTaskTimeVerified'] = str_replace('-', '/', $dataLocal['wo_task_time_verified']);
                $row_result['durationResponded'] = $this->fn_general->timeDiff($row_result['woTaskTimeCreated'], ($row_result['woTaskIsWr'] === '1' ? $row_result['woTaskTimeWrChecked'] : $row_result['woTaskTimeAssigned']));
                $row_result['woTaskStatus'] = $dataLocal['wo_task_status'];
                $row_result['kpiResponseResult'] = '';
                $durationResponded = $this->fn_general->timeDiffMinute($row_result['woTaskTimeCreated'], ($row_result['woTaskIsWr'] === '1' ? $row_result['woTaskTimeWrChecked'] : $row_result['woTaskTimeAssigned']));
                if ($durationResponded !== '') {
                    if ($dataLocal['wo_task_severity'] === '5') {
                        $row_result['kpiResponseResult'] = $durationResponded <= 15 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '4') {
                        $row_result['kpiResponseResult'] = $durationResponded <= 15 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '3') {
                        $row_result['kpiResponseResult'] = $durationResponded <= 30 ? 'Success' : 'Fail';
                    }
                }
                $row_result['durationMitigated'] = $this->fn_general->timeDiff($row_result['woTaskTimeCreated'], $row_result['woTaskTimeExecuted']);
                $row_result['kpiMitigateResult'] = '';
                $durationMitigated = $this->fn_general->timeDiffHour($row_result['woTaskTimeCreated'], $row_result['woTaskTimeExecuted']);
                if ($durationMitigated !== '') {
                    if ($dataLocal['wo_task_severity'] === '5') {
                        $row_result['kpiMitigateResult'] = $durationMitigated <= 3 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '4') {
                        $row_result['kpiMitigateResult'] = $durationMitigated <= 24 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '3') {
                        $row_result['kpiMitigateResult'] = $durationMitigated <= 168 ? 'Success' : 'Fail';
                    }
                }
                $row_result['assistants'] = $dataLocal['assistants'];
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
     * @param string $dateStart
     * @param string $dateEnd
     * @param bool $isPending
     * @param string $kpiType
     * @return array
     * @throws Exception
     */
    public function get_wo_task_dashboard_list2 ($clientId='', $siteId='', $dateStart='', $dateEnd='', $isPending=false, $kpiType='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

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

            $arrWhere = array('site_id'=>$siteId, 'DATE(wo_task_time_created)'=>'>='.$dateStart, 'DATE(wo_task_time_created) '=>'<='.$dateEnd);
            $arrSeverity = $this->fn_general->getSeverityName();
            $arrWoType = $this->get_wo_type();
            if ($isPending) {
                $arrWhere['wo_task_status'] = 'N(16, 25)';
            }
            if ($kpiType === 'responseTime') {
                $arrWhere['wo_task_status'] = 'N(25)';
                $arrWhere['wo_task_type'] = '<>2';
            } else if ($kpiType === 'mitigateTime') {
                $arrWhere['wo_task_time_executed'] = 'is not NULL';
                $arrWhere['wo_task_type'] = '<>2';
            } else if ($kpiType === 'serviceQuality') {
                $arrWhere['wo_task_status'] = '16';
                $arrWhere['wo_task_type'] = '<>2';
                $arrWhere['wo_task_rate'] = 'is not NULL';
            } else if ($kpiType === 'turnaroundTime') {
                $arrWhere['wo_task_status'] = 'N(25)';
                $arrWhere['wo_task_type'] = '<>2';
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vg_wo_dashboard', $arrWhere);
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woTaskNoOri'] = $dataLocal['wo_task_no'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_is_wr'] === '1' && $dataLocal['wo_task_wr_confirm'] !== '1' ? '-' : $dataLocal['wo_task_no'];
                $row_result['woTaskRequestNo'] = $this->fn_general->clear_null($dataLocal['wo_task_request_no'], '-');
                $row_result['woTaskType'] = $this->fn_general->clear_null($dataLocal['wo_task_type'], '0');
                $row_result['woTaskTypeDesc'] = $arrWoType[intval($this->fn_general->clear_null($dataLocal['wo_task_type'], '0'))];
                $row_result['woTaskIsWr'] = $dataLocal['wo_task_is_wr'];
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['woTaskComplaint'] = $this->fn_general->clear_null($dataLocal['wo_task_complaint']);
                $row_result['woTaskAssignedTo'] = $this->fn_general->clear_null($dataLocal['wo_task_assigned_to']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['woTaskSeverity'] = $arrSeverity[intval($this->fn_general->clear_null($dataLocal['wo_task_severity'], '0'))];
                $row_result['woTaskRepairDesc'] = $this->fn_general->clear_null($dataLocal['wo_task_repair_desc']);
                $row_result['woTaskRateOri'] = $this->fn_general->clear_null($dataLocal['wo_task_rate']);
                $row_result['woTaskRate'] = empty($dataLocal['wo_task_rate']) ? '' : $dataLocal['wo_task_rate'].' / 5';
                $row_result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
                $row_result['pdfIdWr'] = $this->fn_general->clear_null($dataLocal['pdf_id_wr']);
                $row_result['woTaskCreatedBy'] = $dataLocal['wo_task_created_by'];
                $row_result['woTaskFixedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_fixed_by']);
                $row_result['woTaskAssignedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_assigned_by']);
                $row_result['woTaskVerifiedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_verified_by']);
                $row_result['woTaskTimeCreated'] = str_replace('-', '/', $dataLocal['wo_task_time_created']);
                $row_result['woTaskTimeResponded'] = str_replace('-', '/', $dataLocal['wo_task_time_responded']);
                $row_result['woTaskTimeAssigned'] = str_replace('-', '/', $dataLocal['wo_task_time_assigned']);
                $row_result['woTaskTimeWrChecked'] = str_replace('-', '/', $dataLocal['wo_task_time_wr_checked']);
                $row_result['woTaskTimeWrVerified'] = str_replace('-', '/', $dataLocal['wo_task_time_wr_verified']);
                $row_result['woTaskTimeExecuted'] = str_replace('-', '/', $dataLocal['wo_task_time_executed']);
                $row_result['woTaskTimeVerified'] = str_replace('-', '/', $dataLocal['wo_task_time_verified']);
                $row_result['durationResponded'] = $this->fn_general->timeDiff($row_result['woTaskTimeCreated'], ($row_result['woTaskIsWr'] === '1' ? $row_result['woTaskTimeWrChecked'] : $row_result['woTaskTimeAssigned']));
                $row_result['woTaskStatus'] = $dataLocal['wo_task_status'];
                $row_result['kpiResponseResult'] = '';
                $durationResponded = $this->fn_general->timeDiffMinute($row_result['woTaskTimeCreated'], ($row_result['woTaskIsWr'] === '1' ? $row_result['woTaskTimeWrChecked'] : $row_result['woTaskTimeAssigned']));
                if ($durationResponded !== '') {
                    if ($dataLocal['wo_task_severity'] === '5') {
                        $row_result['kpiResponseResult'] = $durationResponded <= 15 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '4') {
                        $row_result['kpiResponseResult'] = $durationResponded <= 15 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '3') {
                        $row_result['kpiResponseResult'] = $durationResponded <= 30 ? 'Success' : 'Fail';
                    }
                }
                $row_result['durationMitigated'] = $this->fn_general->timeDiff($row_result['woTaskTimeCreated'], $row_result['woTaskTimeExecuted']);
                $row_result['kpiMitigateResult'] = '';
                $durationMitigated = $this->fn_general->timeDiffHour($row_result['woTaskTimeCreated'], $row_result['woTaskTimeExecuted']);
                if ($durationMitigated !== '') {
                    if ($dataLocal['wo_task_severity'] === '5') {
                        $row_result['kpiMitigateResult'] = $durationMitigated <= 3 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '4') {
                        $row_result['kpiMitigateResult'] = $durationMitigated <= 24 ? 'Success' : 'Fail';
                    } else if ($dataLocal['wo_task_severity'] === '3') {
                        $row_result['kpiMitigateResult'] = $durationMitigated <= 168 ? 'Success' : 'Fail';
                    }
                }
                $row_result['assistants'] = $dataLocal['assistants'];
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetNo'] = $dataLocal['asset_no'];
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
     * @param $siteId
     * @return array
     * @throws Exception
     */
    public function get_severity_list_by_site ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }

            $clientId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'client_id', null, 1);
            $arrSeverity = $this->fn_general->getSeverityName();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_client_severity', array('client_id'=>$clientId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['clientSeverityId'] = $dataLocal['client_severity_id'];
                $row_result['clientId'] = $dataLocal['client_id'];
                $row_result['severityId'] = $dataLocal['severity_id'];
                $row_result['severityName'] = $arrSeverity[intval($dataLocal['severity_id'])];
                $row_result['severityHour'] = $dataLocal['client_severity_hour'];
                $row_result['severityRespondTime'] = $dataLocal['client_severity_respond_time'];
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
     * @param $ppmGroupId
     * @return array
     * @throws Exception
     */
    public function get_ppm_group_user_list ($ppmGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            }

            $attTypeArr = Class_db::getInstance()->db_select_cols('att_type', array('att_type_id', 'att_type_name'), array('att_type_mode'=>"('Leave','Training')", 'att_type_status'=>'1'));
            $arrUser = $this->fn_general->getUserFullName();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_group_user', array('ppm_group_id'=>$ppmGroupId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmGroupUserId'] = $dataLocal['ppm_group_user_id'];
                $row_result['ppmGroupId'] = $dataLocal['ppm_group_id'];
                $row_result['userId'] = $dataLocal['user_id'];
                $row_result['userFirstName'] = $arrUser[intval($dataLocal['user_id'])];
                $attendance = Class_db::getInstance()->db_select_single('att_transaction', array('user_id'=>$dataLocal['user_id'], 'att_transaction_date'=>'Curdate()'));
                if (!empty($attendance) && isset($attTypeArr[$attendance['att_type_id']])) {
                    $row_result['userFirstName'] .= ' ('.$attTypeArr[$attendance['att_type_id']].')';
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
     * @param bool $isPending
     * @return array
     * @throws Exception
     */
    public function get_helpdesk_list ($isPending) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$this->userId), 'site_id', null, 1);
            $arrWhere = array('site_id'=>$siteId);
            if ($isPending === '1') {
                $arrWhere['wo_task_status'] = 'N(16, 25, 30)';
            } else if ($isPending === '2') {
                $arrWhere['wo_task_status'] = '(16, 25, 30)';
            }

            $arrSeverity = $this->fn_general->getSeverityName();
            $arrWoType = $this->get_wo_type();

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('wo_task', $arrWhere);
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['woTaskId'] = $dataLocal['wo_task_id'];
                $row_result['woTaskNo'] = $dataLocal['wo_task_is_wr'] === '1' && $dataLocal['wo_task_wr_confirm'] !== '1' ? '-' : $dataLocal['wo_task_no'];
                $row_result['woTaskRequestNo'] = $this->fn_general->clear_null($dataLocal['wo_task_request_no'], '-');
                $row_result['woTaskType'] = $this->fn_general->clear_null($dataLocal['wo_task_type'], '0');
                $row_result['woTaskTypeDesc'] = $arrWoType[intval($this->fn_general->clear_null($dataLocal['wo_task_type'], '0'))];
                $row_result['woTaskIsWr'] = $dataLocal['wo_task_is_wr'];
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['woTaskLocation'] = $this->fn_general->clear_null($dataLocal['wo_task_location']);
                $row_result['woTaskComplaint'] = $this->fn_general->clear_null($dataLocal['wo_task_complaint']);
                $row_result['woTaskAssignedTo'] = $this->fn_general->clear_null($dataLocal['wo_task_assigned_to']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['woTaskSeverity'] = $arrSeverity[intval($this->fn_general->clear_null($dataLocal['wo_task_severity'], '0'))];
                $row_result['woTaskRepairDesc'] = $this->fn_general->clear_null($dataLocal['wo_task_repair_desc']);
                $row_result['woTaskRate'] = empty($dataLocal['wo_task_rate']) ? '' : $dataLocal['wo_task_rate'].' / 5';
                $row_result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
                $row_result['pdfIdWr'] = $this->fn_general->clear_null($dataLocal['pdf_id_wr']);
                $row_result['woTaskCreatedBy'] = $dataLocal['wo_task_created_by'];
                $row_result['woTaskFixedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_fixed_by']);
                $row_result['woTaskAssignedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_assigned_by']);
                $row_result['woTaskVerifiedBy'] = $this->fn_general->clear_null($dataLocal['wo_task_verified_by']);
                $row_result['woTaskTimeCreated'] = str_replace('-', '/', $dataLocal['wo_task_time_created']);
                $row_result['woTaskTimeResponded'] = str_replace('-', '/', $dataLocal['wo_task_time_responded']);
                $row_result['woTaskTimeAssigned'] = str_replace('-', '/', $dataLocal['wo_task_time_assigned']);
                $row_result['woTaskTimeWrChecked'] = str_replace('-', '/', $dataLocal['wo_task_time_wr_checked']);
                $row_result['woTaskTimeWrVerified'] = str_replace('-', '/', $dataLocal['wo_task_time_wr_verified']);
                $row_result['woTaskTimeExecuted'] = str_replace('-', '/', $dataLocal['wo_task_time_executed']);
                $row_result['woTaskTimeVerified'] = str_replace('-', '/', $dataLocal['wo_task_time_verified']);
                $row_result['woTaskStatus'] = $dataLocal['wo_task_status'];
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
     * @param string $dateStart
     * @param string $dateEnd
     * @return mixed
     * @throws Exception
     */
    public function get_total_wo_by_site_status ($clientId='', $dateStart='', $dateEnd='') {
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
                array('name'=>'Completed', 'woTaskStatus'=>'16', 'data'=>$dataEmpty),
                array('name'=>'Responding', 'woTaskStatus'=>'24|26', 'data'=>$dataEmpty),
                array('name'=>'In Progress', 'woTaskStatus'=>'13|21', 'data'=>$dataEmpty),
                array('name'=>'Verify', 'woTaskStatus'=>'15', 'data'=>$dataEmpty),
                array('name'=>'Cancelled', 'woTaskStatus'=>'25', 'data'=>$dataEmpty)
            );
            if (!empty($siteIds)) {
                $siteIdStr = implode(',', $siteIds);
                $woBySites = Class_db::getInstance()->db_select('vg_count_wo_by_site_status', array('site_id'=>'('.$siteIdStr.')'), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
                foreach ($woBySites as $woBySite) {
                    $status = $woBySite['wo_task_status'];
                    $total = $woBySite['total'];
                    $siteIndex = array_search($woBySite['site_id'], $siteIds);
                    if ($status === '16') {
                        $series[0]['data'][$siteIndex] = intval($total);
                    } else if ($status === '24' || $status === '26') {
                        $series[1]['data'][$siteIndex] += intval($total);
                    } else if ($status === '13' || $status === '21') {
                        $series[2]['data'][$siteIndex] += intval($total);
                    } else if ($status === '15') {
                        $series[3]['data'][$siteIndex] = intval($total);
                    } else if ($status === '25') {
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
     * @return array
     * @throws Exception
     */
    public function get_total_wo_by_site_type ($clientId='', $dateStart='', $dateEnd='') {
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
                array('name'=>'Complaint', 'woTaskType'=>'1', 'data'=>$dataEmpty),
                array('name'=>'Finding', 'woTaskType'=>'2', 'data'=>$dataEmpty),
                array('name'=>'Request', 'woTaskType'=>'3', 'data'=>$dataEmpty),
                array('name'=>'Breakdown', 'woTaskType'=>'4', 'data'=>$dataEmpty),
                array('name'=>'Defect', 'woTaskType'=>'5', 'data'=>$dataEmpty),
                array('name'=>'Public Complaint', 'woTaskType'=>'6', 'data'=>$dataEmpty)
            );
            if (!empty($siteIds)) {
                $siteIdStr = implode(',', $siteIds);
                $woByTypes = Class_db::getInstance()->db_select('vg_count_wo_by_site_type', array('site_id'=>'('.$siteIdStr.')'), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
                foreach ($woByTypes as $woByType) {
                    $woType = $woByType['wo_task_type'];
                    $total = $woByType['total'];
                    $siteIndex = array_search($woByType['site_id'], $siteIds);
                    if ($woType === '1') {
                        $series[0]['data'][$siteIndex] = intval($total);
                    } else if ($woType === '2') {
                        $series[1]['data'][$siteIndex] += intval($total);
                    } else if ($woType === '3') {
                        $series[2]['data'][$siteIndex] += intval($total);
                    } else if ($woType === '4') {
                        $series[3]['data'][$siteIndex] = intval($total);
                    } else if ($woType === '5') {
                        $series[4]['data'][$siteIndex] = intval($total);
                    } else if ($woType === '6') {
                        $series[5]['data'][$siteIndex] = intval($total);
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
    public function get_total_wo_by_type ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
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
                array('name'=>'Complaint', 'woTaskType'=>'1', 'y'=>0, 'sliced'=>true, 'selected'=>true),
                array('name'=>'Finding', 'woTaskType'=>'2', 'y'=>0),
                array('name'=>'Request', 'woTaskType'=>'3', 'y'=>0),
                array('name'=>'Breakdown', 'woTaskType'=>'4', 'y'=>0),
                array('name'=>'Defect', 'woTaskType'=>'5', 'y'=>0),
                array('name'=>'Public Complaint', 'woTaskType'=>'6', 'y'=>0)
            );
            $woByTypes = Class_db::getInstance()->db_select('vg_count_wo_by_site_type', array('site_id'=>$siteId), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($woByTypes as $woByType) {
                $woType = $woByType['wo_task_type'];
                $total = $woByType['total'];
                if ($woType === '1') {
                    $series[0]['y'] += intval($total);
                } else if ($woType === '2') {
                    $series[1]['y'] += intval($total);
                } else if ($woType === '3') {
                    $series[2]['y'] += intval($total);
                } else if ($woType === '4') {
                    $series[3]['y'] += intval($total);
                } else if ($woType === '5') {
                    $series[4]['y'] += intval($total);
                } else if ($woType === '6') {
                    $series[5]['y'] += intval($total);
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
    public function get_total_wo_by_status ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
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

            $categories = array('Completed', 'Responding', 'In Progress', 'Verify', 'Cancelled');
            $data = array(
                array('y'=>0, 'woTaskStatus'=>'16'),
                array('y'=>0, 'woTaskStatus'=>'24|26'),
                array('y'=>0, 'woTaskStatus'=>'13|21'),
                array('y'=>0, 'woTaskStatus'=>'15'),
                array('y'=>0, 'woTaskStatus'=>'25')
            );
            $woByStatus = Class_db::getInstance()->db_select('vg_count_wo_by_site_status', array('site_id'=>$siteId), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($woByStatus as $woStatus) {
                $status = $woStatus['wo_task_status'];
                $total = $woStatus['total'];
                if ($status === '16') {
                    $data[0]['y'] += intval($total);
                } else if ($status === '24' || $status === '26') {
                    $data[1]['y'] += intval($total);
                } else if ($status === '13' || $status === '21') {
                    $data[2]['y'] += intval($total);
                } else if ($status === '15') {
                    $data[3]['y'] += intval($total);
                } else if ($status === '25') {
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
    public function get_total_wo_by_group ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
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

            $groups = array();
            $series = array();
            $woByGroups = Class_db::getInstance()->db_select('vg_count_wo_by_site_group', array('site_id'=>$siteId), null, null, null, array('date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($woByGroups as $woByGroup) {
                $ppmGroupId = $woByGroup['ppm_group_id'];
                $ppmGroupName = $woByGroup['ppm_group_name'];
                $total = $woByGroup['total'];
                if ($ppmGroupName !== null) {
                    if (!in_array($ppmGroupName, $groups)) {
                        array_push($groups, $ppmGroupName);
                        if (count($series) === 0) {
                            array_push($series, array('name' => $ppmGroupName, 'ppmGroupId' => $ppmGroupId, 'y' => intval($total), 'sliced' => true, 'selected' => true));
                        } else {
                            array_push($series, array('name' => $ppmGroupName, 'ppmGroupId' => $ppmGroupId, 'y' => intval($total)));
                        }
                    } else {
                        $groupIndex = array_search($ppmGroupName, $groups);
                        $series[$groupIndex]['ppmGroupId'] .= '|'.$ppmGroupId;
                        $series[$groupIndex]['y'] += intval($total);
                    }
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
    public function get_wo_top5_execute ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
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

            $woByTop5Executes = Class_db::getInstance()->db_select('vg_wo_top5_execute', array(), null, null, null, array('site_id'=>$siteId, 'date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($woByTop5Executes as $key => $woByTop5Execute) {
                array_push($categories, $arrUserFullName[intval($woByTop5Execute['wo_task_fixed_by'])]);
                array_push($data,
                    array(
                        'y'=>intval($woByTop5Execute['total']),
                        'woTaskFixedBy'=>$woByTop5Execute['wo_task_fixed_by'],
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
    public function get_wo_bottom5_execute ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
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

            $woByBottom5Executes = Class_db::getInstance()->db_select('vg_wo_bottom5_execute', array(), 'total DESC', null, null, array('site_id'=>$siteId, 'date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($woByBottom5Executes as $key => $woByBottom5Execute) {
                array_push($categories, $arrUserFullName[intval($woByBottom5Execute['wo_task_fixed_by'])]);
                array_push($data,
                    array(
                        'y'=>intval($woByBottom5Execute['total']),
                        'woTaskFixedBy'=>$woByBottom5Execute['wo_task_fixed_by'],
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
    public function get_wo_average_execute_by_trade ($clientId='', $siteId='', $dateStart='', $dateEnd='') {
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

            $woByAverageExecutes = Class_db::getInstance()->db_select('vg_wo_average_execute_by_trade', array(), null, null, null, array('site_id'=>$siteId, 'date_start'=>$dateStart, 'date_end'=>$dateEnd));
            foreach ($woByAverageExecutes as $woByAverageExecute) {
                array_push($categories, $woByAverageExecute['ppm_group_name']);
                array_push($data,
                    array(
                        'y'=>doubleval($woByAverageExecute['total']),
                        'display'=>substr($woByAverageExecute['display'], 0, 8),
                        'ppmGroupName'=>$woByAverageExecute['ppm_group_name']
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
     * @param string $year
     * @param string $month
     * @return mixed
     * @throws Exception
     */
    public function get_report_wo_summary ($clientId='', $year='', $month='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (empty($year)) {
                throw new Exception('[' . __LINE__ . '] - Parameter year empty');
            }
            if ($month === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter month empty');
            }

            $sumSiteStr = '';
            $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
            foreach ($siteIds as $siteId) {
                $sumSiteStr .= ', SUM(IF(wo_task.site_id = '.$siteId.', 1, 0)) AS open'.$siteId;
                $sumSiteStr .= ', SUM(IF(wo_task.site_id = '.$siteId.' AND wo_task_status IN (16, 25), 1, 0)) AS closed'.$siteId;
            }

            $result = array();
            $reportDatas = Class_db::getInstance()->db_select('vg_report_wo_summary', array(), null, null, null, array('client_id'=>$clientId, 'selected_year'=>$year, 'selected_month'=>$month, 'sum_site_str'=>$sumSiteStr));
            foreach ($reportDatas as $reportData) {
                $row_result['woTaskType'] = $reportData['task_type'];
                foreach ($siteIds as $siteId) {
                    $row_result['open'.$siteId] = $reportData['open'.$siteId];
                    $row_result['closed'.$siteId] = $reportData['closed'.$siteId];
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
     * @param $woTaskId
     * @return
     * @throws Exception
     */
    public function delete_wo ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$woTaskId), null, 1);
            if (!empty($woTask['pdf_id'])) {
                Class_db::getInstance()->db_update('sys_pdf', array('pdf_status'=>'6'), array('pdf_id'=>$woTask['pdf_id']));
            }
            $woTaskUploads = Class_db::getInstance()->db_select('wo_task_upload', array('wo_task_id'=>$woTaskId));
            foreach ($woTaskUploads as $woTaskUpload) {
                Class_db::getInstance()->db_update('sys_upload', array('upload_status'=>'6'), array('upload_id'=>$woTaskUpload['upload_id']));
            }

            Class_db::getInstance()->db_delete('wo_task_assist', array('wo_task_id'=>$woTaskId));
            Class_db::getInstance()->db_delete('wo_task_upload', array('wo_task_id'=>$woTaskId));
            Class_db::getInstance()->db_delete('wfl_task_assign', array('transaction_id'=>$woTask['transaction_id']));
            Class_db::getInstance()->db_delete('wfl_task', array('transaction_id'=>$woTask['transaction_id']));
            Class_db::getInstance()->db_delete('wo_task', array('wo_task_id'=>$woTaskId));
            Class_db::getInstance()->db_delete('wfl_transaction', array('transaction_id'=>$woTask['transaction_id']));

            return $woTask['wo_task_no'];
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $year
     * @param string $month
     * @return array
     * @throws Exception
     */
    public function get_report_wo_total ($year='', $month='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($year)) {
                throw new Exception('[' . __LINE__ . '] - Parameter year empty');
            }
            if ($month === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter month empty');
            }

            $result = array();
            $reportDatas = Class_db::getInstance()->db_select('vg_report_wo_total', array(), null, null, null, array('selected_year'=>$year, 'selected_month'=>$month));
            foreach ($reportDatas as $reportData) {
                $row_result['siteId'] = $reportData['site_id'];
                $row_result['siteName'] = $reportData['site_name'];
                $row_result['isManual'] = false;
                $row_result['open0'] = '0';
                $row_result['closed0'] = '0';
                for ($i=1; $i<=5; $i++) {
                    $row_result['open'.$i] = $reportData['open'.$i];
                    $row_result['closed'.$i] = $reportData['closed'.$i];
                }
                array_push($result, $row_result);
            }

            $reportPpms = Class_db::getInstance()->db_select('vg_report_ppm_total', array(), null, null, null, array('selected_year'=>$year, 'selected_month'=>$month));
            foreach ($reportPpms as $reportPpm) {
                foreach ($result as $key => $row) {
                    if ($row['siteName'] === $reportPpm['site_name']) {
                        $result[$key]['open0'] = $reportPpm['total_ppm_not'];
                        $result[$key]['closed0'] = $reportPpm['total_ppm_done'];
                        break;
                    }
                }
            }

            $reportManuals = Class_db::getInstance()->db_select('vg_report_site_manual', array(), null, null, null, array('selected_year'=>$year, 'selected_month'=>$month));
            foreach ($reportManuals as $reportManual) {
                $row_result['siteId'] = $reportManual['site_id'];
                $row_result['siteName'] = $reportManual['site_name'];
                $row_result['isManual'] = true;
                for ($i=0; $i<=5; $i++) {
                    $row_result['open'.$i] = $this->fn_general->clear_null($reportManual['total_manual_open'.$i], 0);
                    $row_result['closed'.$i] = $this->fn_general->clear_null($reportManual['total_manual_closed'.$i], 0);
                }
                array_push($result, $row_result);
            }

            $row_result['siteId'] = '';
            $row_result['siteName'] = 'TOTAL';
            $row_result['isManual'] = false;
            $row_pending['siteId'] = '';
            $row_pending['siteName'] = 'PENDING';
            $row_pending['isManual'] = false;
            for ($i=0; $i<=5; $i++) {
                $row_result['open'.$i] = 0;
                $row_result['closed'.$i] = 0;
                $row_pending['open'.$i] = '';
                $row_pending['closed'.$i] = 0;
            }

            foreach ($result as $row) {
                for ($i=0; $i<=5; $i++) {
                    $row_result['open'.$i] += $row['open'.$i];
                    $row_result['closed'.$i] += $row['closed'.$i];
                }
            }
            for ($i=0; $i<=5; $i++) {
                $row_pending['closed'.$i] = intval($row_result['open'.$i]) - intval($row_result['closed'.$i]);
            }
            array_push($result, $row_result);
            array_push($result, $row_pending);

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @param $isManual
     * @param string $year
     * @param string $month
     * @return array
     * @throws Exception
     */
    public function get_report_wo_daily ($siteId, $isManual, $year='', $month='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($year)) {
                throw new Exception('[' . __LINE__ . '] - Parameter year empty');
            }
            if ($month === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter month empty');
            }

            $result = array();
            if ($isManual == 'true') {
                $reportManuals = Class_db::getInstance()->db_select('cli_site_manual', array('site_id'=>$siteId, 'YEAR(site_manual_date)'=>$year, 'MONTH(site_manual_date)'=>$month));
                foreach ($reportManuals as $reportManual) {
                    $row_result['siteManualId'] = $reportManual['site_manual_id'];
                    $row_result['siteManualDate'] = $this->fn_general->convertDateToDisplay($reportManual['site_manual_date']);
                    for ($i=0; $i<=5; $i++) {
                        $row_result['open'.$i] = $reportManual['site_manual_open'.$i];
                        $row_result['closed'.$i] = $reportManual['site_manual_closed'.$i];
                    }
                    array_push($result, $row_result);
                }
            } else {
                $reportManuals = Class_db::getInstance()->db_select('vg_report_wo_daily', array(), null, null, null, array('site_id'=>$siteId, 'selected_year'=>$year, 'selected_month'=>$month));
                foreach ($reportManuals as $reportManual) {
                    $row_result['siteManualId'] = '';
                    $row_result['siteManualDate'] = $this->fn_general->convertDateToDisplay($reportManual['dates']);
                    for ($i=0; $i<=5; $i++) {
                        $row_result['open'.$i] = $reportManual['combine_open'.$i];
                        $row_result['closed'.$i] = $reportManual['combine_closed'.$i];
                    }
                    array_push($result, $row_result);
                }
            }

            $row_result['siteManualId'] = '';
            $row_result['siteManualDate'] = 'TOTAL';
            $row_pending['siteManualId'] = '';
            $row_pending['siteManualDate'] = 'PENDING';
            for ($i=0; $i<=5; $i++) {
                $row_result['open'.$i] = 0;
                $row_result['closed'.$i] = 0;
                $row_pending['open'.$i] = '';
                $row_pending['closed'.$i] = 0;
            }
            foreach ($result as $row) {
                for ($i=0; $i<=5; $i++) {
                    $row_result['open'.$i] += $row['open'.$i];
                    $row_result['closed'.$i] += $row['closed'.$i];
                }
            }
            for ($i=0; $i<=5; $i++) {
                $row_pending['closed'.$i] = intval($row_result['open'.$i]) - intval($row_result['closed'.$i]);
            }
            array_push($result, $row_result);
            array_push($result, $row_pending);

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return string
     * @throws Exception
     */
    public function add_siteManual ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }

            if (!array_key_exists('siteId', $params) || empty($params['siteId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (!array_key_exists('selectedDate', $params) || empty($params['selectedDate'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter selectedDate empty');
            }
            if (!array_key_exists('selectedMonth', $params) || empty($params['selectedMonth'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter selectedMonth empty');
            }
            if (!array_key_exists('selectedYear', $params) || empty($params['selectedYear'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter selectedYear empty');
            }
            if (!array_key_exists('open0', $params) || $params['open0'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open0 empty');
            }
            if (!array_key_exists('closed0', $params) || $params['closed0'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed0 empty');
            }
            if (!array_key_exists('open1', $params) || $params['open1'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open1 empty');
            }
            if (!array_key_exists('closed1', $params) || $params['closed1'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed1 empty');
            }
            if (!array_key_exists('open2', $params) || $params['open2'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open2 empty');
            }
            if (!array_key_exists('closed2', $params) || $params['closed2'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed2 empty');
            }
            if (!array_key_exists('open3', $params) || $params['open3'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open3 empty');
            }
            if (!array_key_exists('closed3', $params) || $params['closed3'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed3 empty');
            }
            if (!array_key_exists('open4', $params) || $params['open4'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open4 empty');
            }
            if (!array_key_exists('closed4', $params) || $params['closed4'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed4 empty');
            }
            if (!array_key_exists('open5', $params) || $params['open5'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open5 empty');
            }
            if (!array_key_exists('closed5', $params) || $params['closed5'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed5 empty');
            }

            $siteId = $params['siteId'];
            $selectedDates = $params['selectedYear'].'-'.$params['selectedMonth'].'-'.$params['selectedDate'];
            $open0 = $params['open0'];
            $closed0 = $params['closed0'];
            $open1 = $params['open1'];
            $closed1 = $params['closed1'];
            $open2 = $params['open2'];
            $closed2 = $params['closed2'];
            $open3 = $params['open3'];
            $closed3 = $params['closed3'];
            $open4 = $params['open4'];
            $closed4 = $params['closed4'];
            $open5 = $params['open5'];
            $closed5 = $params['closed5'];

            $siteManualId = '';
            if (Class_db::getInstance()->db_count('cli_site_manual', array('site_id'=>$siteId, 'site_manual_date'=>$selectedDates)) > 0) {
                Class_db::getInstance()->db_update('cli_site_manual', array('site_manual_open0' => $open0, 'site_manual_closed0' => $closed0, 'site_manual_open1' => $open1, 'site_manual_closed1' => $closed1, 'site_manual_open2' => $open2, 'site_manual_closed2' => $closed2,
                    'site_manual_open3' => $open3, 'site_manual_closed3' => $closed3, 'site_manual_open4' => $open4, 'site_manual_closed4' => $closed4, 'site_manual_open5' => $open5, 'site_manual_closed5' => $closed5), array('site_id'=>$siteId, 'site_manual_date'=>$selectedDates));
            } else {
                $siteManualId = Class_db::getInstance()->db_insert('cli_site_manual', array('site_id'=>$siteId, 'site_manual_date'=>$selectedDates, 'site_manual_open0' => $open0, 'site_manual_closed0' => $closed0, 'site_manual_open1' => $open1, 'site_manual_closed1' => $closed1, 'site_manual_open2' => $open2, 'site_manual_closed2' => $closed2,
                    'site_manual_open3' => $open3, 'site_manual_closed3' => $closed3, 'site_manual_open4' => $open4, 'site_manual_closed4' => $closed4, 'site_manual_open5' => $open5, 'site_manual_closed5' => $closed5));
            }
            return $siteManualId;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteManualId
     * @param $put_vars
     * @throws Exception
     */
    public function update_siteManual ($siteManualId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($siteManualId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteManualId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['open0']) || $put_vars['open0'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open0 empty');
            }
            if (!isset($put_vars['closed0']) || $put_vars['closed0'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed0 empty');
            }
            if (!isset($put_vars['open1']) || $put_vars['open1'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open1 empty');
            }
            if (!isset($put_vars['closed1']) || $put_vars['closed1'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed1 empty');
            }
            if (!isset($put_vars['open2']) || $put_vars['open2'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open2 empty');
            }
            if (!isset($put_vars['closed2']) || $put_vars['closed2'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed2 empty');
            }
            if (!isset($put_vars['open3']) || $put_vars['open3'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open3 empty');
            }
            if (!isset($put_vars['closed3']) || $put_vars['closed3'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed3 empty');
            }
            if (!isset($put_vars['open4']) || $put_vars['open4'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open4 empty');
            }
            if (!isset($put_vars['closed4']) || $put_vars['closed4'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed4 empty');
            }
            if (!isset($put_vars['open5']) || $put_vars['open5'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter open5 empty');
            }
            if (!isset($put_vars['closed5']) || $put_vars['closed5'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter closed5 empty');
            }

            $open0 = $put_vars['open0'];
            $closed0 = $put_vars['closed0'];
            $open1 = $put_vars['open1'];
            $closed1 = $put_vars['closed1'];
            $open2 = $put_vars['open2'];
            $closed2 = $put_vars['closed2'];
            $open3 = $put_vars['open3'];
            $closed3 = $put_vars['closed3'];
            $open4 = $put_vars['open4'];
            $closed4 = $put_vars['closed4'];
            $open5 = $put_vars['open5'];
            $closed5 = $put_vars['closed5'];

            Class_db::getInstance()->db_update('cli_site_manual', array('site_manual_open0'=>$open0, 'site_manual_closed0'=>$closed0, 'site_manual_open1'=>$open1, 'site_manual_closed1'=>$closed1, 'site_manual_open2'=>$open2, 'site_manual_closed2'=>$closed2,
                'site_manual_open3'=>$open3, 'site_manual_closed3'=>$closed3, 'site_manual_open4'=>$open4, 'site_manual_closed4'=>$closed4, 'site_manual_open5'=>$open5, 'site_manual_closed5'=>$closed5), array('site_manual_id'=>$siteManualId));
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
    public function get_wo_severity_list_m () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }

            $result = array();
            $arrSeverity = $this->fn_general->getSeverityName();
            $siteId = Class_db::getInstance()->db_select_col('wo_task', array('wo_task_id'=>$this->woTaskId), 'site_id', null, 1);
            $clientId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'client_id', null, 1);
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_client_severity', array('client_id'=>$clientId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['severityId'] = $dataLocal['severity_id'];
                $row_result['severityName'] = $arrSeverity[intval($dataLocal['severity_id'])];
                array_push($result, $row_result);
            }
            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_wo_is_wr () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId) && empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId and userId empty');
            }

            if (!empty($this->woTaskId)) {
                $woTask = Class_db::getInstance()->db_select_single('wo_task', array('wo_task_id'=>$this->woTaskId), null, 1);
                $checkpointId = Class_db::getInstance()->db_select_col('wfl_task', array('transaction_id'=>$woTask['transaction_id'], 'task_current'=>'1'), 'checkpoint_id', null, 1);
                return $woTask['wo_task_is_wr'] === '1' && ($checkpointId === '11' || $checkpointId === '17' || $checkpointId === '18' || $checkpointId === '19') ? '1' : '0';
            } else {
				$siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$this->userId), 'site_id', null, 1);
                return Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_is_wr', null, 1);
            }
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetNo
     * @return mixed
     * @throws Exception
     */
    public function save_asset_no_m ($assetNo) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($this->woTaskId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter woTaskId empty');
            }
            if (empty($this->userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            $assetId = '';
            if (!empty($assetNo)) {
                $siteId = Class_db::getInstance()->db_select_col('sys_user', array('user_id'=>$this->userId), 'site_id', null, 1);
                $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_no'=>$assetNo));
                if (empty($asset)) {
                    throw new Exception('[' . __LINE__ . '] - Asset No not exist', 31);
                }
                $contractId = Class_db::getInstance()->db_select_col('cli_contract', array('site_id'=>$siteId), 'contract_id', null, 1);
                if ($contractId !== $asset['contract_id']) {
                    throw new Exception('[' . __LINE__ . '] - Asset No not exist in your site', 31);
                }
                $assetId = $asset['asset_id'];
            }

            Class_db::getInstance()->db_update('wo_task', array('asset_id'=>$assetId, 'wo_task_done_asset'=>'1'), array('wo_task_id'=>$this->woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $userTechId
     * @return array
     * @throws Exception
     */
    public function get_technician_current_task ($userTechId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            if (empty($userTechId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userTechId empty');
            }

            $result = array();

            $woTasks = Class_db::getInstance()->db_select('wo_task', array('wo_task_assigned_to'=>$userTechId, 'wo_task_status'=>'(13, 27)'));
            foreach ($woTasks as $woTask) {
                $row_result['woTaskNo'] = $woTask['wo_task_no'];
                $row_result['dateReceived'] = str_replace('-', '/', $this->fn_general->clear_null($woTask['wo_task_time_assigned']));
                if (!empty($row_result['dateReceived'])) {
                    $row_result['dateReceived'] = substr($row_result['dateReceived'], 0, 10);
                }
                array_push($result, $row_result);
            }
            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getExecutionInfo ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($woTaskId));
            $respondTimeStr = '';
            $executionTimeStr = '';
            $respondTimeDisplay = '';
            $completionTimeDisplay = '';
            $woExecuteTime = '';
            $isRespondTimeExceeded = false;
            $isExecutionTimeExceeded = false;
            $now = new DateTime();
			$woAssignTime = '';

            $woTask = Class_db::getInstance()->db_select_single2('wo_task', array('wo_task_id'=>$woTaskId));
            if ($woTask['woTaskSeverity'] !== '' && $woTask['woTaskTimeCreated'] !== '') {
                $clientId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$woTask['siteId']), 'client_id');
                $severity = Class_db::getInstance()->db_select_single2('cli_client_severity', array('client_id'=>$clientId, 'severity_id'=>$woTask['woTaskSeverity']));
                $respondMinute = intval($severity['clientSeverityRespondTime']);
                $respondTimeStr = $respondMinute <= 1 ? $respondMinute.' minute' : $respondMinute.' minutes';
                $executionHours = intval($severity['clientSeverityHour']);
                $executionTimeStr = $executionHours <= 1 ? $executionHours.' hour' : $executionHours.' hours';

                $respondTime = new DateTime($woTask['woTaskTimeCreated']);
                $respondTime->modify($respondTimeStr);
                $respondTimeDisplay = $respondTime->format('Y-m-d H:i:s');

                $woAssignTime = $woTask['woTaskTimeAssigned'];
                $completionTime = new DateTime($woAssignTime);
                if (!empty($woAssignTime)) {
                    $isRespondTimeExceeded = $completionTime > $respondTime;
                } else {
                    $isRespondTimeExceeded = $now > $respondTime;
                }

                $woExecuteTime = $woTask['woTaskTimeExecuted'];
                $executeTime = new DateTime($woExecuteTime);
                if ($woTask['woTaskType'] === '2' && !empty($woAssignTime)) {
                    $completionTime = new DateTime($woAssignTime);
                    $completionTime->modify($executionTimeStr);
                    $completionTimeDisplay = $completionTime->format('Y-m-d H:i:s');
                    $isExecutionTimeExceeded = $woExecuteTime !== '' ? $executeTime > $completionTime : $now > $completionTime;
                } else if ($woTask['woTaskType'] !== '2') {
                    $completionTime = new DateTime($woTask['woTaskTimeCreated']);
                    $completionTime->modify($executionTimeStr);
                    $completionTimeDisplay = $completionTime->format('Y-m-d H:i:s');
                    $isExecutionTimeExceeded = $woExecuteTime !== '' ? $executeTime > $completionTime : $now > $completionTime;
                }
            }
            return array(
                'responseTimeSla'=>$respondTimeStr,
                'completionTimeSla'=>$executionTimeStr,
                'currentTime'=>$now->format('Y-m-d H:i:s'),
                'assignTime'=>$woAssignTime,
                'executeTime'=>$woExecuteTime,
                'responseTimeDue'=>$respondTimeDisplay,
                'completionTimeDue'=>$completionTimeDisplay,
                'responseTimeExceeded'=>$isRespondTimeExceeded,
                'completionTimeExceeded'=>$isExecutionTimeExceeded
            );
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $woTaskId
     * @return void
     * @throws Exception
     */
    public function saveWoTaskDoneAssistant ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($woTaskId));
            Class_db::getInstance()->db_update('wo_task', array('wo_task_done_assistant'=>'1'), array('wo_task_id'=>$woTaskId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}