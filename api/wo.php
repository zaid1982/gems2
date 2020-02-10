<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_wo.php';
require_once 'pdf/tcpdf_include.php';
require_once 'pdf/wo.php';
require_once 'pdf/wr.php';

$api_name = 'api_asset';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_wo = new Class_wo();
$fn_pdf_wo = new Class_pdf_wo();
$fn_pdf_wr = new Class_pdf_wr();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_wo->__set('constant', $constant);
    $fn_wo->__set('fn_general', $fn_general);
    $fn_pdf_wo->__set('fn_general', $fn_general);
    $fn_pdf_wr->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $type = filter_input(INPUT_GET, 'type');
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');
        if (!is_null($type)) {
            if ($type === 'dashboard_list') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_task_dashboard_list($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_site_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_site_status($clientId, $year, $month);
            }
            else if ($type === 'total_by_site_type') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_site_type($clientId, $year, $month);
            }
            else if ($type === 'total_by_type') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_type($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_status') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_status($clientId, $siteId, $year, $month);
            }
            else if ($type === 'total_by_group') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_total_wo_by_group($clientId, $siteId, $year, $month);
            }
            else if ($type === 'top5_execute') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_top5_execute($clientId, $siteId, $year, $month);
            }
            else if ($type === 'bottom5_execute') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_bottom5_execute($clientId, $siteId, $year, $month);
            }
            else if ($type === 'average_execute_by_trade') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_average_execute_by_trade($clientId, $siteId, $year, $month);
            }
            else if ($type === 'report_wo_summary') {
                $clientId = filter_input(INPUT_GET, 'clientId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_report_wo_summary($clientId, $year, $month);
            }
            else if ($type === 'report_wo_pending_list') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_wo_task_dashboard_list('', $siteId, $year, $month, true);
            }
            else if ($type === 'report_wo_total') {
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $result = $fn_wo->get_report_wo_total($year, $month);
            }
            else if ($type === 'report_wo_daily') {
                $siteId = filter_input(INPUT_GET, 'siteId');
                $year = filter_input(INPUT_GET, 'year');
                $month = filter_input(INPUT_GET, 'month');
                $isManual = filter_input(INPUT_GET, 'isManual');
                $result = $fn_wo->get_report_wo_daily($siteId, $isManual, $year, $month);
            }
            else if ($type === 'wo_by_transaction') {
                $transactionId = filter_input(INPUT_GET, 'transactionId');
                $result = $fn_wo->get_wo_task($transactionId);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
            }
        } else if (!is_null($woTaskId)) {
            $result = $fn_wo->get_wo_task();
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter get invalid');
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'insert_site_manual') {
            $siteId = filter_input(INPUT_POST, 'siteId');
            $siteName = filter_input(INPUT_POST, 'siteName');
            $selectedDate = filter_input(INPUT_POST, 'selectedDate');
            $selectedMonth = filter_input(INPUT_POST, 'selectedMonth');
            $selectedYear = filter_input(INPUT_POST, 'selectedYear');
            $open0 = filter_input(INPUT_POST, 'open0');
            $closed0 = filter_input(INPUT_POST, 'closed0');
            $open1 = filter_input(INPUT_POST, 'open1');
            $closed1 = filter_input(INPUT_POST, 'closed1');
            $open2 = filter_input(INPUT_POST, 'open2');
            $closed2 = filter_input(INPUT_POST, 'closed2');
            $open3 = filter_input(INPUT_POST, 'open3');
            $closed3 = filter_input(INPUT_POST, 'closed3');
            $open4 = filter_input(INPUT_POST, 'open4');
            $closed4 = filter_input(INPUT_POST, 'closed4');
            $open5 = filter_input(INPUT_POST, 'open5');
            $closed5 = filter_input(INPUT_POST, 'closed5');

            $params = array(
                'siteId'=>$siteId,
                'selectedDate'=>$selectedDate,
                'selectedMonth'=>$selectedMonth,
                'selectedYear'=>$selectedYear,
                'open0'=>$open0,
                'closed0'=>$closed0,
                'open1'=>$open1,
                'closed1'=>$closed1,
                'open2'=>$open2,
                'closed2'=>$closed2,
                'open3'=>$open3,
                'closed3'=>$closed3,
                'open4'=>$open4,
                'closed4'=>$closed4,
                'open5'=>$open5,
                'closed5'=>$closed5
            );

            $result = $fn_wo->add_siteManual($params);
            $fn_general->save_audit('125', $jwt_data->userId, 'Site = '.$siteName.', date = '.$selectedDate.'/'.$selectedMonth.'/'.$selectedYear);
            $form_data['errmsg'] = $constant::SUC_WO_MANUAL_REPORT_ADD;
        }
        else if ($action === 'generate_pdf') {
            $woTaskId = filter_input(INPUT_POST, 'woTaskId');
            $fn_pdf_wo->__set('woTaskId', $woTaskId);
            $result = $fn_pdf_wo->create_pdf();
        }
        else if ($action === 'generate_pdf_wr') {
            $woTaskId = filter_input(INPUT_POST, 'woTaskId');
            $fn_pdf_wr->__set('woTaskId', $woTaskId);
            $result = $fn_pdf_wr->create_pdf();
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update_site_manual') {
            $siteManualId = filter_input(INPUT_GET, 'siteManualId');
            $fn_wo->update_siteManual($siteManualId, $put_vars);
            $fn_general->save_audit('126', $jwt_data->userId, 'Site = '.$put_vars['siteName'].', date = '.$put_vars['selectedDate'].'/'.$put_vars['selectedMonth'].'/'.$put_vars['selectedYear']);
            $form_data['errmsg'] = $constant::SUC_WO_MANUAL_REPORT_EDIT;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $woTaskId = filter_input(INPUT_GET, 'woTaskId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $woTaskNo = $fn_wo->delete_wo($woTaskId);
        $fn_general->save_audit('124', $jwt_data->userId, 'WO Task No. = ' . $woTaskNo);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_WO_DELETE;
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