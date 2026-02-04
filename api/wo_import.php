<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo.php';
require_once 'class/WoImportFileParser.php';

// Load composer autoload for PhpSpreadsheet (required for import functionality)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$api_name = 'api_wo_import';
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
date_default_timezone_set("Asia/Kuala_Lumpur");
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo = new Class_wo();
$fileParser = new WoImportFileParser();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];

    // Check authorization
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
    } else {
        throw new Exception('Authorization header missing');
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'get_template':
            echo json_encode(getImportTemplate());
            break;
            
        case 'download_template':
            downloadCsvTemplate();
            break;
            
        case 'validate_file':
            if (!isset($_FILES['import_file'])) {
                throw new Exception('No file uploaded');
            }
            echo json_encode(validateImportFile($_FILES['import_file']));
            break;
            
        case 'preview_import':
            if (!isset($_FILES['import_file'])) {
                throw new Exception('No file uploaded');
            }
            $siteId = $_POST['site_id'] ?? '';
            echo json_encode(previewImportData($_FILES['import_file'], $siteId, $jwt_data->userId));
            break;
            
        case 'execute_import':
            if (!isset($_FILES['import_file'])) {
                throw new Exception('No file uploaded');
            }
            $siteId = $_POST['site_id'] ?? '';
            $importOptions = json_decode($_POST['import_options'] ?? '{}', true);
            echo json_encode(executeImport($_FILES['import_file'], $siteId, $jwt_data->userId, $importOptions));
            break;
            
        case 'get_import_history':
            echo json_encode(getImportHistory($jwt_data->userId));
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Download CSV template
 */
function downloadCsvTemplate() {
    global $fileParser;
    
    $csvContent = $fileParser->generateCsvTemplate();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="wo_import_template.csv"');
    header('Content-Length: ' . strlen($csvContent));
    
    echo $csvContent;
    exit;
}

/**
 * Get import template structure
 */
function getImportTemplate() {
    return [
        'success' => true,
        'template' => [
            'required_columns' => [
                'external_wo_number' => 'External Work Order Number (Request No. or Work Order No.)',
                'description' => 'Work Order Description/Complaint (Title of the ticket)',
                'location' => 'Location of the work',
                'wo_type' => 'Work Order Type (1=Client Complaint, 2=Self Finding, 3=Request, 4=Breakdown, 5=Defect, 6=Public Complaint) or Complaint Type text',
                'severity' => 'Severity (1=Non-Critical, 2=Critical) or text like "Critical"/"Non-Critical"',
                'assigned_to_name' => 'Technician Name (PIC Name/Fixed By - will be looked up in system by user_name)',
                'created_date' => 'Date Created (YYYY-MM-DD or DD/MM/YYYY)',
                'assigned_date' => 'Date Assigned (YYYY-MM-DD or DD/MM/YYYY)',
                'completed_date' => 'Date Completed/Executed (YYYY-MM-DD or DD/MM/YYYY)',
                'verified_date' => 'Date Verified (YYYY-MM-DD or DD/MM/YYYY)'
            ],
            'optional_columns' => [
                'created_by_name' => 'Creator Name (Complainant/Assigned By - will be looked up in system by user_name)',
                'verified_by_name' => 'Verifier Name (Verified By - will be looked up in system by user_name)',
                'repair_description' => 'Repair Work Description',
                'longitude' => 'GPS Longitude',
                'latitude' => 'GPS Latitude',
                'rating' => 'Work Order Rating (1-5)',
                'asset_number' => 'Asset Number',
                'zone_id' => 'Zone ID',
                'external_reference' => 'External System Reference',
                'site_name' => 'Site Name (optional if specified in UI)'
            ],
            'column_mapping' => [
                // User's template columns -> Our system columns
                'Date' => 'created_date',
                'Request No.' => 'external_wo_number',
                'Work Order No.' => 'external_wo_number',
                'Complaint Description' => 'description',
                'Location' => 'location',
                'Complaint Type' => 'wo_type',
                'Severity' => 'severity',
                'PIC Name' => 'assigned_to_name',
                'Fixed By' => 'assigned_to_name',
                'Complainant' => 'created_by_name',
                'Assigned By' => 'created_by_name',
                'Verified By' => 'verified_by_name',
                'Repair Description' => 'repair_description',
                'Rating' => 'rating',
                'Complaint Time' => 'created_date',
                'Assigned Time' => 'assigned_date',
                'Executed Time' => 'completed_date',
                'Verified Time' => 'verified_date',
                // Legacy email-based columns (for backward compatibility)
                'assigned_to_email' => 'assigned_to_email',
                'created_by_email' => 'created_by_email',
                'verified_by_email' => 'verified_by_email'
            ],
            'validation_rules' => [
                'external_wo_number' => 'Must be unique within the import batch',
                'description' => 'Required field, cannot be empty',
                'location' => 'Required field, cannot be empty',
                'wo_type' => 'Must be valid type number (1-6) or valid text (will be mapped)',
                'severity' => 'Must be 1 (Non-Critical) or 2 (Critical) or valid text',
                'assigned_to_name' => 'Must exist in sys_user table for the selected site (or use assigned_to_email)',
                'created_by_name' => 'Must exist in sys_user table for the selected site (or use created_by_email)',
                'verified_by_name' => 'Must exist in sys_user table for the selected site (or use verified_by_email)',
                'dates' => 'Must be in YYYY-MM-DD or DD/MM/YYYY format'
            ],
            'sample_data' => [
                [
                    'Date' => '2024-01-15',
                    'Request No.' => 'REQ-2024-001',
                    'Work Order No.' => 'WO-2024-001',
                    'Location' => 'Level 2, Office Block A',
                    'Complaint Type' => 'Breakdown',
                    'Complainant' => 'John Doe',
                    'Complaint Description' => 'Aircon not working in office area',
                    'Severity' => 'Critical',
                    'PIC Name' => 'Mike Johnson',
                    'Repair Description' => 'Replaced faulty compressor unit',
                    'Rating' => '5',
                    'Fixed By' => 'Mike Johnson',
                    'Assigned By' => 'Sarah Manager',
                    'Verified By' => 'Robert Supervisor',
                    'Complaint Time' => '2024-01-15 09:00:00',
                    'Assigned Time' => '2024-01-15 09:30:00',
                    'Executed Time' => '2024-01-16 14:00:00',
                    'Verified Time' => '2024-01-16 16:00:00'
                ]
            ]
        ]
    ];
}

/**
 * Validate uploaded file format and basic structure
 */
function validateImportFile($file) {
    try {
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
            throw new Exception('Invalid file format. Only CSV, XLS, and XLSX files are supported.');
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            throw new Exception('File size too large. Maximum size is 10MB.');
        }
        
        // Basic file content validation
        $data = parseImportFile($file);
        
        if (empty($data)) {
            throw new Exception('File appears to be empty or invalid.');
        }
        
        if (count($data) < 2) { // At least header + 1 data row
            throw new Exception('File must contain at least one data row besides the header.');
        }
        
        // Check for required columns
        $headers = array_keys($data[0]);
        $requiredColumns = ['external_wo_number', 'description', 'location', 'wo_type', 'severity', 'assigned_to_email', 'created_date', 'assigned_date', 'completed_date', 'verified_date'];
        
        $missingColumns = array_diff($requiredColumns, $headers);
        if (!empty($missingColumns)) {
            throw new Exception('Missing required columns: ' . implode(', ', $missingColumns));
        }
        
        return [
            'success' => true,
            'message' => 'File validation successful',
            'file_info' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $fileExtension,
                'rows' => count($data) - 1, // Excluding header
                'columns' => count($headers)
            ],
            'headers' => $headers
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'File validation failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Preview import data with validation
 */
function previewImportData($file, $siteId, $userId) {
    try {
        $data = parseImportFile($file);
        $headers = array_shift($data); // Remove header row
        
        if (empty($siteId)) {
            throw new Exception('Site ID is required for import');
        }
        
        // Validate site exists
        $site = Class_db::getInstance()->db_select_single('cli_site', ['site_id' => $siteId]);
        if (!$site) {
            throw new Exception('Invalid site ID provided');
        }
        
        $validRows = [];
        $invalidRows = [];
        $errors = [];
        $warnings = [];
        
        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed
            $validation = validateImportRow($row, $siteId, $rowNumber);
            
            if ($validation['valid']) {
                $processedRow = isset($validation['processed_row']) ? $validation['processed_row'] : $row;
                $validRows[] = array_merge($processedRow, ['row_number' => $rowNumber]);
                if (!empty($validation['warnings'])) {
                    $warnings = array_merge($warnings, $validation['warnings']);
                }
            } else {
                $invalidRows[] = array_merge($row, ['row_number' => $rowNumber, 'errors' => $validation['errors']]);
                $errors = array_merge($errors, $validation['errors']);
            }
        }
        
        return [
            'success' => true,
            'preview' => [
                'total_rows' => count($data),
                'valid_rows' => count($validRows),
                'invalid_rows' => count($invalidRows),
                'site_info' => [
                    'site_id' => $site['site_id'],
                    'site_name' => $site['site_name'],
                    'site_code' => $site['site_code']
                ]
            ],
            'sample_valid' => array_slice($validRows, 0, 5), // First 5 valid rows
            'sample_invalid' => array_slice($invalidRows, 0, 5), // First 5 invalid rows
            'errors' => array_unique($errors),
            'warnings' => array_unique($warnings),
            'can_proceed' => count($validRows) > 0
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Preview failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Execute the import process
 */
function executeImport($file, $siteId, $userId, $importOptions = []) {
    try {
        Class_db::getInstance()->db_beginTransaction();
        
        $data = parseImportFile($file);
        $headers = array_shift($data); // Remove header row
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $importLog = [];
        
        // Create import batch record
        $batchId = createImportBatch($file['name'], $siteId, $userId, count($data));
        
        foreach ($data as $index => $row) {
            $rowNumber = $index + 2;
            
            try {
                $validation = validateImportRow($row, $siteId, $rowNumber);
                
                if (!$validation['valid']) {
                    $skipped++;
                    $errors[] = "Row $rowNumber: " . implode(', ', $validation['errors']);
                    logImportRow($batchId, $rowNumber, 'SKIPPED', implode(', ', $validation['errors']), $row);
                    continue;
                }
                
                // Use the processed row for import
                $processedRow = isset($validation['processed_row']) ? $validation['processed_row'] : $row;
                
                // Import the work order
                $woTaskId = importWorkOrder($processedRow, $siteId, $userId, $importOptions);
                
                if ($woTaskId) {
                    $imported++;
                    $importLog[] = "Row $rowNumber: Successfully imported as WO ID $woTaskId";
                    logImportRow($batchId, $rowNumber, 'SUCCESS', "Created WO ID: $woTaskId", $row);
                } else {
                    $skipped++;
                    $errors[] = "Row $rowNumber: Failed to create work order";
                    logImportRow($batchId, $rowNumber, 'FAILED', 'Failed to create work order', $row);
                }
                
            } catch (Exception $e) {
                $skipped++;
                $errors[] = "Row $rowNumber: " . $e->getMessage();
                logImportRow($batchId, $rowNumber, 'ERROR', $e->getMessage(), $row);
            }
        }
        
        // Update batch summary
        updateImportBatch($batchId, $imported, $skipped);
        
        Class_db::getInstance()->db_commit();
        
        return [
            'success' => true,
            'message' => "Import completed. $imported work orders imported, $skipped skipped.",
            'summary' => [
                'batch_id' => $batchId,
                'total_processed' => count($data),
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'import_log' => array_slice($importLog, 0, 10) // First 10 log entries
            ]
        ];
        
    } catch (Exception $e) {
        Class_db::getInstance()->db_rollback();
        return [
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Parse uploaded file (CSV, XLS, XLSX)
 */
function parseImportFile($file) {
    global $fileParser;
    return $fileParser->parseFile($file);
}

/**
 * Apply column mapping to transform user's template columns to our system columns
 */
function applyColumnMapping($row) {
    $template = getImportTemplate();
    $columnMapping = $template['template']['column_mapping'];
    
    $mappedRow = [];
    
    foreach ($row as $userColumn => $value) {
        // Check if this column needs mapping
        if (isset($columnMapping[$userColumn])) {
            $systemColumn = $columnMapping[$userColumn];
            $mappedRow[$systemColumn] = $value;
        } else {
            // Keep unmapped columns as-is (for backward compatibility)
            $mappedRow[$userColumn] = $value;
        }
    }
    
    return $mappedRow;
}

/**
 * Convert names to email addresses for user identification
 */
function resolveUserNames($row, $siteId) {
    $nameFields = ['assigned_to_name', 'created_by_name', 'verified_by_name'];
    
    foreach ($nameFields as $nameField) {
        if (!empty($row[$nameField])) {
            $emailField = str_replace('_name', '_email', $nameField);
            
            // First try to find by user_name (direct match)
            $user = Class_db::getInstance()->db_select_single('sys_user', [
                'user_name' => $row[$nameField], 
                'site_id' => $siteId
            ]);
            
            if ($user) {
                // Use user_name as the "email" since there's no actual email field
                $row[$emailField] = $user['user_name'];
                // Remove the name field after successful conversion
                unset($row[$nameField]);
            } else {
                // Try by first name match
                $user = Class_db::getInstance()->db_select_single('sys_user', [
                    'user_first_name' => $row[$nameField], 
                    'site_id' => $siteId
                ]);
                
                if ($user) {
                    $row[$emailField] = $user['user_name'];
                    unset($row[$nameField]);
                } else {
                    // If not found by first name, try by first/last name combinations
                    $nameParts = explode(' ', trim($row[$nameField]));
                    if (count($nameParts) >= 2) {
                        $firstName = $nameParts[0];
                        $lastName = end($nameParts);
                        
                        $user = Class_db::getInstance()->db_select_single('sys_user', [
                            'user_first_name' => $firstName,
                            'user_last_name' => $lastName,
                            'site_id' => $siteId
                        ]);
                        
                        if ($user) {
                            $row[$emailField] = $user['user_name'];
                            // Remove the name field after successful conversion
                            unset($row[$nameField]);
                        }
                        // If still not found, keep the name field for validation error
                    }
                    // If name parsing failed or user not found, keep the name field for validation
                }
            }
        }
    }
    
    return $row;
}

/**
 * Convert text values to system numeric codes
 */
function normalizeFieldValues($row) {
    // Map work order types
    if (!empty($row['wo_type']) && !is_numeric($row['wo_type'])) {
        $woTypeMap = [
            'client complaint' => '1',
            'complaint' => '1',
            'self finding' => '2',
            'request' => '3',
            'breakdown' => '4',
            'defect' => '5',
            'public complaint' => '6'
        ];
        
        $woTypeText = strtolower(trim($row['wo_type']));
        if (isset($woTypeMap[$woTypeText])) {
            $row['wo_type'] = $woTypeMap[$woTypeText];
        }
    }
    
    // Map severity levels
    if (!empty($row['severity']) && !is_numeric($row['severity'])) {
        $severityMap = [
            'non-critical' => '1',
            'non critical' => '1',
            'low' => '1',
            'critical' => '2',
            'high' => '2',
            'urgent' => '2'
        ];
        
        $severityText = strtolower(trim($row['severity']));
        if (isset($severityMap[$severityText])) {
            $row['severity'] = $severityMap[$severityText];
        }
    }
    
    return $row;
}

/**
 * Validate a single import row
 */
function validateImportRow($row, $siteId, $rowNumber) {
    $errors = [];
    $warnings = [];
    
    // Apply column mapping first
    $row = applyColumnMapping($row);
    
    // Resolve user names to emails
    $row = resolveUserNames($row, $siteId);
    
    // Normalize field values (text to numbers)
    $row = normalizeFieldValues($row);
    
    // Check required fields (flexible - either name or email fields)
    $requiredFields = ['external_wo_number', 'description', 'location', 'wo_type', 'severity', 'created_date', 'assigned_date', 'completed_date', 'verified_date'];
    
    // Require either assigned_to_email or assigned_to_name
    if (empty($row['assigned_to_email']) && empty($row['assigned_to_name'])) {
        $errors[] = "Missing required field: assigned technician (either assigned_to_email or assigned_to_name/PIC Name/Fixed By)";
    }
    
    foreach ($requiredFields as $field) {
        if (empty($row[$field])) {
            $errors[] = "Missing required field: $field";
        }
    }
    
    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings, 'processed_row' => $row];
    }
    
    // Validate WO type
    if (!in_array($row['wo_type'], ['1', '2', '3', '4', '5', '6'])) {
        $errors[] = 'Invalid wo_type. Must be 1-6 or valid text (Client Complaint, Self Finding, Request, Breakdown, Defect, Public Complaint).';
    }
    
    // Validate severity
    if (!in_array($row['severity'], ['1', '2'])) {
        $errors[] = 'Invalid severity. Must be 1 (Non-Critical) or 2 (Critical) or valid text.';
    }
    
    // Validate assigned technician exists (if we have email/username)
    if (!empty($row['assigned_to_email'])) {
        $assignedUser = Class_db::getInstance()->db_select_single('sys_user', ['user_name' => $row['assigned_to_email'], 'site_id' => $siteId]);
        if (!$assignedUser) {
            $errors[] = 'Assigned technician username not found in system: ' . $row['assigned_to_email'];
        }
    }
    
    // If we couldn't resolve a name to email, add warning
    if (!empty($row['assigned_to_name']) && empty($row['assigned_to_email'])) {
        $warnings[] = 'Could not find user with name: ' . $row['assigned_to_name'];
    }
    
    // Validate date sequence
    try {
        $createdDate = new DateTime($row['created_date']);
        $assignedDate = new DateTime($row['assigned_date']);
        $completedDate = new DateTime($row['completed_date']);
        $verifiedDate = new DateTime($row['verified_date']);
        
        if ($createdDate > $assignedDate) {
            $errors[] = 'Created date cannot be after assigned date';
        }
        if ($assignedDate > $completedDate) {
            $errors[] = 'Assigned date cannot be after completed date';
        }
        if ($completedDate > $verifiedDate) {
            $errors[] = 'Completed date cannot be after verified date';
        }
        
    } catch (Exception $e) {
        $errors[] = 'Invalid date format. Use YYYY-MM-DD or DD/MM/YYYY';
    }
    
    // Validate optional rating
    if (!empty($row['rating']) && (!is_numeric($row['rating']) || $row['rating'] < 1 || $row['rating'] > 5)) {
        $errors[] = 'Invalid rating. Must be 1-5 if provided.';
    }
    
    // Check for duplicate external WO number
    $existingWo = Class_db::getInstance()->db_select_single('wo_task', ['wo_task_external_ref' => $row['external_wo_number'], 'site_id' => $siteId]);
    if ($existingWo) {
        $errors[] = 'External WO number already exists: ' . $row['external_wo_number'];
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
        'processed_row' => $row
    ];
}

/**
 * Import a single work order
 */
function importWorkOrder($row, $siteId, $importUserId, $options = []) {
    try {
        // Get user IDs
        $assignedUser = Class_db::getInstance()->db_select_single('sys_user', ['user_name' => $row['assigned_to_email'], 'site_id' => $siteId]);
        $assignedUserId = $assignedUser['user_id'];
        
        // Get assigned user's PPM group for gamification integration
        $userProfile = Class_db::getInstance()->db_select_single('sys_user_profile', ['user_id' => $assignedUserId]);
        $ppmGroupId = null;
        if ($userProfile && !empty($userProfile['designation_id'])) {
            // Get PPM group based on user's designation and site
            $ppmGroup = Class_db::getInstance()->db_select_single('ppm_group', ['site_id' => $siteId]);
            if ($ppmGroup) {
                $ppmGroupId = $ppmGroup['ppm_group_id'];
            }
        }
        // If no specific PPM group found, use default group for the site
        if (!$ppmGroupId) {
            $defaultPpmGroup = Class_db::getInstance()->db_select_single('ppm_group', ['site_id' => $siteId], '', 'ppm_group_id ASC');
            if ($defaultPpmGroup) {
                $ppmGroupId = $defaultPpmGroup['ppm_group_id'];
            }
        }
        
        $createdUserId = $assignedUserId; // Default to assigned user
        if (!empty($row['created_by_email'])) {
            $createdUser = Class_db::getInstance()->db_select_single('sys_user', ['user_name' => $row['created_by_email'], 'site_id' => $siteId]);
            if ($createdUser) {
                $createdUserId = $createdUser['user_id'];
            }
        }
        
        $verifiedUserId = $importUserId; // Default to import user
        if (!empty($row['verified_by_email'])) {
            $verifiedUser = Class_db::getInstance()->db_select_single('sys_user', ['user_name' => $row['verified_by_email'], 'site_id' => $siteId]);
            if ($verifiedUser) {
                $verifiedUserId = $verifiedUser['user_id'];
            }
        }
        
        // Generate WO number
        $site = Class_db::getInstance()->db_select_single('cli_site', ['site_id' => $siteId]);
        $woNumber = generateImportWoNumber($site);
        
        // Parse dates
        $createdDate = new DateTime($row['created_date']);
        $assignedDate = new DateTime($row['assigned_date']);
        $completedDate = new DateTime($row['completed_date']);
        $verifiedDate = new DateTime($row['verified_date']);
        
        // Create workflow transaction first
        $transactionData = [
            'transaction_no' => $woNumber,
            'flow_id' => 1, // Using work order flow
            'user_id' => $importUserId,
            'group_id' => 1,
            'transaction_date_due' => '|CURDATE() + INTERVAL 30 DAY',
            'transaction_status' => 5
        ];
        
        $transactionId = Class_db::getInstance()->db_insert('wfl_transaction', $transactionData);
        
        // Create workflow task
        $wflTaskData = [
            'transaction_id' => $transactionId,
            'checkpoint_id' => 1,
            'role_id' => 5, // Technician role
            'group_id' => 1,
            'task_created_user' => $importUserId,
            'task_created_group' => 1,
            'task_claimed_user' => $assignedUserId,
            'task_time_claimed' => '|NOW()',
            'task_date_due' => '|CURDATE() + INTERVAL 7 DAY',
            'task_status' => 5
        ];
        
        $wflTaskId = Class_db::getInstance()->db_insert('wfl_task', $wflTaskData);
        
        // Create work order record - directly as completed
        $woData = [
            'wo_task_no' => $woNumber,
            'wo_task_type' => $row['wo_type'],
            'wo_task_type_init' => $row['wo_type'],
            'wo_task_location' => $row['location'],
            'wo_task_complaint' => $row['description'],
            'wo_task_repair_desc' => $row['repair_description'] ?? '',
            'wo_task_longitude' => $row['longitude'] ?? '',
            'wo_task_latitude' => $row['latitude'] ?? '',
            'wo_task_severity' => $row['severity'],
            'wo_task_rate' => $row['rating'] ?? '',
            'wo_task_asset_no' => $row['asset_number'] ?? '',
            'zone_id' => $row['zone_id'] ?? '',
            'site_id' => $siteId,
            'ppm_group_id' => $ppmGroupId, // Add PPM group for gamification
            'wo_task_created_by' => $createdUserId,
            'wo_task_assigned_to' => $assignedUserId,
            'wo_task_assigned_by' => $importUserId,
            'wo_task_verified_by' => $verifiedUserId,
            'wo_task_time_created' => $createdDate->format('Y-m-d H:i:s'),
            'wo_task_time_assigned' => $assignedDate->format('Y-m-d H:i:s'),
            'wo_task_time_executed' => $completedDate->format('Y-m-d H:i:s'),
            'wo_task_time_verified' => $verifiedDate->format('Y-m-d H:i:s'),
            'wo_task_status' => '16', // Completed status
            'wo_task_is_pdf' => '1',
            'wo_task_is_wr' => '0',
            'wo_task_is_pdf_wr' => '0',
            'wo_task_external_ref' => $row['external_wo_number'],
            'wo_task_is_imported' => '1',
            'transaction_id' => $transactionId
        ];
        
        $woTaskId = Class_db::getInstance()->db_insert('wo_task', $woData);
        
        return $woTaskId;
        
    } catch (Exception $e) {
        throw new Exception('Failed to import work order: ' . $e->getMessage());
    }
}

/**
 * Generate WO number for imported work orders
 */
function generateImportWoNumber($site) {
    $siteCode = $site['site_code'];
    $runningNo = intval($site['site_running_no_wo']) + 1;
    
    // Update running number
    Class_db::getInstance()->db_update('cli_site', ['site_running_no_wo' => $runningNo], ['site_id' => $site['site_id']]);
    
    $curDate = new DateTime();
    $runningNoTemp = 100000 + $runningNo;
    $runningNoStr = substr(strval($runningNoTemp), 1);
    
    return 'WO' . $siteCode . $curDate->format("ymd") . $runningNoStr;
}

/**
 * Create import batch record
 */
function createImportBatch($filename, $siteId, $userId, $totalRows) {
    $batchData = [
        'import_filename' => $filename,
        'site_id' => $siteId,
        'imported_by' => $userId,
        'total_rows' => $totalRows,
        'imported_rows' => 0,
        'skipped_rows' => 0,
        'import_status' => 'PROCESSING',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return Class_db::getInstance()->db_insert('wo_import_batch', $batchData);
}

/**
 * Update import batch summary
 */
function updateImportBatch($batchId, $imported, $skipped) {
    Class_db::getInstance()->db_update('wo_import_batch', [
        'imported_rows' => $imported,
        'skipped_rows' => $skipped,
        'import_status' => 'COMPLETED',
        'completed_at' => date('Y-m-d H:i:s')
    ], ['batch_id' => $batchId]);
}

/**
 * Log individual row import result
 */
function logImportRow($batchId, $rowNumber, $status, $message, $rowData) {
    $logData = [
        'batch_id' => $batchId,
        'import_row_number' => $rowNumber, // Renamed from row_number to avoid reserved keyword
        'import_status' => $status,
        'error_message' => $message,
        'row_data' => json_encode($rowData),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    Class_db::getInstance()->db_insert('wo_import_log', $logData);
}

/**
 * Get import history
 */
function getImportHistory($userId) {
    try {
        $batches = Class_db::getInstance()->db_select('wo_import_batch', 
            ['imported_by' => $userId], 
            'created_at DESC', 
            '50'
        );
        
        return [
            'success' => true,
            'data' => $batches
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to get import history: ' . $e->getMessage()
        ];
    }
}
?>
