<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';

$api_name = 'api_m_diag';
$is_transaction = false;
$form_data = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';
date_default_timezone_set('Asia/Kuala_Lumpur');

const MOB_DIAG_MAX_BYTES = 307200;

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];

    $headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
    if (isset($headers['Authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    } else if (isset($headers['authorization'])) {
        $jwt_data = $fn_login->check_jwt($headers['authorization']);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }

    $userId = isset($jwt_data->userId) ? (int) $jwt_data->userId : 0;
    if ($userId <= 0) {
        throw new Exception('[' . __LINE__ . '] - Parameter userId invalid');
    }

    if ('POST' !== $request_method) {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }

    $action = filter_input(INPUT_POST, 'action');
    $db = Class_db::getInstance();
    $pdo = $db->DBH;
    if (!($pdo instanceof PDO)) {
        throw new Exception('[' . __LINE__ . '] - Database connection lost');
    }

    $deviceKey = isset($_POST['deviceKey']) ? trim((string) $_POST['deviceKey']) : '';
    if ($deviceKey !== '' && !preg_match('/^[A-Za-z0-9._:-]{8,64}$/', $deviceKey)) {
        throw new Exception('[' . __LINE__ . '] - Parameter deviceKey invalid', 31);
    }

    if ($action === 'status') {
        if ($deviceKey === '') {
            $stmt = $pdo->prepare(
                'SELECT diag_request_id FROM mob_diag_request
                 WHERE user_id = ? AND status = ? AND (device_key IS NULL OR device_key = ?)
                 LIMIT 1'
            );
            $stmt->execute(array($userId, 'pending', ''));
        } else {
            $stmt = $pdo->prepare(
                'SELECT diag_request_id FROM mob_diag_request
                 WHERE user_id = ? AND status = ? AND device_key = ?
                 LIMIT 1'
            );
            $stmt->execute(array($userId, 'pending', $deviceKey));
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = array('requested' => is_array($row));
    } else if ($action === 'upload') {
        $log = isset($_POST['log']) ? (string) $_POST['log'] : '';
        $log = str_replace("\0", '', $log);
        $bearer = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $log);
        if (is_string($bearer)) {
            $log = $bearer;
        }
        $password = preg_replace('/(password\s*=\s*)\S+/i', '$1[redacted]', $log);
        if (is_string($password)) {
            $log = $password;
        }
        if (trim($log) === '') {
            throw new Exception('[' . __LINE__ . '] - Parameter log empty');
        }
        if (strlen($log) > MOB_DIAG_MAX_BYTES) {
            $log = substr($log, -MOB_DIAG_MAX_BYTES);
        }

        $appVersion = filter_input(INPUT_POST, 'appVersion');
        $appVersion = is_string($appVersion) ? substr(trim($appVersion), 0, 40) : '';
        $onlineRaw = filter_input(INPUT_POST, 'online');
        $onlineFlag = ($onlineRaw === '1' || $onlineRaw === 'true') ? 1 : 0;

        $pdo->beginTransaction();
        $is_transaction = true;

        if ($deviceKey === '') {
            $pending = $pdo->prepare(
                'SELECT diag_request_id FROM mob_diag_request
                 WHERE user_id = ? AND status = ? AND (device_key IS NULL OR device_key = ?)
                 ORDER BY diag_request_id ASC
                 LIMIT 1'
            );
            $pending->execute(array($userId, 'pending', ''));
        } else {
            $pending = $pdo->prepare(
                'SELECT diag_request_id FROM mob_diag_request
                 WHERE user_id = ? AND status = ? AND device_key = ?
                 ORDER BY diag_request_id ASC
                 LIMIT 1'
            );
            $pending->execute(array($userId, 'pending', $deviceKey));
        }
        $request = $pending->fetch(PDO::FETCH_ASSOC);
        if (!is_array($request)) {
            throw new Exception('[' . __LINE__ . '] - No diagnostic request is waiting', 31);
        }
        $requestId = (int) $request['diag_request_id'];

        $insert = $pdo->prepare(
            'INSERT INTO mob_diag_log
                (diag_request_id, user_id, app_version, online_flag, log_text)
             VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute(array(
            $requestId,
            $userId,
            $appVersion !== '' ? $appVersion : null,
            $onlineFlag,
            $log,
        ));

        $finish = $pdo->prepare(
            'UPDATE mob_diag_request
             SET status = ?, fulfilled_at = NOW()
             WHERE diag_request_id = ? AND user_id = ? AND status = ?'
        );
        $finish->execute(array('received', $requestId, $userId, 'pending'));

        $pdo->commit();
        $is_transaction = false;
        $result = array('received' => true);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter action (' . $action . ') invalid');
    }

    $form_data['result'] = $result;
    $form_data['success'] = true;
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
    if ($is_transaction) {
        try {
            Class_db::getInstance()->db_rollback();
        } catch (Exception $rollbackEx) {
            $fn_general->log_error('API-M', $api_name, __LINE__, $rollbackEx->getMessage());
        }
    }
    try {
        Class_db::getInstance()->db_close();
    } catch (Exception $closeEx) {
        $fn_general->log_error('API-M', $api_name, __LINE__, $closeEx->getMessage());
    }
    $form_data['error'] = substr($ex->getMessage(), strpos($ex->getMessage(), '] - ') + 4);
    if ($ex->getCode() === 31) {
        $form_data['errmsg'] = $form_data['error'];
    } else {
        $form_data['errmsg'] = $constant::ERR_DEFAULT;
    }
    $fn_general->log_error('API-M', $api_name, __LINE__, $ex->getMessage());
}

echo json_encode($form_data);
