<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';

$response = [ 'success' => false, 'message' => '', 'uploaded' => [] ];

try {
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_general->__set('constant', $constant);
    Class_db::getInstance()->db_connect();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid method');
    }

    // Expect: permit_id (int), site_id (int), and files[]
    $permit_id = isset($_POST['permit_id']) ? intval($_POST['permit_id']) : 0;
    $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
    if ($permit_id <= 0 || $site_id <= 0) {
        throw new Exception('Missing permit_id or site_id');
    }

    if (!isset($_FILES['files'])) {
        throw new Exception('No files uploaded');
    }

    // Destination folder: upload/ptw/{site_id}/{permit_id} (reuse app's standard writable folder)
    $baseUploadDir = __DIR__ . '/../upload/ptw';

    // Helper to create directory and verify writability
    $ensureDir = function($dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                $err = error_get_last();
                throw new Exception('Failed to create directory: ' . $dir . ' | ' . ($err['message'] ?? ''));
            }
        }
        // Try to ensure it's writable; if not, attempt chmod as a best-effort (dev env)
        if (!is_writable($dir)) {
            @chmod($dir, 0777); // best effort; may fail on some systems
        }
        if (!is_writable($dir)) {
            throw new Exception('Upload directory not writable: ' . $dir);
        }
    };

    $ensureDir($baseUploadDir);
    $siteDir = $baseUploadDir . '/' . $site_id;
    $ensureDir($siteDir);
    $permitDir = $siteDir . '/' . $permit_id;
    $ensureDir($permitDir);

    $allowed = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    $maxSize = 10 * 1024 * 1024; // 10MB

    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    for ($i = 0; $i < $count; $i++) {
        $name = $files['name'][$i];
        $tmp = $files['tmp_name'][$i];
        $size = intval($files['size'][$i]);
        $error = intval($files['error'][$i]);
        if ($error !== UPLOAD_ERR_OK) {
            $response['uploaded'][] = [ 'name' => $name, 'status' => 'error', 'message' => 'Upload error ' . $error ];
            continue;
        }
        if ($size <= 0 || $size > $maxSize) {
            $response['uploaded'][] = [ 'name' => $name, 'status' => 'error', 'message' => 'Invalid file size' ];
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            $response['uploaded'][] = [ 'name' => $name, 'status' => 'error', 'message' => 'Unsupported file type' ];
            continue;
        }
        $safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $name);
        // Ensure uniqueness
        $targetPath = $permitDir . '/' . uniqid('doc_') . '_' . $safeName;
        // Move the uploaded file into place
        $moved = @move_uploaded_file($tmp, $targetPath);
        if (!$moved) {
            // Collect diagnostics to help troubleshoot
            $diag = [
                'tmp_is_uploaded' => is_uploaded_file($tmp) ? 'yes' : 'no',
                'tmp_readable' => is_readable($tmp) ? 'yes' : 'no',
                'target_dir_writable' => is_writable($permitDir) ? 'yes' : 'no',
                'target_path' => $targetPath,
                'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: '',
                'sys_temp_dir' => sys_get_temp_dir() ?: ''
            ];
            $err = error_get_last();
            $msg = 'Failed to move file';
            if (!empty($err['message'])) { $msg .= ' | ' . $err['message']; }
            $msg .= ' | diag: ' . json_encode($diag);

            // Fallback attempts (rename/copy) for environments where move_uploaded_file fails
            if (is_readable($tmp)) {
                if (@rename($tmp, $targetPath)) {
                    $moved = true;
                    @error_log('[ptw_document_upload] move_uploaded_file failed but rename() succeeded for ' . $name);
                } elseif (@copy($tmp, $targetPath)) {
                    @unlink($tmp);
                    $moved = true;
                    @error_log('[ptw_document_upload] move_uploaded_file failed but copy()+unlink() succeeded for ' . $name);
                }
            }

            if (!$moved) {
                // Log server-side for deeper debugging and report
                @error_log('[ptw_document_upload] move_uploaded_file failed: ' . $msg);
                $response['uploaded'][] = [ 'name' => $name, 'status' => 'error', 'message' => $msg ];
                continue;
            }
        }

        // Insert into ptw_document
        $relPath = 'upload/ptw/' . $site_id . '/' . $permit_id . '/' . basename($targetPath);
        $docRow = [
            'ptw_permit_id' => $permit_id,
            'document_type' => 'SUPPORTING',
            'document_name' => $name,
            'document_path' => $relPath,
            'document_size' => $size,
            'document_mime_type' => $allowed[$ext],
            'uploaded_by' => 0
        ];
        Class_db::getInstance()->db_insert('ptw_document', $docRow);

        $response['uploaded'][] = [
            'name' => $name,
            'status' => 'ok',
            'path' => $relPath
        ];
    }

    $response['success'] = true;
} catch (Exception $ex) {
    $response['success'] = false;
    $response['message'] = $ex->getMessage();
    @error_log('[ptw_document_upload] exception: ' . $ex->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
