<?php

class Class_att_group {

    private $constant;
    private $fn_general;

    function __construct() {
    }

    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false) {
                $msg = substr($msg, $pos + 2);
            }
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else {
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
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
            throw new Exception($this->get_exception('0001', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @param $value
     * @throws Exception
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception($this->get_exception('0002', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @return bool
     * @throws Exception
     */
    public function __isset($property) {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        } else {
            throw new Exception($this->get_exception('0003', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $property
     * @throws Exception
     */
    public function __unset($property) {
        if (property_exists($this, $property)) {
            unset($this->$property);
        } else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist [' . $property . ']'));
        }
    }

    /**
     * @param $siteId
     * @return string
     * @throws Exception
     */
    public function getAttGroupBySite($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($siteId));
            return Class_db::getInstance()->db_select2('vw_att_group', array('site_id'=>$siteId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $attGroupId
     * @return string
     * @throws Exception
     */
    public function getAttGroup ($attGroupId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($attGroupId));
            return Class_db::getInstance()->db_select_single2('vw_att_group', array('att_group.att_group_id'=>$attGroupId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getAttSiteList () {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            return Class_db::getInstance()->db_select2('vw_attendance_site', array());
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return string
     * @throws Exception
     */
    public function getAttSite ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            $this->fn_general->checkEmptyParams(array($siteId));
            return Class_db::getInstance()->db_select_single2('vw_attendance_site', array('s.site_id'=>$siteId));
        } catch (Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $siteId
     * @return mixed
     * @throws Exception
     */
    public function activateAttSite ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            $this->fn_general->checkEmptyParams(array($siteId));
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId, 'site_is_attendance'=>'1')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_ACTIVATE, 31);
            }
            Class_db::getInstance()->db_update('cli_site', array('site_is_attendance'=>'1'), array('site_id'=>$siteId));
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
    public function deactivateAttSite ($siteId) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;

            $this->fn_general->checkEmptyParams(array($siteId));
            if (Class_db::getInstance()->db_count('cli_site', array('site_id'=>$siteId, 'site_is_attendance'=>'0')) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_SITE_DEACTIVATE, 31);
            }
            Class_db::getInstance()->db_update('cli_site', array('site_is_attendance'=>'0'), array('site_id'=>$siteId));
            return Class_db::getInstance()->db_select_col('cli_site', array('site_id'=>$siteId), 'site_name', null, 1);
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @param array $maps
     * @return mixed
     * @throws Exception
     */
    public function addAttGroup ($params=array(), $maps=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;
            $this->fn_general->checkEmptyParamsArray($params, array('siteId', 'attGroupName', 'attGroupSupervisor', 'attGroupCategory', 'attGroupHoliday', 'attGroupReqWeekHours',
                'attGroupShiftMode', 'attGroupDayShiftStart', 'attGroupDayShiftEnd', 'attGroupNightShiftStart', 'attGroupNightShiftEnd'));
            $this->fn_general->checkEmptyParamsArray($maps, array('coordinates', 'mapCenter', 'zoomLevel'));

            if (empty($maps['coordinates'])) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ATT_NO_POLYGON, 31);
            }
            if (Class_db::getInstance()->db_count('att_group', array('site_id'=>$params['siteId'], 'att_group_name'=>$params['attGroupName'])) > 0) {
                throw new Exception('[' . __LINE__ . '] - '.$constant::ERR_ATT_GROUP_NAME_EXIST, 31);
            }

            $params['attGroupMapCenter'] = "|ST_GEOMFROMTEXT('POINT".str_replace(',', ' ', $maps['mapCenter'])."')";
            $params['attGroupMapZoom'] = $maps['zoomLevel'];
            $coordinateStr = '';
            foreach ($maps['coordinates'] as $coordinates) {
                $coordinateStr .= $coordinates['lat'].' '.$coordinates['lng'].',';
            }
            $coordinateStr .= $maps['coordinates'][0]['lat'].' '.$maps['coordinates'][0]['lng'];
            $params['attGroupPolygon'] = "|ST_GEOMFROMTEXT('POLYGON((".$coordinateStr."))')";
            return Class_db::getInstance()->db_insert('att_group', $this->fn_general->convertToMysqlArrAll($params));
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $attGroupId
     * @param array $params
     * @param array $maps
     * @return mixed
     * @throws Exception
     */
    public function updateAttGroup ($attGroupId, $params=array(), $maps=array()) {
        try {
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $constant = $this->constant;
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, '$maps '.json_encode($maps));

            if (array_key_exists('coordinates',$maps)) {
                throw new Exception('[' . __LINE__ . '] - exist', 31);
            }
        }
        catch(Exception $ex) {
            $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}