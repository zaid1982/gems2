<?php
// Mock apache_request_headers
if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        global $mock_headers;
        return $mock_headers ?? [];
    }
}

// Adjust include path to help scripts find src/
set_include_path(get_include_path() . PATH_SEPARATOR . realpath('api'));

require_once 'api/src/JWT.php';
require_once 'api/src/BeforeValidException.php';
require_once 'api/src/ExpiredException.php';
require_once 'api/src/SignatureInvalidException.php';

use Firebase\JWT\JWT;

function generate_token($userId) {
    $payload = ['user_id' => $userId, 'exp' => time() + 3600];
    try {
        return JWT::encode($payload, 'gems2');
    } catch (Throwable $e) {
        return JWT::encode($payload, 'gems2', 'HS256');
    }
}

// Try to get real DB credentials
$db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'gems';
if (file_exists('api/config.php')) {
    include 'api/config.php';
}

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $stmt = $pdo->query("SELECT user_id FROM sys_user WHERE active = 1 LIMIT 1");
    $activeUser = $stmt->fetchColumn() ?: 1;
} catch (Throwable $e) {
    $activeUser = 1;
}

$token = generate_token($activeUser);

$noti_cases = [
    '/noti_web/by_userid',
    '/noti_web/by_userId',
    '/api/noti_web.php/by_userId',
    '/noti_web/ext/by_userId'
];

echo "--- Testing api/noti_web.php ---\n";
foreach ($noti_cases as $uri) {
    echo "URI: $uri\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $uri;
    $mock_headers = ['Authorization' => 'Bearer ' . $token];
    
    ob_start();
    try {
        include 'api/noti_web.php';
    } catch (Throwable $e) {
        echo "Fatal/Error: " . $e->getMessage() . "\n";
    }
    $output = ob_get_clean();
    // Strip error_log warnings for clarity if possible, but let's keep it simple
    echo "Output: " . substr(trim($output), 0, 1000) . "\n\n";
}

echo "--- Testing api/ptw.php ---\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/ptw.php';
$mock_headers = [];

ob_start();
try {
    include 'api/ptw.php';
} catch (Throwable $e) {
    echo "Fatal/Error: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();
echo "Output: " . substr(trim($output), 0, 1000) . "\n\n";

if ($pdo) {
    echo "--- Schema Checks ---\n";
    $tables = ['noti_web', 'ptw_permit', 'ptw_worker', 'ptw_document', 'ptw_status_history', 'ptw_approval_log', 'ptw_number_sequence', 'sys_site', 'vw_roles', 'sys_user'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        echo "Table/View $table: " . ($stmt->fetch() ? "Exists" : "Missing") . "\n";
    }
    $cols = [
        'noti_web' => ['noti_web_timestamp', 'user_id'],
        'ptw_permit' => ['site_id', 'ptw_status', 'created_date']
    ];
    foreach ($cols as $table => $columns) {
        foreach ($columns as $col) {
            try {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                $stmt->execute([$col]);
                echo "Column $table.$col: " . ($stmt->fetch() ? "Exists" : "Missing") . "\n";
            } catch (Throwable $e) {
                echo "Column $table.$col: Error\n";
            }
        }
    }
}
