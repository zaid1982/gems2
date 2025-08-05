<?php
/**
 * PTW (Permit to Work) Business Logic Class
 * Main class handling all PTW permit operations
 * Following GEMS2 standards and patterns
 * 
 * @author GEMS2 Development Team
 * @created August 5, 2025
 */

class PtwPermit extends General {
    
    private static $tableName = 'ptw_permit';
    private static $workerTableName = 'ptw_worker';
    private static $documentTableName = 'ptw_document';
    private static $statusHistoryTableName = 'ptw_status_history';
    
    public $ptwPermitId = 0;
    public $userSite = 0;

    /**
     * Constructor following GEMS2 pattern
     * @param int $userId User ID
     * @param bool $isLogged Whether user is logged in
     */
    function __construct(int $userId = 0, bool $isLogged = false) {
        $this->userId = $userId;
        $this->isLogged = $isLogged;
        
        // Get user's site if logged in
        if ($isLogged && $userId > 0) {
            $this->userSite = DbMysql::selectColumn('sys_user', array('userId' => $userId), 'siteId', 1);
        }
    }

    /**
     * Generate unique PTW permit number following GEMS2 pattern
     * Format: PTWLLLLYYMMDDXXXXX (PTW + Site Code + Date + Sequential)
     * 
     * @param int $userId User requesting the permit
     * @return string Generated permit number
     * @throws Exception
     */
    public function createPtwNumber(int $userId): string {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($userId, 'userId');
            
            // Get user's site information
            $userSiteId = DbMysql::selectColumn('sys_user', array('userId' => $userId), 'siteId', 1);
            $site = DbMysql::select('cli_site', array('siteId' => $userSiteId), 1);
            
            if (empty($site)) {
                throw new Exception('[' . __LINE__ . '] - User site not found');
            }
            
            // Get current running number
            $runningNo = isset($site['siteRunningNoPtw']) ? $site['siteRunningNoPtw'] : 1;
            
            // Format running number with leading zeros (5 digits)
            $runningNoTemp = 100000 + $runningNo;
            $runningNoStr = substr(strval($runningNoTemp), 1);
            
            // Increment for next use
            $runningNo++;
            
            // Get current date
            $curDate = new DateTime();
            
            // Update running number in database
            DbMysql::update('cli_site', 
                array('siteRunningNoPtw' => $runningNo), 
                array('siteId' => $userSiteId)
            );
            
            // Generate permit number: PTW + Site Code + YYMMDD + Sequential
            $ptwNumber = 'PTW' . $site['siteCode'] . $curDate->format("ymd") . $runningNoStr;
            
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Generated PTW Number: ' . $ptwNumber);
            
            return $ptwNumber;
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Create new PTW permit
     * 
     * @param array $permitData Permit data to insert
     * @return int Created permit ID
     * @throws Exception
     */
    public function createPermit(array $permitData): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            // Validate required fields
            $requiredFields = ['ptwPermitNumber', 'ptwPermitDescription', 'ptwWorkArea', 'ptwWorkType', 'ptwRequestedBy', 'siteId'];
            foreach ($requiredFields as $field) {
                if (empty($permitData[$field])) {
                    throw new Exception('[' . __LINE__ . '] - Required field missing: ' . $field);
                }
            }
            
            // Insert permit record
            $ptwPermitId = DbMysql::insert(self::$tableName, $permitData);
            
            // Log status history
            $this->logStatusHistory($ptwPermitId, null, 'DRAFT', 'CREATED', $permitData['createdBy'], 'PTW permit created');
            
            return $ptwPermitId;
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Get PTW permits list based on user role and permissions
     * 
     * @param int $userId User ID
     * @param int $userSite User's site ID
     * @param string $userRole User's role ID
     * @return array List of permits
     * @throws Exception
     */
    public function getPtwList(int $userId, int $userSite, string $userRole): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            $whereConditions = array('status' => '1');
            
            // Apply site filtering based on role
            if (in_array($userRole, ['3', '4', '5'])) { // Supervisor, Engineer, Technician
                $whereConditions['siteId'] = $userSite;
            } elseif ($userRole === '6') { // Contractor - only see own permits
                $whereConditions['ptwRequestedBy'] = $userId;
            }
            // Managers (role 2) can see all permits across sites
            
            $permits = DbMysql::selectAll(self::$tableName, $whereConditions, 1);
            
            // Enrich data with related information
            foreach ($permits as &$permit) {
                $permit['siteName'] = DbMysql::selectColumn('cli_site', array('siteId' => $permit['siteId']), 'siteName', 1);
                $permit['requestedByName'] = DbMysql::selectColumn('sys_user', array('userId' => $permit['ptwRequestedBy']), 'userFirstName', 1);
                $permit['workerCount'] = DbMysql::count(self::$workerTableName, array('ptwPermitId' => $permit['ptwPermitId'], 'status' => '1'));
                $permit['documentCount'] = DbMysql::count(self::$documentTableName, array('ptwPermitId' => $permit['ptwPermitId'], 'status' => '1'));
                
                // Calculate time remaining
                $permit['timeRemaining'] = $this->calculateTimeRemaining($permit['ptwValidTo']);
                $permit['isExpired'] = strtotime($permit['ptwValidTo']) < time();
            }
            
            return $permits;
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Get detailed PTW permit information
     * 
     * @param int $ptwPermitId Permit ID
     * @param int $userId User ID for access control
     * @param string $userRole User role for access control
     * @return array Permit details
     * @throws Exception
     */
    public function getPtwDetails(int $ptwPermitId, int $userId, string $userRole): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            parent::checkEmptyInteger($ptwPermitId, 'ptwPermitId');
            
            $permit = DbMysql::select(self::$tableName, array('ptwPermitId' => $ptwPermitId, 'status' => '1'), 1);
            
            if (empty($permit)) {
                throw new Exception('[' . __LINE__ . '] - PTW permit not found');
            }
            
            // Check access permissions
            if (!$this->hasPermitAccess($permit, $userId, $userRole)) {
                throw new Exception('[' . __LINE__ . '] - Access denied to this permit', 403);
            }
            
            // Get related data
            $permit['workers'] = $this->getPermitWorkers($ptwPermitId);
            $permit['documents'] = $this->getPermitDocuments($ptwPermitId);
            $permit['statusHistory'] = $this->getPermitStatusHistory($ptwPermitId);
            
            // Get site information
            $permit['siteInfo'] = DbMysql::select('cli_site', array('siteId' => $permit['siteId']), 1);
            
            // Get user information
            $permit['requestedByInfo'] = DbMysql::select('sys_user', array('userId' => $permit['ptwRequestedBy']), 1);
            
            // Calculate time information
            $permit['timeRemaining'] = $this->calculateTimeRemaining($permit['ptwValidTo']);
            $permit['isExpired'] = strtotime($permit['ptwValidTo']) < time();
            $permit['durationHours'] = $this->calculateDurationHours($permit['ptwValidFrom'], $permit['ptwValidTo']);
            
            return $permit;
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Submit PTW for approval workflow
     * 
     * @param int $ptwPermitId Permit ID
     * @param int $userId User submitting
     * @param string $userRole User's role
     * @return array Result data
     * @throws Exception
     */
    public function submitForApproval(int $ptwPermitId, int $userId, string $userRole): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            $permit = DbMysql::select(self::$tableName, array('ptwPermitId' => $ptwPermitId, 'status' => '1'), 1);
            
            if (empty($permit)) {
                throw new Exception('[' . __LINE__ . '] - PTW permit not found');
            }
            
            // Only permit requestor can submit for approval
            if ($permit['ptwRequestedBy'] != $userId) {
                throw new Exception('[' . __LINE__ . '] - Only permit requestor can submit for approval', 403);
            }
            
            // Check if permit is in correct status
            if ($permit['ptwOverallStatus'] !== 'DRAFT') {
                throw new Exception('[' . __LINE__ . '] - Permit must be in DRAFT status to submit for approval');
            }
            
            // Validate permit has minimum required information
            $this->validatePermitForSubmission($permit);
            
            // Update status to pending approval
            $updateData = array(
                'ptwOverallStatus' => 'PENDING_APPROVAL',
                'modifiedBy' => $userId,
                'modifiedDate' => date('Y-m-d H:i:s')
            );
            
            DbMysql::update(self::$tableName, $updateData, array('ptwPermitId' => $ptwPermitId));
            
            // Log status history
            $this->logStatusHistory($ptwPermitId, 'DRAFT', 'PENDING_APPROVAL', 'SUBMITTED', $userId, 'PTW submitted for approval');
            
            return array(
                'ptwPermitId' => $ptwPermitId,
                'ptwPermitNumber' => $permit['ptwPermitNumber'],
                'newStatus' => 'PENDING_APPROVAL',
                'message' => 'PTW submitted for approval successfully'
            );
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Add worker to PTW permit
     * 
     * @param int $ptwPermitId Permit ID
     * @param array $workerData Worker information
     * @param int $userId User adding the worker
     * @return int Worker ID
     * @throws Exception
     */
    public function addWorker(int $ptwPermitId, array $workerData, int $userId): int {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            // Validate required fields
            parent::checkEmptyString($workerData['workerName'] ?? '', 'workerName');
            
            $workerData['ptwPermitId'] = $ptwPermitId;
            $workerData['createdBy'] = $userId;
            
            return DbMysql::insert(self::$workerTableName, $workerData);
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Supervisor approval
     * 
     * @param int $ptwPermitId Permit ID
     * @param int $userId Approving user
     * @param string $remarks Approval remarks
     * @return array Result
     * @throws Exception
     */
    public function supervisorApprove(int $ptwPermitId, int $userId, string $remarks = ''): array {
        try {
            parent::logDebug(__CLASS__, __FUNCTION__, __LINE__, 'Entering ' . __FUNCTION__);
            
            $permit = $this->getPermitForApproval($ptwPermitId, 'PENDING_APPROVAL');
            
            // Update supervisor approval
            $updateData = array(
                'ptwSupervisorApproval' => 'APPROVED',
                'ptwSupervisorApprovedBy' => $userId,
                'ptwSupervisorApprovedDate' => date('Y-m-d H:i:s'),
                'modifiedBy' => $userId
            );
            
            DbMysql::update(self::$tableName, $updateData, array('ptwPermitId' => $ptwPermitId));
            
            // Log status history
            $this->logStatusHistory($ptwPermitId, 'PENDING_APPROVAL', 'PENDING_APPROVAL', 'SUPERVISOR_APPROVED', $userId, $remarks);
            
            return array(
                'success' => true,
                'message' => 'Supervisor approval completed',
                'nextStep' => 'Awaiting SHE Officer approval'
            );
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Log status history following GEMS2 audit pattern
     * 
     * @param int $ptwPermitId Permit ID
     * @param string|null $statusFrom Previous status
     * @param string $statusTo New status
     * @param string $actionType Action type
     * @param int $actionBy User performing action
     * @param string $remarks Action remarks
     * @throws Exception
     */
    private function logStatusHistory(int $ptwPermitId, ?string $statusFrom, string $statusTo, string $actionType, int $actionBy, string $remarks = ''): void {
        try {
            $historyData = array(
                'ptwPermitId' => $ptwPermitId,
                'statusFrom' => $statusFrom,
                'statusTo' => $statusTo,
                'actionType' => $actionType,
                'actionBy' => $actionBy,
                'actionDate' => date('Y-m-d H:i:s'),
                'actionRemarks' => $remarks,
                'createdBy' => $actionBy
            );
            
            DbMysql::insert(self::$statusHistoryTableName, $historyData);
            
        } catch (Exception|Throwable $ex) {
            throw new Exception('[' . __CLASS__ . ':' . __FUNCTION__ . '] ' . $ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Check if user has access to view/modify permit
     * 
     * @param array $permit Permit data
     * @param int $userId User ID
     * @param string $userRole User role
     * @return bool Has access
     */
    private function hasPermitAccess(array $permit, int $userId, string $userRole): bool {
        // Managers can access all permits
        if ($userRole === '2') {
            return true;
        }
        
        // Users can access permits from their site
        if (in_array($userRole, ['3', '4', '5']) && $permit['siteId'] == $this->userSite) {
            return true;
        }
        
        // Contractors can only access their own permits
        if ($userRole === '6' && $permit['ptwRequestedBy'] == $userId) {
            return true;
        }
        
        return false;
    }

    /**
     * Get workers assigned to permit
     * 
     * @param int $ptwPermitId Permit ID
     * @return array Workers list
     */
    private function getPermitWorkers(int $ptwPermitId): array {
        return DbMysql::selectAll(self::$workerTableName, array('ptwPermitId' => $ptwPermitId, 'status' => '1'), 1);
    }

    /**
     * Get documents attached to permit
     * 
     * @param int $ptwPermitId Permit ID
     * @return array Documents list
     */
    private function getPermitDocuments(int $ptwPermitId): array {
        return DbMysql::selectAll(self::$documentTableName, array('ptwPermitId' => $ptwPermitId, 'status' => '1'), 1);
    }

    /**
     * Get permit status history
     * 
     * @param int $ptwPermitId Permit ID
     * @return array Status history
     */
    private function getPermitStatusHistory(int $ptwPermitId): array {
        $history = DbMysql::selectAll(self::$statusHistoryTableName, array('ptwPermitId' => $ptwPermitId, 'status' => '1'), 1, 'actionDate DESC');
        
        // Enrich with user names
        foreach ($history as &$entry) {
            $entry['actionByName'] = DbMysql::selectColumn('sys_user', array('userId' => $entry['actionBy']), 'userFirstName', 1);
        }
        
        return $history;
    }

    /**
     * Calculate time remaining until permit expires
     * 
     * @param string $validTo Expiry date/time
     * @return string Time remaining description
     */
    private function calculateTimeRemaining(string $validTo): string {
        $expiryTime = strtotime($validTo);
        $currentTime = time();
        
        if ($expiryTime <= $currentTime) {
            return 'EXPIRED';
        }
        
        $timeDiff = $expiryTime - $currentTime;
        $hours = floor($timeDiff / 3600);
        $minutes = floor(($timeDiff % 3600) / 60);
        
        if ($hours > 24) {
            $days = floor($hours / 24);
            return $days . ' day(s) ' . ($hours % 24) . ' hour(s)';
        } elseif ($hours > 0) {
            return $hours . ' hour(s) ' . $minutes . ' minute(s)';
        } else {
            return $minutes . ' minute(s)';
        }
    }

    /**
     * Calculate permit duration in hours
     * 
     * @param string $validFrom Start date/time
     * @param string $validTo End date/time
     * @return float Duration in hours
     */
    private function calculateDurationHours(string $validFrom, string $validTo): float {
        $startTime = strtotime($validFrom);
        $endTime = strtotime($validTo);
        
        return round(($endTime - $startTime) / 3600, 2);
    }

    /**
     * Validate permit data before submission
     * 
     * @param array $permit Permit data
     * @throws Exception
     */
    private function validatePermitForSubmission(array $permit): void {
        // Check required fields
        if (empty($permit['ptwPermitDescription'])) {
            throw new Exception('[' . __LINE__ . '] - Permit description is required');
        }
        
        if (empty($permit['ptwWorkArea'])) {
            throw new Exception('[' . __LINE__ . '] - Work area is required');
        }
        
        if (empty($permit['ptwWorkType'])) {
            throw new Exception('[' . __LINE__ . '] - Work type is required');
        }
        
        // Check validity dates
        if (strtotime($permit['ptwValidFrom']) <= time()) {
            throw new Exception('[' . __LINE__ . '] - Valid from date must be in the future');
        }
        
        if (strtotime($permit['ptwValidTo']) <= strtotime($permit['ptwValidFrom'])) {
            throw new Exception('[' . __LINE__ . '] - Valid to date must be after valid from date');
        }
        
        // Check if permit has at least one worker
        $workerCount = DbMysql::count(self::$workerTableName, array('ptwPermitId' => $permit['ptwPermitId'], 'status' => '1'));
        if ($workerCount === 0) {
            throw new Exception('[' . __LINE__ . '] - At least one worker must be assigned to the permit');
        }
    }

    /**
     * Get permit for approval operations
     * 
     * @param int $ptwPermitId Permit ID
     * @param string $expectedStatus Expected current status
     * @return array Permit data
     * @throws Exception
     */
    private function getPermitForApproval(int $ptwPermitId, string $expectedStatus): array {
        $permit = DbMysql::select(self::$tableName, array('ptwPermitId' => $ptwPermitId, 'status' => '1'), 1);
        
        if (empty($permit)) {
            throw new Exception('[' . __LINE__ . '] - PTW permit not found');
        }
        
        if ($permit['ptwOverallStatus'] !== $expectedStatus) {
            throw new Exception('[' . __LINE__ . '] - Permit status is not valid for this operation');
        }
        
        return $permit;
    }

    // TODO: Implement remaining methods for complete workflow
    // - sheApprove, sheReject, fmApprove, fmReject
    // - activatePermit, startWork, completeWork, closePermit
    // - cancelPermit, extendPermit
    // - getPendingApprovals, getActivePermits, getExpiredPermits
    // - updatePermit, deletePermit
}
?>
