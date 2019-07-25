<?php

class Class_assetType {

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
    public function get_assetType_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('vw_asset_type');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetTypeId'] = $dataLocal['asset_type_id'];
                $row_result['assetTypeName'] = $dataLocal['asset_type_name'];
                $row_result['assetTypeDesc'] = $this->fn_general->clear_null($dataLocal['asset_type_desc']);
                $row_result['assetCategoryId'] = $dataLocal['asset_category_id'];
                $row_result['assetGroupId'] = $dataLocal['asset_group_id'];
                $row_result['totalModel'] = $this->fn_general->clear_null($dataLocal['total_model'], 0);
                $row_result['assetTypeTimeCreated'] = str_replace('-', '/', $dataLocal['asset_type_time_created']);
                $row_result['assetTypeStatus'] = $dataLocal['asset_type_status'];
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
     * @param $assetTypeId
     * @return array
     * @throws Exception
     */
    public function get_assetType ($assetTypeId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Array assetTypeId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset_type', array('asset_type_id'=>$assetTypeId), null, 1);
            $result['assetTypeId'] = $dataLocal['asset_type_id'];
            $result['assetTypeName'] = $dataLocal['asset_type_name'];
            $result['assetTypeDesc'] = $this->fn_general->clear_null($dataLocal['asset_type_desc']);
            $result['assetCategoryId'] = $dataLocal['asset_category_id'];
            $result['assetTypeTimeCreated'] = str_replace('-', '/', $dataLocal['asset_type_time_created']);
            $result['assetTypeStatus'] = $dataLocal['asset_type_status'];

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
    public function add_assetType ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('assetTypeName', $params) || empty($params['assetTypeName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeName empty');
            }
            if (!array_key_exists('assetTypeDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeDesc not exist');
            }
            if (!array_key_exists('assetCategoryId', $params) || empty($params['assetCategoryId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId not exist');
            }
            if (!array_key_exists('assetTypeStatus', $params) || empty($params['assetTypeStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeStatus empty');
            }

            $assetTypeName = $params['assetTypeName'];
            $assetTypeDesc = $params['assetTypeDesc'];
            $assetCategoryId = $params['assetCategoryId'];
            $assetTypeStatus = $params['assetTypeStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_type', array('asset_type_name'=>$assetTypeName, 'asset_category_id'=>$assetCategoryId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_TYPE_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ast_asset_type', array('asset_type_name'=>$assetTypeName, 'asset_type_desc'=>$assetTypeDesc, 'asset_category_id'=>$assetCategoryId, 'asset_type_status'=>$assetTypeStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetTypeId
     * @param $put_vars
     * @throws Exception
     */
    public function update_assetType ($assetTypeId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetTypeName']) || empty($put_vars['assetTypeName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeName empty');
            }
            if (!isset($put_vars['assetTypeDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeDesc not exist');
            }
            if (!isset($put_vars['assetCategoryId']) || empty($put_vars['assetCategoryId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId not exist');
            }
            if (!isset($put_vars['assetTypeStatus']) || empty($put_vars['assetTypeStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeStatus empty');
            }

            $assetTypeName = $put_vars['assetTypeName'];
            $assetTypeDesc = $put_vars['assetTypeDesc'];
            $assetCategoryId = $put_vars['assetCategoryId'];
            $assetTypeStatus = $put_vars['assetTypeStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_type', array('asset_type_name'=>$assetTypeName, 'asset_category_id'=>$assetCategoryId, 'asset_type_id'=>'<>'.$assetTypeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_TYPE_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_type', array('asset_type_name'=>$assetTypeName, 'asset_type_desc'=>$assetTypeDesc, 'asset_category_id'=>$assetCategoryId, 'asset_type_status'=>$assetTypeStatus), array('asset_type_id'=>$assetTypeId));
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
    public function deactivate_assetType ($assetTypeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_type', array('asset_type_id'=>$assetTypeId, 'asset_type_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_TYPE_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_type', array('asset_type_status'=>'2'), array('asset_type_id'=>$assetTypeId));
            return Class_db::getInstance()->db_select_col('ast_asset_type', array('asset_type_id'=>$assetTypeId), 'asset_type_name', null, 1);
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
    public function activate_assetType ($assetTypeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_type', array('asset_type_id'=>$assetTypeId, 'asset_type_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_TYPE_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_type', array('asset_type_status'=>'1'), array('asset_type_id'=>$assetTypeId));
            return Class_db::getInstance()->db_select_col('ast_asset_type', array('asset_type_id'=>$assetTypeId), 'asset_type_name', null, 1);
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
    public function delete_assetType ($assetTypeId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetTypeId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_type', array('asset_type_id'=>$assetTypeId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Type data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_type_id'=>$assetTypeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_TYPE_DELETE_ASSET, 31);
            }
            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_type_id'=>$assetTypeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_TYPE_DELETE_MODEL, 31);
            }

            $assetTypeName = Class_db::getInstance()->db_select_col('ast_asset_type', array('asset_type_id'=>$assetTypeId), 'asset_type_name', null, 1);
            Class_db::getInstance()->db_delete('ast_asset_type', array('asset_type_id'=>$assetTypeId));

            return $assetTypeName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
