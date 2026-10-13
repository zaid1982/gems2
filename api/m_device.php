<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';

$api_name = 'api_m_device';
$form_data = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';
date_default_timezone_set('Asia/Kuala_Lumpur');

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
        throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
    }

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

    $action = filter_input(INPUT_POST, 'action');
    $deviceKey = isset($_POST['deviceKey']) ? trim((string) $_POST['deviceKey']) : '';
    $pdo = Class_db::getInstance()->DBH;
    if (!($pdo instanceof PDO)) {
        throw new Exception('[' . __LINE__ . '] - Database connection lost');
    }

    if ($action === 'list') {
        $result = device_rows($pdo, $userId);
    } else if ($action === 'check') {
        device_assert_key($deviceKey);
        $stmt = $pdo->prepare(
            'SELECT status FROM mob_user_device WHERE user_id = ? AND device_key = ? LIMIT 1'
        );
        $stmt->execute(array($userId, $deviceKey));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $status = is_array($row) ? (string) $row['status'] : 'unknown';
        $result = array('status' => $status);
    } else if ($action === 'register') {
        device_assert_key($deviceKey);
        $platform = device_clip(isset($_POST['platform']) ? (string) $_POST['platform'] : 'unknown', 20);
        $label = device_clip(isset($_POST['label']) ? (string) $_POST['label'] : 'Device', 120);
        $appVersion = device_clip(isset($_POST['appVersion']) ? (string) $_POST['appVersion'] : '', 40);
        if ($label === '') {
            $label = 'Device';
        }
        $stmt = $pdo->prepare(
            'INSERT INTO mob_user_device
                (user_id, device_key, platform, device_label, app_version, last_seen_at, status, removed_at)
             VALUES (?, ?, ?, ?, ?, NOW(), ?, NULL)
             ON DUPLICATE KEY UPDATE
                platform = VALUES(platform),
                device_label = VALUES(device_label),
                app_version = VALUES(app_version),
                last_seen_at = NOW(),
                status = ?,
                removed_at = NULL'
        );
        $stmt->execute(array(
            $userId,
            $deviceKey,
            $platform !== '' ? $platform : 'unknown',
            $label,
            $appVersion !== '' ? $appVersion : null,
            'active',
            'active',
        ));
        $result = array('status' => 'active');
    } else if ($action === 'remove') {
        device_assert_key($deviceKey);
        $stmt = $pdo->prepare(
            'UPDATE mob_user_device
             SET status = ?, removed_at = NOW()
             WHERE user_id = ? AND device_key = ? AND status = ?'
        );
        $stmt->execute(array('removed', $userId, $deviceKey, 'active'));
        if ($stmt->rowCount() < 1) {
            throw new Exception('[' . __LINE__ . '] - Device was not found', 31);
        }
        $result = array('removed' => true);
    } else {
        throw new Exception('[' . __LINE__ . '] - Parameter action invalid');
    }

    $form_data['result'] = $result;
    $form_data['success'] = true;
    Class_db::getInstance()->db_close();
} catch (Exception $ex) {
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

function device_assert_key($deviceKey)
{
    if (!is_string($deviceKey) || !preg_match('/^[A-Za-z0-9._:-]{8,64}$/', $deviceKey)) {
        throw new Exception('[' . __LINE__ . '] - Parameter deviceKey invalid', 31);
    }
}

function device_clip($value, $max)
{
    $value = trim(str_replace(array("\r", "\n", "\0"), ' ', $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function device_rows(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare(
        'SELECT device_key, platform, device_label, app_version, last_seen_at
         FROM mob_user_device
         WHERE user_id = ? AND status = ?
         ORDER BY last_seen_at DESC'
    );
    $stmt->execute(array($userId, 'active'));
    $rows = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = array(
            'deviceKey' => $row['device_key'],
            'platform' => $row['platform'],
            'label' => $row['device_label'],
            'appVersion' => $row['app_version'],
            'lastSeenAt' => $row['last_seen_at'],
        );
    }
    return $rows;
}
