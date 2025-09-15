<?php
/**
 * PTW Approval API Endpoint
 * Handles viewing PTW permits and processing approvals
 */

// Clean output buffer and suppress warnings that could break JSON
ob_start();
error_reporting(0); // Suppress all PHP errors/warnings for clean JSON output
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Include required files for database access
try {
    require_once 'library/constant.php';
    require_once 'function/db.php';
    require_once 'function/f_general.php';
    require_once 'function/f_login.php';
    require_once __DIR__ . '/function/f_ptw.php';

    // Initialize classes
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_ptw = new Class_ptw();
    $fn_login = new Class_login();

    // Set up dependencies
    $fn_general->__set('constant', $constant);
    $fn_ptw->__set('constant', $constant);
    $fn_ptw->__set('fn_general', $fn_general);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    
    // Ensure database connection is established
    Class_db::getInstance()->db_connect();
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to initialize: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // Clean any output buffer content that might interfere with JSON
    ob_clean();
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    // Build auth context
    $headersAll = function_exists('apache_request_headers') ? apache_request_headers() : [];
    $authHeaderIn = $headersAll['Authorization'] ?? $headersAll['authorization'] ?? '';
    $authContext = [ 'isAuthenticated' => false, 'user_id' => null, 'site_id' => null ];
    if (!empty($authHeaderIn)) {
        try {
            $jwt = $fn_login->check_jwt($authHeaderIn);
            $authContext['isAuthenticated'] = true;
            $authContext['user_id'] = $jwt->userId ?? null;
            if (!empty($authContext['user_id'])) {
                try {
                    $u = Class_db::getInstance()->db_select('sys_user', array('user_id'=> strval($authContext['user_id'])));
                    if (!empty($u)) { $authContext['site_id'] = $u[0]['site_id']; }
                } catch (Exception $e2) {}
            }
        } catch (Exception $e) {
            // leave unauthenticated
        }
    }
    
    switch ($action) {
        case 'get':
            handleGetPtw();
            break;
            
        case 'approve':
            handleApproval($input);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    // Clean output buffer before sending error response
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    ob_end_flush();
}

/**
 * Get PTW data for view mode
 */
function handleGetPtw() {
    global $fn_ptw, $fn_general, $authContext;
    
    $ptwId = $_GET['id'] ?? '';
    $token = $_GET['t'] ?? '';
    
    if (empty($ptwId)) {
        throw new Exception('PTW ID is required');
    }
    
    try {
        // Ensure database connection is active
        $db = Class_db::getInstance();
        
        // Basic access control: if no Authorization header, require valid token
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $isAuthenticated = !empty($authHeader) && isset($authContext['isAuthenticated']) && $authContext['isAuthenticated'] === true;
        
        // Try to get permit by ID (numeric) or permit number (string)
            $permit_data = null;

        // Enhancement: when unauthenticated and a token is provided, try to resolve the permit directly by token.
            // This avoids false "invalid token" when the id in URL is wrong or omitted.
        $resolvedByToken = false;
            if (!$isAuthenticated && !empty($token)) {
                try {
                    $byToken = $db->db_select('ptw_permit', array('public_token' => $token), 'ptw_permit_id DESC');
                    if (!empty($byToken)) {
                        $permit_data = $byToken[0];
                $resolvedByToken = true;
                        // Normalize ptwId to the resolved permit id for subsequent lookups
                        $ptwId = $permit_data['ptw_permit_id'] ?? $ptwId;
                        // Attach workers and documents as in the ID-based flow
                        $permit_id = $permit_data['ptw_permit_id'];
                        $workers = $db->db_select('ptw_worker', array('ptw_permit_id' => strval($permit_id)), 'ptw_worker_id');
                        $permit_data['workers'] = $workers;
                        $documents = $db->db_select('ptw_document', array('ptw_permit_id' => strval($permit_id)), 'ptw_document_id');
                        $permit_data['documents'] = $documents;
                    }
                } catch (Exception $e) {
                    // Fall back to ID/number lookup below
                }
            }
        
        if (!$permit_data && is_numeric($ptwId)) {
            // Get by permit ID - use ptw_permit_id which is the correct column name
            $permits = $db->db_select('ptw_permit', array(
                'ptw_permit_id' => strval($ptwId)
            ));
            
            if (!empty($permits)) {
                $permit_data = $permits[0];
                // Get the actual permit ID for worker lookup
                $permit_id = $permit_data['ptw_permit_id'] ?? $ptwId;
                
                // Get workers separately
                $workers = $db->db_select('ptw_worker', array(
                    'ptw_permit_id' => strval($permit_id)
                ), 'ptw_worker_id');
                $permit_data['workers'] = $workers;

                // Get documents for this permit
                $documents = $db->db_select('ptw_document', array(
                    'ptw_permit_id' => $permit_id
                ), 'ptw_document_id');
                $permit_data['documents'] = $documents;
            }
    } else if (!$permit_data) {
            // Search by permit number first
            $permits = $db->db_select('ptw_permit', array(
                'ptw_permit_number' => $ptwId
            ));

            if (empty($permits)) {
                // Fallback: search by request number
                $permits = $db->db_select('ptw_permit', array(
                    'ptw_request_number' => $ptwId
                ));
            }

            if (!empty($permits)) {
                $permit_data = $permits[0];
                $permit_id = $permit_data['ptw_permit_id'] ?? $ptwId;

                // Get workers separately
                $workers = $db->db_select('ptw_worker', array(
                    'ptw_permit_id' => $permit_id
                ), 'ptw_worker_id');
                $permit_data['workers'] = $workers;

                // Get documents for this permit
                $documents = $db->db_select('ptw_document', array(
                    'ptw_permit_id' => $permit_id
                ), 'ptw_document_id');
                $permit_data['documents'] = $documents;
            }
        }
        
    if (!$permit_data) {
            // Check if any permits exist to debug database connectivity
            $allPermits = $db->db_select('ptw_permit', array(), 'ptw_permit_id DESC LIMIT 3');
            
            echo json_encode([
                'success' => false,
                'message' => 'PTW not found',
                'searched_id' => $ptwId,
                'available_permits' => array_map(function($p) {
                    return [
                        'id' => $p['ptw_permit_id'], 
                        'number' => $p['ptw_permit_number']
                    ];
                }, $allPermits)
            ]);
        } else {
            // If not authenticated, validate token for this permit
            if (!$isAuthenticated) {
                try {
                    $row = $permit_data;
                    $hasSchema = array_key_exists('public_token', $row) && array_key_exists('public_link_enabled', $row);
                    if (!$hasSchema) {
                        // Backward compatibility: if schema missing, allow public view as before
                        $enabled = true; $stored = '';
                    } else {
                        $enabled = isset($row['public_link_enabled']) ? intval($row['public_link_enabled']) === 1 : false;
                        $stored = $row['public_token'] ?? '';
                    }
                    // Normalize zero-dates to null
                    $revoked_at_raw = $row['public_token_revoked_at'] ?? null;
                    $expires_at_raw = $row['public_token_expires_at'] ?? null;
                    $revoked_at = (empty($revoked_at_raw) || $revoked_at_raw === '0000-00-00 00:00:00') ? null : $revoked_at_raw;
                    $expires_at = (empty($expires_at_raw) || $expires_at_raw === '0000-00-00 00:00:00') ? null : $expires_at_raw;
                    $now = time();
                    $expired = false;
                    if (!empty($expires_at)) {
                        $ts = strtotime($expires_at);
                        $expired = ($ts !== false && $ts < $now);
                    }
                    // If schema exists but no token configured yet, treat as disabled and require auth (or allow if enabled without token)
                    $enforce = $hasSchema; // only enforce when schema present
                    // If we resolved the permit by token, consider it proof of possession and do not require 'enabled' flag.
                    $enabledOk = $resolvedByToken ? true : $enabled;
                    $tokenOk = !empty($token) && !empty($stored) && hash_equals($stored, $token);
                    if ($enforce && (!$enabledOk || $expired || !empty($revoked_at) || !$tokenOk)) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Forbidden: invalid or missing token']);
                        return;
                    }
                } catch (Exception $ignore) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Forbidden']);
                    return;
                }
            }
            // Enforce same-site restriction for authenticated, tokenless access
            if ($isAuthenticated && empty($token)) {
                $userSite = $authContext['site_id'] ?? null;
                $permitSite = $permit_data['site_id'] ?? null;
                if (!empty($permitSite) && !empty($userSite) && strval($permitSite) !== strval($userSite)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Forbidden: cross-site access denied']);
                    return;
                }
            }
            // Transform database data to frontend format
            $transformed_data = transformDatabaseToFrontend($permit_data);
            echo json_encode([
                'success' => true,
                'data' => $transformed_data,
                'source' => 'database'
            ]);
        }
        
    } catch (Exception $e) {
        // Return error with debugging information
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
            'searched_id' => $ptwId,
            'fallback_note' => 'Could not connect to database'
        ]);
    }
}

/**
 * Transform database fields to frontend expected format
 */
function transformDatabaseToFrontend($dbData) {
    // Map database fields to frontend field names based on actual database schema
    // Try to map display status (requires Class_ptw)
    $displayStatus = '';
    try {
        $mapper = new Class_ptw();
        $displayStatus = $mapper->map_display_status($dbData);
    } catch (Exception $e) { $displayStatus = ''; }

    // Normalize Cold Work checklist to canonical nested schema (handles flat cw_* maps and legacy hazard backfill)
    try {
        $normalizedCW = normalizeColdWorkChecklist(
            $dbData['ptw_checklist_cold_work'] ?? null,
            $dbData['ptw_hazard_checklist'] ?? null
        );
        if ($normalizedCW !== null) {
            $dbData['ptw_checklist_cold_work'] = json_encode($normalizedCW, JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) { /* keep raw if normalization fails */ }

    $transformed = array(
        // Basic PTW information - use correct database column names
        'id' => (!empty($dbData['ptw_permit_number']) ? $dbData['ptw_permit_number'] : (!empty($dbData['ptw_request_number']) ? $dbData['ptw_request_number'] : ($dbData['ptw_permit_id'] ?? ''))),
        'ptw_permit_id' => $dbData['ptw_permit_id'] ?? '',
        'ptw_permit_number' => $dbData['ptw_permit_number'] ?? '',
        'ptw_request_number' => $dbData['ptw_request_number'] ?? '',
        'ptw_display_status' => $displayStatus,
        'description' => $dbData['ptw_permit_description'] ?? '',
        'work_description' => $dbData['ptw_permit_description'] ?? '',
        'work_area' => $dbData['ptw_work_area'] ?? '',
        'work_type' => mapWorkType($dbData['ptw_work_type'] ?? 'Cold Work'),
        'ptw_work_type' => $dbData['ptw_work_type'] ?? '',
        'ptw_work_types' => $dbData['ptw_work_types'] ?? '',
        'work_types_selected' => $dbData['ptw_work_types'] ?? '',
        'risk_level' => strtolower($dbData['ptw_risk_level'] ?? 'medium'),
        'ptw_risk_level' => $dbData['ptw_risk_level'] ?? '',
        
        // Date fields
        'valid_from' => formatDate($dbData['ptw_valid_from']),
        'valid_to' => formatDate($dbData['ptw_valid_to']),
        'ptw_valid_from' => $dbData['ptw_valid_from'] ?? '',
        'ptw_valid_to' => $dbData['ptw_valid_to'] ?? '',
        
        // Applicant information - use correct database column names
        'applicant_name' => $dbData['ptw_applicant_name'] ?? '',
        'ptw_applicant_name' => $dbData['ptw_applicant_name'] ?? '',
        'applicant_contact' => $dbData['ptw_applicant_contact'] ?? '',
        'ptw_applicant_contact' => $dbData['ptw_applicant_contact'] ?? '',
        'applicant_department' => $dbData['ptw_applicant_company_dept'] ?? '',
        'ptw_applicant_company_dept' => $dbData['ptw_applicant_company_dept'] ?? '',
        
        // Contractor information - use correct database column names
        'contractor_company' => $dbData['ptw_contractor_company'] ?? '',
        'ptw_contractor_company' => $dbData['ptw_contractor_company'] ?? '',
        'contractor_supervisor' => $dbData['ptw_contractor_supervisor'] ?? '',
        'ptw_contractor_supervisor' => $dbData['ptw_contractor_supervisor'] ?? '',
        'contractor_name' => $dbData['ptw_contractor_name'] ?? '',
        'ptw_contractor_name' => $dbData['ptw_contractor_name'] ?? '',
        'contractor_designation' => $dbData['ptw_contractor_designation'] ?? '',
        'ptw_contractor_designation' => $dbData['ptw_contractor_designation'] ?? '',
        'contractor_date' => $dbData['ptw_contractor_date'] ?? '',
        'ptw_contractor_date' => $dbData['ptw_contractor_date'] ?? '',
        
        // Enhanced fields - use correct database column names
        'staff_nric' => $dbData['ptw_staff_nric'] ?? '',
        'ptw_staff_nric' => $dbData['ptw_staff_nric'] ?? '',
        'supervisor_contact' => $dbData['ptw_supervisor_contact'] ?? '',
        'ptw_supervisor_contact' => $dbData['ptw_supervisor_contact'] ?? '',
        'identification_no' => $dbData['ptw_identification_no'] ?? '',
        'ptw_identification_no' => $dbData['ptw_identification_no'] ?? '',
        'level' => $dbData['ptw_level'] ?? '',
        'ptw_level' => $dbData['ptw_level'] ?? '',
        'work_duration' => $dbData['ptw_work_duration'] ?? '',
        'ptw_work_duration' => $dbData['ptw_work_duration'] ?? '',
        
        // Safety information - use correct database column names
        'hazards' => $dbData['ptw_hazards'] ?? '',
        'ptw_hazards' => $dbData['ptw_hazards'] ?? '',
        'control_measures' => $dbData['ptw_control_measures'] ?? '',
        'ptw_control_measures' => $dbData['ptw_control_measures'] ?? '',
        
        // Remarks and additional info
        'remarks' => $dbData['ptw_remarks'] ?? '',
        'ptw_remarks' => $dbData['ptw_remarks'] ?? '',
        
        // Status and workflow fields
        'status' => $dbData['ptw_status'] ?? 'DRAFT',
        'ptw_status' => $dbData['ptw_status'] ?? 'DRAFT',
        
        // Approval status fields - use correct database column names
        'supervisor_approval' => $dbData['ptw_supervisor_approval'] ?? 'PENDING',
        'ptw_supervisor_approval' => $dbData['ptw_supervisor_approval'] ?? 'PENDING',
        'supervisor_comments' => $dbData['ptw_supervisor_comments'] ?? '',
        'ptw_supervisor_comments' => $dbData['ptw_supervisor_comments'] ?? '',
        'supervisor_approval_date' => formatDate($dbData['ptw_supervisor_approval_date']),
        'ptw_supervisor_approval_date' => $dbData['ptw_supervisor_approval_date'] ?? '',
        
        'she_approval' => $dbData['ptw_she_approval'] ?? 'PENDING',
        'ptw_she_approval' => $dbData['ptw_she_approval'] ?? 'PENDING',
        'she_remarks' => $dbData['ptw_she_remarks'] ?? '',
        'ptw_she_remarks' => $dbData['ptw_she_remarks'] ?? '',
        'she_approval_date' => formatDate($dbData['approved_she_date']),
        'approved_she_date' => $dbData['approved_she_date'] ?? '',
        
        'fm_approval' => $dbData['ptw_fm_approval'] ?? 'PENDING',
        'ptw_fm_approval' => $dbData['ptw_fm_approval'] ?? 'PENDING',
        'fm_remarks' => $dbData['ptw_fm_remarks'] ?? '',
        'ptw_fm_remarks' => $dbData['ptw_fm_remarks'] ?? '',
        'fm_approval_date' => formatDate($dbData['approved_fm_date']),
        'approved_fm_date' => $dbData['approved_fm_date'] ?? '',
        
    // Checklist data - return canonical-first for Cold Work
    'checklist_cold_work' => $dbData['ptw_checklist_cold_work'] ?? '',
    'ptw_checklist_cold_work' => $dbData['ptw_checklist_cold_work'] ?? '',
        'checklist_hot_work' => $dbData['ptw_checklist_hot_work'] ?? '',
        'ptw_checklist_hot_work' => $dbData['ptw_checklist_hot_work'] ?? '',
        'checklist_confined_space' => $dbData['ptw_checklist_confined_space'] ?? '',
        'ptw_checklist_confined_space' => $dbData['ptw_checklist_confined_space'] ?? '',
        'hazard_checklist' => $dbData['ptw_hazard_checklist'] ?? '',
        'ptw_hazard_checklist' => $dbData['ptw_hazard_checklist'] ?? '',
        'declaration_checklist' => $dbData['ptw_declaration_checklist'] ?? '',
        'ptw_declaration_checklist' => $dbData['ptw_declaration_checklist'] ?? '',

    // Public link metadata
    'public_token_expires_at' => $dbData['public_token_expires_at'] ?? '',
    'public_link_enabled' => isset($dbData['public_link_enabled']) ? (int)$dbData['public_link_enabled'] : null,
        'supporting_docs_checklist' => $dbData['ptw_supporting_docs_checklist'] ?? '',
        'ptw_supporting_docs_checklist' => $dbData['ptw_supporting_docs_checklist'] ?? '',
        'certificate_numbers' => $dbData['ptw_certificate_numbers'] ?? '',
        'ptw_certificate_numbers' => $dbData['ptw_certificate_numbers'] ?? '',
        'complete_form_data' => $dbData['ptw_complete_form_data'] ?? '',
        'ptw_complete_form_data' => $dbData['ptw_complete_form_data'] ?? '',
        'ptw_hazardous_activities' => $dbData['ptw_hazardous_activities'] ?? '',

        // System fields
        'site_id' => $dbData['site_id'] ?? '',
        'created_by' => $dbData['created_by'] ?? '',
        'created_date' => formatDate($dbData['created_date']),
        'updated_by' => $dbData['updated_by'] ?? '',
        'updated_date' => formatDate($dbData['updated_date']),
        
        // Additional supervisor fields
        'approved_supervisor_by' => $dbData['approved_supervisor_by'] ?? '',
        'approved_supervisor_date' => formatDate($dbData['approved_supervisor_date']),
        'ptw_supervisor_id' => $dbData['ptw_supervisor_id'] ?? '',
        'ptw_supervisor_rejection_date' => formatDate($dbData['ptw_supervisor_rejection_date']),
        
        // Additional approval fields
        'approved_she_by' => $dbData['approved_she_by'] ?? '',
        'approved_fm_by' => $dbData['approved_fm_by'] ?? '',
        
        // Lifecycle fields
        'activated_by' => $dbData['activated_by'] ?? '',
        'activated_date' => formatDate($dbData['activated_date']),
        'completed_by' => $dbData['completed_by'] ?? '',
        'completed_date' => formatDate($dbData['completed_date']),
        'cancelled_by' => $dbData['cancelled_by'] ?? '',
        'cancelled_date' => formatDate($dbData['cancelled_date']),
        'cancel_reason' => $dbData['cancel_reason'] ?? ''
    );
    
    // Handle workers data
    if (isset($dbData['workers']) && is_array($dbData['workers'])) {
        $transformed['workers'] = array();
        foreach ($dbData['workers'] as $worker) {
            $transformed['workers'][] = array(
                'name' => $worker['worker_name'] ?? '',
                'role' => $worker['worker_role'] ?? 'Worker',
                'designation' => $worker['worker_designation'] ?? 'Worker',
                'identification' => $worker['worker_identification'] ?? ($worker['worker_ic_number'] ?? ''),
                'contact_number' => $worker['worker_phone_number'] ?? '',
                'company' => $worker['worker_company'] ?? '',
                'is_certified' => isset($worker['is_certified']) ? (bool)$worker['is_certified'] : false
            );
        }
    } else {
        $transformed['workers'] = array();
    }
    
    // Handle work type specific data based on type - support multiple work types
    $workType = $transformed['work_type'];
    $workTypes = $transformed['ptw_work_types'] ?? '';
    
    // Parse multiple work types if available
    $selectedWorkTypes = [];
    if (!empty($workTypes)) {
        // Handle comma-separated work types from database
        $selectedWorkTypes = array_map('trim', explode(',', $workTypes));
    } else {
        // Fallback to single work type
        $selectedWorkTypes = [$workType];
    }
    
    // Process each work type and add corresponding data
    foreach ($selectedWorkTypes as $type) {
        $normalizedType = strtolower(trim($type));
        
        if (strpos($normalizedType, 'hot') !== false || $normalizedType === 'hot_work') {
            $transformed['hot_activities'] = parseChecklistData($dbData['ptw_checklist_hot_work'] ?? '') ?: 'welding';
            $transformed['hot_precautions'] = 'Standard hot work precautions';
        }
        
        if (strpos($normalizedType, 'confined') !== false || $normalizedType === 'confined_space') {
            $transformed['cs_activities'] = parseChecklistData($dbData['ptw_checklist_confined_space'] ?? '') ?: 'respiratoryAtmosphere,gasMonitoring';
            $transformed['cs_precautions'] = 'Standard confined space precautions';
        }
        
        if (strpos($normalizedType, 'cold') !== false || $normalizedType === 'cold_work') {
            $transformed['cold_activities'] = parseChecklistData($dbData['ptw_checklist_cold_work'] ?? '') ?: 'visualInspection,lockOutTagOut';
            $transformed['cold_precautions'] = 'Standard safety precautions';
        }
    }
    
    // If no specific work types were processed, default to cold work
    if (empty($selectedWorkTypes) || (count($selectedWorkTypes) === 1 && empty($selectedWorkTypes[0]))) {
        $transformed['cold_activities'] = parseChecklistData($dbData['ptw_checklist_cold_work'] ?? '') ?: 'visualInspection,lockOutTagOut';
        $transformed['cold_precautions'] = 'Standard safety precautions';
    }
    
    // Supporting documents - use database column directly
    $supporting_docs_data = parseChecklistData($dbData['ptw_supporting_docs_checklist'] ?? '');
    $transformed['supporting_docs'] = $supporting_docs_data ?: 'riskAssessment,methodStatement';
    
    // Handle documents if available
    if (isset($dbData['documents']) && is_array($dbData['documents'])) {
        $transformed['documents'] = array();
        foreach ($dbData['documents'] as $doc) {
            $transformed['documents'][] = array(
                'ptw_document_id' => $doc['ptw_document_id'] ?? null,
                'document_name' => $doc['document_name'] ?? ($doc['name'] ?? ''),
                'document_path' => $doc['document_path'] ?? ($doc['path'] ?? ''),
                'document_type' => $doc['document_type'] ?? '',
                'uploaded_by' => $doc['uploaded_by'] ?? null,
                'site_id' => $doc['site_id'] ?? null,
                'created_date' => $doc['created_date'] ?? null
            );
        }
    } else {
        $transformed['documents'] = array();
    }

    return $transformed;
}

/**
 * Map database work type to frontend format
 */
function mapWorkType($dbWorkType) {
    $workTypeMap = array(
        // Database ENUM values
        'HOT_WORK' => 'hot_work',
        'COLD_WORK' => 'cold_work', 
        'CONFINED_SPACE' => 'confined_space',
        'ELECTRICAL' => 'electrical',
        'HEIGHT_WORK' => 'height_work',
        'EXCAVATION' => 'excavation',
        'CHEMICAL' => 'chemical',
        'LIFTING' => 'lifting',
        'MECHANICAL' => 'mechanical',
        'OTHER' => 'cold_work',
        // Legacy string values (for backward compatibility)
        'Hot Work' => 'hot_work',
        'Cold Work' => 'cold_work',
        'Confined Space' => 'confined_space',
        'Electrical' => 'electrical',
        'Height Work' => 'height_work',
        'Excavation' => 'excavation',
        'Chemical' => 'chemical',
        'Lifting' => 'lifting',
        'Mechanical' => 'mechanical'
    );
    
    return $workTypeMap[$dbWorkType] ?? 'cold_work';
}

/**
 * Parse checklist data (could be JSON, comma-separated, or array)
 * Enhanced to handle new object format: {coldElectricalWork: "Electrical Work", ...}
 */
function parseChecklistData($checklistData) {
    if (empty($checklistData)) {
        return '';
    }
    
    // If it's already an array, convert to comma-separated string
    if (is_array($checklistData)) {
        return implode(',', array_keys(array_filter($checklistData)));
    }
    
    // Try to decode as JSON
    $decoded = json_decode($checklistData, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return extractSelectedKeys($decoded);
    }
    
    // Return as is if not JSON (could be comma-separated string)
    return $checklistData;
}

/**
 * Helper function to extract selected keys from decoded checklist data
 */
function extractSelectedKeys($decoded) {
    // Handle array of objects
    if (isset($decoded[0]) && is_array($decoded[0])) {
        return implode(',', array_column($decoded, 'value'));
    }
    
    // Handle key-value pairs
    $selected = [];
    foreach ($decoded as $key => $value) {
        if (isValueSelected($value)) {
            $selected[] = $key;
        }
    }
    return implode(',', $selected);
}

/**
 * Check if a value should be considered as selected
 */
function isValueSelected($value) {
    // Boolean true values
    if ($value === true || $value === 'true' || $value === 'yes' || $value === 1 || $value === '1') {
        return true;
    }
    
    // String values (like checkbox labels) - not empty and not false-like
    if (is_string($value) && !empty(trim($value)) && $value !== 'false' && $value !== '0') {
        return true;
    }
    
    return false;
}

/**
 * Format date for frontend
 */
function formatDate($dateString) {
    if (empty($dateString)) {
        return date('Y-m-d');
    }
    return date('Y-m-d', strtotime($dateString));
}

/**
 * Normalize Cold Work checklist JSON to canonical nested schema.
 * - If $raw is canonical already, returns decoded array
 * - If $raw is a flat cw_* map (values like labels/booleans), converts to canonical
 * - Else if $raw empty and $legacyRaw provided, derive canonical best-effort
 * Returns array on success, or null to keep original.
 */
function normalizeColdWorkChecklist($raw, $legacyRaw = null) {
    // Helper to coerce truthy values
    $b = function($v) {
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return intval($v) !== 0;
        if (is_string($v)) {
            $t = strtolower(trim($v));
            if ($t === '' || $t === 'false' || $t === '0' || $t === 'no' || $t === 'off' || $t === 'null') return false;
            return true; // any non-empty string counts as true (labels)
        }
        return !empty($v);
    };

    // Try to decode $raw if string
    if (is_array($raw)) {
        $decoded = $raw;
    } else if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) { $decoded = null; }
    } else {
        $decoded = null;
    }

    // Case A: Already canonical (has nested groups or expected fields)
    if (is_array($decoded)) {
        if (isset($decoded['electricalWork']) || isset($decoded['workingAtHeight']) || isset($decoded['excavationWork'])
            || array_key_exists('workingUnderLoad', $decoded) || array_key_exists('liftingWork', $decoded)
            || array_key_exists('chemicalHandling', $decoded) || array_key_exists('specialPrecautions', $decoded)) {
            return $decoded;
        }

        // Case B: Flat cw_* map -> convert
        $hasCwKeys = false;
        foreach ($decoded as $k => $v) { if (strpos($k, 'cw_') === 0) { $hasCwKeys = true; break; } }
        if ($hasCwKeys) {
            $cw = array(
                'electricalWork' => array(
                    'circuitIsolation' => $b($decoded['cw_el_circuitIsolation'] ?? false),
                    'lockOutTaggedOut' => $b($decoded['cw_el_lockOutTaggedOut'] ?? false),
                    'fireExtinguisher' => $b($decoded['cw_el_fireExtinguisher'] ?? false),
                    'mainSupplyCutOff' => $b($decoded['cw_el_mainSupplyCutOff'] ?? false),
                    'others' => $b($decoded['cw_el_others'] ?? false) || (isset($decoded['cw_el_othersText']) && trim(strval($decoded['cw_el_othersText'])) !== ''),
                    'othersText' => trim(strval($decoded['cw_el_othersText'] ?? ''))
                ),
                'workingAtHeight' => array(
                    'abseilingWork' => $b($decoded['cw_wh_abseilingWork'] ?? false),
                    'scaffolding' => $b($decoded['cw_wh_scaffolding'] ?? false),
                    'gondola' => $b($decoded['cw_wh_gondola'] ?? false),
                    'workingAtRooftop' => $b($decoded['cw_wh_workingAtRooftop'] ?? false),
                    'usingA' => $b($decoded['cw_wh_usingA'] ?? false) || (isset($decoded['cw_wh_usingAText']) && trim(strval($decoded['cw_wh_usingAText'])) !== ''),
                    'usingAText' => trim(strval($decoded['cw_wh_usingAText'] ?? '')),
                    'others' => $b($decoded['cw_wh_others'] ?? false) || (isset($decoded['cw_wh_othersText']) && trim(strval($decoded['cw_wh_othersText'])) !== ''),
                    'othersText' => trim(strval($decoded['cw_wh_othersText'] ?? ''))
                ),
                'excavationWork' => array(
                    'depthLt1_2m' => $b($decoded['cw_ex_depthLt1_2m'] ?? false),
                    'depthGt1_2mConfined' => $b($decoded['cw_ex_depthGt1_2mConfined'] ?? false),
                    'safeAccessEgress' => $b($decoded['cw_ex_safeAccessEgress'] ?? false),
                    'protectionFromFallingMaterial' => $b($decoded['cw_ex_protectionFromFallingMaterial'] ?? false),
                    'protectionFromEngulfment' => $b($decoded['cw_ex_protectionFromEngulfment'] ?? false),
                    'others' => $b($decoded['cw_ex_others'] ?? false) || (isset($decoded['cw_ex_othersText']) && trim(strval($decoded['cw_ex_othersText'])) !== ''),
                    'othersText' => trim(strval($decoded['cw_ex_othersText'] ?? ''))
                ),
                'workingUnderLoad' => $b($decoded['cw_workingUnderLoad'] ?? false),
                'liftingWork' => $b($decoded['cw_liftingWork'] ?? false),
                'chemicalHandling' => $b($decoded['cw_chemicalHandling'] ?? false),
                'specialPrecautions' => trim(strval($decoded['cw_specialPrecautions'] ?? ''))
            );
            return $cw;
        }
    }

    // Case C: No $raw or not parseable -> backfill from legacy hazard checklist
    if (!empty($legacyRaw)) {
        $legacy = json_decode($legacyRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($legacy)) {
            // Prefer nested cold_work if present
            if (isset($legacy['cold_work']) && is_array($legacy['cold_work'])) {
                $legacy = $legacy['cold_work'];
            }
            $cw = array(
                'electricalWork' => array(
                    'circuitIsolation' => false,
                    'lockOutTaggedOut' => false,
                    'fireExtinguisher' => false,
                    'mainSupplyCutOff' => false,
                    'others' => false,
                    'othersText' => ''
                ),
                'workingAtHeight' => array(
                    'abseilingWork' => false,
                    'scaffolding' => false,
                    'gondola' => false,
                    'workingAtRooftop' => false,
                    'usingA' => false,
                    'usingAText' => '',
                    'others' => false,
                    'othersText' => ''
                ),
                'excavationWork' => array(
                    'depthLt1_2m' => false,
                    'depthGt1_2mConfined' => false,
                    'safeAccessEgress' => false,
                    'protectionFromFallingMaterial' => false,
                    'protectionFromEngulfment' => false,
                    'others' => false,
                    'othersText' => ''
                ),
                'workingUnderLoad' => false,
                'liftingWork' => false,
                'chemicalHandling' => false,
                'specialPrecautions' => ''
            );
            $bool = function($v){ return $v === true || $v === 'true' || $v === 1 || $v === '1' || $v === 'Y' || $v === 'y'; };
            $cw['electricalWork']['circuitIsolation'] = $bool($legacy['electrical_work'] ?? $legacy['electrical'] ?? false);
            $cw['electricalWork']['lockOutTaggedOut'] = $bool($legacy['lock_out_tag_out'] ?? $legacy['loto'] ?? false);
            $cw['electricalWork']['fireExtinguisher'] = $bool($legacy['fire_extinguisher'] ?? false);
            $cw['electricalWork']['mainSupplyCutOff'] = $bool($legacy['main_supply_cut_off'] ?? false);
            $elOthers = isset($legacy['electrical_others']) ? trim(strval($legacy['electrical_others'])) : (isset($legacy['others_text']) ? trim(strval($legacy['others_text'])) : '');
            if ($elOthers !== '') { $cw['electricalWork']['others'] = true; $cw['electricalWork']['othersText'] = $elOthers; }

            $cw['workingAtHeight']['abseilingWork'] = $bool($legacy['abseiling_work'] ?? false);
            $cw['workingAtHeight']['scaffolding'] = $bool($legacy['scaffolding'] ?? false);
            $cw['workingAtHeight']['gondola'] = $bool($legacy['gondola'] ?? false);
            $cw['workingAtHeight']['workingAtRooftop'] = $bool($legacy['working_at_rooftop'] ?? false);
            $whOthers = isset($legacy['height_others']) ? trim(strval($legacy['height_others'])) : '';
            if ($whOthers !== '') { $cw['workingAtHeight']['others'] = true; $cw['workingAtHeight']['othersText'] = $whOthers; }

            $cw['excavationWork']['depthLt1_2m'] = $bool($legacy['depth_lt_1_2'] ?? false);
            $cw['excavationWork']['depthGt1_2mConfined'] = $bool($legacy['depth_gt_1_2_confined'] ?? false);
            $cw['excavationWork']['safeAccessEgress'] = $bool($legacy['safe_access_egress'] ?? false);
            $cw['excavationWork']['protectionFromFallingMaterial'] = $bool($legacy['protect_falling_material'] ?? false);
            $cw['excavationWork']['protectionFromEngulfment'] = $bool($legacy['protect_engulfment'] ?? false);
            $exOthers = isset($legacy['excavation_others']) ? trim(strval($legacy['excavation_others'])) : '';
            if ($exOthers !== '') { $cw['excavationWork']['others'] = true; $cw['excavationWork']['othersText'] = $exOthers; }

            $cw['workingUnderLoad'] = $bool($legacy['working_under_load'] ?? false);
            $cw['liftingWork'] = $bool($legacy['lifting_work'] ?? false);
            $cw['chemicalHandling'] = $bool($legacy['chemical_handling'] ?? false);
            $cw['specialPrecautions'] = trim(strval($legacy['special_precautions'] ?? $legacy['cold_work_notes'] ?? ''));

            return $cw;
        }
    }

    return null;
}

/**
 * Create sample PTW data for testing/fallback
 */
function createSamplePtwData($ptwId) {
    // TODO: Replace with actual database query
    // This is sample data for demonstration
    $sampleData = [
        'PTW-2025-001' => [
            'id' => 'PTW-2025-001',
            'applicant_name' => 'John Doe',
            'contractor_supervisor' => 'Mike Wilson',
            'contractor_company' => 'ABC Engineering Sdn Bhd',
            'work_area' => 'Building 3, Level 2, Pump Room',
            'work_type' => 'hot_work',
            'risk_level' => 'high',
            'work_description' => 'Welding repair on damaged pipeline flange connection',
            'applicant_contact' => '012-345-6789',
            'staff_nric' => 'STF001234',
            'supervisor_contact' => '012-987-6543',
            'identification_no' => 'PKK-12345-A',
            'valid_from' => '2025-08-16',
            'valid_to' => '2025-08-17',
            'level' => 'Level 2',
            'workers' => [
                ['name' => 'Ahmad Ali', 'designation' => 'Welder', 'identification' => '123456-78-9012'],
                ['name' => 'Siti Noor', 'designation' => 'Helper', 'identification' => '234567-89-0123']
            ],
            'hot_activities' => 'welding,cutting,grinding',
            'hot_precautions' => 'Fire extinguisher available, firewatch assigned, adequate ventilation',
            'supporting_docs' => 'riskAssessment,methodStatement,trainingRecords',
            'remarks' => 'High priority repair work'
        ],
        'PTW-2025-002' => [
            'id' => 'PTW-2025-002',
            'applicant_name' => 'Jane Smith',
            'contractor_supervisor' => 'David Lee',
            'contractor_company' => 'Safety First Solutions',
            'work_area' => 'Tank Farm Area A',
            'work_type' => 'confined_space',
            'risk_level' => 'critical',
            'work_description' => 'Internal tank inspection and cleaning',
            'applicant_contact' => '013-456-7890',
            'staff_nric' => 'STF002345',
            'supervisor_contact' => '013-876-5432',
            'identification_no' => 'CS-67890-B',
            'valid_from' => '2025-08-17',
            'valid_to' => '2025-08-18',
            'level' => 'Ground Level',
            'workers' => [
                ['name' => 'Raj Kumar', 'designation' => 'Confined Space Specialist', 'identification' => '345678-90-1234'],
                ['name' => 'Lisa Wong', 'designation' => 'Safety Attendant', 'identification' => '456789-01-2345']
            ],
            'cs_activities' => 'respiratoryAtmosphere,gasMonitoring,ventilation,entryAttendant',
            'cs_precautions' => 'Continuous gas monitoring, emergency rescue equipment ready, dedicated attendant',
            'supporting_docs' => 'riskAssessment,methodStatement,equipmentCerts,trainingRecords',
            'remarks' => 'Critical confined space entry - maximum safety protocols required'
        ],
        // Add sample data that matches supervisor dashboard permit numbers
        'PTW-SPV-001' => [
            'id' => 'PTW-SPV-001',
            'applicant_name' => 'Ahmad Hassan',
            'contractor_supervisor' => 'Robert Smith',
            'contractor_company' => 'Elite Engineering Solutions',
            'work_area' => 'Production Line A, Section 2',
            'work_type' => 'hot_work',
            'risk_level' => 'medium',
            'work_description' => 'Repair welding on conveyor support structure',
            'applicant_contact' => '014-567-8901',
            'staff_nric' => 'STF003456',
            'supervisor_contact' => '014-765-4321',
            'identification_no' => 'EE-12345-C',
            'valid_from' => '2025-08-18',
            'valid_to' => '2025-08-19',
            'level' => 'Ground Level',
            'workers' => [
                ['name' => 'Hassan Ali', 'designation' => 'Senior Welder', 'identification' => '567890-12-3456'],
                ['name' => 'Kumar Singh', 'designation' => 'Safety Observer', 'identification' => '678901-23-4567']
            ],
            'hot_activities' => 'welding,grinding',
            'hot_precautions' => 'Fire watch assigned, area cleared of flammables, proper ventilation',
            'supporting_docs' => 'riskAssessment,methodStatement,trainingRecords,equipmentCerts',
            'remarks' => 'Standard repair work with medium risk level'
        ]
    ];
    
    // Handle both exact matches and partial matches for compatibility
    $matchedData = null;
    if (isset($sampleData[$ptwId])) {
        $matchedData = $sampleData[$ptwId];
    } else {
        // Try to find partial matches for supervisor dashboard compatibility
        foreach ($sampleData as $key => $data) {
            if (strpos($key, $ptwId) !== false || strpos($ptwId, $key) !== false) {
                $matchedData = $data;
                break;
            }
        }
    }
    
    if (!$matchedData) {
        // If no sample data found, create generic data structure
        $matchedData = [
            'id' => $ptwId,
            'applicant_name' => 'Sample Applicant',
            'contractor_supervisor' => 'Sample Supervisor',
            'contractor_company' => 'Sample Company Ltd',
            'work_area' => 'Sample Work Area',
            'work_type' => 'cold_work',
            'risk_level' => 'medium',
            'work_description' => 'Sample work description for permit ' . $ptwId,
            'applicant_contact' => '012-000-0000',
            'staff_nric' => 'STF000000',
            'supervisor_contact' => '012-111-1111',
            'identification_no' => 'ID-000000',
            'valid_from' => date('Y-m-d'),
            'valid_to' => date('Y-m-d', strtotime('+1 day')),
            'level' => 'Ground Level',
            'workers' => [
                ['name' => 'Sample Worker', 'designation' => 'General Worker', 'identification' => '000000-00-0000']
            ],
            'cold_activities' => 'visualInspection,lockOutTagOut',
            'cold_precautions' => 'Standard safety precautions applied',
            'supporting_docs' => 'riskAssessment,methodStatement',
            'remarks' => 'Sample PTW data for testing - ID: ' . $ptwId
        ];
    }
    
    return $matchedData;
}

/**
 * Handle approval/rejection
 */
function handleApproval($input) {
    $ptwId = $input['id'] ?? '';
    $role = $input['role'] ?? '';
    $decision = $input['decision'] ?? '';
    $comments = $input['comments'] ?? '';
    $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Validate input
    if (empty($ptwId) || empty($role) || empty($decision)) {
        throw new Exception('Missing required fields');
    }
    
    if (!in_array($role, ['supervisor', 'she', 'facility_manager'])) {
        throw new Exception('Invalid role');
    }
    
    if (!in_array($decision, ['approved', 'rejected'])) {
        throw new Exception('Invalid decision');
    }
    
    if ($decision === 'rejected' && empty(trim($comments))) {
        throw new Exception('Comments are required for rejection');
    }
    
    // TODO: Replace with actual database operations
    // This would typically involve:
    // 1. Update PTW status in database
    // 2. Insert approval record
    // 3. Send notifications
    // 4. Update workflow state
    
    // Simulate database operation
    $approvalData = [
        'ptw_id' => $ptwId,
        'role' => $role,
        'decision' => $decision,
        'comments' => $comments,
        'timestamp' => $timestamp,
        'approver_name' => getCurrentUserName($role), // This would come from session/auth
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Log the approval for audit trail
    error_log('PTW Approval: ' . json_encode($approvalData));
    
    echo json_encode([
        'success' => true,
        'message' => "PTW {$decision} successfully",
        'approver_name' => getCurrentUserName($role),
        'timestamp' => $timestamp,
        'data' => $approvalData
    ]);
}

/**
 * Get current user name based on role (mock implementation)
 */
function getCurrentUserName($role) {
    // TODO: Replace with actual user authentication system
    $mockUsers = [
        'supervisor' => 'Ahmad Supervisor',
        'she' => 'Sarah SHE Officer',
        'facility_manager' => 'Robert Facility Manager'
    ];
    
    return $mockUsers[$role] ?? 'Unknown User';
}

/**
 * Database operations would go here
 * Example structure:
 * 
 * CREATE TABLE ptw_applications (
 *     id VARCHAR(50) PRIMARY KEY,
 *     applicant_name VARCHAR(255),
 *     status ENUM('draft', 'pending_supervisor', 'pending_she', 'pending_facility_manager', 'approved', 'rejected'),
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *     -- ... other fields
 * );
 * 
 * CREATE TABLE ptw_approvals (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     ptw_id VARCHAR(50),
 *     role ENUM('supervisor', 'she', 'facility_manager'),
 *     decision ENUM('approved', 'rejected'),
 *     comments TEXT,
 *     approver_name VARCHAR(255),
 *     timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     ip_address VARCHAR(45),
 *     user_agent TEXT,
 *     FOREIGN KEY (ptw_id) REFERENCES ptw_applications(id)
 * );
 */
?>
