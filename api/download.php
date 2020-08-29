<?php
require_once 'function/db.php';
require_once 'function/f_general.php';

function download_blob($file, $blob) {    
    file_put_contents($file, base64_decode($blob));
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        //unlink($file);
    }
}

function download($file) {
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        //unlink($file);
    }
}

$request_method = $_SERVER['REQUEST_METHOD'];
if ('GET' === $request_method) {    // get aduan details
    $docId = filter_input(INPUT_GET, 'docId');
    if (!empty($docId)) {
        Class_db::getInstance()->db_connect();
        $result = Class_db::getInstance()->db_select_single('sys_upload', array('upload_id'=>$docId));
        if (!empty($result)) {
            if ($result['upload_blob_data'] !== null) {
                download_blob($result['upload_uplname'], $result['upload_blob_data']);
            } else {
                download('upload/17/2/f_12336.png');
                download('upload/17/2/f_12337.png');
                //download($result['upload_folder'].'/'.$result['upload_filename'].'.'.$result['upload_extension']);
            }
        }
        Class_db::getInstance()->db_close();
    }
}
exit;

?>