-- Migration: Split Request Number and Permit Number; add per-site-per-day sequence table

START TRANSACTION;

-- 1) Add ptw_request_number column (unique, nullable initially)
ALTER TABLE `ptw_permit`
  ADD COLUMN `ptw_request_number` varchar(50) NULL AFTER `ptw_permit_number`,
  ADD UNIQUE KEY `uk_ptw_request_number` (`ptw_request_number`);

-- 1b) Make ptw_permit_number nullable (it will be assigned at FM approval)
ALTER TABLE `ptw_permit`
  MODIFY `ptw_permit_number` varchar(50) NULL;

-- 2) Create per-site-per-day sequence table
CREATE TABLE IF NOT EXISTS `ptw_number_sequence` (
  `site_id` int(11) NOT NULL,
  `seq_date` date NOT NULL,
  `seq_type` enum('REQUEST','PERMIT') NOT NULL,
  `next_value` int(11) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`, `seq_date`, `seq_type`),
  KEY `idx_seq_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
