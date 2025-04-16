<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_task.php';
require_once 'function/f_email.php';
require_once 'function/f_wo.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/wo.php';
require_once 'pdf/wr.php';
require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/NotiWeb.php';

$api_name = 'api_m_wo';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_task = new Class_task();
$fn_email = new Class_email();
$fn_wo = new Class_wo();
$fn_pdf_wo = new Class_pdf_wo();
$fn_pdf_wr = new Class_pdf_wr();
$fn_noti_web = new NotiWeb();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_email->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_pdf_wo->__set('fn_general', $fn_general);
    $fn_pdf_wr->__set('fn_general', $fn_general);
    $fn_noti_web->isLogged = Constant::$isLogged;
    DbMysql::$isLogged = Constant::$isLogged;

    Class_db::getInstance()->db_connect();
    DbMysql::connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty - '.json_encode($headers));
    }
    $fn_wo->__set('userId', $jwt_data->userId);
    $fn_noti_web->userId = $jwt_data->userId;
    DbMysql::$userId = $jwt_data->userId;

    if (!isset($headers['deviceid'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty - '.$headers['deviceid']);
    }
    //$fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');
        $fn_wo->__set('woTaskId', $woTaskId);
        if ($type === 'submitted_wo') {
            $searchTxt = filter_input(INPUT_GET, 'searchTxt');
            $woType = filter_input(INPUT_GET, 'woType');
            $result = $fn_wo->get_submitted_wo_m($searchTxt, $woType);
        } else if ($type === 'pending_task') {
            $searchTxt = filter_input(INPUT_GET, 'searchTxt');
            $woType = filter_input(INPUT_GET, 'woType');
            $result = $fn_wo->get_pending_task_m($searchTxt, $woType);
        } else if ($type === 'section_status') {
            $result = $fn_wo->get_section_status_m();
        } else if ($type === 'section_status_assign') {
            $result = $fn_wo->get_section_status_assign_m();
        } else if ($type === 'section_status_wr') {
            $result = $fn_wo->get_section_status_wr_m();
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
        } else if ($type === 'assign_and_severity') {
            $result = $fn_wo->get_wo_assign_severity_m();
        } else if ($type === 'wo_repair_work') {
            $result = $fn_wo->get_wo_repair_desc_m();
        } else if ($type === 'wo_repair_images') {
            $result = $fn_wo->get_wo_repair_images_m();
        } else if ($type === 'preview_pdf') {
            if ($fn_wo->get_wo_is_wr() === '1') {
                $fn_pdf_wr->__set('woTaskId', $woTaskId);
                $returnVal = $fn_pdf_wr->create_pdf();
            } else {
                $fn_pdf_wo->__set('woTaskId', $woTaskId);
                $returnVal = $fn_pdf_wo->create_pdf();
            }
            $result = $fn_general->getPdf($returnVal['pdfId']);
            $fn_general->save_audit('118', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo']);
        } else if ($type === 'wo_rate') {
            $result = $fn_wo->get_wo_rate_m();
        } else if ($type === 'wo_severity_list') {
            $result = $fn_wo->get_wo_severity_list_m();
        } else if ($type === 'wr_rectification_time') {
            $result = $fn_wo->get_wr_rectification_time_m();
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

            $roleId = $fn_wo->get_role_id_from_user();
            $checkpointId = $roleId==='6'?'11':'10';
            $groupId = $fn_task->get_group_id_from_user($jwt_data->userId, $roleId);
            $woTaskNo = $fn_wo->create_wo_no($groupId, false);
            $taskId = $fn_task->create_new_task('2', $jwt_data->userId, $roleId, $groupId, $woTaskNo, '', $checkpointId);
			$fn_wo->__set('userId', $jwt_data->userId);
            $isWr = $fn_wo->get_wo_is_wr();
            if ($roleId === '6' && $isWr === '1') {
                $newTaskId = $fn_task->submit_task($taskId, $jwt_data->userId, '9', '', '1', '', $groupId);
            } else {
                $newTaskId = $fn_task->submit_task($taskId, $jwt_data->userId, '9', '', '', '', $groupId);
            }
            $woTaskId = $fn_wo->submit_new_complaint($taskId, $woTaskNo, $woTaskLocation, $woTaskComplaint, $complaintImageUploads, $woTaskLongitude, $woTaskLatitude);
            $fn_wo->__set('woTaskId', $woTaskId);
            $nextUsers = $fn_task->get_checkpoints_users('7', '12', $woTaskId);
            foreach ($nextUsers as $userId) {
                //$fn_email->setup_email($userId, 4, array('task_no' => $woTaskNo), $isWr === '1');
                $fn_email->setup_email($userId, 4, array('task_no' => $woTaskNo));
                $fn_email->setup_mobile_notification($userId, 5, array('task_no' => $woTaskNo));
                $fn_noti_web->insert($isWr === '1' ? 2 : 1, $userId, $woTaskNo);
            }
            $fn_wo->save_respond_time_m();
            $form_data['errmsg'] = $constant::SUC_WO_COMPLAINT_SUBMITTED;
            if ($isWr === '1') {
                $fn_general->save_audit('104', $jwt_data->userId, 'Work Request no. = '.$woTaskNo);
            } else {
                $fn_general->save_audit('104', $jwt_data->userId, 'Work Order no. = '.$woTaskNo);
            }
        }
        else if ($action === 'save_assigned_technician') {
            $ppmGroupId = filter_input(INPUT_POST, 'groupId');
            $userId = filter_input(INPUT_POST, 'userId');
            $severity = filter_input(INPUT_POST, 'severity');
            $woTaskType = filter_input(INPUT_POST, 'woTaskCategory');
            $assistUserId = filter_input(INPUT_POST, 'assistUserId', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $returnVal = $fn_wo->save_assigned_technician_m($ppmGroupId, $userId, $severity, $assistUserId, $woTaskType);
            $fn_general->save_audit('110', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo'].', technician = '.$returnVal['userFirstName'].', severity = '.$returnVal['severityName'].', category = '.$returnVal['woTaskType']);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_ASSIGNED_TECHNICIAN;
        }
        else if ($action === 'save_wo_severity') {
            $severity = filter_input(INPUT_POST, 'severity');
            $returnVal = $fn_wo->save_wo_severity_m($severity);
            $fn_general->save_audit('111', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo'].', severity = '.$returnVal['severityName']);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_WO_SEVERITY;
        }
        else if ($action === 'save_wr_rectification_time') {
            $rectificationTime = filter_input(INPUT_POST, 'rectificationTime');
            $returnVal = $fn_wo->save_wr_rectification_time_m($rectificationTime);
            $fn_general->save_audit('111', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo'].', rectification time = '.$returnVal['timeRectified']);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_WO_RECTIFICATION_TIME;
        }
        else if ($action === 'save_asset_no') {
            $assetNo = filter_input(INPUT_POST, 'assetNo');
            $fn_wo->__set('userId', $jwt_data->userId);
            $fn_wo->save_asset_no_m($assetNo);
            $fn_general->save_audit('135', $jwt_data->userId, 'Work Order ID = '.$woTaskId.', Asset No. = '.$assetNo);
            $form_data['errmsg'] = 'Asset No successfully saved';
        }
        else if ($action === 'submit_assign') {
            $assignedTechnician = $fn_wo->get_assigned_technician();
            $isWr = $fn_wo->get_wo_is_wr();
            $currentTask = $fn_wo->get_current_task('24', '12', '26', '17', '29');
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '10', '', '', '', '', $assignedTechnician);
            $returnVal = $fn_wo->submit_assign($currentTask['transactionId']);
            $auditLabel = $isWr === '1' ? 'Work Request no. = ' : 'Work Order no. = ';
            $emailTemplateId = $isWr === '1' ? 11 : 5;
            $notiTextId = $isWr === '1' ? 12 : 6;
            $fn_general->save_audit('129', $jwt_data->userId, $auditLabel.$returnVal);
            $fn_email->setup_email($assignedTechnician, $emailTemplateId, array('task_no' => $returnVal));
            $fn_email->setup_mobile_notification($assignedTechnician, $notiTextId, array('task_no' => $returnVal));
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
        }
        else if ($action === 'submit_wr_check') {
            $woTask = $fn_wo->getWoTask($woTaskId);
            $currentTask = $fn_wo->get_current_task('27', '18', '31');
            $isVerified = filter_input(INPUT_POST, 'isVerified');
            if ($woTask['woTaskIsPublic'] === '1' && ($isVerified === '0' || $isVerified === '3')) {
                throw new Exception('[' . __LINE__ . '] - This public complaint cannot be submitted to Client for verification.', 31);
            }
            $remark = filter_input(INPUT_POST, 'remark');
            $signature = filter_input(INPUT_POST, 'signature', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $signatureId = $fn_general->uploadDocument($signature, 17, $jwt_data->userId);
            if ($isVerified === '0') {
                $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '9', $remark, '1');
                $returnVal = $fn_wo->submit_wr_check($currentTask['transactionId'], $signatureId, $remark, '0');
                $fn_general->save_audit('131', $jwt_data->userId, 'Work Request no. = '.$returnVal['woTaskNo']);
                $fn_email->setup_email($returnVal['woTaskCreatedBy'], 12, array('task_no'=>$returnVal['woTaskNo'], 'comment'=>$remark, 'suggestion'=>'VALID'));
                $fn_email->setup_mobile_notification($returnVal['woTaskCreatedBy'], 13, array('task_no'=>$returnVal['woTaskNo'], 'suggestion'=>'VALID', 'comment'=>$remark));
            }
            else if ($isVerified === '1') {
                $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '9', $remark);
                $returnValCheck = $fn_wo->submit_wr_check($currentTask['transactionId'], $signatureId, $remark, '1');
                $signatureVerifier = filter_input(INPUT_POST, 'signatureVerifier', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
                $signatureVerifierId = $fn_general->uploadDocument($signature, 18, $returnValCheck['woTaskCreatedBy']);
                $roleId = $fn_wo->get_role_id_from_user();
                $groupId = $fn_task->get_group_id_from_user($jwt_data->userId, $roleId);
                $woTaskNo = $fn_wo->create_wo_no($groupId);
                $returnVal = $fn_wo->submit_wr_verify($currentTask['transactionId'], $signatureVerifierId, $woTaskNo, $returnValCheck['woTaskCreatedBy']);
                $fn_general->save_audit('130', $jwt_data->userId, 'Work Request no. = ' . $returnVal['woTaskNo']);
                $fn_email->setup_email($returnVal['woTaskAssignedTo'], 5, array('task_no' => $returnVal['woTaskNo']));
                $fn_email->setup_mobile_notification($returnVal['woTaskAssignedTo'], 6, array('task_no' => $returnVal['woTaskNo']));
            }
            else if ($isVerified === '2') {
                $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '30', $remark, '3');
                $returnValCheck = $fn_wo->submit_wr_check($currentTask['transactionId'], $signatureId, $remark, '1', '1');
                $signatureVerifier = filter_input(INPUT_POST, 'signatureVerifier', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
                $signatureVerifierId = $fn_general->uploadDocument($signature, 18, $returnValCheck['woTaskCreatedBy']);
                $roleId = $fn_wo->get_role_id_from_user();
                $groupId = $fn_task->get_group_id_from_user($jwt_data->userId, $roleId);
                $woTaskNo = $fn_wo->create_wo_no($groupId);
                $returnVal = $fn_wo->submit_wr_verify($currentTask['transactionId'], $signatureVerifierId, $woTaskNo, $returnValCheck['woTaskCreatedBy'], '1');
                $fn_general->save_audit('134', $jwt_data->userId, 'Work Request no. = ' . $returnVal['woTaskNo']);
                if ($woTask['woTaskIsPublic'] === '1') {
                    $woTaskPublic = $fn_wo->getWoTaskPublic($woTaskId);
                    $fn_email->setup_email($returnVal['woTaskCreatedBy'], 14, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark), false, $woTaskPublic['woTaskPublicName'], $woTaskPublic['woTaskPublicEmail']);
                } else {
                    $fn_email->setup_email($returnVal['woTaskCreatedBy'], 14, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
                    $fn_email->setup_mobile_notification($returnVal['woTaskCreatedBy'], 15, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
                }
            }
            else if ($isVerified === '3') {
                $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '9', $remark, '1');
                $returnVal = $fn_wo->submit_wr_check($currentTask['transactionId'], $signatureId, $remark, '0', '1');
                $fn_general->save_audit('131', $jwt_data->userId, 'Work Request no. = '.$returnVal['woTaskNo']);
                $fn_email->setup_email($returnVal['woTaskCreatedBy'], 12, array('task_no'=>$returnVal['woTaskNo'], 'comment'=>$remark, 'suggestion'=>'INVALID'));
                $fn_email->setup_mobile_notification($returnVal['woTaskCreatedBy'], 13, array('task_no'=>$returnVal['woTaskNo'], 'suggestion'=>'INVALID', 'comment'=>$remark));
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter isVerified invalid');
            }
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
        }
        else if ($action === 'submit_wr_verified') {
            $currentTask = $fn_wo->get_current_task('28', '19');
            $isRejected = $fn_wo->get_wr_validity();
            $remark = filter_input(INPUT_POST, 'remark');
            $signature = filter_input(INPUT_POST, 'signature', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $signatureId = $fn_general->uploadDocument($signature, 18, $jwt_data->userId);
            $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '9', $remark, '1');
            $roleId = $fn_wo->get_role_id_from_user();
            $groupId = $fn_task->get_group_id_from_user($jwt_data->userId, $roleId);
            $woTaskNo = $fn_wo->create_wo_no($groupId);
            if ($isRejected === '1') {
                $auditActionId = '134';
                $emailTemplateId = 14;
                $notiTextId = 15;
            } else {
                $auditActionId = '132';
                $emailTemplateId = 5;
                $notiTextId = 6;
            }
            $returnVal = $fn_wo->submit_wr_verify($currentTask['transactionId'], $signatureId, $woTaskNo, '', $isRejected);
            $fn_general->save_audit($auditActionId, $jwt_data->userId, 'Work Request no. = '.$returnVal['woTaskNo']);
            $fn_email->setup_email($jwt_data->userId, $emailTemplateId, array('task_no'=>$returnVal['woTaskNo'], 'comment'=>$remark));
            $fn_email->setup_mobile_notification($jwt_data->userId, $notiTextId, array('task_no'=>$returnVal['woTaskNo'], 'comment'=>$remark));
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
        }
        else if ($action === 'return_by_verifier') {
            $remark = filter_input(INPUT_POST, 'remark');
            $currentTask = $fn_wo->get_current_task('28', '19');
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '31', $remark, '2');
            $returnVal = $fn_wo->return_wr_verify($currentTask['transactionId']);
            $fn_general->save_audit('147', $jwt_data->userId, 'Work Request no. = '.$returnVal['woTaskNo']);
            $fn_email->setup_email($returnVal['technician'], 15, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
            $fn_email->setup_mobile_notification($returnVal['technician'], 16, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
            $form_data['errmsg'] = $constant::SUC_RETURNED;
        }
        else if ($action === 'reject_complaint') {
            $remark = filter_input(INPUT_POST, 'remark');
            $currentTask = $fn_wo->get_current_task('24', '12');
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '25', $remark, '1');
            $returnVal = $fn_wo->reject_complaint($currentTask['transactionId']);
            $fn_general->save_audit('122', $jwt_data->userId, 'Work Order no. = '.$returnVal);
            $woTask = $fn_wo->getWoTask($woTaskId);
            $complainer = $fn_wo->get_complainer();
            if ($woTask['woTaskIsPublic'] === '1') {
                $woTaskPublic = $fn_wo->getWoTaskPublic($woTaskId);
                $fn_email->setup_email($complainer, 10, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark), false, $woTaskPublic['woTaskPublicName'], $woTaskPublic['woTaskPublicEmail']);
            } else {
                $fn_email->setup_email($complainer, 10, array('task_no' => $returnVal, 'comment'=>$remark));
                $fn_email->setup_mobile_notification($complainer, 11, array('task_no' => $returnVal, 'comment'=>$remark));
            }
            $form_data['errmsg'] = $constant::SUC_REJECTED;
        }
        else if ($action === 'save_wo_repair_work') {
            $repairDesc = filter_input(INPUT_POST, 'repairWork');
            $returnVal = $fn_wo->save_wo_repair_desc_m($repairDesc);
            $fn_general->save_audit('113', $jwt_data->userId, 'Work Order no. = '.$returnVal.', repair work = '.$repairDesc);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_WO_REPAIR_WORK;
        }
        else if ($action === 'upload_repair_image') {
            $uploadType = filter_input(INPUT_POST, 'uploadType');
            $longitude = filter_input(INPUT_POST, 'longitude');
            $latitude = filter_input(INPUT_POST, 'latitude');
            $fileUpload = filter_input(INPUT_POST, 'fileUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            if ($uploadType !== '2' && $uploadType !== '3' && $uploadType !== '4') {
                throw new Exception('[' . __LINE__ . '] - Parameter uploadType invalid');
            }
            $uploadId = $fn_general->uploadDocument($fileUpload, intval($uploadType)+8, $jwt_data->userId);
            $returnVal = $fn_wo->save_wo_image_m($uploadId, $uploadType, $longitude, $latitude);
            $arrUploadType = $fn_wo->get_upload_type();
            $fn_general->save_audit('114', $jwt_data->userId, 'Work Order no. = '.$returnVal.', upload type = '.$arrUploadType[intval($uploadType)]);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'save_wo_repair_image_desc') {
            $woTaskUploads = filter_input(INPUT_POST, 'woTaskUpload', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $returnVal = $fn_wo->save_wo_image_desc_m($woTaskUploads);
            $fn_general->save_audit('115', $jwt_data->userId, 'Work Order no. = '.$returnVal);
            $form_data['errmsg'] = $constant::SUC_SAVE;
        }
        else if ($action === 'return_by_technician') {
            $remark = filter_input(INPUT_POST, 'remark');
            $currentTask = $fn_wo->get_current_task('13', '13', '21', '18', '27');
            if ($currentTask['checkpointId'] === '13') {
                $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '20', $remark, '1');
                $returnVal = $fn_wo->return_by_technician($currentTask['transactionId']);
                $fn_general->save_audit('117', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo']);
                $fn_email->setup_email($returnVal['woTaskAssignedBy'], 6, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
                $fn_email->setup_mobile_notification($returnVal['woTaskAssignedBy'], 7, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
                $fn_noti_web->insert(1, $returnVal['woTaskAssignedBy'], $returnVal['woTaskNo']);
            }
            else if ($currentTask['checkpointId'] === '18') {
                $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '29', $remark, '2');
                $returnVal = $fn_wo->return_wr_by_technician($currentTask['transactionId']);
                $fn_general->save_audit('133', $jwt_data->userId, 'Work Request no. = '.$returnVal['woTaskNo']);
                $fn_email->setup_email($returnVal['woTaskAssignedBy'], 13, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
                $fn_email->setup_mobile_notification($returnVal['woTaskAssignedBy'], 14, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
                $fn_noti_web->insert(2, $returnVal['woTaskAssignedBy'], $returnVal['woTaskNo']);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter checkpointId invalid');
            }
            $form_data['errmsg'] = $constant::SUC_RETURNED;
        }
        else if ($action === 'submit_repair') {
            $signature = filter_input(INPUT_POST, 'signature', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $signatureId = $fn_general->uploadDocument($signature, 15, $jwt_data->userId);
            $currentTask = $fn_wo->get_current_task('13', '13', '21');
            $woType = $fn_wo->get_wo_task_type();
            $nextCheck = $woType==='2'||$woType==='6'?'2':'';
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '9', '', $nextCheck);
            $returnVal = $fn_wo->submit_repair($currentTask['transactionId'], $signatureId);
            $fn_wo->saveWoTaskDoneAssistant($woTaskId);
            $fn_general->save_audit('119', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo']);
            $receiver = $woType==='2'||$woType==='6' ? $returnVal['woTaskAssignedBy'] : $returnVal['woTaskCreatedBy'];
            $fn_email->setup_email($receiver, 7, array('task_no'=>$returnVal['woTaskNo']));
            $fn_email->setup_mobile_notification($receiver, 8, array('task_no'=>$returnVal['woTaskNo']));
            $fn_noti_web->insert(3, $receiver, $returnVal['woTaskNo']);
            $form_data['errmsg'] = $constant::SUC_SUBMITTED;
        }
        else if ($action === 'return_verify') {
            $remark = filter_input(INPUT_POST, 'remark');
            $woType = $fn_wo->get_wo_task_type();
            $currentCheckpoint = $woType==='2'?'16':'14';
            $currentTask = $fn_wo->get_current_task('15', $currentCheckpoint);
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '20', $remark, '1');
            $returnVal = $fn_wo->return_verify($currentTask['transactionId']);
            $fn_general->save_audit('120', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo']);
            $fn_email->setup_email($returnVal['woTaskReturnTo'], 8, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
            $fn_email->setup_mobile_notification($returnVal['woTaskReturnTo'], 9, array('task_no' => $returnVal['woTaskNo'], 'comment'=>$remark));
            $form_data['errmsg'] = $constant::SUC_RETURNED;
        }
        else if ($action === 'submit_verify') {
            $rating = filter_input(INPUT_POST, 'rating');
            $signature = filter_input(INPUT_POST, 'signature', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $signatureId = $fn_general->uploadDocument($signature, 16, $jwt_data->userId);
            $woType = $fn_wo->get_wo_task_type();
            $currentCheckpoint = $woType==='2'?'16':'14';
            $currentTask = $fn_wo->get_current_task('15', $currentCheckpoint);
            $newTaskId = $fn_task->submit_task($currentTask['taskId'], $jwt_data->userId, '9');
            $returnVal = $fn_wo->submit_verify($currentTask['transactionId'], $signatureId, $rating);
            $fn_general->save_audit('121', $jwt_data->userId, 'Work Order no. = '.$returnVal['woTaskNo']);
            $fn_email->setup_email($returnVal['woTaskTechnician'], 9, array('task_no'=>$returnVal['woTaskNo']));
            $fn_email->setup_mobile_notification($returnVal['woTaskTechnician'], 10, array('task_no'=>$returnVal['woTaskNo']));
            if ($woType === '6') {
                $woTaskPublic = $fn_wo->getWoTaskPublic($woTaskId);
                $fn_email->setup_email(1, 25, array('task_no' => $returnVal['woTaskNo']), false, $woTaskPublic['woTaskPublicName'], $woTaskPublic['woTaskPublicEmail']);
            }
            $form_data['errmsg'] = $constant::SUC_VERIFIED_AND_CLOSED;
        }
        else if ($action === 'save_wo_rate') {
            $rating = filter_input(INPUT_POST, 'rating');
            $returnVal = $fn_wo->save_wo_rate_m($rating);
            $fn_general->save_audit('123', $jwt_data->userId, 'Work Order no. = '.$returnVal.', rating = '.$rating);
            $form_data['errmsg'] = $constant::SUC_WO_SAVE_WO_RATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $action = filter_input(INPUT_GET, 'action');
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');
        $fn_wo->__set('woTaskId', $woTaskId);

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'delete_wo_repair_image') {
            $woTaskUploadId = filter_input(INPUT_GET, 'woTaskUploadId');
            $returnVal = $fn_wo->delete_wo_repair_image_m($woTaskUploadId);
            $fn_general->save_audit('116', $jwt_data->userId, 'Work Order no. = '.$returnVal);
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