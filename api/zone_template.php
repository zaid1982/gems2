<?php
// Dedicated endpoint for zone template download
// No output buffering, no logging, no JSON response

require_once 'class/Constant.php';
require_once 'class/General.php';
require_once 'class/DbMysql.php';
require_once 'class/Zone.php';
require_once '../vendor/autoload.php';

date_default_timezone_set("Asia/Kuala_Lumpur");

$fnMain = new Zone();

try {
    // Connect to database FIRST (needed for JWT check)
    DbMysql::connect();
    
    // Check JWT
    $fnMain->checkJwt(apache_request_headers());
    
    // Generate and stream template
    $fnMain->downloadTemplate();
    
} catch (Exception $e) {
    // If there's an error, send it as plain text
    header('Content-Type: text/plain');
    http_response_code(500);
    echo 'Error generating template: ' . $e->getMessage();
    exit;
}
