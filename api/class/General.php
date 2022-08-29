<?php
require_once 'src/BeforeValidException.php';
require_once 'src/ExpiredException.php';
require_once 'src/SignatureInvalidException.php';
require_once 'src/JWT.php';

use \Firebase\JWT\JWT;

class General {

    public $userId = 0;
    public $isLogged = false;

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public function logDebug ($class, $function, $line, $msg) {
        if ($this->isLogged) {
            $debugMsg = date("Y/m/d h:i:sa")." (".$this->userId.") [".$class.":".$function.":".$line."] - ".$msg."\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug.'debug\debug_'.date("Ymd").'.log');
        }
    }

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public function logError ($class, $function, $line, $msg) {
        if ($this->isLogged) {
            $debugMsg = date("Y/m/d h:i:sa") . " (" . $this->userId . ") [" . $class . ":" . $function . ":" . $line . "] - (ERROR) " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug . 'debug\debug_' . date("Ymd") . '.log');
            $debugMsg = date("Y/m/d h:i:sa") . " (" . $this->userId . ") [" . $class . ":" . $function . ":" . $line . "] - " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug . 'error\error_' . date("Ymd") . '.log');
        }
    }

    /**
     * @param  $string
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
     * @param $float $float
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
            } else if (isset($headers['authorization']) && isset($headers['deviceid'])) {
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
            if (DbMysql::count('sys_user', array('userId'=>$this->userId, 'userStatus'=>1)) !== 1) {
                throw new Exception('User not exist', 31);
            }
            if (isset($headers['authorization']) && DbMysql::count('sys_user', array('userId'=>$this->userId, 'userDeviceId'=>$headers['deviceid'])) !== 1) {
                throw new Exception('Device ID invalid with this login');
            }
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
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
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $this->checkEmptyParams(array($requestUri, $apiName));
            $urlArr = explode('/', $_SERVER['REQUEST_URI']);
            foreach ($urlArr as $param) {
                if ($param === $apiName) {
                    break;
                }
                array_shift($urlArr);
            }
            return $urlArr;
        } catch(Exception|Throwable $ex) {
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
     * @return void
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
     * @return void
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
                $hour = intval($pieces[0]);
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
}
