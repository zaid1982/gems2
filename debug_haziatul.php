<?php
// Debug script to check haziatul user details and site filtering

// Include necessary files
require_once 'api/class/DbMysql.php';

try {
    // Check haziatul user details
    echo "=== User haziatul Details ===\n";
    $userData = DbMysql::selectSql("SELECT user_id, user_name, site_id FROM sys_user WHERE user_name = ?", array('haziatul'));
    if (!empty($userData)) {
        $user = $userData[0];
        echo "User ID: " . $user['user_id'] . "\n";
        echo "User Name: " . $user['user_name'] . "\n";
        echo "Site ID: " . $user['site_id'] . "\n";
        
        // Check user roles
        echo "\n=== User Roles ===\n";
        $userRoles = DbMysql::selectSqlAll("SELECT role_id FROM sys_user_role WHERE user_id = ?", array($user['user_id']));
        foreach ($userRoles as $role) {
            echo "Role ID: " . $role['role_id'] . "\n";
        }
        
        // Check if user is administrator
        $isAdministrator = false;
        foreach ($userRoles as $role) {
            if (in_array($role['role_id'], [1, 10])) {
                $isAdministrator = true;
                break;
            }
        }
        echo "Is Administrator: " . ($isAdministrator ? "YES" : "NO") . "\n";
        
        // Test the SysUser getRef method
        echo "\n=== Testing SysUser getRef() ===\n";
        require_once 'api/class/General.php';
        require_once 'api/class/SysUser.php';
        
        $sysUser = new SysUser($user['user_id'], true);
        $result = $sysUser->getRef();
        
        echo "Number of users returned: " . count($result) . "\n";
        echo "Users from site " . $user['site_id'] . ":\n";
        foreach ($result as $usr) {
            echo "- " . $usr['user_name'] . " (site: " . $usr['site_id'] . ")\n";
        }
        
    } else {
        echo "User 'haziatul' not found!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
