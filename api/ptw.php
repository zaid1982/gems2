<?php
/**
 * PTW (Permit to Work) Module - Main API Endpoint
 * Handles creating and retrieving PTW permits
 * Following GEMS2 standards and patterns
 * 
 * @author GEMS2 Development Team
 * @created August 5, 2025
 */

require_once 'function/f_general.php';
require_once 'function/f_email.php';
require_once 'class/PtwPermit.php';

// Initialize response structure (GEMS2 standard)
$form_data = array();
$form_data['success'] = false;
$form_data['result'] = array();
$form_data['errmsg'] = '';

// Transaction flag for proper rollback handling
$is_transaction = false;

try {
    // GEMS2 Standard: JWT Authentication required for all API calls
    $jwt_data = checkJwt();
    $userId = $jwt_data->userId;
    $userRole = $jwt_data->roleId;
    $userSite = $jwt_data->siteId;
    
    // Get request method
    $request_method = $_SERVER['REQUEST_METHOD'];
    
    // Initialize function classes following GEMS2 pattern
    $fn_general = new General();
    $fn_email = new Email();
    $fn_ptw = new PtwPermit($userId, true);
    
    // Parse URL for RESTful endpoints
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Remove 'api' and 'ptw.php' from path to get parameters
    $urlArr = array_slice($pathParts, array_search('ptw.php', $pathParts) + 1);
    
    if ('GET' === $request_method) {
        // Handle GET requests - retrieving data
        
        if (empty($urlArr[0])) {
            // GET /api/ptw.php - Get all permits for current user
            $result = $fn_ptw->getPtwList($userId, $userSite, $userRole);
        } 
        else if ($urlArr[0] === 'pending') {
            // GET /api/ptw.php/pending - Get permits pending approval
            $result = $fn_ptw->getPendingApprovals($userId, $userRole);
        }
        else if ($urlArr[0] === 'active') {
            // GET /api/ptw.php/active - Get active permits
            $result = $fn_ptw->getActivePermits($userId, $userSite);
        }
        else if ($urlArr[0] === 'expired') {
            // GET /api/ptw.php/expired - Get expired permits
            $result = $fn_ptw->getExpiredPermits($userId, $userSite);
        }
        else if (is_numeric($urlArr[0])) {
            // GET /api/ptw.php/{id} - Get specific permit details
            $ptwPermitId = intval($urlArr[0]);
            $result = $fn_ptw->getPtwDetails($ptwPermitId, $userId, $userRole);
        }
        else {
            throw new Exception('[' . __LINE__ . '] - Invalid GET request path');
        }
        
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        // Handle POST requests - creating new data
        
        // GEMS2 Standard: Role-based access control
        // Only allow Supervisors, Engineers, and Managers to create PTW
        if (!in_array($userRole, ['2', '3', '4'])) { // Manager, Supervisor, Engineer
            throw new Exception('[' . __LINE__ . '] - Insufficient permissions to create PTW', 403);
        }
        
        // Get POST data
        $postData = $_POST;
        
        if (empty($urlArr[0])) {
            // POST /api/ptw.php - Create new PTW permit
            
            // GEMS2 Standard: Start transaction for all write operations
            DbMysql::beginTransaction();
            $is_transaction = true;
            
            // Input validation following GEMS2 patterns
            $fn_ptw->checkEmptyString($postData['ptwPermitDescription'] ?? '', 'Permit Description');
            $fn_ptw->checkEmptyString($postData['ptwWorkArea'] ?? '', 'Work Area');
            $fn_ptw->checkEmptyString($postData['ptwWorkType'] ?? '', 'Work Type');
            
            // Validate date ranges
            $validFrom = $postData['ptwValidFrom'] ?? '';
            $validTo = $postData['ptwValidTo'] ?? '';
            
            if (empty($validFrom) || empty($validTo)) {
                throw new Exception('[' . __LINE__ . '] - Valid From and Valid To dates are required');
            }
            
            if (strtotime($validFrom) >= strtotime($validTo)) {
                throw new Exception('[' . __LINE__ . '] - Valid To date must be after Valid From date');
            }
            
            // Generate unique PTW number following GEMS2 pattern
            $ptwNumber = $fn_ptw->createPtwNumber($userId);
            
            // Prepare data for insertion
            $permitData = array(
                'ptwPermitNumber' => $ptwNumber,
                'ptwPermitDescription' => $postData['ptwPermitDescription'],
                'ptwWorkArea' => $postData['ptwWorkArea'],
                'ptwWorkType' => $postData['ptwWorkType'],
                'ptwRiskLevel' => $postData['ptwRiskLevel'] ?? 'MEDIUM',
                'ptwValidFrom' => $validFrom,
                'ptwValidTo' => $validTo,
                'ptwRequestedBy' => $userId,
                
                // New applicant fields
                'ptwApplicantName' => $postData['ptwApplicantName'] ?? null,
                'ptwApplicantContact' => $postData['ptwApplicantContact'] ?? null,
                'ptwApplicantCompanyDept' => $postData['ptwApplicantCompanyDept'] ?? null,
                'ptwWorkDuration' => $postData['ptwWorkDuration'] ?? null,
                
                'ptwContractorCompany' => $postData['ptwContractorCompany'] ?? null,
                'ptwRemarks' => $postData['ptwRemarks'] ?? null,
                
                // JSON checklist data
                'ptwChecklistHotWork' => !empty($postData['ptwChecklistHotWork']) ? $postData['ptwChecklistHotWork'] : null,
                'ptwChecklistColdWork' => !empty($postData['ptwChecklistColdWork']) ? $postData['ptwChecklistColdWork'] : null,
                'ptwChecklistConfinedSpace' => !empty($postData['ptwChecklistConfinedSpace']) ? $postData['ptwChecklistConfinedSpace'] : null,
                'ptwHazardChecklist' => !empty($postData['ptwHazardChecklist']) ? $postData['ptwHazardChecklist'] : null,
                'ptwDeclarationChecklist' => !empty($postData['ptwDeclarationChecklist']) ? $postData['ptwDeclarationChecklist'] : null,
                
                'siteId' => $userSite,
                'ptwOverallStatus' => 'DRAFT',
                'createdBy' => $userId
            );
            
            // Create the permit
            $ptwPermitId = $fn_ptw->createPermit($permitData);
            
            // Add workers if provided
            if (!empty($postData['workers']) && is_array($postData['workers'])) {
                foreach ($postData['workers'] as $worker) {
                    if (!empty($worker['workerName'])) {
                        $fn_ptw->addWorker($ptwPermitId, $worker, $userId);
                    }
                }
            }
            
            // Log audit trail following GEMS2 standard
            $fn_general->save_audit('200', $userId, 'PTW Created: ' . $ptwNumber);
            
            // GEMS2 Standard: Commit transaction
            DbMysql::commit();
            
            $result = array(
                'ptwPermitId' => $ptwPermitId,
                'ptwPermitNumber' => $ptwNumber,
                'message' => 'PTW permit created successfully'
            );
            
            $form_data['result'] = $result;
            $form_data['errmsg'] = 'PTW permit created successfully';
        }
        else {
            throw new Exception('[' . __LINE__ . '] - Invalid POST request path');
        }
        
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        // Handle PUT requests - updating existing data
        
        if (!is_numeric($urlArr[0])) {
            throw new Exception('[' . __LINE__ . '] - PTW Permit ID required for update');
        }
        
        $ptwPermitId = intval($urlArr[0]);
        
        // Get PUT data
        parse_str(file_get_contents("php://input"), $putData);
        
        // GEMS2 Standard: Start transaction for updates
        DbMysql::beginTransaction();
        $is_transaction = true;
        
        if (isset($urlArr[1]) && $urlArr[1] === 'submit') {
            // PUT /api/ptw.php/{id}/submit - Submit PTW for approval
            $result = $fn_ptw->submitForApproval($ptwPermitId, $userId, $userRole);
            
            // Log audit trail
            $permitDetails = $fn_ptw->getPtwDetails($ptwPermitId, $userId, $userRole);
            $fn_general->save_audit('201', $userId, 'PTW Submitted for Approval: ' . $permitDetails['ptwPermitNumber']);
            
            // Send notification emails
            $fn_email->setup_email($userId, 1, array(
                'ptw_number' => $permitDetails['ptwPermitNumber'],
                'work_area' => $permitDetails['ptwWorkArea']
            ));
        }
        else {
            // PUT /api/ptw.php/{id} - Update PTW details
            $result = $fn_ptw->updatePermit($ptwPermitId, $putData, $userId, $userRole);
            
            // Log audit trail
            $fn_general->save_audit('202', $userId, 'PTW Updated: ID ' . $ptwPermitId);
        }
        
        DbMysql::commit();
        
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        // Handle DELETE requests - soft delete following GEMS2 pattern
        
        if (!is_numeric($urlArr[0])) {
            throw new Exception('[' . __LINE__ . '] - PTW Permit ID required for deletion');
        }
        
        $ptwPermitId = intval($urlArr[0]);
        
        // GEMS2 Standard: Check permissions for deletion
        if (!in_array($userRole, ['2', '3'])) { // Only Manager and Supervisor can delete
            throw new Exception('[' . __LINE__ . '] - Insufficient permissions to delete PTW', 403);
        }
        
        DbMysql::beginTransaction();
        $is_transaction = true;
        
        $result = $fn_ptw->deletePermit($ptwPermitId, $userId, $userRole);
        
        // Log audit trail
        $fn_general->save_audit('203', $userId, 'PTW Deleted: ID ' . $ptwPermitId);
        
        DbMysql::commit();
        
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else {
        throw new Exception('[' . __LINE__ . '] - Invalid request method');
    }

} catch (Exception $ex) {
    // GEMS2 Standard: Proper error handling with transaction rollback
    if ($is_transaction) {
        DbMysql::rollback();
    }
    
    $form_data['success'] = false;
    $form_data['errmsg'] = $ex->getMessage();
    $form_data['error_code'] = $ex->getCode();
    
    // Log error for debugging
    error_log('[PTW API Error] ' . $ex->getMessage() . ' - Line: ' . $ex->getLine() . ' - File: ' . $ex->getFile());
}

// GEMS2 Standard: Return JSON response
header('Content-Type: application/json');
echo json_encode($form_data);

// Close database connection
DbMysql::close();
?>
