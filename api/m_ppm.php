<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_ppm.php';
require_once 'function/f_user.php';
require_once 'function/f_email.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/ppm.php';

$api_name = 'api_m_ppm';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_user = new Class_user();
$fn_email = new Class_email();
$fn_ppm = new Class_ppm();
$fn_pdf_ppm = new Class_pdf_ppm();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_email->__set('fn_general', $fn_general);
    $fn_user->__set('constant', $constant);
    $fn_user->__set('fn_general', $fn_general);
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
    if (!isset($headers['authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['authorization']);

    if (!isset($headers['deviceid'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
    }
    $fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        if ($type === 'pending_task') {
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId);
        } else if ($type === 'all_task') {
            $result = $fn_ppm->get_ppm_all_task_m($jwt_data->userId);
        } else if ($type === 'all_task_search') {
            $searchTxt = filter_input(INPUT_GET, 'searchTxt');
            $result = $fn_ppm->get_ppm_all_task_m($jwt_data->userId,'', '', $searchTxt);
        } else if ($type === 'all_task_scan_asset') {
            $assetNo = filter_input(INPUT_GET, 'assetNo');
            $result = $fn_ppm->get_ppm_all_task_m($jwt_data->userId,'', $assetNo);
        } else if ($type === 'pending_task_search') {
            $searchTxt = filter_input(INPUT_GET, 'assetNo');
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId, '', $searchTxt);
        } else if ($type === 'pending_task_scan_asset') {
            $assetNo = filter_input(INPUT_GET, 'assetNo');
            $result = $fn_ppm->get_pending_task_m($jwt_data->userId, $assetNo);
        } else if ($type === 'calendar_list') {
            $date = filter_input(INPUT_GET, 'date');
            $result = $fn_ppm->get_ppm_all_task_m($jwt_data->userId, $date);
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
        }
        else if ($type === 'preview_pdf') {
            $ppmTaskId = filter_input(INPUT_GET, 'ppmTaskId');
            $fn_pdf_ppm->__set('ppmTaskId', $ppmTaskId);
            $pdfId = $fn_pdf_ppm->create_pdf();
            $result = $fn_general->getPdf($pdfId);
            $fn_general->save_audit('101', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
        }
        else if ($type === 'tnm_list') {
            $flowId = filter_input(INPUT_GET, 'flowId');
            $result = $fn_task->get_track_monitoring_list_m($jwt_data->userId, $flowId);
        }
        else if ($type === 'tnm_list_scan_asset') {
            $flowId = filter_input(INPUT_GET, 'flowId');
            $assetNo = filter_input(INPUT_GET, 'assetNo');
            $result = $fn_task->get_track_monitoring_list_m($jwt_data->userId, $flowId, $assetNo);
        }
        else if ($type === 'tnm_list_search') {
            $flowId = filter_input(INPUT_GET, 'flowId');
            $searchTxt = filter_input(INPUT_GET, 'searchTxt');
            $result = $fn_task->get_track_monitoring_list_m($jwt_data->userId, $flowId, '', $searchTxt);
        }
        else if ($type === 'tnm_details') {
            $transactionId = filter_input(INPUT_GET, 'transactionId');
            $result = $fn_task->get_track_monitoring_details_m($transactionId);
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

        if ($action === 'save_token') {
            $token = filter_input(INPUT_POST, 'token');
            $fn_user->save_token($jwt_data->userId, $token);
        }
        else if ($action === 'change_password') {
            $oldPassword = filter_input(INPUT_POST, 'oldPassword');
            $newPassword = filter_input(INPUT_POST, 'newPassword');
            $put_vars = array(
                'oldPassword'=>$oldPassword,
                'newPassword'=>$newPassword
            );
            $fn_user->change_password($jwt_data->userId, $put_vars);
            $fn_general->save_audit('6', $jwt_data->userId);
            $form_data['errmsg'] = $constant::SUC_CHANGE_PASSWORD;
        }
        else if ($action === 'edit_profile') {
            $name = filter_input(INPUT_POST, 'name');
            $phoneNo = filter_input(INPUT_POST, 'phoneNo');
            $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $uploadId = '';
            if (is_array($fileUpload) && !empty($fileUpload) && !empty($fileUpload['filename'])) {
                $uploadId = $fn_general->uploadDocument($fileUpload, 8, $jwt_data->userId);
            }
            $result = $fn_user->update_profile_m($jwt_data->userId, $name, $phoneNo, $uploadId);
            $fn_general->save_audit('5', $jwt_data->userId);
            $form_data['errmsg'] = $constant::SUC_UPDATE_PROFILE;
        }
        else if ($action === 'save_qualitative_tasks') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $ppmTaskQuals = filter_input(INPUT_POST, 'ppmTaskQual', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->save_qualitative_tasks_m($ppmTaskId, $ppmTaskQuals);
            $fn_general->save_audit('82', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_quantitative_tasks') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $ppmTaskQuans = filter_input(INPUT_POST, 'ppmTaskQuan', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->save_quantitative_tasks_m($ppmTaskId, $ppmTaskQuans);
            $fn_general->save_audit('83', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'check_ppm_parts') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $checked = filter_input(INPUT_POST, 'checked');
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $fn_ppm->save_ppm_check_parts_m($ppmTaskId, $checked);
            $fn_general->save_audit('102', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId . ', Check = '.$checked);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'add_ppm_parts') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskPartsDesc = filter_input(INPUT_POST, 'ppmTaskPartsDesc');
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $result = $fn_ppm->add_ppm_parts_m($ppmTaskId, $ppmTaskPartsDesc);
            $fn_general->save_audit('84', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'check_additional_report') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $checked = filter_input(INPUT_POST, 'checked');
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $fn_ppm->save_ppm_check_additional_report_m($ppmTaskId, $checked);
            $fn_general->save_audit('86', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId . ', Check = '.$checked);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'upload_additional_report') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $uploadId = $fn_general->uploadDocument($fileUpload, 1, $jwt_data->userId);
            $fn_ppm->save_ppm_additional_report_m($ppmTaskId, $uploadId);
            $fn_general->save_audit('87', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_ppm_remark') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskRemark = filter_input(INPUT_POST, 'ppmTaskRemark');
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
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
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $uploadId = $fn_general->uploadDocument($fileUpload, intval($uploadType)+2, $jwt_data->userId);
            $fn_ppm->save_ppm_maintenance_image_m($ppmTaskId, $uploadId, $uploadType, $longitude, $latitude);
            $fn_general->save_audit('89', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_scan_start_time') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $fn_ppm->check_current_task($ppmTaskId, '1');
            $fn_ppm->save_ppm_scan_start_time_m($ppmTaskId, $jwt_data->userId);
            $fn_general->save_audit('91', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SCAN_START_TIME;
        }
        else if ($action === 'save_image_desc') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $ppmTaskUploads = filter_input(INPUT_POST, 'ppmTaskUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $fn_ppm->check_current_task($ppmTaskId, '1', $jwt_data->userId);
            $fn_ppm->save_image_desc_m($ppmTaskId, $ppmTaskUploads);
            $fn_general->save_audit('98', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'submit_ppm') {
            $ppmTaskId = filter_input(INPUT_POST, 'ppmTaskId');
            $checkpoint = filter_input(INPUT_POST, 'checkpoint');
            $result = filter_input(INPUT_POST, 'result');
            $remark = filter_input(INPUT_POST, 'remark');
            $fn_ppm->check_current_task($ppmTaskId, $checkpoint, $jwt_data->userId);
            $uploadId = '';
            $nextUser = '';
            if ($result == '1') {
                if ($checkpoint == '1' || $checkpoint == '2') {
                    $nextUser = $fn_ppm->get_next_ppm_user($ppmTaskId, $checkpoint);
                }
                $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
                $uploadId = $fn_general->uploadDocument($fileUpload, intval($checkpoint) + 4, $jwt_data->userId);
            }
            $submitParam = $fn_ppm->process_ppm($ppmTaskId, $checkpoint, $result, $uploadId, $jwt_data->userId, $remark, $nextUser);
            $taskId = $submitParam['taskId'];
            if ($result == '1') {
                $toGroup = '';
                if ($checkpoint == '2' && $nextUser !== '') {
                    $toGroup = $fn_task->get_group_id_from_user($nextUser, '4');
                }
                $fn_task->submit_task($taskId, $jwt_data->userId, '9', $remark, '', '', $toGroup, $nextUser);
            } else if ($result == '2') {
                $fn_task->submit_task($taskId, $jwt_data->userId, '20', $remark, '1', '', '', '', 1);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter result invalid');
            }
            $fn_general->save_audit('100', $jwt_data->userId, 'PPM Task Id = ' . $ppmTaskId . ', $checkpoint = ' . $checkpoint . ', result = ' . $result);
            $fn_ppm->ppm_submit_notification($submitParam['emailTo'], $submitParam['taskStatus'], $submitParam['ppmTaskNo'], $submitParam['comment']);
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
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