<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_site {

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
    public function get_site_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_site');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['siteName'] = $dataLocal['site_name'];
                $row_result['siteDesc'] = $this->fn_general->clear_null($dataLocal['site_desc']);
                $row_result['clientId'] = $dataLocal['client_id'];
                $row_result['groupId'] = $dataLocal['group_id'];
                $row_result['siteTimeCreated'] = $dataLocal['site_time_created'];
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('cli_site', array('site_id'=>$siteId), null, 1);
            $result['siteId'] = $dataLocal['site_id'];
            $result['siteName'] = $dataLocal['site_name'];
            $result['siteDesc'] = $this->fn_general->clear_null($dataLocal['site_desc']);
            $result['clientId'] = $dataLocal['client_id'];
            $result['groupId'] = $dataLocal['group_id'];
            $result['siteTimeCreated'] = $dataLocal['site_time_created'];
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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('siteName', $params) || empty($params['siteName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteName empty');
            }
            if (!array_key_exists('siteDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteDesc not exist');
            }
            if (!array_key_exists('clientId', $params) || empty($params['clientId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter clientId empty');
            }
            if (!array_key_exists('siteStatus', $params) || empty($params['siteStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteStatus empty');
            }

            $siteName = $params['siteName'];
            $siteDesc = $params['siteDesc'];
            $clientId = $params['clientId'];
            $siteStatus = $params['siteStatus'];

            if (Class_db::getInstance()->db_count('cli_site', array('site_name'=>$siteName, 'client_id'=>$clientId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_SIMILAR, 31);
            }

            $groupId = Class_db::getInstance()->db_insert('sys_group', array('group_name'=>$siteName, 'group_type'=>'2', 'group_status'=>$siteStatus));
            return Class_db::getInstance()->db_insert('cli_site', array('site_name'=>$siteName, 'site_desc'=>$siteDesc, 'group_id'=>$groupId, 'site_status'=>$siteStatus));
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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['siteName']) || empty($put_vars['siteName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteName empty');
            }
            if (!isset($put_vars['siteDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteDesc not exist');
            }
            if (!isset($put_vars['siteStatus']) || empty($put_vars['siteStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteStatus empty');
            }

            $siteName = $put_vars['siteName'];
            $siteDesc = $put_vars['siteDesc'];
            $siteStatus = $put_vars['siteStatus'];

            if (Class_db::getInstance()->db_count('cli_site', array('site_name'=>$siteName, 'site_id'=>'<>'.$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_SIMILAR, 31);
            }

            $groupId = Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'group_id', null, 1);
            Class_db::getInstance()->db_update('cli_site', array('site_name'=>$siteName, 'site_desc'=>$siteDesc, 'site_status'=>$siteStatus), array('site_id'=>$siteId));
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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId, 'site_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_DEACTIVATE, 31);
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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId, 'site_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_ACTIVATE, 31);
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
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($siteId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
            }
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Site data not exist');
            }
            if (Class_db::getInstance()->db_count('cli_contract', array('site_id'=>$siteId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_CLIENT_DELETE_SITE, 31);
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
