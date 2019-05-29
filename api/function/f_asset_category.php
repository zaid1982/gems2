<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_assetCategory {

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
    public function get_assetCategory_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset_category');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetCategoryId'] = $dataLocal['asset_category_id'];
                $row_result['assetCategoryName'] = $dataLocal['asset_category_name'];
                $row_result['assetCategoryDesc'] = $this->fn_general->clear_null($dataLocal['asset_category_desc']);
                $row_result['assetGroupId'] = $dataLocal['asset_group_id'];
                $row_result['assetCategoryTimeCreated'] = $dataLocal['asset_category_time_created'];
                $row_result['assetCategoryStatus'] = $dataLocal['asset_category_status'];
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
     * @param $assetCategoryId
     * @return array
     * @throws Exception
     */
    public function get_assetCategory ($assetCategoryId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset_category', array('asset_category_id'=>$assetCategoryId), null, 1);
            $result['assetCategoryId'] = $dataLocal['asset_category_id'];
            $result['assetCategoryName'] = $dataLocal['asset_category_name'];
            $result['assetCategoryDesc'] = $this->fn_general->clear_null($dataLocal['asset_category_desc']);
            $result['assetGroupId'] = $dataLocal['asset_group_id'];
            $result['assetCategoryTimeCreated'] = $dataLocal['asset_category_time_created'];
            $result['assetCategoryStatus'] = $dataLocal['asset_category_status'];

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
    public function add_assetCategory ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('assetCategoryName', $params) || empty($params['assetCategoryName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryName empty');
            }
            if (!array_key_exists('assetCategoryDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryDesc not exist');
            }
            if (!array_key_exists('assetGroupId', $params) || empty($params['assetGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId not exist');
            }
            if (!array_key_exists('assetCategoryStatus', $params) || empty($params['assetCategoryStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryStatus empty');
            }

            $assetCategoryName = $params['assetCategoryName'];
            $assetCategoryDesc = $params['assetCategoryDesc'];
            $assetGroupId = $params['assetGroupId'];
            $assetCategoryStatus = $params['assetCategoryStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_category', array('asset_category_name'=>$assetCategoryName, 'asset_group_id'=>$assetGroupId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_CATEGORY_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ast_asset_category', array('asset_category_name'=>$assetCategoryName, 'asset_category_desc'=>$assetCategoryDesc, 'asset_group_id'=>$assetGroupId, 'asset_category_status'=>$assetCategoryStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetCategoryId
     * @param $put_vars
     * @throws Exception
     */
    public function update_assetCategory ($assetCategoryId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetCategoryId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetCategoryName']) || empty($put_vars['assetCategoryName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryName empty');
            }
            if (!isset($put_vars['assetCategoryDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryDesc not exist');
            }
            if (!isset($put_vars['assetGroupId']) || empty($put_vars['assetGroupId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetGroupId not exist');
            }
            if (!isset($put_vars['assetCategoryStatus']) || empty($put_vars['assetCategoryStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryStatus empty');
            }

            $assetCategoryName = $put_vars['assetCategoryName'];
            $assetCategoryDesc = $put_vars['assetCategoryDesc'];
            $assetGroupId = $put_vars['assetGroupId'];
            $assetCategoryStatus = $put_vars['assetCategoryStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_category', array('asset_category_name'=>$assetCategoryName, 'asset_group_id'=>$assetGroupId, 'asset_category_id'=>'<>'.$assetCategoryId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_CATEGORY_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_category', array('asset_category_name'=>$assetCategoryName, 'asset_category_desc'=>$assetCategoryDesc, 'asset_group_id'=>$assetGroupId, 'asset_category_status'=>$assetCategoryStatus), array('asset_category_id'=>$assetCategoryId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetCategoryId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_assetCategory ($assetCategoryId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetCategoryId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_category', array('asset_category_id'=>$assetCategoryId, 'asset_category_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_CATEGORY_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_category', array('asset_category_status'=>'2'), array('asset_category_id'=>$assetCategoryId));
            return Class_db::getInstance()->db_select_col('ast_asset_category', array('asset_category_id'=>$assetCategoryId), 'asset_category_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetCategoryId
     * @return mixed
     * @throws Exception
     */
    public function activate_assetCategory ($assetCategoryId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetCategoryId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_category', array('asset_category_id'=>$assetCategoryId, 'asset_category_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_CATEGORY_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_category', array('asset_category_status'=>'1'), array('asset_category_id'=>$assetCategoryId));
            return Class_db::getInstance()->db_select_col('ast_asset_category', array('asset_category_id'=>$assetCategoryId), 'asset_category_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetCategoryId
     * @return mixed
     * @throws Exception
     */
    public function delete_assetCategory ($assetCategoryId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetCategoryId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetCategoryId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_category', array('asset_category_id'=>$assetCategoryId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Category data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_asset_type', array('asset_category_id'=>$assetCategoryId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_CATEGORY_DELETE_TYPE, 31);
            }

            $assetCategoryName = Class_db::getInstance()->db_select_col('ast_asset_category', array('asset_category_id'=>$assetCategoryId), 'asset_category_name', null, 1);
            Class_db::getInstance()->db_delete('ast_asset_category', array('asset_category_id'=>$assetCategoryId));

            return $assetCategoryName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
