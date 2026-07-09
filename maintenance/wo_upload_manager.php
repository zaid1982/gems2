<?php

declare(strict_types=1);

require_once __DIR__ . '/_require_auth.php';

require_once __DIR__ . '/../api/class/Constant.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

const WO_UPLOAD_MAX_BYTES = 10_485_760; // 10 MB
const WO_UPLOAD_ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$action = $_REQUEST['action'] ?? '';

try {
    $pdo = woUploadDb();
    switch ($action) {
        case 'search':
            woUploadJson(woUploadSearch($pdo));
            break;
        case 'list':
            woUploadJson(woUploadList($pdo, (int) ($_GET['woTaskId'] ?? 0)));
            break;
        case 'preview':
            woUploadPreview($pdo, (int) ($_GET['uploadId'] ?? 0));
            break;
        case 'replace':
            woUploadJson(woUploadReplace($pdo));
            break;
        case 'soft_delete':
            woUploadJson(woUploadSoftDelete($pdo));
            break;
        default:
            throw new InvalidArgumentException('Invalid action. Use: search, list, preview, replace, soft_delete');
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

function woUploadDb(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        Constant::$dbHost,
        Constant::$dbName
    );

    return new PDO($dsn, Constant::$dbUserName, Constant::$dbUserPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function woUploadJson(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')] + $payload);
    exit;
}

function woUploadTypeLabel(int $type): string
{
    return match ($type) {
        1 => 'Complaint',
        2 => 'Before repair',
        3 => 'During repair',
        4 => 'After repair',
        5 => 'Signature (complainer)',
        6 => 'Signature (responder)',
        7 => 'Signature (executor)',
        8 => 'Signature (verified)',
        9 => 'Signature (WR checked)',
        10 => 'Signature (WR verified)',
        11 => 'Response image',
        12 => 'Signature (check)',
        default => 'Type ' . $type,
    };
}

function woUploadProjectRoot(): string
{
    $root = realpath(dirname(__DIR__));
    if ($root === false) {
        throw new RuntimeException('Unable to resolve project root');
    }

    return $root;
}

function woUploadResolveFilePath(string $folder, string $filename, string $extension): string
{
    $relative = trim(str_replace('\\', '/', $folder), '/') . '/' . $filename . '.' . $extension;
    $root = woUploadProjectRoot();
    $candidates = [
        $root . '/' . $relative,
        $root . '/api/' . $relative,
    ];

    foreach ($candidates as $absolute) {
        if (is_file($absolute)) {
            return $absolute;
        }
    }

    return $candidates[0];
}

function woUploadTargetPath(string $folder, string $filename, string $extension): string
{
    $relative = trim(str_replace('\\', '/', $folder), '/') . '/' . $filename . '.' . $extension;
    $root = woUploadProjectRoot();
    $preferred = $root . '/' . $relative;
    if (is_file($preferred) || is_dir(dirname($preferred))) {
        return $preferred;
    }

    return $root . '/api/' . $relative;
}

function woUploadFetchUpload(PDO $pdo, int $uploadId): array
{
    $stmt = $pdo->prepare(
        'SELECT su.*, wtu.wo_task_upload_id, wtu.wo_task_upload_type, wtu.wo_task_id, wtu.wo_task_upload_desc,
                wt.wo_task_no, rd.document_desc
         FROM sys_upload su
         INNER JOIN wo_task_upload wtu ON wtu.upload_id = su.upload_id
         INNER JOIN wo_task wt ON wt.wo_task_id = wtu.wo_task_id
         LEFT JOIN ref_document rd ON rd.document_id = su.document_id
         WHERE su.upload_id = ?'
    );
    $stmt->execute([$uploadId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new InvalidArgumentException('Upload not found or not linked to a work order');
    }

    return $row;
}

function woUploadSearch(PDO $pdo): array
{
    $query = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
    if ($query === '') {
        throw new InvalidArgumentException('Enter a work order number or wo_task_id');
    }

    if (ctype_digit($query)) {
        $stmt = $pdo->prepare(
            'SELECT wo_task_id, wo_task_no, wo_task_location, wo_task_complaint, wo_task_type, site_id
             FROM wo_task
             WHERE wo_task_id = ? OR wo_task_no LIKE ?
             ORDER BY wo_task_id DESC
             LIMIT 20'
        );
        $stmt->execute([(int) $query, '%' . $query . '%']);
    } else {
        $stmt = $pdo->prepare(
            'SELECT wo_task_id, wo_task_no, wo_task_location, wo_task_complaint, wo_task_type, site_id
             FROM wo_task
             WHERE wo_task_no LIKE ?
             ORDER BY wo_task_id DESC
             LIMIT 20'
        );
        $stmt->execute(['%' . $query . '%']);
    }

    return ['results' => $stmt->fetchAll()];
}

function woUploadList(PDO $pdo, int $woTaskId): array
{
    if ($woTaskId <= 0) {
        throw new InvalidArgumentException('woTaskId is required');
    }

    $taskStmt = $pdo->prepare(
        'SELECT wo_task_id, wo_task_no, wo_task_location, wo_task_complaint, wo_task_type, site_id
         FROM wo_task WHERE wo_task_id = ?'
    );
    $taskStmt->execute([$woTaskId]);
    $task = $taskStmt->fetch();
    if (!$task) {
        throw new InvalidArgumentException('Work order not found');
    }

    $stmt = $pdo->prepare(
        'SELECT wtu.wo_task_upload_id, wtu.wo_task_upload_type, wtu.wo_task_upload_desc,
                wtu.wo_task_upload_timestamp, wtu.wo_task_upload_longitude, wtu.wo_task_upload_latitude,
                su.upload_id, su.upload_folder, su.upload_filename, su.upload_extension, su.upload_uplname,
                su.upload_name, su.upload_status, su.document_id, rd.document_desc
         FROM wo_task_upload wtu
         INNER JOIN sys_upload su ON su.upload_id = wtu.upload_id
         LEFT JOIN ref_document rd ON rd.document_id = su.document_id
         WHERE wtu.wo_task_id = ?
         ORDER BY wtu.wo_task_upload_type ASC, wtu.wo_task_upload_timestamp ASC'
    );
    $stmt->execute([$woTaskId]);

    $images = [];
    foreach ($stmt->fetchAll() as $row) {
        $absolute = woUploadResolveFilePath(
            (string) $row['upload_folder'],
            (string) $row['upload_filename'],
            (string) $row['upload_extension']
        );
        $images[] = [
            'woTaskUploadId' => (int) $row['wo_task_upload_id'],
            'uploadId' => (int) $row['upload_id'],
            'uploadType' => (int) $row['wo_task_upload_type'],
            'uploadTypeLabel' => woUploadTypeLabel((int) $row['wo_task_upload_type']),
            'description' => $row['wo_task_upload_desc'],
            'timestamp' => $row['wo_task_upload_timestamp'],
            'longitude' => $row['wo_task_upload_longitude'],
            'latitude' => $row['wo_task_upload_latitude'],
            'originalFilename' => $row['upload_uplname'],
            'uploadName' => $row['upload_name'],
            'documentDesc' => $row['document_desc'],
            'documentId' => (int) $row['document_id'],
            'uploadStatus' => (string) $row['upload_status'],
            'relativePath' => trim(str_replace('\\', '/', $row['upload_folder']), '/')
                . '/' . $row['upload_filename'] . '.' . $row['upload_extension'],
            'fileExists' => is_file($absolute),
            'fileSize' => is_file($absolute) ? filesize($absolute) : null,
            'previewUrl' => 'wo_upload_manager.php?action=preview&uploadId=' . (int) $row['upload_id'],
        ];
    }

    return [
        'task' => $task,
        'images' => $images,
        'imageCount' => count($images),
    ];
}

function woUploadPreview(PDO $pdo, int $uploadId): void
{
    if ($uploadId <= 0) {
        throw new InvalidArgumentException('uploadId is required');
    }

    $row = woUploadFetchUpload($pdo, $uploadId);
    $path = woUploadResolveFilePath(
        (string) $row['upload_folder'],
        (string) $row['upload_filename'],
        (string) $row['upload_extension']
    );

    if (!is_file($path)) {
        throw new InvalidArgumentException('Image file missing on disk: ' . $row['upload_folder']);
    }

    $mime = match (strtolower((string) $row['upload_extension'])) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };

    header('Content-Type: ' . $mime);
    header('Cache-Control: no-cache, must-revalidate');
    readfile($path);
    exit;
}

function woUploadValidateImageUpload(array $upload): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload failed (code ' . ($upload['error'] ?? 0) . ')');
    }

    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > WO_UPLOAD_MAX_BYTES) {
        throw new InvalidArgumentException('Image must be 10 MB or less');
    }

    $original = basename((string) ($upload['name'] ?? ''));
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, WO_UPLOAD_ALLOWED_EXT, true)) {
        throw new InvalidArgumentException('Allowed image types: ' . implode(', ', WO_UPLOAD_ALLOWED_EXT));
    }

    $tmp = (string) ($upload['tmp_name'] ?? '');
    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new InvalidArgumentException('Uploaded file is not a valid image');
    }

    return $ext;
}

function woUploadReplace(PDO $pdo): array
{
    $uploadId = (int) ($_POST['uploadId'] ?? 0);
    if ($uploadId <= 0) {
        throw new InvalidArgumentException('uploadId is required');
    }
    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        throw new InvalidArgumentException('No image file uploaded');
    }

    $row = woUploadFetchUpload($pdo, $uploadId);
    $newExt = woUploadValidateImageUpload($_FILES['file']);

    $oldPath = woUploadResolveFilePath(
        (string) $row['upload_folder'],
        (string) $row['upload_filename'],
        (string) $row['upload_extension']
    );
    $oldExt = strtolower((string) $row['upload_extension']);
    $newPath = woUploadTargetPath(
        (string) $row['upload_folder'],
        (string) $row['upload_filename'],
        $newExt
    );

    $dir = dirname($newPath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create upload directory: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException(
            'Upload folder is not writable by the web server. Grant write access to the upload/ directory.'
        );
    }

    if (is_file($oldPath)) {
        $backup = $oldPath . '.bak_' . date('Ymd_His');
        if (!@copy($oldPath, $backup)) {
            throw new RuntimeException('Failed to create backup before replace');
        }
    }

    if (!move_uploaded_file((string) $_FILES['file']['tmp_name'], $newPath)) {
        throw new RuntimeException('Failed to save replacement image');
    }

    if ($oldExt !== $newExt && is_file($oldPath) && $oldPath !== $newPath) {
        @unlink($oldPath);
    }

    $update = [
        'upload_uplname' => basename((string) $_FILES['file']['name']),
        'upload_filesize' => (int) $_FILES['file']['size'],
        'upload_extension' => $newExt,
        'upload_status' => '1',
    ];
    $set = [];
    $params = [];
    foreach ($update as $column => $value) {
        $set[] = $column . ' = ?';
        $params[] = $value;
    }
    $params[] = $uploadId;
    $pdo->prepare('UPDATE sys_upload SET ' . implode(', ', $set) . ' WHERE upload_id = ?')->execute($params);

    return [
        'message' => 'Image replaced successfully. upload_id ' . $uploadId . ' unchanged — app links stay valid.',
        'uploadId' => $uploadId,
        'woTaskNo' => $row['wo_task_no'],
        'relativePath' => trim(str_replace('\\', '/', $row['upload_folder']), '/')
            . '/' . $row['upload_filename'] . '.' . $newExt,
        'backupCreated' => isset($backup) && is_file($backup),
    ];
}

function woUploadSoftDelete(PDO $pdo): array
{
    $woTaskUploadId = (int) ($_POST['woTaskUploadId'] ?? 0);
    if ($woTaskUploadId <= 0) {
        throw new InvalidArgumentException('woTaskUploadId is required');
    }

    $stmt = $pdo->prepare(
        'SELECT wtu.upload_id, wt.wo_task_no
         FROM wo_task_upload wtu
         INNER JOIN wo_task wt ON wt.wo_task_id = wtu.wo_task_id
         WHERE wtu.wo_task_upload_id = ?'
    );
    $stmt->execute([$woTaskUploadId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new InvalidArgumentException('WO upload record not found');
    }

    $pdo->prepare('DELETE FROM wo_task_upload WHERE wo_task_upload_id = ?')->execute([$woTaskUploadId]);
    $pdo->prepare('UPDATE sys_upload SET upload_status = ? WHERE upload_id = ?')->execute(['6', (int) $row['upload_id']]);

    return [
        'message' => 'Upload unlinked and marked deleted (same as mobile delete).',
        'woTaskNo' => $row['wo_task_no'],
        'uploadId' => (int) $row['upload_id'],
    ];
}
