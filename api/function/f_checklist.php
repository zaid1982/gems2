<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_checklist {

    private $fn_general;

    function __construct() {
        $this->fn_general = new Class_general();
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_checklist_by_type');
            foreach ($arr_dataLocal as $dataLocal) {
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_checklist', array('asset_type_id'=>$assetTypeId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['checklistId'] = $dataLocal['checklist_id'];
                $row_result['checklistName'] = $this->fn_general->clear_null($dataLocal['checklist_name']);
                $row_result['checklistVersion'] = $this->fn_general->clear_null($dataLocal['checklist_version']);
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
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
}