<?php
require_once 'src/BeforeValidException.php';
require_once 'src/ExpiredException.php';
require_once 'src/SignatureInvalidException.php';
require_once 'src/JWT.php';
require_once 'trait/SiteFilterTrait.php';

use \Firebase\JWT\JWT;

class General {
    use SiteFilterTrait;

    public $userId = 0;
    public $isLogged = false;
    public $userSite = 0;
    public $auditRemark;
    public $errMsg;
    public $pdfFontSize = 10;
    public $pdfPageWidth = 180;
    public $pdfLineSize = 0.1;
    public $pdfLineBoldSize = 0.6;

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public function logDebug ($class, $function, $line, $msg): void {
        if ($this->isLogged) {
            $debugMsg = date("Y/m/d h:i:sa")." (".$this->userId.") [".$class.":".$function.":".$line."] - ".$msg."\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug.'debug_'.date("Ymd").'.log');
        }
    }

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public function logError ($class, $function, $line, $msg): void {
        if ($this->isLogged) {
            $debugMsg = date("Y/m/d h:i:sa") . " (" . $this->userId . ") [" . $class . ":" . $function . ":" . $line . "] - (ERROR) " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug . 'debug_' . date("Ymd") . '.log');
            $debugMsg = date("Y/m/d h:i:sa") . " (" . $this->userId . ") [" . $class . ":" . $function . ":" . $line . "] - " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderError . 'error_' . date("Ymd") . '.log');
        }
    }

    /**
     * @param string $requestUri
     * @param string $apiName
     * @return array
     * @throws Exception
     */
    public function getUrlArr (string $requestUri, string $apiName): array {
        try {
            $this->checkEmptyParams(array($requestUri, $apiName));
            $requestUri = str_replace('?%22%22', '', $requestUri);
            $urlArr = explode('/', $requestUri);
            $isApiName = false;
            foreach ($urlArr as $param) {
                if ($param === $apiName) {
                    $isApiName = true;
                    break;
                }
                array_shift($urlArr);
            }
            if (empty($urlArr) || !$isApiName) {
                throw new Exception('Wrong Request');
            }
            return $urlArr;
        } catch(Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $string
     * @param string $stringName
     * @return bool
     * @throws Exception
     */
    public function checkEmptyString ($string, string $stringName=''): bool {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($string === '' || $string === null) {
                throw new Exception('Empty string '.$stringName);
            }
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $integer
     * @param string $integerName
     * @return bool
     * @throws Exception
     */
    public function checkEmptyInteger ($integer, string $integerName): bool {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($integer)) {
                throw new Exception('Empty integer '.$integerName);
            }
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $float
     * @param string $floatName
     * @return bool
     * @throws Exception
     */
    public function checkEmptyFloat ($float, string $floatName): bool {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($float)) {
                throw new Exception('Empty float '.$floatName);
            }
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $dataInputs
     * @param string $arrayName
     * @return bool
     * @throws Exception
     */
    public function checkEmptyArray (array $dataInputs, string $arrayName): bool {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($dataInputs)) {
                throw new Exception('Empty array '.$arrayName);
            }
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function checkEmptyParams (array $params): bool {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
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
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $inputArr
     * @param array $indexArr
     * @param bool $isAlert
     * @return void
     * @throws Exception
     */
    public function checkMandatoryArray (array $inputArr, array $indexArr, bool $isAlert=false): void {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyArray($inputArr, 'inputArr');
            $this->checkEmptyArray($indexArr, 'indexArr');
            $throwCode = $isAlert ? 31 : 30;
            foreach ($indexArr as $index) {
                if (!array_key_exists($index, $inputArr)) {
                    throw new Exception('Index '.$index.' not exist');
                }
                $param = $inputArr[$index];
                if (is_null($param) || $param === '') {
                    throw new Exception('[' . __LINE__ . '] - Parameter '.$index.' empty', $throwCode);
                } else if (gettype($param) === 'integer' && $param === 0) {
                    throw new Exception('[' . __LINE__ . '] - Integer '.$index.' 0', $throwCode);
                }
            }
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $input
     * @param array $optionArr
     * @param string $inputName
     * @param bool $isAlert
     * @return void
     * @throws Exception
     */
    public function checkMandatoryOption ($input, array $optionArr, string $inputName, bool $isAlert=false): void {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyArray($optionArr, 'optionArr');
            $this->checkEmptyString($inputName);
            $throwCode = $isAlert ? 31 : 30;
            $check = false;
            foreach ($optionArr as $option) {
                if ($input === $option) {
                    $check = true;
                    break;
                }
            }
            if (!$check) {
                throw new Exception('[' . __LINE__ . '] - '.$inputName.' not in the options', $throwCode);
            }
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $param
     * @param string $replaced
     * @return string
     * @throws Exception
     */
    public function clearNull ($param, string $replaced=''): string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (is_null($param)) {
                return $replaced;
            }
            return $param;
        } catch(Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $auditActionId
     * @param string $remark
     * @return void
     * @throws Exception
     */
    public function saveAudit (int $auditActionId, string $remark): void {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyInteger($auditActionId, 'auditActionId');
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
            DbMysql::insert('sys_audit', array('auditActionId'=>$auditActionId, 'userId'=>$this->userId, 'auditIp'=>$ipaddress, 'auditRemark'=>$remark));
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function generateRandomString (int $length = 20): string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $filename
     * @return string
     * @throws Exception
     */
    public function getStringFromFile (string $filename): string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyString($filename, 'filename');
            $handle = fopen($filename.'.txt', "rb");
            if (FALSE === $handle) {
                throw new Exception('Fail to open '.$filename);
            }
            $contents = fread($handle, filesize($filename));
            fclose($handle);
            return $contents;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $headers
     * @return void
     * @throws Exception
     */
    public function checkJwt (array $headers) {
        try {
            $this->checkEmptyArray($headers, 'headers');
            if (isset($headers['Authorization'])) {
                $jwt = $headers['Authorization'];
            } else if (isset($headers['authorization'])) {  //  && isset($headers['deviceid'])
                $jwt = $headers['authorization'];
            } else {
                throw new Exception('Parameter Authorization empty');
            }
            $key = "gems2";
            JWT::$leeway = 86400; // $leeway in seconds
            $token = substr($jwt, 7);
            $data = JWT::decode($token, $key, array('HS256'));
            $this->userId = intval($data->userId);
            DbMysql::$userId = $this->userId;
            //if (DbMysql::count('sys_user', array('userId'=>$this->userId, 'userToken'=>$token)) !== 1) {
            //    throw new Exception('Expired token', 31);
            //}
            $user = DbMysql::select('sys_user', array('userId'=>$this->userId));
            if (empty($user)) {
                throw new Exception('User not exist!', 31);
            } else if ($user['userStatus'] !== 1) {
                throw new Exception('Your account is deactivated. Please contact Administrator!', 31);
            //} else if (isset($headers['authorization']) && $user['userDeviceId'] !== $headers['deviceid']) {
                //throw new Exception('Your Device is invalid!', 31);
            }
            $this->userSite = $user['siteId'];
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Check if current user is Administrator or GFM Management
     * @return bool
     * @throws Exception
     */
    public function isAdministrator(): bool {
        try {
            if (!$this->userId) {
                return false;
            }
            $roles = array();
            // Prefer legacy Class_db if available; otherwise use DbMysql
            if (class_exists('Class_db')) {
                $roles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$this->userId), 'role_id');
            } else {
                // Fallback using DbMysql (keys will be camelCased)
                $rows = DbMysql::selectAll('sys_user_role', array('user_id'=>$this->userId));
                foreach ($rows as $row) {
                    if (isset($row['roleId'])) { $roles[] = $row['roleId']; }
                    else if (isset($row['role_id'])) { $roles[] = $row['role_id']; }
                }
            }
            foreach ($roles as $roleId) {
                if (in_array(intval($roleId), [1, 10], true)) { // Administrator or GFM Management
                    return true;
                }
            }
            return false;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Add site filtering to SQL queries for non-administrators
     * @param string $query
     * @param string $siteField
     * @return string
     * @throws Exception
     */
    public function addSiteFilter(string $query, string $siteField = 'site_id'): string {
        try {
            if (!$this->isAdministrator() && $this->userSite) {
                if (stripos($query, 'WHERE') !== false) {
                    $query .= " AND {$siteField} = {$this->userSite}";
                } else {
                    $query .= " WHERE {$siteField} = {$this->userSite}";
                }
            }
            return $query;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $versionId
     * @return void
     * @throws Exception
     */
    public function updateVersion (int $versionId): void {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyInteger($versionId, 'versionId');
            DbMysql::update('sys_version', array('versionNo'=>'++'), array('versionId'=>$versionId));
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $inputArr
     * @param array $indexArr
     * @return array
     * @throws Exception
     */
    public function arraySpliceAssoc (array $inputArr, array $indexArr): array {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyArray($inputArr, 'inputArr');
            $this->checkEmptyArray($indexArr, 'indexArr');
            $outputArr = array();
            foreach ($indexArr as $index) {
                if (!array_key_exists($index, $inputArr)) {
                    throw new Exception('Index '.$index.' not exist');
                }
                $outputArr[$index] = $inputArr[$index];
            }
            return $outputArr;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $arrayList
     * @param array $indexArr
     * @return array
     * @throws Exception
     */
    public function arraySpliceAssocMultiple (array $arrayList, array $indexArr): array {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyArray($arrayList, 'arrayList');
            $this->checkEmptyArray($indexArr, 'indexArr');
            $outputList = array();
            foreach ($arrayList as $n => $row) {
                $outputArr = array();
                foreach ($indexArr as $index) {
                    if ($n === 0 && !array_key_exists($index, $row)) {
                        throw new Exception('Index '.$index.' not exist');
                    }
                    $outputArr[$index] = $row[$index];
                }
                $outputList[] = $outputArr;
            }
            return $outputList;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $folder
     * @return bool
     */
    public function folderExist (string $folder): bool {
        $path = realpath($folder);
        return ($path !== false AND is_dir($path));
    }

    /**
     * @param array $fileArr
     * @param int $documentId
     * @return array
     * @throws Exception
     */
    public function uploadPrepare (array $fileArr, int $documentId): array {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyArray($fileArr, 'fileArr');
            $this->checkEmptyInteger($this->userId, 'userId');
            $this->checkEmptyInteger($documentId, 'documentId');
            $this->checkMandatoryArray($fileArr, array('name', 'filename', 'type', 'size', 'data'));

            $curDates = new DateTime();
            $uploadUplname = $fileArr['filename'];
            $pos = strrpos($uploadUplname,'.');

            $uploadArr['documentId'] = $documentId;
            $uploadArr['uploadName'] = $fileArr['name'];
            $uploadArr['uploadUplname'] = $uploadUplname;
            $uploadArr['uploadFilename'] = $curDates->format("ymdHis").'_'.$documentId.'_'.$this->userId;
            $uploadArr['uploadExtension'] = $pos !== false ? substr($uploadUplname, $pos+1) : ' - ';
            $uploadArr['uploadFolder'] = 'upload/temp';
            $uploadArr['uploadFilesize'] = $fileArr['size'];
            $uploadArr['uploadFileWidth'] = $fileArr['width'];
            $uploadArr['uploadFileHeight'] = $fileArr['height'];
            $uploadArr['uploadBlobType'] = $fileArr['type'];
            $uploadArr['uploadCreatedBy'] = $this->userId;

            if (!$this->folderExist($uploadArr['uploadFolder'])) {
                mkdir ($uploadArr['uploadFolder'],0777, true);
            }
            file_put_contents($uploadArr['uploadFolder'].'/'.$uploadArr['uploadFilename'].'.'.$uploadArr['uploadExtension'], base64_decode($fileArr['data']));
            return $uploadArr;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $uploadArr
     * @param string $folder
     * @param string $filename
     * @return int
     * @throws Exception
     */
    public function uploadSave (array $uploadArr, string $folder, string $filename): int {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyArray($uploadArr, 'uploadArr');
            $folderNew = 'upload/'.$folder;
            if (!$this->folderExist($folderNew)) {
                mkdir ($folderNew,0777, true);
            }
            $currentFile = $uploadArr['uploadFolder'].'/'.$uploadArr['uploadFilename'].'.'.$uploadArr['uploadExtension'];
            $newFile = $folderNew.'/'.$filename.'.'.$uploadArr['uploadExtension'];
            rename($currentFile, $newFile);
            //unlink($currentFile);
            $uploadArr['uploadFolder'] = $folderNew;
            $uploadArr['uploadFilename'] = $filename;
            return DbMysql::insert('sys_upload', $uploadArr);
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $uploadId
     * @return array
     * @throws Exception
     */
    public function getUpload (int $uploadId): array {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyInteger($uploadId, 'uploadId');
            $upload = DbMysql::select('sys_upload', array('uploadId'=>$uploadId), true);
            $file = $upload['uploadFolder'].'/'.$upload['uploadFilename'].'.'.$upload['uploadExtension'];
            $upload['src'] = Constant::$url.$file.'?t='.time();
            $upload['fileExist'] = file_exists($file);
            return $upload;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $pdfId
     * @return string
     * @throws Exception
     */
    public function getPdfLink (int $pdfId): string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyInteger($pdfId, 'pdfId');
            $upload = DbMysql::select('sys_pdf', array('pdfId'=>$pdfId), true);
            $file = $upload['pdfFolder'].'/'.$upload['pdfFilename'];
            if (!file_exists($file)) {
                throw new Exception('PDF File '.$file.' not exist');
            }
            return Constant::$url.$file.'?t='.time();
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $uploadId
     * @return string
     * @throws Exception
     */
    public function getUploadLink (int $uploadId): string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyInteger($uploadId, 'uploadId');
            $upload = DbMysql::select('sys_upload', array('uploadId'=>$uploadId), true);
            $file = $upload['uploadFolder'].'/'.$upload['uploadFilename'].'.'.$upload['uploadExtension'];
            if (!file_exists($file)) {
                $file = 'upload/upload_placeholder.png';
            }
            return Constant::$url.$file.'?t='.time();
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $time
     * @param bool $withSeconds
     * @return string|null
     * @throws Exception
     */
    public function timeDisplay ($time, bool $withSeconds = false): ?string {
        try {
            //$this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $durationStr = '';
            $pieces = explode(':', $time);
            if (count($pieces) === 3) {
                $hour = abs(intval($pieces[0]));
                $minute = intval($pieces[1]);
                $seconds = intval($pieces[2]);
                if ($hour >= 24) {
                    $durationInt = intval(floor($hour/24));
                    $durationStr = $durationInt === 1 ? $durationInt.' day' : $durationInt.' days';
                }
                if ($hour > 0) {
                    $durationStr .= $hour === 1 ? ' '.$hour.' hour' : ' '.$hour.' hours';
                }
                if ($minute === 0 && $seconds === 0){
                    return ltrim($durationStr);
                }
                $durationStr .= $minute <= 1 ? ' '.$minute.' minute' : ' '.$minute.' minutes';
                if ($withSeconds && $seconds !== 0) {
                    $durationStr .= ' '.$seconds.' seconds';
                }
            } else {
                return $time;
            }
            return ltrim($durationStr);
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param $timestamp
     * @param bool $withSecond
     * @return string|null
     * @throws Exception
     */
    public function timeDisplayPretty ($timestamp, bool $withSecond=false): ?string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($timestamp)) {
                return null;
            }
            $dateTime = new DateTime($timestamp);
            if ($withSecond) {
                return $dateTime->format('j/n/Y g:i:s A');
            }
            return $dateTime->format('j/n/Y g:i A');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $datetime
     * @return string
     * @throws Exception
     */
    public function dateDisplay (string $datetime): string {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($datetime)) {
                return '';
            }
            $dateResult = '';
            if (strlen($datetime) === 10 || strlen($datetime) === 19) {
                $dateStr = substr($datetime, 0, 10);
                $pieces = explode('-', $dateStr);
                if (count($pieces) === 3) {
                    $dateResult = $pieces[2].'/'.$pieces[1].'/'.$pieces[0];
                }
            }
            return $dateResult;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param object $pdf
     * @return void
     */
    public function pdfCheckYTitle(object $pdf): void {
        if ($pdf->GetY() > 272) {
            $pdf->AddPage();
            $pdf->setPage($pdf->getPage());
        }
    }

    /**
     * @param object $pdf
     * @param int $startY
     * @param int $maxNoCells
     * @return void
     */
    private function pdfCheckYRow (object $pdf, int $startY, int $maxNoCells): void {
        $tempY = $startY+($maxNoCells*4)+2;
        if ($tempY > 273) {
            $previousY = $tempY - 277;
            $pdf->AddPage();
            $pdf->setPage($pdf->getPage());
            if ($previousY > 0) {
                $pdf->SetY($pdf->GetY() + $previousY);
            }
        }
    }

    /**
     * @param object $pdf
     * @param string $value
     * @return void
     */
    public function pdfWriteOneColumn (object $pdf, string $value): void {
        $maxNoCells = 0;
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $cellCount = $pdf->MultiCell(180,4,$value,0,'L',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $pdf->SetXY($startX,$startY);
        $pdf->MultiCell(180, ($maxNoCells*4)+2, '', 1, 'L', 0, 0);
        $pdf->Ln();
        $this->pdfCheckYRow($pdf, $startY, $maxNoCells);
    }

    /**
     * @param object $pdf
     * @param string $label
     * @param string $value
     * @return void
     */
    public function pdfWriteTwoColumn (object $pdf, string $label, string $value): void {
        $maxNoCells = 0;
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $cellCount = $pdf->MultiCell(32,4,$label,0,'R',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(148,4,$value,0,'L',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $pdf->SetXY($startX,$startY);
        $pdf->MultiCell(32, ($maxNoCells*4)+2, '', 1, 'R', 0, 0);
        $pdf->MultiCell(148, ($maxNoCells*4)+2, '', 1, 'L', 0, 0);
        $pdf->Ln();
        $this->pdfCheckYRow($pdf, $startY, $maxNoCells);
    }

    /**
     * @param object $pdf
     * @param string $label1
     * @param string $value1
     * @param string $label2
     * @param string $value2
     * @return void
     */
    public function pdfWriteFourColumn (object $pdf, string $label1, string $value1, string $label2, string $value2): void {
        $maxNoCells = 0;
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $cellCount = $pdf->MultiCell(32,4,$label1,0,'R',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(58,4,$value1,0,'L',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(32,4,$label2,0,'R',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $cellCount = $pdf->MultiCell(58,4,$value2,0,'L',0,0);
        if ($cellCount > $maxNoCells ) {$maxNoCells = $cellCount;}
        $pdf->SetXY($startX,$startY);
        $pdf->MultiCell(32, ($maxNoCells*4)+2, '', 1, 'R', 0, 0);
        $pdf->MultiCell(58, ($maxNoCells*4)+2, '', 1, 'L', 0, 0);
        $pdf->MultiCell(32, ($maxNoCells*4)+2, '', 1, 'R', 0, 0);
        $pdf->MultiCell(58, ($maxNoCells*4)+2, '', 1, 'L', 0, 0);
        $pdf->Ln();
        $this->pdfCheckYRow($pdf, $startY, $maxNoCells);
    }

    /**
     * @param array $fontSize
     * @return int
     * @throws Exception
     */
    private function pdfGetFontSizeMax (array $fontSize= array()): int {
        try {
            if (array_key_exists(0, $fontSize)) {
                $fontSizeMax = 5;
                foreach ($fontSize as $size) {
                    if (is_int($size) && $size > $fontSizeMax) {
                        $fontSizeMax = $size;
                    }
                }
            } else {
                $fontSizeMax = $this->pdfFontSize;
            }
            return $fontSizeMax;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param object $pdf
     * @param array $labels
     * @param array $columnWidths
     * @param array $aligns
     * @param array $styles
     * @param string $boldBorders
     * @param float $startXAxis
     * @param float $defaultHeight
     * @param string $vAlign
     * @return void
     */
    public function pdfWriteColumn (object $pdf, array $labels, array $columnWidths, array $aligns, array $styles, string $boldBorders = '', float $startXAxis = 0.0, float $defaultHeight = 0.0, string $vAlign = 'M'): void {
        $totalCells = count($labels);
        $maxNoCells = 0;
        $pdf->SetLineWidth($this->pdfLineSize);
        $startX = $startXAxis > 0 ? $pdf->GetX() + $startXAxis : $pdf->GetX();
        $startY = $pdf->GetY();
        $pdf->SetXY($startX, $startY);
        $pdf->MultiCell(10, '', '', 0, '', 0, 1);
        $oneLineHeight = $pdf->GetY() - $startY;
        $pdf->SetXY($startX, $startY);
        for ($i = 0; $i < $totalCells; $i++) {
            $pdf->SetFont('helvetica', $styles[$i]);
            if ($defaultHeight > 0) {
                $cellCount = $pdf->MultiCell($columnWidths[$i], $defaultHeight, $labels[$i], 0, $aligns[$i], 0, 0,'', '', true, 0, false, true, $defaultHeight, $vAlign);
            } else {
                $cellCount = $pdf->MultiCell($columnWidths[$i], '', $labels[$i], 0, $aligns[$i], 0, 0);
            }
            if ($cellCount > $maxNoCells ) {
                $maxNoCells = $cellCount;
            }
        }
        $cellHeight = $maxNoCells * ($oneLineHeight - 1) + 1;
        if ($defaultHeight > $cellHeight) {
            $cellHeight = $defaultHeight;
        }
        $pdf->SetXY($startX,$startY);
        for ($i = 0; $i < $totalCells; $i++) {
            $pdf->MultiCell($columnWidths[$i], $cellHeight, '', 1, '', 0, 0);
        }
        if ($boldBorders !== '') {
            $pdf->SetXY($startX, $startY);
            $pdf->SetLineWidth($this->pdfLineBoldSize);
            $pdf->MultiCell(array_sum($columnWidths), $cellHeight, '', $boldBorders, '', 0, 0);
            $pdf->SetLineWidth($this->pdfLineSize);
        }
        $pdf->Ln();
        $this->pdfCheckYRow($pdf, $startY, $maxNoCells);
    }

    /**
     * @param object $pdf
     * @param array $labels
     * @param array $columnWidths
     * @param array $aligns
     * @param array $borders
     * @param array $styles
     * @param array $fontSize
     * @param string $boldBorders
     * @param float $startXAxis
     * @param float $defaultHeight
     * @param string $vAlign
     * @return void
     * @throws Exception
     */
    public function pdfWriteColumnV2 (object $pdf, array $labels, array $columnWidths, array $aligns, array $borders = array(), array $styles = array(), array $fontSize = array(), string $boldBorders = '', float $startXAxis = 0.0, float $defaultHeight = 0, string $vAlign = 'M'): void {
        try {
            $totalCells = count($labels);
            $maxNoCells = 0;
            $pdf->SetLineWidth($this->pdfLineSize);
            $startX = $startXAxis > 0 ? $pdf->GetX() + $startXAxis : $pdf->GetX();
            $startY = $pdf->GetY();
            $pdf->SetXY($startX, $startY);
            $pdf->SetFontSize($this->pdfGetFontSizeMax($fontSize));
            $pdf->MultiCell(10, '', '', 0, '', 0, 1);
            $oneLineHeight = $pdf->GetY() - $startY;
            $pdf->SetXY($startX, $startY);
            if ($vAlign !== 'T' && count($labels) > 1) {
                $pdf->SetTextColor(255, 255, 255);
                for ($i = 0; $i < $totalCells; $i++) {
                    $pdf->SetFont('helvetica', array_key_exists($i, $styles) ? $styles[$i] : '', array_key_exists($i, $fontSize) && $fontSize[$i] !== '' ? $fontSize[$i] : $this->pdfFontSize);
                    $cellCount = $pdf->MultiCell($columnWidths[$i], '', $labels[$i], 0, $aligns[$i], 0, 0);
                    if ($cellCount > $maxNoCells ) {
                        $maxNoCells = $cellCount;
                    }
                }
                $tempHeight = $maxNoCells * ($oneLineHeight - $this->pdfLineSize) + 0.5;
                if ($tempHeight > $defaultHeight) {
                    $defaultHeight = $tempHeight;
                }
            }
            $pdf->SetXY($startX, $startY);
            $pdf->SetTextColor(0, 0, 0);
            for ($i = 0; $i < $totalCells; $i++) {
                $pdf->SetFont('helvetica', array_key_exists($i, $styles) ? $styles[$i] : '', array_key_exists($i, $fontSize) && $fontSize[$i] !== '' ? $fontSize[$i] : $this->pdfFontSize);
                if ($defaultHeight > 0) {
                    $cellCount = $pdf->MultiCell($columnWidths[$i], $defaultHeight, $labels[$i], 0, $aligns[$i], 0, 0,'', '', true, 0, false, true, $defaultHeight, $vAlign);
                } else {
                    $cellCount = $pdf->MultiCell($columnWidths[$i], '', $labels[$i], 0, $aligns[$i], 0, 0);
                }
                if ($cellCount > $maxNoCells ) {
                    $maxNoCells = $cellCount;
                }
            }
            $cellHeight = ($maxNoCells * ($oneLineHeight - $this->pdfLineSize)) + 0.5;
            if ($defaultHeight > $cellHeight) {
                $cellHeight = $defaultHeight;
            }
            $pdf->SetXY($startX,$startY);
            for ($i = 0; $i < $totalCells; $i++) {
                $pdf->MultiCell($columnWidths[$i], $cellHeight, '', array_key_exists($i, $borders) ? $borders[$i] : 1, '', 0, 0);
            }
            if ($boldBorders !== '') {
                $pdf->SetXY($startX, $startY);
                $pdf->SetLineWidth($this->pdfLineBoldSize);
                $pdf->MultiCell(array_sum($columnWidths), $cellHeight, '', $boldBorders, '', 0, 0);
                $pdf->SetLineWidth($this->pdfLineSize);
            }
            $pdf->Ln();
            $this->pdfCheckYRow($pdf, $startY, $maxNoCells);
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}
