<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_asset {

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
    public function get_asset_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetCode'] = $this->fn_general->clear_null($dataLocal['asset_code']);
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
                $row_result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
                $row_result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
                $row_result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
                $row_result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
                $row_result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
                $row_result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
                $row_result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
                $row_result['assetTimeCreated'] = str_replace('-', '/', $dataLocal['asset_time_created']);
                $row_result['assetStatus'] = $dataLocal['asset_status'];
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
     * @param $assetId
     * @return array
     * @throws Exception
     */
    public function get_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Array assetId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $result['assetId'] = $dataLocal['asset_id'];
            $result['assetCode'] = $this->fn_general->clear_null($dataLocal['asset_code']);
            $result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
            $result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
            $result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
            $result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
            $result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
            $result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
            $result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
            $result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
            $result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
            $result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
            $result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
            $result['assetTimeRegistered'] = str_replace('-', '/', $dataLocal['asset_time_registered']);
            $result['assetTimeCreated'] = str_replace('-', '/', $dataLocal['asset_time_created']);
            $result['assetRegisteredBy'] = $this->fn_general->clear_null($dataLocal['asset_registered_by']);
            $result['assetStatus'] = $dataLocal['asset_status'];

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
    public function create_asset ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('assetName', $params) || empty($params['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetName empty');
            }
            if (!array_key_exists('assetDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetDesc not exist');
            }
            if (!array_key_exists('assetStatus', $params) || empty($params['assetStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetStatus empty');
            }

            $assetName = $params['assetName'];
            $assetDesc = $params['assetDesc'];
            $assetStatus = $params['assetStatus'];

            if (Class_db::getInstance()->db_count('ast_asset', array('asset_name'=>$assetName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ast_asset', array('asset_name'=>$assetName, 'asset_desc'=>$assetDesc, 'asset_status'=>$assetStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @param $put_vars
     * @throws Exception
     */
    public function update_asset ($assetId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetName']) || empty($put_vars['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetName empty');
            }
            if (!isset($put_vars['assetDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetDesc not exist');
            }
            if (!isset($put_vars['assetStatus']) || empty($put_vars['assetStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetStatus empty');
            }

            $assetName = $put_vars['assetName'];
            $assetDesc = $put_vars['assetDesc'];
            $assetStatus = $put_vars['assetStatus'];

            if (Class_db::getInstance()->db_count('ast_asset', array('asset_name'=>$assetName, 'asset_id'=>'<>'.$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_name'=>$assetName, 'asset_desc'=>$assetDesc, 'asset_status'=>$assetStatus), array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_asset ($assetId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_status'=>'2'), array('asset_id'=>$assetId));
            return Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$assetId), 'asset_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @return mixed
     * @throws Exception
     */
    public function activate_asset ($assetId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_status'=>'1'), array('asset_id'=>$assetId));
            return Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$assetId), 'asset_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @return mixed
     * @throws Exception
     */
    public function delete_asset ($assetId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_site', array('asset_id'=>$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_DELETE_PPM, 31);
            }

            $assetName = Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$assetId), 'asset_name', null, 1);
            Class_db::getInstance()->db_delete('ast_asset', array('asset_id'=>$assetId));

            return $assetName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
