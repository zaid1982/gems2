<?php
/**
 * Test User Validation - Check if attendance2 exists
 * Quick check before running the 20-ticket import test
 */

require_once '../../api/class/Constant.php';

$config = [
    'DB_HOST' => Constant::$dbHost,
    'DB_NAME' => Constant::$dbName,
    'DB_USER' => Constant::$dbUserName,
    'DB_PASS' => Constant::$dbUserPassword,
];

try {
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}", 
        $config['DB_USER'], 
        $config['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🧪 Test User Validation</h1>\n";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>\n";
    
    echo "<div class='info'>\n";
    echo "<h2>📋 Pre-Import Test Validation</h2>\n";
    echo "<p>Checking if test user 'attendance2' exists and is properly configured for the 20-ticket import test.</p>\n";
    echo "</div>\n";
    
    // Check if attendance2 user exists
    echo "<h2>👤 Test User Check</h2>\n";
    $stmt = $pdo->prepare("SELECT user_id, user_name, user_first_name, user_last_name, site_id, user_role FROM sys_user WHERE user_name = ?");
    $stmt->execute(['attendance2']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='success'>✅ User 'attendance2' found in system!</p>\n";
        echo "<table>\n";
        echo "<tr><th>Property</th><th>Value</th></tr>\n";
        echo "<tr><td>User ID</td><td>{$user['user_id']}</td></tr>\n";
        echo "<tr><td>Username</td><td>{$user['user_name']}</td></tr>\n";
        echo "<tr><td>Full Name</td><td>{$user['user_first_name']} {$user['user_last_name']}</td></tr>\n";
        echo "<tr><td>Site ID</td><td>{$user['site_id']}</td></tr>\n";
        echo "<tr><td>Role</td><td>{$user['user_role']}</td></tr>\n";
        echo "</table>\n";
        
        // Get site information
        if ($user['site_id']) {
            $stmt = $pdo->prepare("SELECT site_name, site_code FROM cli_site WHERE site_id = ?");
            $stmt->execute([$user['site_id']]);
            $site = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($site) {
                echo "<h3>🏢 User's Site Information</h3>\n";
                echo "<table>\n";
                echo "<tr><th>Site Property</th><th>Value</th></tr>\n";
                echo "<tr><td>Site Name</td><td>{$site['site_name']}</td></tr>\n";
                echo "<tr><td>Site Code</td><td>{$site['site_code']}</td></tr>\n";
                echo "</table>\n";
            }
        }
        
        // Check user profile for PPM group
        $stmt = $pdo->prepare("SELECT * FROM sys_user_profile WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            echo "<h3>👤 User Profile</h3>\n";
            echo "<table>\n";
            echo "<tr><th>Profile Property</th><th>Value</th></tr>\n";
            if (isset($profile['designation_id'])) echo "<tr><td>Designation ID</td><td>{$profile['designation_id']}</td></tr>\n";
            if (isset($profile['ppm_group_id'])) echo "<tr><td>PPM Group ID</td><td>{$profile['ppm_group_id']}</td></tr>\n";
            echo "</table>\n";
        } else {
            echo "<p class='warning'>⚠️ No user profile found for attendance2</p>\n";
        }
        
    } else {
        echo "<p class='error'>❌ User 'attendance2' NOT found in system!</p>\n";
        echo "<p class='warning'>You need to create this user before running the test.</p>\n";
        
        // Show available users for reference
        echo "<h3>🔍 Available Users (first 10)</h3>\n";
        $stmt = $pdo->query("SELECT user_name, user_first_name, user_last_name, site_id FROM sys_user ORDER BY user_name LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>\n";
        echo "<tr><th>Username</th><th>Full Name</th><th>Site ID</th></tr>\n";
        foreach ($users as $u) {
            echo "<tr><td>{$u['user_name']}</td><td>{$u['user_first_name']} {$u['user_last_name']}</td><td>{$u['site_id']}</td></tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check test sites
    echo "<h2>🏢 Test Sites Check</h2>\n";
    $testSites = ['Site A', 'Site B', 'Site C'];
    
    foreach ($testSites as $siteName) {
        $stmt = $pdo->prepare("SELECT site_id, site_name, site_code FROM cli_site WHERE site_name LIKE ?");
        $stmt->execute(["%$siteName%"]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($site) {
            echo "<p class='success'>✅ {$siteName} found: {$site['site_name']} (ID: {$site['site_id']})</p>\n";
        } else {
            echo "<p class='warning'>⚠️ {$siteName} not found - may need site mapping</p>\n";
        }
    }
    
    // Quick summary
    echo "<div class='info'>\n";
    echo "<h2>📝 Test Readiness Summary</h2>\n";
    if ($user) {
        echo "<p class='success'>✅ Test user 'attendance2' is ready</p>\n";
        echo "<p>You can now proceed with importing the 20-ticket test file:</p>\n";
        echo "<p><strong>File</strong>: <code>test_wo_import_20_tickets.csv</code></p>\n";
        echo "<p><strong>Location</strong>: <code>/developer/test/</code></p>\n";
    } else {
        echo "<p class='error'>❌ Test user 'attendance2' needs to be created first</p>\n";
        echo "<p>Create the user in your system before running the import test.</p>\n";
    }
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>Database Error: " . $e->getMessage() . "</p>";
}
?>
