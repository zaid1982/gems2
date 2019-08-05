<?php

class Class_ppmGroup {

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
     * @param string $siteId
     * @param string $roleId
     * @return array
     * @throws Exception
     */
    public function get_ppmGroup_list ($siteId='', $roleId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_ppm_group', array('ppm_group.site_id'=>$siteId, 'ppm_group.role_id'=>$roleId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['ppmGroupId'] = $dataLocal['ppm_group_id'];
                $row_result['ppmGroupName'] = $dataLocal['ppm_group_name'];
                $row_result['siteId'] = $dataLocal['site_id'];
                $row_result['roleId'] = $dataLocal['role_id'];
                $row_result['totalUser'] = $this->fn_general->clear_null($dataLocal['total_user'], '0');
                $row_result['ppmGroupReportTo'] = $this->fn_general->clear_null($dataLocal['ppm_group_report_to']);
                $row_result['reportTo'] = $this->fn_general->clear_null($dataLocal['report_to']);
                $row_result['ppmGroupStatus'] = $dataLocal['ppm_group_status'];
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
    public function get_ppmGroup ($ppmGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Array ppmGroupId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ppm_group', array('ppm_group_id'=>$ppmGroupId), null, 1);
            $result['ppmGroupId'] = $dataLocal['ppm_group_id'];
            $result['ppmGroupName'] = $dataLocal['ppm_group_name'];
            $result['siteId'] = $dataLocal['site_id'];
            $result['roleId'] = $dataLocal['role_id'];
            $result['ppmGroupReportTo'] = $this->fn_general->clear_null($dataLocal['ppm_group_report_to']);
            $result['ppmGroupStatus'] = $dataLocal['ppm_group_status'];

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
    public function add_ppmGroup ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('ppmGroupName', $params) || empty($params['ppmGroupName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupName empty');
            }
            if (!array_key_exists('ppmGroupDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupDesc not exist');
            }
            if (!array_key_exists('ppmGroupStatus', $params) || empty($params['ppmGroupStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupStatus empty');
            }

            $ppmGroupName = $params['ppmGroupName'];
            $ppmGroupDesc = $params['ppmGroupDesc'];
            $ppmGroupStatus = $params['ppmGroupStatus'];

            if (Class_db::getInstance()->db_count('ppm_group', array('ppm_group_name'=>$ppmGroupName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ppm_group', array('ppm_group_name'=>$ppmGroupName, 'ppm_group_desc'=>$ppmGroupDesc, 'ppm_group_status'=>$ppmGroupStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmGroupId
     * @param $put_vars
     * @throws Exception
     */
    public function update_ppmGroup ($ppmGroupId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['ppmGroupName']) || empty($put_vars['ppmGroupName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupName empty');
            }
            if (!isset($put_vars['ppmGroupDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupDesc not exist');
            }
            if (!isset($put_vars['ppmGroupStatus']) || empty($put_vars['ppmGroupStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupStatus empty');
            }

            $ppmGroupName = $put_vars['ppmGroupName'];
            $ppmGroupDesc = $put_vars['ppmGroupDesc'];
            $ppmGroupStatus = $put_vars['ppmGroupStatus'];

            if (Class_db::getInstance()->db_count('ppm_group', array('ppm_group_name'=>$ppmGroupName, 'ppm_group_id'=>'<>'.$ppmGroupId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ppm_group', array('ppm_group_name'=>$ppmGroupName, 'ppm_group_desc'=>$ppmGroupDesc, 'ppm_group_status'=>$ppmGroupStatus), array('ppm_group_id'=>$ppmGroupId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $ppmGroupId
     * @return mixed
     * @throws Exception
     */
    public function delete_ppmGroup ($ppmGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($ppmGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId empty');
            }
            if (Class_db::getInstance()->db_count('ppm_group', array('ppm_group_id'=>$ppmGroupId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Group data not exist');
            }
            if (Class_db::getInstance()->db_count('ppm_category', array('ppm_group_id'=>$ppmGroupId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_DELETE_CATEGORY, 31);
            }

            $ppmGroupName = Class_db::getInstance()->db_select_col('ppm_group', array('ppm_group_id'=>$ppmGroupId), 'ppm_group_name', null, 1);
            Class_db::getInstance()->db_delete('ppm_group', array('ppm_group_id'=>$ppmGroupId));

            return $ppmGroupName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
