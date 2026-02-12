-- PTW (Permit to Work) Database Setup - Clean Version
-- Only creates PTW tables, doesn't modify existing tables

-- PTW Permit main table
CREATE TABLE IF NOT EXISTS `ptw_permit` (
  `ptw_permit_id` int(11) NOT NULL AUTO_INCREMENT,
  `ptw_permit_number` varchar(50) NOT NULL,
  `ptw_permit_description` text NOT NULL,
  `ptw_work_area` varchar(255) NOT NULL,
  `ptw_work_type` enum('HOT_WORK','COLD_WORK','ELECTRICAL','CONFINED_SPACE','HEIGHT_WORK','EXCAVATION','CHEMICAL','LIFTING','MECHANICAL','OTHER') NOT NULL,
  `ptw_risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
  `ptw_valid_from` datetime NOT NULL,
  `ptw_valid_to` datetime NOT NULL,
  `ptw_contractor_company` varchar(255) DEFAULT NULL,
  `ptw_remarks` text DEFAULT NULL,
  `ptw_status` enum('DRAFT','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','COMPLETED','EXPIRED','CANCELLED','REJECTED') NOT NULL DEFAULT 'DRAFT',
  `ptw_applicant_name` varchar(255) NOT NULL,
  `ptw_applicant_contact` varchar(50) DEFAULT NULL,
  `ptw_applicant_company_dept` varchar(255) DEFAULT NULL,
  `ptw_hazards` text DEFAULT NULL,
  `ptw_control_measures` text DEFAULT NULL,
  `ptw_checklist_data` json DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `ptw_supervisor_approval` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `ptw_supervisor_approved_by` int(11) DEFAULT NULL,
  `ptw_supervisor_approved_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_remarks` text DEFAULT NULL,
  `ptw_she_approval` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `ptw_she_approved_by` int(11) DEFAULT NULL,
  `ptw_she_approved_date` timestamp NULL DEFAULT NULL,
  `ptw_she_remarks` text DEFAULT NULL,
  `ptw_fm_approval` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `ptw_fm_approved_by` int(11) DEFAULT NULL,
  `ptw_fm_approved_date` timestamp NULL DEFAULT NULL,
  `ptw_fm_remarks` text DEFAULT NULL,
  `activated_by` int(11) DEFAULT NULL,
  `activated_date` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_date` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  PRIMARY KEY (`ptw_permit_id`),
  UNIQUE KEY `ptw_permit_number` (`ptw_permit_number`),
  KEY `idx_ptw_site_id` (`site_id`),
  KEY `idx_ptw_status` (`ptw_status`),
  KEY `idx_ptw_created_by` (`created_by`),
  KEY `idx_ptw_valid_period` (`ptw_valid_from`,`ptw_valid_to`),
  KEY `idx_ptw_supervisor_approval` (`ptw_supervisor_approval`),
  KEY `idx_ptw_she_approval` (`ptw_she_approval`),
  KEY `idx_ptw_fm_approval` (`ptw_fm_approval`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PTW Workers table
CREATE TABLE IF NOT EXISTS `ptw_worker` (
  `ptw_worker_id` int(11) NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int(11) NOT NULL,
  `worker_name` varchar(255) NOT NULL,
  `worker_contact_number` varchar(50) DEFAULT NULL,
  `worker_role` varchar(100) DEFAULT NULL,
  `worker_is_certified` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ptw_worker_id`),
  KEY `idx_ptw_worker_permit_id` (`ptw_permit_id`),
  CONSTRAINT `fk_ptw_worker_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PTW Status History table (for workflow tracking)
CREATE TABLE IF NOT EXISTS `ptw_status_history` (
  `ptw_status_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int(11) NOT NULL,
  `action_type` enum('CREATED','SUBMITTED','SUPERVISOR_APPROVED','SUPERVISOR_REJECTED','SHE_APPROVED','SHE_REJECTED','FM_APPROVED','FM_REJECTED','ACTIVATED','COMPLETED','CANCELLED','REJECTED') NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `remarks` text DEFAULT NULL,
  `action_by` int(11) NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ptw_status_history_id`),
  KEY `idx_ptw_status_permit_id` (`ptw_permit_id`),
  KEY `idx_ptw_status_action_type` (`action_type`),
  KEY `idx_ptw_status_action_date` (`action_date`),
  CONSTRAINT `fk_ptw_status_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PTW Supporting Documents table
CREATE TABLE IF NOT EXISTS `ptw_document` (
  `ptw_document_id` int(11) NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_path` varchar(500) NOT NULL,
  `document_size` int(11) DEFAULT NULL,
  `document_mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ptw_document_id`),
  KEY `idx_ptw_document_permit_id` (`ptw_permit_id`),
  CONSTRAINT `fk_ptw_document_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PTW Approval Log table (for audit trail)
CREATE TABLE IF NOT EXISTS `ptw_approval_log` (
  `ptw_approval_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int(11) NOT NULL,
  `approval_type` enum('SUPERVISOR','SHE','FM','ACTIVATION','COMPLETION','CANCELLATION') NOT NULL,
  `previous_status` varchar(50) NOT NULL,
  `new_status` varchar(50) NOT NULL,
  `remarks` text DEFAULT NULL,
  `approved_by` int(11) NOT NULL,
  `approved_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ptw_approval_log_id`),
  KEY `idx_ptw_approval_permit_id` (`ptw_permit_id`),
  CONSTRAINT `fk_ptw_approval_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
