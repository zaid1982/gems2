<?php
echo "Testing PPM Groups with Site Information from API Directory\n";
echo "=========================================================\n\n";

// Test by setting the POST data and including the script directly
$_POST['action'] = 'get_ppm_groups';

echo "Setting action: get_ppm_groups\n";
echo "Including gamification_config.php...\n\n";

// Capture the output
ob_start();
include 'gamification_config.php';
$response = ob_get_clean();

echo "Raw Response:\n";
echo $response . "\n\n";

// Parse and display the data
$responseData = json_decode($response, true);
if ($responseData && isset($responseData['success'])) {
    if ($responseData['success']) {
        $ppmGroups = $responseData['data'];
        $groupsBySite = array();
        
        // Group by site for display
        foreach ($ppmGroups as $group) {
            $siteName = $group['site_name'] ?? 'Unknown Site';
            if (!isset($groupsBySite[$siteName])) {
                $groupsBySite[$siteName] = array();
            }
            $groupsBySite[$siteName][] = $group;
        }
        
        echo "PPM Groups Organized by Site:\n";
        echo "=============================\n\n";
        
        foreach ($groupsBySite as $siteName => $groups) {
            echo "📍 Site: $siteName (" . count($groups) . " groups)\n";
            echo str_repeat("-", 50) . "\n";
            foreach ($groups as $group) {
                echo "  • ID: {$group['ppm_group_id']}, Name: {$group['ppm_group_name']}, Role: {$group['role_id']}\n";
            }
            echo "\n";
        }
        
        echo "Total Sites: " . count($groupsBySite) . "\n";
        echo "Total PPM Groups: " . count($ppmGroups) . "\n";
    } else {
        echo "API Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "Invalid JSON response or missing success field\n";
    echo "Response data: ";
    var_dump($responseData);
}
?>
