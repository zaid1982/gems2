-- ============================================================================
-- GEMS JKR staging/prod schema upgrade
-- Generated: 2026-05-20
-- Source dump: docs/jkr-db/gems_jkr_staging_prod.sql
-- Compared against current dev database configured by api/class/Constant.php.
-- ============================================================================

-- Target environments patched from older subsidiaries may still be missing
-- runtime tables that exist in the dump. Keep these guards idempotent and
-- data-preserving.

CREATE TABLE IF NOT EXISTS `noti_web`  (
	`noti_web_id` bigint NOT NULL AUTO_INCREMENT,
	`noti_web_type` tinyint NOT NULL COMMENT '1=WO assign\n2=WO verify',
	`user_id` int NOT NULL,
	`noti_web_title` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
	`noti_web_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
	`noti_web_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
	`noti_web_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
	`noti_web_link` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
	`nav_id` tinyint NULL DEFAULT NULL,
	`nav_second_id` tinyint NULL DEFAULT NULL,
	`noti_web_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY (`noti_web_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

ALTER TABLE `noti_web`
	ADD COLUMN IF NOT EXISTS `nav_id` tinyint NULL DEFAULT NULL AFTER `noti_web_link`,
	ADD COLUMN IF NOT EXISTS `nav_second_id` tinyint NULL DEFAULT NULL AFTER `nav_id`;

SELECT 'SCHEMA_UPGRADE_COMPLETED' AS status;
