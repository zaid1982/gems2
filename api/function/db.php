<?php
require_once 'library/sql.php';

class Class_db{
    
    private static $instance;
    private $DBH;
    private $fn_general;
            
    function __construct()
    {
        $this->fn_general = new Class_general();
    }
    
    private function __clone() {}

    public static function getInstance() {
        if (!Class_db::$instance instanceof self) {
             Class_db::$instance = new self();
        }
        return Class_db::$instance;
    }
    
    private function get_exception($codes, $function, $line, $msg) {
        if ($msg != '') {
            return "(ErrCode:".$codes.") [".__CLASS__.":".$function.":".$line."] - ".$msg;
        }
        else {
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
        }
        else {
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
        }
        else {
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
        }
        else {
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
        }
        else {
            throw new Exception($this->get_exception('0004', __FUNCTION__, __LINE__, 'Get Property not exist ['.$property.']'));
        }
    }
    
    private function get_whereAnd_str($columnsArr) {
        $where_str = NULL;
        foreach ($columnsArr as $item => $value) {
            if ($value == '' || $value === '%%') {
                continue;
            }
            $l1 = substr($value, 0, 1);
            $l2 = substr($value, 0, 2);
            if ($item === 'w1' || $item === 'w2') {
                $where_str .= $value." AND ";
            } 
            else if ($value == 'is NULL' || $value == 'is not NULL') {
                $where_str .= "$item $value AND ";
            } 
            else if ($l1 == '%') {
                $where_str .= "$item like '".str_replace("'", "`", $value)."' AND ";
            } 
            else if ($l1 == '(') {
                $where_str .= "$item in $value AND ";
            } 
            else if ($l2 == 'N(') {
                $r1 = substr($value, 1);
                $where_str .= "$item not in $r1 AND ";
            } 
            else if ($l2 == '>=' || $l2 == '<=' || $l2 == '<>') {
                $r2 = substr($value, 2);
                $where_str .= "$item $l2 '$r2' AND "; 
            } 
            else if ($l1 == '>' || $l1 == '<') {
                $r1 = substr($value, 1);
                if ($r1 == 'Now()') {
                    $where_str .= "$item $l1 $r1 AND "; 
                }
                else {
                    $where_str .= "$item $l1 '$r1' AND "; 
                }
            } 
            else {
                $where_str .= "$item = '".addslashes($value)."' AND ";
            }
        } 
        
        if ($where_str != NULL) {
            $where_str = " WHERE ".rtrim($where_str, 'AND ');
        }
        return $where_str;
    }

    /**
     * @param $columnsArr
     * @return string|null
     * @throws Exception
     */
    private function get_set_str($columnsArr) {
        $set_str = NULL;
        foreach ($columnsArr as $item => $value) {
            if ($value === '') {
                $set_str .= "$item=NULL, ";
                continue;
            }
            $l1 = substr($value, 0, 1);
            if ($value === 0 || $value === '0') {
                $set_str .= "$item='0', ";
            }
            else if ($value == 'Now()') {
                $set_str .= "$item=Now(), ";
            } 
            else if ($value == 'Curdate()') {
                $set_str .= "$item=Curdate(), ";
            }
            else if ($value == 'NULL') {
                $set_str .= "$item=$value, ";
            }
            else if ($value == '++') {
                $set_str .= "$item=$item+1, ";
            }
            else if ($l1 == '|') {
                $r1 = substr($value, 1);
                $set_str .= "$item=$r1, ";
            } else {
                $set_str .= "$item='".addslashes($value)."', ";  // $set_str .= "$item='".str_replace("'", "`", $value)."', ";
            }
        } 
        
        if ($set_str != NULL) {
            $set_str = " SET ".rtrim($set_str, ', ');
        }
        else {
            throw new Exception($this->get_exception('0007', __FUNCTION__, __LINE__, ''));
        }
        return $set_str;
    }
    
    private function get_comma_str($valueArr) {
        $comma_str = NULL;
        foreach ($valueArr as $item => $value) {
            if ($value != '') {
                $comma_str .= "$item, ";
            }
        } 
        
        if ($comma_str != NULL) {
            $comma_str = " (".rtrim($comma_str, ", ").")";
        }
        else {
            $comma_str = " ";
        }
        return $comma_str;
    }
    
    private function get_commaVal_str($valueArr) {
        $comma_str = NULL;        
        foreach ($valueArr as $item => $value) {
            if ($value === '') {
                continue;            
            }
            $l1 = substr($value, 0, 1);
            if ($value == 'Now()') {
                $comma_str .= "$value, ";
            }
            else if ($l1 == '|') {
                $r1 = substr($value, 1);
                $comma_str .= "$r1, ";
            } 
            else if ($value == 'Curdate()') {
                $comma_str .= "$value, ";
            } 
            else {
                $comma_str .= "'".addslashes($value)."', ";
            }
        } 
        
        if ($comma_str != NULL) {
            $comma_str = " (".rtrim($comma_str, ", ").")";
        } 
        else {
            $comma_str = " ()";
        }        
        return $comma_str;
    }
    
    public function convert_tStamp ($inputDate) {    
        $outputDate = '';
        $dateSplit = explode("/", $inputDate);
        if (count($dateSplit) == 3) {
            list($day, $month, $year) = $dateSplit;
            $outputDate = $year."-".$month."-".$day;
        }
        return $outputDate;
    }
    
    public function get_date_split ($inputDate) {
        $outputDate = array();
        if (strlen($inputDate) == 23) {
            $dateRangeSplit = explode(" - ", $inputDate);
            if (count($dateRangeSplit)) {
                list($dateFrom, $dateTo) = $dateRangeSplit;
                $outputDate[0] = $this->convert_tStamp($dateFrom);
                $outputDate[1] = $this->convert_tStamp($dateTo);
            }
        }
        return $outputDate;
    }

    /**
     * @param $tablename
     * @param array $param
     * @return mixed|string
     * @throws Exception
     */
    public function get_sql ($tablename, $param=array()) {
        if (substr($tablename, 0, 2) == 'vw' || substr($tablename, 0, 2) == 'mw' || substr($tablename, 0, 2) == 'dt' || substr($tablename, 0, 2) == 'vg' || substr($tablename, 0, 2) == 'mg') {
            $fn_sql = new Class_sql();
            $s = $fn_sql->get_sql($tablename);
            if (substr($tablename, 0, 2) == 'vg' || substr($tablename, 0, 2) == 'mg') {
                $s = "SELECT * FROM (".$s.") as mainTable ";
            }          
            if (!empty($param)){
                foreach ($param as $item => $value) {
                    if (strpos($s,"[".$item."]") !== false) {
                        $s = str_replace ("[".$item."]", $value, $s);
                    }
                }
            }
        } else {
            $s = "SELECT * FROM ".$tablename;
        }
        return $s;
    }

    /**
     * @param $tablename
     * @param array $param
     * @return mixed|string
     * @throws Exception
     */
    public function get_sql_v2 ($tablename, $param=array()) {
        if (substr($tablename, 0, 2) == 'vw' || substr($tablename, 0, 2) == 'dt') {
            $fn_sql = new Class_sql();
            $s = $fn_sql->get_sql($tablename);
            if (strpos($s,"[season_id]") !== false) {
                $s = str_replace ("[season_id]", $_SESSION["season_id"], $s);
            }
            if (!empty($param)){
                foreach ($param as $item => $value) {
                    if (strpos($s,"[".$item."]") !== false) {
                        $s = str_replace ("[".$item."]", $value, $s);
                    }
                }
            }
        } else {
            $s = "SELECT * FROM ".$tablename." ";
        } 
        return $s;
    }

    /**
     * @param $tablename
     * @param array $columns
     * @param string $orderby
     * @param string $limit
     * @param int $throwEmpty
     * @param array $sqlParam
     * @return mixed
     * @throws Exception
     */
    public function db_select($tablename, $columns=array(), $orderby='', $limit='', $throwEmpty=0, $sqlParam=array())
    {
        try { 
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $where_str = '';
            if (!empty($columns)) { 
                $where_str = $this->get_whereAnd_str($columns);
            } 
            if ($orderby != '') { 
                $orderby = ' ORDER BY '.$orderby;             
            }
            $limits = $limit != '' ? $limit = ' LIMIT '.$limit : ' ';
            $sql = $this->get_sql($tablename, $sqlParam).$where_str.$orderby.$limits;
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);                
            if (empty($result)){
                if ($throwEmpty == 1) {
                    throw new Exception($this->get_exception('0010', __FUNCTION__, __LINE__, 'Select query result empty'));
                }
                elseif ($throwEmpty == 2) {
                    throw new Exception($this->get_exception('0011', __FUNCTION__, __LINE__, 'Select query result empty'), 30);      
                }
            } 
            return $result;
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @param $tablename
     * @param array $columns
     * @param string $orderby
     * @param int $throwEmpty
     * @param array $sqlParam
     * @return mixed
     * @throws Exception
     */
    public function db_select_single($tablename, $columns=array(), $orderby='', $throwEmpty=0, $sqlParam=array())
    {
        try { 
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $where_str = '';
            if (!empty($columns)) {  
                $where_str = $this->get_whereAnd_str($columns);                 
            }  
            if (!empty($orderby)) {
                $orderby = ' ORDER BY '.$orderby;
            }
            $sql = $this->get_sql($tablename, $sqlParam).$where_str.$orderby." LIMIT 1";
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);                
            if (empty($result)){
                if ($throwEmpty == 1) {
                    throw new Exception($this->get_exception('0010', __FUNCTION__, __LINE__, 'Select query result empty'));
                }
                elseif ($throwEmpty == 2) {
                    throw new Exception($this->get_exception('0011', __FUNCTION__, __LINE__, 'Select query result empty'), 30); 
                }
            } 
            return $result;
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @param $tablename
     * @param $columns
     * @param $colOut
     * @param string $orderby
     * @param int $throwEmpty
     * @param array $sqlParam
     * @return string
     * @throws Exception
     */
    public function db_select_col ($tablename, $columns, $colOut, $orderby='', $throwEmpty=0, $sqlParam=array())
    {
        try { 
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $where_str = '';
            if (!empty($columns)) {  
                $where_str = $this->get_whereAnd_str($columns);                 
            } 
            if (!empty($orderby)) {
                $orderby = ' ORDER BY '.$orderby;
            }
            $sql = $this->get_sql($tablename,$sqlParam).$where_str.$orderby." LIMIT 1";
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);                
            if (empty($result)){
                if ($throwEmpty == 1) {
                    throw new Exception($this->get_exception('0010', __FUNCTION__, __LINE__, 'Select query result empty'));
                } 
                elseif ($throwEmpty == 2) {
                    throw new Exception($this->get_exception('0011', __FUNCTION__, __LINE__, 'Select query result empty'), 30);                    
                }
                else {
                    $result = '';       
                }
            } else {
                if (!array_key_exists($colOut, $result)) {
                    throw new Exception($this->get_exception('0012', __FUNCTION__, __LINE__, 'Column in result query not found'));                    
                }
                $result = $result[$colOut];
            }
            return $result;
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @param $tablename
     * @param $columns
     * @param $colOut
     * @param string $orderby
     * @param int $throwEmpty
     * @param array $sqlParam
     * @return array
     * @throws Exception
     */
    public function db_select_colm ($tablename, $columns, $colOut, $orderby='', $throwEmpty=0, $sqlParam=array())
    {
        try { 
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $arrCols = array();
            $where_str = '';
            if (!empty($columns)) {  
                $where_str = $this->get_whereAnd_str($columns);                 
            } 
            if ($orderby !== '') {
                $orderby = ' ORDER BY '.$orderby;
            }
            $sql = $this->get_sql($tablename,$sqlParam).$where_str.$orderby;
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);                
            if (empty($result)){
                if ($throwEmpty == 1) {
                    throw new Exception($this->get_exception('0010', __FUNCTION__, __LINE__, 'Select query result empty'));
                }
                elseif ($throwEmpty == 2) { 
                    throw new Exception($this->get_exception('0011', __FUNCTION__, __LINE__, 'Select query result empty'), 30);  
                }
            } else {
                foreach ($result as $rows) {       
                    if (!array_key_exists($colOut, $rows)) { 
                        throw new Exception($this->get_exception('0012', __FUNCTION__, __LINE__, 'Column in result query not found')); 
                    }
                    array_push($arrCols, $rows[$colOut]);                                              
                }
            }
            return $arrCols;
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @param $tablename
     * @param $colOut
     * @param array $columns
     * @param string $orderby
     * @param string $limit
     * @param int $throwEmpty
     * @param array $sqlParam
     * @return array
     * @throws Exception
     */
    public function db_select_cols($tablename, $colOut, $columns=array(), $orderby='', $limit='', $throwEmpty=0, $sqlParam=array())
    {
        try { 
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $where_str = '';
            if (!empty($columns)) {  
                $where_str = $this->get_whereAnd_str($columns);                     
            } 
            if ($orderby !== '') {
                $orderby = ' ORDER BY '.$orderby;
            }
            $limits = $limit != '' ? $limit = ' LIMIT '.$limit : ' ';
            $sql = $this->get_sql($tablename, $sqlParam).$where_str.$orderby.$limits;
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);                
            if (empty($result)){
                if ($throwEmpty == 1) {
                    throw new Exception($this->get_exception('0010', __FUNCTION__, __LINE__, 'Select query result empty'));
                }
                elseif ($throwEmpty == 2) {
                    throw new Exception($this->get_exception('0011', __FUNCTION__, __LINE__, 'Select query result empty'), 30);                   
                }                 
            } 
            $arrCols = array();
            foreach ($result as $rows) {       
                if (array_key_exists($colOut[0], $rows) && array_key_exists($colOut[1], $rows)) {
                    $arrCols[$rows[$colOut[0]]] = $rows[$colOut[1]];
                }
                else {
                    throw new Exception($this->get_exception('0012', __FUNCTION__, __LINE__, 'Column in result query not found'));   
                }
            }
            return $arrCols;
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @param $tablename
     * @param array $columns
     * @param array $sqlParam
     * @return mixed
     * @throws Exception
     */
    public function db_count($tablename, $columns=array(), $sqlParam=array())
    {
        try {
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $where_str = '';
            if (!empty($columns)) {  
                $where_str = $this->get_whereAnd_str($columns); 
            }
            //$sql = "SELECT count(*) FROM ".$this->get_sql($tablename, $sqlParam).$where_str;
            $sql = "SELECT COUNT(*) FROM (".$this->get_sql($tablename, $sqlParam).$where_str.") aa";
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->query($sql);
            $result = $stmt->fetch();
            return $result[0];
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @param $tablename
     * @param null $columns
     * @return mixed
     * @throws Exception
     */
    public function db_insert($tablename, $columns=NULL)
    {
        try {    
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            if (empty($columns)) {
                $sql = empty($columns) ? "INSERT INTO ".$tablename." () VALUES ()" : "INSERT INTO ".$tablename.$this->get_comma_str($columns)." VALUES ".$this->get_commaVal_str($columns);
            } else {
                $sql = "INSERT INTO ".$tablename.$this->get_comma_str($columns)." VALUES ".$this->get_commaVal_str($columns);
            }
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $this->DBH->exec($sql);
            return $this->DBH->lastInsertId();
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()), $e->getCode());
        }
    }

    /**
     * @param $tablename
     * @param $setArr
     * @param $whereArr
     * @return mixed
     * @throws Exception
     */
    public function db_update($tablename, $setArr, $whereArr)
    {
        try {
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            if (empty($whereArr)) {
                throw new Exception($this->get_exception('0015', __FUNCTION__, __LINE__, 'Where String empty'));
            }
            $whereStr = $this->get_whereAnd_str($whereArr);
            if ($whereStr != NULL) {
                $sql = "UPDATE ".$tablename.$this->get_set_str($setArr).$whereStr;
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
                $stmt = $this->DBH->prepare($sql);
                $stmt->execute();
                return $stmt->rowCount();
            } else {
                throw new Exception($this->get_exception('0014', __FUNCTION__, __LINE__, 'Where String empty'));            
            }
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()), $e->getCode());
        }
    }

    /**
     * @param $tablename
     * @param $columns
     * @param null $whereCustom
     * @return mixed
     * @throws Exception
     */
    public function db_delete($tablename, $columns, $whereCustom=NULL)
    {
        try {
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            if (empty($columns)) {
                throw new Exception($this->get_exception('0014', __FUNCTION__, __LINE__, 'Where String empty'));
            }
            if (!empty($whereCustom)) {
                $sql = "DELETE FROM ".$tablename.' WHERE '.$whereCustom;
            }
            else {    
                $whereStr = $this->get_whereAnd_str($columns);
                if ($whereStr == NULL || $whereStr == '') {
                    throw new Exception($this->get_exception('0014', __FUNCTION__, __LINE__, 'Where String empty'));                
                }
                $sql = "DELETE FROM ".$tablename.$whereStr;
            }
            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, $sql);
            $stmt = $this->DBH->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    public function db_beginTransaction() {
        try {
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $this->DBH->beginTransaction();
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    public function db_commit() {
        try {
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
            $this->DBH->commit();
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    public function db_connect() {
        try {
            $config = parse_ini_file('library/config.ini');
            $dbname = $config['dbname'];    
            $dbhost = $config['dbhost'];    
            $this->DBH = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $config['username'], $config['password']);
            $this->DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    public function db_rollback() {
         try {
            if (empty($this->DBH)) {
                throw new Exception($this->get_exception('0006', __FUNCTION__, __LINE__, 'Connection lost'));
            }
             $this->DBH->rollBack();
        }
        catch(PDOException $e) {
            throw new Exception($this->get_exception('0005', __FUNCTION__, __LINE__, $e->getMessage()));
        }
    }
    
    public function db_close() {  
        $this->DBH = null;
    }
}
?>
