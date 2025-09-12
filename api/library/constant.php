<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2/18/2019
 * Time: 10:39 PM
 */

class Class_constant {

    const URL = '//localhost/gems2/api/';
    //const URL = '//gems.globalfm.com.my/api/';
    // const URL = '//gems.metadatasystem.my/api/';
    //const URL_FULL = 'http://localhost/gems2/api/';
    //const URL_FULL = 'https://gems.globalfm.com.my/api/';
    const URL_FULL = 'https://gems.metadatasystem.my/gems2/api/';

    const ERR_DEFAULT = 'Error on system. Please contact Administrator!';
    const ERR_LOGIN_NOT_EXIST = 'Invalid Login ID or Password. Please try again.';
    const ERR_LOGIN_WRONG_PASSWORD = 'Invalid Login ID or Password. Please try again.';
    const ERR_LOGIN_BLOCK = 'You account has been blocked. Please retry after 10 minutes.';
    const ERR_RESET_SAME_PASSWORD = 'Password cannot be similar to previous';
    const ERR_LOGIN_NOT_ACTIVE = 'User ID is not active. Please contact Administrator to activate.';
    const ERR_USER_ALREADY_ACTIVATED = 'Your ID already activated.';
    const ERR_FORGOT_PASSWORD_NOT_EXIST = 'Email not exist';
    const ERR_CHANGE_PASSWORD_WRONG_CURRENT = 'Old password not correct';
    const ERR_CHANGE_PASSWORD_OLD_NEW_SAME = 'New password cannot be the same as old password';
    const ERR_ROLE_DELETE_HAVE_TASK = 'This user cannot be removed from this roles since there are still task assigned. Please delegate the task first.';
    const ERR_ROLE_DELETE_ALONE = 'There is no other user are assigned to this role. Please assign this role to new user before remove this user form this role.';
    const ERR_USER_ADD_SIMILAR_USERNAME = 'Login ID already registered. Please choose another ID.';
    const ERR_USER_ADD_SIMILAR_EMAIL = 'Email already registered. Please choose another email.';

    const SUC_FORGOT_PASSWORD = 'Your password successfully reset. Please login with temporary password sent to your email.';
    const SUC_CHANGE_PASSWORD = 'Your password has been changed';
    const SUC_RESET_PASSWORD = 'Your password successfully updated';
    const SUC_UPDATE_PROFILE = 'Your profile successfully updated';
    const SUC_EDIT_PASSWORD = 'Password successfully changed';

    const ERR_USER_DEACTIVATE = 'User already inactive';
    const ERR_USER_ACTIVATE = 'User already active';
    const ERR_USER_EXIST_IN_GROUP = 'User already registered in PPM / WO Group for current site. Please remove user from the group first to change site.';
    const ERR_TASK_ALREADY_SUBMITTED = 'This task already submitted';
    const ERR_TASK_CLAIMED = 'This task currently processed by other user';

    const ERR_DESIGNATION_SIMILAR = 'Designation already exist';
    const ERR_DESIGNATION_DEACTIVATE = 'Designation already inactive';
    const ERR_DESIGNATION_ACTIVATE = 'Designation already active';
    const ERR_DESIGNATION_DELETE = 'Designation cannot be deleted because currently registered under user profile';
    const ERR_CLIENT_SIMILAR = 'Client already exist';
    const ERR_CLIENT_DEACTIVATE = 'Client already inactive';
    const ERR_CLIENT_ACTIVATE = 'Client already active';
    const ERR_CLIENT_DELETE_SITE = 'Client cannot be deleted because currently registered under Site';
    const ERR_CLIENT_SEVERITY_NOT_EXIST = 'Severity not exist under this client. Please try again.';
    const ERR_SITE_SIMILAR = 'Site Name already exist under similar Client';
    const ERR_SITE_SIMILAR_CODE = 'Site Code already exist';
    const ERR_SITE_DEACTIVATE = 'Site already inactive';
    const ERR_SITE_ACTIVATE = 'Site already active';
    const ERR_SITE_DELETE_CONTRACT = 'Site cannot be deleted because currently registered under Contract';
    const ERR_CONTRACT_SIMILAR = 'Contract already exist under similar Client';
    const ERR_CONTRACT_DEACTIVATE = 'Contract already inactive';
    const ERR_CONTRACT_ACTIVATE = 'Contract already active';
    const ERR_CONTRACT_DELETE_ASSET = 'Contract cannot be deleted because currently registered under Asset';
    const ERR_ASSET_GROUP_SIMILAR = 'Asset Group already exist';
    const ERR_ASSET_GROUP_DEACTIVATE = 'Asset Group already inactive';
    const ERR_ASSET_GROUP_ACTIVATE = 'Asset Group already active';
    const ERR_ASSET_GROUP_DELETE_CATEGORY = 'Asset Group cannot be deleted because currently registered under Asset Category';
    const ERR_ASSET_CATEGORY_SIMILAR = 'Asset Category already exist';
    const ERR_ASSET_CATEGORY_DEACTIVATE = 'Asset Category already inactive';
    const ERR_ASSET_CATEGORY_ACTIVATE = 'Asset Category already active';
    const ERR_ASSET_CATEGORY_DELETE_TYPE = 'Asset Category cannot be deleted because currently registered under Asset Type';
    const ERR_ASSET_TYPE_SIMILAR = 'Asset Type already exist';
    const ERR_ASSET_TYPE_DEACTIVATE = 'Asset Type already inactive';
    const ERR_ASSET_TYPE_ACTIVATE = 'Asset Type already active';
    const ERR_ASSET_TYPE_DELETE_ASSET = 'Asset Type cannot be deleted because currently registered under Asset';
    const ERR_ASSET_TYPE_DELETE_MODEL = 'Asset Type cannot be deleted because currently registered under Asset Model';
    const ERR_ASSET_BRAND_SIMILAR = 'Asset Brand already exist';
    const ERR_ASSET_BRAND_DEACTIVATE = 'Asset Brand already inactive';
    const ERR_ASSET_BRAND_ACTIVATE = 'Asset Brand already active';
    const ERR_ASSET_BRAND_DELETE_MODEL = 'Asset Brand cannot be deleted because currently registered under Asset Model';
    const ERR_ASSET_MODEL_SIMILAR = 'Asset Model already exist';
    const ERR_ASSET_MODEL_DEACTIVATE = 'Asset Model already inactive';
    const ERR_ASSET_MODEL_ACTIVATE = 'Asset Model already active';
    const ERR_ASSET_MODEL_DELETE_ASSET = 'Asset Model cannot be deleted because currently registered under Asset';
    const ERR_ASSET_SUBMITTED = 'Asset already registered';
    const ERR_ASSET_SIMILAR = 'Asset No already exist in similar contract';
    const ERR_ASSET_SIMILAR_SERIAL_NO = 'Asset Serial No already exist in similar contract';
    const ERR_ASSET_DEACTIVATE = 'Asset already inactive';
    const ERR_ASSET_ACTIVATE = 'Asset already active';
    const ERR_ASSET_DELETE_PPM = 'Asset cannot be deleted because currently registered under Preventive Maintenance';
    const ERR_CHECKLIST_SUBMITTED = 'Checklist already registered';
    const ERR_CHECKLIST_SIMILAR = 'Checklist Name and Version already exist in similar Asset Type';
    const ERR_CHECKLIST_EMPTY_QUAL = 'Qualitative Task empty';
    const ERR_CHECKLIST_DEACTIVATE = 'Checklist already inactive';
    const ERR_CHECKLIST_ACTIVATE = 'Checklist already active';
    const ERR_CHECKLIST_DELETE_PPM = 'Checklist cannot be deleted because currently registered under Preventive Maintenance';
    const ERR_CHECKLIST_QUAL_SUBMITTED = 'Qualitative Task already registered';
    const ERR_CHECKLIST_QUAL_SIMILAR = 'Qualitative Task Description and Numbering already exist in this checklist';
    const ERR_CHECKLIST_QUAL_DEACTIVATE = 'Qualitative Task already inactive';
    const ERR_CHECKLIST_QUAL_ACTIVATE = 'Qualitative Task already active';
    const ERR_CHECKLIST_QUAL_DELETE_PPM = 'Qualitative Task cannot be deleted because currently registered under Preventive Maintenance';
    const ERR_CHECKLIST_QUAN_SUBMITTED = 'Quantitative Task already registered';
    const ERR_CHECKLIST_QUAN_SIMILAR = 'Quantitative Task Description and Numbering already exist in this checklist';
    const ERR_CHECKLIST_QUAN_DEACTIVATE = 'Quantitative Task already inactive';
    const ERR_CHECKLIST_QUAN_ACTIVATE = 'Quantitative Task already active';
    const ERR_CHECKLIST_QUAN_DELETE_PPM = 'Quantitative Task cannot be deleted because currently registered under Preventive Maintenance';
    const ERR_PPM_SIMILAR_ASSET = 'Asset already assign to PPM';
    const ERR_PPM_NO_TECHNICIAN = 'No technician assign to this contract. Please make sure at least 1 technician assign under this contract.';
    const ERR_PPM_NO_DATES = 'No dates available between the start cycle date and contract end date';
    const ERR_PPM_PARTS_EXIST = 'Spare Parts / Material Used already exist';
    const ERR_LOCATION_CODE_SIMILAR = 'Location Code already exist under similar contract';
    const ERR_LOCATION_CODE_DEACTIVATE = 'Location Code already inactive';
    const ERR_LOCATION_CODE_ACTIVATE = 'Location Code already active';
    const ERR_LOCATION_CODE_DELETE_USER = 'Location Code cannot be deleted because currently registered under Technician';
    const ERR_LOCATION_CODE_DELETE_ASSET = 'Location Code cannot be deleted because currently registered under Asset';
    const ERR_CONTRACT_USER_SIMILAR = 'This technician already exist in this Location Code and Asset Group';
    const ERR_PPM_GROUP_SIMILAR = 'PPM Group already exist in similar site';
    const ERR_PPM_GROUP_DELETE_USER = 'PPM Group cannot be deleted because currently have user in it';
    const ERR_PPM_GROUP_DELETE_REPORT_TO = 'PPM Group cannot be deleted because currently have another PPM Group report to this group';
    const ERR_PPM_GROUP_DELETE_ASSET = 'PPM Group cannot be deleted because currently registered under Asset';
    const ERR_PPM_GROUP_DELETE_PPM = 'PPM Group cannot be deleted because already assigned PPM Task';
    const ERR_PPM_GROUP_USER_SIMILAR = 'This user already in this group';
    const ERR_PPM_GROUP_SUPERVISOR_EMPTY = 'Task fail to submit because supervisor not assigned yet. Please contact system administrator.';
    const ERR_PPM_GROUP_ENGINEER_EMPTY = 'Task fail to submit because engineer not assigned yet. Please contact system administrator.';
    const ERR_PPM_RESCHEDULE_EXIST = 'The selected date cannot be proceeded because already occupied with another PPM under same asset no';
    const ERR_PPM_RESCHEDULE_UNALLOWED = 'This PPM is are not allowed to perform rescheduling';

    const ERR_WO_NOT_CLIENT = 'You are not registered to any client to perform complaint. Please contact system administrator.';
    const ERR_WO_LOCATION_EMPTY = 'Complaint Location is empty';
    const ERR_WO_DESCRIPTION_EMPTY = 'Complaint Description is empty';

    const SUC_SAVE = 'Successfully saved!';
    const SUC_SUBMITTED = 'Successfully submitted!';
    const SUC_RETURNED = 'Successfully returned!';
    const SUC_REJECTED = 'Successfully rejected!';
    const SUC_ACTIVATED = 'Successfully activated!';
    const SUC_DEACTIVATED = 'Successfully deactivated!';
    const SUC_VERIFIED_AND_CLOSED = 'Successfully verified and closed!';
    const SUC_SCAN_START_TIME = 'Start Time successfully set!';
    const SUC_DELETE = 'Successfully removed!';
    const SUC_UPLOADED_IMAGE = 'Image Successfully uploaded!';

    const SUC_USER_ADD = 'User successfully added';
    const SUC_USER_UPDATE = 'User successfully updated';
    const SUC_USER_DEACTIVATE = 'User successfully deactivated';
    const SUC_USER_ACTIVATE = 'User successfully activated';
    const SUC_DESIGNATION_ADD = 'Designation successfully added';
    const SUC_DESIGNATION_EDIT = 'Designation successfully updated';
    const SUC_DESIGNATION_DEACTIVATE = 'Designation successfully deactivated';
    const SUC_DESIGNATION_ACTIVATE = 'Designation successfully activated';
    const SUC_DESIGNATION_DELETE = 'Designation successfully deleted';
    const SUC_CLIENT_ADD = 'Client successfully added';
    const SUC_CLIENT_EDIT = 'Client successfully updated';
    const SUC_CLIENT_DEACTIVATE = 'Client successfully deactivated';
    const SUC_CLIENT_ACTIVATE = 'Client successfully activated';
    const SUC_CLIENT_DELETE = 'Client successfully deleted';
    const SUC_CLIENT_SEVERITY_HOUR_EDIT = 'Client Severity KPI Hour successfully updated';
    const SUC_SITE_ADD = 'Site successfully added';
    const SUC_SITE_EDIT = 'Site successfully updated';
    const SUC_SITE_DEACTIVATE = 'Site successfully deactivated';
    const SUC_SITE_ACTIVATE = 'Site successfully activated';
    const SUC_SITE_DELETE = 'Site successfully deleted';
    const SUC_CONTRACT_ADD = 'Contract successfully added';
    const SUC_CONTRACT_EDIT = 'Contract successfully updated';
    const SUC_CONTRACT_DEACTIVATE = 'Contract successfully deactivated';
    const SUC_CONTRACT_ACTIVATE = 'Contract successfully activated';
    const SUC_CONTRACT_DELETE = 'Contract successfully deleted';
    const SUC_ASSET_GROUP_ADD = 'Asset Group successfully added';
    const SUC_ASSET_GROUP_EDIT = 'Asset Group successfully updated';
    const SUC_ASSET_GROUP_DEACTIVATE = 'Asset Group successfully deactivated';
    const SUC_ASSET_GROUP_ACTIVATE = 'Asset Group successfully activated';
    const SUC_ASSET_GROUP_DELETE = 'Asset Group successfully deleted';
    const SUC_ASSET_CATEGORY_ADD = 'Asset Category successfully added';
    const SUC_ASSET_CATEGORY_EDIT = 'Asset Category successfully updated';
    const SUC_ASSET_CATEGORY_DEACTIVATE = 'Asset Category successfully deactivated';
    const SUC_ASSET_CATEGORY_ACTIVATE = 'Asset Category successfully activated';
    const SUC_ASSET_CATEGORY_DELETE = 'Asset Category successfully deleted';
    const SUC_ASSET_TYPE_ADD = 'Asset Type successfully added';
    const SUC_ASSET_TYPE_EDIT = 'Asset Type successfully updated';
    const SUC_ASSET_TYPE_DEACTIVATE = 'Asset Type successfully deactivated';
    const SUC_ASSET_TYPE_ACTIVATE = 'Asset Type successfully activated';
    const SUC_ASSET_TYPE_DELETE = 'Asset Type successfully deleted';
    const SUC_ASSET_BRAND_ADD = 'Asset Brand successfully added';
    const SUC_ASSET_BRAND_EDIT = 'Asset Brand successfully updated';
    const SUC_ASSET_BRAND_DEACTIVATE = 'Asset Brand successfully deactivated';
    const SUC_ASSET_BRAND_ACTIVATE = 'Asset Brand successfully activated';
    const SUC_ASSET_BRAND_DELETE = 'Asset Brand successfully deleted';
    const SUC_ASSET_MODEL_ADD = 'Asset Model successfully added';
    const SUC_ASSET_MODEL_EDIT = 'Asset Model successfully updated';
    const SUC_ASSET_MODEL_DEACTIVATE = 'Asset Model successfully deactivated';
    const SUC_ASSET_MODEL_ACTIVATE = 'Asset Model successfully activated';
    const SUC_ASSET_MODEL_DELETE = 'Asset Model successfully deleted';
    const SUC_ASSET_SAVE = 'Asset successfully saved';
    const SUC_ASSET_REGISTER = 'Asset successfully registered';
    const SUC_ASSET_EDIT = 'Asset successfully updated';
    const SUC_ASSET_DEACTIVATE = 'Asset successfully deactivated';
    const SUC_ASSET_ACTIVATE = 'Asset successfully activated';
    const SUC_ASSET_DELETE = 'Asset successfully deleted';
    const SUC_CHECKLIST_SAVE = 'Checklist successfully saved';
    const SUC_CHECKLIST_DUPLICATED = 'Checklist successfully duplicated';
    const SUC_CHECKLIST_REGISTER = 'Checklist successfully registered';
    const SUC_CHECKLIST_EDIT = 'Checklist successfully updated';
    const SUC_CHECKLIST_DEACTIVATE = 'Checklist successfully deactivated';
    const SUC_CHECKLIST_ACTIVATE = 'Checklist successfully activated';
    const SUC_CHECKLIST_DELETE = 'Checklist successfully deleted';
    const SUC_CHECKLIST_QUAL_ADD = 'Qualitative Task successfully added';
    const SUC_CHECKLIST_QUAL_EDIT = 'Qualitative Task successfully updated';
    const SUC_CHECKLIST_QUAL_DEACTIVATE = 'Qualitative Task successfully deactivated';
    const SUC_CHECKLIST_QUAL_ACTIVATE = 'Qualitative Task successfully activated';
    const SUC_CHECKLIST_QUAL_DELETE = 'Qualitative Task successfully deleted';
    const SUC_CHECKLIST_QUAN_ADD = 'Quantitative Task successfully added';
    const SUC_CHECKLIST_QUAN_EDIT = 'Quantitative Task successfully updated';
    const SUC_CHECKLIST_QUAN_DEACTIVATE = 'Quantitative Task successfully deactivated';
    const SUC_CHECKLIST_QUAN_ACTIVATE = 'Quantitative Task successfully activated';
    const SUC_CHECKLIST_QUAN_DELETE = 'Quantitative Task successfully deleted';
    const SUC_PPM_SAVE = 'PM tasks successfully scheduled and assigned to technician';
    const SUC_LOCATION_CODE_ADD = 'Location Code successfully added';
    const SUC_LOCATION_CODE_EDIT = 'Location Code successfully updated';
    const SUC_LOCATION_CODE_DEACTIVATE = 'Location Code successfully deactivated';
    const SUC_LOCATION_CODE_ACTIVATE = 'Location Code successfully activated';
    const SUC_LOCATION_CODE_DELETE = 'Location Code successfully deleted';
    const SUC_CONTRACT_USER_ADD = 'Technician successfully added';
    const SUC_CONTRACT_USER_DELETE = 'Technician successfully removed';
    const SUC_PPM_GROUP_ADD = 'PPM Group successfully added';
    const SUC_PPM_GROUP_EDIT = 'PPM Group successfully updated';
    const SUC_PPM_GROUP_DELETE = 'PPM Group successfully deleted';
    const SUC_PPM_GROUP_USER_ADD = 'PPM Group User successfully added';
    const SUC_PPM_GROUP_USER_DELETE = 'PPM Group User successfully deleted';
    const SUC_PPM_RESCHEDULE = 'PPM Date successfully rescheduled';
    const SUC_PPM_ADD_ASSISTANT = 'PPM Assistant successfully added';
    const SUC_PPM_DELETE_ASSISTANT = 'PPM Assistant successfully removed';
    const SUC_PPM_SAVE_ASSISTANTS = 'PPM Assistant List successfully saved';

    const SUC_WO_COMPLAINT_SUBMITTED = 'We have received your Complaint. You will be contacted soon. You will receive notification soon on your mobile and email.';
    const SUC_WO_SAVE_ASSIGNED_TECHNICIAN = 'Assigned technician successfully saved';
    const SUC_WO_ADD_ASSISTANT = 'Assistant successfully added';
    const SUC_WO_DELETE_ASSISTANT = 'Assistant successfully removed';
    const SUC_WO_SAVE_ASSISTANTS = 'Assistant List successfully saved';
    const SUC_WO_SAVE_WO_SEVERITY = 'Work Order severity successfully saved';
    const SUC_WO_SAVE_WO_RECTIFICATION_TIME = 'Rectification time successfully saved';
    const SUC_WO_SAVE_WO_REPAIR_WORK = 'Work Order repair work successfully saved';
    const SUC_WO_SAVE_WO_RATE = 'Rating successfully saved';
    const SUC_WO_DELETE = 'Work Order successfully deleted';
    const SUC_WO_MANUAL_REPORT_ADD = 'Manual Work Order Report successfully added';
    const SUC_WO_MANUAL_REPORT_EDIT = 'Manual Work Order Report successfully updated';

    // Visitor Management (VM)
    const SUC_VM_VISIT_SUBMITTED = 'Visitor registration successfully submitted';

    const ERR_ATT_NO_POLYGON = 'Please make sure site/office Perimeter for attendance verification selected from the Map';
    const ERR_ATT_GROUP_NAME_EXIST = 'The Attendance Group name already exist. Please choose other group name';

    // ===== PTW Role Enums (centralized) =====
    // Use these for RBAC checks across PTW APIs and UI guards.
    // Keep lists broad to be backward-compatible with legacy role names.
    const PTW_ROLE_SUPERVISOR_NAME = 'PTW Supervisor';
    const PTW_ROLE_SHE_NAME = 'PTW SHE';
    const PTW_ROLE_FM_NAME = 'PTW Facility Manager';
    const ROLE_ADMIN = [
        'ADMIN', 'SUPER ADMIN', 'SUPERADMIN', 'SYSTEM ADMINISTRATOR', 'PTW ADMIN'
    ];
    const PTW_ROLE_SUPERVISOR = [
        'PTW SUPERVISOR', 'PTW Supervisor',
        'SUPERVISOR', 'SITE SUPERVISOR', 'PTW_SUPERVISOR'
    ];
    const PTW_ROLE_SHE = [
        'PTW SHE', 'SHE', 'HSE', 'HSSE'
    ];
    const PTW_ROLE_FM = [
        'PTW FACILITY MANAGER', 'PTW Facility Manager',
        'FACILITY MANAGER', 'FACILITY_MANAGER', 'FM'
    ];
}