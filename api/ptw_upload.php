<?php
/**
 * PTW Document Upload API
 * Handles file uploads for PTW permits following GEMS2 standards
 * 
 * @author GEMS2 Development Team
 * @version 1.0
 * @since August 2025
 */

// Include required GEMS2 classes
require_once __DIR__ . '/library/jwt.php';
require_once __DIR__ . '/function/db.php';
require_once __DIR__ . '/function/general.php';
require_once __DIR__ . '/class/PtwPermit.php';

// GEMS2 Standard: Security headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize response following GEMS2 pattern
$form_data = array(
    'success' => false,
    'errmsg' => '',
    'result' => null
);

$is_transaction = false;

try {
    // GEMS2 Standard: JWT Authentication
    $jwt = new JwtHelper();
    $userInfo = $jwt->checkJwt();
    
    if (!$userInfo) {
        throw new Exception('[' . __LINE__ . '] - Authentication failed', 401);
    }
    
    $userId = $userInfo['userId'];
    $userRole = $userInfo['userRole'];
    $userSite = $userInfo['siteId'] ?? 1;
    
    // Initialize classes following GEMS2 pattern
    $fn_general = new General();
    $fn_ptw = new PtwPermit();
    
    // Only handle POST requests for file uploads
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('[' . __LINE__ . '] - Only POST method allowed', 405);
    }
    
    // Validate required parameters
    if (empty($_POST['ptwPermitId']) || !is_numeric($_POST['ptwPermitId'])) {
        throw new Exception('[' . __LINE__ . '] - Valid PTW Permit ID is required');
    }
    
    if (empty($_POST['documentType'])) {
        throw new Exception('[' . __LINE__ . '] - Document type is required');
    }
    
    if (empty($_FILES['file'])) {
        throw new Exception('[' . __LINE__ . '] - No file uploaded');
    }
    
    $ptwPermitId = (int)$_POST['ptwPermitId'];
    $documentType = $_POST['documentType'];
    $file = $_FILES['file'];
    
    // Validate document type
    $allowedDocTypes = [
        'CIDB_GREEN_CARD',
        'JOB_METHOD_STATEMENT', 
        'RISK_ASSESSMENT',
        'SAFETY_DATA_SHEET',
        'EQUIPMENT_CERTIFICATE',
        'DRAWING',
        'PHOTO',
        'OTHER'
    ];
    
    if (!in_array($documentType, $allowedDocTypes)) {
        throw new Exception('[' . __LINE__ . '] - Invalid document type');
    }
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('[' . __LINE__ . '] - File upload error: ' . $file['error']);
    }
    
    // File size validation (max 10MB)
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxFileSize) {
        throw new Exception('[' . __LINE__ . '] - File size exceeds maximum limit of 10MB');
    }
    
    // File type validation
    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('[' . __LINE__ . '] - Invalid file type. Allowed: ' . implode(', ', $allowedExtensions));
    }
    
    // MIME type validation for security
    $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif'
    ];
    
    $fileMimeType = mime_content_type($file['tmp_name']);
    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        throw new Exception('[' . __LINE__ . '] - Invalid file MIME type');
    }
    
    // Verify PTW permit exists and user has access
    $permitDetails = $fn_ptw->getPtwDetails($ptwPermitId, $userId, $userRole);
    if (empty($permitDetails)) {
        throw new Exception('[' . __LINE__ . '] - PTW permit not found or access denied');
    }
    
    // Create upload directory if it doesn't exist
    $uploadBaseDir = __DIR__ . '/../uploads/ptw/';
    if (!is_dir($uploadBaseDir)) {
        if (!mkdir($uploadBaseDir, 0755, true)) {
            throw new Exception('[' . __LINE__ . '] - Unable to create upload directory');
        }
    }
    
    // Generate unique filename
    $timestamp = date('Y-m-d_H-i-s');
    $uniqueId = uniqid();
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $newFileName = $permitDetails['ptwPermitNumber'] . '_' . $documentType . '_' . $timestamp . '_' . $uniqueId . '.' . $fileExtension;
    
    $uploadPath = $uploadBaseDir . $newFileName;
    $relativePath = 'uploads/ptw/' . $newFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('[' . __LINE__ . '] - Failed to move uploaded file');
    }
    
    // GEMS2 Standard: Start transaction
    DbMysql::beginTransaction();
    $is_transaction = true;
    
    // Save document record to database
    $documentData = array(
        'ptwPermitId' => $ptwPermitId,
        'documentName' => $file['name'],
        'documentPath' => $relativePath,
        'documentType' => $documentType,
        'documentSize' => $file['size'],
        'documentMimeType' => $fileMimeType,
        'documentDescription' => $_POST['documentDescription'] ?? null,
        'uploadedBy' => $userId,
        'createdBy' => $userId,
        'status' => '1'
    );
    
    $documentId = DbMysql::insert('ptw_document', $documentData);
    
    // Log audit trail following GEMS2 standard
    $fn_general->save_audit('210', $userId, 'PTW Document Uploaded: ' . $file['name'] . ' for Permit ' . $permitDetails['ptwPermitNumber']);
    
    // GEMS2 Standard: Commit transaction
    DbMysql::commit();
    
    $result = array(
        'documentId' => $documentId,
        'fileName' => $newFileName,
        'originalName' => $file['name'],
        'filePath' => $relativePath,
        'fileSize' => $file['size'],
        'documentType' => $documentType,
        'message' => 'File uploaded successfully'
    );
    
    $form_data['result'] = $result;
    $form_data['success'] = true;
    $form_data['errmsg'] = 'File uploaded successfully';

} catch (Exception $e) {
    // GEMS2 Standard: Rollback transaction on error
    if ($is_transaction) {
        DbMysql::rollback();
    }
    
    // Log error following GEMS2 pattern
    error_log('[PTW Upload API Error] ' . $e->getMessage());
    
    $form_data['success'] = false;
    $form_data['errmsg'] = $e->getMessage();
    
    // Set appropriate HTTP status code
    $errorCode = $e->getCode();
    if ($errorCode === 401) {
        http_response_code(401);
    } elseif ($errorCode === 403) {
        http_response_code(403);
    } elseif ($errorCode === 405) {
        http_response_code(405);
    } else {
        http_response_code(400);
    }

} finally {
    // Close database connection
    DbMysql::getInstance()->db_close();
}

// Return JSON response following GEMS2 pattern
echo json_encode($form_data);
exit();
?>
