<?php

class Class_asset {

    private $constant;
    private $fn_general;
    private $assetId;

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
                $row_result['assetManufacturer'] = $this->fn_general->clear_null($dataLocal['asset_manufacturer']);
                $row_result['assetSupplier'] = $this->fn_general->clear_null($dataLocal['asset_supplier']);
                $row_result['assetAgency'] = $this->fn_general->clear_null($dataLocal['asset_agency']);
                $row_result['assetDepartment'] = $this->fn_general->clear_null($dataLocal['asset_department']);
                $row_result['assetConstructionZone'] = $this->fn_general->clear_null($dataLocal['asset_construction_zone']);
                $row_result['assetOperationZone'] = $this->fn_general->clear_null($dataLocal['asset_operation_zone']);
                $row_result['assetRoom'] = $this->fn_general->clear_null($dataLocal['asset_room']);
                $row_result['assetCompartment'] = $this->fn_general->clear_null($dataLocal['asset_compartment']);
                $row_result['assetAuthEmployee'] = $this->fn_general->clear_null($dataLocal['asset_auth_employee']);
                $row_result['assetCriticality'] = $this->fn_general->clear_null($dataLocal['asset_criticality']);
                $row_result['assetContractor'] = $this->fn_general->clear_null($dataLocal['asset_contractor']);
                $row_result['assetWarranty'] = $this->fn_general->clear_null($dataLocal['asset_warranty']);
                $row_result['assetWarrantyExpDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_warranty_exp_date']));
                $row_result['assetLifeCycle'] = $this->fn_general->clear_null($dataLocal['asset_life_cycle']);
                $row_result['assetWarrantyNotes'] = $this->fn_general->clear_null($dataLocal['asset_warranty_notes']);
                $row_result['assetTechnicianNotes'] = $this->fn_general->clear_null($dataLocal['asset_technician_notes']);
                $row_result['assetPurchasePrice'] = $this->fn_general->clear_null($dataLocal['asset_purchase_price']);
                $row_result['assetCommissionedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_commissioned_date']));
                $row_result['assetDisposedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_disposed_date']));
                $row_result['assetCurrentValue'] = $this->fn_general->clear_null($dataLocal['asset_current_value']);
                $row_result['assetEstimatedLife'] = $this->fn_general->clear_null($dataLocal['asset_estimated_life']);
                $row_result['assetLifetimeDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_lifetime_date']));
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
            $result['assetManufacturer'] = $this->fn_general->clear_null($dataLocal['asset_manufacturer']);
            $result['assetSupplier'] = $this->fn_general->clear_null($dataLocal['asset_supplier']);
            $result['assetAgency'] = $this->fn_general->clear_null($dataLocal['asset_agency']);
            $result['assetDepartment'] = $this->fn_general->clear_null($dataLocal['asset_department']);
            $result['assetConstructionZone'] = $this->fn_general->clear_null($dataLocal['asset_construction_zone']);
            $result['assetOperationZone'] = $this->fn_general->clear_null($dataLocal['asset_operation_zone']);
            $result['assetRoom'] = $this->fn_general->clear_null($dataLocal['asset_room']);
            $result['assetCompartment'] = $this->fn_general->clear_null($dataLocal['asset_compartment']);
            $result['assetAuthEmployee'] = $this->fn_general->clear_null($dataLocal['asset_auth_employee']);
            $result['assetCriticality'] = $this->fn_general->clear_null($dataLocal['asset_criticality']);
            $result['assetContractor'] = $this->fn_general->clear_null($dataLocal['asset_contractor']);
            $result['assetWarranty'] = $this->fn_general->clear_null($dataLocal['asset_warranty']);
            $result['assetWarrantyExpDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_warranty_exp_date']));
            $result['assetLifeCycle'] = $this->fn_general->clear_null($dataLocal['asset_life_cycle']);
            $result['assetWarrantyNotes'] = $this->fn_general->clear_null($dataLocal['asset_warranty_notes']);
            $result['assetTechnicianNotes'] = $this->fn_general->clear_null($dataLocal['asset_technician_notes']);
            $result['assetPurchasePrice'] = $this->fn_general->clear_null($dataLocal['asset_purchase_price']);
            $result['assetCommissionedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_commissioned_date']));
            $result['assetDisposedDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_disposed_date']));
            $result['assetCurrentValue'] = $this->fn_general->clear_null($dataLocal['asset_current_value']);
            $result['assetEstimatedLife'] = $this->fn_general->clear_null($dataLocal['asset_estimated_life']);
            $result['assetLifetimeDate'] = str_replace('-', '/', $this->fn_general->clear_null($dataLocal['asset_lifetime_date']));
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
     * @return mixed
     * @throws Exception
     */
    public function create_asset () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            return Class_db::getInstance()->db_insert('ast_asset', array('asset_status'=>'5'));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $userId
     * @throws Exception
     */
    public function submit_asset ($userId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($this->assetId)) { throw new Exception('[' . __LINE__ . '] - Parameter assetId empty'); }
            if (empty($userId)) { throw new Exception('[' . __LINE__ . '] - Parameter userId empty'); }

            $assetStatus = Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$this->assetId), 'asset_status', null, 1);
            if ($assetStatus !== '5') { throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SUBMITTED, 31); }

            $updateArr = array(
                'asset_registered_by'=>$userId,
                'asset_time_registered'=>'Now()',
                'asset_status'=>'1'
            );
            Class_db::getInstance()->db_update('ast_asset', $updateArr, array('asset_id'=>$this->assetId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $putVars
     * @throws Exception
     */
    public function update_asset ($putVars) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            if (empty($this->assetId)) { throw new Exception('[' . __LINE__ . '] - Parameter assetId empty', 31); }
            if (empty($putVars)) { throw new Exception('[' . __LINE__ . '] - Array putVars empty'); }

            $contractId = Class_db::getInstance()->db_select_col('ast_asset', array('asset_id'=>$this->assetId), 'contract_id', null, 1);
            $params = array();
            if (isset($putVars['assetNo'])) {                   $params['asset_no'] = $putVars['assetNo']; }
            if (isset($putVars['assetSerialNo'])) {             $params['asset_serial_no'] = $putVars['assetSerialNo']; }
            if (isset($putVars['contractId'])) {                $params['contract_id'] = $putVars['contractId']; }
            if (isset($putVars['assetName'])) {                 $params['asset_name'] = $putVars['assetName']; }
            if (isset($putVars['assetDesc'])) {                 $params['asset_desc'] = $putVars['assetDesc']; }
            if (isset($putVars['assetGroupId'])) {              $params['asset_group_id'] = $putVars['assetGroupId']; }
            if (isset($putVars['assetCategoryId'])) {           $params['asset_category_id'] = $putVars['assetCategoryId']; }
            if (isset($putVars['assetTypeId'])) {               $params['asset_type_id'] = $putVars['assetTypeId']; }
            if (isset($putVars['assetBrandId'])) {              $params['asset_brand_id'] = $putVars['assetBrandId']; }
            if (isset($putVars['assetModelId'])) {              $params['asset_model_id'] = $putVars['assetModelId']; }
            if (isset($putVars['assetLocationCode'])) {         $params['asset_location_code'] = $putVars['assetLocationCode']; }
            if (isset($putVars['assetLocationDesc'])) {         $params['asset_location_desc'] = $putVars['assetLocationDesc']; }
            if (isset($putVars['ppmGroupId'])) {                $params['ppm_group_id'] = $putVars['ppmGroupId']; }
            if (isset($putVars['assetCapacity'])) {             $params['asset_capacity'] = $putVars['assetCapacity']; }
            if (isset($putVars['assetBlock'])) {                $params['asset_block'] = $putVars['assetBlock']; }
            if (isset($putVars['assetLevel'])) {                $params['asset_level'] = $putVars['assetLevel']; }
            if (isset($putVars['assetManufacturer'])) {         $params['asset_manufacturer'] = $putVars['assetManufacturer']; }
            if (isset($putVars['assetSupplier'])) {             $params['asset_supplier'] = $putVars['assetSupplier']; }
            if (isset($putVars['assetAgency'])) {               $params['asset_agency'] = $putVars['assetAgency']; }
            if (isset($putVars['assetDepartment'])) {           $params['asset_department'] = $putVars['assetDepartment']; }
            if (isset($putVars['assetConstructionZone'])) {     $params['asset_construction_zone'] = $putVars['assetConstructionZone']; }
            if (isset($putVars['assetOperationZone'])) {        $params['asset_operation_zone'] = $putVars['assetOperationZone']; }
            if (isset($putVars['assetRoom'])) {                 $params['asset_room'] = $putVars['assetRoom']; }
            if (isset($putVars['assetCompartment'])) {          $params['asset_compartment'] = $putVars['assetCompartment']; }
            if (isset($putVars['assetAuthEmployee'])) {         $params['asset_auth_employee'] = $putVars['assetAuthEmployee']; }
            if (isset($putVars['assetCriticality'])) {          $params['asset_criticality'] = $putVars['assetCriticality']; }
            if (isset($putVars['assetContractor'])) {           $params['asset_contractor'] = $putVars['assetContractor']; }
            if (isset($putVars['assetWarranty'])) {             $params['asset_warranty'] = $putVars['assetWarranty']; }
            if (isset($putVars['assetWarrantyExpDate'])) {      $params['asset_warranty_exp_date'] = $putVars['assetWarrantyExpDate']; }
            if (isset($putVars['assetLifeCycle'])) {            $params['asset_life_cycle'] = $putVars['assetLifeCycle']; }
            if (isset($putVars['assetWarrantyNotes'])) {        $params['asset_warranty_notes'] = $putVars['assetWarrantyNotes']; }
            if (isset($putVars['assetTechnicianNotes'])) {      $params['asset_technician_notes'] = $putVars['assetTechnicianNotes']; }
            if (isset($putVars['assetPurchasePrice'])) {        $params['asset_purchase_price'] = $putVars['assetPurchasePrice']; }
            if (isset($putVars['assetCommissionedDate'])) {     $params['asset_commissioned_date'] = $putVars['assetCommissionedDate']; }
            if (isset($putVars['assetDisposedDate'])) {         $params['asset_disposed_date'] = $putVars['assetDisposedDate']; }
            if (isset($putVars['assetCurrentValue'])) {         $params['asset_current_value'] = $putVars['assetCurrentValue']; }
            if (isset($putVars['assetEstimatedLife'])) {        $params['asset_estimated_life'] = $putVars['assetEstimatedLife']; }
            if (isset($putVars['assetLifetimeDate'])) {         $params['asset_lifetime_date'] = $putVars['assetLifetimeDate']; }

            if (isset($params['asset_no']) && !empty($params['asset_no'])
                && Class_db::getInstance()->db_count('ast_asset', array('asset_no'=>$params['asset_no'], 'contract_id'=>$contractId, 'asset_id'=>'<>'.$this->assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR, 31);
            }
            if (isset($params['asset_serial_no']) && !empty($params['asset_serial_no'])
                && Class_db::getInstance()->db_count('ast_asset', array('asset_serial_no'=>$params['asset_serial_no'], 'contract_id'=>$contractId, 'asset_id'=>'<>'.$this->assetId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_SIMILAR_SERIAL_NO, 31);
            }
            Class_db::getInstance()->db_update('ast_asset', $params, array('asset_id'=>$this->assetId));
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
     * @param string $siteId
     * @return
     * @throws Exception
     */
    public function get_total_asset ($clientId='', $siteId='') {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($clientId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetId empty');
            }

            if (empty($siteId)) {
                $siteIds = Class_db::getInstance()->db_select_colm('cli_site', array('client_id'=>$clientId, 'site_status'=>'1'), 'site_id');
                $siteIdStr = '('.implode(',', $siteIds).')';
            } else {
                $siteIdStr = $siteId;
            }
            $contractIds = Class_db::getInstance()->db_select_colm('cli_contract', array('site_id'=>$siteIdStr, 'contract_status'=>'1'), 'contract_id');
            if (!empty($contractIds)) {
                $contractId = '('.implode(',', $contractIds).')';
                return Class_db::getInstance()->db_select_col('vw_count_asset', array('contract_id'=>$contractId), 'total');
            }
            return '';
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
