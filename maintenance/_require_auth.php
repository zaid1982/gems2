<?php

/**
 * Maintenance API key guard (legacy stack).
 *
 * Configure in api/library/config.ini:
 *   [maintenance]
 *   api_key = your-secret-here
 */
if (PHP_SAPI === 'cli') {
    return;
}

if (!function_exists('maintenance_deny')) {
    function maintenance_deny(int $status, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($accept, 'text/html') && !str_contains($accept, 'application/json')) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<!DOCTYPE html><html><head><title>Maintenance access denied</title></head><body>';
                echo '<h1>Access denied</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<p><a href="dashboard.html">Return to maintenance dashboard</a></p></body></html>';
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $message]);
            }
        }
        exit;
    }
}

$configPath = dirname(__DIR__) . '/api/library/config.ini';
$config = is_file($configPath) ? @parse_ini_file($configPath, true, INI_SCANNER_RAW) : false;
$expected = '';
if (is_array($config) && isset($config['maintenance']['api_key'])) {
    $expected = trim((string) $config['maintenance']['api_key']);
}

if ($expected === '') {
    maintenance_deny(
        503,
        'Maintenance tools are locked. Set [maintenance] api_key in api/library/config.ini.'
    );
}

$provided = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($provided === '' && isset($_GET['api_key']) && is_string($_GET['api_key'])) {
    $provided = trim($_GET['api_key']);
}
if ($provided === '' && isset($_POST['api_key']) && is_string($_POST['api_key'])) {
    $provided = trim($_POST['api_key']);
}

if ($provided === '' || !hash_equals($expected, $provided)) {
    maintenance_deny(401, 'Invalid or missing X-Api-Key.');
}
