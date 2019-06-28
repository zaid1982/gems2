<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_ppm.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_ppm = new Class_ppm();
$api_name = 'api_m_ppm';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

try {
    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    //$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if (!isset($headers['Deviceid'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
    }
    $fn_login->check_device_id($jwt_data->userId, $headers['Deviceid']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        if ($type === 'pending_task') {
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId);
        } else if ($type === 'pending_task_scan_asset') {
            $assetNo = filter_input(INPUT_GET, 'assetNo');
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId, '', $assetNo);
        } else if ($type === 'calendar_list') {
            $date = filter_input(INPUT_GET, 'date');
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId, $date);
        } else if ($type === 'calendar_dot') {
            $month = filter_input(INPUT_GET, 'month');
            $year = filter_input(INPUT_GET, 'year');
            $result = $fn_ppm->get_calendar_task_dot_m($jwt_data->userId, $month, $year);
        } else if ($type === 'ppm_section_status') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_status_m($ppmTaskId);
        } else if ($type === 'ppm_section_a') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_a_m($ppmTaskId);
        } else if ($type === 'ppm_section_b') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_b_m($ppmTaskId);
        } else if ($type === 'ppm_section_c') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_c_m($ppmTaskId);
        } else if ($type === 'ppm_section_d') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_d_m($ppmTaskId);
        } else if ($type === 'ppm_section_f') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_upload_m($ppmTaskId, '3');
        } else if ($type === 'ppm_section_e') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_e_m($ppmTaskId);
        } else if ($type === 'ppm_section_g') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_g_m($ppmTaskId);
        } else if ($type === 'ppm_section_h') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $result = $fn_ppm->get_ppm_section_upload_m($ppmTaskId, '(0,1,2)');
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter type invalid');
        }

        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'save_qualitative_tasks') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskQuals = filter_input(INPUT_POST, 'ppmTaskQual', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->save_qualitative_tasks_m($ppmTaskId, $ppmTaskQuals);
            $fn_general->save_audit('82', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_quantitative_tasks') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskQuans = filter_input(INPUT_POST, 'ppmTaskQuan', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->save_quantitative_tasks_m($ppmTaskId, $ppmTaskQuans);
            $fn_general->save_audit('83', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'add_ppm_parts') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskPartsDesc = filter_input(INPUT_POST, 'ppmTaskPartsDesc');
            $result = $fn_ppm->add_ppm_parts_m($ppmTaskId, $ppmTaskPartsDesc);
            $fn_general->save_audit('84', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'check_additional_report') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $checked = filter_input(INPUT_POST, 'checked');
            $fn_ppm->save_ppm_check_additional_report_m($ppmTaskId, $checked);
            $fn_general->save_audit('86', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId . ', Check = '.$checked);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'upload_additional_report') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $uploadId = $fn_general->uploadDocument($fileUpload, 1, $jwt_data->userId);
            $fn_ppm->save_ppm_additional_report_m($ppmTaskId, $uploadId);
            $fn_general->save_audit('87', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_ppm_remark') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskRemark = filter_input(INPUT_POST, 'ppmTaskRemark');
            $fn_ppm->save_ppm_remark_m($ppmTaskId, $ppmTaskRemark);
            $fn_general->save_audit('81', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId . ', Comment = ' . $ppmTaskRemark);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'upload_maintenance_image') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $uploadType = filter_input(INPUT_POST, 'uploadType');
            $longitude = filter_input(INPUT_POST, 'longitude');
            $latitude = filter_input(INPUT_POST, 'latitude');
            $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            if ($uploadType != '0' && $uploadType != '1' && $uploadType != '2') {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadType invalid');
            }
            $uploadId = $fn_general->uploadDocument($fileUpload, intval($uploadType)+2, $jwt_data->userId);
            $fn_ppm->save_ppm_maintenance_image_m($ppmTaskId, $uploadId, $uploadType, $longitude, $latitude);
            $fn_general->save_audit('89', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_scan_start_time') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fn_ppm->save_ppm_scan_start_time_m($ppmTaskId);
            $fn_general->save_audit('91', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SCAN_START_TIME;
        }
        else if ($action === 'save_image_desc') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskUploads = filter_input(INPUT_POST, 'ppmTaskUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->save_image_desc_m($ppmTaskId, $ppmTaskUploads);
            $fn_general->save_audit('98', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'submit_ppm') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $checkpoint = filter_input(INPUT_POST, 'checkpoint');
            $result = filter_input(INPUT_POST, 'result');
            $remark = filter_input(INPUT_POST, 'remark');
            $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $uploadId = $fn_general->uploadDocument($fileUpload, intval($checkpoint)+4, $jwt_data->userId);
            $taskId = $fn_ppm->process_ppm($ppmTaskId, $checkpoint, $result, $uploadId, $jwt_data->userId);
            if ($result == '1') {
                $fn_task->submit_task($taskId, $jwt_data->userId, '9', $remark);
            } else if ($result == '2') {
                $fn_task->submit_task($taskId, $jwt_data->userId, '20', $remark, '1');
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter result invalid');
            }
            $fn_general->save_audit('100', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId . ', $checkpoint = ' . $checkpoint . ', result = ' . $result);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $action = filter_input(INPUT_GET, 'action');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'delete_ppm_parts') {
            $ppmTaskPartsId = filter_input(INPUT_GET, 'ppmTaskPartsId');
            $ppmTaskId = $fn_ppm->delete_ppm_parts_m($ppmTaskPartsId);
            $fn_general->save_audit('85', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_DELETE;
        }
        else if ($action === 'delete_ppm_additional_report') {
            $ppmTaskUploadId = filter_input(INPUT_GET, 'ppmTaskUploadId');
            $ppmTaskId = $fn_ppm->delete_ppm_additional_report_m($ppmTaskUploadId);
            $fn_general->save_audit('88', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_DELETE;
        }
        else if ($action === 'delete_ppm_maintenance_image') {
            $ppmTaskUploadId = filter_input(INPUT_GET, 'ppmTaskUploadId');
            $ppmTaskId = $fn_ppm->delete_ppm_maintenance_image_m($ppmTaskUploadId);
            $fn_general->save_audit('90', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_DELETE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action empty');
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