<?php

class Class_wo_task_assist {

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
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getWoAssistantDropdownM ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($woTaskId));
            $assistantList = array();
            $woTask = Class_db::getInstance()->db_select_single2('wo_task', array('wo_task_id'=>$woTaskId), '', 1);
            $assistantDropdownArr = Class_db::getInstance()->db_select2('mw_ppm_group_user',
                array('ppm_group_user.ppm_group_id'=>$woTask['ppmGroupId'], 'ppm_group_user.user_id'=>'<>'.$woTask['woTaskAssignedTo'], 'user_status'=>'1'), 'user_first_name');
            foreach ($assistantDropdownArr as $assistantDropdown) {
                $assistantList[] = array('userId'=>$assistantDropdown['userId'], 'userFullName'=>$assistantDropdown['userFirstName']);
            }
            return $assistantList;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $woTaskId
     * @return array
     * @throws Exception
     */
    public function getWoAssistantListM ($woTaskId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $this->fn_general->checkEmptyParams(array($woTaskId));
            $userFullNameArr = $this->fn_general->getUserFullName();
            $assistantList = array();
            $woTaskAssistArr = Class_db::getInstance()->db_select2('wo_task_assist', array('wo_task_id'=>$woTaskId));
            foreach ($woTaskAssistArr as $woTaskAssist) {
                $assistantList[] = array('userId'=>$woTaskAssist['userId'], 'userFullName'=>$userFullNameArr[intval($woTaskAssist['userId'])]);
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
     * @return void
     * @throws Exception
     */
    public function addWoTaskAssist ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParamsArray($params, array('woTaskId', 'assistant'));
            if (Class_db::getInstance()->db_count('wo_task_assist', array('wo_task_id'=>$params['woTaskId'], 'user_id'=>$params['assistant'])) == 0) {
                Class_db::getInstance()->db_insert('wo_task_assist', array('wo_task_id'=>$params['woTaskId'], 'user_id'=>$params['assistant']));
            }
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}