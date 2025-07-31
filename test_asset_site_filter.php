<?php
/**
 * Test script to verify asset site filtering is working
 */

require_once 'api/library/constant.php';
require_once 'api/function/db.php';

Class_db::getInstance()->db_connect();

echo "Asset Site Filtering Test\n";
echo "=========================\n\n";

// Test data for demotapah (UiTM Tapah, site_id = 10)
$testUserId = 1331; // demotapah
$testUserSite = 10;  // UiTM Tapah
$validContractId = 15; // UiTM Tapah Contract
$invalidContractId = 1; // Bank Negara Malaysia HQ Contract (different site)

echo "Test User: demotapah (User ID: $testUserId, Site: $testUserSite)\n";
echo "Valid Contract ID: $validContractId (UiTM Tapah)\n";
echo "Invalid Contract ID: $invalidContractId (Bank Negara Malaysia HQ)\n\n";

// Check user's site
$user = Class_db::getInstance()->db_select('sys_user', array('user_id'=>$testUserId));
echo "User's actual site: " . $user['site_id'] . "\n";

// Check user's roles  
$userRoles = Class_db::getInstance()->db_select_colm('sys_user_role', array('user_id'=>$testUserId), 'role_id');
echo "User's roles: " . implode(', ', $userRoles) . "\n";

// Check if user is administrator
$isAdministrator = false;
foreach ($userRoles as $roleId) {
    if (in_array($roleId, [1, 10])) {
        $isAdministrator = true;
        break;
    }
}
echo "Is Administrator: " . ($isAdministrator ? 'Yes' : 'No') . "\n\n";

// Test contract site validation
echo "Contract Validation Test:\n";
echo "-------------------------\n";

// Valid contract (should belong to user's site)
$contractSite = Class_db::getInstance()->db_select_colm('cli_contract', array('contract_id'=>$validContractId), 'site_id');
echo "Contract $validContractId belongs to site: " . $contractSite[0] . " - " . ($contractSite[0] == $testUserSite ? "ALLOWED" : "BLOCKED") . "\n";

// Invalid contract (should belong to different site)
$contractSite = Class_db::getInstance()->db_select_colm('cli_contract', array('contract_id'=>$invalidContractId), 'site_id');
echo "Contract $invalidContractId belongs to site: " . $contractSite[0] . " - " . ($contractSite[0] == $testUserSite ? "ALLOWED" : "BLOCKED") . "\n\n";

// Test asset counts
echo "Asset Count Test:\n";
echo "-----------------\n";

$validAssets = Class_db::getInstance()->db_count('ast_asset', array('contract_id'=>$validContractId));
echo "Assets in valid contract ($validContractId): $validAssets\n";

$invalidAssets = Class_db::getInstance()->db_count('ast_asset', array('contract_id'=>$invalidContractId)); 
echo "Assets in invalid contract ($invalidContractId): $invalidAssets\n\n";

echo "Test completed. The API should now block access to assets from contract $invalidContractId for user demotapah.\n";

Class_db::getInstance()->db_close();
?>
