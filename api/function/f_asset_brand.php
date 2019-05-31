<?php
require_once 'library/constant.php';
require_once 'function/f_general.php';

class Class_assetBrand {

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
    public function get_assetBrand_list () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $result = array();
            $arr_dataLocal = Class_db::getInstance()->db_select('ast_asset_brand');
            foreach ($arr_dataLocal as $dataLocal) {
                $row_result['assetBrandId'] = $dataLocal['asset_brand_id'];
                $row_result['assetBrandName'] = $dataLocal['asset_brand_name'];
                $row_result['assetBrandDesc'] = $this->fn_general->clear_null($dataLocal['asset_brand_desc']);
                $row_result['assetBrandTimeCreated'] = str_replace('-', '/', $dataLocal['asset_brand_time_created']);
                $row_result['assetBrandStatus'] = $dataLocal['asset_brand_status'];
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
     * @param $assetBrandId
     * @return array
     * @throws Exception
     */
    public function get_assetBrand ($assetBrandId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetBrandId)) {
                throw new Exception('[' . __LINE__ . '] - Array assetBrandId empty');
            }

            $result = array();
            $dataLocal = Class_db::getInstance()->db_select_single('ast_asset_brand', array('asset_brand_id'=>$assetBrandId), null, 1);
            $result['assetBrandId'] = $dataLocal['asset_brand_id'];
            $result['assetBrandName'] = $dataLocal['asset_brand_name'];
            $result['assetBrandDesc'] = $this->fn_general->clear_null($dataLocal['asset_brand_desc']);
            $result['assetBrandTimeCreated'] = str_replace('-', '/', $dataLocal['asset_brand_time_created']);
            $result['assetBrandStatus'] = $dataLocal['asset_brand_status'];

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
    public function add_assetBrand ($params) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($params)) {
                throw new Exception('[' . __LINE__ . '] - Array params empty');
            }
            if (!array_key_exists('assetBrandName', $params) || empty($params['assetBrandName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandName empty');
            }
            if (!array_key_exists('assetBrandDesc', $params)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandDesc not exist');
            }
            if (!array_key_exists('assetBrandStatus', $params) || empty($params['assetBrandStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandStatus empty');
            }

            $assetBrandName = $params['assetBrandName'];
            $assetBrandDesc = $params['assetBrandDesc'];
            $assetBrandStatus = $params['assetBrandStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_brand', array('asset_brand_name'=>$assetBrandName)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_BRAND_SIMILAR, 31);
            }

            return Class_db::getInstance()->db_insert('ast_asset_brand', array('asset_brand_name'=>$assetBrandName, 'asset_brand_desc'=>$assetBrandDesc, 'asset_brand_status'=>$assetBrandStatus));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetBrandId
     * @param $put_vars
     * @throws Exception
     */
    public function update_assetBrand ($assetBrandId, $put_vars) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetBrandId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId empty');
            }
            if (empty($put_vars)) {
                throw new Exception('[' . __LINE__ . '] - Array put_vars empty');
            }

            if (!isset($put_vars['assetBrandName']) || empty($put_vars['assetBrandName'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandName empty');
            }
            if (!isset($put_vars['assetBrandDesc'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandDesc not exist');
            }
            if (!isset($put_vars['assetBrandStatus']) || empty($put_vars['assetBrandStatus'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandStatus empty');
            }

            $assetBrandName = $put_vars['assetBrandName'];
            $assetBrandDesc = $put_vars['assetBrandDesc'];
            $assetBrandStatus = $put_vars['assetBrandStatus'];

            if (Class_db::getInstance()->db_count('ast_asset_brand', array('asset_brand_name'=>$assetBrandName, 'asset_brand_id'=>'<>'.$assetBrandId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_BRAND_SIMILAR, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_brand', array('asset_brand_name'=>$assetBrandName, 'asset_brand_desc'=>$assetBrandDesc, 'asset_brand_status'=>$assetBrandStatus), array('asset_brand_id'=>$assetBrandId));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetBrandId
     * @return mixed
     * @throws Exception
     */
    public function deactivate_assetBrand ($assetBrandId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetBrandId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_brand', array('asset_brand_id'=>$assetBrandId, 'asset_brand_status'=>'2')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_BRAND_DEACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_brand', array('asset_brand_status'=>'2'), array('asset_brand_id'=>$assetBrandId));
            return Class_db::getInstance()->db_select_col('ast_asset_brand', array('asset_brand_id'=>$assetBrandId), 'asset_brand_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetBrandId
     * @return mixed
     * @throws Exception
     */
    public function activate_assetBrand ($assetBrandId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetBrandId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_brand', array('asset_brand_id'=>$assetBrandId, 'asset_brand_status'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_BRAND_ACTIVATE, 31);
            }

            Class_db::getInstance()->db_update('ast_asset_brand', array('asset_brand_status'=>'1'), array('asset_brand_id'=>$assetBrandId));
            return Class_db::getInstance()->db_select_col('ast_asset_brand', array('asset_brand_id'=>$assetBrandId), 'asset_brand_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $assetBrandId
     * @return mixed
     * @throws Exception
     */
    public function delete_assetBrand ($assetBrandId) {
        $constant = new Class_constant();
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            if (empty($assetBrandId)) {
                throw new Exception('[' . __LINE__ . '] - Parameter assetBrandId empty');
            }
            if (Class_db::getInstance()->db_count('ast_asset_brand', array('asset_brand_id'=>$assetBrandId)) == 0) {
                throw new Exception('[' . __LINE__ . '] - Asset Brand data not exist');
            }
            if (Class_db::getInstance()->db_count('ast_asset_model', array('asset_brand_id'=>$assetBrandId)) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ASSET_BRAND_DELETE_MODEL, 31);
            }

            $assetBrandName = Class_db::getInstance()->db_select_col('ast_asset_brand', array('asset_brand_id'=>$assetBrandId), 'asset_brand_name', null, 1);
            Class_db::getInstance()->db_delete('ast_asset_brand', array('asset_brand_id'=>$assetBrandId));

            return $assetBrandName;
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}
