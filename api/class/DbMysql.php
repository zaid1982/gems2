<?php

class DbMysql {

    public static $DBH;
    public static $userId = 0;
    public static $isLogged = false;

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public static function logDebug ($class, $function, $line, $msg) {
        if (self::$isLogged) {
            $debugMsg = date("Y/m/d h:i:sa") . " (" . self::$userId . ") [" . $class . ":" . $function . ":" . $line . "] - " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug . 'debug\debug_' . date("Ymd") . '.log');
        }
    }

    /**
     * @param $class
     * @param $function
     * @param $line
     * @param $msg
     */
    public static function logError ($class, $function, $line, $msg) {
        if (self::$isLogged) {
            $debugMsg = date("Y/m/d h:i:sa") . " (" . self::$userId . ") [" . $class . ":" . $function . ":" . $line . "] - (ERROR) " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug . 'debug\debug_' . date("Ymd") . '.log');
            $debugMsg = date("Y/m/d h:i:sa") . " (" . self::$userId . ") [" . $class . ":" . $function . ":" . $line . "] - " . $msg . "\r\n";
            error_log($debugMsg, 3, Constant::$folderDebug . 'error\error_' . date("Ymd") . '.log');
        }
    }

    /**
     * @throws Exception
     */
    public static function connect () {
        try {
            //self::$DBH = new PDO("mysql:host=".Constant::$dbHost.";dbname=".Constant::$dbName.";charset=utf8", Constant::$dbUserName, Constant::$dbUserPassword);
            self::$DBH = new PDO("mysql:host=".Constant::$dbHost.";port=3307;dbname=".Constant::$dbName.";charset=utf8", Constant::$dbUserName, Constant::$dbUserPassword);
            //self::$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$DBH->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$DBH->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        }
        catch(PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public static function close () {
        self::$DBH = null;
    }

    /**
     * @throws Exception
     */
    public static function beginTransaction () {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            self::$DBH->beginTransaction();
        }
        catch(PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public static function commit () {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            self::$DBH->commit();
        }
        catch(PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public static function rollback () {
        try {
            if (!empty(self::$DBH)) {
                self::$DBH->rollBack();
            }
        }
        catch(PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $dataInput
     * @return array
     * @throws Exception
     */
    private static function convertFromDbIndex (array $dataInput): array {
        try {
            $dataOutput = array();
            foreach ($dataInput as $key=>$value) {
                $keyTemps = explode('_', $key);
                foreach ($keyTemps as $j=>$keyTemp) {
                    if ($j > 0) {
                        $keyTemps[$j] = ucfirst($keyTemp);
                    }
                }
                $newIndex = implode('', $keyTemps);
                $dataOutput[$newIndex] = $value;
            }
            return $dataOutput;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $dataInputs
     * @return array
     * @throws Exception
     */
    private static function convertFromDbIndexes (array $dataInputs): array {
        try {
            $dataOutputs = array();
            $newIndexArray = array();
            $cnt = 0;
            foreach ($dataInputs as $dataKey=>$dataInput) {
                if ($cnt === 0) {
                    foreach ($dataInput as $key=>$value) {
                        $keyTemps = explode('_', $key);
                        foreach ($keyTemps as $j=>$keyTemp) {
                            if ($j > 0) {
                                $keyTemps[$j] = ucfirst($keyTemp);
                            }
                        }
                        $newIndex = implode('', $keyTemps);
                        $newIndexArray[$key] = $newIndex;
                    }
                    $cnt++;
                }
                $newData = array();
                foreach ($dataInput as $key=>$value) {
                    $newData[$newIndexArray[$key]] = $value;
                }
                $dataOutputs[$dataKey] = $newData;
            }
            return $dataOutputs;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $index
     * @return string
     * @throws Exception
     */
    private static function convertToDbString (string $index): string {
        try {
            $newIndex = '';
            for ($i = 0; $i < strlen($index); $i++) {
                if (ctype_digit($index[$i])) {
                    $newIndex .= '_' . $index[$i];
                } else if (ctype_upper($index[$i])) {
                    $newIndex .= '_' . strtolower($index[$i]);
                } else {
                    $newIndex .= $index[$i];
                }
            }
            return $newIndex;
        } catch(Exception $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return string
     * @throws Exception
     */
    private static function getWhereString (array $columns): string {
        try {
            $whereString = '';
            foreach ($columns as $columnIndex => $columnValue) {
                if ($columnValue === '' || $columnValue === '%%') {
                    continue;
                }
                $index = self::convertToDbString($columnIndex);
                $values = explode('|', $columnValue);
                if (count($values) > 1) {
                    switch ($values[0]) {
                        case 's1':
                        case 's2':
                        case 's3':
                            $whereString .= $values[1];
                            break;
                        case '%':
                            $whereString .= "$index LIKE ?";
                            break;
                        case '>':
                        case '<':
                        case '>=':
                        case '<=':
                        case '<>':
                            $whereString .= "$index $values[0] ?";
                            break;
                        case 'IN':
                        case 'NOT IN':
                            $inValues = explode(',', $values[1]);
                            if (count($inValues) > 0) {
                                $inStrings = array();
                                $x = 0;
                                do {
                                    $inStrings[] = '?';
                                    $x++;
                                } while ($x < count($inValues));
                                $inString = implode(" , ", $inStrings);
                                $whereString .= "$index $values[0] ($inString)";
                            }
                            break;
                        default:
                            $whereString .= "$index = ?";
                    }
                } else if ($columnValue === 'IS NULL' || $columnValue === 'IS NOT NULL') {
                    $whereString .= "$index $columnValue";
                } else {
                    $whereString .= "$index = ?";
                }
                $whereString .= ' AND ';
            }
            return !empty($whereString) ? ' WHERE '.rtrim($whereString, 'AND ') : '';
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     */
    private static function getPreparedWhere (array $columns): array {
        try {
            $preparedValues = array();
            foreach ($columns as $columnValue) {
                if ($columnValue === '' || $columnValue === '%%') {
                    continue;
                }
                $values = explode('|', $columnValue);
                if (count($values) > 1) {
                    switch ($values[0]) {
                        case 's1':
                        case 's2':
                        case 's3':
                            break;
                        case '%':
                            $preparedValues[] = addslashes($values[1]);
                            break;
                        case '>':
                        case '<':
                        case '>=':
                        case '<=':
                        case '<>':
                            $preparedValues[] = $values[1];
                            break;
                        case 'IN':
                        case 'NOT IN':
                            $inValues = explode(',', $values[1]);
                            foreach ($inValues as $inValue) {
                                $preparedValues[] = $inValue;
                            }
                            break;
                        default:
                            $preparedValues[] = addslashes($columnValue);
                    }
                } else if ($columnValue === 'IS NULL' || $columnValue === 'IS NOT NULL') {
                    continue;
                } else {
                    $preparedValues[] = addslashes($columnValue);
                }
            }
            return $preparedValues;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return string
     * @throws Exception
     */
    private static function getSetString (array $columns): string {
        try {
            if (empty($columns)) {
                throw new Exception('Empty $columns');
            }
            $setString = '';
            foreach ($columns as $columnIndex => $columnValue) {
                $index = self::convertToDbString($columnIndex);
                if ($columnValue === '++') {
                    $setString .= "$index = $index + 1, ";
                } else if (substr($columnValue, 0, 1) === '|') {
                    $setString.= "$index = ".substr($columnValue, 1).", ";
                } else if ($columnValue === 'NOW()' || $columnValue === 'CURDATE()') {
                    $setString .= "$index = $columnValue, ";
                } else {
                    $setString .= "$index = ?, ";
                }
            }            
            return ' SET '.rtrim($setString, ', ');
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     */
    private static function getPreparedSet (array $columns): array {
        try {
            if (empty($columns)) {
                throw new Exception('Empty $columns');
            }
            $preparedValues = array();
            foreach ($columns as $columnValue) {
                if ($columnValue === '++') {
                    continue;
                } else if ($columnValue === '' || $columnValue === 'NULL' || $columnValue === null) {
                    $preparedValues[] = null;
                } else if (substr($columnValue, 0, 1) === '|') {
                    continue;
                } else if (gettype($columnValue) === 'integer' && $columnValue === 0) {
                    $preparedValues[] = null;
                } else if ($columnValue === 'NOW()' || $columnValue === 'CURDATE()') {
                    continue;
                } else {
                    $preparedValues[] = addslashes($columnValue);
                }
            }
            return $preparedValues;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     */
    private static function getInsertString (array $columns): array {
        try {
            if (empty($columns)) {
                throw new Exception('Empty $columns');
            }
            $insertKeys = array();
            foreach ($columns as $columnValue) {
                $insertKeys[] = self::convertToDbString($columnValue);
            }
            return $insertKeys;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     */
    private static function getInsertValues (array $columns): array {
        try {
            if (empty($columns)) {
                throw new Exception('Empty $columns');
            }
            $insertValues = array();
            foreach ($columns as $columnValue) {
                if ($columnValue === '' || $columnValue === 'NULL' || $columnValue === null) {
                    $insertValues[] = '?';
                } else if (gettype($columnValue) === 'integer' && $columnValue === 0) {
                    $insertValues[] = '?';
                } else if ($columnValue === 'NOW()' || $columnValue === 'CURDATE()') {
                    $insertValues[] = $columnValue;
                } else if (substr($columnValue, 0, 1) === '|') {
                    $insertValues[] = substr($columnValue, 1);
                } else {
                    $insertValues[] = '?';
                }
            }
            return $insertValues;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     */
    private static function getPreparedInsert (array $columns): array {
        try {
            if (empty($columns)) {
                throw new Exception('Empty $columns');
            }
            $insertValues = array();
            foreach ($columns as $columnValue) {
                if ($columnValue === '' || $columnValue === 'NULL' || $columnValue === null) {
                    $insertValues[] = null;
                } else if (gettype($columnValue) === 'integer' && $columnValue === 0) {
                    $insertValues[] = null;
                } else if ($columnValue === 'NOW()' || $columnValue === 'CURDATE()') {
                    continue;
                } else if (substr($columnValue, 0, 1) === '|') {
                    continue;
                } else {
                    $insertValues[] = addslashes($columnValue);
                }
            }
            return $insertValues;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param string $tableName
     * @param array $columns
     * @param bool $throwEmpty
     * @param string $orderBy
     * @param string $orderDirection
     * @return array
     * @throws Exception
     */
    public static function select (string $tableName, array $columns=array(), bool $throwEmpty=false, string $orderBy='', string $orderDirection='ASC'): array {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            $order = !empty($orderBy) ? ' ORDER BY '.self::convertToDbString($orderBy).' '.$orderDirection : '';
            $preparedWheres = self::getPreparedWhere($columns);
            $statement = /** @lang text */
                "SELECT * FROM ".$tableName.self::getWhereString($columns).$order;
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedWheres));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedWheres);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            if ($throwEmpty && empty($result)) {
                throw new Exception('Select query result empty');
            }
            return !empty($result) ? self::convertFromDbIndex($result) : array();
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $tableName
     * @param array $columns
     * @param string $columnOut
     * @param bool $throwEmpty
     * @param string $orderBy
     * @param string $orderDirection
     * @return string
     * @throws Exception
     */
    public static function selectColumn (string $tableName, array $columns=array(), string $columnOut='', bool $throwEmpty=false, string $orderBy='', string $orderDirection='ASC'): string {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            if (empty($columnOut)) {
                throw new Exception('Empty $columnOut');
            }
            $order = !empty($orderBy) ? ' ORDER BY '.self::convertToDbString($orderBy).' '.$orderDirection : '';
            $preparedWheres = self::getPreparedWhere($columns);
            $statement = /** @lang text */
                "SELECT * FROM ".$tableName.self::getWhereString($columns).$order;
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedWheres));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedWheres);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            if ($throwEmpty && empty($result)) {
                throw new Exception('Select query result empty');
            }
            if (empty($result)) {
                return '';
            }
            $resultConvert = self::convertFromDbIndex($result);
            if (!array_key_exists($columnOut, $resultConvert)) {
                throw new Exception('Column '.$columnOut.' in result query not found');
            }
            return $resultConvert[$columnOut];
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $tableName
     * @param array $columns
     * @param int $fetchType
     * @param bool $throwEmpty
     * @param string $orderBy
     * @param string $orderDirection
     * @param string $limit
     * @return array
     * @throws Exception
     */
    public static function selectAll (string $tableName, array $columns=array(), int $fetchType=0, bool $throwEmpty=false, string $orderBy='', string $orderDirection='ASC', string $limit=''): array {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            $order = !empty($orderBy) ? ' ORDER BY '.self::convertToDbString($orderBy).' '.$orderDirection : '';
            $limit = !empty($limit) ? ' LIMIT '.$limit : '';
            $preparedWheres = self::getPreparedWhere($columns);
            $statement = /** @lang text */
                "SELECT * FROM ".$tableName.self::getWhereString($columns).$order.$limit;
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedWheres));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedWheres);
            $result = $stmt->fetchAll($fetchType === 1 ? PDO::FETCH_UNIQUE : PDO::FETCH_ASSOC);
            $stmt = null;
            if ($throwEmpty && empty($result)) {
                throw new Exception('Select query result empty');
            }
            return self::convertFromDbIndexes($result);
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $query
     * @param array $columns
     * @param bool $throwEmpty
     * @param string $orderBy
     * @param string $orderDirection
     * @return array
     * @throws Exception
     */
    public static function selectSql (string $query, array $columns=array(), bool $throwEmpty=false, string $orderBy='', string $orderDirection='ASC'): array {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($query)) {
                throw new Exception('Empty $query');
            }
            $order = !empty($orderBy) ? ' ORDER BY '.self::convertToDbString($orderBy).' '.$orderDirection : '';
            $preparedWheres = self::getPreparedWhere($columns);
            $statement = $query.self::getWhereString($columns).$order;
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedWheres));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedWheres);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            if ($throwEmpty && empty($result)) {
                throw new Exception('Select query result empty');
            }
            return !empty($result) ? self::convertFromDbIndex($result) : array();
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $query
     * @param array $columns
     * @param int $fetchType
     * @param bool $throwEmpty
     * @param string $orderBy
     * @param string $orderDirection
     * @param string $limit
     * @return array
     * @throws Exception
     */
    public static function selectSqlAll (string $query, array $columns=array(), int $fetchType=0, bool $throwEmpty=false, string $orderBy='', string $orderDirection='', string $limit=''): array {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($query)) {
                throw new Exception('Empty $statement');
            }
            $order = !empty($orderBy) ? ' ORDER BY '.self::convertToDbString($orderBy).' '.$orderDirection : '';
            $limit = !empty($limit) ? ' LIMIT '.$limit : '';
            $preparedWheres = self::getPreparedWhere($columns);
            $statement = $query.self::getWhereString($columns).$order.$limit;
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedWheres));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedWheres);
            if ($fetchType === 1) {
                $result = $stmt->fetchAll(PDO::FETCH_UNIQUE);
            } else if ($fetchType === 2) {
                $result = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
            } else {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $stmt = null;
            if ($throwEmpty && empty($result)) {
                throw new Exception('Select query result empty');
            }
            if ($fetchType === 2) {
                $resultNew = array();
                foreach ($result as $key => $list) {
                    $resultNew[$key] = self::convertFromDbIndexes($list);
                }
                return $resultNew;
            }
            return self::convertFromDbIndexes($result);
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $tableName
     * @param array $columns
     * @return int
     * @throws Exception
     */
    public static function count (string $tableName, array $columns=array()): int {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            $preparedWheres = self::getPreparedWhere($columns);
            $statement = /** @lang text */
                "SELECT COUNT(*) FROM ".$tableName.self::getWhereString($columns);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedWheres));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedWheres);
            $result = $stmt->fetch();
            $stmt = null;
            return $result[0];
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $tableName
     * @param array $insertArr
     * @return int
     * @throws Exception
     */
    public static function insert (string $tableName, array $insertArr=array()): int {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            if (empty($insertArr)) {
                throw new Exception('Empty $insertArr');
            }
            $insertKeys = array_keys($insertArr);
            $insertValues = array_values($insertArr);
            $preparedValues = self::getPreparedInsert($insertValues);
            $statement = /** @lang text */
                "INSERT INTO ".$tableName." (".implode(', ', self::getInsertString($insertKeys)).") VALUES (".implode(', ', self::getInsertValues($insertValues)).")";
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedValues));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedValues);
            $lastInsertId = self::$DBH->lastInsertId();
            $stmt = null;
            return $lastInsertId;
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $tableName
     * @param array $setArr
     * @param array $whereArr
     * @return int
     * @throws Exception
     */
    public static function update (string $tableName, array $setArr=array(), array $whereArr=array()): int {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            if (empty($whereArr)) {
                throw new Exception('Empty $whereArr');
            }
            if (empty($setArr)) {
                throw new Exception('Empty $setArr');
            }
            $preparedSetValues = self::getPreparedSet($setArr);
            $preparedWheres = self::getPreparedWhere($whereArr);
            $preparedValues = array_merge($preparedSetValues, $preparedWheres);
            $statement = /** @lang text */
                "UPDATE ".$tableName.self::getSetString($setArr).self::getWhereString($whereArr);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedValues));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedValues);
            $rowCount = $stmt->rowCount();
            $stmt = null;
            return $rowCount;
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }

    /**
     * @param string $tableName
     * @param array $whereArr
     * @return void
     * @throws Exception
     */
    public static function delete (string $tableName, array $whereArr=array()): void {
        try {
            if (empty(self::$DBH)) {
                throw new Exception('Connection lost');
            }
            if (empty($tableName)) {
                throw new Exception('Empty $tableName');
            }
            if (empty($whereArr)) {
                throw new Exception('Empty $whereArr');
            }
            $preparedValues = self::getPreparedWhere($whereArr);
            $statement = /** @lang text */
                "DELETE FROM ".$tableName.self::getWhereString($whereArr);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'sql = '.$statement);
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'params = '.json_encode($preparedValues));
            $stmt = self::$DBH->prepare($statement);
            $stmt->execute($preparedValues);
            $stmt = null;
        }
        catch (PDOException $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage());
        }
    }
}