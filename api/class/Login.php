<?php


class Login extends General {

    /**
     */
    function __construct() {
    }

    /**
     * @throws Exception
     */
    public function testLogin() {
        try {
            self::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__CLASS__);

            $xx = DbMysql::insert('temp3', array('tempDesc'=>'XXX', 'tempDate'=>'CURDATE()', 'tempTime'=>'NOW()'));
            print_r($xx);
            echo('<br/>');
            //$xx = DbMysql::selectSqlAll(/** @lang text */ "SELECT * FROM v_address_full WHERE city_id = ?", array(57));
            /*$xx = DbMysql::selectAll('sys_user', array('userId'=>'IN|1,2,3,4'), 0, 0, 'site_id DESC');
            foreach ($xx as $a => $b) {
                echo($a.' - ');
                print_r($b);
                echo('<br/>');
            }*/
        } catch (Exception|Throwable $ex) {
            self::logError(__CLASS__,__FUNCTION__, $ex->getLine(), $ex->getMessage());
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
    }
}