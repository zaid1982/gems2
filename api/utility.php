<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_utility.php';

$api_name = 'api_utility';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_utility = new Class_utility();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_utility->__set('fn_general', $fn_general);
    $fn_utility->__set('constant', $constant);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'utility') {
            break;
        }
        array_shift($urlArr);
    }

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

    if ('GET' === $request_method) {
        if (isset ($urlArr[1])) {
            if ($urlArr[1] === 'list_utility_mobile') {
                $type = isset ($urlArr[2]) ? $urlArr[2] : '';
                $readingType = isset ($urlArr[3]) ? $urlArr[3] : '';
                $meterId = isset ($urlArr[4]) ? $urlArr[4] : '';
                $result = $fn_utility->getUtilityMobileList($userId, $type, $readingType, $meterId);
            }  else if ($urlArr[1] === 'data_monthly_analyzed') {
                if ($urlArr[2] === 'Electricity') {
                    $result = $fn_utility->getUtilityMonthlyElectricityAnalyzed($userId);
                } else if ($urlArr[2] === 'Water') {
                    $result = $fn_utility->getUtilityMonthlyWaterAnalyzed($userId);
                }
            }  else if ($urlArr[1] === 'data_daily_analyzed') {
                if ($urlArr[2] === 'Electricity') {
                    $result = $fn_utility->getUtilityDailyElectricityAnalyzed($urlArr[3], $urlArr[4], $urlArr[5]);
                } else if ($urlArr[2] === 'Water') {
                    $result = $fn_utility->getUtilityDailyWaterAnalyzed($urlArr[3], $urlArr[4], $urlArr[5]);
                }
            }
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $params = $_POST;
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        if (!isset ($urlArr[2])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        // ********** submit reading ********** \\
        if (!isset ($params['readingImage'])) {
            throw new Exception('[' . __LINE__ . '] - Invalid Reading Image');
        }
        $uploadId = $fn_general->uploadDocument($params['readingImage'], 24, $userId);
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        $result = $fn_utility->addUtility($urlArr[1], $urlArr[2], $userId, $params, $uploadId);

        // ********** audit trail ********** \\
        $fn_general->save_audit('177', $userId, 'Type = ' . $urlArr[1] . ', Reading Type = ' . $urlArr[2] . ', Reading Date = ' . $params['utilityDate']);
        $form_data['errmsg'] = 'Utility reading successfully recorded.';
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




