-- Create table for License management
CREATE TABLE IF NOT EXISTS `lic_license` (
  `license_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `license_title` varchar(255) NOT NULL,
  `license_start_date` date NOT NULL,
  `license_end_date` date NOT NULL,
  `upload_id` int(11) DEFAULT NULL,
  `license_status` tinyint(1) NOT NULL DEFAULT 1,
  `license_created_by` int(11) DEFAULT NULL,
  `license_updated_by` int(11) DEFAULT NULL,
  `license_created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `license_updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`license_id`),
  KEY `site_id` (`site_id`),
  KEY `upload_id` (`upload_id`),
  CONSTRAINT `lic_license_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`),
  CONSTRAINT `lic_license_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: add document type for License if not exists (id 29 assumed elsewhere)
INSERT INTO ref_document (`document_id`,`document_name`,`document_status`)
SELECT 29, 'License Document', 1 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM ref_document WHERE document_id = 29);
