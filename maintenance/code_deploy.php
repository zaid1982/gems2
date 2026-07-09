<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

const CODE_DEPLOY_MAX_READ_BYTES = 2_097_152;   // 2 MB
const CODE_DEPLOY_MAX_WRITE_BYTES = 5_242_880;  // 5 MB

/** @var list<string> */
const CODE_DEPLOY_BLOCKED_DIRS = [
    '.git',
    'vendor',
    'upload',
    'node_modules',
    'maintenance/logs',
];

/** @var list<string> */
const CODE_DEPLOY_BLOCKED_FILES = [
    'api/library/config.ini',
    '.env',
    'maintenance/.git_tool_secret',
    'maintenance/.git_tool_allow_ips',
    'maintenance/.allow_git_tool',
];

/** @var list<string> */
const CODE_DEPLOY_ALLOWED_EXTENSIONS = [
    'php', 'js', 'html', 'htm', 'css', 'sql', 'json', 'md', 'txt', 'xml',
    'yml', 'yaml', 'neon', 'htaccess', 'svg', 'scss', 'less', 'map',
    'dist', 'example', 'bat', 'sh',
];

function codeDeployRoot(): string
{
    $root = realpath(dirname(__DIR__));
    if ($root === false) {
        throw new RuntimeException('Unable to resolve project root');
    }

    return $root;
}

function codeDeployNormalizeRelative(string $path): string
{
    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');

    if ($path === '' || $path === '.') {
        return '';
    }

    $parts = [];
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            throw new InvalidArgumentException('Path traversal is not allowed');
        }
        $parts[] = $segment;
    }

    return implode('/', $parts);
}

function codeDeployIsBlockedRelative(string $relativePath, bool $isDir = false): bool
{
    $relativePath = codeDeployNormalizeRelative($relativePath);
    $normalized = $relativePath === '' ? '' : $relativePath . ($isDir ? '/' : '');

    foreach (CODE_DEPLOY_BLOCKED_DIRS as $blocked) {
        $blockedPrefix = rtrim(str_replace('\\', '/', $blocked), '/') . '/';
        if ($relativePath === $blocked || str_starts_with($normalized, $blockedPrefix)) {
            return true;
        }
    }

    if (!$isDir) {
        foreach (CODE_DEPLOY_BLOCKED_FILES as $blockedFile) {
            if ($relativePath === $blockedFile) {
                return true;
            }
        }
        if (str_ends_with(strtolower($relativePath), '.ini') && !str_ends_with(strtolower($relativePath), '.example')) {
            return true;
        }
        if (str_ends_with(strtolower($relativePath), '.pem')
            || str_ends_with(strtolower($relativePath), '.der')
            || str_ends_with(strtolower($relativePath), '.key')) {
            return true;
        }
    }

    return false;
}

function codeDeployResolveRelative(string $relativePath, bool $mustExist = false): array
{
    $relativePath = codeDeployNormalizeRelative($relativePath);
    if (codeDeployIsBlockedRelative($relativePath, false)) {
        throw new InvalidArgumentException('Access to this path is blocked');
    }

    $root = codeDeployRoot();
    $absolute = $relativePath === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $real = realpath($absolute);

    if ($real === false) {
        if ($mustExist) {
            throw new InvalidArgumentException('Path not found');
        }
        $parent = dirname($absolute);
        $parentReal = realpath($parent);
        if ($parentReal === false || !str_starts_with($parentReal, $root)) {
            throw new InvalidArgumentException('Invalid path');
        }
        if (codeDeployIsBlockedRelative($relativePath, is_dir($absolute) || str_ends_with($relativePath, '/'))) {
            throw new InvalidArgumentException('Access to this path is blocked');
        }

        return [
            'relative' => $relativePath,
            'absolute' => $absolute,
            'exists' => false,
        ];
    }

    if (!str_starts_with($real, $root)) {
        throw new InvalidArgumentException('Path escapes project root');
    }

    $relativeFromRoot = ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
    if (codeDeployIsBlockedRelative($relativeFromRoot, is_dir($real))) {
        throw new InvalidArgumentException('Access to this path is blocked');
    }

    return [
        'relative' => $relativeFromRoot,
        'absolute' => $real,
        'exists' => true,
    ];
}

function codeDeployAllowedExtension(string $filename): bool
{
    $base = strtolower(basename($filename));
    if ($base === '.htaccess' || $base === '.env.example') {
        return true;
    }

    $pos = strrpos($base, '.');
    if ($pos === false) {
        return false;
    }

    $ext = substr($base, $pos + 1);
    if (in_array($ext, CODE_DEPLOY_ALLOWED_EXTENSIONS, true)) {
        return true;
    }

    return str_contains($base, '.example.') || str_ends_with($base, '.dist.php');
}

function codeDeployWebUser(): string
{
    if (function_exists('posix_geteuid')) {
        $info = posix_getpwuid(posix_geteuid());
        if (is_array($info) && !empty($info['name'])) {
            return (string) $info['name'];
        }
    }

    return get_current_user() ?: 'unknown';
}

function codeDeployPathOwner(string $absolutePath): string
{
    if (!function_exists('posix_getpwuid')) {
        return 'unknown';
    }
    $stat = @stat($absolutePath);
    if ($stat === false) {
        return 'unknown';
    }
    $ownerInfo = posix_getpwuid($stat['uid']);
    if (is_array($ownerInfo) && !empty($ownerInfo['name'])) {
        return (string) $ownerInfo['name'];
    }

    return (string) $stat['uid'];
}

function codeDeployPermissionMessage(string $absolutePath, string $relativePath): string
{
    $webUser = codeDeployWebUser();
    $owner = codeDeployPathOwner($absolutePath);
    $dir = dirname($absolutePath);
    $isDirCheck = is_dir($absolutePath);

    $hint = match (PHP_OS_FAMILY) {
        'Darwin' => 'On XAMPP/macOS run once: maintenance/setup_code_deploy_permissions.sh',
        'Windows' => 'On Windows run once as Administrator: maintenance\\setup_code_deploy_permissions.bat (XAMPP) or .bat iis (IIS)',
        default => 'On Linux run once: sudo maintenance/setup_code_deploy_permissions.sh (grants www-data write access)',
    };

    return sprintf(
        'Cannot write %s. Web server user: %s. Owner: %s. %s is not writable. %s',
        $relativePath,
        $webUser,
        $owner,
        $isDirCheck ? 'Directory' : 'File/directory',
        $hint
    );
}

function codeDeployAssertWritable(string $absolutePath, string $relativePath): void
{
    $dir = is_dir($absolutePath) ? $absolutePath : dirname($absolutePath);
    if (!is_writable($dir) || (is_file($absolutePath) && !is_writable($absolutePath))) {
        throw new RuntimeException(codeDeployPermissionMessage($absolutePath, $relativePath));
    }
}

function codeDeployRespond(bool $success, array $data = [], ?string $error = null): void
{
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s'),
    ] + $data);
    exit;
}

function codeDeployList(string $relativePath): void
{
    $resolved = codeDeployResolveRelative($relativePath, true);
    if (!is_dir($resolved['absolute'])) {
        throw new InvalidArgumentException('Not a directory');
    }

    $entries = [];
    $items = scandir($resolved['absolute']);
    if ($items === false) {
        throw new RuntimeException('Unable to read directory');
    }

    foreach ($items as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $childRelative = $resolved['relative'] === ''
            ? $name
            : $resolved['relative'] . '/' . $name;
        $childAbsolute = $resolved['absolute'] . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($childAbsolute);
        if (codeDeployIsBlockedRelative($childRelative, $isDir)) {
            continue;
        }
        $entries[] = [
            'name' => $name,
            'path' => $childRelative,
            'type' => $isDir ? 'dir' : 'file',
            'size' => $isDir ? null : filesize($childAbsolute),
            'modified' => date('Y-m-d H:i:s', filemtime($childAbsolute) ?: time()),
            'editable' => !$isDir && codeDeployAllowedExtension($name),
        ];
    }

    usort($entries, static function (array $a, array $b): int {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'dir' ? -1 : 1;
        }

        return strcasecmp($a['name'], $b['name']);
    });

    codeDeployRespond(true, [
        'path' => $resolved['relative'],
        'root' => basename(codeDeployRoot()),
        'entries' => $entries,
    ]);
}

function codeDeployRead(string $relativePath): void
{
    $resolved = codeDeployResolveRelative($relativePath, true);
    if (!is_file($resolved['absolute'])) {
        throw new InvalidArgumentException('Not a file');
    }
    if (!codeDeployAllowedExtension(basename($resolved['absolute']))) {
        throw new InvalidArgumentException('This file type cannot be opened in the editor');
    }

    $size = filesize($resolved['absolute']);
    if ($size === false || $size > CODE_DEPLOY_MAX_READ_BYTES) {
        throw new InvalidArgumentException('File is too large to open (max 2 MB). Use upload/download instead.');
    }

    $content = file_get_contents($resolved['absolute']);
    if ($content === false) {
        throw new RuntimeException('Unable to read file');
    }

    codeDeployRespond(true, [
        'path' => $resolved['relative'],
        'content' => $content,
        'size' => $size,
        'modified' => date('Y-m-d H:i:s', filemtime($resolved['absolute']) ?: time()),
    ]);
}

function codeDeployWrite(string $relativePath, string $content): void
{
    if (strlen($content) > CODE_DEPLOY_MAX_WRITE_BYTES) {
        throw new InvalidArgumentException('Content exceeds maximum size (5 MB)');
    }

    $resolved = codeDeployResolveRelative($relativePath, true);
    if (!is_file($resolved['absolute'])) {
        throw new InvalidArgumentException('File not found');
    }
    if (!codeDeployAllowedExtension(basename($resolved['absolute']))) {
        throw new InvalidArgumentException('This file type cannot be edited');
    }

    codeDeployAssertWritable($resolved['absolute'], $resolved['relative']);

    if (file_put_contents($resolved['absolute'], $content, LOCK_EX) === false) {
        throw new RuntimeException('Failed to save file');
    }

    codeDeployRespond(true, [
        'path' => $resolved['relative'],
        'size' => strlen($content),
        'modified' => date('Y-m-d H:i:s', filemtime($resolved['absolute']) ?: time()),
        'message' => 'File saved',
    ]);
}

function codeDeployUpload(string $relativePath): void
{
    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        throw new InvalidArgumentException('No file uploaded');
    }

    $upload = $_FILES['file'];
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload failed (code ' . ($upload['error'] ?? 0) . ')');
    }

    $tmp = $upload['tmp_name'] ?? '';
    $originalName = basename((string) ($upload['name'] ?? ''));
    if ($originalName === '' || !codeDeployAllowedExtension($originalName)) {
        throw new InvalidArgumentException('File type is not allowed');
    }

    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > CODE_DEPLOY_MAX_WRITE_BYTES) {
        throw new InvalidArgumentException('File exceeds maximum size (5 MB)');
    }

    $relativePath = codeDeployNormalizeRelative($relativePath);
    if ($relativePath === '') {
        throw new InvalidArgumentException('Target path is required');
    }

    if (codeDeployIsBlockedRelative($relativePath, false)) {
        throw new InvalidArgumentException('Access to this path is blocked');
    }

    if (!codeDeployAllowedExtension(basename($relativePath))) {
        throw new InvalidArgumentException('Target file type is not allowed');
    }

    if (strcasecmp(basename($relativePath), $originalName) !== 0) {
        throw new InvalidArgumentException('Uploaded filename must match the target file name');
    }

    $resolved = codeDeployResolveRelative($relativePath, false);
    $parent = dirname($resolved['absolute']);
    $parentReal = realpath($parent);
    $root = codeDeployRoot();
    if ($parentReal === false || !str_starts_with($parentReal, $root)) {
        throw new InvalidArgumentException('Invalid target directory');
    }
    if (codeDeployIsBlockedRelative(dirname($relativePath) === '.' ? '' : dirname(str_replace('\\', '/', $relativePath)), true)) {
        throw new InvalidArgumentException('Access to this directory is blocked');
    }

    if (!is_dir($parentReal) && !mkdir($parentReal, 0755, true) && !is_dir($parentReal)) {
        throw new RuntimeException('Unable to create target directory');
    }
    codeDeployAssertWritable(
        file_exists($resolved['absolute']) ? $resolved['absolute'] : $parentReal,
        $relativePath
    );

    if (!move_uploaded_file($tmp, $resolved['absolute'])) {
        throw new RuntimeException('Failed to store uploaded file');
    }

    codeDeployRespond(true, [
        'path' => $relativePath,
        'size' => $size,
        'modified' => date('Y-m-d H:i:s', filemtime($resolved['absolute']) ?: time()),
        'message' => 'File uploaded and replaced',
    ]);
}

function codeDeployDownload(string $relativePath): void
{
    $resolved = codeDeployResolveRelative($relativePath, true);
    if (!is_file($resolved['absolute'])) {
        throw new InvalidArgumentException('Not a file');
    }
    if (!codeDeployAllowedExtension(basename($resolved['absolute']))) {
        throw new InvalidArgumentException('This file type cannot be downloaded');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($resolved['absolute']) . '"');
    header('Content-Length: ' . (string) filesize($resolved['absolute']));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($resolved['absolute']);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'info':
            codeDeployRespond(true, [
                'root' => basename(codeDeployRoot()),
                'web_user' => codeDeployWebUser(),
                'blocked_dirs' => CODE_DEPLOY_BLOCKED_DIRS,
                'max_read_mb' => CODE_DEPLOY_MAX_READ_BYTES / 1048576,
                'max_write_mb' => CODE_DEPLOY_MAX_WRITE_BYTES / 1048576,
                'permissions_hint' => match (PHP_OS_FAMILY) {
                    'Darwin' => 'Run maintenance/setup_code_deploy_permissions.sh once so Apache (daemon) can save files.',
                    'Windows' => 'Run maintenance\\setup_code_deploy_permissions.bat as Administrator (use .bat iis for IIS).',
                    default => 'Run sudo maintenance/setup_code_deploy_permissions.sh once so www-data can save files.',
                },
            ]);
            break;

        case 'list':
            codeDeployList((string) ($_GET['path'] ?? ''));
            break;

        case 'read':
            codeDeployRead((string) ($_GET['path'] ?? ''));
            break;

        case 'write':
            $path = (string) ($_POST['path'] ?? '');
            $content = (string) ($_POST['content'] ?? '');
            codeDeployWrite($path, $content);
            break;

        case 'upload':
            codeDeployUpload((string) ($_POST['path'] ?? ''));
            break;

        case 'download':
            codeDeployDownload((string) ($_GET['path'] ?? ''));
            break;

        default:
            throw new InvalidArgumentException('Unknown action');
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
    }
    codeDeployRespond(false, [], $e->getMessage());
}
