<?php
$request_method = $_SERVER['REQUEST_METHOD'];
$form_data = array('success'=>true, 'result'=>'', 'error'=>'', 'errmsg'=>'');

if ('POST' === $request_method) {
    $action = filter_input(INPUT_POST, 'action');
    $form_data['result'] = $action;
}
echo json_encode($form_data);