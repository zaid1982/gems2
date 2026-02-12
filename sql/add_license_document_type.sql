-- Add License Document type to ref_document table
-- Required for license file uploads (sys_upload.document_id FK constraint)
-- Run this script on production before using license file upload feature

INSERT INTO `ref_document` (`document_id`, `document_desc`, `document_type`, `document_status`) 
VALUES (29, 'License Document', 'License', 1)
ON DUPLICATE KEY UPDATE 
    `document_desc` = VALUES(`document_desc`),
    `document_type` = VALUES(`document_type`),
    `document_status` = VALUES(`document_status`);
