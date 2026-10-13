<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

$action = $_REQUEST['action'] ?? '';

try {
    $pdo = diagDb();
    switch ($action) {
        case 'search':
            diagJson(diagSearch($pdo, (string) ($_GET['q'] ?? '')));
            break;
        case 'detail':
            diagJson(diagDetail($pdo, (int) ($_GET['userId'] ?? 0)));
            break;
        case 'request':
            diagJson(diagRequest($pdo, (int) ($_POST['userId'] ?? $_GET['userId'] ?? 0)));
            break;
        default:
            throw new InvalidArgumentException('Invalid action. Use: search, detail, request');
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
    }
    $message = $e->getMessage();
    if (str_contains($message, 'mob_diag_')) {
        $message = 'Diagnostic tables are missing. Run sql/2026-10-13_mobile_diag_log.sql';
    }
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

function diagDb(): PDO
{
    $configPath = dirname(__DIR__) . '/api/library/config.ini';
    $config = is_file($configPath) ? parse_ini_file($configPath) : false;
    if (!is_array($config)) {
        throw new RuntimeException('Database config is missing');
    }
    $host = (string) ($config['dbhost'] ?? '');
    $name = (string) ($config['dbname'] ?? '');
    $user = (string) ($config['username'] ?? '');
    $password = (string) ($config['password'] ?? '');
    if ($host === '' || $name === '' || $user === '') {
        throw new RuntimeException('Database config is incomplete');
    }

    return new PDO(
        "mysql:host={$host};port=3306;dbname={$name};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function diagJson(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')] + $payload);
    exit;
}

function diagSearch(PDO $pdo, string $q): array
{
    $q = trim($q);
    if (strlen($q) < 2) {
        throw new InvalidArgumentException('Enter at least 2 characters of the username or name');
    }
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare(
        'SELECT user_id, user_name, user_first_name, user_last_name
         FROM sys_user
         WHERE user_name LIKE ? OR user_first_name LIKE ? OR user_last_name LIKE ?
         ORDER BY user_name
         LIMIT 25'
    );
    $stmt->execute([$like, $like, $like]);
    $users = [];
    foreach ($stmt->fetchAll() as $row) {
        $users[] = diagUser($row);
    }
    return ['users' => $users];
}

function diagDetail(PDO $pdo, int $userId): array
{
    $user = diagUserRow($pdo, $userId);
    $pending = $pdo->prepare(
        'SELECT diag_request_id, requested_at, status
         FROM mob_diag_request
         WHERE user_id = ? AND status = ?
         ORDER BY diag_request_id DESC
         LIMIT 1'
    );
    $pending->execute([$userId, 'pending']);
    $request = $pending->fetch();

    $logStmt = $pdo->prepare(
        'SELECT diag_log_id, received_at, app_version, online_flag, log_text
         FROM mob_diag_log
         WHERE user_id = ?
         ORDER BY diag_log_id DESC
         LIMIT 1'
    );
    $logStmt->execute([$userId]);
    $log = $logStmt->fetch();

    return [
        'user' => $user,
        'devices' => diagDevices($pdo, $userId),
        'request' => is_array($request) ? [
            'id' => (int) $request['diag_request_id'],
            'requestedAt' => $request['requested_at'],
            'status' => 'pending',
        ] : null,
        'log' => is_array($log) ? [
            'id' => (int) $log['diag_log_id'],
            'receivedAt' => $log['received_at'],
            'appVersion' => $log['app_version'],
            'online' => (int) $log['online_flag'] === 1,
            'text' => (string) $log['log_text'],
        ] : null,
    ];
}

function diagRequest(PDO $pdo, int $userId): array
{
    diagUserRow($pdo, $userId);
    $deviceKey = trim((string) ($_POST['deviceKey'] ?? $_GET['deviceKey'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9._:-]{8,64}$/', $deviceKey)) {
        throw new InvalidArgumentException('Choose a device');
    }
    $owns = $pdo->prepare(
        'SELECT device_label FROM mob_user_device
         WHERE user_id = ? AND device_key = ? AND status = ? LIMIT 1'
    );
    $owns->execute([$userId, $deviceKey, 'active']);
    if (!is_array($owns->fetch())) {
        throw new InvalidArgumentException('That device is not signed in');
    }

    $existing = $pdo->prepare(
        'SELECT diag_request_id, requested_at
         FROM mob_diag_request
         WHERE user_id = ? AND device_key = ? AND status = ?
         LIMIT 1'
    );
    $existing->execute([$userId, $deviceKey, 'pending']);
    $row = $existing->fetch();
    if (is_array($row)) {
        return diagDetail($pdo, $userId) + ['created' => false];
    }

    $insert = $pdo->prepare(
        'INSERT INTO mob_diag_request (user_id, device_key, status) VALUES (?, ?, ?)'
    );
    $insert->execute([$userId, $deviceKey, 'pending']);

    return diagDetail($pdo, $userId) + ['created' => true];
}

function diagDevices(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT d.device_key, d.platform, d.device_label, d.app_version, d.last_seen_at,
                r.requested_at
         FROM mob_user_device d
         LEFT JOIN mob_diag_request r
           ON r.user_id = d.user_id AND r.device_key = d.device_key AND r.status = ?
         WHERE d.user_id = ? AND d.status = ?
         ORDER BY d.last_seen_at DESC'
    );
    $stmt->execute(['pending', $userId, 'active']);
    $devices = [];
    foreach ($stmt->fetchAll() as $row) {
        $devices[] = [
            'deviceKey' => (string) $row['device_key'],
            'platform' => (string) $row['platform'],
            'label' => (string) $row['device_label'],
            'appVersion' => $row['app_version'],
            'lastSeenAt' => $row['last_seen_at'],
            'pending' => $row['requested_at'] !== null,
            'requestedAt' => $row['requested_at'],
        ];
    }
    return $devices;
}

function diagUserRow(PDO $pdo, int $userId): array
{
    if ($userId <= 0) {
        throw new InvalidArgumentException('Choose a user');
    }
    $stmt = $pdo->prepare(
        'SELECT user_id, user_name, user_first_name, user_last_name
         FROM sys_user WHERE user_id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        throw new InvalidArgumentException('User not found');
    }
    return diagUser($row);
}

function diagUser(array $row): array
{
    $first = trim((string) ($row['user_first_name'] ?? ''));
    $last = trim((string) ($row['user_last_name'] ?? ''));
    return [
        'userId' => (int) $row['user_id'],
        'username' => (string) ($row['user_name'] ?? ''),
        'name' => trim($first . ' ' . $last),
    ];
}
