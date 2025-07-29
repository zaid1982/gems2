<?php
require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';

// Initialize required classes
$constant = new Class_constant();
$fn_general = new Class_general();
$fn_general->__set('constant', $constant);

// Establish database connection
Class_db::getInstance()->db_connect();

echo "Debugging PPM Groups and Site Data\n";
echo "==================================\n\n";

try {
    // First, let's check what's in the ppm_group table
    echo "1. Checking PPM Group structure:\n";
    $sampleGroups = Class_db::getInstance()->db_select2('ppm_group', array(), 'ppm_group_id ASC', 5);
    echo "Sample groups (first 5):\n";
    foreach ($sampleGroups as $group) {
        echo "- ID: {$group['ppm_group_id']}, Name: {$group['ppm_group_name']}, Site ID: {$group['site_id']}\n";
    }
    echo "\n";

    // Check site table structure
    echo "2. Checking Site table structure:\n";
    $sampleSites = Class_db::getInstance()->db_select2('cli_site', array(), 'site_id ASC', 5);
    echo "Sample sites (first 5):\n";
    foreach ($sampleSites as $site) {
        echo "- Site ID: {$site['site_id']}, Name: {$site['site_name']}\n";
    }
    echo "\n";

    // Test the lookup logic
    echo "3. Testing site lookup logic:\n";
    $testGroup = $sampleGroups[0];
    $testSiteId = $testGroup['site_id'];
    echo "Testing lookup for site_id: $testSiteId\n";
    
    $siteInfo = Class_db::getInstance()->db_select_single2('cli_site', array('site_id' => $testSiteId));
    echo "Site lookup result: " . json_encode($siteInfo) . "\n";
    
    if ($siteInfo) {
        echo "Site name found: " . $siteInfo['site_name'] . "\n";
    } else {
        echo "No site found for site_id: $testSiteId\n";
        
        // Try with string conversion
        $siteInfo2 = Class_db::getInstance()->db_select_single2('cli_site', array('site_id' => strval($testSiteId)));
        echo "String conversion result: " . json_encode($siteInfo2) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
