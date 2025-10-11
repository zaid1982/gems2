-- Space Management Module Schema
-- Execute these statements on the GEMS database before deploying the module.

-- Reference tables
CREATE TABLE IF NOT EXISTS `ref_space_location` (
  `space_location_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_location_name` varchar(100) NOT NULL,
  `space_location_desc` varchar(255) DEFAULT NULL,
  `space_location_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`space_location_id`),
  UNIQUE KEY `uq_ref_space_location_name` (`space_location_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed common locations (idempotent)
INSERT INTO `ref_space_location` (`space_location_name`, `space_location_desc`, `space_location_status`)
SELECT 'Ground Floor', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM `ref_space_location` WHERE `space_location_name` = 'Ground Floor');
INSERT INTO `ref_space_location` (`space_location_name`, `space_location_desc`, `space_location_status`)
SELECT 'Level 1', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM `ref_space_location` WHERE `space_location_name` = 'Level 1');
INSERT INTO `ref_space_location` (`space_location_name`, `space_location_desc`, `space_location_status`)
SELECT 'Level 2', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM `ref_space_location` WHERE `space_location_name` = 'Level 2');

CREATE TABLE IF NOT EXISTS `ref_space_category` (
  `space_category_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_category_name` varchar(100) NOT NULL,
  `space_category_desc` varchar(255) DEFAULT NULL,
  `space_category_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`space_category_id`),
  UNIQUE KEY `uq_ref_space_category_name` (`space_category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ref_space_type` (
  `space_type_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_category_id` smallint UNSIGNED NOT NULL,
  `space_type_name` varchar(100) NOT NULL,
  `space_type_desc` varchar(255) DEFAULT NULL,
  `space_type_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`space_type_id`),
  UNIQUE KEY `uq_ref_space_type_cat_name` (`space_category_id`, `space_type_name`),
  KEY `idx_ref_space_type_category` (`space_category_id`),
  CONSTRAINT `fk_ref_space_type_category` FOREIGN KEY (`space_category_id`) REFERENCES `ref_space_category` (`space_category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ref_space_status` (
  `space_status_code` varchar(20) NOT NULL,
  `space_status_name` varchar(50) NOT NULL,
  `space_status_order` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `space_status_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`space_status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ref_space_status` (`space_status_code`, `space_status_name`, `space_status_order`, `space_status_active`)
VALUES
  ('ACTIVE', 'Active', 1, 1),
  ('AVAILABLE', 'Available', 2, 1),
  ('RESERVED', 'Reserved', 3, 1),
  ('DISABLED', 'Disabled', 4, 1)
ON DUPLICATE KEY UPDATE
  `space_status_name` = VALUES(`space_status_name`),
  `space_status_order` = VALUES(`space_status_order`),
  `space_status_active` = VALUES(`space_status_active`);

-- Register document type for space uploads
INSERT INTO `ref_document` (`document_id`, `document_desc`, `document_status`)
SELECT 41, 'Space Media', 1
WHERE NOT EXISTS (SELECT 1 FROM `ref_document` WHERE `document_id` = 41);

-- Space master
CREATE TABLE IF NOT EXISTS `spc_space` (
  `space_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` smallint UNSIGNED NOT NULL,
  `space_name` varchar(150) NOT NULL,
  `space_location_id` smallint UNSIGNED DEFAULT NULL,
  `space_category_id` smallint UNSIGNED DEFAULT NULL,
  `space_type_id` smallint UNSIGNED DEFAULT NULL,
  `space_area` decimal(10,2) DEFAULT NULL,
  `space_capacity` smallint UNSIGNED DEFAULT NULL,
  `space_status` varchar(20) NOT NULL DEFAULT 'AVAILABLE',
  `space_desc` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `space_created_by` int(11) DEFAULT NULL,
  `space_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `space_updated_by` int(11) DEFAULT NULL,
  `space_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`space_id`),
  UNIQUE KEY `uq_spc_space_site_name` (`site_id`, `space_name`),
  KEY `idx_spc_space_site` (`site_id`),
  KEY `idx_spc_space_status` (`space_status`),
  CONSTRAINT `fk_spc_space_location` FOREIGN KEY (`space_location_id`) REFERENCES `ref_space_location` (`space_location_id`),
  CONSTRAINT `fk_spc_space_category` FOREIGN KEY (`space_category_id`) REFERENCES `ref_space_category` (`space_category_id`),
  CONSTRAINT `fk_spc_space_type` FOREIGN KEY (`space_type_id`) REFERENCES `ref_space_type` (`space_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Space to asset junction
CREATE TABLE IF NOT EXISTS `spc_space_asset` (
  `space_asset_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_id` int UNSIGNED NOT NULL,
  `asset_id` int UNSIGNED NOT NULL,
  `linked_by` int(11) DEFAULT NULL,
  `linked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`space_asset_id`),
  UNIQUE KEY `uq_spc_space_asset` (`space_id`, `asset_id`),
  KEY `idx_spc_space_asset_asset` (`asset_id`),
  CONSTRAINT `fk_spc_space_asset_space` FOREIGN KEY (`space_id`) REFERENCES `spc_space` (`space_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spc_space_asset_asset` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Space media (photos, floorplans)
CREATE TABLE IF NOT EXISTS `spc_space_media` (
  `space_media_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_id` int UNSIGNED NOT NULL,
  `upload_id` int UNSIGNED NOT NULL,
  `media_type` enum('PHOTO','FLOORPLAN') NOT NULL,
  `media_caption` varchar(150) DEFAULT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `media_created_by` int(11) DEFAULT NULL,
  `media_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`space_media_id`),
  KEY `idx_spc_space_media_space` (`space_id`),
  KEY `idx_spc_space_media_cover` (`space_id`,`is_cover`),
  CONSTRAINT `fk_spc_space_media_space` FOREIGN KEY (`space_id`) REFERENCES `spc_space` (`space_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spc_space_media_upload` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reservations
CREATE TABLE IF NOT EXISTS `spc_reservation` (
  `reservation_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_id` int UNSIGNED NOT NULL,
  `site_id` smallint UNSIGNED NOT NULL,
  `reservation_start` datetime NOT NULL,
  `reservation_end` datetime NOT NULL,
  `reservation_status` varchar(20) NOT NULL DEFAULT 'RESERVED',
  `special_request` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `requested_by_name` varchar(150) DEFAULT NULL,
  `requested_by_contact` varchar(100) DEFAULT NULL,
  `auto_approved_at` datetime DEFAULT NULL,
  `canceled_by` int(11) DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_spc_reservation_space` (`space_id`),
  KEY `idx_spc_reservation_site` (`site_id`),
  KEY `idx_spc_reservation_status` (`reservation_status`),
  CONSTRAINT `fk_spc_reservation_space` FOREIGN KEY (`space_id`) REFERENCES `spc_space` (`space_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Helper view to speed up availability checks (optional)
CREATE OR REPLACE VIEW `vw_spc_reservation_active` AS
SELECT
  r.reservation_id,
  r.space_id,
  r.site_id,
  r.reservation_start,
  r.reservation_end,
  r.reservation_status
FROM spc_reservation r
WHERE r.reservation_status = 'RESERVED';
