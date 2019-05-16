<?php
require_once 'function/db.php';

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
        unlink($file);
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
        unlink($file);
    }
}

$request_method = $_SERVER['REQUEST_METHOD'];
if ('GET' === $request_method) {    // get aduan details 
    $type = filter_input(INPUT_GET, 't');
    $docId = filter_input(INPUT_GET, 'docId');
    if (!empty($type) && !empty($docId)) { 
        Class_db::getInstance()->db_connect(); 
        if ($type == '1') {
            $result = Class_db::getInstance()->db_select_single('sys_upload', array('upload_id'=>$docId));
            if (!empty($result)) { 
                download_blob($result['upload_uplname'], $result['upload_blob_data']);
            }
        } else if ($type == '2') {
            $pdfId = Class_db::getInstance()->db_select_col('sem_certificate', array('certificate_id'=>$docId), 'pdf_id');
            if (!empty($pdfId)) { 
                $result = Class_db::getInstance()->db_select_single('sys_pdf', array('pdf_id'=>$pdfId));
                if (!empty($result)) { 
                    download($result['pdf_folder'].'/'.$result['pdf_filename']);
                }
            }            
        }
        Class_db::getInstance()->db_close();     
    }
} 
exit;

?>