<?php

class Class_checklist {

    private $constant;
    private $fn_general;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg,'-');
            if ($pos !== false) {
                $msg = substr($msg, $pos+2);
            }
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        } else {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."]";
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
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value ) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property ) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property ) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_checklist_by_type () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_checklist_by_type');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['checklistType'] = $dataLocal['checklist_types'];
                $row_result['assetGroupId'] = $dataLocal['asset_group_id'];
                $row_result['assetCategoryId'] = $dataLocal['asset_category_id'];
                $row_result['assetTypeId'] = $dataLocal['asset_type_id'];
                $row_result['totalChecklist'] = $this->fn_general->clear_null($dataLocal['total_checklist'], '0');
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $assetTypeId
     * @return array
     * @throws Exception
     */
    public function get_checklist_list ($assetTypeId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_checklist', array('asset_type_id'=>$assetTypeId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['checklistId'] = $dataLocal['checklist_id'];
                $row_result['checklistName'] = $this->fn_general->clear_null($dataLocal['checklist_name']);
                $row_result['checklistDocumentNo'] = $this->fn_general->clear_null($dataLocal['checklist_document_no']);
                $row_result['checklistIssueNo'] = $this->fn_general->clear_null($dataLocal['checklist_issue_no']);
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
                $row_result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
                $row_result['checklistMinExecTime'] = $this->fn_general->clear_null($dataLocal['checklist_min_exec_time']);
                $row_result['checklistMaxExecTime'] = $this->fn_general->clear_null($dataLocal['checklist_max_exec_time']);
                $row_result['checklistRegisteredBy'] = $this->fn_general->clear_null($dataLocal['checklist_registered_by']);
                $row_result['checklistTimeRegistered'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['checklist_time_registered']));
                $row_result['checklistTimeCreated'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['checklist_time_created']));
                $row_result['checklistStatus'] = $dataLocal['checklist_status'];
                array_push($result, $row_result);
            }

            return $result;
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    public function get_checklist ($checklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Array checklistId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $result['checklistId'] = $dataLocal['checklist_id'];
            $result['checklistName'] = $this->fn_general->clear_null($dataLocal['checklist_name']);
            $result['checklistDocumentNo'] = $this->fn_general->clear_null($dataLocal['checklist_document_no']);
            $result['checklistIssueNo'] = $this->fn_general->clear_null($dataLocal['checklist_issue_no']);
            $result['checklistDesc'] = $this->fn_general->clear_null($dataLocal['checklist_desc']);
            $result['checklistGuideline'] = $this->fn_general->clear_null($dataLocal['checklist_guideline']);
            $result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
            $result['pdfId'] = $this->fn_general->clear_null($dataLocal['pdf_id']);
            $result['checklistMinExecTime'] = $this->fn_general->clear_null($dataLocal['checklist_min_exec_time']);
            $result['checklistMaxExecTime'] = $this->fn_general->clear_null($dataLocal['checklist_max_exec_time']);
            $result['checklistTimeRegistered'] = str_replace('-', '/', $dataLocal['checklist_time_registered']);
            $result['checklistTimeCreated'] = str_replace('-', '/', $dataLocal['checklist_time_created']);
            $result['checklistRegisteredBy'] = $this->fn_general->clear_null($dataLocal['checklist_registered_by']);
            $result['checklistStatus'] = $dataLocal['checklist_status'];

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetTypeId
     * @return mixed
     * @throws Exception
     */
    public function create_checklist ($assetTypeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Array assetTypeId empty');
            }

            return Class_db::getInstance()->db_insert('ppm_checklist', array('asset_type_id'=>$assetTypeId, 'checklist_status'=>'5'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistId
     * @param $put_vars
     * @throws Exception
     */
    public function save_checklist ($checklistId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['checklistName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistName not exist');
            }
            if (!isset($put_vars['checklistDocumentNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistDocumentNo not exist');
            }
            if (!isset($put_vars['checklistIssueNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistIssueNo not exist');
            }
            if (!isset($put_vars['checklistDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistDesc not exist');
            }
            if (!isset($put_vars['checklistGuideline'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistGuideline not exist');
            }
            if (!isset($put_vars['checklistMinExecTimeHour']) || !isset($put_vars['checklistMinExecTimeMinute'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistMinExecTime not exist');
            }
            if (!isset($put_vars['checklistMaxExecTimeHour']) || !isset($put_vars['checklistMaxExecTimeMinute'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistMaxExecTime not exist');
            }

            $minExecTime = '';
            if ($put_vars['checklistMinExecTimeHour'] !== '' && $put_vars['checklistMinExecTimeMinute'] === '') {
                $minExecTime = $put_vars['checklistMinExecTimeHour'];
            } else if ($put_vars['checklistMinExecTimeHour'] !== '' && $put_vars['checklistMinExecTimeMinute'] !== '') {
                $minExecTime = $put_vars['checklistMinExecTimeHour'].':'.$put_vars['checklistMinExecTimeMinute'];
            }
            $maxExecTime = '';
            if ($put_vars['checklistMaxExecTimeHour'] !== '' && $put_vars['checklistMaxExecTimeMinute'] === '') {
                $maxExecTime = $put_vars['checklistMaxExecTimeHour'];
            } else if ($put_vars['checklistMaxExecTimeHour'] !== '' && $put_vars['checklistMaxExecTimeMinute'] !== '') {
                $maxExecTime = $put_vars['checklistMaxExecTimeHour'].':'.$put_vars['checklistMaxExecTimeMinute'];
            }
            $checklistName = $put_vars['checklistName'];
            $checklistDocumentNo = $put_vars['checklistDocumentNo'];
            $checklistIssueNo = $put_vars['checklistIssueNo'];
            $updateArr = array(
                'checklist_name'=>$checklistName,
                'checklist_document_no'=>$checklistDocumentNo,
                'checklist_issue_no'=>$checklistIssueNo,
                'checklist_desc'=>$put_vars['checklistDesc'],
                'checklist_guideline'=>$put_vars['checklistGuideline'],
                'checklist_min_exec_time'=>$minExecTime,
                'checklist_max_exec_time'=>$maxExecTime
            );

            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $assetTypeId = $checklist['asset_type_id'];

            if ($checklist['checklist_status'] != '5') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_SUBMITTED, 31);
            }
            if (!empty($checklistName) && !empty($checklistVersion) && Class_db::getInstance()->db_count('ppm_checklist', array('checklist_name'=>$checklistName, 'asset_type_id'=>$assetTypeId, 'checklist_id'=>'<>'.$checklistId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist', $updateArr, array('checklist_id'=>$checklistId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistId
     * @param $put_vars
     * @param $userId
     * @throws Exception
     */
    public function submit_checklist ($checklistId, $put_vars, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            if (!isset($put_vars['checklistName']) || empty($put_vars['checklistName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistName empty');
            }
            if (!isset($put_vars['checklistDocumentNo']) || empty($put_vars['checklistDocumentNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistDocumentNo empty');
            }
            if (!isset($put_vars['checklistIssueNo']) || empty($put_vars['checklistIssueNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistIssueNo empty');
            }
            if (!isset($put_vars['checklistDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistDesc not exist');
            }
            if (!isset($put_vars['checklistGuideline']) || empty($put_vars['checklistGuideline'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistGuideline empty');
            }
            if (!isset($put_vars['checklistMinExecTimeHour']) || !isset($put_vars['checklistMinExecTimeMinute'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistMinExecTime not exist');
            }
            if (!isset($put_vars['checklistMaxExecTimeHour']) || !isset($put_vars['checklistMaxExecTimeMinute'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistMaxExecTime not exist');
            }

            $checklistName = $put_vars['checklistName'];
            $checklistDocumentNo = $put_vars['checklistDocumentNo'];
            $checklistIssueNo = $put_vars['checklistIssueNo'];
            $updateArr = array(
                'checklist_name'=>$checklistName,
                'checklist_document_no'=>$checklistDocumentNo,
                'checklist_issue_no'=>$checklistIssueNo,
                'checklist_desc'=>$put_vars['checklistDesc'],
                'checklist_guideline'=>$put_vars['checklistGuideline'],
                'checklist_min_exec_time'=>$put_vars['checklistMinExecTimeHour'].':'.$put_vars['checklistMinExecTimeMinute'],
                'checklist_max_exec_time'=>$put_vars['checklistMaxExecTimeHour'].':'.$put_vars['checklistMaxExecTimeMinute'],
                'checklist_registered_by'=>$userId,
                'checklist_time_registered'=>'Now()',
                'checklist_status'=>'1'
            );

            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $assetTypeId = $checklist['asset_type_id'];

            if ($checklist['checklist_status'] != '5') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_SUBMITTED, 31);
            }
            if (Class_db::getInstance()->db_count('ppm_checklist', array('checklist_name'=>$checklistName, 'asset_type_id'=>$assetTypeId, 'checklist_id'=>'<>'.$checklistId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_SIMILAR, 31);
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_id'=>$checklistId, 'checklist_qual_status'=>'1')) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_EMPTY_QUAL, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist', $updateArr, array('checklist_id'=>$checklistId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistId
     * @param $put_vars
     * @throws Exception
     */
    public function update_checklist ($checklistId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['checklistName']) || empty($put_vars['checklistName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistName empty');
            }
            if (!isset($put_vars['checklistDocumentNo']) || empty($put_vars['checklistDocumentNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistDocumentNo empty');
            }
            if (!isset($put_vars['checklistIssueNo']) || empty($put_vars['checklistIssueNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistIssueNo empty');
            }
            if (!isset($put_vars['checklistDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistDesc not exist');
            }
            if (!isset($put_vars['checklistGuideline']) || empty($put_vars['checklistGuideline'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistGuideline empty');
            }
            if (!isset($put_vars['checklistMinExecTimeHour']) || !isset($put_vars['checklistMinExecTimeMinute'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistMinExecTime not exist');
            }
            if (!isset($put_vars['checklistMaxExecTimeHour']) || !isset($put_vars['checklistMaxExecTimeMinute'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistMaxExecTime not exist');
            }

            $checklistName = $put_vars['checklistName'];
            $checklistDocumentNo = $put_vars['checklistDocumentNo'];
            $checklistIssueNo = $put_vars['checklistIssueNo'];
            $updateArr = array(
                'checklist_name'=>$checklistName,
                'checklist_document_no'=>$checklistDocumentNo,
                'checklist_issue_no'=>$checklistIssueNo,
                'checklist_desc'=>$put_vars['checklistDesc'],
                'checklist_guideline'=>$put_vars['checklistGuideline'],
                'checklist_min_exec_time'=>$put_vars['checklistMinExecTimeHour'].':'.$put_vars['checklistMinExecTimeMinute'],
                'checklist_max_exec_time'=>$put_vars['checklistMaxExecTimeHour'].':'.$put_vars['checklistMaxExecTimeMinute']
            );

            $checklist = Class_db::getInstance()->db_select_single('ppm_checklist', array('checklist_id'=>$checklistId), null, 1);
            $assetTypeId = $checklist['asset_type_id'];

            if (Class_db::getInstance()->db_count('ppm_checklist', array('checklist_name'=>$checklistName, 'asset_type_id'=>$assetTypeId, 'checklist_id'=>'<>'.$checklistId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_SIMILAR, 31);
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_id'=>$checklistId, 'checklist_qual_status'=>'1')) == 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_EMPTY_QUAL, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist', $updateArr, array('checklist_id'=>$checklistId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistId
     * @throws Exception
     */
    public function deactivate_checklist ($checklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist', array('checklist_id'=>$checklistId, 'checklist_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist', array('checklist_status'=>'2'), array('checklist_id'=>$checklistId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistId
     * @throws Exception
     */
    public function activate_checklist ($checklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist', array('checklist_id'=>$checklistId, 'checklist_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist', array('checklist_status'=>'1'), array('checklist_id'=>$checklistId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistId
     * @throws Exception
     */
    public function delete_checklist ($checklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist', array('checklist_id'=>$checklistId, 'checklist_status'=>'5')) == 0) {
                throw new Exception('[' . __LINE__ . '] - Checklist data not exist');
            }
            if (Class_db::getInstance()->db_count('ppm', array('checklist_id'=>$checklistId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_DELETE_PPM, 31);
            }

            Class_db::getInstance()->db_delete('ppm_checklist_qual', array('checklist_id'=>$checklistId));
            Class_db::getInstance()->db_delete('ppm_checklist_quan', array('checklist_id'=>$checklistId));
            Class_db::getInstance()->db_delete('ppm_checklist', array('checklist_id'=>$checklistId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}