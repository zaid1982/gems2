<?php

class Class_general {
     
    private $log_dir = '';
    private $constant;
    
    function __construct()
    {
        $config = parse_ini_file('library/config.ini');
        $this->log_dir = $config['log_dir'];
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
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public function log_debug ($class, $function, $line, $msg) {
        $debugMsg = date("Y/m/d h:i:sa")." [".$class.":".$function.":".$line."] - ".$msg."\r\n";
        error_log($debugMsg, 3, $this->log_dir.'/debug/debug_'.date("Ymd").'.log');
        //error_log($debugMsg, 3, 'C:\Users\User\logs\gems\debug\debug_'.date("Ymd").'.log');
    }

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public function log_error ($class, $function, $line, $msg) {
        $debugMsg = date("Y/m/d h:i:sa")." [".$class.":".$function.":".$line."] - (ERROR) ".$msg."\r\n";
        error_log($debugMsg, 3, $this->log_dir.'/debug/debug_'.date("Ymd").'.log');
        //error_log($debugMsg, 3, 'C:\Users\User\logs\gems\debug\debug_'.date("Ymd").'.log');
        $debugMsg = date("Y/m/d h:i:sa")." [".$class.":".$function.":".$line."] - ".$msg."\r\n";
        error_log($debugMsg, 3, $this->log_dir.'/error/error_'.date("Ymd").'.log');
        //error_log($debugMsg, 3, 'C:\Users\User\logs\gems\error\error_'.date("Ymd").'.log');
    }

    /**
     * @param $param
     * @param string $replaced
     * @return string
     * @throws Exception
     */
    public function clear_null ($param, $replaced='') {
        try {
            if (is_null($param)) {
                return $replaced;
            }
            return $param;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $audit_action_id
     * @param string $user_id
     * @param string $remark
     * @return mixed
     * @throws Exception
     */
    public function save_audit ($audit_action_id='', $user_id='', $remark='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            if ($audit_action_id === '') {
                throw new Exception('(ErrCode:0052) [' . __LINE__ . '] - Parameter audit_action_id empty');   
            }
            
            $place = '';
            
            if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']!='') {
                $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
            } else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']!='') {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if(isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED']!='') {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
            } else if(isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR']!='') {
                $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
            } else if(isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED']!='') {
                $ipaddress = $_SERVER['HTTP_FORWARDED'];
            } else if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']!='') {
                $ipaddress = $_SERVER['REMOTE_ADDR'];
            } else {
                $ipaddress = 'UNKNOWN';
            }
            
            /*if (!in_array($ipaddress, array('', 'UNKNOWN', '::1'), true)) {
                $details = json_decode(file_get_contents("http://ipinfo.io/$ipaddress/json"));
                if (isset($details->city)) {
                    $place = $details->city;
                }
            }*/
            return Class_db::getInstance()->db_insert('sys_audit', array('audit_action_id'=>$audit_action_id, 'user_id'=>$user_id, 'audit_ip'=>$ipaddress, 'audit_place'=>$place, 'audit_remark'=>$remark));
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function generateRandomString ($length = 20) {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $versionId
     * @throws Exception
     */
    public function updateVersion ($versionId='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            if ($versionId === '') {
                throw new Exception('(ErrCode:0053) [' . __LINE__ . '] - Parameter versionId empty');   
            }            
            Class_db::getInstance()->db_update('sys_version', array('version_no'=>'++'), array('version_id'=>(string)$versionId));
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $folder
     * @return bool|string
     */
    public function folderExist($folder) {
        $path = realpath($folder);
        return ($path !== false AND is_dir($path)) ? $path : false;
    }

    /**
     * @param array $uploadDetails
     * @param string $documentId
     * @param string $userId
     * @return mixed
     * @throws Exception
     */
    public function uploadDocument ($uploadDetails=array(), $documentId='', $userId='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            
            if (empty($uploadDetails)) {
                throw new Exception('(ErrCode:0054) [' . __LINE__ . '] - Array uploadDetails empty');   
            }
            if (empty($documentId)) {
                throw new Exception('(ErrCode:0055) [' . __LINE__ . '] - Parameter documentId empty');   
            } 
            if (empty($userId)) {
                throw new Exception('(ErrCode:0056) [' . __LINE__ . '] - Parameter userId empty');   
            }

            if (!array_key_exists('name', $uploadDetails)) {
                throw new Exception('(ErrCode:0057) [' . __LINE__ . '] - Parameter upload name not exist');
            }
            if (!array_key_exists('filename', $uploadDetails) || empty($uploadDetails['filename'])) {
                throw new Exception('(ErrCode:0058) [' . __LINE__ . '] - Parameter upload filename empty');
            }
            if (!array_key_exists('size', $uploadDetails) || empty($uploadDetails['size'])) {
                throw new Exception('(ErrCode:0059) [' . __LINE__ . '] - Parameter upload size empty');
            }
            if (!array_key_exists('type', $uploadDetails)) {
                throw new Exception('(ErrCode:0060) [' . __LINE__ . '] - Parameter upload type empty');
            }
            if (!array_key_exists('data', $uploadDetails) || empty($uploadDetails['data'])) {
                throw new Exception('(ErrCode:0061) [' . __LINE__ . '] - Parameter upload data empty');
            }

            $uploadName = $uploadDetails['name'];
            $uploadUplname = $uploadDetails['filename'];
            $uploadFilesize = $uploadDetails['size']; 
            $uploadBlobType = $uploadDetails['type']; 
            $uploadBlobData = $uploadDetails['data'];             
            $pos = strrpos($uploadUplname,'.');
            $uploadExtension = $pos !== false ? substr($uploadUplname, $pos+1) : ' - ';
            $uploadFileWidth = array_key_exists('width', $uploadDetails) ? $uploadDetails['width'] : '';
            $uploadFileHeight = array_key_exists('height', $uploadDetails) ? $uploadDetails['height'] : '';
            
            $uploadId = Class_db::getInstance()->db_insert('sys_upload', array('document_id'=>$documentId, 'upload_name'=>$uploadName, 'upload_uplname'=>$uploadUplname, 'upload_filesize'=>$uploadFilesize, 'upload_blob_type'=>$uploadBlobType,
                'upload_extension'=>$uploadExtension, 'upload_created_by'=>$userId, 'upload_file_width'=>$uploadFileWidth, 'upload_file_height'=>$uploadFileHeight));
            $uploadFilename = 'f_'.(10000 + intval($uploadId));
            $uploadFolder = 'upload/'.$documentId.'/'.(floor(intval($uploadId)/1000));
            if (!$this->folderExist($uploadFolder)) {
                mkdir ($uploadFolder,0777, true);   
            }               
            file_put_contents($uploadFolder.'/'.$uploadFilename.'.'.$uploadExtension, base64_decode($uploadBlobData));
            Class_db::getInstance()->db_update('sys_upload', array('upload_filename'=>$uploadFilename, 'upload_folder'=>$uploadFolder), array('upload_id'=>$uploadId));
                        
            return $uploadId;            
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function deleteDocument ($uploadId='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $this->checkEmptyParams(array($uploadId));
            $sysUpload = $this->convertDbIndex(Class_db::getInstance()->db_select_single('sys_upload', array('upload_id'=>$uploadId), null, 1));
            $filePath = $sysUpload['uploadFolder'].'/'.$sysUpload['uploadFilename'].'.'.$sysUpload['uploadExtension'];
            $deleted = unlink($filePath);
            if ($deleted){
                $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'File '.$filePath.' successfully deleted.');
            } else{
                $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'File '.$filePath.' could not be deleted.');
                $this->log_error(__CLASS__, __FUNCTION__, __LINE__, 'File '.$filePath.' could not be deleted.');
            }
            Class_db::getInstance()->db_delete('sys_upload', array('upload_id'=>$uploadId));
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $uploadId
     * @return array
     * @throws Exception
     */
    public function getDocument ($uploadId='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            if (empty($uploadId)) {
                throw new Exception('(ErrCode:0061) [' . __LINE__ . '] - Parameter uploadId empty');   
            }
            
            $sysUpload = Class_db::getInstance()->db_select_single('vw_sys_upload', array('upload_id'=>$uploadId), null, 1);
            $document = Class_db::getInstance()->db_select_single('ref_document', array('document_id'=>$sysUpload['document_id']), null, 1);
            
            return 
                array(
                    'documentDesc'=>$document['document_desc'], 
                    'documentFilename'=>$sysUpload['upload_uplname'],
                    'documentSrc'=>$constant::URL.$sysUpload['upload_folder'].'/'.$sysUpload['upload_filename'].'.'.$sysUpload['upload_extension']
                );            
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $pdfId
     * @return string
     * @throws Exception
     */
    public function getPdf ($pdfId='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            if (empty($pdfId)) {
                throw new Exception('(ErrCode:0061) [' . __LINE__ . '] - Parameter pdfId empty');
            }

            $pdf = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id'=>$pdfId), null, 1);
            return $constant::URL.$pdf['pdf_folder'].'/'.$pdf['pdf_filename'].'?t='.time();
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $uploadId
     * @return array
     * @throws Exception
     */
    public function getUpload ($uploadId='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            $constant = $this->constant;

            $this->checkEmptyParams(array($uploadId));
            $upload = $this->convertDbIndex(Class_db::getInstance()->db_select_single('sys_upload', array('upload_id'=>$uploadId), null, 1));
            return
                array(
                    'title'=>$upload['uploadName'],
                    'filename'=>$upload['uploadUplname'],
                    'src'=>$constant::URL.$upload['uploadFolder'].'/'.$upload['uploadFilename'].'.'.$upload['uploadExtension'].'?t='.time()
                );
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $date
     * @return string
     * @throws Exception
     */
    public function convertMysqlDate ($date='') {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            if (empty($date)) {
                return '';
            }
            
            $newDate = '';
            $arrMonth = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
            $dateSplit = explode('-', $date);
            if (sizeof($dateSplit) === 3) {                
                $newDate = intval($dateSplit[2]).' '.$arrMonth[intval($dateSplit[1])].' '.$dateSplit[0];
            }
            return $newDate;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param string $date
     * @return string
     * @throws Exception
     */
    public function convertDateToDisplay ($date='') {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);
            if (empty($date)) {
                return '';
            }

            $newDate = new DateTime($date);
            if(strlen($date)>10) {
                return $newDate->format('j/n/Y g:i:sa');
            } else {
                return $newDate->format('j/n/Y');
            }
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPpmFrequency () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_frequency', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['frequency_id'])] = $dataLocal['frequency_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPpmFrequencyCode () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_frequency', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['frequency_id'])] = $dataLocal['frequency_code'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRefStatus () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_status', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['status_id'])] = $dataLocal['status_desc'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRefRole () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_role', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['role_id'])] = $dataLocal['role_desc'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUserFullName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('sys_user', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['user_id'])] = $dataLocal['user_first_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFlowName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('wfl_flow', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['flow_id'])] = $dataLocal['flow_desc'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCheckPointName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('wfl_checkpoint', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['checkpoint_id'])] = $dataLocal['checkpoint_desc'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getGroupName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('sys_group', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['group_id'])] = $dataLocal['group_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getLocationCode () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_location_code');
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['location_code_id'])] = $dataLocal['location_code_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSiteName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('cli_site', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['site_id'])] = $dataLocal['site_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPpmGroupName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('ppm_group', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['ppm_group_id'])] = $dataLocal['ppm_group_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSeverityName () {
        try {
            $refArray = array('');
            $arr_dataLocal = Class_db::getInstance()->db_select('ref_severity', array(), null, null, 1);
            foreach ($arr_dataLocal as $dataLocal) {
                $refArray[intval($dataLocal['severity_id'])] = $dataLocal['severity_name'];
            }
            return $refArray;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $firstTime
     * @param $lastTime
     * @return string
     * @throws Exception
     */
    public function timeDiff ($firstTime, $lastTime){
        try {
            if (empty($firstTime) || empty($lastTime)) {
                return '';
            }

            $firstTime = strtotime($firstTime);
            $lastTime = strtotime($lastTime);
            $difference = $lastTime-$firstTime;
            $days = abs(floor($difference / 86400));
            $hours = abs(floor(($difference-($days * 86400))/3600));
            $mins = abs(floor(($difference-($days * 86400)-($hours * 3600))/60));
            $secs = abs(floor($difference-($days * 86400)-($hours * 3600)-($mins * 60)));
            $returnStr = '';
            if ($days == 1) {
                $returnStr .= $days." day, ";
            } else if ($days > 1) {
                $returnStr .= $days." days, ";
            }
            if ($hours == 1) {
                $returnStr .= $hours." hour, ";
            } else if ($hours > 1) {
                $returnStr .= $hours." hours, ";
            }
            if ($mins == 1) {
                $returnStr .= $mins." minute, ";
            } else if ($mins > 1) {
                $returnStr .= $mins." minutes, ";
            }
            if ($secs == 1) {
                $returnStr .= $secs." second";
            } else if ($secs > 1) {
                $returnStr .= $secs." seconds";
            }
            return $returnStr;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $firstTime
     * @param $lastTime
     * @return string
     * @throws Exception
     */
    public function timeDiffHour ($firstTime, $lastTime){
        try {
            if (empty($firstTime) || empty($lastTime)) {
                return '';
            }

            $firstTime = strtotime($firstTime);
            $lastTime = strtotime($lastTime);
            $difference = $lastTime-$firstTime;
            return $difference/3600;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $firstTime
     * @param $lastTime
     * @return string
     * @throws Exception
     */
    public function timeDiffMinute ($firstTime, $lastTime){
        try {
            if (empty($firstTime) || empty($lastTime)) {
                return '';
            }

            $firstTime = strtotime($firstTime);
            $lastTime = strtotime($lastTime);
            $difference = $lastTime-$firstTime;
            return $difference/60;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $dataInputs
     * @return array
     * @throws Exception
     */
    public function convertDbIndexs ($dataInputs) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $dataOutputs = array();
            $newIndexs = array();
            $cnt = 0;
            foreach ($dataInputs as $dataInput) {
                if ($cnt === 0) {
                    foreach ($dataInput as $key=>$value) {
                        $keyTemps = explode('_', $key);
                        foreach ($keyTemps as $j=>$keyTemp) {
                            if ($j > 0) {
                                $keyTemps[$j] = ucfirst($keyTemp);
                            }
                        }
                        $newIndex = implode('', $keyTemps);
                        $newIndexs[$key] = $newIndex;
                    }
                    $cnt++;
                }
                $newData = array();
                foreach ($dataInput as $key=>$value) {
                    $newData[$newIndexs[$key]] = is_null($value) ? '' : strval($value);
                }
                array_push($dataOutputs, $newData);
            }
            return $dataOutputs;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $dataInput
     * @return array
     * @throws Exception
     */
    public function convertDbIndex ($dataInput) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $dataOutput = array();
            foreach ($dataInput as $key=>$value) {
                $keyTemps = explode('_', $key);
                foreach ($keyTemps as $j=>$keyTemp) {
                    if ($j > 0) {
                        $keyTemps[$j] = ucfirst($keyTemp);
                    }
                }
                $newIndex = implode('', $keyTemps);
                $dataOutput[$newIndex] = is_null($value) ? '' : strval($value);
            }

            return $dataOutput;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $dataInput
     * @return array
     * @throws Exception
     */
    public function convertStrObject ($dataInput) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $dataOutput = array();
			if (empty($dataInput)) return $dataInput;
            foreach ($dataInput as $key=>$value) {
                $dataOutput[$key] = is_null($value) ? '' : strval($value);
            }

            return $dataOutput;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $dataInputs
     * @return array
     * @throws Exception
     */
    public function convertStrObjects ($dataInputs) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $dataOutputs = array();
            foreach ($dataInputs as $index=>$dataInput) {
                $newData = array();
                foreach ($dataInput as $key=>$value) {
                    $newData[$key] = is_null($value) ? '' : strval($value);
                }
                $dataOutputs[$index] = $newData;
            }
            return $dataOutputs;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $dataInputs
     * @param $indexs
     * @return array
     * @throws Exception
     */
    public function convertToMysqlArr ($dataInputs, $indexs) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            if (empty($dataInputs)) {
                throw new Exception('[' . __LINE__ . '] - Array dataInputs empty');
            }

            $dataOutputs = array();
            foreach ($indexs as $index) {
                if (!array_key_exists($index, $dataInputs)) {
                    throw new Exception('[' . __LINE__ . '] - '.$index.' not exist');
                }

                $newIndexs = '';
                for ($i = 0; $i < strlen($index); $i++){
                    if (ctype_digit($index[$i])) {
                        $newIndexs .= '_' . $index[$i];
                    } else if (ctype_upper($index[$i])) {
                        $newIndexs .= '_' . strtolower($index[$i]);
                    } else {
                        $newIndexs .= $index[$i];
                    }
                }

                $dataOutputs[$newIndexs] = $dataInputs[$index];
            }
            return $dataOutputs;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $dataInputs
     * @return array
     * @throws Exception
     */
    public function convertToMysqlArrAll ($dataInputs) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);

            $dataOutputs = array();
            if (!empty($dataInputs)) {
                foreach ($dataInputs as $index => $dataInput) {
                    $newIndexs = '';
                    for ($i = 0; $i < strlen($index); $i++) {
                        if (ctype_digit($index[$i])) {
                            $newIndexs .= '_' . $index[$i];
                        } else if (ctype_upper($index[$i])) {
                            $newIndexs .= '_' . strtolower($index[$i]);
                        } else {
                            $newIndexs .= $index[$i];
                        }
                    }
                    $dataOutputs[$newIndexs] = $dataInput;
                }
            }
            return $dataOutputs;
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param $params
     * @return void
     * @throws Exception
     */
    public function checkEmptyParams ($params) {
        try {
            //$this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            foreach ($params as $key=>$param) {
                if (isset($param)) {
                    if ($param === '') {
                        throw new Exception('[' . __LINE__ . '] - Parameter '.$key.' empty');
                    } else if (is_array($param) && empty($param)) {
                        throw new Exception('[' . __LINE__ . '] - Array '.$key.' empty');
                    }
                } else {
                    throw new Exception('[' . __LINE__ . '] - Parameter '.$key.' not available');
                }
            }
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }

    /**
     * @param array $indexs
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function checkEmptyParamsArray ($params , $indexs) {
        try {
            $this->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            foreach ($indexs as $index) {
                if (!array_key_exists($index, $params)) {
                    throw new Exception('[' . __LINE__ . '] - '.$index.' not exist');
                }
                if ($params[$index] === '') {
                    throw new Exception('[' . __LINE__ . '] - Parameter '.$index.' empty');
                }
            }
        } catch(Exception $ex) {
            $this->log_error(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage());
            throw new Exception($this->get_exception('0051', __FUNCTION__, __LINE__, $ex->getMessage()), $ex->getCode());
        }
    }
}