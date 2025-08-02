<?php

require_once 'api/library/constant.php';
require_once 'api/function/db.php';
require_once 'api/function/f_general.php';
require_once 'api/function/f_gamification.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$api_name = 'get_wo_details_for_gamification';
$form_data = array('success'=>false, 'result'=>'', 'error'=>'', 'errmsg'=>'');
$result = '';
date_default_timezone_set("Asia/Kuala_Lumpur");

try {
    // Initialize classes like the working API
    $constant = new Class_constant();
    $fn_general = new Class_general();
    $fn_gamification = new Class_gamification();

    // Set dependencies like the working API
    $fn_general->__set('constant', $constant);
    $fn_gamification->__set('constant', $constant);
    $fn_gamification->__set('fn_general', $fn_general);

    // Connect to database like the working API
    Class_db::getInstance()->db_connect();
    
    // Get parameters from URL
    $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
    $month = isset($_GET['month']) ? intval($_GET['month']) : 0;
    $userId = isset($_GET['userId']) ? $_GET['userId'] : '';

    if (!$year || !$month || !$userId) {
        throw new Exception('Missing required parameters: year, month, userId');
    }

    // Use the corrected gamification method
    $woDetails = $fn_gamification->getWoDetailsForGamification($year, $month, $userId);
    
    // Return success response like working API
    $form_data['success'] = true;
    $form_data['result'] = $woDetails;
    echo json_encode($form_data);
    
} catch (Exception $e) {
    $fn_general->log_error('API', $api_name, __LINE__, $e->getMessage());
    $form_data['success'] = false;
    $form_data['error'] = $e->getMessage();
    $form_data['errmsg'] = 'Error on system. Please contact Administrator!';
    
    http_response_code(500);
    echo json_encode($form_data);
}
?>
