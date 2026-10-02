<?php

class Class_ppm_task_assist {

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
     * @param $ppmTaskId
     * @return array
     * @throws Exception
     */
    public function getPpmAssistantDropdownM ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($ppmTaskId));
            $assistantList = array();
            $ppmTask = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id'=>$ppmTaskId), '', 1);
            $ppmGroupId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmTask['ppmId']), 'ppm_group_id');
            $assistantDropdownArr = Class_db::getInstance()->db_select2('mw_ppm_group_user',
                array('ppm_group_user.ppm_group_id'=>$ppmGroupId, 'ppm_group_user.user_id'=>'<>'.$ppmTask['ppmTaskAssignedTo'], 'user_status'=>'1'), 'user_first_name');
            foreach ($assistantDropdownArr as $assistantDropdown) {
                $assistantList[] = array(
                    'userId'=>$assistantDropdown['userId'],
                    'userFullName'=>$assistantDropdown['userFirstName'],
                    'ppmGroupId'=>$ppmGroupId
                );
            }
            return $assistantList;
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
    public function getPpmAssistantListM ($ppmTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($ppmTaskId));
            $userFullNameArr = $this->fn_general->getUserFullName();
            $assistantList = array();
            $ppmTask = Class_db::getInstance()->db_select_single2('ppm_task', array('ppm_task_id'=>$ppmTaskId), '', 1);
            $ppmGroupId = Class_db::getInstance()->db_select_col('ppm', array('ppm_id'=>$ppmTask['ppmId']), 'ppm_group_id');
            $ppmTaskAssistArr = Class_db::getInstance()->db_select2('ppm_task_assist', array('ppm_task_id'=>$ppmTaskId));
            foreach ($ppmTaskAssistArr as $ppmTaskAssist) {
                $assistantList[] = array(
                    'ppmTaskAssistId'=>$ppmTaskAssist['ppmTaskAssistId'],
                    'userId'=>$ppmTaskAssist['userId'],
                    'userFullName'=>$userFullNameArr[intval($ppmTaskAssist['userId'])],
                    'ppmGroupId'=>$ppmGroupId
                );
            }
            return $assistantList;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function addPpmTaskAssist ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParamsArray($params, array('ppmTaskId', 'assistant'));
            $existing = Class_db::getInstance()->db_select_single('ppm_task_assist', array('ppm_task_id'=>$params['ppmTaskId'], 'user_id'=>$params['assistant']));
            if (!empty($existing['ppm_task_assist_id'])) {
                return $existing['ppm_task_assist_id'];
            }
            return Class_db::getInstance()->db_insert('ppm_task_assist', array('ppm_task_id'=>$params['ppmTaskId'], 'user_id'=>$params['assistant']));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $ppmTaskAssistId
     * @return string
     * @throws Exception
     */
    public function deletePpmTaskAssist ($ppmTaskAssistId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($ppmTaskAssistId));
            $row = Class_db::getInstance()->db_select_single('ppm_task_assist', array('ppm_task_assist_id'=>$ppmTaskAssistId), null, 1);
            Class_db::getInstance()->db_delete('ppm_task_assist', array('ppm_task_assist_id'=>$ppmTaskAssistId));
            return $row['ppm_task_id'];
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}