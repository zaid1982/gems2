<?php

class Class_assetGroup {

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
    public function get_assetGroup_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset_group');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetGroupId'] = $dataLocal['asset_group_id'];
                $row_result['assetGroupName'] = $dataLocal['asset_group_name'];
                $row_result['assetGroupDesc'] = $this->fn_general->clear_null($dataLocal['asset_group_desc']);
                $row_result['assetGroupTimeCreated'] = str_replace('-', '/', $dataLocal['asset_group_time_created']);
                $row_result['assetGroupStatus'] = $dataLocal['asset_group_status'];
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
     * @param $assetGroupId
     * @return array
     * @throws Exception
     */
    public function get_assetGroup ($assetGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Array assetGroupId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset_group', array('asset_group_id'=>$assetGroupId), null, 1);
            $result['assetGroupId'] = $dataLocal['asset_group_id'];
            $result['assetGroupName'] = $dataLocal['asset_group_name'];
            $result['assetGroupDesc'] = $this->fn_general->clear_null($dataLocal['asset_group_desc']);
            $result['assetGroupTimeCreated'] = str_replace('-', '/', $dataLocal['asset_group_time_created']);
            $result['assetGroupStatus'] = $dataLocal['asset_group_status'];

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
    public function add_assetGroup ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('assetGroupName', $params) || empty($params['assetGroupName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupName empty');
            }
            if (!array_key_exists('assetGroupDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupDesc not exist');
            }
            if (!array_key_exists('assetGroupStatus', $params) || empty($params['assetGroupStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupStatus empty');
            }

            $assetGroupName = $params['assetGroupName'];
            $assetGroupDesc = $params['assetGroupDesc'];
            $assetGroupStatus = $params['assetGroupStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_group', array('asset_group_name'=>$assetGroupName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ast_asset_group', array('asset_group_name'=>$assetGroupName, 'asset_group_desc'=>$assetGroupDesc, 'asset_group_status'=>$assetGroupStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetGroupId
     * @param $put_vars
     * @throws Exception
     */
    public function update_assetGroup ($assetGroupId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetGroupName']) || empty($put_vars['assetGroupName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupName empty');
            }
            if (!isset($put_vars['assetGroupDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupDesc not exist');
            }
            if (!isset($put_vars['assetGroupStatus']) || empty($put_vars['assetGroupStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupStatus empty');
            }

            $assetGroupName = $put_vars['assetGroupName'];
            $assetGroupDesc = $put_vars['assetGroupDesc'];
            $assetGroupStatus = $put_vars['assetGroupStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_group', array('asset_group_name'=>$assetGroupName, 'asset_group_id'=>'<>'.$assetGroupId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_group', array('asset_group_name'=>$assetGroupName, 'asset_group_desc'=>$assetGroupDesc, 'asset_group_status'=>$assetGroupStatus), array('asset_group_id'=>$assetGroupId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_assetGroup ($assetGroupId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_group', array('asset_group_id'=>$assetGroupId, 'asset_group_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_group', array('asset_group_status'=>'2'), array('asset_group_id'=>$assetGroupId));
            return Class_db::getInstance()->db_select_col('ast_asset_group', array('asset_group_id'=>$assetGroupId), 'asset_group_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function activate_assetGroup ($assetGroupId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_group', array('asset_group_id'=>$assetGroupId, 'asset_group_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_group', array('asset_group_status'=>'1'), array('asset_group_id'=>$assetGroupId));
            return Class_db::getInstance()->db_select_col('ast_asset_group', array('asset_group_id'=>$assetGroupId), 'asset_group_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetGroupId
     * @return mixed
     * @throws Exception
     */
    public function delete_assetGroup ($assetGroupId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetGroupId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_group', array('asset_group_id'=>$assetGroupId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Group data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_asset_category', array('asset_group_id'=>$assetGroupId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_GROUP_DELETE_CATEGORY, 31);
            }

            $assetGroupName = Class_db::getInstance()->db_select_col('ast_asset_group', array('asset_group_id'=>$assetGroupId), 'asset_group_name', null, 1);
            Class_db::getInstance()->db_delete('ast_asset_group', array('asset_group_id'=>$assetGroupId));

            return $assetGroupName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
