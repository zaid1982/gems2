<?php
require_once 'library/constant.php';
require_once 'function/db.php';
require_once 'function/f_general.php';
require_once 'function/f_login.php';
require_once 'function/f_user.php';

$constant = new Class_constant();
$fn_general = new Class_general();
$fn_login = new Class_login();
$fn_user = new Class_user();
$api_name = 'api_profile';
$is_transaction = false;
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';

try {   
    Class_db::getInstance()->db_connect();
    $request_method = $_SERVER['REQUEST_METHOD'];
    //$request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    $fn_general->log_debug('API', $api_name, __LINE__, 'Request method = '.$request_method);

    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        throw new Exception('[' . __LINE__ . '] - Parameter Authorization empty');
    }
    $jwt_data = $fn_login->check_jwt($headers['Authorization']);

    if ('GET' === $request_method) {
        $userId = filter_input(INPUT_GET, 'userId');

        if (isset($headers['Reportid'])) {
            $reportId = $headers['Reportid'];
            if ($reportId === '1') {
                $result = $fn_user->get_user_by_role();
            } else {
                throw new Exception('[' . __LINE__ . '] - Parameter Reportid ('.$reportId.') invalid');
            }
        } else if (!is_null($userId)) {
            $result = $fn_user->get_user($userId);
        } else {
            $result = $fn_user->get_users();
        }

        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('POST' === $request_method) {
        $action = filter_input(INPUT_POST, 'action');
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;

        if ($action === 'add_user') {
            $userName = filter_input(INPUT_POST, 'userName');
            $userPassword = filter_input(INPUT_POST, 'userPassword');
            $userFullName = filter_input(INPUT_POST, 'userFirstName');
            $userContactNo = filter_input(INPUT_POST, 'userContactNo');
            $userEmail = filter_input(INPUT_POST, 'userEmail');
            $designationId = filter_input(INPUT_POST, 'designationId');
            $userType = filter_input(INPUT_POST, 'userType');
            $siteId = filter_input(INPUT_POST, 'siteId');
            $roles = filter_input(INPUT_POST, 'roles');

            $params = array(
                'userName'=>$userName,
                'userPassword'=>$userPassword,
                'userFirstName'=>$userFullName,
                'userContactNo'=>$userContactNo,
                'userEmail'=>$userEmail,
                'designationId'=>$designationId,
                'userType'=>$userType,
                'siteId'=>$siteId,
                'roles'=>$roles
            );
            $result = $fn_user->add_user($params);
            $fn_general->updateVersion(3);
            $fn_general->save_audit('17', $jwt_data->userId, 'User ID = ' . $result);
            $form_data['errmsg'] = $constant::SUC_USER_ADD;
        } else {
            throw new Exception('[' . __LINE__ . '] - Parameter action (' . $action . ') invalid');
        }

        Class_db::getInstance()->db_commit();
        $form_data['result'] = $result;
        $form_data['success'] = true;
    }
    else if ('PUT' === $request_method) {
        $userId = filter_input(INPUT_GET, 'userId'); 
        $put_data = file_get_contents("php://input");
        parse_str($put_data, $put_vars);
        $action = $put_vars['action'];
        
        if (empty($userId)) {
            throw new Exception('[' . __LINE__ . '] - Parameter userId empty');
        }        
        if (empty($action)) {
            throw new Exception('[' . __LINE__ . '] - Parameter action empty');
        } 
        
        Class_db::getInstance()->db_beginTransaction();
        $is_transaction = true;
        
        if ($action === 'profile') {
            $fn_user->update_profile($userId, $put_vars);  
            $fn_general->save_audit('5', $jwt_data->userId);
        } 
        else if ($action === 'password') {
            $fn_user->change_password($userId, $put_vars);  
            $fn_general->save_audit('6', $jwt_data->userId);
            $form_data['errmsg'] = $constant::SUC_CHANGE_PASSWORD;
        }
        else if ($action === 'update_user') {
            $fn_user->update_profile($userId, $put_vars);
            $fn_general->updateVersion(3);
            $fn_general->save_audit('18', $jwt_data->userId, 'User ID = ' . $userId);
            $form_data['errmsg'] = $constant::SUC_USER_UPDATE;
        }
        else if ($action === 'deactivate') {
            $fn_user->deactivate_profile($userId);
            $fn_general->updateVersion(3);
            $fn_general->save_audit('19', $jwt_data->userId, 'User ID = ' . $userId);
            $form_data['errmsg'] = $constant::SUC_USER_DEACTIVATE;
        }
        else if ($action === 'activate') {
            $fn_user->activate_profile($userId);
            $fn_general->updateVersion(3);
            $fn_general->save_audit('20', $jwt_data->userId, 'User ID = ' . $userId);
            $form_data['errmsg'] = $constant::SUC_USER_ACTIVATE;
        }
        
        Class_db::getInstance()->db_commit();        
        Class_db::getInstance()->db_close();
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