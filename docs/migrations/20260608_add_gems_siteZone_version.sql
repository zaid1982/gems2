-- Required by asset.html and ppm_management.html (zone/ref local cache).
-- zone.php bumps version_id 38 on cli_zone create/update/delete/import.
INSERT INTO sys_version (version_id, version_name, version_no, version_status)
VALUES (38, 'gems_siteZone', 1, 1)
ON DUPLICATE KEY UPDATE version_name = 'gems_siteZone';
