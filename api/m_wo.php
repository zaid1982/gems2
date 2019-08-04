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

    if (!isset($headers['deviceid'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
    }
    $fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        if ($type === 'pending_task') {

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

        if ($action === 'submit_complain') {
            $woTaskLocation = filter_input(INPUT_POST, 'woTaskLocation');
            $woTaskComplaint = filter_input(INPUT_POST, 'woTaskComplaint');
            $woTaskLongitude = filter_input(INPUT_POST, 'woTaskLongitude');
            $woTaskLatitude = filter_input(INPUT_POST, 'woTaskLatitude');
            $complaintImageUploads = array();
            $complaintImages = filter_input(INPUT_POST, 'complaintImages', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            foreach ($complaintImages as $complaintImage) {
                $uploadId = $fn_general->uploadDocument($complaintImage, 9, $jwt_data->userId);
                $complaintImageUpload = array('uploadId'=>$uploadId, 'description'=>$complaintImage['description'], 'longitude'=>$complaintImage['longitude'], 'latitude'=>$complaintImage['latitude']);
                array_push($complaintImageUploads, $complaintImageUpload);
            }
            $signature = filter_input(INPUT_POST, 'signature', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $signatureId = $fn_general->uploadDocument($signature, 13, $jwt_data->userId);

            $groupId = $fn_task->get_group_id_from_user($jwt_data->userId, '6');
            $woTaskNo = $fn_wo->create_wo_no($jwt_data->userId, $groupId);
            $taskId = $fn_task->create_new_task('2', $jwt_data->userId, '6', $groupId, $woTaskNo);
            $newTaskId = $fn_task->submit_task($taskId, $jwt_data->userId, '9', $woTaskComplaint);
            $fn_wo->process_new_complaint($taskId, $woTaskNo, $woTaskLocation, $woTaskComplaint, $complaintImageUploads, $signatureId, $woTaskLongitude, $woTaskLatitude);
            $fn_general->save_audit('104', $jwt_data->userId, 'Work Order no. = ' . $woTaskNo);
            $nextUsers = $fn_task->get_checkpoints_users ('7', '12');
            foreach ($nextUsers as $userId) {
                $this->fn_email->setup_email($userId, 4, array('task_no'=>$woTaskNo));
                $this->fn_email->setup_mobile_notification($userId, 5);
            }
            $form_data['errmsg'] = $constant::SUC_WO_COMPLAINT_SUBMITTED;
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