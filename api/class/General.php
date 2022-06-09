<?php
require_once 'src/BeforeValidException.php';
require_once 'src/ExpiredException.php';
require_once 'src/SignatureInvalidException.php';
require_once 'src/JWT.php';

use \Firebase\JWT\JWT;

class General {

    public $userId;
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
     * @param string $string
     * @return bool
     * @throws Exception
     */
    public function checkEmptyString (string $string=''): bool {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($string === '' || $string === NULL) {
                throw new Exception('Empty $string');
            }
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param int $integer
     * @return bool
     * @throws Exception
     */
    public function checkEmptyInteger (int $integer=0): bool {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if ($integer === 0) {
                throw new Exception('Empty $integer');
            }
            return true;
        } catch (Exception | Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $dataInputs
     * @return bool
     * @throws Exception
     */
    public function checkEmptyArray (array $dataInputs): bool {
        try {
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            if (empty($dataInputs)) {
                throw new Exception('Empty $dataInputs');
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
            $this->logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
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
            $this->checkEmptyInteger($auditActionId);
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
            $this->checkEmptyString($filename);
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
            $this->checkEmptyArray($headers);
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
            $this->userId = $data->userId;
            DbMysql::$userId = $this->userId;
            if (DbMysql::count('sys_user', array('userId'=>$this->userId, 'userToken'=>$token)) !== 1) {
                throw new Exception('Token not valid');
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
            $this->checkEmptyInteger($versionId);
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
            $this->checkEmptyArray($inputArr);
            $this->checkEmptyArray($indexArr);
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
}
