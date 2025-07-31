<?php
// Check actual users for CSV mapping
$config = parse_ini_file('api/library/config.ini');

try {
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}", 
        $config['DB_USER'], 
        $config['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>GEMS Users (showing actual schema):</h2>\n";
    
    // Get all users
    $stmt = $pdo->query("SELECT user_id, user_name, user_first_name, user_last_name, site_id FROM sys_user ORDER BY user_name LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>First Name</th><th>Last Name</th><th>Site ID</th></tr>\n";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['user_id'] . "</td>";
        echo "<td><strong>" . $user['user_name'] . "</strong></td>";
        echo "<td>" . ($user['user_first_name'] ?? '') . "</td>";
        echo "<td>" . ($user['user_last_name'] ?? '') . "</td>";
        echo "<td>" . $user['site_id'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>For your CSV, you should use the <strong>Username</strong> column values in PIC Name field:</h3>\n";
    echo "<ul>\n";
    foreach ($users as $user) {
        if (!empty($user['user_name'])) {
            echo "<li><strong>" . $user['user_name'] . "</strong>";
            if (!empty($user['user_first_name'])) {
                echo " (" . $user['user_first_name'] . " " . ($user['user_last_name'] ?? '') . ")";
            }
            echo " - Site: " . $user['site_id'] . "</li>\n";
        }
    }
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
