<?php

class Class_checklistQuan {

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
     * @param $checklistId
     * @return array
     * @throws Exception
     */
    public function get_checklistQuan_list ($checklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_checklist_quan', array('checklist_id'=>$checklistId), 'ABS(checklist_quan_numb)');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['checklistQuanId'] = $dataLocal['checklist_quan_id'];
                $row_result['checklistQuanDesc'] = $this->fn_general->clear_null($dataLocal['checklist_quan_desc']);
                $row_result['checklistQuanNumb'] = $this->fn_general->clear_null($dataLocal['checklist_quan_numb']);
                $row_result['checklistQuanUnit'] = $this->fn_general->clear_null($dataLocal['checklist_quan_unit']);
                $row_result['checklistQuanSetValues'] = $this->fn_general->clear_null($dataLocal['checklist_quan_set_values']);
                $row_result['frequencyId'] = $this->fn_general->clear_null($dataLocal['frequency_id']);
                $row_result['checklistId'] = $dataLocal['checklist_id'];
                $row_result['checklistQuanStatus'] = $dataLocal['checklist_quan_status'];
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
     * @param $checklistQuanId
     * @return array
     * @throws Exception
     */
    public function get_checklistQuan ($checklistQuanId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($checklistQuanId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ppm_checklist_quan', array('checklist_quan_id'=>$checklistQuanId), null, 1);
            $result['checklistQuanId'] = $dataLocal['checklist_quan_id'];
            $result['checklistQuanDesc'] = $this->fn_general->clear_null($dataLocal['checklist_quan_desc']);
            $result['checklistQuanNumb'] = $this->fn_general->clear_null($dataLocal['checklist_quan_numb']);
            $result['checklistQuanUnit'] = $this->fn_general->clear_null($dataLocal['checklist_quan_unit']);
            $result['checklistQuanSetValues'] = $this->fn_general->clear_null($dataLocal['checklist_quan_set_values']);
            $result['frequencyId'] = $this->fn_general->clear_null($dataLocal['frequency_id']);
            $result['checklistId'] = $dataLocal['checklist_id'];
            $result['checklistQuanStatus'] = $dataLocal['checklist_quan_status'];

            return $result;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function add_checklistQuan ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('checklistQuanDesc', $params) || empty($params['checklistQuanDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanDesc empty');
            }
            if (!array_key_exists('checklistQuanNumb', $params) || empty($params['checklistQuanNumb'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanName empty');
            }
            if (!array_key_exists('checklistQuanUnit', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanUnit not exist');
            }
            if (!array_key_exists('checklistQuanSetValues', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanSetValues not exist');
            }
            if (!array_key_exists('frequencyId', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter frequencyId not exist');
            }
            if (!array_key_exists('checklistId', $params) || empty($params['checklistId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (!array_key_exists('checklistQuanStatus', $params) || empty($params['checklistQuanStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanStatus empty');
            }

            $checklistQuanDesc = $params['checklistQuanDesc'];
            $checklistQuanNumb = $params['checklistQuanNumb'];
            $checklistQuanUnit = $params['checklistQuanUnit'];
            $checklistQuanSetValues = $params['checklistQuanSetValues'];
            $frequencyId = $params['frequencyId'];
            $checklistId = $params['checklistId'];
            $checklistQuanStatus = $params['checklistQuanStatus'];

            if (Class_db::getInstance()->db_count('ppm_checklist_quan', array('checklist_quan_desc'=>$checklistQuanDesc, 'checklist_quan_numb'=>$checklistQuanNumb, 'checklist_id'=>$checklistId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAN_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ppm_checklist_quan', array('checklist_quan_desc'=>$checklistQuanDesc, 'checklist_quan_numb'=>$checklistQuanNumb, 'frequency_id'=>$frequencyId,
                'checklist_quan_unit'=>$checklistQuanUnit, 'checklist_quan_set_values'=>$checklistQuanSetValues, 'checklist_id'=>$checklistId, 'checklist_quan_status'=>$checklistQuanStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQuanId
     * @param $put_vars
     * @throws Exception
     */
    public function update_checklistQuan ($checklistQuanId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistQuanId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['checklistQuanDesc']) || empty($put_vars['checklistQuanDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanDesc empty');
            }
            if (!isset($put_vars['checklistQuanNumb']) || empty($put_vars['checklistQuanNumb'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanNumb empty');
            }
            if (!isset($put_vars['checklistQuanUnit'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanUnit not exist');
            }
            if (!isset($put_vars['checklistQuanSetValues'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanSetValues not exist');
            }
            if (!isset($put_vars['frequencyId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter frequencyId not exist');
            }
            if (!isset($put_vars['checklistId']) || empty($put_vars['checklistId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId not exist');
            }
            if (!isset($put_vars['checklistQuanStatus']) || empty($put_vars['checklistQuanStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanStatus empty');
            }

            $checklistQuanDesc = $put_vars['checklistQuanDesc'];
            $checklistQuanNumb = $put_vars['checklistQuanNumb'];
            $checklistQuanUnit = $put_vars['checklistQuanUnit'];
            $checklistQuanSetValues = $put_vars['checklistQuanSetValues'];
            $frequencyId = $put_vars['frequencyId'];
            $checklistId = $put_vars['checklistId'];
            $checklistQuanStatus = $put_vars['checklistQuanStatus'];

            if (Class_db::getInstance()->db_count('ppm_checklist_quan', array('checklist_quan_desc'=>$checklistQuanDesc, 'checklist_quan_numb'=>$checklistQuanNumb, 'checklist_id'=>$checklistId, 'checklist_quan_id'=>'<>'.$checklistQuanId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAN_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist_quan', array('checklist_quan_desc'=>$checklistQuanDesc, 'checklist_quan_numb'=>$checklistQuanNumb, 'checklist_quan_unit'=>$checklistQuanUnit, 'checklist_quan_set_values'=>$checklistQuanSetValues,
                'frequency_id'=>$frequencyId, 'checklist_quan_status'=>$checklistQuanStatus), array('checklist_quan_id'=>$checklistQuanId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQuanId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_checklistQuan ($checklistQuanId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistQuanId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_quan', array('checklist_quan_id'=>$checklistQuanId, 'checklist_quan_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAN_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist_quan', array('checklist_quan_status'=>'2'), array('checklist_quan_id'=>$checklistQuanId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQuanId
     * @return mixed
     * @throws Exception
     */
    public function activate_checklistQuan ($checklistQuanId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistQuanId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_quan', array('checklist_quan_id'=>$checklistQuanId, 'checklist_quan_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAN_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist_quan', array('checklist_quan_status'=>'1'), array('checklist_quan_id'=>$checklistQuanId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQuanId
     * @return mixed
     * @throws Exception
     */
    public function delete_checklistQuan ($checklistQuanId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($checklistQuanId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQuanId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_quan', array('checklist_quan_id'=>$checklistQuanId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Category data not exist');
            }
            if (Class_db::getInstance()->db_count('ppm_task_quan', array('checklist_quan_id'=>$checklistQuanId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAN_DELETE_PPM, 31);
            }

            Class_db::getInstance()->db_delete('ppm_checklist_quan', array('checklist_quan_id'=>$checklistQuanId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
