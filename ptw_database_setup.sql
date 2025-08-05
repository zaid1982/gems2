-- PTW Module Database Setup
-- Created: August 5, 2025
-- Following GEMS2 standards and naming conventions

-- Use the GEMS database
USE gems;

-- 1. Update existing cli_site table to add PTW running number
ALTER TABLE cli_site 
ADD COLUMN siteRunningNoPtw INT DEFAULT 1 COMMENT 'Running number for PTW permit generation';

-- 2. PTW Permit main table
CREATE TABLE ptw_permit (
    ptwPermitId INT AUTO_INCREMENT PRIMARY KEY,
    ptwPermitNumber VARCHAR(50) NOT NULL UNIQUE COMMENT 'Auto-generated permit number: PTWLLLLYYMMDDXXXXX',
    ptwPermitDescription TEXT NOT NULL COMMENT 'Description of work to be performed',
    ptwWorkArea VARCHAR(255) NOT NULL COMMENT 'Location/area where work will be performed',
    ptwWorkType VARCHAR(100) NOT NULL COMMENT 'Type of work (Hot Work, Confined Space, etc.)',
    ptwRiskLevel ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM',
    ptwValidFrom DATETIME NOT NULL COMMENT 'Permit valid from date/time',
    ptwValidTo DATETIME NOT NULL COMMENT 'Permit expires date/time',
    ptwRequestedBy INT NOT NULL COMMENT 'User ID who requested the permit',
    ptwApplicantName VARCHAR(255) NULL COMMENT 'Name of the applicant/requester',
    ptwApplicantContact VARCHAR(50) NULL COMMENT 'Contact number of the applicant',
    ptwApplicantCompanyDept VARCHAR(255) NULL COMMENT 'Company/Department of the applicant',
    ptwWorkDuration VARCHAR(100) NULL COMMENT 'Expected duration of work',
    ptwContractorCompany VARCHAR(255) NULL COMMENT 'Contractor company name',
    ptwSupervisorApproval ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    ptwSupervisorApprovedBy INT NULL COMMENT 'Supervisor who approved',
    ptwSupervisorApprovedDate DATETIME NULL,
    ptwSheApproval ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    ptwSheApprovedBy INT NULL COMMENT 'SHE Officer who approved',
    ptwSheApprovedDate DATETIME NULL,
    ptwFmApproval ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    ptwFmApprovedBy INT NULL COMMENT 'Facility Manager who approved',
    ptwFmApprovedDate DATETIME NULL,
    -- MODIFIED: Added 'SUSPENDED' and 'EXTENDED' to the ENUM list
    ptwOverallStatus ENUM('DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'ACTIVE', 'SUSPENDED', 'EXTENDED', 'COMPLETED', 'CANCELLED', 'EXPIRED') DEFAULT 'DRAFT',
    ptwWorkStarted DATETIME NULL COMMENT 'Actual work start time',
    ptwWorkCompleted DATETIME NULL COMMENT 'Actual work completion time',
    ptwClosedBy INT NULL COMMENT 'User who closed the permit',
    ptwClosedDate DATETIME NULL,
    ptwRemarks TEXT NULL COMMENT 'Additional remarks or notes',
    -- JSON columns for detailed checklists
    ptwChecklistHotWork JSON NULL COMMENT 'Stores checkbox data from the Hot Work section',
    ptwChecklistColdWork JSON NULL COMMENT 'Stores checkbox data from the Cold Work section',
    ptwChecklistConfinedSpace JSON NULL COMMENT 'Stores checkbox data from the Confined Space section',
    ptwHazardChecklist JSON NULL COMMENT 'Stores checkbox data from the Hazardous Activities section',
    ptwDeclarationChecklist JSON NULL COMMENT 'Stores checkbox data from the Contractor Declaration & PPE section',
    siteId INT NOT NULL COMMENT 'Site where permit is issued',
    -- Standard GEMS2 audit columns
    createdBy INT NOT NULL,
    createdDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedBy INT NULL,
    modifiedDate DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('1', '0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
    
    -- Indexes for performance
    INDEX idx_ptw_permit_number (ptwPermitNumber),
    INDEX idx_ptw_permit_site (siteId),
    INDEX idx_ptw_permit_status (ptwOverallStatus),
    INDEX idx_ptw_permit_requested_by (ptwRequestedBy),
    INDEX idx_ptw_permit_valid_dates (ptwValidFrom, ptwValidTo),
    
    -- Foreign key constraints
    FOREIGN KEY (siteId) REFERENCES cli_site(siteId) ON DELETE RESTRICT,
    FOREIGN KEY (ptwRequestedBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (ptwSupervisorApprovedBy) REFERENCES sys_user(userId) ON DELETE SET NULL,
    FOREIGN KEY (ptwSheApprovedBy) REFERENCES sys_user(userId) ON DELETE SET NULL,
    FOREIGN KEY (ptwFmApprovedBy) REFERENCES sys_user(userId) ON DELETE SET NULL,
    FOREIGN KEY (ptwClosedBy) REFERENCES sys_user(userId) ON DELETE SET NULL,
    FOREIGN KEY (createdBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (modifiedBy) REFERENCES sys_user(userId) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='PTW Permit main table storing all permit details and approval status';

-- 3. PTW Workers table (workers assigned to the permit)
CREATE TABLE ptw_worker (
    ptwWorkerId INT AUTO_INCREMENT PRIMARY KEY,
    ptwPermitId INT NOT NULL COMMENT 'Reference to ptw_permit',
    workerName VARCHAR(255) NOT NULL COMMENT 'Full name of the worker',
    workerDesignation VARCHAR(100) NULL COMMENT 'Worker designation/position',
    workerIcNumber VARCHAR(50) NULL COMMENT 'IC/Passport number',
    workerPhoneNumber VARCHAR(20) NULL COMMENT 'Contact phone number',
    workerCompany VARCHAR(255) NULL COMMENT 'Company the worker belongs to',
    workerTrade VARCHAR(100) NULL COMMENT 'Worker trade/specialization',
    workerCertification VARCHAR(255) NULL COMMENT 'Relevant certifications',
    workerRole ENUM('LEAD', 'WORKER', 'OBSERVER') DEFAULT 'WORKER' COMMENT 'Role in the work team',
    -- Standard GEMS2 audit columns
    createdBy INT NOT NULL,
    createdDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedBy INT NULL,
    modifiedDate DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('1', '0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
    
    -- Indexes
    INDEX idx_ptw_worker_permit (ptwPermitId),
    INDEX idx_ptw_worker_name (workerName),
    INDEX idx_ptw_worker_ic (workerIcNumber),
    
    -- Foreign key constraints
    FOREIGN KEY (ptwPermitId) REFERENCES ptw_permit(ptwPermitId) ON DELETE CASCADE,
    FOREIGN KEY (createdBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (modifiedBy) REFERENCES sys_user(userId) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='PTW Workers assigned to permits';

-- 4. PTW Documents table (attachments and documents)
CREATE TABLE ptw_document (
    ptwDocumentId INT AUTO_INCREMENT PRIMARY KEY,
    ptwPermitId INT NOT NULL COMMENT 'Reference to ptw_permit',
    documentName VARCHAR(255) NOT NULL COMMENT 'Original document name',
    documentPath VARCHAR(500) NOT NULL COMMENT 'File path on server',
    documentType ENUM('RISK_ASSESSMENT', 'METHOD_STATEMENT', 'DRAWING', 'CERTIFICATE', 'PHOTO', 'OTHER') DEFAULT 'OTHER',
    documentSize INT NULL COMMENT 'File size in bytes',
    documentMimeType VARCHAR(100) NULL COMMENT 'MIME type of the file',
    documentDescription TEXT NULL COMMENT 'Description of the document',
    uploadedBy INT NOT NULL COMMENT 'User who uploaded the document',
    uploadedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Standard GEMS2 audit columns
    createdBy INT NOT NULL,
    createdDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedBy INT NULL,
    modifiedDate DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('1', '0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
    
    -- Indexes
    INDEX idx_ptw_document_permit (ptwPermitId),
    INDEX idx_ptw_document_type (documentType),
    INDEX idx_ptw_document_uploaded_by (uploadedBy),
    
    -- Foreign key constraints
    FOREIGN KEY (ptwPermitId) REFERENCES ptw_permit(ptwPermitId) ON DELETE CASCADE,
    FOREIGN KEY (uploadedBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (createdBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (modifiedBy) REFERENCES sys_user(userId) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='PTW Documents and attachments';

-- 5. PTW Status History table (audit trail of status changes)
CREATE TABLE ptw_status_history (
    ptwStatusHistoryId INT AUTO_INCREMENT PRIMARY KEY,
    ptwPermitId INT NOT NULL COMMENT 'Reference to ptw_permit',
    statusFrom VARCHAR(50) NULL COMMENT 'Previous status',
    statusTo VARCHAR(50) NOT NULL COMMENT 'New status',
    actionType ENUM('CREATED', 'SUBMITTED', 'SUPERVISOR_APPROVED', 'SUPERVISOR_REJECTED', 'SHE_APPROVED', 'SHE_REJECTED', 'FM_APPROVED', 'FM_REJECTED', 'ACTIVATED', 'WORK_STARTED', 'WORK_COMPLETED', 'CLOSED', 'CANCELLED', 'EXPIRED') NOT NULL,
    actionBy INT NOT NULL COMMENT 'User who performed the action',
    actionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    actionRemarks TEXT NULL COMMENT 'Remarks for the action',
    approvalLevel ENUM('SUPERVISOR', 'SHE', 'FM') NULL COMMENT 'Which approval level this action relates to',
    -- Standard GEMS2 audit columns
    createdBy INT NOT NULL,
    createdDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedBy INT NULL,
    modifiedDate DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('1', '0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
    
    -- Indexes
    INDEX idx_ptw_status_permit (ptwPermitId),
    INDEX idx_ptw_status_action_type (actionType),
    INDEX idx_ptw_status_action_by (actionBy),
    INDEX idx_ptw_status_action_date (actionDate),
    
    -- Foreign key constraints
    FOREIGN KEY (ptwPermitId) REFERENCES ptw_permit(ptwPermitId) ON DELETE CASCADE,
    FOREIGN KEY (actionBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (createdBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (modifiedBy) REFERENCES sys_user(userId) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='PTW Status change history and audit trail';

-- 6. User Signatures table (digital signatures for approvals)
CREATE TABLE user_signatures (
    userSignatureId INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL COMMENT 'User who owns the signature',
    signatureName VARCHAR(255) NOT NULL COMMENT 'Display name for signature',
    signatureData LONGTEXT NOT NULL COMMENT 'Base64 encoded signature image data',
    signatureType ENUM('DRAWN', 'UPLOADED', 'TYPED') DEFAULT 'DRAWN',
    isDefault ENUM('1', '0') DEFAULT '0' COMMENT 'Is this the default signature for the user',
    -- Standard GEMS2 audit columns
    createdBy INT NOT NULL,
    createdDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    modifiedBy INT NULL,
    modifiedDate DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('1', '0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
    
    -- Indexes
    INDEX idx_user_signature_user (userId),
    INDEX idx_user_signature_default (userId, isDefault),
    
    -- Foreign key constraints
    FOREIGN KEY (userId) REFERENCES sys_user(userId) ON DELETE CASCADE,
    FOREIGN KEY (createdBy) REFERENCES sys_user(userId) ON DELETE RESTRICT,
    FOREIGN KEY (modifiedBy) REFERENCES sys_user(userId) ON DELETE SET NULL,
    
    -- Ensure only one default signature per user
    UNIQUE KEY unique_default_signature (userId, isDefault, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='User digital signatures for PTW approvals';

-- Insert initial test data for PTW work types (you can modify these as needed)
INSERT INTO ptw_permit (ptwPermitNumber, ptwPermitDescription, ptwWorkArea, ptwWorkType, ptwRiskLevel, ptwValidFrom, ptwValidTo, ptwRequestedBy, siteId, createdBy) 
VALUES 
('PTW_SAMPLE_001', 'Sample PTW for testing', 'Test Area', 'Hot Work', 'HIGH', NOW(), DATE_ADD(NOW(), INTERVAL 8 HOUR), 1, 1, 1)
ON DUPLICATE KEY UPDATE ptwPermitNumber = ptwPermitNumber; -- Avoid duplicate key error if already exists

-- Create views for easier data retrieval (following GEMS2 patterns)
CREATE OR REPLACE VIEW v_ptw_permit_summary AS
SELECT 
    p.ptwPermitId,
    p.ptwPermitNumber,
    p.ptwPermitDescription,
    p.ptwWorkArea,
    p.ptwWorkType,
    p.ptwRiskLevel,
    p.ptwValidFrom,
    p.ptwValidTo,
    p.ptwOverallStatus,
    p.ptwContractorCompany,
    s.siteName,
    s.siteCode,
    u_req.userFirstName as requestedByName,
    u_req.userEmail as requestedByEmail,
    u_sup.userFirstName as supervisorApprovedByName,
    u_she.userFirstName as sheApprovedByName,
    u_fm.userFirstName as fmApprovedByName,
    p.createdDate,
    p.status
FROM ptw_permit p
LEFT JOIN cli_site s ON p.siteId = s.siteId
LEFT JOIN sys_user u_req ON p.ptwRequestedBy = u_req.userId
LEFT JOIN sys_user u_sup ON p.ptwSupervisorApprovedBy = u_sup.userId
LEFT JOIN sys_user u_she ON p.ptwSheApprovedBy = u_she.userId
LEFT JOIN sys_user u_fm ON p.ptwFmApprovedBy = u_fm.userId
WHERE p.status = '1';

-- Grant appropriate permissions (adjust as per your DB user)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ptw_permit TO 'gems2_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ptw_worker TO 'gems2_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ptw_document TO 'gems2_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ptw_status_history TO 'gems2_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON user_signatures TO 'gems2_user'@'localhost';

-- Database setup complete
-- Next step: Create the API files following GEMS2 patterns