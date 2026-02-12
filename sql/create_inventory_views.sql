-- Create missing views for Inventory Image functionality
-- Run this on the production database to fix "Image of items unable to load" issue

-- View to join item images with upload details
CREATE OR REPLACE VIEW `vw_item_image` AS
SELECT 
    ii.item_image_id AS itemImageId,
    ii.item_id AS itemId,
    ii.upload_id AS uploadId,
    u.upload_name AS uploadName,
    u.upload_filename AS uploadFilename,
    u.upload_extension AS uploadExtension,
    u.upload_folder AS uploadFolder,
    u.upload_file_width AS uploadFileWidth,
    u.upload_file_height AS uploadFileHeight
FROM ref_item_image ii
INNER JOIN sys_upload u ON ii.upload_id = u.upload_id
WHERE u.upload_status = 1;

-- View to get parts with their associated images (grouped)
CREATE OR REPLACE VIEW `vw_part_with_image` AS
SELECT 
    p.part_id AS partId,
    p.site_id AS siteId,
    p.store_id AS storeId,
    p.asset_group_id AS assetGroupId,
    p.item_type_id AS itemTypeId,
    p.item_id AS itemId,
    p.part_count AS partCount,
    p.part_locked AS partLocked,
    p.part_threshold AS partThreshold,
    p.part_min_order AS partMinOrder,
    p.part_max_order AS partMaxOrder,
    p.part_remark AS partRemark,
    p.part_status AS partStatus,
    GROUP_CONCAT(DISTINCT CONCAT(u.upload_folder, '/', u.upload_filename, '.', u.upload_extension) ORDER BY ii.item_image_id SEPARATOR '||') AS uploadList,
    GROUP_CONCAT(DISTINCT u.upload_name ORDER BY ii.item_image_id SEPARATOR '||') AS titleList,
    GROUP_CONCAT(DISTINCT u.upload_file_width ORDER BY ii.item_image_id SEPARATOR '||') AS widthList,
    GROUP_CONCAT(DISTINCT u.upload_file_height ORDER BY ii.item_image_id SEPARATOR '||') AS heightList
FROM ast_part p
LEFT JOIN ref_item_image ii ON p.item_id = ii.item_id
LEFT JOIN sys_upload u ON ii.upload_id = u.upload_id AND u.upload_status = 1
GROUP BY p.part_id, p.site_id, p.store_id, p.asset_group_id, p.item_type_id, p.item_id,
         p.part_count, p.part_locked, p.part_threshold, p.part_min_order, p.part_max_order,
         p.part_remark, p.part_status;
