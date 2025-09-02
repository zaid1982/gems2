<?php
// Token-gated PTW document download
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { ob_end_clean(); exit(0); }

try {
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_general->__set('constant', $constant);
    Class_db::getInstance()->db_connect();
} catch (Exception $e) {
    http_response_code(500);
    echo 'Init failed';
    ob_end_flush();
    exit;
}

try {
    $docId = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['doc_id']) ? $_GET['doc_id'] : '');
    $token = isset($_GET['t']) ? $_GET['t'] : '';
    if (empty($docId)) { http_response_code(400); echo 'Bad request'; ob_end_flush(); exit; }

    $db = Class_db::getInstance();
    $doc = $db->db_select_single('ptw_document', array('ptw_document_id' => $docId));
    if (!$doc) { http_response_code(404); echo 'Not found'; ob_end_flush(); exit; }

    $permitId = $doc['ptw_permit_id'];
    $permit = $db->db_select_single('ptw_permit', array('ptw_permit_id' => $permitId));
    if (!$permit) { http_response_code(404); echo 'Not found'; ob_end_flush(); exit; }

    // Check auth header; if not present, require valid token
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
    $isAuthenticated = !empty($authHeader) && stripos($authHeader, 'bearer ') === 0;

    if (!$isAuthenticated) {
        $enabled = false;
        if (isset($permit['public_link_enabled'])) {
            $val = $permit['public_link_enabled'];
            $s = is_string($val) ? strtolower(trim($val)) : $val;
            $enabled = ($s === 1) || ($s === '1') || ($s === true) || ($s === 'true') || ($s === 'y') || ($s === 'yes');
        }
        $stored = isset($permit['public_token']) ? $permit['public_token'] : '';
        // Normalize zero-dates to null
        $normalizeDate = function ($v) {
            if (!isset($v)) return null;
            $s = trim((string)$v);
            if ($s === '' || $s === '0000-00-00' || $s === '0000-00-00 00:00:00') return null;
            return $s;
        };
        $revoked_at = $normalizeDate(isset($permit['public_token_revoked_at']) ? $permit['public_token_revoked_at'] : null);
        $expires_at = $normalizeDate(isset($permit['public_token_expires_at']) ? $permit['public_token_expires_at'] : null);
        $now = time();
        $expired = (!empty($expires_at) && (strtotime($expires_at) < $now));
        if (!$enabled || empty($stored) || $expired || !empty($revoked_at) || empty($token) || !hash_equals($stored, $token)) {
            http_response_code(403);
            echo 'Forbidden';
            ob_end_flush();
            exit;
        }
    }

    $relative = $doc['document_path'];
    if (empty($relative)) { http_response_code(404); echo 'Not found'; ob_end_flush(); exit; }
    $relative = ltrim($relative, '/');
    $root = realpath(__DIR__ . '/..'); // gems2 directory
    $abs = realpath($root . '/' . $relative);
    // Ensure within upload/ptw
    $allowedBase = realpath($root . '/upload/ptw');
    if ($abs === false || $allowedBase === false || strpos($abs, $allowedBase) !== 0) {
        http_response_code(403);
        echo 'Forbidden path';
        ob_end_flush();
        exit;
    }

    if (!is_file($abs) || !is_readable($abs)) {
        http_response_code(404);
        echo 'File not found';
        ob_end_flush();
        exit;
    }

    // Infer mime type
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mime = $finfo ? finfo_file($finfo, $abs) : 'application/octet-stream';
    if ($finfo) finfo_close($finfo);

    $downloadName = isset($doc['document_name']) && $doc['document_name'] !== '' ? $doc['document_name'] : basename($abs);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($abs));
    header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=600');

    ob_clean();
    readfile($abs);
    ob_end_flush();
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo 'Server error';
    ob_end_flush();
}
