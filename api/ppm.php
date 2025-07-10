<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_ppm.php';
require_once 'function/f_email.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/ppm.php';

$api_name = 'api_ppm';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_email = new Class_email();
$fn_ppm = new Class_ppm();
$fn_pdf_ppm = new Class_pdf_ppm();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_ppm->__set('constant', $constant);
    $fn_ppm->__set('fn_general', $fn_general);
    $fn_ppm->__set('fn_task', $fn_task);
    $fn_ppm->__set('fn_email', $fn_email);
    $fn_pdf_ppm->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $ppmId = filter_input(INPUT_GET, 'ppmId');
        $type = filter_input(INPUT_GET, 'type');
        if (!is_null($type)) {
            if ($type === 'asset_with_ppm') {
                $contractId = filter_input(INPUT_GET, 'contractId');
                $result = $fn_ppm->get_ppm_from_asset_list($contractId);
            } else if ($type === 'scheduled_ppm') {
                $result = $fn_ppm->get_ppm_scheduled_list($ppmId);
            } else if ($type === 'total_ppm_task') {
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $clientId = filter_input(INPUT_GET, 'clientId');
                $contractId = filter_input(INPUT_GET, 'contractId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $result = $fn_ppm->get_total_ppm_task($dateFrom, $dateTo, $clientId, $siteId, $contractId);
            } else if ($type === 'total_ppm_late') {
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $clientId = filter_input(INPUT_GET, 'clientId');
                $contractId = filter_input(INPUT_GET, 'contractId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $result = $fn_ppm->get_total_ppm_late($dateFrom, $dateTo, $clientId, $siteId, $contractId);
            } else if ($type === 'perc_ppm_done') {
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $clientId = filter_input(INPUT_GET, 'clientId');
                $contractId = filter_input(INPUT_GET, 'contractId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $result = $fn_ppm->get_perc_ppm_done($dateFrom, $dateTo, $clientId, $siteId, $contractId);
            }
            else if ($type === 'total_by_site_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_total_ppm_by_site_status($clientId, $dateFrom, $dateTo);
            }
            else if ($type === 'total_by_site_trade') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_total_ppm_by_site_trade($clientId, $dateFrom, $dateTo);
            }
            else if ($type === 'total_by_trade') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_total_ppm_by_trade($clientId, $siteId, $dateFrom, $dateTo);
            }
            else if ($type === 'total_by_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_total_ppm_by_status($clientId, $siteId, $dateFrom, $dateTo);
            }
            else if ($type === 'top5_execute') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_ppm_top5_execute($clientId, $siteId, $dateFrom, $dateTo);
            }
            else if ($type === 'bottom5_execute') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_ppm_bottom5_execute($clientId, $siteId, $dateFrom, $dateTo);
            }
            else if ($type === 'average_execute_by_trade') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $dateFrom = filter_input(INPUT_GET, 'dateFrom');
                $dateTo = filter_input(INPUT_GET, 'dateTo');
                $result = $fn_ppm->get_ppm_average_execute_by_trade($clientId, $siteId, $dateFrom, $dateTo);
            }
            else if ($type === 'report_ppm_summary') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_ppm->get_report_ppm_summary($siteId, $year, $month);
            }
            else if ($type === 'dashboard_list') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $isRoutine = filter_input(INPUT_GET, 'isRoutine');
                $result = $fn_ppm->get_ppm_list($clientId, $siteId, $year, $month, $isRoutine);
            } else if ($type === 'ppm_set_list') {
                $result = $fn_ppm->get_ppm_set_list();
            } else if ($type === 'ppm_set_details') {
                $ppmSetId = filter_input(INPUT_GET, 'ppmSetId');
                $result = $fn_ppm->get_ppm_set_details($ppmSetId);
            } else if ($type === 'assets_for_ppm_set_selection') { // <-- NEW: Endpoint for assets to add to a set
                $ppmSetId = filter_input(INPUT_GET, 'ppmSetId'); // Pass ppmSetId to exclude existing assets
                // REMOVED: $contractId = filter_input(INPUT_GET, 'contractId'); // contractId is no longer a direct filter for the core function
                // NEW: assetGroupId and assetCategoryId are now explicitly required filters
                $assetGroupId = filter_input(INPUT_GET, 'assetGroupId');
                $assetCategoryId = filter_input(INPUT_GET, 'assetCategoryId');
                $assetTypeId = filter_input(INPUT_GET, 'assetTypeId'); // assetTypeId was already there

                // UPDATED function call with new parameters
                $result = $fn_ppm->get_assets_for_ppm_set_modal($ppmSetId, $assetGroupId, $assetCategoryId, $assetTypeId);
            } else if ($type === 'assets_in_ppm_set') {
                $ppmSetId = filter_input(INPUT_GET, 'ppmSetId');
                $result = $fn_ppm->get_assets_in_ppm_set($ppmSetId);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
            }
        } else if (!is_null($ppmId)) {
            //$result = $fn_asset->get_asset($ppmId);
        } else {
            //$result = $fn_asset->get_asset_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'assign_ppm_single') {
            $assetId = filter_input(INPUT_POST, 'assetId');
            $checklistId = filter_input(INPUT_POST, 'checklistId');
            $ppmDateStart = filter_input(INPUT_POST, 'ppmDateStart');
            $ppmGroupId = filter_input(INPUT_POST, 'ppmGroupId');

            $result = $fn_ppm->assign_ppm_single($assetId, $checklistId, $ppmDateStart, $jwt_data->userId, $ppmGroupId);
            $fn_general->save_audit('80', $jwt_data->userId, 'PPM Task No = ' . $result['ppmTaskNo']);
            //$form_data['errmsg'] = $constant::SUC_PPM_SAVE;
            $form_data['errmsg'] = 'PPM Task (Document No. = '.$result['ppmTaskNo'].') for Asset '.$result['assetNo'].' successfully registered and ready to executed by Technician!';
        }
        else if ($action === 'create_ppm_set') { // <-- NEW: Action for creating a PPM Set
            $ppmSetName = filter_input(INPUT_POST, 'ppmSetName');
            $ppmSetDesc = filter_input(INPUT_POST, 'ppmSetDesc');
            // ADD THESE TWO LINES:
            $assetGroupId = filter_input(INPUT_POST, 'assetGroupId');
            $assetCategoryId = filter_input(INPUT_POST, 'assetCategoryId');
            $assetTypeId = filter_input(INPUT_POST, 'assetTypeId');
            $ppmGroupId = filter_input(INPUT_POST, 'ppmGroupId');

            // Update the function call signature
            $result = $fn_ppm->create_ppm_set_basic($ppmSetName, $ppmSetDesc, $assetGroupId, $assetCategoryId, $assetTypeId, $ppmGroupId, $jwt_data->userId);
            $fn_general->save_audit('X_AUDIT_ID_FOR_CREATE_SET', $jwt_data->userId, 'PPM Set Id = ' . $result['ppmSetId'] . ', Name = ' . $ppmSetName); // Use a relevant audit ID
            $form_data['errmsg'] = $constant::SUC_SAVE; // "Successfully saved!"
        }
        else if ($action === 'generate_pdf') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fn_pdf_ppm->__set('ppmTaskId', $ppmTaskId);
            $result = $fn_pdf_ppm->create_pdf();
        } else if ($action === 'add_assets_to_ppm_set') {
            $ppmSetId = filter_input(INPUT_POST, 'ppmSetId');
            $assetIds = json_decode(filter_input(INPUT_POST, 'assetIds'), true); // Assuming assetIds is sent as a JSON string array from frontend
            if (empty($assetIds) && filter_input(INPUT_POST, 'assetIds') !== '[]') { // Handle cases where json_decode returns null/empty
                throw new Exception('[' . __LINE__ . '] - Parameter assetIds invalid or empty');
            }
            $result = $fn_ppm->add_assets_to_ppm_set($ppmSetId, $assetIds, $jwt_data->userId);
            $fn_general->save_audit('X_AUDIT_ID_FOR_ADD_ASSET_TO_SET', $jwt_data->userId, 'PPM Set Id = ' . $ppmSetId . ', Added ' . $result['totalAdded'] . ' assets.');
            $form_data['errmsg'] = $result['totalAdded'] . ' asset(s) successfully added to PPM Set!';
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'reschedule_date') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $fn_ppm->reschedule_date($ppmTaskId, $put_vars);
            $fn_general->save_audit('127', $jwt_data->userId, 'ppmTaskId = '.$ppmTaskId.', new date = '.$put_vars['newDate']);
            $form_data['errmsg'] = $constant::SUC_PPM_RESCHEDULE;
        } else if ($action === 'update_ppm_set') {
            $ppmSetId = filter_input(INPUT_GET, 'ppmSetId'); // Assuming ID is in GET for PUT, common pattern. Or from $put_vars.
            // Let's assume ppmSetId comes from put_vars for consistency with other PUT actions from JS.
            if (!isset($put_vars['ppmSetId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmSetId empty');
            }
            $ppmSetId = $put_vars['ppmSetId']; // Get ppmSetId from parsed PUT data.

            $fn_ppm->update_ppm_set($ppmSetId, $put_vars, $jwt_data->userId); // Pass all put_vars as params
            $fn_general->save_audit('X_AUDIT_ID_FOR_UPDATE_SET', $jwt_data->userId, 'PPM Set Id = ' . $ppmSetId); // Use a relevant audit ID
            $form_data['errmsg'] = $constant::SUC_SAVE; // "Successfully saved!"
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    } else if ('DELETE' === $request_method) {
        $delete_data = file_get_contents("php://input");
        parse_str($delete_data, $delete_vars); // Parse the delete payload

        $action = $delete_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'remove_assets_from_ppm_set') {
            $ppmSetId = $delete_vars['ppmSetId'];
            $assetIds = json_decode($delete_vars['assetIds'], true); // Assuming assetIds is sent as JSON string array
            if (empty($assetIds) && $delete_vars['assetIds'] !== '[]') {
                throw new Exception('[' . __LINE__ . '] - Parameter assetIds invalid or empty');
            }

            $result = $fn_ppm->remove_assets_from_ppm_set($ppmSetId, $assetIds, $jwt_data->userId);
            $fn_general->save_audit('X_AUDIT_ID_FOR_REMOVE_ASSET_FROM_SET', $jwt_data->userId, 'PPM Set Id = ' . $ppmSetId . ', Removed ' . $result['totalRemoved'] . ' assets.');
            $form_data['errmsg'] = $result['totalRemoved'] . ' asset(s) successfully removed from PPM Set!';
        } else if ($action === 'delete_ppm_set') {
            if (!isset($delete_vars['ppmSetId']) || empty($delete_vars['ppmSetId'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter ppmSetId empty');
            }
            $ppmSetId = $delete_vars['ppmSetId'];
            
            $fn_ppm->delete_ppm_set($ppmSetId, $jwt_data->userId);
            $form_data['errmsg'] = "PPM Set successfully deleted!";
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    } else {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        Class_db::getInstance()->db_rollback();
    }
    Class_db::getInstance()->db_close();
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);