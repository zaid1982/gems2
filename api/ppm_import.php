<?php
// PPM Import API - GEMS System
// Handles PPM task data import from Excel files

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';

// Load composer autoload for PhpSpreadsheet (required for import functionality)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

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
    
    if (!str_ends_with(strtolower($file['name']), '.xlsx') && !str_ends_with(strtolower($file['name']), '.xls')) {
        throw new Exception('Only Excel files (.xlsx, .xls) are allowed');
    }
    
    // Read and validate Excel file
    $excelData = readExcelFile($file['tmp_name']);
    $validationResult = validatePPMData($excelData);
    
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
    
    // Read and process Excel file
    $excelData = readExcelFile($file['tmp_name']);
    $validationResult = validatePPMData($excelData);
    
    if (!$validationResult['can_proceed']) {
        throw new Exception('File validation failed - cannot proceed with import');
    }
    
    // Execute the import using the actual valid data rows
    $importResult = executePPMImport($validationResult['valid_data']);
    
    return $importResult;
}

function readExcelFile($filePath) {
    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Convert worksheet to array
        $sheetData = $worksheet->toArray(null, true, true, true);
        
        if (empty($sheetData)) {
            throw new Exception('Excel file is empty');
        }
        
        // Get headers from first row
        $firstRow = reset($sheetData);
        $headers = [];
        
        foreach ($firstRow as $cellValue) {
            $headers[] = trim($cellValue ?? '');
        }
        
        // Filter out empty headers from the end
        while (count($headers) > 0 && empty(end($headers))) {
            array_pop($headers);
        }
        
        if (empty($headers)) {
            throw new Exception('No headers found in Excel file');
        }
        
        // Process data rows
        $excelData = [];
        $rowIndex = 0;
        
        foreach ($sheetData as $rowNumber => $rowData) {
            $rowIndex++;
            
            // Skip header row
            if ($rowIndex === 1) {
                continue;
            }
            
            // Convert row data to array and trim values
            $processedRow = [];
            $hasData = false;
            
            $colIndex = 0;
            foreach ($headers as $header) {
                $cellValue = isset($rowData[array_keys($rowData)[$colIndex]]) ? 
                            trim($rowData[array_keys($rowData)[$colIndex]] ?? '') : '';
                
                if (!empty($cellValue)) {
                    $hasData = true;
                }
                
                $processedRow[] = $cellValue;
                $colIndex++;
            }
            
            // Skip completely empty rows
            if (!$hasData) {
                continue;
            }
            
            // Ensure data array matches headers count
            $headerCount = count($headers);
            $dataCount = count($processedRow);
            
            if ($dataCount < $headerCount) {
                // Pad with empty strings if data has fewer columns
                $processedRow = array_pad($processedRow, $headerCount, '');
            } elseif ($dataCount > $headerCount) {
                // Trim excess columns if data has more columns
                $processedRow = array_slice($processedRow, 0, $headerCount);
            }
            
            // Combine headers with data
            $combinedRow = array_combine($headers, $processedRow);
            
            if ($combinedRow === false) {
                throw new Exception("Failed to process row $rowNumber - header/data mismatch");
            }
            
            $combinedRow['row_number'] = $rowIndex;
            $excelData[] = $combinedRow;
        }
        
        return [
            'headers' => $headers,
            'rows' => $excelData,
            'total_rows' => count($excelData)
        ];
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Failed to process row') === 0) {
            // Re-throw our custom row processing errors
            throw $e;
        }
        throw new Exception('Unable to read Excel file: ' . $e->getMessage());
    }
}

function validatePPMData($excelData) {
    global $fnMain;
    
    $headers = $excelData['headers'];
    $rows = $excelData['rows'];
    
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
    
    // Performance tracking
    $startTime = microtime(true);
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 
        "Starting PPM import for " . count($validRows) . " tasks at " . date('Y-m-d H:i:s'));
    
    // Initialize performance counters
    $dbQueryTime = 0;
    $validationTime = 0;
    $insertTime = 0;
    $cacheTime = 0;
    
    // Check database connection health and performance
    try {
        $connectionStartTime = microtime(true);
        $connectionTest = DbMysql::select('sys_user', array('user_id' => '1'));
        $connectionTime = microtime(true) - $connectionStartTime;
        
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Database connection test: " . (empty($connectionTest) ? "FAILED" : "OK") . " in " . round($connectionTime * 1000, 2) . "ms");
        
        if ($connectionTime > 1.0) {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "WARNING: Slow database connection detected (" . round($connectionTime, 2) . "s). Consider database optimization.");
        }
    } catch (Exception $e) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Database connection issue: " . $e->getMessage());
        throw new Exception("Database connection problem: " . $e->getMessage());
    }
    
    // Performance optimization: Cache assets and users to avoid repeated DB queries
    $cacheStartTime = microtime(true);
    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Building cache for assets and users...");
    
    $assetCache = [];
    $userCache = [];
    
    // Build asset cache
    try {
        $cacheStartTime = microtime(true);
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Starting asset cache build...");
        
        $allAssets = DbMysql::selectAll('ast_asset', [], 0, false, 'asset_no', 'ASC');
        $assetFetchTime = microtime(true) - $cacheStartTime;
        
        // Debug: Log the raw asset query result
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Raw asset query returned " . count($allAssets) . " records");
        
        // Debug: Log first few assets for troubleshooting
        if (!empty($allAssets)) {
            $sampleAssets = array_slice($allAssets, 0, 3);
            foreach ($sampleAssets as $i => $asset) {
                $assetNo = $asset['assetNo'] ?? $asset['asset_no'] ?? 'NULL';
                $assetName = $asset['assetName'] ?? $asset['asset_name'] ?? 'NULL';
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Sample asset $i: code='$assetNo', name='$assetName'");
            }
        } else {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "WARNING: No assets found in ast_asset table!");
        }
        
        foreach ($allAssets as $asset) {
            // Fix: Use camelCase keys as returned by the database
            $assetCode = $asset['assetNo'] ?? $asset['asset_no'] ?? '';
            if (!empty($assetCode)) {
                $assetCache[$assetCode] = $asset;
            }
        }
        
        // Debug: Log first few cache keys
        if (!empty($assetCache)) {
            $cacheKeys = array_slice(array_keys($assetCache), 0, 10);
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "First 10 asset cache keys: " . json_encode($cacheKeys));
        }
        
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Cached " . count($assetCache) . " assets in " . round($assetFetchTime, 2) . "s");
        
        if ($assetFetchTime > 2.0) {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "SLOW QUERY ALERT: Asset cache took " . round($assetFetchTime, 2) . "s. Consider adding index on ast_asset.asset_no");
        }
    } catch (Exception $e) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Error building asset cache: " . $e->getMessage());
    }
    
    // Build user cache
    try {
        $cacheStartTime = microtime(true);
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Starting user cache build...");
        
        $allUsers = DbMysql::selectAll('sys_user', [], 0, false, 'user_name', 'ASC');
        $userFetchTime = microtime(true) - $cacheStartTime;
        
        foreach ($allUsers as $user) {
            // Fix: Use camelCase keys as returned by the database
            $userName = $user['userName'] ?? $user['user_name'] ?? '';
            if (!empty($userName)) {
                $userCache[$userName] = $user;
            }
        }
        
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Cached " . count($userCache) . " users in " . round($userFetchTime, 2) . "s");
        
        if ($userFetchTime > 2.0) {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "SLOW QUERY ALERT: User cache took " . round($userFetchTime, 2) . "s. Consider adding index on sys_user.user_name");
        }
    } catch (Exception $e) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Error building user cache: " . $e->getMessage());
    }
    
    $cacheTime = microtime(true) - $cacheStartTime;
    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Total cache built in " . round($cacheTime, 2) . " seconds");
    
    // Early validation: Check if we have basic data to proceed
    if (empty($assetCache)) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "WARNING: No assets found in cache - all imports will fail");
    }
    
    if (empty($userCache)) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "WARNING: No users found in cache - all imports will fail");
    }
    
    // Performance optimization: If most assets are missing, provide early feedback
    $sampleAssetCodes = array_slice(array_map(function($row) { return trim($row['Asset Code']); }, $validRows), 0, 3);
    $foundAssets = 0;
    
    // Debug: Log the asset codes we're looking for
    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Looking for asset codes: " . json_encode($sampleAssetCodes));
    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Available asset cache has " . count($assetCache) . " entries");
    
    foreach ($sampleAssetCodes as $assetCode) {
        if (isset($assetCache[$assetCode])) {
            $foundAssets++;
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "FOUND asset code: '$assetCode' in cache");
        } else {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "NOT FOUND asset code: '$assetCode' in cache");
        }
    }
    
    if ($foundAssets == 0 && count($sampleAssetCodes) > 0) {
        $availableAssetKeys = array_slice(array_keys($assetCache), 0, 10);
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "WARNING: None of the sample assets found in cache. Available assets: " . implode(', ', $availableAssetKeys));
    }
    
    // Build existing PPM task cache to avoid repeated lookups
    $existingPpmCache = [];
    $ppmTaskNumbers = array_map(function($row) { return trim($row['PPM Task No.']); }, $validRows);
    
    if (!empty($ppmTaskNumbers)) {
        $ppmCacheStartTime = microtime(true);
        $fnMain->logDebug('API', 'ppm_import', __LINE__, "Building existing PPM task cache for " . count($ppmTaskNumbers) . " task numbers...");
        
        try {
            // Optimized: Check in smaller batches to reduce query time
            $batchSize = 5; // Process 5 at a time to reduce database load
            $batches = array_chunk($ppmTaskNumbers, $batchSize);
            $totalFound = 0;
            
            foreach ($batches as $batchIndex => $batch) {
                $batchStartTime = microtime(true);
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Processing batch " . ($batchIndex + 1) . "/" . count($batches) . " (" . count($batch) . " tasks)");
                
                foreach ($batch as $taskNo) {
                    $task = DbMysql::select('ppm_task', array('ppm_task_no' => $taskNo));
                    if (!empty($task)) {
                        $existingPpmCache[$taskNo] = $task;
                        $totalFound++;
                    }
                }
                
                $batchTime = microtime(true) - $batchStartTime;
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Batch " . ($batchIndex + 1) . " completed in " . round($batchTime, 2) . "s");
                
                // Add small delay between batches to reduce database stress
                if ($batchIndex < count($batches) - 1) {
                    usleep(100000); // 0.1 second delay
                }
            }
            
            $ppmCacheTime = microtime(true) - $ppmCacheStartTime;
            $cacheTime += $ppmCacheTime;
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found " . $totalFound . " existing PPM tasks in " . round($ppmCacheTime, 2) . "s");
        } catch (Exception $e) {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, "Error building PPM task cache: " . $e->getMessage());
        }
    }
    
    foreach ($validRows as $index => $row) {
        $rowStartTime = microtime(true);
        
        // Progress reporting every 25% or every 5 rows (whichever is smaller)
        $progressInterval = max(1, min(5, ceil(count($validRows) / 4)));
        if (($index + 1) % $progressInterval == 0 || $index == 0) {
            $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                "Processing row " . ($index + 1) . "/" . count($validRows) . " (" . round((($index + 1) / count($validRows)) * 100, 1) . "%)");
        }
            
        try {
            // Extract and validate data from Excel row
            $validationStartTime = microtime(true);
            $ppmTaskNo = trim($row['PPM Task No.']);
            $assetCode = trim($row['Asset Code']);
            $assignedTechnician = trim($row['Assigned Technician']);
            $taskDescription = trim($row['Task Description']);
            $nextDueDate = date('Y-m-d', strtotime(trim($row['Next Due Date'])));
            
            // Assume all imported tasks are Complete status
            $status = 'Complete';
            
            // Additional fields from Excel
            $estimatedDuration = floatval($row['Estimated Duration (Hours)'] ?? 0);
            $workInstructions = trim($row['Work Instructions'] ?? '');
            $requiredTools = trim($row['Required Tools'] ?? '');
            $requiredParts = trim($row['Required Parts'] ?? '');
            $completionCriteria = trim($row['Completion Criteria'] ?? '');
            
            $validationTime += (microtime(true) - $validationStartTime);
            
            // Optimize: Only log detailed info for first few rows or errors
            if ($index < 3 || ($index + 1) % 10 == 0) {
                $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                    "Processing PPM: $ppmTaskNo, Asset: $assetCode, Technician: $assignedTechnician");
            }
            
            // Check if PPM task already exists (using cache)
            if (isset($existingPpmCache[$ppmTaskNo])) {
                $existingTask = $existingPpmCache[$ppmTaskNo];
                
                // Optimize: Only log detailed info for first few duplicates
                if ($errorCount < 3) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "PPM task {$ppmTaskNo} found in cache - skipping");
                }
                
                // Get more details about the existing task with null safety
                $statusNames = [1 => 'Scheduled', 2 => 'In Progress', 3 => 'Completed', 4 => 'Cancelled'];
                $statusName = isset($existingTask['ppm_task_status']) && isset($statusNames[$existingTask['ppm_task_status']]) 
                    ? $statusNames[$existingTask['ppm_task_status']] 
                    : 'Unknown';
                
                $taskId = $existingTask['ppm_task_id'] ?? 'Unknown';
                $assignedDate = !empty($existingTask['ppm_task_time_assigned']) 
                    ? date('Y-m-d H:i', strtotime($existingTask['ppm_task_time_assigned']))
                    : 'Not set';
                $scheduleDate = !empty($existingTask['ppm_task_schedule_date']) 
                    ? $existingTask['ppm_task_schedule_date']
                    : 'Not set';
                
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => "PPM Task '{$ppmTaskNo}' already exists (ID: {$taskId}, Status: {$statusName}, Scheduled: {$scheduleDate}, Created: {$assignedDate}). Skipping import."
                ];
                
                $rowTime = microtime(true) - $rowStartTime;
                
                // Only log detailed timing for first few rows
                if ($index < 5 || $errorCount < 3) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Row {$index} processed in " . round($rowTime * 1000, 2) . "ms (duplicate - cached)");
                }
                continue;
            }
            
            // Optimize: Only log "not found" message for first few rows or every 10th row
            if ($index < 3 || ($index + 1) % 10 == 0) {
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "PPM task {$ppmTaskNo} not found - proceeding with import");
            }
            
            // Get asset ID from asset code (using cache)
            if (isset($assetCache[$assetCode])) {
                $asset = $assetCache[$assetCode];
                if ($index < 3) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found asset in cache: {$assetCode}");
                }
            } else {
                $asset = null;
                if ($index < 3 || $errorCount < 5) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Asset not found in cache: {$assetCode}");
                }
            }
            
            if (empty($asset)) {
                // Get first 5 asset codes for suggestions (only for first few errors)
                $suggestions = [];
                if ($errorCount < 3) {
                    $suggestions = array_slice(array_keys($assetCache), 0, 5);
                }
                $suggestionText = !empty($suggestions) ? " Available assets: " . implode(', ', $suggestions) : " Available assets: " . count($assetCache) . " total assets in system";
                
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => "Asset code '{$assetCode}' not found in database.{$suggestionText}"
                ];
                
                if ($errorCount < 5) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Asset not found: {$assetCode}");
                }
                
                $rowTime = microtime(true) - $rowStartTime;
                if ($index < 5) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Row {$index} processed in " . round($rowTime * 1000, 2) . "ms (asset not found)");
                }
                continue;
            }
            // Fix: Use camelCase key as returned by the database
            $assetId = $asset['assetId'] ?? $asset['asset_id'] ?? null;
            if ($index < 3) {
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found asset ID: {$assetId} for code: {$assetCode}");
            }
            
            // Get user ID from technician name/username (using cache)
            if (isset($userCache[$assignedTechnician])) {
                $technician = $userCache[$assignedTechnician];
                if ($index < 3) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found technician in cache: {$assignedTechnician}");
                }
            } else {
                $technician = null;
                if ($index < 3 || $errorCount < 5) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Technician not found in cache: {$assignedTechnician}");
                }
            }
            
            if (empty($technician)) {
                // Get first 5 usernames for suggestions (only for first few errors)
                $suggestions = [];
                if ($errorCount < 3) {
                    $suggestions = array_slice(array_keys($userCache), 0, 5);
                }
                $suggestionText = !empty($suggestions) ? " Available users: " . implode(', ', $suggestions) : " Available users: " . count($userCache) . " total users in system";
                
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => "Technician username '{$assignedTechnician}' not found in database.{$suggestionText}"
                ];
                
                if ($errorCount < 5) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Technician not found: {$assignedTechnician}");
                }
                
                $rowTime = microtime(true) - $rowStartTime;
                if ($index < 5) {
                    $fnMain->logDebug('API', 'ppm_import', __LINE__, "Row {$index} processed in " . round($rowTime * 1000, 2) . "ms (technician not found)");
                }
                continue;
            }
            // Fix: Use camelCase key as returned by the database
            $technicianId = $technician['userId'] ?? $technician['user_id'] ?? null;
            if ($index < 3) {
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Found technician ID: {$technicianId} for: {$assignedTechnician}");
            }
            
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
            $insertStartTime = microtime(true);
            $ppmTaskId = DbMysql::insert('ppm_task', $ppmTaskData);
            $insertTime += (microtime(true) - $insertStartTime);
            
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
                
                $rowTime = microtime(true) - $rowStartTime;
                $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                    "Successfully imported PPM: $ppmTaskNo (ID: $ppmTaskId) in " . round($rowTime * 1000, 2) . "ms");
            } else {
                $errorCount++;
                $errors[] = [
                    'row' => $row['row_number'],
                    'ppm_task_no' => $ppmTaskNo,
                    'message' => "Database insertion failed for PPM task '{$ppmTaskNo}'. Check database logs for detailed error information. Verify ppm_id={$ppmId} and transaction_id={$transactionId} are valid."
                ];
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Failed to insert PPM task: {$ppmTaskNo}");
                
                $rowTime = microtime(true) - $rowStartTime;
                $fnMain->logDebug('API', 'ppm_import', __LINE__, "Row {$index} processed in " . round($rowTime * 1000, 2) . "ms (insert failed)");
            }
            
        } catch (Exception $e) {
            $errorCount++;
            
            // Provide more specific error messages based on exception type
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Duplicate entry') !== false) {
                $errorMessage = "Duplicate data detected: {$errorMessage}. Check for unique constraints violation.";
            } elseif (strpos($errorMessage, 'cannot be null') !== false) {
                $errorMessage = "Required field missing: {$errorMessage}. Check Excel data completeness.";
            } elseif (strpos($errorMessage, 'Foreign key constraint') !== false) {
                $errorMessage = "Invalid reference data: {$errorMessage}. Check related asset, user, or PPM records.";
            } else {
                $errorMessage = "Import error for PPM '{$ppmTaskNo}': {$errorMessage}";
            }
            
            $errors[] = [
                'row' => $row['row_number'],
                'ppm_task_no' => $ppmTaskNo ?? 'Unknown',
                'message' => $errorMessage
            ];
            
            $rowTime = microtime(true) - $rowStartTime;
            $fnMain->logDebug('API', 'ppm_import', __LINE__, 
                "Error importing PPM {$ppmTaskNo} in " . round($rowTime * 1000, 2) . "ms: " . $e->getMessage());
        }
    }
    
    // Calculate total execution time and performance metrics
    $totalTime = microtime(true) - $startTime;
    
    // Log final summary with performance metrics
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 
        "PPM import completed: $successCount successful, $errorCount errors in " . round($totalTime, 2) . " seconds");
        
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 
        "Performance breakdown: Cache build: " . round($cacheTime, 2) . "s, DB queries: " . round($dbQueryTime, 2) . "s, Validation: " . round($validationTime, 2) . "s, Inserts: " . round($insertTime, 2) . "s");
        
    $fnMain->logDebug('API', 'ppm_import', __LINE__, 
        "Average time per row: " . round(($totalTime / count($validRows)) * 1000, 2) . "ms");
        
    if (!empty($errors)) {
        $fnMain->logDebug('API', 'ppm_import', __LINE__, 
            "Import errors summary: " . json_encode(array_map(function($error) {
                return $error['ppm_task_no'] . ': ' . $error['message'];
            }, $errors)));
    }
    
    // Determine final message
    $finalMessage = '';
    if ($successCount > 0 && $errorCount > 0) {
        $finalMessage = "Partial import completed: {$successCount} PPM tasks imported successfully, {$errorCount} tasks skipped or failed.";
    } elseif ($successCount > 0) {
        $finalMessage = "Import successful: All {$successCount} PPM tasks imported to database.";
    } elseif ($errorCount > 0) {
        $finalMessage = "Import completed with {$errorCount} issues. Review error details for specific problems.";
    } else {
        $finalMessage = "No tasks processed - check Excel file format and content.";
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
            'message' => $finalMessage
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
                'asset_no' => $asset['assetNo'] ?? $asset['asset_no'] ?? 'Unknown',
                'asset_name' => $asset['assetName'] ?? $asset['asset_name'] ?? 'Unknown Asset',
                'asset_type' => $asset['assetTypeId'] ?? $asset['asset_type_id'] ?? 'Unknown Type'
            ];
        }
        
        return [
            'sample_assets' => $sampleAssets,
            'count' => count($sampleAssets),
            'message' => 'Use these asset numbers in your Excel file'
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
                'user_name' => $user['userName'] ?? $user['user_name'] ?? 'Unknown',
                'user_id' => $user['userId'] ?? $user['user_id'] ?? 'Unknown',
                'user_display_name' => $user['userDisplayName'] ?? $user['user_display_name'] ?? ($user['userName'] ?? $user['user_name'] ?? 'Unknown')
            ];
        }
        
        return [
            'sample_users' => $sampleUsers,
            'count' => count($sampleUsers),
            'message' => 'Use these usernames as Assigned Technician in your Excel file'
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
