<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_gamification.php';

$api_name = 'api_gamification';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");
$userId = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_gamification = new Class_gamification();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_gamification->__set('constant', $constant);
    $fn_gamification->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $urlArr = explode('/', $_SERVER['REQUEST_URI']);
    foreach ($urlArr as $i=>$param) {
        if ($param === 'gamification') {
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
        if (!isset ($urlArr[1])) {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }

        if ($urlArr[1] === 'gmi_monthly') {
            if (isset ($urlArr[3])) {
                $result = $fn_gamification->getGmiMonthlyList($urlArr[2], $urlArr[3]);
            } else {
                $result = $fn_gamification->getGmiMonthly($urlArr[2]);
            }
        } else if ($urlArr[1] === 'gmi_monthly_top_5') {
            $result = $fn_gamification->getGmiMonthlyTop5($urlArr[2], $urlArr[3]);
        } else if ($urlArr[1] === 'gmi_monthly_top_5_m') {
            $result = $fn_gamification->getGmiMonthlyTop5M($urlArr[2], $urlArr[3]);
        } else if ($urlArr[1] === 'gmi_monthly_top_5_project_m') {
            $result = $fn_gamification->getGmiMonthlyTop5ProjectM($urlArr[2], $urlArr[3]);
        } else if ($urlArr[1] === 'gmi_monthly_history') {
            $result = $fn_gamification->getGmiMonthlyHistory($urlArr[2], $urlArr[3], $urlArr[4]);
        } else if ($urlArr[1] === 'current_score') {
            $now = new DateTime();
            $year = intval($now->format('Y'));
            $month = intval($now->format('n'));
            $fn_gamification->runMonthly($year, $month);
            $result = $fn_gamification->getCurrentScore($userId, $year, $month);
        } else {
            throw new Exception('[' . __LINE__ . '] - Wrong Request Method');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $putData = file_get_contents("php://input");
        $params = array();
        parse_str($putData, $params);

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($urlArr[1] === 'run_monthly') {
            if (!isset ($urlArr[2])) {
                throw new Exception('[' . __LINE__ . '] - Parameter year null');
            } else if (!isset ($urlArr[3])) {
                throw new Exception('[' . __LINE__ . '] - Parameter month null');
            }
            $fn_gamification->runMonthly($urlArr[2], $urlArr[3]);
            $arrMonth = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
            $fn_general->save_audit('183', $userId, 'Month = '.$arrMonth[intval($urlArr[3])].', '.$urlArr[2]);
            $form_data['errmsg'] = 'Leaderboard Points successfully update for month '.$arrMonth[intval($urlArr[3])].', '.$urlArr[2];
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid (' . $urlArr[1] . ')');
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