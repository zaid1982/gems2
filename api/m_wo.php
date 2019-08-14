<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_email.php';
require_once 'function/f_wo.php';

$api_name = 'api_m_wo';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_email = new Class_email();
$fn_wo = new Class_wo();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_email->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['authorization']);
    $fn_wo->__set('userId', $jwt_data->userId);

    if (!isset($headers['deviceid'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
    }
    $fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');
        $fn_wo->__set('woTaskId', $woTaskId);
        if ($type === 'submitted_wo') {
            $searchTxt = filter_input(INPUT_GET, 'searchTxt');
            $result = $fn_wo->get_submitted_wo_m($searchTxt);
        } else if ($type === 'pending_task') {
            $searchTxt = filter_input(INPUT_GET, 'searchTxt');
            $result = $fn_wo->get_pending_task_m($searchTxt);
        } else if ($type === 'section_status') {
            $result = $fn_wo->get_section_status_m();
        } else if ($type === 'section_status_assign') {
            $result = $fn_wo->get_section_status_assign_m();
        } else if ($type === 'complaint_details') {
            $result = $fn_wo->get_complaint_details_m();
        } else if ($type === 'wo_group_list') {
            $result = $fn_wo->get_wo_group_m();
        } else if ($type === 'wo_technician_list') {
            $ppmGroupId = filter_input(INPUT_GET, 'groupId');
            $result = $fn_wo->get_wo_technician_m($ppmGroupId);
        } else if ($type === 'technician_details') {
            $ppmGroupId = filter_input(INPUT_GET, 'groupId');
            $userId = filter_input(INPUT_GET, 'userId');
            $result = $fn_wo->get_technician_details_m($userId, $ppmGroupId);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter type invalid');
        }

        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');
        $woTaskId = filter_input(INPUT_POST, 'woTaskId');
        $fn_wo->__set('woTaskId', $woTaskId);

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'submit_complain') {
            $woTaskLocation = filter_input(INPUT_POST, 'woTaskLocation');
            $woTaskComplaint = filter_input(INPUT_POST, 'woTaskComplaint');
            $woTaskLongitude = filter_input(INPUT_POST, 'woTaskLongitude');
            $woTaskLatitude = filter_input(INPUT_POST, 'woTaskLatitude');
            $complaintImageUploads = array();
            $complaintImages = filter_input(INPUT_POST, 'complaintImages', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            if (!empty($complaintImages)) {
                foreach ($complaintImages as $complaintImage) {
                    $uploadId = $fn_general->uploadDocument($complaintImage, 9, $jwt_data->userId);
                    $complaintImageUpload = array('uploadId' => $uploadId, 'description' => $complaintImage['description'], 'longitude' => $complaintImage['longitude'], 'latitude' => $complaintImage['latitude']);
                    array_push($complaintImageUploads, $complaintImageUpload);
                }
            }
            $signature = filter_input(INPUT_POST, 'signature', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $signatureId = $fn_general->uploadDocument($signature, 13, $jwt_data->userId);

            $groupId = $fn_task->get_group_id_from_user($jwt_data->userId, '6');
            $woTaskNo = $fn_wo->create_wo_no($jwt_data->userId, $groupId);
            $taskId = $fn_task->create_new_task('2', $jwt_data->userId, '6', $groupId, $woTaskNo);
            $newTaskId = $fn_task->submit_task($taskId, $jwt_data->userId, '9', $woTaskComplaint, '', '', $groupId);
            $woTaskId = $fn_wo->submit_new_complaint($taskId, $woTaskNo, $woTaskLocation, $woTaskComplaint, $complaintImageUploads, $signatureId, $woTaskLongitude, $woTaskLatitude);
            $fn_wo->__set('woTaskId', $woTaskId);
            $fn_general->save_audit('104', $jwt_data->userId, 'Work Order no. = '.$woTaskNo);
            $nextUsers = $fn_task->get_checkpoints_users('7', '12');
            foreach ($nextUsers as $userId) {
                $fn_email->setup_email($userId, 4, array('task_no' => $woTaskNo));
                $fn_email->setup_mobile_notification($userId, 5);
            }
            $fn_wo->save_respond_time_m();
            $form_data['errmsg'] = $constant::SUC_WO_COMPLAINT_SUBMITTED;
        }
        else if ($action === 'save_assigned_technician') {
            $userId = filter_input(INPUT_POST, 'userId');
            $returnVal = $fn_wo->save_assigned_technician_m($userId);
            $fn_general->save_audit('110', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo'].', technician = '.$returnVal['userFirstName']);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_ASSIGNED_TECHNICIAN;
        }
        else if ($action === 'save_wo_severity') {
            $severity = filter_input(INPUT_POST, 'severity');
            $returnVal = $fn_wo->save_wo_severity_m($severity);
            $fn_general->save_audit('111', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo'].', severity = '.$returnVal['severityName']);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_WO_SEVERITY;
        }
        else if ($action === 'submit_assign') {
            $assignedTechnician = $fn_wo->get_assigned_technician();
            $currentTask = $fn_wo->get_current_task('24', '12');
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '10', '', '', '', '', $assignedTechnician);
            $returnVal = $fn_wo->submit_assign($currentTask['transactionId']);
            $fn_general->save_audit('112', $jwt_data->userId, 'Work Order no. = '.$returnVal);
            $fn_email->setup_email($assignedTechnician, 5, array('task_no' => $returnVal));
            $fn_email->setup_mobile_notification($assignedTechnician, 6);
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
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