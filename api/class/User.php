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
            $sysUserArrNew = array();
            $sysUserArr = DbMysql::selectAll('sys_user', array(), 1);
            foreach ($sysUserArr as $sysUser) {
                $sysUserNew = parent::arraySpliceAssoc($sysUser, array('userName', 'userType', 'userFirstName', 'userMykadNo', 'siteId', 'uploadId', 'userFailAttempt', 'userTimeCreated', 'userTimeActivate',
                    'userTimeLogin', 'userTimeBlock', 'userSignature', 'userStatus'));
                $sysUserArrNew[] = $sysUserNew;
            }
            return $sysUserArrNew;
        } catch (Exception|Throwable $ex) {
            throw new Exception('['.__CLASS__.':'.__FUNCTION__.'] '.$ex->getMessage(), $ex->getCode());
        }
    }
}