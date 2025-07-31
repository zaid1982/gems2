<?php
// PPM Import API - GEMS System
// Handles PPM task data import from CSV files

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';

$apiName = 'ppm_import';
$isTransaction = false;
$formData = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new General();

try {
    $fnMain->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $fnMain->logDebug('API', $apiName, __LINE__, 'Request method = '.$requestMethod.', URL = '.$_SERVER['REQUEST_URI']);
    
    DbMysql::connect();
    $fnMain->checkJwt(apache_request_headers());

    if ($requestMethod === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'preview_import':
                $result = handlePreviewImport();
                break;
            case 'execute_import':
                $result = handleExecuteImport();
                break;
            default:
                throw new Exception('Invalid action: ' . $action);
        }
    } elseif ($requestMethod === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_import_history':
                $result = getImportHistory();
                break;
            case 'get_sample_assets':
                $result = getSampleAssets();
                break;
            case 'get_sample_users':
                $result = getSampleUsers();
                break;
            default:
                throw new Exception('Invalid GET action: ' . $action);
        }
    } else {
        throw new Exception('Invalid request method');
    }

    $formData['success'] = true;
    $formData['result'] = $result;
    
} catch (Exception $e) {
    $fnMain->logDebug('API', $apiName, __LINE__, 'Error: ' . $e->getMessage());
    $formData['success'] = false;
    $formData['error'] = $e->getMessage();
    $formData['errmsg'] = $e->getMessage();
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($formData);

function handlePreviewImport() {
    global $fnMain;
    
    if (!isset($_FILES['import_file'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['import_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    if (!str_ends_with(strtolower($file['name']), '.csv')) {
        throw new Exception('Only CSV files are allowed');
    }
    
    // Read and validate CSV file
    $csvData = readCSVFile($file['tmp_name']);
    $validationResult = validatePPMData($csvData);
    
    return $validationResult;
}

function handleExecuteImport() {
    global $fnMain;
    
    if (!isset($_FILES['import_file'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['import_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    // Read and process CSV file
    $csvData = readCSVFile($file['tmp_name']);
    $validationResult = validatePPMData($csvData);
    
    if (!$validationResult['can_proceed']) {
        throw new Exception('File validation failed - cannot proceed with import');
    }
    
    // Execute the import using the actual valid data rows
    $importResult = executePPMImport($validationResult['valid_data']);
    
    return $importResult;
}

function readCSVFile($filePath) {
    $csvData = [];
    $headers = [];
    
    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        $rowNumber = 0;
        
        while (($data = fgetcsv($handle, 10000, ',')) !== FALSE) {
            $rowNumber++;
            
            if ($rowNumber === 1) {
                // First row contains headers
                $headers = array_map('trim', $data);
                continue;
            }
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Ensure data array matches headers count
            $headerCount = count($headers);
            $dataCount = count($data);
            
            if ($dataCount < $headerCount) {
                // Pad with empty strings if data has fewer columns
                $data = array_pad($data, $headerCount, '');
            } elseif ($dataCount > $headerCount) {
                // Trim excess columns if data has more columns
                $data = array_slice($data, 0, $headerCount);
            }
            
            // Now safely combine headers with data
            $row = array_combine($headers, $data);
            
            if ($row === false) {
                throw new Exception("Failed to process row $rowNumber - header/data mismatch");
            }
            
            $row['row_number'] = $rowNumber;
            $csvData[] = $row;
        }
        
        fclose($handle);
    } else {
        throw new Exception('Unable to read CSV file');
    }
    
    return [
        'headers' => $headers,
        'rows' => $csvData,
        'total_rows' => count($csvData)
    ];
}

function validatePPMData($csvData) {
    global $fnMain;
    
    $headers = $csvData['headers'];
    $rows = $csvData['rows'];
    
    // Debug: Log the detected headers
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 'Detected headers: ' . json_encode($headers));
    
    // Required columns for PPM import
    $requiredColumns = [
        'PPM Task No.',
        'Asset Code', 
        'Assigned Technician',
        'Task Description',
        'Next Due Date'
    ];
    
    // Debug: Check each required column
    foreach ($requiredColumns as $col) {
        $found = in_array($col, $headers);
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Required column '$col' found: " . ($found ? 'YES' : 'NO'));
    }
    
    // Check for required columns
    $missingColumns = [];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $headers)) {
            $missingColumns[] = $col;
        }
    }
    
    $validRows = [];
    $errorRows = [];
    $warnings = [];
    $errors = [];
    
    if (empty($missingColumns)) {
        // Validate each row
        foreach ($rows as $row) {
            $rowErrors = [];
            $rowWarnings = [];
            
            // Validate PPM Task No.
            if (empty(trim($row['PPM Task No.'] ?? ''))) {
                $rowErrors[] = 'PPM Task No. is required';
            }
            
            // Validate Asset Code
            if (empty(trim($row['Asset Code'] ?? ''))) {
                $rowErrors[] = 'Asset Code is required';
            }
            
            // Validate Assigned Technician
            if (empty(trim($row['Assigned Technician'] ?? ''))) {
                $rowErrors[] = 'Assigned Technician is required';
            }
            
            // Validate Task Description
            if (empty(trim($row['Task Description'] ?? ''))) {
                $rowErrors[] = 'Task Description is required';
            }
            
            // Validate Next Due Date
            $dueDate = trim($row['Next Due Date'] ?? '');
            if (empty($dueDate)) {
                $rowErrors[] = 'Next Due Date is required';
            } elseif (!strtotime($dueDate)) {
                $rowErrors[] = 'Invalid Next Due Date format';
            }
            
            if (empty($rowErrors)) {
                $validRows[] = $row;
            } else {
                $errorRows[] = array_merge($row, ['errors' => $rowErrors]);
                foreach ($rowErrors as $error) {
                    $errors[] = "Row {$row['row_number']}: $error";
                }
            }
            
            if (!empty($rowWarnings)) {
                foreach ($rowWarnings as $warning) {
                    $warnings[] = "Row {$row['row_number']}: $warning";
                }
            }
        }
    } else {
        foreach ($missingColumns as $col) {
            $errors[] = "Missing required column: $col";
        }
    }
    
    return [
        'headers' => $headers,
        'total_rows' => count($rows),
        'valid_rows' => count($validRows),
        'error_rows' => count($errorRows),
        'warning_rows' => 0,
        'can_proceed' => count($validRows) > 0 && empty($missingColumns),
        'errors' => $errors,
        'warnings' => $warnings,
        'sample_valid' => array_slice($validRows, 0, 5),
        'valid_data' => $validRows, // Add the actual valid rows data
        'preview' => [
            'total_rows' => count($rows),
            'valid_rows' => count($validRows),
            'error_rows' => count($errorRows),
            'warning_rows' => 0
        ],
        'suggestions' => [
            'available_assets_api' => 'GET api/ppm_import.php?action=get_sample_assets',
            'available_users_api' => 'GET api/ppm_import.php?action=get_sample_users',
            'note' => 'Use Asset Code column with existing asset_no values from ast_asset table'
        ]
    ];
}

function executePPMImport($validRows) {
    global $fnMain;
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $importedTasks = [];
    
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 
        "Starting PPM import for " . count($validRows) . " tasks");
    
    foreach ($validRows as $row) {
        try {
            // Extract and validate data from CSV row
            $ppmTaskNo = trim($row['PPM Task No.']);
            $assetCode = trim($row['Asset Code']);
            $assignedTechnician = trim($row['Assigned Technician']);
            $taskDescription = trim($row['Task Description']);
            $nextDueDate = date('Y-m-d', strtotime(trim($row['Next Due Date'])));
            
            // Additional fields from CSV
            $estimatedDuration = floatval($row['Estimated Duration (Hours)'] ?? 0);
            $workInstructions = trim($row['Work Instructions'] ?? '');
            $requiredTools = trim($row['Required Tools'] ?? '');
            $requiredParts = trim($row['Required Parts'] ?? '');
            $completionCriteria = trim($row['Completion Criteria'] ?? '');
            
            $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                "Processing PPM: $ppmTaskNo, Asset: $assetCode, Technician: $assignedTechnician");
            
            // Check if PPM task already exists
            $existingTask = DbMysql::select('ppm_task', array('ppm_task_no' => $ppmTaskNo));
            if (!empty($existingTask)) {
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "PPM task {$ppmTaskNo} already exists - skipping");
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => 'PPM Task No. already exists in database'
                ];
                continue;
            }
            
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "PPM task {$ppmTaskNo} not found - proceeding with import");
            
            // Get asset ID from asset code
            $asset = DbMysql::select('ast_asset', array('asset_no' => $assetCode));
            if (empty($asset)) {
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => "Asset with code '$assetCode' not found in database"
                ];
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Asset not found: {$assetCode}");
                continue;
            }
            $assetId = $asset['asset_id'];
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found asset ID: {$assetId} for code: {$assetCode}");
            
            // Get user ID from technician name/username
            $technician = DbMysql::select('sys_user', array('user_name' => $assignedTechnician));
            if (empty($technician)) {
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => "Technician '$assignedTechnician' not found in database"
                ];
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Technician not found: {$assignedTechnician}");
                continue;
            }
            $technicianId = $technician['user_id'];
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found technician ID: {$technicianId} for: {$assignedTechnician}");
            
            // Use a default ppm_id of 1 since this is required but we don't have enough schema info
            $ppmId = 1; // Default value - you may need to adjust this based on your system
            
            // Generate transaction ID as integer - let's use a simple incrementing number
            $transactionId = time() + $successCount; // Unix timestamp + counter for uniqueness
            
            // Convert estimated duration from hours to time format
            $estimatedHours = floatval($estimatedDuration);
            $hours = floor($estimatedHours);
            $minutes = round(($estimatedHours - $hours) * 60);
            $minExecTime = sprintf('%02d:%02d:00', $hours, $minutes);
            $maxExecTime = sprintf('%02d:%02d:00', $hours + 1, $minutes); // Add 1 hour buffer
            
            // Prepare PPM task data for insertion (only using actual columns)
            $ppmTaskData = [
                'ppm_task_no' => $ppmTaskNo,
                'ppm_task_schedule_date' => $nextDueDate,
                'ppm_id' => $ppmId,
                'ppm_task_guideline' => $workInstructions,
                'ppm_task_is_parts' => (!empty($requiredParts) && $requiredParts !== 'None') ? 1 : 0,
                'ppm_task_is_additional_report' => 0,
                'ppm_task_refer_to' => $completionCriteria,
                'ppm_task_remark' => $taskDescription,
                'transaction_id' => $transactionId,
                'ppm_task_is_scheduled' => 1,
                'ppm_task_assigned_to' => $technicianId,
                'ppm_task_min_exec_time' => $minExecTime,
                'ppm_task_max_exec_time' => $maxExecTime,
                'ppm_task_max_assistant' => 1,
                'ppm_task_is_group_executed' => 0,
                'ppm_task_status' => 1 // Status: Scheduled
            ];
            
            // Debug: Log the data we're about to insert
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "PPM Task Data: " . json_encode($ppmTaskData));
            
            // Insert PPM task
            $ppmTaskId = DbMysql::insert('ppm_task', $ppmTaskData);
            
            if ($ppmTaskId > 0) {
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Successfully inserted PPM task with ID: {$ppmTaskId}");
                
                $successCount++;
                $importedTasks[] = [
                    'ppm_task_id' => $ppmTaskId,
                    'ppm_task_no' => $ppmTaskNo,
                    'asset_code' => $assetCode,
                    'technician' => $assignedTechnician,
                    'due_date' => $nextDueDate,
                    'status' => 'Imported Successfully'
                ];
                
                $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                    "Successfully imported PPM: $ppmTaskNo (ID: $ppmTaskId)");
            } else {
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => 'Failed to insert PPM task into database'
                ];
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Failed to insert PPM task: {$ppmTaskNo}");
            }
            
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = [
                'row' => $row['row_number'],
                'ppm_task_no' => $ppmTaskNo ?? 'Unknown',
                'message' => $e->getMessage()
            ];
            
            $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                "Error importing PPM {$ppmTaskNo}: " . $e->getMessage());
        }
    }
    
    // Log final summary with error details
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 
        "PPM import completed: $successCount successful, $errorCount errors");
        
    if (!empty($errors)) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, 
            "Import errors: " . json_encode($errors));
    }
    
    return [
        'success' => $successCount > 0,
        'summary' => [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_processed' => count($validRows),
            'errors' => $errors,
            'imported_tasks' => array_slice($importedTasks, 0, 10), // Show first 10
            'import_date' => date('Y-m-d H:i:s'),
            'message' => $successCount > 0 
                ? "Successfully imported $successCount PPM tasks to database" 
                : "Import failed - no tasks were imported"
        ]
    ];
}

function getImportHistory() {
    global $fnMain;
    
    // Return mock history for now
    return [
        'history' => [
            [
                'import_date' => date('Y-m-d H:i:s'),
                'description' => 'PPM Tasks Import',
                'total_processed' => 0,
                'success_count' => 0,
                'error_count' => 0
            ]
        ]
    ];
}

function getSampleAssets() {
    global $fnMain;
    
    try {
        // Get first 10 assets from database for testing
        $assets = DbMysql::selectAll('ast_asset', [], 0, false, 'asset_no', 'ASC', '10');
        
        $sampleAssets = [];
        foreach ($assets as $asset) {
            $sampleAssets[] = [
                'asset_no' => $asset['asset_no'],
                'asset_name' => $asset['asset_name'] ?? 'Unknown Asset',
                'asset_type' => $asset['asset_type_id'] ?? 'Unknown Type'
            ];
        }
        
        return [
            'sample_assets' => $sampleAssets,
            'count' => count($sampleAssets),
            'message' => 'Use these asset numbers in your CSV file'
        ];
        
    } catch (Exception $e) {
        return [
            'sample_assets' => [],
            'count' => 0,
            'message' => 'Error fetching assets: ' . $e->getMessage()
        ];
    }
}

function getSampleUsers() {
    global $fnMain;
    
    try {
        // Get first 10 users from database for testing
        $users = DbMysql::selectAll('sys_user', [], 0, false, 'user_name', 'ASC', '10');
        
        $sampleUsers = [];
        foreach ($users as $user) {
            $sampleUsers[] = [
                'user_name' => $user['user_name'],
                'user_id' => $user['user_id'],
                'user_display_name' => $user['user_display_name'] ?? $user['user_name']
            ];
        }
        
        return [
            'sample_users' => $sampleUsers,
            'count' => count($sampleUsers),
            'message' => 'Use these usernames as Assigned Technician in your CSV file'
        ];
        
    } catch (Exception $e) {
        return [
            'sample_users' => [],
            'count' => 0,
            'message' => 'Error fetching users: ' . $e->getMessage()
        ];
    }
}

?>
