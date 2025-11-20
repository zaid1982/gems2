<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo_request.php';
require_once 'function/f_wo.php';
require_once 'function/f_task.php';
require_once 'function/f_email.php';

$api_name = 'api_wo_request';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo_request = new Class_wo_request();
$fn_wo = new Class_wo();
$fn_task = new Class_task();
$fn_email = new Class_email();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo_request->__set('fn_general', $fn_general);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_task->__set('constant', $constant);
    $fn_task->__set('fn_general', $fn_general);
    $fn_email->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'wo_request' || $param === 'wo_request.php') {
            if ($param === 'wo_request.php') {
                $urlArr[$i] = 'wo_request';
            }
            break;
        }
        array_shift($urlArr);
    }

    foreach ($urlArr as $index => $segment) {
        if (strpos($segment, '?') !== false) {
            $urlArr[$index] = explode('?', $segment, 2)[0];
        }
    }

    if (isset($urlArr[1]) && $urlArr[1] === 'external') {
        array_shift($urlArr);
    } else {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $jwt_data = $fn_login->check_jwt($headers['Authorization']);
        } else if (isset($headers['authorization'])) {
            $jwt_data = $fn_login->check_jwt($headers['authorization']);
            if (!isset($headers['deviceid'])) {
                throw new Exception('[' . __LINE__ . '] - Parameter Deviceid empty');
            }
            $fn_login->check_device_id($jwt_data->userId, $headers['deviceid']);
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
        }
        $userId = $jwt_data->userId;
    }

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'pending_task') {
                $searchText = isset ($urlArr[2]) ? $urlArr[2] : '';
                $result = $fn_wo_request->getPendingTask($userId, $searchText);
            } else if ($urlArr[1] === 'request_details') {
                $result = $fn_wo_request->getWoRequestDetails($urlArr[2]);
            } else if ($urlArr[1] === 'list_mobile_check_out') {
                $result = $fn_wo_request->getCheckOutMobileList($userId);
            } else if ($urlArr[1] === 'list_mobile_return') {
                $result = $fn_wo_request->getReturnMobileList($userId);
            } else if ($urlArr[1] === 'list_return_verification') {
                $siteId = '';
                if (isset($urlArr[2]) && $urlArr[2] === 'site') {
                    if (!isset($urlArr[3])) {
                        throw new Exception('[' . __LINE__ . '] - Parameter siteId empty');
                    }
                    $siteId = $urlArr[3];
                }
                $includeDetail = isset($_GET['detail']) && $_GET['detail'] === '1';
                $result = $fn_wo_request->listReturnVerification($userId, $siteId, $includeDetail);
            } else {
                $result = $fn_wo_request->getWoRequest($urlArr[1]);
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $rawBody = file_get_contents("php://input");
        $param = $_POST;
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        if ($urlArr[1] === 'return_parts') {
            $payload = !empty($param) ? $param : json_decode($rawBody, true);
            if (!is_array($payload) || !isset($payload['items'])) {
                throw new Exception('[' . __LINE__ . '] - Invalid return payload');
            }

            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $result = $fn_wo_request->returnCollectedParts($userId, $payload['items']);
            Class_db::getInstance()->db_commit();

            $fn_general->save_audit('190', $userId, 'Parts returned to store (total '.$result['totalReturned'].')');
            $form_data['result'] = $result;
            $form_data['errmsg'] = 'Items successfully returned to store';
        }
        else if ($urlArr[1] === 'verify_return') {
            $payload = !empty($param) ? $param : json_decode($rawBody, true);
            if (!is_array($payload)) {
                throw new Exception('[' . __LINE__ . '] - Invalid verification payload');
            }

            $returnId = isset($payload['returnTicketId']) ? $payload['returnTicketId'] : '';
            $action = isset($payload['action']) ? $payload['action'] : '';
            $partSubIds = isset($payload['partSubIds']) ? $payload['partSubIds'] : array();
            $remark = isset($payload['remark']) ? $payload['remark'] : '';

            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $result = $fn_wo_request->verifyReturnTicket($userId, $returnId, $action, $partSubIds, $remark);
            Class_db::getInstance()->db_commit();

            $fn_general->save_audit('191', $userId, 'Return ticket '.$result['returnTicketId'].' '.$result['action']);
            $form_data['result'] = $result;
            $form_data['errmsg'] = 'Return verification updated';
        }
        else if ($urlArr[1] === 'reset') {
            $fn_wo_request->resetRequest($urlArr[2], $userId);

            // ********** audit trail ********** \\
            $fn_wo->__set('woTaskId', $urlArr[2]);
            $wo = $fn_wo->get_wo_task();
            $fn_general->save_audit('179', $userId, 'Work Order No. = '.$wo['woTaskNo']);
            $form_data['errmsg'] = 'Request Parts successfully reset for reapply / resume';
        }
        else {
            $woTaskId = $urlArr[1];
            $checkResult = $fn_wo_request->checkRequestTask('submit_request', $userId, '', '', '', $woTaskId);
            if ($checkResult === 'noPart') {
                $form_data['errmsg'] = 'This work order has been set to No Material/Part Requested.';
            } else {
                // ********** submit request ********** \\
                Class_db::getInstance()->db_beginTransaction();
                $is_transaction = true;
                $woTaskRequestNo = $fn_wo_request->createRequestNo($userId);
                $taskId = $fn_task->create_new_task('4', $userId, '8', '1', $woTaskRequestNo);
                $fn_task->submit_task($taskId, $userId);
                $transactionId = $fn_task->getTransactionId($taskId);
                $fn_wo_request->submitRequest($woTaskId, $transactionId, $woTaskRequestNo);
                Class_db::getInstance()->db_commit();

                // ********** email & notification ********** \\
                $fn_wo->__set('woTaskId', $woTaskId);
                $wo = $fn_wo->get_wo_task();
                $nextUsers = $fn_task->get_checkpoints_users('17', '42', $woTaskId);
                Class_db::getInstance()->db_beginTransaction();
                foreach ($nextUsers as $nextUser) {
                    $fn_email->setup_email($nextUser, 16, array('request_no'=>$woTaskRequestNo, 'wo_no'=>$wo['woTaskNo']));
                    $fn_email->setup_mobile_notification($nextUser, 17, array('task_no'=>$woTaskRequestNo));
                }
                Class_db::getInstance()->db_commit();

                // ********** audit trail ********** \\
                $fn_general->save_audit('171', $userId, 'Work Order No. = '.$wo['woTaskNo'].', Part Request No. = '.$woTaskRequestNo);
                $form_data['errmsg'] = 'Request Parts successfully submitted for approval';
            }
        }

        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        if (!isset ($urlArr[2])) {
            throw new Exception('[' . __LINE__ . '] - woTaskRequestId empty');
        }

        $woTaskRequestId = $urlArr[2];
        $currentTask = $fn_wo_request->getCurrentTask($woTaskRequestId);
        $transactionId = $currentTask['transactionId'];
        $taskId = $currentTask['taskId'];
        $fn_wo_request->checkRequestTask($urlArr[1], $userId, $woTaskRequestId, $transactionId, $taskId, '', ($urlArr[1] === 'reject_request' ? $params['comment'] : ''));
        $woTaskRequest = $fn_wo_request->getWoRequest($woTaskRequestId);
        $woTaskId = $woTaskRequest['woTaskId'];
        $fn_wo->__set('woTaskId', $woTaskId);
        $wo = $fn_wo->get_wo_task();

        if ($urlArr[1] === 'approve_request') {
            // ********** approve request ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $fn_task->submit_task($taskId, $userId, '49');
            $fn_wo_request->submitApprove($woTaskRequestId, $transactionId);
            Class_db::getInstance()->db_commit();

            // ********** email & notification ********** \\
            $nextUsers = $fn_task->get_checkpoints_users('16', '43', $woTaskId);
            Class_db::getInstance()->db_beginTransaction();
            foreach ($nextUsers as $nextUser) {
                $fn_email->setup_email($nextUser, 17, array('request_no'=>$woTaskRequest['woTaskRequestNo'], 'wo_no'=>$wo['woTaskNo']));
                $fn_email->setup_mobile_notification($nextUser, 18, array('task_no'=>$woTaskRequest['woTaskRequestNo']));
            }
            Class_db::getInstance()->db_commit();

            // ********** audit trail ********** \\
            $fn_general->save_audit('172', $userId, 'Work Order No. = '.$wo['woTaskNo'].', Part Request No. = '.$woTaskRequest['woTaskRequestNo']);
            $form_data['errmsg'] = 'Request Parts successfully approved';
        } else if ($urlArr[1] === 'reject_request') {
            // ********** reject request ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $fn_task->submit_task($taskId, $userId, '50', $params['comment'], '1');
            $fn_wo_request->submitReject($woTaskRequestId, $transactionId, $params['comment']);
            Class_db::getInstance()->db_commit();

            // ********** email & notification ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $fn_email->setup_email($woTaskRequest['woTaskRequestOrderBy'], 20, array('request_no'=>$woTaskRequest['woTaskRequestNo'], 'wo_no'=>$wo['woTaskNo'], 'comment'=>$params['comment']));
            $fn_email->setup_mobile_notification($woTaskRequest['woTaskRequestOrderBy'], 21, array('task_no'=>$woTaskRequest['woTaskRequestNo'], 'comment'=>$params['comment']));
            Class_db::getInstance()->db_commit();

            // ********** audit trail ********** \\
            $fn_general->save_audit('173', $userId, 'Work Order No. = '.$wo['woTaskNo'].', Part Request No. = '.$woTaskRequest['woTaskRequestNo']);
            $form_data['errmsg'] = 'Request Parts successfully rejected';
        } else if ($urlArr[1] === 'reserve_request') {
            // ********** reserve request ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $fn_task->submit_task($taskId, $userId, '51', '', '1');
            $fn_wo_request->submitReserve($woTaskRequestId, $transactionId, $wo['woTaskNo'], $woTaskRequest['woTaskRequestNo']);
            Class_db::getInstance()->db_commit();

            // ********** email & notification ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $fn_email->setup_email($woTaskRequest['woTaskRequestOrderBy'], 18, array('request_no'=>$woTaskRequest['woTaskRequestNo'], 'wo_no'=>$wo['woTaskNo']));
            $fn_email->setup_mobile_notification($woTaskRequest['woTaskRequestOrderBy'], 19, array('task_no'=>$woTaskRequest['woTaskRequestNo']));
            Class_db::getInstance()->db_commit();

            // ********** audit trail ********** \\
            $fn_general->save_audit('174', $userId, 'Work Order No. = '.$wo['woTaskNo'].', Part Request No. = '.$woTaskRequest['woTaskRequestNo']);
            $form_data['errmsg'] = 'Request Parts successfully reserved and technician is being notified to collect the parts';
        } else if ($urlArr[1] === 'check_out_request') {
            // ********** check out request ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $is_transaction = true;
            $fn_task->submit_task($taskId, $userId, '36');
            $fn_wo_request->submitCheckOutRequest($woTaskRequestId, $transactionId, $userId);
            Class_db::getInstance()->db_commit();

            // ********** email & notification ********** \\
            Class_db::getInstance()->db_beginTransaction();
            $fn_email->setup_email($woTaskRequest['woTaskRequestOrderBy'], 19, array('request_no'=>$woTaskRequest['woTaskRequestNo'], 'wo_no'=>$wo['woTaskNo']));
            $fn_email->setup_mobile_notification($woTaskRequest['woTaskRequestOrderBy'], 20, array('task_no'=>$woTaskRequest['woTaskRequestNo'], 'wo_no'=>$wo['woTaskNo']));
            Class_db::getInstance()->db_commit();

            // ********** audit trail ********** \\
            $fn_general->save_audit('175', $userId, 'Work Order No. = '.$wo['woTaskNo'].', Part Request No. = '.$woTaskRequest['woTaskRequestNo']);
            $form_data['errmsg'] = 'Request Parts successfully collected';
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

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

