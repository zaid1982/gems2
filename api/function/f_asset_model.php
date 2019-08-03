<?php

class Class_assetModel {

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
     * @param $assetTypeId
     * @return array
     * @throws Exception
     */
    public function get_assetModel_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset_model');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetModelId'] = $dataLocal['asset_model_id'];
                $row_result['assetModelName'] = $dataLocal['asset_model_name'];
                $row_result['assetModelDesc'] = $this->fn_general->clear_null($dataLocal['asset_model_desc']);
                $row_result['assetBrandId'] = $dataLocal['asset_brand_id'];
                $row_result['assetTypeId'] = $dataLocal['asset_type_id'];
                $row_result['assetModelTimeCreated'] = str_replace('-', '/', $dataLocal['asset_model_time_created']);
                $row_result['assetModelStatus'] = $dataLocal['asset_model_status'];
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
     * @param $assetModelId
     * @return array
     * @throws Exception
     */
    public function get_assetModel ($assetModelId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetModelId)) {
                throw new Exception('[' . __LINE__ . '] - Array assetModelId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset_model', array('asset_model_id'=>$assetModelId), null, 1);
            $result['assetModelId'] = $dataLocal['asset_model_id'];
            $result['assetModelName'] = $dataLocal['asset_model_name'];
            $result['assetModelDesc'] = $this->fn_general->clear_null($dataLocal['asset_model_desc']);
            $result['assetBrandId'] = $dataLocal['asset_brand_id'];
            $result['assetTypeId'] = $dataLocal['asset_type_id'];
            $result['assetModelTimeCreated'] = str_replace('-', '/', $dataLocal['asset_model_time_created']);
            $result['assetModelStatus'] = $dataLocal['asset_model_status'];

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
    public function add_assetModel ($params) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('assetModelName', $params) || empty($params['assetModelName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelName empty');
            }
            if (!array_key_exists('assetModelDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelDesc not exist');
            }
            if (!array_key_exists('assetBrandId', $params) || empty($params['assetBrandId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId not exist');
            }
            if (!array_key_exists('assetTypeId', $params) || empty($params['assetTypeId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId not exist');
            }
            if (!array_key_exists('assetModelStatus', $params) || empty($params['assetModelStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelStatus empty');
            }

            $assetModelName = $params['assetModelName'];
            $assetModelDesc = $params['assetModelDesc'];
            $assetBrandId = $params['assetBrandId'];
            $assetTypeId = $params['assetTypeId'];
            $assetModelStatus = $params['assetModelStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_model_name'=>$assetModelName, 'asset_brand_id'=>$assetBrandId, 'asset_type_id'=>$assetTypeId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_MODEL_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ast_asset_model', array('asset_model_name'=>$assetModelName, 'asset_model_desc'=>$assetModelDesc, 'asset_brand_id'=>$assetBrandId, 'asset_type_id'=>$assetTypeId, 'asset_model_status'=>$assetModelStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetModelId
     * @param $put_vars
     * @throws Exception
     */
    public function update_assetModel ($assetModelId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetModelId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetModelName']) || empty($put_vars['assetModelName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelName empty');
            }
            if (!isset($put_vars['assetModelDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelDesc not exist');
            }
            if (!isset($put_vars['assetBrandId']) || empty($put_vars['assetBrandId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId not exist');
            }
            if (!isset($put_vars['assetTypeId']) || empty($put_vars['assetTypeId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId not exist');
            }
            if (!isset($put_vars['assetModelStatus']) || empty($put_vars['assetModelStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelStatus empty');
            }

            $assetModelName = $put_vars['assetModelName'];
            $assetModelDesc = $put_vars['assetModelDesc'];
            $assetBrandId = $put_vars['assetBrandId'];
            $assetTypeId = $put_vars['assetTypeId'];
            $assetModelStatus = $put_vars['assetModelStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_model_name'=>$assetModelName, 'asset_brand_id'=>$assetBrandId, 'asset_type_id'=>$assetTypeId, 'asset_model_id'=>'<>'.$assetModelId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_MODEL_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_model', array('asset_model_name'=>$assetModelName, 'asset_model_desc'=>$assetModelDesc, 'asset_brand_id'=>$assetBrandId, 'asset_model_status'=>$assetModelStatus), array('asset_model_id'=>$assetModelId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetModelId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_assetModel ($assetModelId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetModelId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_model_id'=>$assetModelId, 'asset_model_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_MODEL_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_model', array('asset_model_status'=>'2'), array('asset_model_id'=>$assetModelId));
            return Class_db::getInstance()->db_select_col('ast_asset_model', array('asset_model_id'=>$assetModelId), 'asset_model_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetModelId
     * @return mixed
     * @throws Exception
     */
    public function activate_assetModel ($assetModelId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetModelId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_model_id'=>$assetModelId, 'asset_model_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_MODEL_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_model', array('asset_model_status'=>'1'), array('asset_model_id'=>$assetModelId));
            return Class_db::getInstance()->db_select_col('ast_asset_model', array('asset_model_id'=>$assetModelId), 'asset_model_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetModelId
     * @return mixed
     * @throws Exception
     */
    public function delete_assetModel ($assetModelId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetModelId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_model_id'=>$assetModelId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Model data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_model_id'=>$assetModelId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_MODEL_DELETE_ASSET, 31);
            }

            $assetModelName = Class_db::getInstance()->db_select_col('ast_asset_model', array('asset_model_id'=>$assetModelId), 'asset_model_name', null, 1);
            Class_db::getInstance()->db_delete('ast_asset_model', array('asset_model_id'=>$assetModelId));

            return $assetModelName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
