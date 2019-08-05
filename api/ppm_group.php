<?php

require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_ppm_group.php';

$api_name = 'api_ppm_group';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_ppmGroup = new Class_ppmGroup();

try {
    $fn_general->__set('constant', $constant);
    $fn_login->__set('constant', $constant);
    $fn_login->__set('fn_general', $fn_general);
    $fn_ppmGroup->__set('constant', $constant);
    $fn_ppmGroup->__set('fn_general', $fn_general);

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
        $ppmGroupId = filter_input(INPUT_GET, 'ppmGroupId');
        if (!is_null($type)) {
            if ($type === 'ppm_group_user') {
                $result = $fn_ppmGroup->get_ppmGroupUser_list($ppmGroupId);
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter type invalid');
            }
        } else if (!is_null($ppmGroupId)) {
            $result = $fn_ppmGroup->get_ppmGroup($ppmGroupId);
        } else {
            $siteId = filter_input(INPUT_GET, 'siteId');
            $roleId = filter_input(INPUT_GET, 'roleId');
            if (!is_null($siteId)) {
                $result = $fn_ppmGroup->get_ppmGroup_list_filtered($siteId, $roleId);
            } else {
                $result = $fn_ppmGroup->get_ppmGroup_list();
            }
        }
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if (!is_null($action)) {
            if ($action === 'add_ppm_group_user') {
                $ppmGroupId = filter_input(INPUT_POST, 'ppmGroupId');
                $userId = filter_input(INPUT_POST, 'userId');
                $result = $fn_ppmGroup->add_ppmGroupUser($ppmGroupId, $userId);
                $fn_general->save_audit('108', $jwt_data->userId, 'PPM Group Id = ' . $ppmGroupId . ', User Id = ' . $userId);
                $form_data['errmsg'] = $constant::SUC_PPM_GROUP_USER_ADD;
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter type invalid');
            }
        } else {
            $siteId = filter_input(INPUT_POST, 'siteId');
            $roleId = filter_input(INPUT_POST, 'roleId');
            $ppmGroupName = filter_input(INPUT_POST, 'ppmGroupName');
            $reportTo = filter_input(INPUT_POST, 'reportTo');

            $params = array(
                'siteId' => $siteId,
                'roleId' => $roleId,
                'ppmGroupName' => $ppmGroupName,
                'reportTo' => $reportTo
            );

            $result = $fn_ppmGroup->add_ppmGroup($params);
            $fn_general->updateVersion(19);
            $fn_general->save_audit('105', $jwt_data->userId, 'PPM Group = ' . $ppmGroupName);
            $form_data['errmsg'] = $constant::SUC_PPM_GROUP_ADD;
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $ppmGroupId = filter_input(INPUT_GET, 'ppmGroupId');
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'update') {
            $fn_ppmGroup->update_ppmGroup($ppmGroupId, $put_vars);
            $fn_general->updateVersion(19);
            $fn_general->save_audit('106', $jwt_data->userId, 'PPM Group = ' . $put_vars['ppmGroupName']);
            $form_data['errmsg'] = $constant::SUC_PPM_GROUP_EDIT;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action invalid ('.$action.')');
        }

        Class_db::getInstance()->db_commit();
        $form_data['success'] = true;
    }
    else if ('DELETE' === $request_method) {
        $action = filter_input(INPUT_GET, 'action');

        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'delete_ppm_group') {
            $ppmGroupId = filter_input(INPUT_GET, 'ppmGroupId');
            $ppmGroupName = $fn_ppmGroup->delete_ppmGroup($ppmGroupId);
            $fn_general->updateVersion(19);
            $fn_general->save_audit('107', $jwt_data->userId, 'PPM Group = ' . $ppmGroupName);
            $form_data['errmsg'] = $constant::SUC_PPM_GROUP_DELETE;
        }
        else if ($action === 'delete_ppm_group_user') {
            $ppmGroupUserId = filter_input(INPUT_GET, 'ppmGroupUserId');
            $result = $fn_ppmGroup->delete_ppmGroupUser($ppmGroupUserId);
            $fn_general->save_audit('109', $jwt_data->userId, 'PPM Group = ' . $result['ppmGroupName'] . ', User = ' . $result['userFirstName']);
            $form_data['errmsg'] = $constant::SUC_PPM_GROUP_USER_DELETE;
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