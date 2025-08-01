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
            
            // Get current user's site information for site filtering
            $currentUserData = DbMysql::selectSql("SELECT site_id FROM sys_user WHERE user_id = ?", array($this->userId));
            $userSite = !empty($currentUserData) ? $currentUserData[0]['siteId'] : null;
            
            // Debug: Log user information
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Current User ID: ' . $this->userId . ', Site ID: ' . $userSite);
            
            // Check if user is administrator (roles 1 or 10)
            $userRoles = DbMysql::selectSqlAll("SELECT role_id FROM sys_user_role WHERE user_id = ?", array($this->userId));
            $isAdministrator = false;
            foreach ($userRoles as $role) {
                if (in_array($role['role_id'], [1, 10])) {
                    $isAdministrator = true;
                    break;
                }
            }
            
            // Debug: Log roles and administrator status
            $roleIds = array_column($userRoles, 'role_id');
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'User Roles: ' . implode(',', $roleIds) . ', Is Administrator: ' . ($isAdministrator ? 'YES' : 'NO'));
            
            // Build query with site filtering for non-administrators
            $whereClause = "";
            $params = array();
            if (!$isAdministrator && !empty($userSite)) {
                $whereClause = " WHERE sys_user.site_id = ?";
                $params[] = $userSite;
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Applying site filter for site_id: ' . $userSite);
            } else {
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'No site filter applied - Administrator: ' . ($isAdministrator ? 'YES' : 'NO') . ', UserSite: ' . $userSite);
            }
            
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
                    ) user_group ON user_group.user_id = sys_user.user_id" . $whereClause, $params, 1);
            
            // Debug: Log query results
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Query returned ' . count($sysUserArr) . ' users');
            foreach ($sysUserArr as $user) {
                parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'User: ' . $user['userName'] . ' (Site: ' . $user['siteId'] . ')');
            }
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