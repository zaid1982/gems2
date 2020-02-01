<?php

class Class_site {

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
    public function get_site_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_site');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['siteName'] = $dataLocal['site_name'];
                $row_result['siteCode'] = $dataLocal['site_code'];
                $row_result['siteDesc'] = $this->fn_general->clear_null($dataLocal['site_desc']);
                $row_result['clientId'] = $dataLocal['client_id'];
                $row_result['groupId'] = $dataLocal['group_id'];
                $row_result['siteIsWr'] = $dataLocal['site_is_wr'];
                $row_result['siteTimeCreated'] = str_replace('-', '/', $dataLocal['site_time_created']);
                $row_result['siteStatus'] = $dataLocal['site_status'];
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
    public function get_site ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Array siteId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('cli_site', array('site_id'=>$siteId), null, 1);
            $result['siteId'] = $dataLocal['site_id'];
            $result['siteName'] = $dataLocal['site_name'];
            $result['siteCode'] = $dataLocal['site_code'];
            $result['siteDesc'] = $this->fn_general->clear_null($dataLocal['site_desc']);
            $result['clientId'] = $dataLocal['client_id'];
            $result['groupId'] = $dataLocal['group_id'];
            $result['siteIsWr'] = $dataLocal['site_is_wr'];
            $result['siteTimeCreated'] = str_replace('-', '/', $dataLocal['site_time_created']);
            $result['siteStatus'] = $dataLocal['site_status'];

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
    public function add_site ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('siteName', $params) || empty($params['siteName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteName empty');
            }
            if (!array_key_exists('siteCode', $params) || empty($params['siteCode'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteCode not exist');
            }
            if (!array_key_exists('siteDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteDesc not exist');
            }
            if (!array_key_exists('clientId', $params) || empty($params['clientId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (!array_key_exists('siteIsWr', $params) || $params['siteIsWr'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter siteStatus empty');
            }
            if (!array_key_exists('siteStatus', $params) || empty($params['siteStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteStatus empty');
            }

            $siteName = $params['siteName'];
            $siteCode = $params['siteCode'];
            $siteDesc = $params['siteDesc'];
            $clientId = $params['clientId'];
            $siteIsWr = $params['siteIsWr'];
            $siteStatus = $params['siteStatus'];

            if (Class_db::getInstance()->db_count('cli_site', array('site_name'=>$siteName, 'client_id'=>$clientId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_SIMILAR, 31);
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_code'=>$siteCode)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_SIMILAR_CODE, 31);
            }

            $groupId = Class_db::getInstance()->db_insert('sys_group', array('group_name'=>$siteName, 'group_type'=>'2', 'group_status'=>$siteStatus));
            return Class_db::getInstance()->db_insert('cli_site', array('site_name'=>$siteName, 'site_code'=>$siteCode, 'site_desc'=>$siteDesc, 'client_id'=>$clientId, 'group_id'=>$groupId, 'site_is_wr'=>$siteIsWr, 'site_status'=>$siteStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @param $put_vars
     * @throws Exception
     */
    public function update_site ($siteId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['siteName']) || empty($put_vars['siteName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteName empty');
            }
            if (!isset($put_vars['siteCode']) || empty($put_vars['siteCode'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteCode not exist');
            }
            if (!isset($put_vars['siteDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteDesc not exist');
            }
            if (!isset($put_vars['clientId']) || empty($put_vars['clientId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId not exist');
            }
            if (!isset($put_vars['siteIsWr']) || $put_vars['siteIsWr'] === '') {
                throw new Exception('[' . __LINE__ . '] - Parameter siteStatus empty');
            }
            if (!isset($put_vars['siteStatus']) || empty($put_vars['siteStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteStatus empty');
            }

            $siteName = $put_vars['siteName'];
            $siteCode = $put_vars['siteCode'];
            $siteDesc = $put_vars['siteDesc'];
            $clientId = $put_vars['clientId'];
            $siteIsWr = $put_vars['siteIsWr'];
            $siteStatus = $put_vars['siteStatus'];

            if (Class_db::getInstance()->db_count('cli_site', array('site_name'=>$siteName, 'client_id'=>$clientId, 'site_id'=>'<>'.$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_SIMILAR, 31);
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_code'=>$siteCode, 'site_id'=>'<>'.$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_SIMILAR_CODE, 31);
            }

            $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            Class_db::getInstance()->db_update('cli_site', array('site_name'=>$siteName, 'site_code'=>$siteCode, 'site_desc'=>$siteDesc, 'site_is_wr'=>$siteIsWr, 'site_status'=>$siteStatus), array('site_id'=>$siteId));
            Class_db::getInstance()->db_update('sys_group', array('group_name'=>$siteName, 'group_status'=>'2'), array('group_id'=>$groupId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_site ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId, 'site_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_DEACTIVATE, 31);
            }

            $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            Class_db::getInstance()->db_update('cli_site', array('site_status'=>'2'), array('site_id'=>$siteId));
            Class_db::getInstance()->db_update('sys_group', array('group_status'=>'2'), array('group_id'=>$groupId));
            return Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return mixed
     * @throws Exception
     */
    public function activate_site ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId, 'site_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_ACTIVATE, 31);
            }

            $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            Class_db::getInstance()->db_update('cli_site', array('site_status'=>'1'), array('site_id'=>$siteId));
            Class_db::getInstance()->db_update('sys_group', array('group_status'=>'1'), array('group_id'=>$groupId));
            return Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return mixed
     * @throws Exception
     */
    public function delete_site ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Site data not exist');
            }
            if (Class_db::getInstance()->db_count('cli_contract', array('site_id'=>$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_DELETE_CONTRACT, 31);
            }

            $siteName = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_name', null, 1);
            Class_db::getInstance()->db_delete('cli_site', array('site_id'=>$siteId));

            return $siteName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
