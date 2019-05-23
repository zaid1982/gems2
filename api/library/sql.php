<?php

class Class_sql
{

    function __construct()
    {
        // 1010 - 1019
    }

    private function get_exception($codes, $function, $line, $msg)
    {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false)
                $msg = substr($msg, $pos + 2);
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
    }

    public function get_sql($title)
    {
        try {
            if ($title == 'vw_profile') {
                $sql = "SELECT
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email,
                    sys_address.address_desc,
                    sys_address.address_postcode,
                    sys_address.address_city,
                    ref_state.state_desc
                FROM sys_user
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id
                LEFT JOIN sys_address ON sys_address.address_id = sys_user_profile.address_id
                LEFT JOIN ref_state ON ref_state.state_id = sys_address.state_id";
            } else if ($title == 'vw_roles') {
                $sql = "SELECT
                    ref_role.role_id AS roleId, 
                    ref_role.role_desc AS roleDesc, 
                    ref_role.role_type AS roleType
                FROM (SELECT DISTINCT(role_id) FROM sys_user_role WHERE user_id = [user_id] GROUP BY role_id) roles
                INNER JOIN ref_role ON roles.role_id = ref_role.role_id AND role_status = 1";
            } else if ($title === 'vw_menu') {
                $sql = "SELECT 
                    sys_nav.nav_id,
                    sys_nav.nav_desc,
                    sys_nav.nav_icon,
                    sys_nav.nav_page,
                    sys_nav_second.nav_second_id,
                    sys_nav_second.nav_second_desc,
                    sys_nav_second.nav_second_page
                FROM
                    (SELECT
                            nav_id, nav_second_id, MIN(nav_role_turn) AS turn
                    FROM sys_nav_role
                    WHERE role_id IN ([roles])
                    GROUP BY nav_id, nav_second_id) AS nav_role
                LEFT JOIN sys_nav ON sys_nav.nav_id = nav_role.nav_id
                LEFT JOIN sys_nav_second ON sys_nav_second.nav_second_id = nav_role.nav_second_id
                WHERE nav_status = 1 AND (ISNULL(sys_nav_second.nav_second_id) OR nav_second_status = 1)
                ORDER BY nav_role.turn";
            } else if ($title === 'vw_user_profile') {
                $sql = "SELECT 
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email
                FROM sys_user 
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id AND user_profile_status = 1";
            } else if ($title === 'vw_check_assigned') {
                $sql = "SELECT 
                    wfl_task_assign.* 
                FROM wfl_task_assign  
                INNER JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task_assign.transaction_id AND transaction_status = 4";
            } else if ($title === 'vw_user_list') {
                $sql = "SELECT
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email,
                    user_group.roles
                FROM sys_user
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id AND sys_user_profile.user_profile_status = 1
                LEFT JOIN
                    (
                        SELECT 
                            user_id, GROUP_CONCAT(role_id) AS roles
                        FROM sys_user_role
                        GROUP BY user_id
                    ) user_group ON user_group.user_id = sys_user.user_id";
            } else if ($title === 'vw_activity_by_status') {
                $sql = "SELECT
                    activity_status, COUNT(*) AS total
                FROM ast_activity 
                [where_str] 
                GROUP BY activity_status";
            } else if ($title === 'vw_activity_list') {
                $sql = "SELECT
                    ast_activity.*,
                    activity_asset.assets AS assets
                FROM ast_activity
                LEFT JOIN 
                    (SELECT activity_id, GROUP_CONCAT(asset_id) AS assets 
                    FROM ast_activity_asset 
                    GROUP BY activity_id) activity_asset ON activity_asset.activity_id = ast_activity.activity_id";
            } else if ($title === 'vw_user_by_role') {
                $sql = "SELECT
                    role_id, COUNT(*) AS total
                FROM sys_user_role
                GROUP BY role_id";
            } else if ($title === 'vg_schedule_who_next') {
                $sql = "SELECT 
                    sys_user_role.user_id AS user_id,
	                IFNULL(activity_user.total_day / DATEDIFF((SELECT IF(MAX(activity_user_date) > CURDATE(), MAX(activity_user_date), CURDATE())+INTERVAL 1 DAY FROM ast_activity_user),DATE(user_role_time_created)), 0) AS index_work 
                FROM sys_user_role 
                LEFT JOIN 
                    (SELECT 
                        ast_activity_user.user_id AS user_id, 
                        COUNT(*) AS total_day, 
                        SUM(IF(activity_user_date = '[date_checked]', 1, 0)) AS total_today,
                        MAX(activity_user_date) as max_date 
                    FROM ast_activity_user 
                    LEFT JOIN sys_user_role ON sys_user_role.user_id = ast_activity_user.user_id AND sys_user_role.role_id = 2
                    GROUP BY user_id) activity_user ON activity_user.user_id = sys_user_role.user_id
                WHERE role_id = 2 AND IFNULL(activity_user.total_today, 0) < 1
                ORDER BY index_work LIMIT 1";
            } else {
                throw new Exception($this->get_exception('0098', __FUNCTION__, __LINE__, 'Sql not exist : ' . $title));
            }
            return $sql;
        } catch (Exception $e) {
            if ($e->getCode() == 30) {
                $errCode = 32;
            } else {
                $errCode = $e->getCode();
            }
            throw new Exception($this->get_exception('0099', __FUNCTION__, __LINE__, $e->getMessage()), $errCode);
        }
    }

}

?>
