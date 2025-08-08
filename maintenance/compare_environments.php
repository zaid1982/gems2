<?php
/**
 * Environment Comparison Tool
 * Compares workflow configuration between environments
 */

require_once('../api/class/Constant.php');
require_once('../api/class/DbMysql.php');

// Database connection
DbMysql::connect();

try {
    echo "=== STAGING vs DEV/LOCAL COMPARISON ===\n\n";
    
    echo "1. WORKFLOW FLOWS COMPARISON...\n";
    $flows = DbMysql::selectAll('wfl_flow', array(), 1);
    echo "   Total Flows in Staging: " . count($flows) . "\n";
    foreach ($flows as $flow) {
        echo "   - Flow {$flow['flowId']}: {$flow['flowName']} (Status: {$flow['flowStatus']})\n";
    }
    
    echo "\n2. WORKFLOW CHECKPOINTS FOR FLOW 2...\n";
    $checkpoints = DbMysql::selectAll('wfl_checkpoint', array('flowId' => 2), 1);
    echo "   Total Checkpoints for Flow 2: " . count($checkpoints) . "\n";
    foreach ($checkpoints as $cp) {
        echo "   - CP {$cp['checkpointId']}: {$cp['checkpointName']} (Type: {$cp['checkpointType']}, Role: {$cp['roleId']}, Group: {$cp['groupId']})\n";
    }
    
    echo "\n3. REFERENCE ROLES...\n";
    $roles = DbMysql::selectAll('ref_role', array(), 1, 20);
    echo "   Total Roles: " . count($roles) . "\n";
    foreach ($roles as $role) {
        echo "   - Role {$role['roleId']}: {$role['roleDesc']}\n";
    }
    
    echo "\n4. USER GROUPS FOR PUBLIC USER...\n";
    $userGroups = DbMysql::selectAll('sys_user_group', array('userId' => 1349), 1);
    echo "   Total Groups for User 1349: " . count($userGroups) . "\n";
    foreach ($userGroups as $ug) {
        echo "   - Group {$ug['groupId']} (UserGroupId: {$ug['userGroupId']})\n";
    }
    
    echo "\n5. USER ROLES FOR PUBLIC USER...\n";
    $userRoles = DbMysql::selectAll('sys_user_role', array('userId' => 1349), 1);
    echo "   Total Roles for User 1349: " . count($userRoles) . "\n";
    foreach ($userRoles as $ur) {
        echo "   - Role {$ur['roleId']} in Group {$ur['groupId']} (Status: {$ur['userRoleStatus']})\n";
    }
    
    echo "\n=== COMPARISON COMPLETE ===\n";
    echo "Save this output and compare with your dev/local environment.\n";
    echo "Missing records in staging need to be synchronized.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

DbMysql::close();
?>
