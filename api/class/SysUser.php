<?php

class SysUser extends General {

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
            $sysUserArr = DbMysql::selectSqlAll(/** @lang text */"SELECT
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email,
                    sys_user_profile.designation_id,
                    user_group.group_id,
                    user_group.roles
                FROM sys_user
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id AND sys_user_profile.user_profile_status = 1
                LEFT JOIN
                    (
                        SELECT 
                            user_id, GROUP_CONCAT(role_id) AS roles, MIN(group_id) AS group_id
                        FROM sys_user_role
                        GROUP BY user_id
                    ) user_group ON user_group.user_id = sys_user.user_id", array(), 1);
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