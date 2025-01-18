<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_site.php';

$api_name = 'api_site';
$is_transaction = false;
$form_data = array('success' => false, 'result' => '', 'error' => '', 'errmsg' => '');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_site = new Class_site();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_site->__set('constant', $constant);
    $fn_site->__set('fn_general', $fn_general);

    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = ' . $request_method);

    $headers = apache_request_headers();
    $external = filter_input(INPUT_GET, 'external');
    $fn_general->log_debug('API', $api_name, __LINE__, '$external = ' . $external);
    if ($external !== '1') {
        if (!isset($headers['Authorization'])) {
            throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
        }
        $jwt_data = $fn_login->check_jwt($headers['Authorization']);
    }

    if ('GET' === $request_method) {
        $siteId = filter_input(INPUT_GET, 'siteId');
        if (!is_null($siteId)) {
            $result = $fn_site->get_site($siteId);
        } else {
            $result = $fn_site->get_site_list();
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    } else if ('POST' === $request_method) {
        $siteName = filter_input(INPUT_POST, 'siteName');
        $siteCode = filter_input(INPUT_POST, 'siteCode');
        $siteDesc = filter_input(INPUT_POST, 'siteDesc');
        $clientId = filter_input(INPUT_POST, 'clientId');
        $siteIsWr = filter_input(INPUT_POST, 'siteIsWr');
        $siteStatus = filter_input(INPUT_POST, 'siteStatus');

        $params = array(
            'siteName' => $siteName,
            'siteCode' => $siteCode,
            'siteDesc' => $siteDesc,
            'clientId' => $clientId,
            'siteIsWr' => $siteIsWr,
            'siteStatus' => $siteStatus
        );

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $result = $fn_site->add_site($params);
        $fn_general->updateVersion(6);
        $fn_general->updateVersion(32);
        $fn_general->save_audit('12', $jwt_data->userId, 'Site = ' . $siteName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_SITE_ADD;
        $form_data['result'] = $result;
        $form_data['success'] = true;
    } else if ('PUT' === $request_method) {
        $siteId = filter_input(INPUT_GET, 'siteId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_site->update_site($siteId, $put_vars);
            $fn_general->updateVersion(6);
            $fn_general->updateVersion(32);
            $fn_general->save_audit('13', $jwt_data->userId, 'Site = ' . $put_vars['siteName']);
            $form_data['errmsg'] = $constant::SUC_SITE_EDIT;
        } else if ($action === 'deactivate') {
            $siteName = $fn_site->deactivate_site($siteId);
            $fn_general->updateVersion(6);
            $fn_general->updateVersion(32);
            $fn_general->save_audit('14', $jwt_data->userId, 'Site = ' . $siteName);
            $form_data['errmsg'] = $constant::SUC_SITE_DEACTIVATE;
        } else if ($action === 'activate') {
            $siteName = $fn_site->activate_site($siteId);
            $fn_general->updateVersion(6);
            $fn_general->updateVersion(32);
            $fn_general->save_audit('15', $jwt_data->userId, 'Site = ' . $siteName);
            $form_data['errmsg'] = $constant::SUC_SITE_ACTIVATE;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid (' . $action . ')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    } else if ('DELETE' === $request_method) {
        $siteId = filter_input(INPUT_GET, 'siteId');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        $siteName = $fn_site->delete_site($siteId);
        $fn_general->updateVersion(6);
        $fn_general->updateVersion(32);
        $fn_general->save_audit('16', $jwt_data->userId, 'Site = ' . $siteName);

        Class_db::getInstance()->db_commit();
        $form_data['errmsg'] = $constant::SUC_SITE_DELETE;
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