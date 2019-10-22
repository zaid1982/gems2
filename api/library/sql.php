<?php

class Class_sql
{

    function __construct()
    {
        // 1010 - 1019
    }

    private function get_exception($codes, $function, $line, $msg)
    {
        if ($msg != '') {
            $pos = strpos($msg, '-');
            if ($pos !== false)
                $msg = substr($msg, $pos + 2);
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "] - " . $msg;
        } else
            return "(ErrCode:" . $codes . ") [" . __CLASS__ . ":" . $function . ":" . $line . "]";
    }

    /**
     * @param $title
     * @return string
     * @throws Exception
     */
    public function get_sql($title)
    {
        try {
            if ($title == 'vw_profile') {
                $sql = "SELECT
                    TIMESTAMPDIFF(MINUTE, user_time_block, NOW()) + 1 AS minute_block,
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email,
                    sys_address.address_desc,
                    sys_address.address_postcode,
                    sys_address.address_city,
                    ref_state.state_desc
                FROM sys_user
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id
                LEFT JOIN sys_address ON sys_address.address_id = sys_user_profile.address_id
                LEFT JOIN ref_state ON ref_state.state_id = sys_address.state_id";
            } else if ($title == 'vw_roles') {
                $sql = "SELECT
                    ref_role.role_id AS roleId, 
                    ref_role.role_desc AS roleDesc, 
                    ref_role.role_type AS roleType
                FROM (SELECT DISTINCT(role_id) FROM sys_user_role WHERE user_id = [user_id] GROUP BY role_id) roles
                INNER JOIN ref_role ON roles.role_id = ref_role.role_id AND role_status = 1";
            } else if ($title === 'vw_menu') {
                $sql = "SELECT 
                    sys_nav.nav_id,
                    sys_nav.nav_desc,
                    sys_nav.nav_icon,
                    sys_nav.nav_page,
                    sys_nav_second.nav_second_id,
                    sys_nav_second.nav_second_desc,
                    sys_nav_second.nav_second_page
                FROM
                    (SELECT
                            nav_id, nav_second_id, MIN(nav_role_turn) AS turn
                    FROM sys_nav_role
                    WHERE role_id IN ([roles])
                    GROUP BY nav_id, nav_second_id) AS nav_role
                LEFT JOIN sys_nav ON sys_nav.nav_id = nav_role.nav_id
                LEFT JOIN sys_nav_second ON sys_nav_second.nav_second_id = nav_role.nav_second_id
                WHERE nav_status = 1 AND (ISNULL(sys_nav_second.nav_second_id) OR nav_second_status = 1)
                ORDER BY nav_role.turn";
            } else if ($title === 'vw_user_profile') {
                $sql = "SELECT 
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email
                FROM sys_user 
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id AND user_profile_status = 1";
            } else if ($title === 'vw_check_assigned') {
                $sql = "SELECT 
                    wfl_task_assign.* 
                FROM wfl_task_assign  
                INNER JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task_assign.transaction_id AND transaction_status = 4";
            } else if ($title === 'vw_user_list') {
                $sql = "SELECT
                    sys_user.*,
                    sys_user_profile.user_contact_no,
                    sys_user_profile.user_email,
                    sys_user_profile.designation_id,
                    user_group.group_id,
                    user_group.roles
                FROM sys_user
                LEFT JOIN sys_user_profile ON sys_user_profile.user_id = sys_user.user_id AND sys_user_profile.user_profile_status = 1
                LEFT JOIN
                    (
                        SELECT 
                            user_id, GROUP_CONCAT(role_id) AS roles, MIN(group_id) AS group_id
                        FROM sys_user_role
                        GROUP BY user_id
                    ) user_group ON user_group.user_id = sys_user.user_id";
            } else if ($title === 'vw_activity_by_status') {
                $sql = "SELECT
                    activity_status, COUNT(*) AS total
                FROM ast_activity 
                [where_str] 
                GROUP BY activity_status";
            } else if ($title === 'vw_activity_list') {
                $sql = "SELECT
                    ast_activity.*,
                    activity_asset.assets AS assets
                FROM ast_activity
                LEFT JOIN 
                    (SELECT activity_id, GROUP_CONCAT(asset_id) AS assets 
                    FROM ast_activity_asset 
                    GROUP BY activity_id) activity_asset ON activity_asset.activity_id = ast_activity.activity_id";
            } else if ($title === 'vw_user_by_role') {
                $sql = "SELECT
                    role_id, COUNT(*) AS total
                FROM sys_user_role
                GROUP BY role_id";
            } else if ($title === 'vw_contract') {
                $sql = "SELECT
                    cli_contract.*,
                    cli_site.client_id
                FROM cli_contract
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id";
            } else if ($title === 'vw_asset_type') {
                $sql = "SELECT
                    ast_asset_type.*,
                    ast_asset_category.asset_group_id,
                    group_model.total_model
                FROM ast_asset_type
                LEFT JOIN ast_asset_category ON ast_asset_category.asset_category_id = ast_asset_type.asset_category_id
                LEFT JOIN (
                    SELECT asset_type_id, COUNT(*) AS total_model
                    FROM ast_asset_model 
                    GROUP BY asset_type_id
                ) group_model ON group_model.asset_type_id = ast_asset_type.asset_type_id";
            } else if ($title === 'vw_asset_brand_group') {
                $sql = "SELECT
                    ast_asset_brand.*,
                    asset_model.asset_type_id
                FROM (
                        SELECT
                            asset_brand_id, asset_type_id
                        FROM ast_asset_model
                        GROUP BY asset_type_id, asset_brand_id
                    ) asset_model
                LEFT JOIN ast_asset_brand ON ast_asset_brand.asset_brand_id = asset_model.asset_brand_id";
            } else if ($title === 'vw_checklist_by_type') {
                $sql = "SELECT
                    'Asset Type' AS checklist_types,
                    ast_asset_type.asset_type_id,
                    ast_asset_category.asset_category_id,
                    ast_asset_category.asset_group_id,
                    group_checklist.total_checklist
                FROM ast_asset_type
                LEFT JOIN ast_asset_category ON ast_asset_category.asset_category_id = ast_asset_type.asset_category_id
                LEFT JOIN (
                    SELECT asset_type_id, COUNT(*) AS total_checklist
                    FROM ppm_checklist 
                    GROUP BY asset_type_id
                ) group_checklist ON group_checklist.asset_type_id = ast_asset_type.asset_type_id
                UNION
                SELECT 'Special Checklist' AS checklist_types, '' AS asset_type_id, '' AS asset_category_id, '' AS asset_group_id, COUNT(*) AS total_checklist
                FROM ppm_checklist WHERE checklist_type = 2";
            } else if ($title === 'vw_ppm_asset') {
                $sql = "SELECT 
                    ast_asset.*,
                    ppm.ppm_id,
                    ppm.ppm_task_no,
                    ppm.ppm_date_start,
                    ppm.checklist_id,
                    ppm.ppm_group_id AS ppm_group_id_ppm,
                    ppm.ppm_created_by,
                    ppm.ppm_time_created,
                    ppm.ppm_status,
                    cli_location_code.location_code_name
                FROM ast_asset 
                LEFT JOIN ppm ON ppm.asset_id = ast_asset.asset_id
                LEFT JOIN cli_location_code ON cli_location_code.location_code_id = ast_asset.location_code_id";
            } else if ($title === 'vw_ppm_asset_backdoor') { //  AND ppm_checklist.asset_type_id = ast_asset.asset_type_id
                $sql = "SELECT 
                    ppm_checklist.checklist_id AS checklist_id,
                    aa.total_user,
                    ppm.ppm_id,
                    ast_asset.*
                FROM ast_asset 
                LEFT JOIN ppm ON ppm.asset_id = ast_asset.asset_id
                LEFT JOIN ppm_checklist ON ppm_checklist.checklist_document_no = ast_asset.document_no AND checklist_status = 1 
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN (
                    select ppm_group_id, site_id, count(*) AS total_user
                    from ppm_group
                    GROUP BY ppm_group_id, site_id
                ) aa ON aa.ppm_group_id = ast_asset.ppm_group_id AND aa.site_id = cli_contract.site_id";
            } else if ($title === 'vw_technicians') {
                $sql = "SELECT
                    ppm_group_user.user_id
                FROM ppm_group_user 
                INNER JOIN ppm_group ON ppm_group.ppm_group_id = ppm_group_user.ppm_group_id AND role_id = 5               
                INNER JOIN sys_user ON sys_user.user_id = ppm_group_user.user_id AND sys_user.user_status = 1";
            } else if ($title === 'vw_technicians_ppm_monthly') {
                $sql = "SELECT
                    YEAR(ppm_task_schedule_date) AS ppm_year, 
                    MONTH(ppm_task_schedule_date) AS ppm_month, 
                    ppm_task_assigned_to, COUNT(*) AS total
                FROM ppm_task WHERE ppm_task_assigned_to IN ([technicians])
                GROUP BY ppm_year, ppm_month, ppm_task_assigned_to";
            } else if ($title === 'mw_task_ppm_pending') {
                $sql = "SELECT
                    wfl_task.*,
                    ppm_task.ppm_task_id,
                    ppm_task.ppm_task_start_date,
                    ppm_task.ppm_task_schedule_date,
                    wfl_transaction.transaction_no,
                    ast_asset.asset_no,
                    ast_asset_type.asset_type_name,
                    cli_site.site_name,
                    ref_status.status_desc,
                    task_frequency.frequency,
                    sys_user.user_first_name
                FROM wfl_task
                INNER JOIN wfl_checkpoint_user ON wfl_task.checkpoint_id = wfl_checkpoint_user.checkpoint_id
                    AND wfl_task.role_id = wfl_checkpoint_user.role_id AND wfl_task.group_id = wfl_checkpoint_user.group_id AND wfl_checkpoint_user.user_id = [user_id]
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id
                LEFT JOIN ppm_task ON ppm_task.transaction_id = wfl_transaction.transaction_id
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(frequency_name) AS frequency
                    FROM ppm_task_frequency
                    LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id
                    GROUP BY ppm_task_id) task_frequency ON task_frequency.ppm_task_id = ppm_task.ppm_task_id
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ppm_group_user ON ppm_group_user.ppm_group_id = ppm.ppm_group_id AND ppm_group_user.user_id = [user_id]
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                LEFT JOIN sys_user ON sys_user.user_id = ppm_task.ppm_task_assigned_to
                WHERE wfl_transaction.flow_id = 1 AND wfl_task.task_current = 1 AND ppm_task_start_date >= CURDATE() - INTERVAL 2 MONTH AND ppm_task_start_date <= CURDATE() + INTERVAL 1 MONTH 
                AND (task_claimed_user = [user_id] OR (task_claimed_user IS NULL AND (wfl_task.checkpoint_id <> 1 OR (wfl_task.checkpoint_id = 1 AND ppm_group_user.user_id = [user_id])) )) [rest_filter]";
            } else if ($title === 'mw_task_ppm_all') {
                $sql = "SELECT
                    ppm_task.*,
                    ast_asset.asset_no,
                    ast_asset_type.asset_type_name,
                    cli_site.site_name,
                    ref_status.status_desc,
                    GROUP_CONCAT(ppm_task_frequency.frequency_id) AS frequency
                FROM ppm_task 
                LEFT JOIN ppm_task_frequency ON ppm_task_frequency.ppm_task_id = ppm_task.ppm_task_id                
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFt JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                LEFT JOIN sys_user ON sys_user.user_id = ppm_task.ppm_task_assigned_to
                WHERE [rest_filter] GROUP BY ppm_task.ppm_task_id";
            } else if ($title === 'mw_task_ppm_calendar_count_all') {
                $sql = "SELECT
                    ppm_task_start_date, GROUP_CONCAT(status_desc) AS status, COUNT(*) AS total
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                WHERE ppm.contract_id IN ([contract_id]) AND YEAR(ppm_task_start_date) = [year] AND MONTH(ppm_task_start_date) = [month]
                GROUP BY ppm_task_start_date";
            } else if ($title === 'mw_ppm_section_a') {
                $sql = "SELECT
                    ppm_task.ppm_task_id,
                    ppm_task.ppm_task_schedule_date,
                    ppm.asset_id,
                    ast_asset_group.asset_group_name,
                    ast_asset_category.asset_category_name,
                    ast_asset_type.asset_type_name,
                    ast_asset_brand.asset_brand_name,
                    ast_asset_model.asset_model_name,
                    ast_asset.asset_no,
                    ast_asset.asset_name,
                    ast_asset.asset_location_code AS location_code_id,
                    ast_asset.asset_location_desc,
                    ast_asset.asset_capacity,
                    cli_site.site_name,
                    ppm_task.ppm_task_time_start,
                    ppm_task.ppm_task_time_serviced
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_group ON ast_asset_group.asset_group_id = ast_asset.asset_group_id
                LEFT JOIN ast_asset_category ON ast_asset_category.asset_category_id = ast_asset.asset_category_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN ast_asset_brand ON ast_asset_brand.asset_brand_id = ast_asset.asset_brand_id
                LEFT JOIN ast_asset_model ON ast_asset_model.asset_model_id = ast_asset.asset_model_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id";
            } else if ($title === 'mw_ppm_section_h') {
                $sql = "SELECT 
                    ppm_task_upload_id,
                    ppm_task_upload_type,
                    ppm_task_id,
                    ppm_task_upload_longitude,
                    ppm_task_upload_latitude,
                    ppm_task_upload_timestamp,
                    ppm_task_upload_desc,
                    ref_document.document_desc,
                    ref_document.document_type,
                    sys_upload.upload_id,
                    sys_upload.upload_folder,
                    sys_upload.upload_filename,
                    sys_upload.upload_extension,
                    sys_upload.upload_name,
                    sys_upload.upload_uplname
                FROM ppm_task_upload
                LEFT JOIN sys_upload ON sys_upload.upload_id = ppm_task_upload.upload_id
                LEFT JOIN ref_document ON ref_document.document_id = sys_upload.document_id";
            } else if ($title === 'vw_sys_upload') {
                $sql = "SELECT 
                    upload_id,
                    upload_folder,
                    upload_filename,
                    upload_extension,
                    upload_name,
                    upload_uplname,
                    upload_time_upload
                FROM sys_upload";
            } else if ($title === 'vw_ppm_scheduled') {
                $sql = "SELECT
                    ppm_task.*,
                    task_frequency.frequency
                FROM ppm_task
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(frequency_name SEPARATOR ', ') AS frequency
                    FROM ppm_task_frequency
                    LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id
                    GROUP BY ppm_task_id) task_frequency ON task_frequency.ppm_task_id = ppm_task.ppm_task_id";
            } else if ($title === 'vw_track_monitoring') {
                $sql = "SELECT
                    transaction_no,
                    wfl_transaction.group_id AS trans_group,
                    wfl_transaction.user_id AS trans_user,
                    transaction_time_created,
                    transaction_date_due,
                    transaction_time_complete,
                    transaction_status,
                    flow_id,
                    wfl_task.*
                FROM wfl_task
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id";
            } else if ($title === 'vw_track_monitoring_wo_m') {
                $sql = "SELECT
                    transaction_no,
                    transaction_time_created,
                    transaction_date_due,
                    transaction_time_complete,
                    transaction_status,
                    flow_id,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                     WHEN wo_task_type = 2 THEN 'Self Finding'
                     WHEN wo_task_type = 3 THEN 'Request'
                     WHEN wo_task_type = 4 THEN 'Breakdown'
                     WHEN wo_task_type = 5 THEN 'Defect'
                     ELSE '' END AS wo_task_type,      
                    wo_task_severity,
                    wo_task_assigned_to,
                    wo_task.site_id,
                    wo_task_created_by,
                    wfl_task.*
                FROM wfl_task
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id
                INNER JOIN wo_task ON wo_task.transaction_id = wfl_task.transaction_id";
            } else if ($title === 'vg_track_monitoring_wo_search_m') {
                $sql = "SELECT
                    transaction_no,
                    transaction_time_created,
                    transaction_date_due,
                    transaction_time_complete,
                    transaction_status,
                    wfl_transaction.flow_id,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                     WHEN wo_task_type = 2 THEN 'Self Finding'
                     WHEN wo_task_type = 3 THEN 'Request'
                     WHEN wo_task_type = 4 THEN 'Breakdown'
                     WHEN wo_task_type = 5 THEN 'Defect'
                     ELSE '' END AS wo_task_type,                     
                    CASE WHEN wo_task_severity = 1 THEN 'Non-Critical'
                     WHEN wo_task_severity = 2 THEN 'Critical'
                     ELSE '' END AS wo_task_severity,
                    wo_task_assigned_to,
                    wo_task.site_id,
                    wfl_flow.flow_desc,
                    wfl_checkpoint.checkpoint_desc,
                    sys_user.user_first_name,
                    ref_status.status_desc,
                    user_assigned.user_first_name AS assigned_name,
                    wfl_task.*
                FROM wfl_task
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id
                INNER JOIN wo_task ON wo_task.transaction_id = wfl_task.transaction_id
                LEFT JOIN wfl_flow ON wfl_flow.flow_id = wfl_transaction.flow_id
                LEFT JOIN wfl_checkpoint ON wfl_checkpoint.checkpoint_id = wfl_task.checkpoint_id
                LEFT JOIN sys_user ON sys_user.user_id = wo_task.wo_task_created_by
                LEFT JOIN sys_user user_assigned ON user_assigned.user_id = wo_task.wo_task_assigned_to
                LEFT JOIN ref_status ON ref_status.status_id = wfl_transaction.transaction_status";
            } else if ($title === 'vw_track_monitoring_ppm_m') {
                $sql = "SELECT
                    transaction_no,
                    transaction_time_created,
                    transaction_date_due,
                    transaction_time_complete,
                    transaction_status,
                    flow_id,
                    asset_no,
                    ppm_task_start_date,
                    ppm.contract_id,
                    wfl_task.*
                FROM wfl_task
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id
                LEFT JOIN ppm_task ON ppm_task.transaction_id = wfl_task.transaction_id
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id";
            } else if ($title === 'vw_track_monitoring_ppm_search_m') {
                $sql = "SELECT
                    transaction_no,
                    transaction_time_created,
                    transaction_date_due,
                    transaction_time_complete,
                    transaction_status,
                    wfl_transaction.flow_id,
                    asset_no,
                    ppm_task_start_date,
                    ppm.contract_id,
                    wfl_flow.flow_desc,
                    wfl_checkpoint.checkpoint_desc,
                    sys_user.user_first_name AS user_first_name,
                    ref_status.status_desc,
                    wfl_task.*
                FROM wfl_task
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id
                LEFT JOIN ppm_task ON ppm_task.transaction_id = wfl_task.transaction_id
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN wfl_flow ON wfl_flow.flow_id = wfl_transaction.flow_id
                LEFT JOIN wfl_checkpoint ON wfl_checkpoint.checkpoint_id = wfl_task.checkpoint_id
                LEFT JOIN sys_user ON sys_user.user_id = ppm_task.ppm_task_assigned_to
                LEFT JOIN sys_user checked_by ON checked_by.user_id = ppm_task.ppm_task_checked_by
                LEFT JOIN sys_user verified_by ON verified_by.user_id = ppm_task.ppm_task_verified_by
                LEFT JOIN ref_status ON ref_status.status_id = wfl_transaction.transaction_status";
            } else if ($title === 'vw_count_asset') {
                $sql = "SELECT count(*) AS total FROM ast_asset";
            } else if ($title === 'vw_count_ppm_task') {
                $sql = "SELECT 
                    count(*) AS total 
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id";
            } else if ($title === 'vw_location_code_with_count') {
                $sql = "SELECT
                    cli_location_code.*,
                    contract_user.total
                FROM cli_location_code
                LEFT JOIN (
                        SELECT location_code_id, COUNT(*) AS total FROM cli_contract_user WHERE contract_id = [contract_id] GROUP BY location_code_id
                    ) contract_user ON contract_user.location_code_id = cli_location_code.location_code_id
                WHERE site_id = [site_id]";
            } else if ($title === 'vw_ppm_group') {
                $sql = "SELECT
                    ppm_group.*,
                    ppm_group_report.ppm_group_name AS report_to,
                    group_user.total_user
                FROM ppm_group
                LEFT JOIN ppm_group ppm_group_report ON ppm_group_report.ppm_group_id = ppm_group.ppm_group_report_to
                LEFT JOIN (
                    SELECT ppm_group_id, COUNT(*) AS total_user
                    FROM ppm_group_user 
                    GROUP BY ppm_group_id
                ) group_user ON group_user.ppm_group_id = ppm_group.ppm_group_id";
            } else if ($title === 'vw_ppm_least_task') {
                $sql = "SELECT 
                        sys_user.user_id, SUM(IF(wfl_transaction.transaction_id IS NOT NULL, 1, 0)) AS total
                FROM sys_user
                LEFT JOIN wfl_task_assign ON wfl_task_assign.user_id = sys_user.user_id AND wfl_task_assign.checkpoint_id NOT IN (11,12)
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task_assign.transaction_id AND MONTH(transaction_time_update) = MONTH(CURDATE()) AND YEAR(transaction_time_update) = YEAR(CURDATE()) 
                WHERE sys_user.user_id IN ([user_ids]) 
                GROUP BY sys_user.user_id";
            } else if ($title === 'mw_wo_submitted_m') {
                $sql = "SELECT
                    wo_task.*,
                    sys_user.user_first_name,
                    sys_user_assigned.user_first_name AS assigned_to,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        ELSE ''
                    END AS wo_task_type_desc,
                    CASE WHEN wo_task_severity = 1 THEN 'Non-Critical'
                        WHEN wo_task_severity = 2 THEN 'Critical'
                        ELSE ''
                    END AS wo_task_severity_desc
                FROM wo_task 
                LEFT JOIN sys_user ON sys_user.user_id = wo_task.wo_task_created_by
                LEFT JOIN sys_user sys_user_assigned ON sys_user_assigned.user_id = wo_task.wo_task_assigned_to
                WHERE wo_task_created_by = [user_id] 
                HAVING (wo_task_no LIKE '%[search_text]%' OR wo_task_location LIKE '%[search_text]%' OR sys_user.user_first_name LIKE '%[search_text]%')";
            } else if ($title === 'mw_wo_pending_m') {
                $sql = "SELECT
                    wo_task.*,
                    sys_user.user_first_name,
                    sys_user_assigned.user_first_name AS assigned_to,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        ELSE ''
                    END AS wo_task_type_desc,
                    CASE WHEN wo_task_severity = 1 THEN 'Non-Critical'
                        WHEN wo_task_severity = 2 THEN 'Critical'
                        ELSE ''
                    END AS wo_task_severity_desc,
                    wfl_task.checkpoint_id
                FROM wfl_task
                INNER JOIN wo_task ON wo_task.transaction_id = wfl_task.transaction_id
                INNER JOIN wfl_checkpoint_user ON wfl_checkpoint_user.checkpoint_id = wfl_task.checkpoint_id AND wfl_checkpoint_user.role_id = wfl_task.role_id AND wfl_checkpoint_user.group_id = wfl_task.group_id AND wfl_checkpoint_user.user_id = [user_id]
                LEFT JOIN sys_user ON sys_user.user_id = wo_task.wo_task_created_by
                LEFT JOIN sys_user sys_user_assigned ON sys_user_assigned.user_id = wfl_checkpoint_user.user_id
                WHERE task_current = 1 AND (task_claimed_user IS NULL OR task_claimed_user = [user_id]) 
                AND (wfl_task.checkpoint_id <> 12 OR (wfl_task.checkpoint_id = 12 AND wo_task.site_id = sys_user_assigned.site_id))
                HAVING (wo_task_no LIKE '%[search_text]%' OR wo_task_location LIKE '%[search_text]%' OR sys_user.user_first_name LIKE '%[search_text]%' OR assigned_to LIKE '%[search_text]%' OR wo_task_type_desc LIKE '%[search_text]%' OR wo_task_severity_desc LIKE '%[search_text]%')";
            } else if ($title === 'mw_wo_upload') {
                $sql = "SELECT 
                    wo_task_upload_id,
                    wo_task_upload_type,
                    wo_task_id,
                    wo_task_upload_longitude,
                    wo_task_upload_latitude,
                    wo_task_upload_timestamp,
                    wo_task_upload_desc,
                    ref_document.document_desc,
                    ref_document.document_type,
                    sys_upload.upload_id,
                    sys_upload.upload_folder,
                    sys_upload.upload_filename,
                    sys_upload.upload_extension,
                    sys_upload.upload_name,
                    sys_upload.upload_uplname
                FROM wo_task_upload
                LEFT JOIN sys_upload ON sys_upload.upload_id = wo_task_upload.upload_id
                LEFT JOIN ref_document ON ref_document.document_id = sys_upload.document_id";
            } else if ($title === 'mw_ppm_group_user') {
                $sql = "SELECT
                    ppm_group_user.user_id,
                    sys_user.user_first_name,
                    ppm_group.*
                FROM ppm_group_user 
                LEFT JOIN ppm_group ON ppm_group.ppm_group_id = ppm_group_user.ppm_group_id
                LEFT JOIN sys_user ON sys_user.user_id = ppm_group_user.user_id";
            } else if ($title === 'mw_checkpoint_user_with_site') {
                $sql = "SELECT
                    wfl_checkpoint_user.*,
                    sys_user.site_id
                FROM wfl_checkpoint_user 
                LEFT JOIN sys_user ON sys_user.user_id = wfl_checkpoint_user.user_id";
            } else if ($title === 'mw_wo_execute_duration') {
                $sql = "SELECT
                    SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, task_time_created, task_time_submit))) as duration
                FROM wfl_task
                WHERE transaction_id = [transaction_id] AND task_time_submit IS NOT NULL AND checkpoint_id = 13";
            } else if ($title === 'vg_count_wo_by_site_status') {
                $sql = "SELECT 
                    site_id, wo_task_status, count(*) AS total 
                FROM wo_task 
                WHERE YEAR(wo_task_time_created) = [cur_year] AND MONTH(wo_task_time_created) - 1 = [cur_month]
                GROUP BY site_id, wo_task_status";
            } else if ($title === 'vg_count_wo_by_site_type') {
                $sql = "SELECT 
                    site_id, wo_task_type, count(*) AS total 
                FROM wo_task 
                WHERE YEAR(wo_task_time_created) = [cur_year] AND MONTH(wo_task_time_created) - 1 = [cur_month]
                GROUP BY site_id, wo_task_type";
            } else if ($title === 'vg_count_wo_by_site_group') {
                $sql = "SELECT 
                    wo_task.site_id, wo_task.ppm_group_id, ppm_group_name, count(*) AS total 
                FROM wo_task 
                LEFT JOIN ppm_group ON ppm_group.ppm_group_id = wo_task.ppm_group_id
                WHERE YEAR(wo_task_time_created) = [cur_year] AND MONTH(wo_task_time_created) - 1 = [cur_month]
                GROUP BY wo_task.site_id, wo_task.ppm_group_id ORDER BY wo_task.ppm_group_id";
            } else if ($title === 'vg_count_ppm_by_site_status') {
                $sql = "SELECT 
                    site_id, ppm_task_status, count(*) AS total 
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                WHERE YEAR(ppm_task_start_date) = [cur_year] AND MONTH(ppm_task_start_date) - 1 = [cur_month]
                GROUP BY site_id, ppm_task_status";
            } else if ($title === 'vg_count_ppm_by_site_trade') {
                $sql = "SELECT 
                    site_id, asset_group_id, count(*) AS total 
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                WHERE YEAR(ppm_task_start_date) = [cur_year] AND MONTH(ppm_task_start_date) - 1 = [cur_month]
                GROUP BY site_id, asset_group_id";
            } else if ($title === 'vg_report_wo_summary') {
                $sql = "SELECT                     
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        ELSE '' END AS task_type
                        [sum_site_str]
                FROM wo_task
                LEFT JOIN cli_site ON cli_site.site_id = wo_task.site_id
                WHERE cli_site.client_id = [client_id] AND YEAR(wo_task_time_created) = [selected_year] AND MONTH(wo_task_time_created) = [selected_month]
                GROUP BY wo_task_type
                UNION
                SELECT
                    'TOTAL' AS task_type
                    [sum_site_str]
                 FROM wo_task
                LEFT JOIN cli_site ON cli_site.site_id = wo_task.site_id
                WHERE cli_site.client_id = [client_id] AND YEAR(wo_task_time_created) = [selected_year] AND MONTH(wo_task_time_created) = [selected_month]
                ";
            } else if ($title === 'vg_report_ppm_summary') {
                $sql = "SELECT                     
                    asset_type_name, 
                    task_frequency.frequency,
                    COUNT(DISTINCT(ast_asset.asset_id)) AS no_asset,
                    COUNT(*) AS total_ppm, 
                    SUM(IF(ppm_task_status = 16, 1, 0)) AS total_ppm_done
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ast_asset ON ppm.asset_id = ast_asset.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id 
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(frequency_name SEPARATOR ', ') AS frequency
                    FROM ppm_task_frequency
                    LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id
                    GROUP BY ppm_task_id) task_frequency ON task_frequency.ppm_task_id = ppm_task.ppm_task_id
                WHERE YEAR(ppm_task_start_date) = [selected_year] AND MONTH(ppm_task_start_date) = [selected_month] AND cli_contract.site_id = [site_id]  
                GROUP BY ast_asset.asset_type_id";
            } else if ($title === 'vg_report_wo_total') {
                $sql = "SELECT               
                    cli_site.site_id,      
                    cli_site.site_name, 
                    SUM(IF(wo_task_type = 1 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open1, 
                    SUM(IF(wo_task_type = 1 AND wo_task_status IN (16, 25), 1, 0)) AS closed1, 
                    SUM(IF(wo_task_type = 2 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open2, 
                    SUM(IF(wo_task_type = 2 AND wo_task_status IN (16, 25), 1, 0)) AS closed2, 
                    SUM(IF(wo_task_type = 3 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open3, 
                    SUM(IF(wo_task_type = 3 AND wo_task_status IN (16, 25), 1, 0)) AS closed3, 
                    SUM(IF(wo_task_type = 4 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open4, 
                    SUM(IF(wo_task_type = 4 AND wo_task_status IN (16, 25), 1, 0)) AS closed4, 
                    SUM(IF(wo_task_type = 5 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open5, 
                    SUM(IF(wo_task_type = 5 AND wo_task_status IN (16, 25), 1, 0)) AS closed5
                FROM cli_site 
                LEFT JOIN wo_task ON cli_site.site_id = wo_task.site_id AND YEAR(wo_task_time_created) = [selected_year] AND MONTH(wo_task_time_created) = [selected_month]
                WHERE site_is_launched = 1
                GROUP BY cli_site.site_id";
            } else if ($title === 'vg_report_ppm_total') {
                $sql = "SELECT                
                    cli_site.site_id,     
                    site_name, 
                    SUM(IF(ppm_task_status <> 16, 1, 0)) AS total_ppm_not,
                    SUM(IF(ppm_task_status = 16, 1, 0)) AS total_ppm_done
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                WHERE cli_site.site_is_launched = 1 AND YEAR(ppm_task_start_date) = [selected_year] AND MONTH(ppm_task_start_date) = [selected_month]
                GROUP BY cli_contract.site_id";
            } else if ($title === 'vg_report_site_manual') {
                $sql = "SELECT
                    cli_site.site_id,
                    site_name,
                    SUM(site_manual_open0) AS total_manual_open0,
                    SUM(site_manual_closed0) AS total_manual_closed0,
                    SUM(site_manual_open1) AS total_manual_open1,
                    SUM(site_manual_closed1) AS total_manual_closed1,
                    SUM(site_manual_open2) AS total_manual_open2,
                    SUM(site_manual_closed2) AS total_manual_closed2,
                    SUM(site_manual_open3) AS total_manual_open3,
                    SUM(site_manual_closed3) AS total_manual_closed3,
                    SUM(site_manual_open4) AS total_manual_open4,
                    SUM(site_manual_closed4) AS total_manual_closed4,
                    SUM(site_manual_open5) AS total_manual_open5,
                    SUM(site_manual_closed5) AS total_manual_closed5
                FROM cli_site
                LEFT JOIN cli_site_manual ON cli_site_manual.site_id = cli_site.site_id AND YEAR(cli_site_manual.site_manual_date) = [selected_year] AND MONTH(cli_site_manual.site_manual_date) = [selected_month]
                WHERE site_is_manual = 1
                GROUP BY cli_site.site_id";
            } else if ($title === 'vg_report_wo_daily') {
                $sql = "SELECT 
                    dates, 
                    SUM(open0) AS combine_open0, 
                    SUM(closed0) AS combine_closed0, 
                    SUM(open1) AS combine_open1, 
                    SUM(closed1) AS combine_closed1, 
                    SUM(open2) AS combine_open2, 
                    SUM(closed2) AS combine_closed2, 
                    SUM(open3) AS combine_open3, 
                    SUM(closed3) AS combine_closed3, 
                    SUM(open4) AS combine_open4, 
                    SUM(closed4) AS combine_closed4, 
                    SUM(open5) AS combine_open5, 
                    SUM(closed5) AS combine_closed5 
                FROM (
                    SELECT               
                        date(wo_task_time_created) AS dates, 
                        0 AS open0, 0 AS closed0,
                        SUM(IF(wo_task_type = 1 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open1, 
                        SUM(IF(wo_task_type = 1 AND wo_task_status IN (16, 25), 1, 0)) AS closed1, 
                        SUM(IF(wo_task_type = 2 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open2, 
                        SUM(IF(wo_task_type = 2 AND wo_task_status IN (16, 25), 1, 0)) AS closed2, 
                        SUM(IF(wo_task_type = 3 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open3, 
                        SUM(IF(wo_task_type = 3 AND wo_task_status IN (16, 25), 1, 0)) AS closed3, 
                        SUM(IF(wo_task_type = 4 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open4, 
                        SUM(IF(wo_task_type = 4 AND wo_task_status IN (16, 25), 1, 0)) AS closed4, 
                        SUM(IF(wo_task_type = 5 AND wo_task_status NOT IN (16, 25), 1, 0)) AS open5, 
                        SUM(IF(wo_task_type = 5 AND wo_task_status IN (16, 25), 1, 0)) AS closed5
                    FROM wo_task 
                    WHERE site_id = [site_id] AND YEAR(wo_task_time_created) = [selected_year] AND MONTH(wo_task_time_created) = [selected_month]
                    GROUP BY dates
                    UNION 
                    SELECT                
                        DATE(ppm_task_start_date) AS dates, 
                        SUM(IF(ppm_task_status <> 16, 1, 0)) AS open0,
                        SUM(IF(ppm_task_status = 16, 1, 0)) AS closed0,
                        0 AS open1, 0 AS closed1,
                        0 AS open2, 0 AS closed2,
                        0 AS open3, 0 AS closed3,
                        0 AS open4, 0 AS closed4,
                        0 AS open5, 0 AS closed5
                    FROM ppm_task 
                    LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                    LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                    WHERE cli_contract.site_id = [site_id] AND YEAR(ppm_task_start_date) = [selected_year] AND MONTH(ppm_task_start_date) = [selected_month]
                    GROUP BY dates) aa 
                GROUP BY dates";
            } else {
                throw new Exception($this->get_exception('0098', __FUNCTION__, __LINE__, 'Sql not exist : ' . $title));
            }
            return $sql;
        } catch (Exception $e) {
            if ($e->getCode() == 30) {
                $errCode = 32;
            } else {
                $errCode = $e->getCode();
            }
            throw new Exception($this->get_exception('0099', __FUNCTION__, __LINE__, $e->getMessage()), $errCode);
        }
    }

}

?>
