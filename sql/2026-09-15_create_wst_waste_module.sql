-- GEMS Scheduled Waste Register V1
-- Idempotent schema + seed for wst_* tables, official SW codes, roles, audit, documents.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Official Scheduled Waste codes (EQ (Scheduled Wastes) Regulations 2005)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ref_sw_code` (
  `sw_code_id` smallint NOT NULL AUTO_INCREMENT,
  `sw_code` varchar(10) NOT NULL,
  `sw_group` varchar(5) NOT NULL,
  `sw_description` varchar(500) NOT NULL,
  `sw_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`sw_code_id`),
  UNIQUE KEY `uk_ref_sw_code` (`sw_code`),
  KEY `idx_ref_sw_group` (`sw_group`),
  KEY `idx_ref_sw_status` (`sw_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ref_sw_code` (`sw_code`, `sw_group`, `sw_description`, `sw_status`)
SELECT v.code, v.grp, v.descr, 1
FROM (
  SELECT 'SW101' AS code, 'SW1' AS grp, 'Waste containing arsenic or its compound' AS descr
  UNION ALL SELECT 'SW102', 'SW1', 'Waste of lead acid batteries in whole or crushed form'
  UNION ALL SELECT 'SW103', 'SW1', 'Waste of batteries containing cadmium and nickel or mercury or lithium'
  UNION ALL SELECT 'SW104', 'SW1', 'Dust, slag, dross or ash containing arsenic, mercury, lead, cadmium, chromium, nickel, copper, vanadium or beryllium'
  UNION ALL SELECT 'SW105', 'SW1', 'Galvanic sludges'
  UNION ALL SELECT 'SW106', 'SW1', 'Residues from recovery of acid pickling liquor'
  UNION ALL SELECT 'SW107', 'SW1', 'Slags from copper smelting'
  UNION ALL SELECT 'SW108', 'SW1', 'Leaching residues from zinc processing in the form of jarosite, hematite, etc.'
  UNION ALL SELECT 'SW109', 'SW1', 'Waste containing mercury or its compound'
  UNION ALL SELECT 'SW110', 'SW1', 'Waste from electrical and electronic assemblies containing components such as accumulators, mercury-switches, glass from cathode-ray tubes and other activated glass or polychlorinated biphenyl-capacitors, or contaminated with cadmium, mercury, lead, nickel, chromium, copper, lithium, silver, manganese or polychlorinated biphenyl'
  UNION ALL SELECT 'SW201', 'SW2', 'Asbestos wastes in the form of dust or fibres'
  UNION ALL SELECT 'SW202', 'SW2', 'Waste catalysts'
  UNION ALL SELECT 'SW203', 'SW2', 'Immobilized scheduled wastes including chemically fixed, encapsulated, solidified or stabilized sludges'
  UNION ALL SELECT 'SW204', 'SW2', 'Sludges containing one or several metals including chromium, copper, nickel, zinc, lead, cadmium, aluminium, tin, vanadium and barium'
  UNION ALL SELECT 'SW205', 'SW2', 'Waste gypsum arising from chemical industry or power plant'
  UNION ALL SELECT 'SW206', 'SW2', 'Spent inorganic acids'
  UNION ALL SELECT 'SW207', 'SW2', 'Sludges containing fluoride'
  UNION ALL SELECT 'SW301', 'SW3', 'Spent organic acids with pH less or equal to 2 which are corrosive or hazardous'
  UNION ALL SELECT 'SW302', 'SW3', 'Flux waste containing mixture of organic acids, solvents or compounds of ammonium chloride'
  UNION ALL SELECT 'SW303', 'SW3', 'Adhesive or glue waste containing organic solvents excluding solid polymeric materials'
  UNION ALL SELECT 'SW304', 'SW3', 'Press cake from pretreatment of glycerol soap lye'
  UNION ALL SELECT 'SW305', 'SW3', 'Spent lubricating oil'
  UNION ALL SELECT 'SW306', 'SW3', 'Spent hydraulic oil'
  UNION ALL SELECT 'SW307', 'SW3', 'Spent mineral oil-water emulsion'
  UNION ALL SELECT 'SW308', 'SW3', 'Oil tanker sludges'
  UNION ALL SELECT 'SW309', 'SW3', 'Oil-water mixture such as ballast water'
  UNION ALL SELECT 'SW310', 'SW3', 'Sludge from mineral oil storage tank'
  UNION ALL SELECT 'SW311', 'SW3', 'Waste oil or oily sludge'
  UNION ALL SELECT 'SW312', 'SW3', 'Oily residue from automotive workshop, service station oil or grease interceptor'
  UNION ALL SELECT 'SW313', 'SW3', 'Oil contaminated earth from re-refining of used lubricating oil'
  UNION ALL SELECT 'SW314', 'SW3', 'Oil or sludge from oil interceptor in petrol station'
  UNION ALL SELECT 'SW315', 'SW3', 'Tar or tarry residues from oil refinery or petrochemical plant'
  UNION ALL SELECT 'SW316', 'SW3', 'Acid sludge'
  UNION ALL SELECT 'SW317', 'SW3', 'Spent organometallic compounds including tetraethyl lead, tetramethyl lead and organotin compounds'
  UNION ALL SELECT 'SW318', 'SW3', 'Waste, substances and articles containing or contaminated with polychlorinated biphenyls (PCB) or polychlorinated terphenyls (PCT)'
  UNION ALL SELECT 'SW319', 'SW3', 'Waste of phenols or phenol compounds including chlorophenol in the form of liquids or sludges'
  UNION ALL SELECT 'SW320', 'SW3', 'Waste containing formaldehyde'
  UNION ALL SELECT 'SW321', 'SW3', 'Rubber or latex wastes or treated rubber effluent sludges'
  UNION ALL SELECT 'SW322', 'SW3', 'Waste of non-halogenated organic solvents'
  UNION ALL SELECT 'SW323', 'SW3', 'Waste of halogenated organic solvents'
  UNION ALL SELECT 'SW324', 'SW3', 'Waste of halogenated or unhalogenated non-aqueous distillation residues arising from organic solvents recovery processes'
  UNION ALL SELECT 'SW325', 'SW3', 'Uncured resin waste containing organic solvents or heavy metals including epoxy resin and phenolic resin'
  UNION ALL SELECT 'SW326', 'SW3', 'Waste of organic phosphorus compound'
  UNION ALL SELECT 'SW327', 'SW3', 'Waste of thermal fluids (heat transfer) such as ethylene glycol'
  UNION ALL SELECT 'SW401', 'SW4', 'Spent alkalis containing heavy metals'
  UNION ALL SELECT 'SW402', 'SW4', 'Spent alkalis with pH more or equal to 11.5 which are corrosive or hazardous'
  UNION ALL SELECT 'SW403', 'SW4', 'Discarded drugs containing psychotropic substances or containing or contaminated with radionuclides or heavy metals'
  UNION ALL SELECT 'SW404', 'SW4', 'Pathogenic wastes, clinical wastes or quarantined materials'
  UNION ALL SELECT 'SW405', 'SW4', 'Waste arising from the preparation and production of pharmaceutical product'
  UNION ALL SELECT 'SW406', 'SW4', 'Clinker, slag and ashes from scheduled wastes incinerator'
  UNION ALL SELECT 'SW407', 'SW4', 'Sludges containing cyanide'
  UNION ALL SELECT 'SW408', 'SW4', 'Contaminated soil, debris or matter resulting from cleaning-up of a spill of chemical, mineral oil or scheduled wastes'
  UNION ALL SELECT 'SW409', 'SW4', 'Disposed containers, bags or equipment contaminated with chemicals, pesticides, mineral oil or scheduled wastes'
  UNION ALL SELECT 'SW410', 'SW4', 'Rags, plastics, papers or filters contaminated with scheduled wastes'
  UNION ALL SELECT 'SW411', 'SW4', 'Spent activated carbon excluding carbon from the treatment of potable water and processes of the food industry and vitamin production'
  UNION ALL SELECT 'SW412', 'SW4', 'Sludges containing phenols or chlorophenols'
  UNION ALL SELECT 'SW413', 'SW4', 'Spent filter clay'
  UNION ALL SELECT 'SW414', 'SW4', 'Spent aqueous alkaline solution containing cyanide'
  UNION ALL SELECT 'SW415', 'SW4', 'Spent quenching oils containing cyanides'
  UNION ALL SELECT 'SW416', 'SW4', 'Discarded chemicals'
  UNION ALL SELECT 'SW417', 'SW4', 'Waste of inks, paints, pigments, lacquer, dye or varnish'
  UNION ALL SELECT 'SW418', 'SW4', 'Discarded or off-specification inks, paints, pigments, lacquer, dye or varnish products'
  UNION ALL SELECT 'SW419', 'SW4', 'Spent di-isocyanates and residues of isocyanate compounds excluding solid polymeric materials from foam manufacturing'
  UNION ALL SELECT 'SW420', 'SW4', 'Leachate from scheduled waste landfill'
  UNION ALL SELECT 'SW421', 'SW4', 'A mixture of scheduled wastes'
  UNION ALL SELECT 'SW422', 'SW4', 'A mixture of scheduled and non-scheduled wastes'
  UNION ALL SELECT 'SW423', 'SW4', 'Spent processing solution, discarded photographic chemicals or discarded photographic papers'
  UNION ALL SELECT 'SW424', 'SW4', 'Spent oxidizing agent'
  UNION ALL SELECT 'SW501', 'SW5', 'Any residues from treatment or recovery of scheduled wastes'
  UNION ALL SELECT 'SW502', 'SW5', 'Debris from scheduled waste landfill or dump site'
  UNION ALL SELECT 'SW503', 'SW5', 'Heat treatment salts containing cyanides'
  UNION ALL SELECT 'SW504', 'SW5', 'Pathogenic and clinical wastes and quarantined materials'
  UNION ALL SELECT 'SW505', 'SW5', 'Waste containing or contaminated with polychlorinated dibenzofurans and/or polychlorinated dibenzo-p-dioxins'
) v
WHERE NOT EXISTS (SELECT 1 FROM ref_sw_code r WHERE r.sw_code = v.code);

-- ---------------------------------------------------------------------------
-- Premise settings (1:1 with cli_site)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wst_premise` (
  `site_id` smallint NOT NULL,
  `premise_address` varchar(500) DEFAULT NULL,
  `premise_contact_no` varchar(50) DEFAULT NULL,
  `cutover_date` date DEFAULT NULL,
  `evidence_required_produced` tinyint(1) NOT NULL DEFAULT 0,
  `evidence_required_disposed` tinyint(1) NOT NULL DEFAULT 0,
  `premise_status` tinyint NOT NULL DEFAULT 1,
  `premise_created_by` int DEFAULT NULL,
  `premise_updated_by` int DEFAULT NULL,
  `premise_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `premise_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`),
  CONSTRAINT `wst_premise_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_waste_profile` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `sw_code_id` smallint NOT NULL,
  `profile_alias` varchar(150) DEFAULT NULL,
  `profile_status` tinyint NOT NULL DEFAULT 1,
  `profile_created_by` int DEFAULT NULL,
  `profile_updated_by` int DEFAULT NULL,
  `profile_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `uk_wst_profile_site_code` (`site_id`, `sw_code_id`),
  KEY `idx_wst_profile_status` (`profile_status`),
  CONSTRAINT `wst_profile_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`),
  CONSTRAINT `wst_profile_sw_fk` FOREIGN KEY (`sw_code_id`) REFERENCES `ref_sw_code` (`sw_code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_location` (
  `location_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `location_name` varchar(150) NOT NULL,
  `location_type` varchar(20) NOT NULL DEFAULT 'STORAGE',
  `location_status` tinyint NOT NULL DEFAULT 1,
  `location_created_by` int DEFAULT NULL,
  `location_updated_by` int DEFAULT NULL,
  `location_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  KEY `idx_wst_location_site` (`site_id`, `location_status`),
  CONSTRAINT `wst_location_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_ref_value` (
  `ref_value_id` int NOT NULL AUTO_INCREMENT,
  `value_type` varchar(40) NOT NULL,
  `site_id` smallint DEFAULT NULL,
  `value_name` varchar(150) NOT NULL,
  `value_status` tinyint NOT NULL DEFAULT 1,
  `value_created_by` int DEFAULT NULL,
  `value_updated_by` int DEFAULT NULL,
  `value_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `value_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ref_value_id`),
  KEY `idx_wst_ref_type` (`value_type`, `value_status`),
  KEY `idx_wst_ref_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `wst_ref_value` (`value_type`, `site_id`, `value_name`, `value_status`)
SELECT v.t, NULL, v.n, 1
FROM (
  SELECT 'PACKAGING_TYPE' AS t, 'Drum' AS n
  UNION ALL SELECT 'PACKAGING_TYPE', 'IBC / Container'
  UNION ALL SELECT 'PACKAGING_TYPE', 'Bag'
  UNION ALL SELECT 'PACKAGING_TYPE', 'Box'
  UNION ALL SELECT 'PACKAGING_TYPE', 'Other'
  UNION ALL SELECT 'HANDLING_METHOD', 'Storage'
  UNION ALL SELECT 'HANDLING_METHOD', 'Disposal'
  UNION ALL SELECT 'HANDLING_METHOD', 'Treatment'
  UNION ALL SELECT 'HANDLING_METHOD', 'Recovery'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Work Order'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Weight Ticket'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Collection Note'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Consignment Note'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'eSWIS Reference Document'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Disposal / Receiver Evidence'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Photograph'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Other'
  UNION ALL SELECT 'SUBMISSION_CHANNEL', 'Email'
  UNION ALL SELECT 'SUBMISSION_CHANNEL', 'Portal'
  UNION ALL SELECT 'SUBMISSION_CHANNEL', 'Hand Delivery'
  UNION ALL SELECT 'SUBMISSION_CHANNEL', 'Other'
) v
WHERE NOT EXISTS (
  SELECT 1 FROM wst_ref_value r WHERE r.value_type = v.t AND r.site_id IS NULL AND r.value_name = v.n
);

CREATE TABLE IF NOT EXISTS `wst_number_sequence` (
  `site_id` smallint NOT NULL,
  `seq_year` smallint NOT NULL,
  `last_no` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`site_id`, `seq_year`),
  CONSTRAINT `wst_seq_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_transaction` (
  `txn_id` int NOT NULL AUTO_INCREMENT,
  `txn_ref` varchar(40) NOT NULL,
  `client_ref` varchar(64) DEFAULT NULL,
  `site_id` smallint NOT NULL,
  `txn_type` char(1) NOT NULL,
  `event_date` date NOT NULL,
  `sw_code_id` smallint NOT NULL,
  `profile_id` int DEFAULT NULL,
  `source_activity` varchar(255) DEFAULT NULL,
  `wo_ref` varchar(80) DEFAULT NULL,
  `handling_method_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `location_text` varchar(150) DEFAULT NULL,
  `remarks` text,
  `qty` decimal(14,3) NOT NULL,
  `unit` varchar(5) NOT NULL,
  `qty_kg` decimal(14,3) NOT NULL,
  `packaging_type_id` int DEFAULT NULL,
  `package_count` int DEFAULT NULL,
  `transporter_id` int DEFAULT NULL,
  `transporter_text` varchar(150) DEFAULT NULL,
  `receiver_id` int DEFAULT NULL,
  `receiver_text` varchar(150) DEFAULT NULL,
  `vehicle_reg` varchar(40) DEFAULT NULL,
  `external_ref` varchar(80) DEFAULT NULL,
  `txn_status` varchar(12) NOT NULL DEFAULT 'DRAFT',
  `finalised_by` int DEFAULT NULL,
  `finalised_at` datetime DEFAULT NULL,
  `cancelled_by` int DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `txn_created_by` int DEFAULT NULL,
  `txn_updated_by` int DEFAULT NULL,
  `txn_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `txn_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`txn_id`),
  UNIQUE KEY `uk_wst_txn_ref` (`txn_ref`),
  UNIQUE KEY `uk_wst_txn_client_ref` (`client_ref`),
  KEY `idx_wst_txn_site_code_status_date` (`site_id`, `sw_code_id`, `txn_status`, `event_date`),
  KEY `idx_wst_txn_external` (`external_ref`),
  KEY `idx_wst_txn_status` (`txn_status`),
  CONSTRAINT `wst_txn_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`),
  CONSTRAINT `wst_txn_sw_fk` FOREIGN KEY (`sw_code_id`) REFERENCES `ref_sw_code` (`sw_code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_transaction_document` (
  `doc_id` int NOT NULL AUTO_INCREMENT,
  `txn_id` int NOT NULL,
  `upload_id` bigint NOT NULL,
  `document_type_id` int DEFAULT NULL,
  `doc_ref` varchar(80) DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `doc_description` varchar(255) DEFAULT NULL,
  `doc_status` tinyint NOT NULL DEFAULT 1,
  `doc_created_by` int DEFAULT NULL,
  `doc_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `idx_wst_doc_txn` (`txn_id`, `doc_status`),
  CONSTRAINT `wst_doc_txn_fk` FOREIGN KEY (`txn_id`) REFERENCES `wst_transaction` (`txn_id`),
  CONSTRAINT `wst_doc_upload_fk` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_opening_balance` (
  `opening_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `sw_code_id` smallint NOT NULL,
  `as_at_date` date NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `unit` varchar(5) NOT NULL,
  `qty_kg` decimal(14,3) NOT NULL,
  `location_id` int DEFAULT NULL,
  `remarks` text,
  `upload_id` bigint DEFAULT NULL,
  `opening_status` tinyint NOT NULL DEFAULT 1,
  `opening_created_by` int DEFAULT NULL,
  `opening_updated_by` int DEFAULT NULL,
  `opening_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opening_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`opening_id`),
  UNIQUE KEY `uk_wst_opening_site_code` (`site_id`, `sw_code_id`),
  KEY `idx_wst_opening_status` (`opening_status`),
  CONSTRAINT `wst_opening_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`),
  CONSTRAINT `wst_opening_sw_fk` FOREIGN KEY (`sw_code_id`) REFERENCES `ref_sw_code` (`sw_code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(30) NOT NULL,
  `entity_id` int NOT NULL,
  `site_id` smallint DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `old_values` longtext,
  `new_values` longtext,
  `reason` varchar(500) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_wst_hist_entity` (`entity_type`, `entity_id`),
  KEY `idx_wst_hist_site_date` (`site_id`, `event_date`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_report` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `as_at_date` date NOT NULL,
  `version_no` smallint NOT NULL,
  `include_detail` tinyint(1) NOT NULL DEFAULT 1,
  `include_zero` tinyint(1) NOT NULL DEFAULT 0,
  `summary_json` longtext,
  `detail_json` longtext,
  `pdf_upload_id` bigint DEFAULT NULL,
  `excel_upload_id` bigint DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remarks` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `uk_wst_report_version` (`site_id`, `period_start`, `period_end`, `version_no`),
  KEY `idx_wst_report_site` (`site_id`, `period_start`),
  CONSTRAINT `wst_report_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wst_report_submission` (
  `submission_id` int NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL,
  `submitted_at` datetime NOT NULL,
  `recipient` varchar(150) DEFAULT NULL,
  `channel_id` int DEFAULT NULL,
  `submission_ref` varchar(80) DEFAULT NULL,
  `evidence_upload_id` bigint DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`submission_id`),
  KEY `idx_wst_sub_report` (`report_id`),
  CONSTRAINT `wst_sub_report_fk` FOREIGN KEY (`report_id`) REFERENCES `wst_report` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Document types, roles, audit
-- ---------------------------------------------------------------------------
INSERT INTO `ref_document` (`document_id`, `document_desc`, `document_type`, `document_status`)
SELECT 42, 'Waste Supporting Document', 'Waste Management', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_document WHERE document_id = 42);

INSERT INTO `ref_document` (`document_id`, `document_desc`, `document_type`, `document_status`)
SELECT 43, 'Waste Report File', 'Waste Management', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_document WHERE document_id = 43);

INSERT INTO `ref_document` (`document_id`, `document_desc`, `document_type`, `document_status`)
SELECT 44, 'Waste Submission Evidence', 'Waste Management', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_document WHERE document_id = 44);

INSERT INTO `ref_role` (`role_id`, `role_desc`, `role_type`, `role_status`)
SELECT 28, 'Waste User', 2, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_id = 28);

INSERT INTO `ref_role` (`role_id`, `role_desc`, `role_type`, `role_status`)
SELECT 29, 'Waste Officer', 2, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_id = 29);

INSERT INTO `sys_audit_module` (`audit_module_id`, `audit_module_desc`, `audit_module_status`)
VALUES (18, 'Waste Management', 1)
ON DUPLICATE KEY UPDATE `audit_module_desc` = VALUES(`audit_module_desc`), `audit_module_status` = VALUES(`audit_module_status`);

INSERT INTO `sys_audit_action` (`audit_action_id`, `audit_action_desc`, `audit_module_id`, `audit_action_status`) VALUES
  (236, 'Create Waste Record', 18, 1),
  (237, 'Update Waste Record', 18, 1),
  (238, 'Finalise Waste Record', 18, 1),
  (239, 'Amend Waste Record', 18, 1),
  (240, 'Cancel Waste Record', 18, 1),
  (241, 'Save Opening Balance', 18, 1),
  (242, 'Generate JKR Waste Report', 18, 1),
  (243, 'Record Waste Report Submission', 18, 1),
  (244, 'Update Waste Setup', 18, 1)
ON DUPLICATE KEY UPDATE
  `audit_action_desc` = VALUES(`audit_action_desc`),
  `audit_module_id` = VALUES(`audit_module_id`),
  `audit_action_status` = VALUES(`audit_action_status`);

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
