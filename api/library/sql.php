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
                            nav_id, nav_second_id, MAX(nav_role_turn) AS turn
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
                    ppm_frequency.frequency_name AS frequency,
                    sys_user.user_first_name
                FROM wfl_task
                INNER JOIN wfl_checkpoint_user ON wfl_task.checkpoint_id = wfl_checkpoint_user.checkpoint_id
                    AND wfl_task.role_id = wfl_checkpoint_user.role_id AND wfl_task.group_id = wfl_checkpoint_user.group_id AND wfl_checkpoint_user.user_id = [user_id]
                LEFT JOIN wfl_transaction ON wfl_transaction.transaction_id = wfl_task.transaction_id
                LEFT JOIN ppm_task ON ppm_task.transaction_id = wfl_transaction.transaction_id                
                LEFT JOIN ppm_task_frequency ON ppm_task_frequency.ppm_task_id = ppm_task.ppm_task_id
                LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id               
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ppm_group_user ON ppm_group_user.ppm_group_id = ppm.ppm_group_id AND ppm_group_user.user_id = [user_id]
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                LEFT JOIN sys_user ON sys_user.user_id = ppm_task.ppm_task_assigned_to
                WHERE wfl_transaction.flow_id = 1 AND wfl_task.task_current = 1 AND ppm_task_status NOT IN (3, 53) AND ppm_task_start_date >= CURDATE() - INTERVAL 2 MONTH AND ppm_task_start_date <= CURDATE() + INTERVAL 1 MONTH 
                AND (task_claimed_user = [user_id] OR (task_claimed_user IS NULL AND (wfl_task.checkpoint_id <> 1 OR (wfl_task.checkpoint_id = 1 AND ppm_group_user.user_id = [user_id])) )) [rest_filter] GROUP BY ppm_task.ppm_task_id";
            } else if ($title === 'mw_task_ppm_pending_scan') {
                $sql = "SELECT
                    ppm_task.ppm_task_id,
                    ppm_task.ppm_task_no,
                    ast_asset_type.asset_type_name,
                    ast_asset.asset_no,
                    ppm_task.ppm_task_schedule_date,
                    ref_status.status_desc,
                    GROUP_CONCAT(ppm_frequency.frequency_name) AS frequency
                FROM ppm_task          
                LEFT JOIN ppm_task_frequency ON ppm_task_frequency.ppm_task_id = ppm_task.ppm_task_id
                LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id               
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                INNER JOIN ppm_group_user ON ppm_group_user.ppm_group_id = ppm.ppm_group_id AND ppm_group_user.user_id = [user_id]
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                WHERE ppm.asset_id = [asset_id] AND ppm_task_status IN (12, 13) AND (ppm_task_assigned_to = 1 OR ppm_task_assigned_to IS NULL)                
                AND ppm_task_start_date >= CURDATE() - INTERVAL 2 MONTH AND ppm_task_start_date <= CURDATE() + INTERVAL 1 MONTH 
                GROUP BY ppm_task.ppm_task_id";
            } else if ($title === 'mw_task_ppm_all') {
                $sql = "SELECT DISTINCT
                    ppm_task.ppm_task_id,
                    ppm_task.ppm_task_no,
                    ppm_task.ppm_task_start_date,
                    ppm_task.ppm_task_status,
                    ppm_task.ppm_task_assigned_to,
                    ppm_task.ppm_id,
                    ast_asset.asset_no,
                    ast_asset_type.asset_type_name,
                    cli_site.site_name,
                    ref_status.status_desc,
                    (SELECT GROUP_CONCAT(frequency_id) 
                     FROM ppm_task_frequency 
                     WHERE ppm_task_frequency.ppm_task_id = ppm_task.ppm_task_id) AS frequency
                FROM ppm_task 
                INNER JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                INNER JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                WHERE [rest_filter]";
            } else if ($title === 'mw_task_ppm_all_fast') {
                $sql = "SELECT
                    ppm_task.ppm_task_id,
                    ppm_task.ppm_task_no,
                    ppm_task.ppm_task_start_date,
                    ppm_task.ppm_task_status,
                    ppm_task.ppm_task_assigned_to,
                    ppm_task.ppm_id,
                    ast_asset.asset_no,
                    ast_asset_type.asset_type_name,
                    cli_site.site_name,
                    ref_status.status_desc
                FROM ppm_task 
                INNER JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                INNER JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ast_asset.asset_type_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                WHERE [rest_filter]";
            } else if ($title === 'mw_task_ppm_calendar_count_all') {
                $sql = "SELECT
                    ppm_task_start_date, GROUP_CONCAT(status_desc) AS status, COUNT(*) AS total
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ref_status ON ref_status.status_id = ppm_task.ppm_task_status
                WHERE ppm.contract_id IN ([contract_id]) AND ppm_task_status NOT IN (3, 53) AND YEAR(ppm_task_start_date) = [year] AND MONTH(ppm_task_start_date) = [month]
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
                    ppm.ppm_id,
                    ppm.ppm_is_group,
                    ppm_task.ppm_task_time_start,
                    ppm_task.ppm_task_time_serviced
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_type ON ast_asset_type.asset_type_id = ppm.asset_type_id
                LEFT JOIN ast_asset_category ON ast_asset_category.asset_category_id = ast_asset_type.asset_category_id
                LEFT JOIN ast_asset_group ON ast_asset_group.asset_group_id = ast_asset_category.asset_group_id
                LEFT JOIN ast_asset_brand ON ast_asset_brand.asset_brand_id = ast_asset.asset_brand_id
                LEFT JOIN ast_asset_model ON ast_asset_model.asset_model_id = ast_asset.asset_model_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ast_asset.contract_id
                LEFT JOIN cli_site ON cli_site.site_id = cli_contract.site_id";
            } else if ($title === 'mw_ppm_section_a_asset_group') {
                $sql = "SELECT
                    ast.asset_id,
                    ast.asset_no,
                    ast.asset_name,
                    ast.asset_location_code AS location_code_id,
                    ast.asset_location_desc,
                    ast.asset_capacity,
                    asb.asset_brand_name,
                    asm.asset_model_name
                FROM ppm_asset pst
                LEFT JOIN ast_asset ast ON ast.asset_id = pst.asset_id
                LEFT JOIN ast_asset_brand asb ON asb.asset_brand_id = ast.asset_brand_id
                LEFT JOIN ast_asset_model asm ON asm.asset_model_id = ast.asset_model_id";
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
                     WHEN wo_task_type = 6 THEN 'Public Complaint'
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
                     WHEN wo_task_type = 6 THEN 'Public Complaint'
                     ELSE '' END AS wo_task_type,          
                    ref_severity.severity_name AS wo_task_severity,
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
                LEFT JOIN ref_status ON ref_status.status_id = wfl_transaction.transaction_status
                LEFT JOIN ref_severity ON ref_severity.severity_id = wo_task.wo_task_severity";
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
                    CASE WHEN wo_task_type_init = 1 THEN 'Client Complaint'
                        WHEN wo_task_type_init = 2 THEN 'Self Finding'
                        WHEN wo_task_type_init = 3 THEN 'Request'
                        WHEN wo_task_type_init = 4 THEN 'Breakdown'
                        WHEN wo_task_type_init = 5 THEN 'Defect'
                        WHEN wo_task_type_init = 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_task_type_init_desc,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        WHEN wo_task_type = 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_task_type_desc,
                    ref_severity.severity_name AS wo_task_severity_desc,
                    IF(wo_task_is_wr = 1 AND wo_task_wr_confirm = 0, 'WR', 'WO') AS wo_type
                FROM wo_task 
                LEFT JOIN sys_user ON sys_user.user_id = wo_task.wo_task_created_by
                LEFT JOIN sys_user sys_user_assigned ON sys_user_assigned.user_id = wo_task.wo_task_assigned_to
                LEFT JOIN ref_severity ON ref_severity.severity_id = wo_task.wo_task_severity
                WHERE wo_task_created_by = [user_id] 
                HAVING (wo_task_no LIKE '%[search_text]%' OR wo_task_location LIKE '%[search_text]%' OR sys_user.user_first_name LIKE '%[search_text]%') AND wo_type LIKE '%[wo_type]%'";
            } else if ($title === 'mw_wo_pending_m') {
                $sql = "SELECT
                    wo_task.*,
                    sys_user.user_first_name,
                    sys_user_assigned.user_first_name AS assigned_to,
                    CASE WHEN wo_task_type_init = 1 THEN 'Client Complaint'
                        WHEN wo_task_type_init = 2 THEN 'Self Finding'
                        WHEN wo_task_type_init = 3 THEN 'Request'
                        WHEN wo_task_type_init = 4 THEN 'Breakdown'
                        WHEN wo_task_type_init = 5 THEN 'Defect'
                        WHEN wo_task_type_init = 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_task_type_init_desc,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        WHEN wo_task_type = 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_task_type_desc,
                    ref_severity.severity_name AS wo_task_severity_desc,
                    wfl_task.checkpoint_id,
                    IF(wo_task_is_wr = 1 AND wo_task_wr_confirm = 0, 'WR', 'WO') AS wo_type
                FROM wfl_task
                INNER JOIN wo_task ON wo_task.transaction_id = wfl_task.transaction_id
                INNER JOIN wfl_checkpoint_user ON wfl_checkpoint_user.checkpoint_id = wfl_task.checkpoint_id AND wfl_checkpoint_user.role_id = wfl_task.role_id AND wfl_checkpoint_user.group_id = wfl_task.group_id AND wfl_checkpoint_user.user_id = [user_id]
                LEFT JOIN sys_user ON sys_user.user_id = wo_task.wo_task_created_by
                LEFT JOIN sys_user sys_user_assigned ON sys_user_assigned.user_id = wfl_checkpoint_user.user_id
                LEFT JOIN ref_severity ON ref_severity.severity_id = wo_task.wo_task_severity
                WHERE task_current = 1 AND (task_claimed_user IS NULL OR task_claimed_user = [user_id]) 
                AND wo_task.site_id = sys_user_assigned.site_id
                HAVING (wo_task_no LIKE '%[search_text]%' OR wo_task_location LIKE '%[search_text]%' OR sys_user.user_first_name LIKE '%[search_text]%' OR assigned_to LIKE '%[search_text]%' OR wo_task_type_desc LIKE '%[search_text]%' OR wo_task_severity_desc LIKE '%[search_text]%') AND wo_type LIKE '%[wo_type]%'";
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
                WHERE DATE(wo_task_time_created) >= '[date_start]' AND DATE(wo_task_time_created) <= '[date_end]'
                GROUP BY site_id, wo_task_status";
            } else if ($title === 'vg_count_wo_by_site_type') {
                $sql = "SELECT 
                    site_id, wo_task_type, count(*) AS total 
                FROM wo_task 
                WHERE DATE(wo_task_time_created) >= '[date_start]' AND DATE(wo_task_time_created) <= '[date_end]'
                GROUP BY site_id, wo_task_type";
            } else if ($title === 'vg_count_wo_by_site_group') {
                $sql = "SELECT 
                    wo_task.site_id, wo_task.ppm_group_id, ppm_group_name, count(*) AS total 
                FROM wo_task 
                LEFT JOIN ppm_group ON ppm_group.ppm_group_id = wo_task.ppm_group_id
                WHERE DATE(wo_task_time_created) >= '[date_start]' AND DATE(wo_task_time_created) <= '[date_end]'
                GROUP BY wo_task.site_id, wo_task.ppm_group_id ORDER BY wo_task.ppm_group_id";
            } else if ($title === 'vg_wo_top5_execute') {
                $sql = "SELECT
                    wo_task_fixed_by, 
                    COUNT(*) AS total
                FROM wo_task 
                WHERE wo_task_fixed_by IS NOT NULL AND site_id [site_id]
                AND DATE(wo_task_time_created) >= '[date_start]' AND DATE(wo_task_time_created) <= '[date_end]'
                GROUP BY wo_task_fixed_by ORDER BY total DESC LIMIT 5";
            } else if ($title === 'vg_wo_bottom5_execute') {
                $sql = "SELECT
                    wo_task_fixed_by, 
                    COUNT(*) AS total
                FROM wo_task 
                WHERE wo_task_fixed_by IS NOT NULL AND site_id [site_id]
                AND DATE(wo_task_time_created) >= '[date_start]' AND DATE(wo_task_time_created) <= '[date_end]'
                GROUP BY wo_task_fixed_by ORDER BY total LIMIT 5";
            } else if ($title === 'vg_wo_average_execute_by_trade') {
                $sql = "SELECT 
                    ppm_group_name,
                    AVG(TIMESTAMPDIFF(SECOND, wo_task_time_assigned, wo_task_time_executed))/60 AS total, 
                    SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, wo_task_time_assigned, wo_task_time_executed))) AS display
                FROM wo_task
                LEFT JOIN ppm_group ON ppm_group.ppm_group_id = wo_task.ppm_group_id
                WHERE wo_task.ppm_group_id IS NOT NULL AND wo_task_time_executed IS NOT NULL AND wo_task.site_id [site_id]
                AND DATE(wo_task_time_created) >= '[date_start]' AND DATE(wo_task_time_created) <= '[date_end]'
                GROUP BY ppm_group_name
                ORDER BY total";
            } else if ($title === 'vg_count_ppm_by_site_status') {
                $sql = "SELECT 
                    site_id, ppm_task_status, count(*) AS total 
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                WHERE DATE(ppm_task_start_date) >= '[date_start]' AND DATE(ppm_task_start_date) <= '[date_end]' 
                GROUP BY site_id, ppm_task_status";
            } else if ($title === 'vg_count_ppm_by_site_trade') {
                $sql = "SELECT 
                    site_id, asset_group_id, count(*) AS total 
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                WHERE DATE(ppm_task_start_date) >= '[date_start]' AND DATE(ppm_task_start_date) <= '[date_end]' 
                GROUP BY site_id, asset_group_id";
            } else if ($title === 'vg_ppm_top5_execute') {
                $sql = "SELECT
                    ppm_task_serviced_by, 
                    COUNT(*) AS total
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                WHERE ppm_task_serviced_by IS NOT NULL AND site_id [site_id]
                AND DATE(ppm_task_start_date) >= '[date_start]' AND DATE(ppm_task_start_date) <= '[date_end]' 
                GROUP BY ppm_task_serviced_by ORDER BY total DESC LIMIT 5";
            } else if ($title === 'vg_ppm_bottom5_execute') {
                $sql = "SELECT
                    ppm_task_serviced_by, 
                    COUNT(*) AS total
                FROM ppm_task 
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                WHERE ppm_task_serviced_by IS NOT NULL AND site_id [site_id]
                AND DATE(ppm_task_start_date) >= '[date_start]' AND DATE(ppm_task_start_date) <= '[date_end]' 
                GROUP BY ppm_task_serviced_by ORDER BY total LIMIT 5";
            } else if ($title === 'vg_ppm_average_execute_by_trade') {
                $sql = "SELECT 
                    ast_asset.asset_group_id,
                    asset_group_name,
                    AVG(TIMESTAMPDIFF(SECOND, ppm_task_time_start, ppm_task_time_serviced))/60 AS total, 
                    SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, ppm_task_time_start, ppm_task_time_serviced))) AS display
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ast_asset_group ON ast_asset_group.asset_group_id = ast_asset.asset_group_id
                WHERE ppm_task_time_serviced IS NOT NULL AND cli_contract.site_id [site_id]
                AND DATE(ppm_task_start_date) >= '[date_start]' AND DATE(ppm_task_start_date) <= '[date_end]' 
                GROUP BY ast_asset.asset_group_id
                ORDER BY total";
            } else if ($title === 'vg_report_wo_summary') {
                $sql = "SELECT                     
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        WHEN wo_task_type = 6 THEN 'Public Complaint'
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
                    SUM(IF(wo_task_type = 1, 1, 0)) AS open1, 
                    SUM(IF(wo_task_type = 1 AND wo_task_status IN (16, 25), 1, 0)) AS closed1, 
                    SUM(IF(wo_task_type = 2, 1, 0)) AS open2, 
                    SUM(IF(wo_task_type = 2 AND wo_task_status IN (16, 25), 1, 0)) AS closed2, 
                    SUM(IF(wo_task_type = 3, 1, 0)) AS open3, 
                    SUM(IF(wo_task_type = 3 AND wo_task_status IN (16, 25), 1, 0)) AS closed3, 
                    SUM(IF(wo_task_type = 4, 1, 0)) AS open4, 
                    SUM(IF(wo_task_type = 4 AND wo_task_status IN (16, 25), 1, 0)) AS closed4, 
                    SUM(IF(wo_task_type = 5, 1, 0)) AS open5, 
                    SUM(IF(wo_task_type = 5 AND wo_task_status IN (16, 25), 1, 0)) AS closed5, 
                    SUM(IF(wo_task_type = 6, 1, 0)) AS open6, 
                    SUM(IF(wo_task_type = 6 AND wo_task_status IN (16, 25), 1, 0)) AS closed6
                FROM cli_site 
                LEFT JOIN wo_task ON cli_site.site_id = wo_task.site_id AND YEAR(wo_task_time_created) = [selected_year] AND MONTH(wo_task_time_created) = [selected_month]
                WHERE site_is_launched = 1
                GROUP BY cli_site.site_id";
            } else if ($title === 'vg_report_ppm_total') {
                $sql = "SELECT                
                    cli_site.site_id,     
                    site_name, 
                    COUNT(*) AS total_ppm_not,
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
                        SUM(IF(wo_task_type = 1, 1, 0)) AS open1, 
                        SUM(IF(wo_task_type = 1 AND wo_task_status IN (16, 25), 1, 0)) AS closed1, 
                        SUM(IF(wo_task_type = 2, 1, 0)) AS open2, 
                        SUM(IF(wo_task_type = 2 AND wo_task_status IN (16, 25), 1, 0)) AS closed2, 
                        SUM(IF(wo_task_type = 3, 1, 0)) AS open3, 
                        SUM(IF(wo_task_type = 3 AND wo_task_status IN (16, 25), 1, 0)) AS closed3, 
                        SUM(IF(wo_task_type = 4, 1, 0)) AS open4, 
                        SUM(IF(wo_task_type = 4 AND wo_task_status IN (16, 25), 1, 0)) AS closed4, 
                        SUM(IF(wo_task_type = 5, 1, 0)) AS open5, 
                        SUM(IF(wo_task_type = 5 AND wo_task_status IN (16, 25), 1, 0)) AS closed5, 
                        SUM(IF(wo_task_type = 6, 1, 0)) AS open6, 
                        SUM(IF(wo_task_type = 6 AND wo_task_status IN (16, 25), 1, 0)) AS closed6
                    FROM wo_task 
                    WHERE site_id = [site_id] AND YEAR(wo_task_time_created) = [selected_year] AND MONTH(wo_task_time_created) = [selected_month]
                    GROUP BY dates
                    UNION 
                    SELECT                
                        DATE(ppm_task_start_date) AS dates, 
                        COUNT(*) AS open0,
                        SUM(IF(ppm_task_status = 16, 1, 0)) AS closed0,
                        0 AS open1, 0 AS closed1,
                        0 AS open2, 0 AS closed2,
                        0 AS open3, 0 AS closed3,
                        0 AS open4, 0 AS closed4,
                        0 AS open5, 0 AS closed5,
                        0 AS open6, 0 AS closed6
                    FROM ppm_task 
                    LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                    LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                    WHERE cli_contract.site_id = [site_id] AND YEAR(ppm_task_start_date) = [selected_year] AND MONTH(ppm_task_start_date) = [selected_month]
                    GROUP BY dates) aa 
                GROUP BY dates";
            } else if ($title === 'vw_ppm_list') {
                $sql = "SELECT 
                    ppm_task.*,
                    cli_contract.site_id,
                    ppm.ppm_task_no AS document_no,
                    ppm.ppm_group_id,
                    task_frequency.frequency_ids,
                    task_frequency.frequency,
                    ast_asset.asset_no,
                    ast_asset.asset_name,
                    ast_asset.asset_location_code,
                    ast_asset.asset_location_desc,
                    ast_asset.asset_group_id,
                    ast_asset.asset_category_id,
                    ast_asset.asset_type_id,
                    ast_asset.asset_block,
                    ast_asset.asset_level,
                    task_upload.upload_ids,                     
                    IF ((ppm_task_time_serviced IS NULL AND CURDATE() > ppm_task_schedule_date) OR DATE(ppm_task_time_serviced) > ppm_task_schedule_date, 'Late', 'On-time') AS lateness,                    
                    CASE WHEN ppm_task_time_serviced IS NULL AND CURDATE() <= ppm_task_schedule_date THEN 'In Progress'
                        WHEN ppm_task_time_serviced IS NULL AND CURDATE() > ppm_task_schedule_date THEN 'Not Started'
                        WHEN DATE(ppm_task_time_serviced) > ppm_task_schedule_date THEN 'Late'
                        ELSE 'On-time' END AS lateness2,
                    CASE WHEN ppm_task_min_exec_time IS NULL OR ppm_task_max_exec_time IS NULL OR ppm_task_time_start IS NULL OR ppm_task_time_serviced IS NULL THEN '' 
                        WHEN TIMEDIFF(ppm_task_time_serviced, ppm_task_time_start) < ppm_task_min_exec_time THEN 'Less'
                        WHEN TIMEDIFF(ppm_task_time_serviced, ppm_task_time_start) <= ppm_task_max_exec_time THEN 'Within'
                        ELSE 'Exceed' END AS within_status
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(ppm_task_frequency.frequency_id) AS frequency_ids, GROUP_CONCAT(frequency_name SEPARATOR ', ') AS frequency
                    FROM ppm_task_frequency
                    LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id
                    GROUP BY ppm_task_id) task_frequency ON task_frequency.ppm_task_id = ppm_task.ppm_task_id
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(ppm_task_upload.upload_id SEPARATOR '||') AS upload_ids 
                    FROM ppm_task_upload
                    WHERE ppm_task_upload_type = 3
                    GROUP BY ppm_task_id) task_upload ON task_upload.ppm_task_id = ppm_task.ppm_task_id";
            } else if ($title === 'vg_ppm_dashboard') {
                $sql = "SELECT 
                    ppm_task.*,
                    cli_contract.site_id,
                    ppm.ppm_task_no AS document_no,
                    ppm.ppm_group_id,
                    task_frequency.frequency_ids,
                    task_frequency.frequency,
                    ast_asset.asset_no,
                    ast_asset.asset_name,
                    ast_asset.asset_location_code,
                    ast_asset.asset_location_desc,
                    ast_asset.asset_group_id,
                    ast_asset.asset_category_id,
                    ast_asset.asset_type_id,
                    ast_asset.asset_block,
                    ast_asset.asset_level,
                    task_upload.upload_ids,
                    IF ((ppm_task_time_serviced IS NULL AND CURDATE() > ppm_task_schedule_date) OR DATE(ppm_task_time_serviced) > ppm_task_schedule_date, 'Late', 'On-time') AS lateness,
                    CASE WHEN ppm_task_time_serviced IS NULL AND CURDATE() <= ppm_task_schedule_date THEN 'In Progress'
                        WHEN ppm_task_time_serviced IS NULL AND CURDATE() > ppm_task_schedule_date THEN 'Not Started'
                        WHEN DATE(ppm_task_time_serviced) > ppm_task_schedule_date THEN 'Late'
                        ELSE 'On-time' END AS lateness2,
                    CASE WHEN ppm_task_min_exec_time IS NULL OR ppm_task_max_exec_time IS NULL OR ppm_task_time_start IS NULL OR ppm_task_time_serviced IS NULL THEN ''
                        WHEN TIMEDIFF(ppm_task_time_serviced, ppm_task_time_start) < ppm_task_min_exec_time THEN 'Less'
                        WHEN TIMEDIFF(ppm_task_time_serviced, ppm_task_time_start) <= ppm_task_max_exec_time THEN 'Within'
                        ELSE 'Exceed' END AS within_status
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(ppm_task_frequency.frequency_id) AS frequency_ids, GROUP_CONCAT(frequency_name SEPARATOR ', ') AS frequency
                    FROM ppm_task_frequency
                    LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_task_frequency.frequency_id
                    GROUP BY ppm_task_id) task_frequency ON task_frequency.ppm_task_id = ppm_task.ppm_task_id
                LEFT JOIN (SELECT ppm_task_id, GROUP_CONCAT(ppm_task_upload.upload_id SEPARATOR '||') AS upload_ids
                    FROM ppm_task_upload
                    WHERE ppm_task_upload_type = 3
                    GROUP BY ppm_task_id) task_upload ON task_upload.ppm_task_id = ppm_task.ppm_task_id";
            } else if ($title === 'vw_client_with_severity') {
                $sql = "SELECT
                    cli_client.*,
                    severity.severities,
                    severity.severity_hour,
                    severity.severity_respond_time,
                    failure_code.failure_codes
                FROM cli_client
                LEFT JOIN
                (
                    SELECT 
                        client_id, GROUP_CONCAT(severity_id) AS severities, GROUP_CONCAT(client_severity_hour) AS severity_hour, GROUP_CONCAT(client_severity_respond_time) AS severity_respond_time
                    FROM cli_client_severity
                    GROUP BY client_id
                ) severity ON severity.client_id = cli_client.client_id
                LEFT JOIN
                (
                    SELECT 
                        client_id, GROUP_CONCAT(failure_code_id) AS failure_codes
                    FROM cli_client_failure_code
                    GROUP BY client_id
                ) failure_code ON failure_code.client_id = cli_client.client_id";
            } else if ($title === 'vw_drawing') {
                $sql = "SELECT
                    drawing.*,
                    user_publish.user_first_name AS published_by,
                    user_create.user_first_name AS upload_by
                FROM drawing
                LEFT JOIN sys_user user_publish ON user_publish.user_id = drawing.drawing_published_by
                LEFT JOIN sys_user user_create ON user_create.user_id = drawing.drawing_created_by";
            } else if ($title === 'vw_part_mobile') {
                $sql = "SELECT
                    ast_part.part_id,
                    ref_item_type.item_type_desc,
                    ref_item.item_description,
                    SUM(part_count) AS part_counts
                FROM ast_part
                LEFT JOIN ref_item_type ON ref_item_type.item_type_id = ast_part.item_type_id  
                LEFT JOIN ref_item ON ref_item.item_id = ast_part.item_id  
                WHERE ast_part.site_id = [siteId] AND ast_part.item_type_id = [itemTypeId] AND item_status = 1
                GROUP BY ast_part.item_id
                ORDER BY item_turn";
            } else if ($title === 'vw_wo_task_parts_mobile') {
                $sql = "SELECT     	
                    docs.upload_list,
                    docs.title_list,
                    docs.width_list,
                    docs.height_list,
                    a.wo_task_parts_id,
                    a.wo_task_request_id,
                    a.part_id,
                    b.item_id,
                    c.item_description,
                    d.item_type_desc,
                    e.asset_group_name,
                    a.wo_task_parts_remark,
		            a.wo_task_parts_quantity,
                    b.part_count,
                    b.part_locked,
                    b.part_count-b.part_locked AS part_available,
                    CASE 
                        WHEN a.wo_task_parts_quantity > b.part_count-b.part_locked THEN 'Not Enough'
                        WHEN b.part_threshold > b.part_count-b.part_locked THEN 'Below Threshold'
                        ELSE 'Available' 
                    END AS status_storekeeper,
                    b.part_threshold,
                    a.wo_task_parts_status,
                    f.status_desc        
                FROM wo_task_parts a
                LEFT JOIN wo_task_request r ON r.wo_task_request_id = a.wo_task_request_id
                LEFT JOIN ast_part b ON b.part_id = a.part_id
                LEFT JOIN ref_item c ON c.item_id = b.item_id
                LEFT JOIN ref_item_type d ON d.item_type_id = b.item_type_id
                LEFT JOIN ast_asset_group e ON e.asset_group_id = b.asset_group_id
                LEFT JOIN ref_status f ON f.status_id = a.wo_task_parts_status
                LEFT JOIN (
                    SELECT 
                        g.item_id, 
                        GROUP_CONCAT(CONCAT(u.upload_folder,'/',u.upload_filename,'.',u.upload_extension) SEPARATOR '||') AS upload_list, 
                        GROUP_CONCAT(u.upload_name SEPARATOR '||') AS title_list, 
                        GROUP_CONCAT(u.upload_file_width SEPARATOR '||') AS width_list, 
                        GROUP_CONCAT(u.upload_file_height SEPARATOR '||') AS height_list
                    FROM ref_item_image g
                    LEFT JOIN sys_upload u ON u.upload_id = g.upload_id
                    GROUP BY g.item_id
                ) docs ON docs.item_id = c.item_id";
            } else if ($title === 'vw_wo_task_parts') {
                $sql = "SELECT 
                    a.*,
                    w.wo_task_no,
                    r.wo_task_request_no,
                    r.wo_task_request_mrf_pdf,
                    r.wo_task_request_mrf_generate,
                    r.wo_task_request_order_by,
                    r.wo_task_request_time_ordered,
                    r.wo_task_request_time_collected
                FROM wo_task_parts a
                LEFT JOIN wo_task_request r ON r.wo_task_request_id = a.wo_task_request_id
                LEFT JOIN wo_task w ON w.wo_task_id = r.wo_task_id";
            } else if ($title === 'vw_pr_current') {
                $sql = "SELECT
                    t.task_id,
                    p.*
                FROM wfl_task t 
                INNER JOIN pr p ON p.transaction_id = t.transaction_id";
            } else if ($title === 'vw_store') {
                $sql = "SELECT 
                    s.*,
                    t.total_item_desc,
                    t.total_item
                FROM cli_store s
                LEFT JOIN (SELECT store_id, COUNT(*) AS total_item_desc, SUM(part_count) AS total_item FROM ast_part GROUP BY store_id) AS t ON t.store_id = s.store_id";
            } else if ($title === 'vw_item_image') {
                $sql = "SELECT 
                     u.*,
                     g.item_image_id,
                     g.item_id
                FROM ref_item_image g
                LEFT JOIN sys_upload u ON u.upload_id = g.upload_id";
            } else if ($title === 'vw_item_with_image') {
                $sql = "SELECT 
                    i.*,
                    docs.upload_list,
                    docs.title_list,
                    docs.width_list,
                    docs.height_list 
                FROM ref_item i
                LEFT JOIN (
                        SELECT 
                            g.item_id, 
                            GROUP_CONCAT(CONCAT(u.upload_folder,'/',u.upload_filename,'.',u.upload_extension) SEPARATOR '||') AS upload_list, 
                            GROUP_CONCAT(u.upload_name SEPARATOR '||') AS title_list, 
                            GROUP_CONCAT(u.upload_file_width SEPARATOR '||') AS width_list, 
                            GROUP_CONCAT(u.upload_file_height SEPARATOR '||') AS height_list
                        FROM ref_item_image g
                        LEFT JOIN sys_upload u ON u.upload_id = g.upload_id
                        GROUP BY g.item_id
                ) docs ON docs.item_id = i.item_id";
            } else if ($title === 'vw_part_with_image') {
                $sql = "SELECT 
                    p.*,
                    docs.upload_list,
                    docs.title_list,
                    docs.width_list,
                    docs.height_list 
                FROM ast_part p
                LEFT JOIN (
                    SELECT 
                        g.item_id, 
                        GROUP_CONCAT(CONCAT(u.upload_folder,'/',u.upload_filename,'.',u.upload_extension) SEPARATOR '||') AS upload_list, 
                        GROUP_CONCAT(u.upload_name SEPARATOR '||') AS title_list, 
                        GROUP_CONCAT(u.upload_file_width SEPARATOR '||') AS width_list, 
                        GROUP_CONCAT(u.upload_file_height SEPARATOR '||') AS height_list
                    FROM ref_item_image g
                    LEFT JOIN sys_upload u ON u.upload_id = g.upload_id
                    GROUP BY g.item_id
                ) docs ON docs.item_id = p.item_id";
            } else if ($title === 'vw_part_left_asset_group') {
                $sql = "SELECT 
                    DISTINCT(t.asset_group_id) AS asset_group_list
                FROM ref_item i
                LEFT JOIN ast_part p ON p.item_id = i.item_id AND p.store_id = [storeId]
                LEFT JOIN ref_item_type t ON t.item_type_id = i.item_type_id 
                WHERE i.item_status = 1 AND p.part_id IS NULL";
            } else if ($title === 'vw_part_left_item_type') {
                $sql = "SELECT 
                    DISTINCT(i.item_type_id) AS item_type_list
                FROM ref_item i
                LEFT JOIN ast_part p ON p.item_id = i.item_id AND p.store_id = [storeId]
                LEFT JOIN ref_item_type t ON t.item_type_id = i.item_type_id 
                WHERE i.item_status = 1 AND p.part_id IS NULL AND t.asset_group_id = [assetGroupId]";
            } else if ($title === 'vw_part_left_item') {
                $sql = "SELECT 
                    i.item_id AS item_list
                FROM ref_item i
                LEFT JOIN ast_part p ON p.item_id = i.item_id AND p.store_id = [storeId]
                WHERE i.item_status = 1 AND p.part_id IS NULL AND i.item_type_id = [itemTypeId]";
            } else if ($title === 'vw_part_asset_group') {
                $sql = "SELECT 
                    p.asset_group_id,
                    a.asset_group_name
                FROM ast_part p
                LEFT JOIN ast_asset_group a ON a.asset_group_id = p.asset_group_id
                WHERE p.store_id IN ([storeIds]) AND p.part_status = 1
                GROUP BY p.asset_group_id";
            } else if ($title === 'vw_part_item_type') {
                $sql = "SELECT 
                    p.item_type_id,
                    i.item_type_desc
                FROM ast_part p
                LEFT JOIN ref_item_type i ON i.item_type_id = p.item_type_id
                WHERE p.store_id IN ([storeIds]) AND p.asset_group_id = [assetGroupId] AND p.part_status = 1
                GROUP BY p.item_type_id
                ORDER BY i.item_type_turn";
            } else if ($title === 'vw_part_item') {
                $sql = "SELECT 
                    p.item_id,
                    i.item_description
                FROM ast_part p
                LEFT JOIN ref_item i ON i.item_id = p.item_id
                WHERE p.store_id IN ([storeIds]) AND i.item_type_id = [itemTypeId] AND p.part_status = 1
                GROUP BY p.item_id
                ORDER BY i.item_turn";
            } else if ($title === 'vw_purchase_asset_group_option') {
                $sql = "SELECT 
                    p.asset_group_id,
                    a.asset_group_name
                FROM ast_part p
                LEFT JOIN ast_asset_group a ON a.asset_group_id = p.asset_group_id
                WHERE p.store_id = [storeId] AND p.part_status = 1
                GROUP BY p.asset_group_id";
            } else if ($title === 'vw_purchase_item_type_option') {
                $sql = "SELECT 
                    p.item_type_id,
                    i.item_type_desc
                FROM ast_part p
                LEFT JOIN ref_item_type i ON i.item_type_id = p.item_type_id
                WHERE p.store_id = [storeId] AND p.asset_group_id = [assetGroupId] AND p.part_status = 1
                GROUP BY p.item_type_id
                ORDER BY i.item_type_turn";
            } else if ($title === 'vw_purchase_part_option') {
                $sql = "SELECT 
                    p.part_id,
                    i.item_description,
                    p.part_count,
                    p.part_locked,
                    p.part_count-p.part_locked AS part_available,
                    p.part_min_order,
                    p.part_max_order,
                    p.part_remark
                FROM ast_part p
                LEFT JOIN ref_item i ON i.item_id = p.item_id
                WHERE p.store_id = [storeId] AND i.item_type_id = [itemTypeId] AND p.part_status = 1
                GROUP BY p.item_id
                ORDER BY i.item_turn";
            } else if ($title === 'vw_do_item') {
                $sql = "SELECT 
                    pdi.do_item_timestamp,
                    pdo.do_no,
                    pdo.do_date,
                    pdi.do_item_warranty,
                    pdi.do_item_validity,
                    pdi.do_item_cost,
                    pdi.do_item_total,
                    pdi.do_item_total_cost
                FROM do_item pdi
                LEFT JOIN do pdo ON pdo.do_id = pdi.do_id";
            } else if ($title === 'vw_do_item_mobile') {
                $sql = "SELECT 
                    docs.upload_list,
                    docs.title_list,
                    docs.width_list,
                    docs.height_list,
                    pdi.do_item_warranty,
                    pdi.do_item_validity,
                    pdi.do_item_cost,
                    pdi.do_item_total,
                    pdi.do_item_total_cost,
                    p.part_id,
                    i.item_description,
                    d.item_type_desc,
                    e.asset_group_name
                FROM do_item pdi
                LEFT JOIN ast_part p ON p.part_id = pdi.part_id
                LEFT JOIN cli_store s ON s.store_id = p.store_id
                LEFT JOIN ref_item i ON i.item_id = p.item_id
                LEFT JOIN ref_item_type d ON d.item_type_id = i.item_type_id
                LEFT JOIN ast_asset_group e ON e.asset_group_id = d.asset_group_id
                LEFT JOIN (
                    SELECT 
                        g.item_id, 
                        GROUP_CONCAT(CONCAT(u.upload_folder,'/',u.upload_filename,'.',u.upload_extension) SEPARATOR '||') AS upload_list, 
                        GROUP_CONCAT(u.upload_name SEPARATOR '||') AS title_list, 
                        GROUP_CONCAT(u.upload_file_width SEPARATOR '||') AS width_list, 
                        GROUP_CONCAT(u.upload_file_height SEPARATOR '||') AS height_list
                    FROM ref_item_image g
                    LEFT JOIN sys_upload u ON u.upload_id = g.upload_id
                    GROUP BY g.item_id
                ) docs ON docs.item_id = i.item_id";
            } else if ($title === 'vw_wo_request_task_m') {
                $sql = "SELECT
                    r.wo_task_request_id,
                    r.wo_task_request_no,
                    w.wo_task_no,
                    l.site_name,
                    u.user_first_name AS task_from,
                    t.task_time_created AS task_received_time,
                    u2.user_first_name AS request_by,
                    r.wo_task_request_time_ordered AS request_time,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        WHEN wo_task_type = 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_type_desc,                    
                    sv.severity_name AS wo_severity_desc,
                    s.status_id,
                    s.status_desc
                FROM wfl_task t
                LEFT JOIN wo_task_request r ON r.transaction_id = t.transaction_id
                LEFT JOIN wo_task w ON w.wo_task_id = r.wo_task_id
                LEFT JOIN sys_user u ON u.user_id = t.task_created_user
                LEFT JOIN sys_user u2 ON u2.user_id = r.wo_task_request_order_by
                LEFT JOIN ref_status s ON s.status_id = r.wo_task_request_status
                LEFT JOIN cli_site l ON l.site_id = w.site_id
                LEFT JOIN ref_severity sv ON sv.severity_id = r.wo_task_request_severity
                WHERE checkpoint_id IN ([checkpoints]) AND w.site_id = [siteId] AND [taskCurrent] 
                HAVING (wo_task_request_no LIKE '%[search_text]%' OR wo_task_no LIKE '%[search_text]%' OR task_from LIKE '%[search_text]%' OR wo_type_desc LIKE '%[search_text]%' OR wo_severity_desc LIKE '%[search_text]%' OR status_desc LIKE '%[search_text]%')";
            } else if ($title === 'vw_wo_request_task_detail_m') {
                $sql = "SELECT
                    r.wo_task_request_id,
                    r.wo_task_request_no,
                    w.wo_task_no,
                    l.site_name,
                    u2.user_first_name AS request_by,
                    r.wo_task_request_time_ordered AS request_time,
                    r.wo_task_request_time_collected AS collect_time,
                    CASE WHEN wo_task_type = 1 THEN 'Client Complaint'
                        WHEN wo_task_type = 2 THEN 'Self Finding'
                        WHEN wo_task_type = 3 THEN 'Request'
                        WHEN wo_task_type = 4 THEN 'Breakdown'
                        WHEN wo_task_type = 5 THEN 'Defect'
                        WHEN wo_task_type = 6 THEN 'Public Complaint'
                        ELSE ''
                    END AS wo_type_desc,
                    sv.severity_name AS wo_severity_desc,
                    s.status_id,
                    s.status_desc
                FROM wo_task_request r 
                LEFT JOIN wo_task w ON w.wo_task_id = r.wo_task_id
                LEFT JOIN sys_user u2 ON u2.user_id = r.wo_task_request_order_by
                LEFT JOIN ref_status s ON s.status_id = r.wo_task_request_status
                LEFT JOIN cli_site l ON l.site_id = w.site_id
                LEFT JOIN ref_severity sv ON sv.severity_id = r.wo_task_request_severity";
            } else if ($title === 'vw_part_tree_category_m') {
                $sql = "SELECT 
                    p.part_id,
                    p.asset_group_id,
                    a.asset_group_name,
                    p.item_type_id,
                    t.item_type_desc,
                    p.item_id,
                    i.item_description,
                    p.part_count,
                    p.part_locked,
                    p.part_count-p.part_locked AS part_available,
                    p.part_threshold,
                    p.part_min_order,
                    p.part_max_order,
                    p.part_remark
                FROM ast_part p
                LEFT JOIN ref_item i ON i.item_id = p.item_id
                LEFT JOIN ref_item_type t ON t.item_type_id = p.item_type_id
                LEFT JOIN ast_asset_group a ON a.asset_group_id = p.asset_group_id";
            } else if ($title === 'vw_part_sub_grouped') {
                $sql = "SELECT
                    ast_part_sub.do_no, `do`.supplier_name, part_sub_location, part_sub_cost, part_sub_validity, part_sub_warranty, DATE(do_item.do_item_timestamp) AS date_check_in, COUNT(*) AS total
                FROM ast_part_sub
                LEFT JOIN do_item ON do_item.do_item_id = ast_part_sub.do_item_id
                LEFT JOIN `do` ON `do`.do_id = do_item.do_id
                WHERE ast_part_sub.part_id = [partId]
                GROUP BY ast_part_sub.do_no, `do`.supplier_name, part_sub_location, part_sub_cost, part_sub_validity, part_sub_warranty, date_check_in";
            } else if ($title === 'vw_check_in_mobile_list') {
                $sql = "SELECT
                    d.do_id,
                    d.do_no,
                    d.do_date,
                    d.do_timestamp,
                    d.supplier_name,
                    u.user_first_name,
                    i.total_cost
                FROM `do` d
                LEFT JOIN sys_user u ON u.user_id = d.do_created_by
                LEFT JOIN (
                    SELECT do_id, SUM(do_item_total_cost) AS total_cost
                    FROM do_item
                    GROUP BY do_id
                ) i ON i.do_id = d.do_id";
            } else if ($title === 'vw_do_upload') {
                $sql = "SELECT 
                     u.*,
                     d.do_upload_id,
                     d.do_id
                FROM do_upload d
                LEFT JOIN sys_upload u ON u.upload_id = d.upload_id";
            } else if ($title === 'vw_check_out_mobile_list') {
                $sql = "SELECT
                    r.wo_task_request_id,
                    r.wo_task_request_no,
                    r.wo_task_request_time_collected AS check_out_time,
                    u.user_first_name AS check_out_by,
                    w.wo_task_id,
                    w.wo_task_no,
                    p.total
                FROM wo_task_request r
                LEFT JOIN wo_task w ON w.wo_task_id = r.wo_task_id
                LEFT JOIN sys_user u ON u.user_id = r.wo_task_request_order_by
                LEFT JOIN (
                        SELECT wo_task_request_id, SUM(wo_task_parts_quantity) AS total
                        FROM wo_task_parts
                        GROUP BY wo_task_request_id
                ) p ON p.wo_task_request_id = r.wo_task_request_id";
            } else if ($title === 'vw_return_mobile_list') {
                $sql = "SELECT
                    w.wo_task_no,
                    r.wo_task_request_no,
                    r.wo_task_request_id,
                    wp.wo_task_parts_id,
                    wp.part_id,
                    i.item_description,
                    ps.part_sub_id,
                    ps.part_sub_no,
                    ps.part_sub_time_check_out AS check_out_time,
                    ps.part_sub_status
                FROM ast_part_sub ps
                LEFT JOIN wo_task_parts wp ON wp.wo_task_parts_id = ps.wo_task_parts_id
                LEFT JOIN wo_task_request r ON r.wo_task_request_id = wp.wo_task_request_id
                LEFT JOIN wo_task w ON w.wo_task_id = r.wo_task_id
                LEFT JOIN ast_part p ON p.part_id = wp.part_id
                LEFT JOIN ref_item i ON i.item_id = p.item_id
                WHERE ps.part_sub_collected_by = [userId]
                    AND ps.part_sub_status = 36
                    AND w.site_id = [siteId]";
            } else if ($title === 'vw_parts_value') {
                $sql = "SELECT
                    SUM(s.part_sub_cost) AS total_value
                FROM ast_part_sub s
                LEFT JOIN ast_part p ON p.part_id = s.part_id ";
            } else if ($title === 'vw_utility_mobile_list') {
                $sql = "SELECT
                    u.utility_id,
                    u.utility_type,
                    u.utility_reading_type,
                    u.utility_reading,
                    u.utility_date,
                    u.utility_total_rm,
                    u.utility_max_demand,
                    u.utility_timestamp,
                    m.meter_id,
                    m.meter_name,
                    m.meter_location,
                    s.user_first_name AS utility_recorded_by,
                    CONCAT('https://gems.globalfm.com.my/api/', p.upload_folder,'/',p.upload_filename,'.',p.upload_extension) AS utility_image
                FROM utl_utility u
                LEFT JOIN utl_meter m ON m.meter_id = u.meter_id 
                LEFT JOIN sys_user s ON s.user_id = u.utility_recorded_by
                LEFT JOIN sys_upload p ON p.upload_id = u.utility_image";
            } else if ($title === 'vw_kpi_ppns') {
                $sql = "SELECT 
                    k.kpi_id,
                    kpi_year,
                    kpi_month,
                    SUM(IF(kpi_ppns_category = 2, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_2,
                    SUM(IF(kpi_ppns_category = 3, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_3,
                    SUM(IF(kpi_ppns_category = 4, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_4,
                    SUM(IF(kpi_ppns_category = 5, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_5,
                    SUM(IF(kpi_ppns_category = 6, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_6,
                    SUM(IF(kpi_ppns_category = 7, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_7,
                    SUM(IF(kpi_ppns_category = 8, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_8,
                    SUM(IF(kpi_ppns_category = 9, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_9,
                    SUM(IF(kpi_ppns_category = 10, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_10,
                    SUM(IF(kpi_ppns_category = 11, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_11,
                    SUM(IF(kpi_ppns_category = 12, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_12,
                    SUM(IF(kpi_ppns_category = 13, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_13,
                    SUM(IF(kpi_ppns_category = 14, ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2), 0)) AS kpi_cate_14,
                    SUM(ROUND((kpi_ppns_ncp*kpi_ppns_weightage*kpi_portion_perc/100*kpi_portion_total_fee),2)) AS kpi_cate_all
                FROM kpi k
                LEFT JOIN kpi_ppns p ON p.kpi_id = k.kpi_id
                WHERE site_id = 7
                GROUP BY k.kpi_id
                ORDER BY kpi_year DESC, kpi_month DESC";
            } else if ($title === 'vw_kpi_ppns_comfort_availability') {
                $sql = "SELECT 
                    ppm_task.*,
                    ast_asset.asset_no,
                    ast_asset.asset_name,
                    ast_asset.asset_location_code,
                    ast_asset.asset_location_desc,
                    ast_asset.asset_group_id,
                    ast_asset.asset_category_id,
                    ast_asset.asset_type_id,
                    ast_asset.asset_block,
                    ast_asset.asset_level,
                    ppm.ppm_group_id,
                    ppm_task_quan.ppm_task_quan_measured_values,
                    IF(ppm_task_quan_measured_values IS NULL, 'No Reading', IF(CAST(ppm_task_quan_measured_values AS DECIMAL(4,1)) BETWEEN 23 AND 25, 'Success', 'Fail')) AS result
                FROM ppm_task
                LEFT JOIN ppm ON ppm.ppm_id = ppm_task.ppm_id
                LEFT JOIN cli_contract ON cli_contract.contract_id = ppm.contract_id
                LEFT JOIN ast_asset ON ast_asset.asset_id = ppm.asset_id
                LEFT JOIN ppm_task_quan ON ppm_task_quan.ppm_task_id = ppm_task.ppm_task_id AND ppm_task_quan.checklist_quan_id = 1253";
            } else if ($title === 'vw_meter_mobile') {
                $sql = "SELECT 
                    m.*,
                    d.daily_total,
                    d.daily_latest_date,
                    l.utility_reading AS daily_latest_reading,
                    n.utility_total_rm AS monthly_total_rm
                FROM utl_meter m
                LEFT JOIN (
                    SELECT 
                        meter_id, SUM(utility_reading) AS daily_total, MAX(utility_date) AS daily_latest_date
                    FROM utl_utility
                    WHERE MONTH(utility_date) = MONTH(CURDATE())  AND utility_reading_type = 'Daily'
                    GROUP BY meter_id
                ) d ON d.meter_id = m.meter_id 
                LEFT JOIN utl_utility l ON l.meter_id = m.meter_id AND l.utility_reading_type = 'Daily' AND l.utility_date = d.daily_latest_date
                LEFT JOIN utl_utility n ON n.meter_id = m.meter_id AND n.utility_reading_type = 'Monthly'";
            } else if ($title === 'vw_utility_monthly_electricity_analyzed') {
                $sql = "SELECT 
                    u.*, 
                    z.site_id,
                    z.utility_max_demand AS utility_actual_max_demand
                FROM
                (
                    SELECT
                        YEAR(utility_date) AS utility_year, 
                        MONTH(utility_date) AS utility_month,
                        DATE_FORMAT(utility_date,'%M %Y') AS utility_month_name,
                        SUM(utility_total) AS utility_total_kwh,
                        MAX(utility_date) AS max_date
                    FROM utl_utility
                    WHERE site_id = [siteId] AND utility_type = 'Electricity' AND utility_reading_type = 'Daily' [andQuery]
                    GROUP BY YEAR(utility_date), MONTH(utility_date) 
                    ORDER BY YEAR(utility_date), MONTH(utility_date)
                ) u
                LEFT JOIN utl_utility z ON z.utility_date = u.max_date AND z.site_id = [siteId] AND z.utility_type = 'Electricity' AND z.utility_reading_type = 'Daily'";
            } else if ($title === 'vw_utility_monthly_water_analyzed') {
                $sql = "SELECT
                    YEAR(utility_date) AS utility_year, 
                    MONTH(utility_date) AS utility_month,
                    DATE_FORMAT(utility_date,'%M %Y') AS utility_month_name,
                    SUM(utility_total) AS utility_total_usage,    
                    site_id
                FROM utl_utility
                WHERE site_id = [siteId] AND utility_type = 'Water' AND utility_reading_type = 'Daily' AND utility_total IS NOT NULL
                GROUP BY YEAR(utility_date), MONTH(utility_date) 
                ORDER BY YEAR(utility_date), MONTH(utility_date)";
            } else if ($title === 'vw_utility_daily_water_analyzed') {
                $sql = "SELECT 
                    utility_date,
                    MAX(IF (utility_shift = 'Morning', utility_reading, 0)) AS reading_morning,
                    SUM(IF (utility_shift = 'Morning', utility_total, 0)) AS total_morning,
                    MAX(IF (utility_shift = 'Evening', utility_reading, 0)) AS reading_evening,
                    SUM(IF (utility_shift = 'Evening', utility_total, 0)) AS total_evening,
                    MAX(IF (utility_shift = 'Night', utility_reading, 0)) AS reading_night,
                    SUM(IF (utility_shift = 'Night', utility_total, 0)) AS total_night,
                    SUM(utility_total) AS total_daily
                FROM utl_utility
                WHERE site_id = [siteId] AND utility_type = 'Water' AND utility_reading_type = 'Daily' AND utility_total IS NOT NULL AND YEAR(utility_date) = [readingYear] AND MONTH(utility_date) = [readingMonth] 
                GROUP BY utility_date";
            } else if ($title === 'vw_utility_shift') {
                $sql = "SELECT 
                    CASE WHEN CURTIME() >= '06:00:00' AND CURTIME() < '18:00:00' THEN 'Morning'
                        WHEN CURTIME() >= '18:00:00' AND CURTIME() < '22:00:00' THEN 'Evening'
                        ELSE 'Night' END AS reading_shift,
                    IF (CURTIME() < '06:00:00', CURDATE() - INTERVAL 1 DAY, CURDATE()) AS reading_date";
            } else if ($title === 'vw_attendance_site') {
                $sql = "SELECT 
                    s.*,
                    IFNULL(ag.total, 0) AS total_group,
                    IFNULL(ap.total, 0) AS total_participant	
                FROM cli_site s
                LEFT JOIN (SELECT site_id, COUNT(*) AS total FROM att_group WHERE att_group_status = 1 GROUP BY site_id) ag ON ag.site_id = s.site_id
                LEFT JOIN (SELECT site_id, COUNT(*) AS total FROM att_participant 
                    LEFT JOIN att_group ON att_group.att_group_id = att_participant.att_group_id
                    WHERE att_participant_status = 1 
                    GROUP BY site_id) ap ON ap.site_id = s.site_id";
            } else if ($title === 'vw_att_participant_site') {
                $sql = "SELECT
                    u.user_first_name,
                    up.user_contact_no,
                    up.user_email,
                    d.designation_desc,
                    IFNULL(p.att_participant_status, 52) AS participant_status,   
                    u.user_id AS user_ids,
                    p.*
                FROM sys_user u
                LEFT JOIN att_participant p ON p.user_id = u.user_id
                LEFT JOIN sys_user_profile up ON up.user_id = u.user_id
                LEFT JOIN ref_designation d ON d.designation_id = up.designation_id";
            } else if ($title === 'vw_gamification_ppm_monthly') {
                $sql = "SELECT 
                    p.ppm_task_assigned_to,
                    u.site_id,
	                g.gmi_id,
                    COUNT(*) AS ppm_total,
                    SUM(IF(p.ppm_task_time_serviced IS NOT NULL, 1, 0)) AS ppm_completed,
                    SUM(IF(DATE(p.ppm_task_time_serviced) <= p.ppm_task_schedule_date, 1, 0)) AS ppm_on_time,
                    SUM(IF(DATE(p.ppm_task_time_serviced) > p.ppm_task_schedule_date, 1, 0)) AS ppm_late,
                    SUM(IF(ppm_task_min_exec_time IS NOT NULL AND ppm_task_max_exec_time IS NOT NULL AND ppm_task_time_start IS NOT NULL AND ppm_task_time_serviced IS NOT NULL 
		                AND TIMEDIFF(ppm_task_time_serviced, ppm_task_time_start) >= ppm_task_min_exec_time AND TIMEDIFF(ppm_task_time_serviced, ppm_task_time_start) <= ppm_task_max_exec_time, 1, 0)) AS ppm_within
                FROM ppm_task p
                LEFT JOIN sys_user u ON u.user_id = p.ppm_task_assigned_to
                LEFT JOIN sys_user_profile up ON up.user_id = u.user_id
                LEFT JOIN gmi_monthly g ON g.user_id = p.ppm_task_assigned_to AND g.gmi_year = [yearNo] AND g.gmi_month = [monthNo]
                WHERE up.designation_id = 4 AND YEAR(p.ppm_task_schedule_date) = [yearNo] AND MONTH(p.ppm_task_schedule_date) = [monthNo] AND p.ppm_task_assigned_to IS NOT NULL
                GROUP BY p.ppm_task_assigned_to";
            } else if ($title === 'vw_gamification_ppm_assist_monthly') {
                $sql = "SELECT 
                    a.user_id,
                    u.site_id,
                    g.gmi_id,
                    COUNT(*) AS ppm_total,
                    SUM(IF(p.ppm_task_time_serviced IS NOT NULL, 1, 0)) AS ppm_completed,
                    SUM(IF(DATE(p.ppm_task_time_serviced) <= p.ppm_task_schedule_date, 1, 0)) AS ppm_on_time,
                    SUM(IF(DATE(p.ppm_task_time_serviced) > p.ppm_task_schedule_date, 1, 0)) AS ppm_late,
					SUM(IF(p.ppm_task_min_exec_time IS NOT NULL AND p.ppm_task_max_exec_time IS NOT NULL AND p.ppm_task_time_start IS NOT NULL AND p.ppm_task_time_serviced IS NOT NULL 
						AND TIMEDIFF(p.ppm_task_time_serviced, p.ppm_task_time_start) >= p.ppm_task_min_exec_time AND TIMEDIFF(p.ppm_task_time_serviced, p.ppm_task_time_start) <= p.ppm_task_max_exec_time, 1, 0)) AS ppm_within
                FROM ppm_task_assist a
                LEFT JOIN ppm_task p ON p.ppm_task_id = a.ppm_task_id
                LEFT JOIN sys_user u ON u.user_id = a.user_id
                LEFT JOIN sys_user_profile up ON up.user_id = u.user_id
                LEFT JOIN gmi_monthly g ON g.user_id = a.user_id AND g.gmi_year = [yearNo] AND g.gmi_month = [monthNo]
                WHERE up.designation_id = 4 AND YEAR(p.ppm_task_schedule_date) = [yearNo] AND MONTH(p.ppm_task_schedule_date) = [monthNo] AND p.ppm_task_assigned_to IS NOT NULL
                GROUP BY a.user_id";
            } else if ($title === 'vw_gamification_ppm_weekly') {
                $sql = "SELECT 
                    p.ppm_task_assigned_to,
                    u.site_id,
	                g.gmw_id,
                    COUNT(*) AS ppm_total,
                    SUM(IF(p.ppm_task_time_serviced IS NOT NULL, 1, 0)) AS ppm_completed,
                    SUM(IF(DATE(p.ppm_task_time_serviced) <= p.ppm_task_schedule_date, 1, 0)) AS ppm_on_time,
                    SUM(IF(DATE(p.ppm_task_time_serviced) > p.ppm_task_schedule_date, 1, 0)) AS ppm_late
                FROM ppm_task p
                LEFT JOIN sys_user u ON u.user_id = p.ppm_task_assigned_to
                LEFT JOIN gmi_weekly g ON g.user_id = p.ppm_task_assigned_to AND g.gmw_year = [yearNo] AND g.gmw_week = [weekNo]
                WHERE YEAR(ppm_task_schedule_date) = [yearNo] AND WEEK(ppm_task_schedule_date, 5) = [weekNo] AND p.ppm_task_assigned_to IS NOT NULL
                GROUP BY p.ppm_task_assigned_to";
            } else if ($title === 'vw_gamification_ppm_weekly_end') {
                $sql = "SELECT 
                    p.ppm_task_assigned_to,
                    u.site_id,
	                g.gmw_id,
                    COUNT(*) AS ppm_total,
                    SUM(IF(p.ppm_task_time_serviced IS NOT NULL, 1, 0)) AS ppm_completed,
                    SUM(IF(DATE(p.ppm_task_time_serviced) <= p.ppm_task_schedule_date, 1, 0)) AS ppm_on_time,
                    SUM(IF(DATE(p.ppm_task_time_serviced) > p.ppm_task_schedule_date, 1, 0)) AS ppm_late
                FROM ppm_task p
                LEFT JOIN sys_user u ON u.user_id = p.ppm_task_assigned_to
                LEFT JOIN gmi_weekly g ON g.user_id = p.ppm_task_assigned_to AND g.gmw_year = [yearNo] AND g.gmw_week = [weekNo]
                WHERE ((YEAR(ppm_task_schedule_date) = [yearNo1] AND WEEK(ppm_task_schedule_date, 5) = [weekNo1]) OR (YEAR(ppm_task_schedule_date) = [yearNo2] AND WEEK(ppm_task_schedule_date, 5) = 0) ) 
                  AND p.ppm_task_assigned_to IS NOT NULL
                GROUP BY p.ppm_task_assigned_to";
            } else if ($title === 'vw_gamification_wo_monthly') {
                $sql = "SELECT 
                    w.wo_task_assigned_to,
                    w.site_id,
                    g.gmi_id,
                    COUNT(*) AS wo_total,
                    SUM(IF(w.wo_task_time_executed IS NOT NULL, 1, 0)) AS wo_completed,
                    SUM(IF(w.wo_task_time_executed IS NOT NULL AND TIMESTAMPDIFF(HOUR, w.wo_task_time_created, w.wo_task_time_executed) <= sv.client_severity_hour, 1, 0)) AS wo_on_time,
                    SUM(IF(w.wo_task_time_executed IS NOT NULL AND TIMESTAMPDIFF(HOUR, w.wo_task_time_created, w.wo_task_time_executed) > sv.client_severity_hour, 1, 0)) AS wo_late,
                    SUM(IF(w.wo_task_type = 2, 1, 0)) AS wo_self_finding
                FROM wo_task w
                LEFT JOIN cli_site s ON s.site_id = w.site_id
                LEFT JOIN sys_user_profile up ON up.user_id = w.wo_task_assigned_to
                LEFT JOIN cli_client_severity sv ON sv.client_id = s.client_id AND sv.severity_id = w.wo_task_severity
                LEFT JOIN gmi_monthly g ON g.user_id = w.wo_task_assigned_to AND g.gmi_year = [yearNo] AND g.gmi_month = [monthNo]
                WHERE up.designation_id = 4 AND YEAR(w.wo_task_time_created) = [yearNo] AND MONTH(w.wo_task_time_created) = [monthNo] AND w.wo_task_assigned_to IS NOT NULL
                GROUP BY w.wo_task_assigned_to";
            } else if ($title === 'vw_gamification_wo_assist_monthly') {
                $sql = "SELECT 
                    a.user_id,
                    w.site_id,
                    g.gmi_id,
                    COUNT(*) AS wo_total,
                    SUM(IF(w.wo_task_time_executed IS NOT NULL, 1, 0)) AS wo_completed,
                    SUM(IF(w.wo_task_time_executed IS NOT NULL AND TIMESTAMPDIFF(HOUR, w.wo_task_time_created, w.wo_task_time_executed) <= sv.client_severity_hour, 1, 0)) AS wo_on_time,
                    SUM(IF(w.wo_task_time_executed IS NOT NULL AND TIMESTAMPDIFF(HOUR, w.wo_task_time_created, w.wo_task_time_executed) > sv.client_severity_hour, 1, 0)) AS wo_late
                FROM wo_task_assist a
                LEFT JOIN wo_task w ON w.wo_task_id = a.wo_task_id
                LEFT JOIN cli_site s ON s.site_id = w.site_id
                LEFT JOIN sys_user_profile up ON up.user_id = a.user_id
                LEFT JOIN cli_client_severity sv ON sv.client_id = s.client_id AND sv.severity_id = w.wo_task_severity
                LEFT JOIN gmi_monthly g ON g.user_id = a.user_id AND g.gmi_year = [yearNo] AND g.gmi_month = [monthNo]
                WHERE up.designation_id = 4 AND YEAR(w.wo_task_time_created) = [yearNo] AND MONTH(w.wo_task_time_created) = [monthNo] AND w.wo_task_assigned_to IS NOT NULL
                GROUP BY a.user_id";
            } else if ($title === 'vw_gamification_ppm_daily') {
                $sql = "SELECT 
                    p.ppm_task_assigned_to as ppmTaskAssignedTo,
                    u.site_id as siteId,
                    ppm.ppm_group_id as ppmGroupId,
                    COUNT(*) AS ppmTotal,
                    SUM(IF(p.ppm_task_status = 16, 1, 0)) AS ppmCompleted,
                    SUM(IF(p.ppm_task_status = 16 AND p.ppm_task_time_verified <= p.ppm_task_schedule_date, 1, 0)) AS ppmOnTime,
                    SUM(IF(p.ppm_task_status = 16 AND p.ppm_task_time_verified > p.ppm_task_schedule_date, 1, 0)) AS ppmLate,
                    SUM(IF(p.ppm_task_status = 16 AND p.ppm_task_time_verified <= DATE_ADD(p.ppm_task_schedule_date, INTERVAL 1 DAY), 1, 0)) AS ppmWithin
                FROM ppm_task p
                LEFT JOIN sys_user u ON u.user_id = p.ppm_task_assigned_to
                LEFT JOIN sys_user_profile up ON up.designation_id = 4
                LEFT JOIN ppm ON ppm.ppm_id = p.ppm_id
                WHERE p.ppm_task_schedule_date >= '[dateStart]' AND p.ppm_task_schedule_date <= '[dateEnd]'
                AND p.ppm_task_assigned_to IS NOT NULL AND up.user_id = u.user_id
                GROUP BY p.ppm_task_assigned_to, u.site_id, ppm.ppm_group_id";
            } else if ($title === 'vw_gamification_ppm_assist_daily') {
                $sql = "SELECT 
                    a.user_id as userId,
                    u.site_id as siteId,
                    ppm.ppm_group_id as ppmGroupId,
                    COUNT(*) AS ppmTotal,
                    SUM(IF(p.ppm_task_status = 16, 1, 0)) AS ppmCompleted,
                    SUM(IF(p.ppm_task_status = 16 AND p.ppm_task_time_verified <= p.ppm_task_schedule_date, 1, 0)) AS ppmOnTime,
                    SUM(IF(p.ppm_task_status = 16 AND p.ppm_task_time_verified > p.ppm_task_schedule_date, 1, 0)) AS ppmLate,
                    SUM(IF(p.ppm_task_status = 16 AND p.ppm_task_time_verified <= DATE_ADD(p.ppm_task_schedule_date, INTERVAL 1 DAY), 1, 0)) AS ppmWithin
                FROM ppm_task_assist a
                LEFT JOIN ppm_task p ON p.ppm_task_id = a.ppm_task_id
                LEFT JOIN sys_user u ON u.user_id = a.user_id
                LEFT JOIN ppm ON ppm.ppm_id = p.ppm_id
                WHERE p.ppm_task_schedule_date >= '[dateStart]' AND p.ppm_task_schedule_date <= '[dateEnd]'
                AND a.user_id IS NOT NULL
                GROUP BY a.user_id, u.site_id, ppm.ppm_group_id";
            } else if ($title === 'vw_gamification_wo_daily') {
                $sql = "SELECT 
                    w.wo_task_assigned_to as woTaskAssignedTo,
                    w.wo_task_assigned_to as userId,
                    w.site_id as siteId,
                    w.ppm_group_id as ppmGroupId,
                    COUNT(*) AS woTotal,
                    SUM(IF(w.wo_task_status >= 16, 1, 0)) AS woCompleted,
                    SUM(IF(w.wo_task_status >= 16 AND w.wo_task_time_verified IS NOT NULL AND w.wo_task_time_verified <= DATE_ADD(w.wo_task_time_created, INTERVAL 72 HOUR), 1, 0)) AS woOnTime,
                    SUM(IF(w.wo_task_status >= 16 AND w.wo_task_time_verified IS NOT NULL AND w.wo_task_time_verified > DATE_ADD(w.wo_task_time_created, INTERVAL 72 HOUR), 1, 0)) AS woLate,
                    SUM(IF(w.wo_task_created_by = w.wo_task_assigned_to, 1, 0)) AS woSelfFinding
                FROM wo_task w
                WHERE DATE(w.wo_task_time_created) >= '[dateStart]' AND DATE(w.wo_task_time_created) <= '[dateEnd]'
                AND w.wo_task_status >= 16
                AND w.wo_task_assigned_to IS NOT NULL 
                AND w.wo_task_assigned_to != ''
                GROUP BY w.wo_task_assigned_to, w.site_id, w.ppm_group_id";
            } else if ($title === 'vw_gamification_wo_assist_daily') {
                $sql = "SELECT 
                    a.user_id as userId,
                    w.site_id as siteId,
                    w.ppm_group_id as ppmGroupId,
                    COUNT(*) AS woTotal,
                    SUM(IF(w.wo_task_status = 16, 1, 0)) AS woCompleted,
                    SUM(IF(w.wo_task_status = 16 AND w.wo_task_time_verified <= DATE_ADD(w.wo_task_time_assigned, INTERVAL 24 HOUR), 1, 0)) AS woOnTime,
                    SUM(IF(w.wo_task_status = 16 AND w.wo_task_time_verified > DATE_ADD(w.wo_task_time_assigned, INTERVAL 24 HOUR), 1, 0)) AS woLate
                FROM wo_task_assist a
                LEFT JOIN wo_task w ON w.wo_task_id = a.wo_task_id
                WHERE w.wo_task_time_assigned >= '[dateStart]' AND w.wo_task_time_assigned < DATE_ADD('[dateEnd]', INTERVAL 1 DAY)
                AND a.user_id IS NOT NULL
                GROUP BY a.user_id, w.site_id, w.ppm_group_id";
            } else if ($title === 'vw_ref_att_group') {
                $sql = "SELECT
                    att_group_id,
                    site_id,
                    att_group_name,
                    att_group_category,
                    att_group_holiday,
                    att_group_shift_mode,          
                    att_group_req_week_hours,
                    att_group_status
                FROM att_group";
            } else if ($title === 'vw_att_group') {
                $sql = "SELECT
                    att_group.att_group_id,
                    site_id,
                    att_group_name,
                    att_group_category,
                    att_group_supervisor,
                    ST_AsGeoJSON(att_group_polygon) AS att_group_polygon, 
                    ST_X(att_group_map_center) AS att_group_map_center_lat,
                    ST_Y(att_group_map_center) AS att_group_map_center_lng,
                    att_group_map_zoom,
                    TIME_FORMAT(att_group_day_shift_start,'%h:%i %p') AS att_group_day_shift_start,
                    TIME_FORMAT(att_group_day_shift_end,'%h:%i %p') AS att_group_day_shift_end,
                    TIME_FORMAT(att_group_night_shift_start,'%h:%i %p') AS att_group_night_shift_start,
                    TIME_FORMAT(att_group_night_shift_end,'%h:%i %p') AS att_group_night_shift_end,
                    TIME_FORMAT(att_group_day_shift_start,'%H:%i') AS att_group_day_shift_start_2,
                    TIME_FORMAT(att_group_day_shift_end,'%H:%i') AS att_group_day_shift_end_2,
                    TIME_FORMAT(att_group_night_shift_start,'%H:%i') AS att_group_night_shift_start_2,
                    TIME_FORMAT(att_group_night_shift_end,'%H:%i') AS att_group_night_shift_end_2,
                    att_group_holiday,
                    att_group_req_week_hours,
                    att_group_shift_mode, 
                    att_group_ot_approver,
                    att_group_remark,
                    att_group_status,
                    participant.total_active AS total_participant_active
                FROM att_group
                LEFT JOIN (
                    SELECT att_group_id, COUNT(*) AS total_active
                    FROM att_participant GROUP BY att_group_id
                ) participant ON participant.att_group_id = att_group.att_group_id
                ";
            } else if ($title === 'vw_gmi_monthly_project_m') {
                $sql = "SELECT 
                        cli_site.site_name,
                        SUM(gmi_monthly.gmi_point_total) AS total_score
                    FROM gmi_monthly 
                    LEFT JOIN cli_site ON cli_site.site_id = gmi_monthly.site_id
                    WHERE gmi_year = [yearNo] AND gmi_month = [monthNo] 
                    GROUP BY gmi_monthly.site_id
                    ";
            } else if ($title === 'vg_wo_dashboard') {
                $sql = "SELECT 
                        wo_task.*,
                        ast_asset.asset_no,
                        GROUP_CONCAT(wo_task_assist.user_id) AS assistants
                    FROM wo_task 
                    LEFT JOIN wo_task_assist ON wo_task_assist.wo_task_id = wo_task.wo_task_id
                    LEFT JOIN ast_asset ON ast_asset.asset_id = wo_task.asset_id
                    GROUP BY wo_task.wo_task_id";
            } else if ($title === 'vw_checklist_frequency') {
                $sql = "
                    SELECT GROUP_CONCAT(DISTINCT frequency_name ORDER BY frequency_id SEPARATOR ', ') AS frequency_name FROM (
                        SELECT 
                            ppm_frequency.frequency_id AS frequency_id,
                            ppm_frequency.frequency_name AS frequency_name
                        FROM ppm_checklist_qual
                        LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_checklist_qual.frequency_id
                        WHERE checklist_id = [checklistId]
                        UNION 
                        SELECT 
                            ppm_frequency.frequency_id AS frequency_id,
                            ppm_frequency.frequency_name AS frequency_name
                        FROM ppm_checklist_quan
                        LEFT JOIN ppm_frequency ON ppm_frequency.frequency_id = ppm_checklist_quan.frequency_id
                        WHERE checklist_id = [checklistId]
                    ) aa
                    WHERE frequency_id IS NOT NULL";
            } else if ($title === 'vg_ppm_group_tasks_for_execution') {
                $sql = "SELECT pt.ppm_task_id
                        FROM ppm_task pt
                        INNER JOIN ppm p ON p.ppm_id = pt.ppm_id
                        INNER JOIN ast_asset aa ON aa.asset_id = p.asset_id
                        WHERE pt.ppm_id = [ppmId]
                          AND pt.ppm_task_start_date = '[ppmTaskStartDate]'
                          AND pt.ppm_task_status = [ppmTaskStatus]
                          AND aa.ppm_group_id = [ppmGroupId]";
            } else if ($title === 'vw_ppm_set_list') { // NEW: View for listing PPM Sets
                $sql = "SELECT
                            ps.ppm_set_id,
                            ps.ppm_set_name,
                            ps.ppm_set_desc, -- NEW: Include description
                            ps.asset_type_id,
                            at.asset_type_name,
                            ps.ppm_group_id,
                            pg.ppm_group_name,
                            COUNT(psa.asset_id) AS total_assets,
                            ps.ppm_set_status
                        FROM ppm_set ps
                        LEFT JOIN ppm_set_asset psa ON ps.ppm_set_id = psa.ppm_set_id
                        LEFT JOIN ast_asset_type at ON ps.asset_type_id = at.asset_type_id
                        LEFT JOIN ppm_group pg ON ps.ppm_group_id = pg.ppm_group_id
                        GROUP BY ps.ppm_set_id, ps.ppm_set_name, ps.ppm_set_desc, ps.asset_type_id, at.asset_type_name, ps.ppm_group_id, pg.ppm_group_name, ps.ppm_set_status
                        ORDER BY ps.ppm_set_name";
            } else if ($title === "vw_ppm_set_asset_details") { 
                $sql = "SELECT
                            psa.ppm_set_asset_id,
                            psa.ppm_set_id,
                            psa.asset_id,
                            aa.asset_no,
                            aa.asset_name,
                            aa.asset_location_desc
                        FROM
                            ppm_set_asset psa
                        JOIN
                            ast_asset aa ON psa.asset_id = aa.asset_id";
            } else if ($title === 'vw_wo_import_stats') {
                $sql = "SELECT 
                            wib.batch_id,
                            wib.import_filename,
                            s.site_name,
                            CONCAT(u.user_first_name, ' ', u.user_last_name) as imported_by_name,
                            wib.total_rows,
                            wib.imported_rows,
                            wib.skipped_rows,
                            wib.import_status,
                            wib.created_at,
                            wib.completed_at,
                            TIMESTAMPDIFF(MINUTE, wib.created_at, COALESCE(wib.completed_at, NOW())) as processing_minutes,
                            ROUND((wib.imported_rows / NULLIF(wib.total_rows, 0)) * 100, 2) as success_rate
                        FROM wo_import_batch wib
                        LEFT JOIN cli_site s ON wib.site_id = s.site_id
                        LEFT JOIN sys_user u ON wib.imported_by = u.user_id";
            } else if ($title === 'vw_return_eligible_items') {
                $sql = "SELECT 
                            wtp.wo_task_parts_id AS woTaskPartsId,
                            wtp.part_id AS partId,
                            MAX(i.item_description) AS partName,
                            MAX(CONCAT('ITEM-', wtp.part_id)) AS partCode,
                            wtp.wo_task_parts_quantity AS quantityCollected,
                            wtr.wo_task_request_order_by AS technicianId,
                            MAX(wtr.wo_task_request_time_collected) AS collectedDate,
                            MAX(wt.wo_task_no) AS workOrderNo,
                            COUNT(ps.part_sub_id) AS partsInPossession,
                            COALESCE(SUM(CASE WHEN mr.return_status = 'completed' THEN mr.quantity_returned ELSE 0 END), 0) AS quantityAlreadyReturned,
                            wtp.wo_task_parts_quantity - COALESCE(SUM(CASE WHEN mr.return_status = 'completed' THEN mr.quantity_returned ELSE 0 END), 0) AS quantityAvailableToReturn
                        FROM wo_task_parts wtp
                        INNER JOIN wo_task_request wtr ON wtp.wo_task_request_id = wtr.wo_task_request_id
                        INNER JOIN wo_task wt ON wtr.wo_task_id = wt.wo_task_id
                        INNER JOIN ast_part p ON wtp.part_id = p.part_id
                        LEFT JOIN ref_item i ON p.item_id = i.item_id
                        LEFT JOIN ast_part_sub ps ON wtp.wo_task_parts_id = ps.wo_task_parts_id 
                            AND ps.part_sub_status = '36' 
                            AND ps.part_sub_return_id IS NULL
                        LEFT JOIN material_returns mr ON wtp.wo_task_parts_id = mr.wo_task_parts_id
                        WHERE wtp.wo_task_parts_status = '36'
                            AND wtr.wo_task_request_order_by = [user_id]
                        GROUP BY wtp.wo_task_parts_id, wtp.part_id, wtp.wo_task_parts_quantity, wtr.wo_task_request_order_by
                        HAVING partsInPossession > 0 AND quantityAvailableToReturn > 0";
            } else if ($title === 'vw_storekeeper_pending_returns') {
                $sql = "SELECT 
                            mr.return_id AS returnId,
                            mr.wo_task_parts_id AS woTaskPartsId,
                            mr.part_id AS partId,
                            mr.technician_user_id AS technicianUserId,
                            mr.quantity_returned AS quantityReturned,
                            mr.return_status AS returnStatus,
                            mr.return_reason AS returnReason,
                            mr.return_remarks AS returnRemarks,
                            mr.return_request_date AS returnRequestDate,
                            mr.return_deadline_date AS returnDeadlineDate,
                            mr.return_confirmed_date AS returnConfirmedDate,
                            mr.storekeeper_user_id AS storekeeperUserId,
                            i.item_description AS partName,
                            CONCAT('ITEM-', mr.part_id) AS partCode,
                            '' AS partUnit,
                            CONCAT(u.user_first_name, ' ', u.user_last_name) AS technicianName,
                            wt.wo_task_no AS workOrderNo,
                            wtr.wo_task_request_no AS woTaskRequestNo,
                            s.site_name AS siteName,
                            wt.site_id AS siteId
                        FROM material_returns mr
                        INNER JOIN ast_part p ON mr.part_id = p.part_id
                        LEFT JOIN ref_item i ON p.item_id = i.item_id
                        INNER JOIN sys_user u ON mr.technician_user_id = u.user_id
                        INNER JOIN wo_task_parts wtp ON mr.wo_task_parts_id = wtp.wo_task_parts_id
                        INNER JOIN wo_task_request wtr ON wtp.wo_task_request_id = wtr.wo_task_request_id
                        INNER JOIN wo_task wt ON wtr.wo_task_id = wt.wo_task_id
                        INNER JOIN cli_site s ON wt.site_id = s.site_id
                        WHERE mr.return_status = 'pending'
                            AND [site_filter]";
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


