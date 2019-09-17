<?php

class Class_asset {

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
     * @param $contractId
     * @return array
     * @throws Exception
     */
    public function get_asset_list ($contractId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($contractId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset', array('contract_id'=>$contractId));
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetId'] = $dataLocal['asset_id'];
                $row_result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
                $row_result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
                $row_result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
                $row_result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
                $row_result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
                $row_result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
                $row_result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
                $row_result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
                $row_result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
                $row_result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
                $row_result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
                $row_result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
                $row_result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
                $row_result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
                $row_result['assetBlock'] = $this->fn_general->clear_null($dataLocal['asset_block']);
                $row_result['assetLevel'] = $this->fn_general->clear_null($dataLocal['asset_level']);
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
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $result['assetId'] = $dataLocal['asset_id'];
            $result['assetNo'] = $this->fn_general->clear_null($dataLocal['asset_no']);
            $result['assetName'] = $this->fn_general->clear_null($dataLocal['asset_name']);
            $result['assetSerialNo'] = $this->fn_general->clear_null($dataLocal['asset_serial_no']);
            $result['assetDesc'] = $this->fn_general->clear_null($dataLocal['asset_desc']);
            $result['assetCapacity'] = $this->fn_general->clear_null($dataLocal['asset_capacity']);
            $result['assetLocationCode'] = $this->fn_general->clear_null($dataLocal['asset_location_code']);
            $result['assetLocationDesc'] = $this->fn_general->clear_null($dataLocal['asset_location_desc']);
            $result['assetGroupId'] = $this->fn_general->clear_null($dataLocal['asset_group_id']);
            $result['assetCategoryId'] = $this->fn_general->clear_null($dataLocal['asset_category_id']);
            $result['assetTypeId'] = $this->fn_general->clear_null($dataLocal['asset_type_id']);
            $result['assetBrandId'] = $this->fn_general->clear_null($dataLocal['asset_brand_id']);
            $result['assetModelId'] = $this->fn_general->clear_null($dataLocal['asset_model_id']);
            $result['contractId'] = $this->fn_general->clear_null($dataLocal['contract_id']);
            $result['ppmGroupId'] = $this->fn_general->clear_null($dataLocal['ppm_group_id']);
            $result['assetBlock'] = $this->fn_general->clear_null($dataLocal['asset_block']);
            $result['assetLevel'] = $this->fn_general->clear_null($dataLocal['asset_level']);
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
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('contractId', $params) || empty($params['contractId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter contractId empty');
            }
            if (!array_key_exists('assetGroupId', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId not exist');
            }
            if (!array_key_exists('assetCategoryId', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (!array_key_exists('assetTypeId', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }

            $contractId = $params['contractId'];
            $assetGroupId = $params['assetGroupId'];
            $assetCategoryId = $params['assetCategoryId'];
            $assetTypeId = $params['assetTypeId'];

            return Class_db::getInstance()->db_insert('ast_asset', array('contract_id'=>$contractId, 'asset_group_id'=>$assetGroupId, 'asset_category_id'=>$assetCategoryId,
                'asset_type_id'=>$assetTypeId, 'asset_status'=>'5'));
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
    public function save_asset ($assetId, $put_vars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetName not exist');
            }
            if (!isset($put_vars['assetNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetNo not exist');
            }
            if (!isset($put_vars['assetSerialNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetSerialNo not exist');
            }
            if (!isset($put_vars['assetDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetDesc not exist');
            }
            if (!isset($put_vars['assetGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId not exist');
            }
            if (!isset($put_vars['assetCategoryId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId not exist');
            }
            if (!isset($put_vars['assetTypeId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId not exist');
            }
            if (!isset($put_vars['assetBrandId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId not exist');
            }
            if (!isset($put_vars['assetModelId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId not exist');
            }
            if (!isset($put_vars['assetLocationCode'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLocationCode not exist');
            }
            if (!isset($put_vars['assetLocationDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLocationDesc not exist');
            }
            if (!isset($put_vars['ppmGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId not exist');
            }
            if (!isset($put_vars['assetCapacity'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCapacity not exist');
            }
            if (!isset($put_vars['assetBlock'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBlock not exist');
            }
            if (!isset($put_vars['assetLevel'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLevel not exist');
            }

            $assetNo = $put_vars['assetNo'];
            $assetSerialNo = $put_vars['assetSerialNo'];
            $updateArr = array(
                'asset_name'=>$put_vars['assetName'],
                'asset_no'=>$assetNo,
                'asset_serial_no'=>$assetSerialNo,
                'asset_desc'=>$put_vars['assetDesc'],
                'asset_group_id'=>$put_vars['assetGroupId'],
                'asset_category_id'=>$put_vars['assetCategoryId'],
                'asset_type_id'=>$put_vars['assetTypeId'],
                'asset_brand_id'=>$put_vars['assetBrandId'],
                'asset_model_id'=>$put_vars['assetModelId'],
                'asset_location_code'=>$put_vars['assetLocationCode'],
                'asset_location_desc'=>$put_vars['assetLocationDesc'],
                'ppm_group_id'=>$put_vars['ppmGroupId'],
                'asset_capacity'=>$put_vars['assetCapacity'],
                'asset_block'=>$put_vars['assetBlock'],
                'asset_level'=>$put_vars['assetLevel']
            );

            $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $contractId = $asset['contract_id'];

            if ($asset['asset_status'] != '5') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SUBMITTED, 31);
            }
            if (!empty($put_vars['assetNo']) && Class_db::getInstance()->db_count('ast_asset', array('asset_no'=>$assetNo, 'contract_id'=>$contractId, 'asset_id'=>'<>'.$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }
            if (!empty($put_vars['assetSerialNo']) && Class_db::getInstance()->db_count('ast_asset', array('asset_serial_no'=>$assetSerialNo, 'contract_id'=>$contractId, 'asset_id'=>'<>'.$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR_SERIAL_NO, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', $updateArr, array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }


    /**
     * @param $assetId
     * @param $put_vars
     * @param $userId
     * @throws Exception
     */
    public function submit_asset ($assetId, $put_vars, $userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }
            if (empty($userId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
            }

            if (!isset($put_vars['assetName']) && empty($put_vars['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetName empty');
            }
            if (!isset($put_vars['assetNo']) && empty($put_vars['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetNo empty');
            }
            if (!isset($put_vars['assetSerialNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetSerialNo empty');
            }
            if (!isset($put_vars['assetDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetDesc not exist');
            }
            if (!isset($put_vars['assetGroupId']) && empty($put_vars['assetGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId empty');
            }
            if (!isset($put_vars['assetCategoryId']) && empty($put_vars['assetCategoryId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (!isset($put_vars['assetTypeId']) && empty($put_vars['assetTypeId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetTypeId empty');
            }
            if (!isset($put_vars['assetBrandId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId not exist');
            }
            if (!isset($put_vars['assetModelId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId not exist');
            }
            if (!isset($put_vars['ppmGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId not exist');
            }
            if (!isset($put_vars['assetLocationCode']) && empty($put_vars['assetLocationCode'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLocationCode empty');
            }
            if (!isset($put_vars['assetLocationDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCapacity not exist');
            }
            if (!isset($put_vars['assetCapacity'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCapacity not exist');
            }
            if (!isset($put_vars['assetBlock'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBlock not exist');
            }
            if (!isset($put_vars['assetLevel'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLevel not exist');
            }

            $assetNo = $put_vars['assetNo'];
            $assetSerialNo = $put_vars['assetSerialNo'];
            $updateArr = array(
                'asset_name'=>$put_vars['assetName'],
                'asset_no'=>$assetNo,
                'asset_serial_no'=>$assetSerialNo,
                'asset_desc'=>$put_vars['assetDesc'],
                'asset_group_id'=>$put_vars['assetGroupId'],
                'asset_category_id'=>$put_vars['assetCategoryId'],
                'asset_type_id'=>$put_vars['assetTypeId'],
                'asset_brand_id'=>$put_vars['assetBrandId'],
                'asset_model_id'=>$put_vars['assetModelId'],
                'asset_location_code'=>$put_vars['assetLocationCode'],
                'asset_location_desc'=>$put_vars['assetLocationDesc'],
                'asset_capacity'=>$put_vars['assetCapacity'],
                'ppm_group_id'=>$put_vars['ppmGroupId'],
                'asset_block'=>$put_vars['assetBlock'],
                'asset_level'=>$put_vars['assetLevel'],
                'asset_registered_by'=>$userId,
                'asset_time_registered'=>'Now()',
                'asset_status'=>'1'
            );

            $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $contractId = $asset['contract_id'];

            if ($asset['asset_status'] != '5') {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SUBMITTED, 31);
            }
            if (!empty($put_vars['assetNo']) && Class_db::getInstance()->db_count('ast_asset', array('asset_no'=>$assetNo, 'contract_id'=>$contractId, 'asset_id'=>'<>'.$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }
            if (!empty($put_vars['assetSerialNo']) && Class_db::getInstance()->db_count('ast_asset', array('asset_serial_no'=>$assetSerialNo, 'contract_id'=>$contractId, 'asset_id'=>'<>'.$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR_SERIAL_NO, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', $updateArr, array('asset_id'=>$assetId));
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
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetName']) && empty($put_vars['assetName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetName empty');
            }
            if (!isset($put_vars['assetSerialNo'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetSerialNo empty');
            }
            if (!isset($put_vars['assetDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetDesc not exist');
            }
            if (!isset($put_vars['assetBrandId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId not exist');
            }
            if (!isset($put_vars['assetModelId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetModelId not exist');
            }
            if (!isset($put_vars['ppmGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmGroupId not exist');
            }
            if (!isset($put_vars['assetLocationCode'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLocationCode not exist');
            }
            if (!isset($put_vars['assetLocationDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLocationDesc not exist');
            }
            if (!isset($put_vars['assetCapacity'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCapacity not exist');
            }
            if (!isset($put_vars['assetBlock'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBlock not exist');
            }
            if (!isset($put_vars['assetLevel'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetLevel not exist');
            }

            $assetSerialNo = $put_vars['assetSerialNo'];
            $updateArr = array(
                'asset_name'=>$put_vars['assetName'],
                'asset_serial_no'=>$assetSerialNo,
                'asset_desc'=>$put_vars['assetDesc'],
                'asset_brand_id'=>$put_vars['assetBrandId'],
                'asset_model_id'=>$put_vars['assetModelId'],
                'asset_location_code'=>$put_vars['assetLocationCode'],
                'asset_location_desc'=>$put_vars['assetLocationDesc'],
                'ppm_group_id'=>$put_vars['ppmGroupId'],
                'asset_capacity'=>$put_vars['assetCapacity'],
                'asset_block'=>$put_vars['assetBlock'],
                'asset_level'=>$put_vars['assetLevel']
            );

            $asset = Class_db::getInstance()->db_select_single('ast_asset', array('asset_id'=>$assetId), null, 1);
            $contractId = $asset['contract_id'];

            if (!empty($put_vars['assetSerialNo']) && Class_db::getInstance()->db_count('ast_asset', array('asset_serial_no'=>$assetSerialNo, 'contract_id'=>$contractId, 'asset_id'=>'<>'.$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR_SERIAL_NO, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', $updateArr, array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @throws Exception
     */
    public function deactivate_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_status'=>'2'), array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @throws Exception
     */
    public function activate_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset', array('asset_status'=>'1'), array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetId
     * @throws Exception
     */
    public function delete_asset ($assetId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($assetId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset', array('asset_id'=>$assetId, 'asset_status'=>'5')) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset data not exist');
            }
            if (Class_db::getInstance()->db_count('ppm', array('asset_id'=>$assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_DELETE_PPM, 31);
            }

            Class_db::getInstance()->db_delete('ast_asset', array('asset_id'=>$assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $clientId
     * @param string $contractId
     * @return
     * @throws Exception
     */
    public function get_total_asset ($clientId='', $contractId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (!empty($clientId) && empty($contractId)) {
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                if (!empty($siteIds)) {
                    $siteIdStr = implode(',', $siteIds);
                    $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>'('.$siteIdStr.')', 'contract_status'=>'1'), 'contract_id');
                    if (!empty($contractIds)) {
                        $contractId = '('.$contractIds.')';
                    }
                }
            }
            return Class_db::getInstance()->db_select_col('vw_count_asset', array('contract_id'=>$contractId), 'total');
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
