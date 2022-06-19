<?php

class User extends General {

    function __construct (int $userId=0, bool $isLogged=false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRef (): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering '.__FUNCTION__);
            $sysUserArr = DbMysql::selectAll('sys_user', array(), 1);
            foreach ($sysUserArr as $i=>$sysUser) {
                $sysUserArr[$i]['userPassword'] = null;
                $sysUserArr[$i]['userPasswordTemp'] = null;
                $sysUserArr[$i]['userToken'] = null;
                $sysUserArr[$i]['userDeviceId'] = null;
                $sysUserArr[$i][1] = null;
                $sysUserArr[$i][2] = null;
                $sysUserArr[$i][10] = null;
                $sysUserArr[$i][17] = null;
            }
            return $sysUserArr;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}