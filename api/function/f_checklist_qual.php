<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_checklistQual {

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
     * @param $checklistId
     * @return array
     * @throws Exception
     */
    public function get_checklistQual_list ($checklistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($checklistId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_checklist_qual', array('checklist_id'=>$checklistId), 'ABS(checklist_qual_numb)');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['checklistQualId'] = $dataLocal['checklist_qual_id'];
                $row_result['checklistQualDesc'] = $this->fn_general->clear_null($dataLocal['checklist_qual_desc']);
                $row_result['checklistQualNumb'] = $this->fn_general->clear_null($dataLocal['checklist_qual_numb']);
                $row_result['frequencyId'] = $this->fn_general->clear_null($dataLocal['frequency_id']);
                $row_result['checklistId'] = $dataLocal['checklist_id'];
                $row_result['checklistQualStatus'] = $dataLocal['checklist_qual_status'];
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
     * @param $checklistQualId
     * @return array
     * @throws Exception
     */
    public function get_checklistQual ($checklistQualId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($checklistQualId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ppm_checklist_qual', array('checklist_qual_id'=>$checklistQualId), null, 1);
            $result['checklistQualId'] = $dataLocal['checklist_qual_id'];
            $result['checklistQualDesc'] = $this->fn_general->clear_null($dataLocal['checklist_qual_desc']);
            $result['checklistQualNumb'] = $this->fn_general->clear_null($dataLocal['checklist_qual_numb']);
            $result['frequencyId'] = $this->fn_general->clear_null($dataLocal['frequency_id']);
            $result['checklistId'] = $dataLocal['checklist_id'];
            $result['checklistQualStatus'] = $dataLocal['checklist_qual_status'];

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
    public function add_checklistQual ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('checklistQualDesc', $params) || empty($params['checklistQualDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualDesc empty');
            }
            if (!array_key_exists('checklistQualNumb', $params) || empty($params['checklistQualNumb'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualName empty');
            }
            if (!array_key_exists('frequencyId', $params) || empty($params['frequencyId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter frequencyId empty');
            }
            if (!array_key_exists('checklistId', $params) || empty($params['checklistId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (!array_key_exists('checklistQualStatus', $params) || empty($params['checklistQualStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualStatus empty');
            }

            $checklistQualDesc = $params['checklistQualDesc'];
            $checklistQualNumb = $params['checklistQualNumb'];
            $frequencyId = $params['frequencyId'];
            $checklistId = $params['checklistId'];
            $checklistQualStatus = $params['checklistQualStatus'];

            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_qual_desc'=>$checklistQualDesc, 'checklist_qual_numb'=>$checklistQualNumb, 'checklist_id'=>$checklistId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAL_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ppm_checklist_qual', array('checklist_qual_desc'=>$checklistQualDesc, 'checklist_qual_numb'=>$checklistQualNumb, 'frequency_id'=>$frequencyId,
                'checklist_id'=>$checklistId, 'checklist_qual_status'=>$checklistQualStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQualId
     * @param $put_vars
     * @throws Exception
     */
    public function update_checklistQual ($checklistQualId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($checklistQualId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['checklistQualDesc']) || empty($put_vars['checklistQualDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualDesc empty');
            }
            if (!isset($put_vars['checklistQualNumb']) || empty($put_vars['checklistQualNumb'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualNumb empty');
            }
            if (!isset($put_vars['frequencyId']) || empty($put_vars['frequencyId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter frequencyId empty');
            }
            if (!isset($put_vars['checklistId']) || empty($put_vars['checklistId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistId empty');
            }
            if (!isset($put_vars['checklistQualStatus']) || empty($put_vars['checklistQualStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualStatus empty');
            }

            $checklistQualDesc = $put_vars['checklistQualDesc'];
            $checklistQualNumb = $put_vars['checklistQualNumb'];
            $frequencyId = $put_vars['frequencyId'];
            $checklistId = $put_vars['checklistId'];
            $checklistQualStatus = $put_vars['checklistQualStatus'];

            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_qual_desc'=>$checklistQualDesc, 'checklist_qual_numb'=>$checklistQualNumb, 'checklist_id'=>$checklistId, 'checklist_qual_id'=>'<>'.$checklistQualId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAL_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist_qual', array('checklist_qual_desc'=>$checklistQualDesc, 'checklist_qual_numb'=>$checklistQualNumb, 'frequency_id'=>$frequencyId, 'checklist_qual_status'=>$checklistQualStatus), array('checklist_qual_id'=>$checklistQualId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQualId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_checklistQual ($checklistQualId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($checklistQualId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_qual_id'=>$checklistQualId, 'checklist_qual_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAL_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist_qual', array('checklist_qual_status'=>'2'), array('checklist_qual_id'=>$checklistQualId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQualId
     * @return mixed
     * @throws Exception
     */
    public function activate_checklistQual ($checklistQualId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($checklistQualId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_qual_id'=>$checklistQualId, 'checklist_qual_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAL_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ppm_checklist_qual', array('checklist_qual_status'=>'1'), array('checklist_qual_id'=>$checklistQualId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $checklistQualId
     * @return mixed
     * @throws Exception
     */
    public function delete_checklistQual ($checklistQualId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($checklistQualId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter checklistQualId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_checklist_qual', array('checklist_qual_id'=>$checklistQualId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Category data not exist');
            }
            if (Class_db::getInstance()->db_count('ppm_task_qual', array('checklist_qual_id'=>$checklistQualId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CHECKLIST_QUAL_DELETE_PPM, 31);
            }

            Class_db::getInstance()->db_delete('ppm_checklist_qual', array('checklist_qual_id'=>$checklistQualId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
