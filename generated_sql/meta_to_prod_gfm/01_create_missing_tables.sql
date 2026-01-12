-- Generated from docs/meta-gems.coffee -> docs/prod_gfm.coffee
-- Target: MariaDB 10.4 (XAMPP). Review before running in production.
-- Forward-only and avoids DROPs.

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;


CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `log_id` bigint(20) NOT NULL auto_increment,
  `part_id` bigint(20) NOT NULL,
  `change_type` enum('purchase','checkout','return','adjustment','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `quantity_change` int(11) NOT NULL COMMENT 'Positive for add, negative for remove',
  `quantity_before` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reference_id` bigint(20) NULL DEFAULT NULL COMMENT 'ID of related record (return_id, wo_task_parts_id, etc)',
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Type of reference (material_return, wo_checkout, etc)',
  `change_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `change_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for all inventory changes';

CREATE TABLE IF NOT EXISTS `lic_license` (
  `license_id` int(11) NOT NULL auto_increment,
  `site_id` smallint(6) NOT NULL,
  `license_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `license_start_date` date NOT NULL,
  `license_end_date` date NOT NULL,
  `upload_id` bigint(20) NULL DEFAULT NULL,
  `warning_days` int(11) NULL DEFAULT NULL,
  `warning_date` date NULL DEFAULT NULL,
  `license_status` tinyint(1) NOT NULL DEFAULT 1,
  `license_created_by` int(11) NULL DEFAULT NULL,
  `license_updated_by` int(11) NULL DEFAULT NULL,
  `license_created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `license_updated_date` datetime NULL DEFAULT NULL on update current_timestamp(),
  PRIMARY KEY (`license_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `material_returns` (
  `return_id` bigint(20) NOT NULL auto_increment,
  `wo_task_parts_id` bigint(20) NOT NULL COMMENT 'Reference to original material collection',
  `part_id` bigint(20) NOT NULL COMMENT 'Reference to parts table',
  `technician_user_id` int(11) NOT NULL COMMENT 'User who requested the return',
  `storekeeper_user_id` int(11) NULL DEFAULT NULL COMMENT 'User who confirmed the return',
  `quantity_returned` int(11) NOT NULL COMMENT 'Quantity being returned (supports partial returns)',
  `return_status` enum('pending','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending' COMMENT 'Return workflow status',
  `return_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Reason from dropdown: unused_excess, wrong_part, damaged, other',
  `return_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Optional free text remarks',
  `return_request_date` datetime NOT NULL COMMENT 'When technician submitted return',
  `return_confirmed_date` datetime NULL DEFAULT NULL COMMENT 'When storekeeper confirmed receipt',
  `return_deadline_date` datetime NULL DEFAULT NULL COMMENT 'Optional deadline for return (not enforced)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() on update current_timestamp(),
  PRIMARY KEY (`return_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Material return requests and confirmations';

CREATE TABLE IF NOT EXISTS `ppm_offline_sync_log` (
  `id` int(11) NOT NULL auto_increment,
  `ppm_task_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'PPM Task ID being synced',
  `sync_timestamp` datetime NOT NULL COMMENT 'Client-provided sync timestamp for idempotency',
  `device_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mobile device identifier',
  `user_id` int(11) NOT NULL COMMENT 'User performing the sync',
  `total_actions` int(11) NOT NULL DEFAULT 0 COMMENT 'Total number of actions in batch',
  `success_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of successful actions',
  `failed_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of failed actions',
  `request_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Full JSON request body',
  `response_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Full JSON response body',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Server timestamp when sync was processed',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks PPM offline batch sync attempts for idempotency';

CREATE TABLE IF NOT EXISTS `ptw_approval_log` (
  `ptw_approval_log_id` int(11) NOT NULL auto_increment,
  `ptw_permit_id` int(11) NOT NULL,
  `approval_type` enum('SUPERVISOR','SHE','FM','ACTIVATION','COMPLETION','CANCELLATION') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `approved_by` int(11) NOT NULL,
  `approved_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `signature_path_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `signature_sha256_snapshot` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`ptw_approval_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ptw_document` (
  `ptw_document_id` int(11) NOT NULL auto_increment,
  `ptw_permit_id` int(11) NOT NULL,
  `document_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_category` enum('SUPPORTING_DOC','CERTIFICATE','PERMIT','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'SUPPORTING_DOC',
  `is_required` tinyint(1) NULL DEFAULT 0 COMMENT 'Whether this document is mandatory',
  `document_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_size` int(11) NULL DEFAULT NULL,
  `document_mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ptw_document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ptw_number_sequence` (
  `site_id` int(11) NOT NULL,
  `seq_date` date NOT NULL,
  `seq_type` enum('REQUEST','PERMIT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `next_value` int(11) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`site_id`, `seq_date`, `seq_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ptw_permit` (
  `ptw_permit_id` int(11) NOT NULL auto_increment,
  `ptw_permit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_request_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_permit_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_type` enum('Cold Work','Hot Work','Confined Space') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `ptw_valid_from` datetime NOT NULL,
  `ptw_valid_to` datetime NOT NULL,
  `cancel_requested_by` int(11) NULL DEFAULT NULL,
  `cancel_requested_at` datetime NULL DEFAULT NULL,
  `ptw_extension_requested_to` datetime NULL DEFAULT NULL,
  `ptw_extension_requested_by` int(11) NULL DEFAULT NULL,
  `ptw_extension_requested_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_extension_requested_at` datetime NULL DEFAULT NULL,
  `ptw_contractor_company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_contractor_supervisor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Contractor Supervisor Name',
  `ptw_staff_nric` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Staff NRIC/IC Number',
  `ptw_supervisor_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Supervisor Contact Number',
  `ptw_identification_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Identification Number',
  `ptw_level` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Level/Floor',
  `ptw_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_status` enum('DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `ptw_applicant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_applicant_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_applicant_company_dept` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_work_duration` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_hazards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_control_measures` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_checklist_cold_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `ptw_checklist_hot_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `ptw_checklist_confined_space` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `ptw_hazard_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `ptw_declaration_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `ptw_supporting_docs_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'URS Supporting Documents Checklist',
  `ptw_certificate_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT 'Certificate and Permit Numbers',
  `public_token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `public_token_expires_at` datetime NULL DEFAULT NULL,
  `public_link_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `public_token_revoked_at` datetime NULL DEFAULT NULL,
  `ptw_complete_form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) NULL DEFAULT NULL,
  `updated_date` timestamp NULL DEFAULT NULL on update current_timestamp(),
  `approved_supervisor_by` int(11) NULL DEFAULT NULL,
  `approved_supervisor_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_supervisor_approval_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_id` int(11) NULL DEFAULT NULL,
  `ptw_supervisor_rejection_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `ptw_she_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_she_by` int(11) NULL DEFAULT NULL,
  `approved_she_date` timestamp NULL DEFAULT NULL,
  `ptw_she_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_fm_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_fm_by` int(11) NULL DEFAULT NULL,
  `approved_fm_date` timestamp NULL DEFAULT NULL,
  `ptw_fm_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `activated_by` int(11) NULL DEFAULT NULL,
  `activated_date` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) NULL DEFAULT NULL,
  `cancelled_date` timestamp NULL DEFAULT NULL,
  `cancel_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspend_requested_by` int(11) NULL DEFAULT NULL,
  `suspend_requested_at` datetime NULL DEFAULT NULL,
  `suspend_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspend_ncr_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspended_by` int(11) NULL DEFAULT NULL,
  `suspended_date` datetime NULL DEFAULT NULL,
  `ptw_hazardous_activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `ptw_contractor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Stores contractor representative name',
  `ptw_contractor_designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Stores contractor designation',
  `ptw_contractor_date` date NULL DEFAULT NULL COMMENT 'Stores acknowledgment date',
  PRIMARY KEY (`ptw_permit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ptw_status_history` (
  `ptw_status_history_id` int(11) NOT NULL auto_increment,
  `ptw_permit_id` int(11) NOT NULL,
  `action_type` enum('DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `new_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `action_by` int(11) NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ptw_status_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ptw_worker` (
  `ptw_worker_id` int(11) NOT NULL auto_increment,
  `ptw_permit_id` int(11) NOT NULL,
  `worker_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker_ic_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_phone_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_designation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_identification` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `is_certified` tinyint(1) NULL DEFAULT 0,
  `worker_ptw_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ptw_worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ref_space_category` (
  `space_category_id` smallint(5) unsigned NOT NULL auto_increment,
  `space_category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_category_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `space_category_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`space_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ref_space_location` (
  `space_location_id` smallint(5) unsigned NOT NULL auto_increment,
  `space_location_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_location_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `space_location_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`space_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ref_space_status` (
  `space_status_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_status_order` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `space_status_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`space_status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ref_space_type` (
  `space_type_id` smallint(5) unsigned NOT NULL auto_increment,
  `space_type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_type_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `space_type_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  `space_category_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`space_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `spc_reservation` (
  `reservation_id` int(10) unsigned NOT NULL auto_increment,
  `space_id` int(10) unsigned NOT NULL,
  `site_id` smallint(5) unsigned NOT NULL,
  `reservation_start` datetime NOT NULL,
  `reservation_end` datetime NOT NULL,
  `reservation_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RESERVED',
  `special_request` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `requested_by` int(11) NULL DEFAULT NULL,
  `requested_by_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `requested_by_contact` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `auto_approved_at` datetime NULL DEFAULT NULL,
  `canceled_by` int(11) NULL DEFAULT NULL,
  `canceled_at` datetime NULL DEFAULT NULL,
  `cancel_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT NULL on update current_timestamp(),
  PRIMARY KEY (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `spc_space` (
  `space_id` int(10) unsigned NOT NULL auto_increment,
  `site_id` smallint(5) unsigned NOT NULL,
  `space_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_location_id` smallint(5) unsigned NULL DEFAULT NULL,
  `space_category_id` smallint(5) unsigned NULL DEFAULT NULL,
  `space_type_id` smallint(5) unsigned NULL DEFAULT NULL,
  `space_area` decimal(10,2) NULL DEFAULT NULL,
  `space_capacity` smallint(5) unsigned NULL DEFAULT NULL,
  `space_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AVAILABLE',
  `space_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `space_created_by` int(11) NULL DEFAULT NULL,
  `space_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `space_updated_by` int(11) NULL DEFAULT NULL,
  `space_updated_at` datetime NULL DEFAULT NULL on update current_timestamp(),
  PRIMARY KEY (`space_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `spc_space_asset` (
  `space_asset_id` int(10) unsigned NOT NULL auto_increment,
  `space_id` int(10) unsigned NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `linked_by` int(11) NULL DEFAULT NULL,
  `linked_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`space_asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `spc_space_media` (
  `space_media_id` int(10) unsigned NOT NULL auto_increment,
  `space_id` int(10) unsigned NOT NULL,
  `upload_id` bigint(20) NOT NULL,
  `media_type` enum('PHOTO','FLOORPLAN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `media_caption` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `media_created_by` int(11) NULL DEFAULT NULL,
  `media_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`space_media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sys_nav_role_backup_20251021` (
  `nav_role_id` smallint(6) NOT NULL DEFAULT 0,
  `role_id` tinyint(4) NOT NULL,
  `nav_id` smallint(6) NOT NULL,
  `nav_second_id` smallint(6) NULL DEFAULT NULL,
  `nav_role_turn` smallint(6) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `sys_site` (
  `site_id` int(11) NOT NULL auto_increment,
  `site_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `site_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `site_running_no` int(11) NOT NULL DEFAULT 0,
  `site_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() on update current_timestamp(),
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sys_user_signature` (
  `user_id` int(11) NOT NULL,
  `signature_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Filesystem relative path to current signature image',
  `signature_sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 hash of file content for integrity',
  `mime_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image/png',
  `width` smallint(6) NULL DEFAULT NULL,
  `height` smallint(6) NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() on update current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vm_host` (
  `host_id` int(11) NOT NULL auto_increment,
  `site_id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `contact_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT NULL on update current_timestamp(),
  PRIMARY KEY (`host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `vm_visit` (
  `visit_id` bigint(20) unsigned NOT NULL auto_increment,
  `site_id` smallint(6) NOT NULL,
  `arrived_at` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ic_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `party_size` int(10) unsigned NOT NULL DEFAULT 1,
  `host_id` int(11) NULL DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `host_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('CHECKED_IN','CHECKED_OUT','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CHECKED_IN',
  `archived_at` datetime NULL DEFAULT NULL,
  `deleted_at` datetime NULL DEFAULT NULL,
  `access_card_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_via` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WEB_FORM',
  `created_by` bigint(20) unsigned NULL DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
