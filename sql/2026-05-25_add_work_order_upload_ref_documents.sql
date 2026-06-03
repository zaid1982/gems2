-- Add missing Work Order upload document types required by api/m_wo.php.
-- These rows satisfy sys_upload.document_id foreign keys for mobile uploads.

INSERT INTO `ref_document` (`document_id`, `document_desc`, `document_type`, `document_status`)
VALUES
    (27, 'Response Image', 'Work Request', 1),
    (28, 'Check By Signature', 'Work Order', 1)
ON DUPLICATE KEY UPDATE
    `document_desc` = VALUES(`document_desc`),
    `document_type` = VALUES(`document_type`),
    `document_status` = VALUES(`document_status`);