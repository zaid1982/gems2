<?php
// LAN Git Maintenance Tool (guarded)
// - Repo path is fixed
// - IP allowlist + enable flag + password login
// - Command allowlist only (no arbitrary shell)

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

$ROOT = realpath(__DIR__ . '/..');
if ($ROOT === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'result' => null, 'error' => 1, 'errmsg' => 'Invalid server root']);
    exit;
}

$REPO_PATH = $ROOT; // pinned
$ENABLE_FLAG = __DIR__ . '/.allow_git_tool';
$ALLOW_IPS_FILE = __DIR__ . '/.git_tool_allow_ips';
$SECRET_FILE = __DIR__ . '/.git_tool_secret';
$LOG_FILE = __DIR__ . '/git_tool.log';

function respond(bool $success, $result = null, int $error = 0, string $errmsg = ''): void {
    echo json_encode(['success' => $success, 'result' => $result, 'error' => $error, 'errmsg' => $errmsg]);
    exit;
}

function clientIp(): string {
    // Do NOT trust X-Forwarded-For by default.
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function isPrivateOrLoopbackIp(string $ip): bool {
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $long = ip2long($ip);
        if ($long === false) return false;
        // 10.0.0.0/8
        if (($long & 0xFF000000) === 0x0A000000) return true;
        // 172.16.0.0/12
        if (($long & 0xFFF00000) === 0xAC100000) return true;
        // 192.168.0.0/16
        if (($long & 0xFFFF0000) === 0xC0A80000) return true;
    }
    return false;
}

function ipInCidr(string $ip, string $cidr): bool {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $parts = explode('/', $cidr, 2);
    if (count($parts) !== 2) return false;
    [$subnet, $maskBits] = $parts;
    if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $maskBits = (int)$maskBits;
    if ($maskBits < 0 || $maskBits > 32) return false;

    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false) return false;

    $mask = $maskBits === 0 ? 0 : (~0 << (32 - $maskBits));
    return (($ipLong & $mask) === ($subnetLong & $mask));
}

function isIpAllowed(string $ip, string $allowFile): bool {
    if (!file_exists($allowFile)) {
        // If no allowlist file is present, default-deny for LAN usage.
        return false;
    }
    $lines = file($allowFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return false;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '/') !== false) {
            if (ipInCidr($ip, $line)) return true;
        } else {
            if ($ip === $line) return true;
        }
    }
    return false;
}

function auditLog(string $logFile, string $msg): void {
    $ts = date('Y-m-d H:i:s');
    $ip = clientIp();
    @file_put_contents($logFile, "[$ts][$ip] $msg\n", FILE_APPEND);
}

function getSecret(string $secretFile): ?string {
    if (!file_exists($secretFile)) return null;
    $raw = trim((string)@file_get_contents($secretFile));
    if ($raw === '') return null;
    return $raw;
}

function requireEnabled(string $enableFlag): void {
    if (!file_exists($enableFlag)) {
        respond(false, null, 1, 'Git tool disabled. Create maintenance/.allow_git_tool to enable.');
    }
}

function requireLanAccess(string $allowIpsFile): void {
    $ip = clientIp();
    if ($ip === '') {
        respond(false, null, 1, 'Unable to determine client IP');
    }
    if (!isPrivateOrLoopbackIp($ip)) {
        respond(false, null, 1, 'Access denied (not a private/LAN address)');
    }
    if (!isIpAllowed($ip, $allowIpsFile)) {
        respond(false, null, 1, 'Access denied (IP not in allowlist)');
    }
}

function requireAuth(string $secretFile): void {
    $secret = getSecret($secretFile);
    if ($secret === null) {
        respond(false, null, 1, 'Git tool not configured. Create maintenance/.git_tool_secret with a strong password.');
    }
    if (!empty($_SESSION['git_tool_authed'])) {
        return;
    }
    respond(false, null, 1, 'Not authenticated');
}

function ensureCsrf(): string {
    if (empty($_SESSION['git_tool_csrf'])) {
        $_SESSION['git_tool_csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['git_tool_csrf'];
}

function checkCsrf(string $token): void {
    $expected = (string)($_SESSION['git_tool_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $token)) {
        respond(false, null, 1, 'Invalid CSRF token');
    }
}

function runGit(string $repoPath, array $args, int $timeoutSec = 20): array {
    // Use proc_open with array command to avoid shell expansion.
    $cmd = array_merge(['git'], $args);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proc = @proc_open($cmd, $descriptors, $pipes, $repoPath, ['GIT_TERMINAL_PROMPT' => '0']);
    if (!is_resource($proc)) {
        return ['code' => 1, 'stdout' => '', 'stderr' => 'Failed to start git process'];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], true);
    stream_set_blocking($pipes[2], true);

    $start = time();
    $stdout = '';
    $stderr = '';

    while (true) {
        $status = proc_get_status($proc);
        if (!$status['running']) {
            break;
        }
        if ((time() - $start) > $timeoutSec) {
            @proc_terminate($proc);
            $stderr .= "\nTimed out";
            break;
        }
        usleep(50_000);
    }

    $stdout .= (string)stream_get_contents($pipes[1]);
    $stderr .= (string)stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $code = proc_close($proc);

    // Cap output to avoid huge responses
    $max = 60_000;
    if (strlen($stdout) > $max) $stdout = substr($stdout, 0, $max) . "\n...<truncated>";
    if (strlen($stderr) > $max) $stderr = substr($stderr, 0, $max) . "\n...<truncated>";

    return ['code' => (int)$code, 'stdout' => $stdout, 'stderr' => $stderr];
}

function validateBranchName(string $branch): bool {
    // Conservative branch validation: allow letters/numbers/._/- only
    // Disallow leading '-', '..', '//' and '@{' patterns.
    if ($branch === '') return false;
    if ($branch[0] === '-') return false;
    if (str_contains($branch, '..') || str_contains($branch, '//') || str_contains($branch, '@{')) return false;
    return (bool)preg_match('/^[A-Za-z0-9._\/-]+$/', $branch);
}

requireEnabled($ENABLE_FLAG);
requireLanAccess($ALLOW_IPS_FILE);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'ping') {
    $csrf = ensureCsrf();
    respond(true, ['csrf' => $csrf, 'authed' => !empty($_SESSION['git_tool_authed'])], 0, 'OK');
}

if ($action === 'login') {
    $secret = getSecret($SECRET_FILE);
    if ($secret === null) {
        respond(false, null, 1, 'Missing maintenance/.git_tool_secret');
    }
    $password = (string)($_POST['password'] ?? '');
    if (!hash_equals($secret, $password)) {
        auditLog($LOG_FILE, 'LOGIN_FAILED');
        respond(false, null, 1, 'Invalid password');
    }
    $_SESSION['git_tool_authed'] = true;
    $csrf = ensureCsrf();
    auditLog($LOG_FILE, 'LOGIN_OK');
    respond(true, ['csrf' => $csrf], 0, 'Authenticated');
}

if ($action === 'logout') {
    $_SESSION['git_tool_authed'] = false;
    $_SESSION['git_tool_csrf'] = null;
    auditLog($LOG_FILE, 'LOGOUT');
    respond(true, null, 0, 'Logged out');
}

// All actions below require auth + CSRF
requireAuth($SECRET_FILE);
$csrf = (string)($_POST['csrf'] ?? '');
checkCsrf($csrf);

// Ensure repo looks like a git repo
if (!is_dir($REPO_PATH . '/.git')) {
    respond(false, null, 1, 'Repo is not a git repository (.git not found)');
}

switch ($action) {
    case 'status': {
        $r = runGit($REPO_PATH, ['status', '--porcelain=v1', '--branch']);
        auditLog($LOG_FILE, 'STATUS');
        respond(true, $r, $r['code'] === 0 ? 0 : 1, $r['code'] === 0 ? 'OK' : 'Git status failed');
    }

    case 'branches': {
        // List local branches first, then remote branches
        $local = runGit($REPO_PATH, ['for-each-ref', '--format=%(refname:short)', 'refs/heads']);
        $remote = runGit($REPO_PATH, ['for-each-ref', '--format=%(refname:short)', 'refs/remotes']);
        $current = runGit($REPO_PATH, ['rev-parse', '--abbrev-ref', 'HEAD']);
        auditLog($LOG_FILE, 'BRANCHES');
        respond(true, [
            'current' => trim($current['stdout']),
            'local' => array_values(array_filter(array_map('trim', explode("\n", $local['stdout'])))),
            'remote' => array_values(array_filter(array_map('trim', explode("\n", $remote['stdout'])))),
        ], 0, 'OK');
    }

    case 'log': {
        $n = (int)($_POST['n'] ?? 20);
        if ($n < 1) $n = 1;
        if ($n > 50) $n = 50;
        $r = runGit($REPO_PATH, ['log', '-n', (string)$n, '--oneline', '--decorate']);
        auditLog($LOG_FILE, 'LOG');
        respond(true, $r, $r['code'] === 0 ? 0 : 1, $r['code'] === 0 ? 'OK' : 'Git log failed');
    }

    case 'fetch': {
        $r = runGit($REPO_PATH, ['fetch', '--all', '--prune'], 60);
        auditLog($LOG_FILE, 'FETCH');
        respond(true, $r, $r['code'] === 0 ? 0 : 1, $r['code'] === 0 ? 'OK' : 'Git fetch failed');
    }

    case 'pull': {
        // Safe default: fast-forward only
        $r = runGit($REPO_PATH, ['pull', '--ff-only'], 120);
        auditLog($LOG_FILE, 'PULL');
        respond(true, $r, $r['code'] === 0 ? 0 : 1, $r['code'] === 0 ? 'OK' : 'Git pull failed');
    }

    case 'checkout': {
        $branch = (string)($_POST['branch'] ?? '');
        if (!validateBranchName($branch)) {
            respond(false, null, 1, 'Invalid branch name');
        }
        // Only allow checkout of existing local branch.
        $exists = runGit($REPO_PATH, ['show-ref', '--verify', '--quiet', 'refs/heads/' . $branch]);
        if ($exists['code'] !== 0) {
            respond(false, null, 1, 'Branch does not exist locally. Create it on server first (or add a safe create flow).');
        }
        $r = runGit($REPO_PATH, ['checkout', $branch], 60);
        auditLog($LOG_FILE, 'CHECKOUT ' . $branch);
        respond(true, $r, $r['code'] === 0 ? 0 : 1, $r['code'] === 0 ? 'OK' : 'Git checkout failed');
    }

    default:
        respond(false, null, 1, 'Unknown action');
}
