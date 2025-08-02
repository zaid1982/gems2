use gems;

-- ===============================================================
-- File: deployment.sql
-- Description: Navigation Content
-- Table: sys_nav
-- ===============================================================

INSERT INTO sys_nav (nav_id, nav_desc, nav_page, nav_icon, nav_status) VALUES(22, 'Waste Management', 'p_waste', 'dumpster', 1);

-- ===============================================================
-- File: deployment.sql
-- Description: Navigation Content
-- Table: sys_nav_second
-- ===============================================================

INSERT IGNORE INTO sys_nav_second
  (nav_second_id, nav_id, nav_second_desc, nav_second_page, nav_second_status)
VALUES
  (1,  5,  'Designation',               'designation.html',          1),
  (2,  6,  'Client Management',         'client.html',               1),
  (3,  6,  'Site Management',           'site.html',                 1),
  (4,  6,  'Contract Management',       'contract.html',             1),
  (5,  7,  'Asset Management',          'asset.html',                1),
  (6,  7,  'Asset Group',               'asset_group.html',          1),
  (7,  7,  'Asset Category',            'asset_category.html',       1),
  (8,  7,  'Asset Type',                'asset_type.html',           1),
  (9,  7,  'Asset Brand',               'asset_brand.html',          1),
  (10, 3,  'Checklist Management',      'checklist.html',            1),
  (11, 3,  'PPM Management',            'ppm_management.html',       1),
  (12, 10, 'Financial Performance 1',   'finance_report1.html',      1),
  (13, 10, 'Financial Performance 2',   'finance_report2.html',      1),
  (14, 10, 'Cash Management',           'finance_report3.html',      1),
  (15, 10, 'Business Development 1',    'finance_report4.html',      1),
  (16, 10, 'Business Development 2',    'finance_report5.html',      1),
  (17, 10, 'Corporate Resource',        'finance_report6.html',      1),
  (18, 11, 'Work Order Summary',        'report_wo_summary.html',    1),
  (19, 11, 'PPM Summary',               'report_ppm_summary.html',   1),
  (20, 11, 'Outstanding Work Order',    'report_wo_pending.html',    1),
  (21, 11, 'System Status',             'report_system_status.html', 2),
  (22, 11, 'Total WO Summary',          'report_all_summary.html',   1),
  (23, 3,  'Reschedule PPM',            'ppm_reschedule.html',       1),
  (24, 5,  'WO Severity',               'severity.html',             1),
  (25, 5,  'WO Failure Code',           'failure_code.html',         1),
  (26, 14, 'Drawing Records',           'p_drawing_records',         1),
  (27, 14, 'Deleted Drawing ',          'p_drawing_deleted',         1),
  (28, 15, 'Purchase Request',          'p_purchase_request',        1),
  (29, 15, 'Supplier Management',       'p_supplier_management',     1),
  (30, 15, 'Item Management',           'p_item_management',         1),
  (31, 15, 'Item Type Management',      'p_item_type_management',    1),
  (32, 15, 'Store Management',          'p_store_management',        1),
  (33, 15, 'Inventory Management',      'p_inventory',               1),
  (35, 17, 'User Management',           'user_management.html',      1),
  (36, 17, 'Group Management',          'ppm_group.html',            1),
  (37, 17, 'Audit Trail',               'audit_trail.html',          1),
  (38, 18, 'Utilities Management',      'p_utility',                 1),
  (39, 18, 'Meter Management',          'p_utility_meter',           1),
  (40, 19, 'All Site',                  'p_attendance',              1),
  (41, 19, 'Site Admin',                'p_attendance_site',         1),
  (43, 21, 'Audit Task',                'p_fca_task',                1),
  (44, 21, 'Zone List',                 'p_fca_zone',                1),
  (45, 21, 'Defect Category List',      'p_fca_defect',              1),
  (46, 21, 'All Records',               'p_fca_all',                 1),
  (47, 21, 'PDF Report',                'p_fca_report',              1),
  (48, 21, 'Supervisor',                'p_attendance_group',        1),
  (49, 15, 'MRF List',                  'p_mrf',                     1),
  (50, 22, 'Waste Collection',          'p_waste_main',              1),
  (51, 22, 'Waste Type List',           'p_waste_type',              1),
  (52, 6,  'Zone Management',           'p_zone',                    1),
  (54, 23, 'Assign WO/WR',              'p_wo_assign',               1),
  (55, 23, 'Verify WO',                 'p_wo_verify',               1),
  (56, 3,  'PPM Asset Group',           'p_ppm_asset_v2',            1)
;

-- ===============================================================
-- File: deployment.sql
-- Description: Navigation Access Control
-- Table: sys_nav_role
-- ===============================================================

ALTER TABLE sys_nav_role
  ADD UNIQUE KEY ux_sys_nav_role_id (nav_role_id);

INSERT IGNORE INTO sys_nav_role
  (nav_role_id, role_id, nav_id, nav_second_id, nav_role_turn)
VALUES
  (1,   1,  1,   NULL,  1),
  (2,   1,  2,   NULL,  2),
  (3,   1,  3,   NULL, 20),
  (5,   1,  5,   NULL,280),
  (6,   1,  5,      1,281),
  (7,   1,  6,   NULL, 40),
  (9,   1,  6,      2, 41),
  (10,  1,  6,      3, 42),
  (11,  1,  6,      4, 43),
  (12,  1,  7,   NULL, 30),
  (13,  1,  7,      5, 31),
  (14,  1,  7,      6, 32),
  (15,  1,  7,      7, 33),
  (16,  1,  7,      8, 34),
  (17,  1,  7,      9, 35),
  (18,  1,  3,     10, 24),
  (19,  1,  3,     11, 21),
  (21,  1,  8,   NULL,270),
  (23,  1, 10,   NULL,240),
  (24,  1, 10,     12,241),
  (25,  1, 10,     13,242),
  (26,  1, 10,     14,243),
  (27,  1, 10,     15,244),
  (28,  1, 10,     16,245),
  (29,  1, 10,     17,246),
  (30,  1, 11,   NULL,250),
  (31,  1, 11,     18,251),
  (32,  1, 11,     19,252),
  (33,  1, 11,     20,253),
  (34,  1, 11,     21,254),
  (35,  6,  1,   NULL,  1),
  (42,  2,  1,   NULL,  1),
  (43,  2,  8,   NULL,270),
  (44,  2, 11,   NULL,250),
  (45,  2, 11,     18,251),
  (46,  2, 11,     19,252),
  (47,  2, 11,     20,253),
  (48,  2, 11,     21,254),
  (49, 10,  1,   NULL,  1),
  (50, 10,  2,   NULL,  2),
  (51, 10,  3,   NULL, 20),
  (53, 10,  5,   NULL,280),
  (54, 10,  5,      1,281),
  (55, 10,  6,   NULL, 40),
  (56, 10,  6,      2, 41),
  (57, 10,  6,      3, 42),
  (58, 10,  6,      4, 43),
  (59, 10,  7,   NULL, 30),
  (60, 10,  7,      5, 31),
  (61, 10,  7,      6, 32),
  (62, 10,  7,      7, 33),
  (63, 10,  7,      8, 34),
  (64, 10,  7,      9, 35),
  (65, 10,  3,     10, 24),
  (66, 10,  3,     11, 21),
  (67, 10,  8,   NULL,270),
  (69, 10, 10,   NULL,240),
  (70, 10, 10,     12,241),
  (71, 10, 10,     13,242),
  (72, 10, 10,     14,243),
  (73, 10, 10,     15,244),
  (74, 10, 10,     16,245),
  (75, 10, 10,     17,246),
  (76, 10, 11,   NULL,250),
  (77, 10, 11,     18,251),
  (78, 10, 11,     19,252),
  (79, 10, 11,     20,253),
  (80, 10, 11,     21,254),
  (81, 10, 11,     22,255),
  (82,  1, 11,     22,255),
  (83,  3,  1,   NULL,  1),
  (84,  3,  8,   NULL,270),
  (85,  3, 11,   NULL,250),
  (86,  3, 11,     18,251),
  (87,  3, 11,     19,252),
  (88,  3, 11,     20,253),
  (89,  3, 11,     21,254),
  (90,  4,  1,   NULL,  1),
  (91,  4,  8,   NULL,270),
  (92,  4, 11,   NULL,250),
  (93,  4, 11,     18,251),
  (94,  4, 11,     19,252),
  (95,  4, 11,     20,253),
  (96,  4, 11,     21,254),
  (97,  5,  1,   NULL,  1),
  (98,  5,  8,   NULL,270),
  (99,  5, 11,   NULL,250),
 (100,  5, 11,     18,251),
 (101,  5, 11,     19,252),
 (102,  5, 11,     20,253),
 (103,  5, 11,     21,254),
 (104,  7,  1,   NULL,  1),
 (105,  7,  8,   NULL,270),
 (106,  7, 11,   NULL,250),
 (107,  7, 11,     18,251),
 (108,  7, 11,     19,252),
 (109,  7, 11,     20,253),
 (110,  7, 11,     21,254),
 (111,  8,  1,   NULL,  1),
 (112,  8,  8,   NULL,270),
 (113,  8, 11,   NULL,250),
 (114,  8, 11,     18,251),
 (115,  8, 11,     19,252),
 (116,  8, 11,     20,253),
 (117,  8, 11,     21,254),
 (118,  9,  1,   NULL,  1),
 (119,  9,  8,   NULL,270),
 (120,  9, 11,   NULL,250),
 (121,  9, 11,     18,251),
 (122,  9, 11,     19,252),
 (123,  9, 11,     20,253),
 (124,  9, 11,     21,254),
 (125,  1,  3,     23, 25),
 (127,  3,  3,   NULL, 20),
 (128,  3,  3,     23, 25),
 (132, 11,  1,   NULL,  1),
 (133, 11, 13,   NULL,  3),
 (134, 11,  8,   NULL,270),
 (135,  1, 13,   NULL,  3),
 (136,  1,  5,     24,282),
 (137,  1,  5,     25,283),
 (138, 10,  5,     24,282),
 (139, 10,  5,     25,283),
 (140,  1, 14,   NULL, 50),
 (143, 10, 14,   NULL, 50),
 (146, 12, 14,   NULL, 50),
 (149, 13, 14,   NULL, 50),
 (152,  2, 14,   NULL, 50),
 (157,  1, 15,   NULL, 60),
 (158,  1, 15,     28, 62),
 (159,  1, 15,     29, 66),
 (160,  1, 15,     30, 63),
 (161,  1, 15,     31, 64),
 (162,  1, 15,     32, 65),
 (163,  1, 15,     33, 61),
 (164,  1, 16,   NULL, 80),
 (166,  1, 17,   NULL,300),
 (167,  1, 17,     35,301),
 (168,  1, 17,     36,302),
 (169,  1, 17,     37,303),
 (170, 10, 17,   NULL,300),
 (171, 10, 17,     36,302),
 (172, 10, 17,     37,303),
 (173,  1, 18,   NULL,120),
 (176, 14, 15,   NULL, 60),
 (177, 14, 15,     28, 62),
 (178, 14, 15,     29, 66),
 (179, 14, 15,     30, 63),
 (180, 14, 15,     31, 64),
 (181, 14, 15,     32, 65),
 (182, 14, 15,     33, 61),
 (183, 15, 15,   NULL, 60),
 (184, 15, 15,     28, 62),
 (185, 10, 16,   NULL, 80),
 (187,  2, 16,   NULL, 80),
 (188, 10, 18,   NULL,120),
 (191, 18, 18,   NULL,120),
 (194, 12,  1,   NULL,  1),
 (195, 13,  1,   NULL,  1),
 (196, 14,  1,   NULL,  1),
 (197, 15,  1,   NULL,  1),
 (198, 16,  1,   NULL,  1),
 (199, 17,  1,   NULL,  1),
 (200, 18,  1,   NULL,  1),
 (201, 19,  1,   NULL,  1),
 (202, 15, 15,     33, 61),
 (203, 16, 15,   NULL, 60),
 (204, 17, 15,   NULL, 60),
 (205, 19,  2,   NULL,  2),
 (206, 19, 13,   NULL,  3),
 (207, 19,  3,   NULL, 20),
 (208, 19,  3,     11, 21),
 (209, 19,  3,     10, 24),
 (210, 19,  3,     23, 25),
 (211, 19,  7,   NULL, 30),
 (212, 19,  7,      5, 31),
 (213, 19,  7,      6, 32),
 (214, 19,  7,      7, 33),
 (215, 19,  7,      8, 34),
 (216, 19,  7,      9, 35),
 (217, 19,  6,   NULL, 40),
 (218, 19,  6,      2, 41),
 (219, 19,  6,      3, 42),
 (220, 19,  6,      4, 43),
 (221, 19, 14,   NULL, 50),
 (222, 19, 15,   NULL, 60),
 (223, 19, 15,     33, 61),
 (224, 19, 15,     28, 62),
 (225, 19, 15,     30, 63),
 (226, 19, 15,     31, 64),
 (227, 19, 15,     32, 65),
 (228, 19, 15,     29, 66),
 (229, 19, 16,   NULL, 80),
 (231, 19, 18,   NULL,120),
 (232, 19, 10,   NULL,240),
 (233, 19, 10,     12,241),
 (234, 19, 10,     13,242),
 (235, 19, 10,     14,243),
 (236, 19, 10,     15,244),
 (237, 19, 10,     16,245),
 (238, 19, 10,     17,246),
 (239, 19, 11,   NULL,250),
 (240, 19, 11,     18,251),
 (241, 19, 11,     19,252),
 (242, 19, 11,     20,253),
 (243, 19, 11,     21,254),
 (244, 19, 11,     22,255),
 (245, 19,  8,   NULL,270),
 (246, 19,  5,   NULL,280),
 (247, 19,  5,      1,281),
 (248, 19,  5,     24,282),
 (249, 19,  5,     25,283),
 (250, 19, 17,   NULL,300),
 (253, 19, 17,     37,303),
 (254,  6, 13,   NULL,  3),
 (255,  6,  3,   NULL, 20),
 (256,  6,  7,   NULL, 30),
 (257,  6, 18,   NULL,120),
 (258,  6, 16,   NULL, 80),
 (259,  6, 15,   NULL, 60),
 (260,  3, 14,   NULL, 50),
 (261,  4, 14,   NULL, 50),
 (262,  5, 14,   NULL, 50),
 (263,  7, 14,   NULL, 50),
 (264,  8, 14,   NULL, 50),
 (265, 14, 14,   NULL, 50),
 (266, 15, 14,   NULL, 50),
 (267, 16, 14,   NULL, 50),
 (268, 17, 14,   NULL, 50),
 (269, 18, 14,   NULL, 50),
 (270,  1, 19,   NULL,130),
 (271,  1, 19,     40,131),
 (274,  6, 14,   NULL, 50),
 (275,  1, 20,   NULL,140),
 (276,  1, 21,   NULL,260),
 (277,  1, 21,     43,261),
 (278, 22, 21,   NULL,260),
 (279, 22, 21,     43,261),
 (280,  1, 21,     44,264),
 (281,  1, 21,     45,265),
 (282, 22, 21,     44,264),
 (283, 22, 21,     45,265),
 (284,  1, 21,     46,262),
 (285, 22, 21,     46,262),
 (286,  1, 21,     47,263),
 (287, 22, 21,     47,263),
 (289, 19, 19,   NULL,130),
 (291, 19, 19,     41,132),
 (293, 20, 19,   NULL,130),
 (297,  2, 19,   NULL,130),
 (298,  2, 19,     41,132),
 (301, 10, 19,     40,131),
 (303, 20, 19,     48,133),
 (304, 10, 19,   NULL,130),
 (305,  1, 15,     49, 67),
 (306, 14, 15,     49, 67),
 (307,  1, 22,   NULL,125),
 (308,  1, 22,     50,126),
 (309,  1, 22,     51,127),
 (310,  2, 22,   NULL,125),
 (311,  2, 22,     50,126),
 (312,  2, 22,     51,127),
 (313, 19, 22,   NULL,125),
 (314, 19, 22,     50,126),
 (315, 19, 22,     51,127),
 (320,  1,  6,     52, 44),
 (321, 10,  6,     52, 44),
 (322,  7, 23,   NULL, 10),
 (323,  7, 23,     54, 11),
 (324,  7, 23,     55, 12),
 (325,  1,  3,     56, 26),
 (326, 19,  3,     56, 26),
 (327, 17, 17,   NULL,300),
 (328, 17, 17,     36,302),
 (329, 17, 17,     37,303),
 (330, 17, 17,   NULL,300),
 (334, 19, 17,     35,301)
;

-- ===============================================================
-- File: deployment.sql
-- Description: Navigation Access Control for Waste Management
-- Table: gmi_config
-- ===============================================================

ALTER TABLE gmi_config
  ADD UNIQUE KEY ux_gmi_config_key (config_key);

INSERT IGNORE INTO gmi_config
  (config_key, config_value, data_type, description, last_updated_by, last_updated_at, status)
VALUES
  ('tier_medalist_threshold',  '150',   'int',   'Minimum completed tasks required to achieve Medalist tier',         1, NOW(), 1),
  ('tier_finisher_threshold',  '80',    'int',   'Minimum completed tasks required to achieve Finisher tier',        1, NOW(), 1),
  ('mbv_tier1_threshold',      '50',    'int',   'MBV threshold for tier 1 multiplier (lowest performance)',        1, NOW(), 1),
  ('mbv_tier2_threshold',      '100',   'int',   'MBV threshold for tier 2 multiplier (medium performance)',        1, NOW(), 1),
  ('mbv_tier1_multiplier',     '1',     'int',   'Point multiplier for tier 1 (MBV <= 50)',                         1, NOW(), 1),
  ('mbv_tier2_multiplier',     '3',     'int',   'Point multiplier for tier 2 (51 <= MBV <= 100)',                  1, NOW(), 1),
  ('mbv_tier3_multiplier',     '5',     'int',   'Point multiplier for tier 3 (MBV > 100)',                         1, NOW(), 1),
  ('weight_completed',         '0.3',   'float', 'Weight percentage for completion points (30%)',                   1, NOW(), 1),
  ('weight_ontime',            '0.7',   'float', 'Weight percentage for on-time points (70%)',                      1, NOW(), 1),
  ('weight_late_penalty',      '0.15',  'float', 'Weight percentage for late penalty (15%)',                        1, NOW(), 1),
  ('self_finding_points',      '5',     'int',   'Points awarded per self-finding work order',                      1, NOW(), 1),
  ('point_scale_factor',       '10000', 'int',   'Scaling factor for all point calculations',                       1, NOW(), 1),
  ('productivity_base',        '90',    'int',   'Base productivity percentage for calculations',                    1, NOW(), 1),
  ('wo_ontime_multiplier',     '2',     'int',   'Multiplier for work order on-time calculations',                  1, NOW(), 1);

-- 3) Create the non-unique indexes only if they don’t already exist (MySQL 8.0.13+)
CREATE INDEX IF NOT EXISTS idx_gmi_config_key    ON gmi_config (config_key);
CREATE INDEX IF NOT EXISTS idx_gmi_config_status ON gmi_config (status);

-- ============================================================================
-- 2) CREATE tables in prod that exist in dev but are missing
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cli_zone` (
  `zone_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `zone_code` varchar(20) NOT NULL,
  `zone_type` varchar(100) DEFAULT NULL,
  `zone_name` varchar(200) NOT NULL,
  `zone_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`zone_id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `cli_zone_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gmi_config` (
  `config_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  `config_group` varchar(50) DEFAULT NULL,
  `config_desc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `gmi_weekly` (
  `gmi_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `site_id` smallint(6) DEFAULT NULL,
  `week_start` date DEFAULT NULL,
  `week_end` date DEFAULT NULL,
  `gmi_ppm_total` smallint(6) DEFAULT NULL,
  `gmi_ppm_completed` smallint(6) DEFAULT NULL,
  `gmi_wo_total` smallint(6) DEFAULT NULL,
  `gmi_wo_completed` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`gmi_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gmi_weekly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `noti_web` (
  `noti_web_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `noti_web_type` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `noti_web_title` varchar(30) NOT NULL,
  `noti_web_text` varchar(255) NOT NULL,
  `noti_web_icon` varchar(30) NOT NULL,
  `noti_web_color` varchar(30) NOT NULL,
  `noti_web_link` varchar(100) DEFAULT NULL,
  `noti_web_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`noti_web_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ppm_set` (
  `ppm_set_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_type_id` smallint(6) NOT NULL,
  `ppm_group_id` smallint(6) NOT NULL,
  `ppm_set_name` varchar(200) DEFAULT NULL,
  `ppm_set_desc` varchar(1000) DEFAULT NULL,
  `asset_group_id` smallint(6) NOT NULL,
  `asset_category_id` smallint(6) NOT NULL,
  `ppm_set_created_by` int(11) DEFAULT NULL,
  `ppm_set_time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `ppm_set_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_set_id`),
  KEY `asset_type_id` (`asset_type_id`),
  KEY `ppm_group_id` (`ppm_group_id`),
  CONSTRAINT `ppm_set_ibfk_1` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`),
  CONSTRAINT `ppm_set_ibfk_2` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ppm_set_asset` (
  `ppm_set_asset_id` int(11) NOT NULL AUTO_INCREMENT,
  `ppm_set_id` smallint(6) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_set_asset_id`),
  KEY `ppm_set_id` (`ppm_set_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `ppm_set_asset_ibfk_1` FOREIGN KEY (`ppm_set_id`) REFERENCES `ppm_set` (`ppm_set_id`),
  CONSTRAINT `ppm_set_asset_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ppm_asset` (
  `ppm_asset_id` int(11) NOT NULL AUTO_INCREMENT,
  `ppm_id` bigint(20) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_asset_id`),
  UNIQUE KEY `ppm_id` (`ppm_id`,`asset_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `ppm_asset_ibfk_1` FOREIGN KEY (`ppm_id`) REFERENCES `ppm` (`ppm_id`),
  CONSTRAINT `ppm_asset_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_import_batch` (
  `batch_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `batch_name` varchar(100) NOT NULL,
  `batch_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_import_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `batch_id` bigint(20) NOT NULL,
  `record_data` text NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`log_id`),
  CONSTRAINT `wo_import_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `wo_import_batch` (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_task_public` (
  `public_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint(20) NOT NULL,
  `public_note` text NOT NULL,
  `public_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`public_id`),
  CONSTRAINT `wo_task_public_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_task_request_2` (
  `request2_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_request_id` bigint(20) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `request2_time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`request2_id`),
  CONSTRAINT `wo_task_request_2_ibfk_1` FOREIGN KEY (`wo_task_request_id`) REFERENCES `wo_task_request` (`wo_task_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ============================================================================
-- 3) ADD missing columns to existing tables (using IF NOT EXISTS)
-- ============================================================================

ALTER TABLE `cli_location_code`
  ADD COLUMN IF NOT EXISTS `location_code_type` varchar(100) DEFAULT NULL AFTER `location_code_status`;

ALTER TABLE `cli_site`
  ADD COLUMN IF NOT EXISTS `site_is_public` tinyint(1) NOT NULL DEFAULT 0 AFTER `site_status`;

ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `zone_id` smallint(6) DEFAULT NULL AFTER `checklist_id`,
  ADD COLUMN IF NOT EXISTS `asset_lifespan_year` tinyint(4) DEFAULT NULL AFTER `zone_id`,
  ADD COLUMN IF NOT EXISTS `asset_lifespan_start_date` date DEFAULT NULL AFTER `asset_lifespan_year`,
  ADD COLUMN IF NOT EXISTS `asset_lifespan_alert` tinyint(4) DEFAULT NULL AFTER `asset_lifespan_start_date`,
  ADD COLUMN IF NOT EXISTS `asset_value_depreciation` tinyint(4) DEFAULT NULL AFTER `asset_lifespan_alert`,
  ADD COLUMN IF NOT EXISTS `asset_value_alert` decimal(10,2) DEFAULT NULL AFTER `asset_value_depreciation`,
  ADD COLUMN IF NOT EXISTS `asset_repair_alert` decimal(10,2) DEFAULT NULL AFTER `asset_value_alert`,
  ADD COLUMN IF NOT EXISTS `asset_running_hours` smallint(6) DEFAULT NULL AFTER `asset_repair_alert`,
  ADD COLUMN IF NOT EXISTS `asset_disposal_status` tinyint(1) DEFAULT NULL AFTER `asset_running_hours`,
  ADD COLUMN IF NOT EXISTS `asset_disposal_date` date DEFAULT NULL AFTER `asset_disposal_status`,
  ADD COLUMN IF NOT EXISTS `asset_disposal_item_cost` decimal(10,2) DEFAULT NULL AFTER `asset_disposal_date`,
  ADD COLUMN IF NOT EXISTS `asset_disposal_service_cost` decimal(10,2) DEFAULT NULL AFTER `asset_disposal_item_cost`,
  ADD COLUMN IF NOT EXISTS `asset_mtbf_alert` smallint(6) DEFAULT NULL AFTER `asset_disposal_service_cost`,
  ADD COLUMN IF NOT EXISTS `asset_mttr_alert` smallint(6) DEFAULT NULL AFTER `asset_mtbf_alert`;

ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `ppm_name` varchar(200) DEFAULT NULL AFTER `ppm_status`,
  ADD COLUMN IF NOT EXISTS `ppm_set_id` smallint(6) DEFAULT NULL AFTER `ppm_name`,
  ADD COLUMN IF NOT EXISTS `ppm_is_group` tinyint(1) DEFAULT NULL AFTER `ppm_set_id`,
  ADD COLUMN IF NOT EXISTS `asset_type_id` smallint(6) DEFAULT NULL AFTER `ppm_is_group`,
  ADD COLUMN IF NOT EXISTS `ppm_frequency` varchar(100) DEFAULT NULL AFTER `asset_type_id`,
  ADD COLUMN IF NOT EXISTS `ppm_remark` text DEFAULT NULL AFTER `ppm_frequency`;

ALTER TABLE `ppm_task`
  ADD COLUMN IF NOT EXISTS `ppm_task_is_group_executed` tinyint(1) NOT NULL DEFAULT 0 AFTER `ppm_task_status`;

ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_external_ref` varchar(100) DEFAULT NULL COMMENT 'External system work order reference' AFTER `wo_task_status`,
  ADD COLUMN IF NOT EXISTS `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Imported flag' AFTER `wo_task_external_ref`,
  ADD COLUMN IF NOT EXISTS `wo_task_is_public` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_is_imported`,
  ADD COLUMN IF NOT EXISTS `zone_id` smallint(6) DEFAULT NULL AFTER `wo_task_is_public`,
  ADD COLUMN IF NOT EXISTS `wo_task_is_pdf_wr` tinyint(1) NOT NULL DEFAULT 0 AFTER `zone_id`,
  ADD COLUMN IF NOT EXISTS `wo_task_is_pdf` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_is_pdf_wr`;

ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_no` varchar(30) DEFAULT NULL AFTER `wo_task_request_status`,
  ADD COLUMN IF NOT EXISTS `store_id` smallint(6) DEFAULT NULL AFTER `wo_task_no`,
  ADD COLUMN IF NOT EXISTS `wo_task_request_severity` tinyint(1) DEFAULT NULL AFTER `store_id`,
  ADD COLUMN IF NOT EXISTS `wo_task_request_is_standalone` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_request_severity`;

-- ============================================================================
-- 4) CONDITIONAL INDEX CREATION on sys_user
-- ============================================================================

DELIMITER $$
DROP PROCEDURE IF EXISTS `add_idx_sys_user` $$
CREATE PROCEDURE `add_idx_sys_user`()
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name   = 'sys_user'
       AND index_name   = 'idx_user_name'
  ) THEN
    ALTER TABLE `sys_user`
      ADD INDEX `idx_user_name` (`user_name`);
  END IF;
END $$
DELIMITER ;

CALL `add_idx_sys_user`();
DROP PROCEDURE IF EXISTS `add_idx_sys_user`;

COMMIT;
